<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role('Intern');

$user = current_user();

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;

if (!$report_id) {
    header('Location: manage_reports.php');
    exit;
}

// Get report details - verify it belongs to this intern
$intern_name = esc($conn, $user['full_name']);
$intern_id = $user['id'];

$report = $conn->query("SELECT * FROM p1014_inventory_reports 
    WHERE report_id=$report_id AND (intern_id=$intern_id OR created_by='$intern_name')")->fetch_assoc();

if (!$report) {
    header('Location: manage_reports.php?error=Report not found or access denied');
    exit;
}

// Get report items
$items = $conn->query("SELECT i.*, p.drug_name AS item_name, p.manufacturer AS category, p.current_inventory AS reorder_level 
    FROM p1014_inventory_report_items i 
    JOIN product_inventory p ON i.product_id = p.product_id 
    WHERE i.report_id=$report_id 
    ORDER BY p.manufacturer, p.drug_name");

$status = $report['status'] ?: 'submitted';
$statusBadge = $status === 'approved' ? 'ls-badge-success' : 
              ($status === 'denied' ? 'ls-badge-danger' : 
              ($status === 'draft' ? 'ls-badge-secondary' : 'ls-badge-warning'));
?>
<?php navBar('Report Details'); ?>
<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-file-earmark-text" style="color:#3498db"></i> Report #<?= $report_id ?> Details
        </div>
        <a href="manage_reports.php" class="ls-btn ls-btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to My Reports
        </a>
    </div>

    <!-- Report Status Alert -->
    <?php if ($status === 'denied'): ?>
    <div class="ls-alert ls-alert-danger">
        <i class="bi bi-x-circle-fill"></i>
        <strong>This report was denied.</strong> Please review the feedback below and resubmit after corrections.
    </div>
    <?php elseif ($status === 'approved'): ?>
    <div class="ls-alert ls-alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <strong>This report has been approved!</strong> The technician can now process requisitions based on this report.
    </div>
    <?php elseif ($status === 'submitted'): ?>
    <div class="ls-alert ls-alert-warning">
        <i class="bi bi-clock-fill"></i>
        <strong>Pending Review.</strong> This report is waiting for technician review.
    </div>
    <?php endif; ?>

    <!-- Report Information -->
    <div class="ls-card">
        <div class="ls-card-header">
            <i class="bi bi-info-circle"></i> Report Information
        </div>
        <div class="ls-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Report ID</label>
                    <strong>#<?= $report['report_id'] ?></strong>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Report Date</label>
                    <strong><?= date('F d, Y', strtotime($report['report_date'])) ?></strong>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Ward</label>
                    <strong><?= htmlspecialchars($report['ward'] ?: 'N/A') ?></strong>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Status</label>
                    <span class="ls-badge <?= $statusBadge ?>"><?= strtoupper($status) ?></span>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Created By</label>
                    <strong><?= htmlspecialchars($report['created_by']) ?></strong>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Created At</label>
                    <strong><?= date('M d, Y H:i', strtotime($report['created_at'])) ?></strong>
                </div>
                <?php if ($report['reviewed_by']): ?>
                <div>
                    <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Reviewed By</label>
                    <strong><?= htmlspecialchars($report['reviewed_by']) ?></strong>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Reviewed At</label>
                    <strong><?= date('M d, Y H:i', strtotime($report['reviewed_at'])) ?></strong>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($report['remarks']): ?>
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:8px">Your Remarks</label>
                <p style="margin: 0;"><?= nl2br(htmlspecialchars($report['remarks'])) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($status === 'denied' && $report['denial_remarks']): ?>
            <div style="margin-top: 20px; padding: 15px; background: rgba(231, 76, 60, 0.1); border-left: 4px solid #e74c3c; border-radius: 6px;">
                <label style="font-size:0.8rem;color:#e74c3c;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:8px">
                    <i class="bi bi-exclamation-triangle-fill"></i> Denial Reason
                </label>
                <p style="margin: 0; color: #c0392b; font-weight: 500;"><?= nl2br(htmlspecialchars($report['denial_remarks'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Report Items -->
    <div class="ls-card" style="margin-top: 20px;">
        <div class="ls-card-header">
            <i class="bi bi-box-seam"></i> Inventory Items
        </div>
        <div class="ls-card-body-flush">
            <?php if ($items && $items->num_rows > 0): ?>
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Old Stock</th>
                        <th>New Stock</th>
                        <th>Sold</th>
                        <th>Balance</th>
                        <th>Stock on Hand</th>
                        <th>Expiration</th>
                        <th>Lot #</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                        <td><?= htmlspecialchars($item['category']) ?></td>
                        <td><?= $item['old_stock'] ?></td>
                        <td><?= $item['new_stock'] ?></td>
                        <td><?= $item['sold'] ?></td>
                        <td><?= $item['balance_stock'] ?></td>
                        <td>
                            <?php if ($item['stock_on_hand'] == 0): ?>
                                <span class="ls-badge ls-badge-danger"><?= $item['stock_on_hand'] ?></span>
                            <?php elseif ($item['stock_on_hand'] <= $item['reorder_level']): ?>
                                <span class="ls-badge ls-badge-warning"><?= $item['stock_on_hand'] ?></span>
                            <?php else: ?>
                                <?= $item['stock_on_hand'] ?>
                            <?php endif; ?>
                        </td>
                        <td><?= $item['expiration_date'] ? date('M d, Y', strtotime($item['expiration_date'])) : '-' ?></td>
                        <td><?= htmlspecialchars($item['lot_number'] ?: '-') ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="ls-empty">
                <i class="bi bi-inbox"></i>
                <p>No items in this report.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions -->
    <?php if ($status === 'denied'): ?>
    <div style="margin-top: 20px; text-align: center;">
        <a href="create_inventory_report.php?resubmit=<?= $report_id ?>" class="ls-btn ls-btn-success">
            <i class="bi bi-arrow-repeat"></i> Resubmit Corrected Report
        </a>
        <a href="manage_reports.php" class="ls-btn ls-btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to My Reports
        </a>
    </div>
    <?php endif; ?>
</div>

<?php $conn->close(); ?>
