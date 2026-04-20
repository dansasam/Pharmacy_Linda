<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role('Intern');

$user = current_user();

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

// Get all reports created by this intern
$intern_name = esc($conn, $user['full_name']);
$intern_id = $user['id'];

$allReports = $conn->query("SELECT r.*, 
    COUNT(i.item_id) as item_count,
    (SELECT COUNT(*) FROM p1014_inventory_report_items i2 
     WHERE i2.report_id = r.report_id AND i2.stock_on_hand = 0) as critical_items
    FROM p1014_inventory_reports r 
    LEFT JOIN p1014_inventory_report_items i ON r.report_id = i.report_id 
    WHERE r.intern_id = $intern_id OR r.created_by = '$intern_name'
    GROUP BY r.report_id 
    ORDER BY r.report_date DESC, r.report_id DESC");

// Count by status
$statusCounts = [
    'submitted' => 0,
    'approved' => 0,
    'denied' => 0,
    'draft' => 0
];

$allReports->data_seek(0);
while ($r = $allReports->fetch_assoc()) {
    $status = $r['status'] ?: 'submitted';
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}
$allReports->data_seek(0);
?>
<?php navBar('My Inventory Reports'); ?>
<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-clipboard-check" style="color:#3498db"></i> My Inventory Reports
        </div>
        <a href="create_inventory_report.php" class="ls-btn ls-btn-primary">
            <i class="bi bi-plus-circle"></i> Create New Report
        </a>
    </div>

    <!-- Status Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div class="ls-card" style="border-left: 4px solid #f39c12;">
            <div class="ls-card-body" style="text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; color: #f39c12;"><?= $statusCounts['submitted'] ?></div>
                <div style="color: #7f8c8d; font-size: 0.9rem;">Pending Review</div>
            </div>
        </div>
        <div class="ls-card" style="border-left: 4px solid #2ecc71;">
            <div class="ls-card-body" style="text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; color: #2ecc71;"><?= $statusCounts['approved'] ?></div>
                <div style="color: #7f8c8d; font-size: 0.9rem;">Approved</div>
            </div>
        </div>
        <div class="ls-card" style="border-left: 4px solid #e74c3c;">
            <div class="ls-card-body" style="text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; color: #e74c3c;"><?= $statusCounts['denied'] ?></div>
                <div style="color: #7f8c8d; font-size: 0.9rem;">Denied</div>
            </div>
        </div>
        <div class="ls-card" style="border-left: 4px solid #95a5a6;">
            <div class="ls-card-body" style="text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; color: #95a5a6;"><?= $statusCounts['draft'] ?></div>
                <div style="color: #7f8c8d; font-size: 0.9rem;">Drafts</div>
            </div>
        </div>
    </div>

    <!-- Reports Table -->
    <div class="ls-card">
        <div class="ls-card-header">
            <i class="bi bi-list-ul"></i> All My Reports
        </div>
        <div class="ls-card-body-flush">
            <?php if ($allReports && $allReports->num_rows > 0): ?>
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Date</th>
                        <th>Ward</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Reviewed By</th>
                        <th>Reviewed At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $allReports->data_seek(0);
                    while ($r = $allReports->fetch_assoc()): 
                        $status = $r['status'] ?: 'submitted';
                        $statusBadge = $status === 'approved' ? 'ls-badge-success' : 
                                      ($status === 'denied' ? 'ls-badge-danger' : 
                                      ($status === 'draft' ? 'ls-badge-secondary' : 'ls-badge-warning'));
                        
                        $rowStyle = '';
                        if ($status === 'denied') {
                            $rowStyle = 'style="background: rgba(231, 76, 60, 0.05);"';
                        } elseif ($status === 'approved') {
                            $rowStyle = 'style="background: rgba(46, 204, 113, 0.05);"';
                        }
                    ?>
                    <tr <?= $rowStyle ?>>
                        <td><strong>#<?= $r['report_id'] ?></strong></td>
                        <td><?= date('M d, Y', strtotime($r['report_date'])) ?></td>
                        <td><?= htmlspecialchars($r['ward'] ?: 'N/A') ?></td>
                        <td>
                            <span class="ls-badge ls-badge-info"><?= $r['item_count'] ?> items</span>
                            <?php if ($r['critical_items'] > 0): ?>
                                <span class="ls-badge ls-badge-danger"><?= $r['critical_items'] ?> critical</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="ls-badge <?= $statusBadge ?>">
                                <?= strtoupper($status) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($r['reviewed_by'] ?: '-') ?></td>
                        <td><?= $r['reviewed_at'] ? date('M d, Y H:i', strtotime($r['reviewed_at'])) : '-' ?></td>
                        <td>
                            <a href="view_report_details.php?report_id=<?= $r['report_id'] ?>" class="ls-btn ls-btn-sm ls-btn-primary">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <?php if ($status === 'denied'): ?>
                                <button type="button" class="ls-btn ls-btn-sm ls-btn-warning" onclick="showDenialReason(<?= $r['report_id'] ?>, '<?= htmlspecialchars(addslashes($r['denial_remarks'] ?: 'No reason provided'), ENT_QUOTES) ?>')">
                                    <i class="bi bi-exclamation-circle"></i> Why?
                                </button>
                                <a href="update_report.php?report_id=<?= $r['report_id'] ?>" class="ls-btn ls-btn-sm ls-btn-success">
                                    <i class="bi bi-pencil-square"></i> Update
                                </a>
                            <?php elseif ($status === 'submitted' || $status === 'draft'): ?>
                                <a href="update_report.php?report_id=<?= $r['report_id'] ?>" class="ls-btn ls-btn-sm ls-btn-secondary">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="ls-empty">
                <i class="bi bi-inbox"></i>
                <p>You haven't created any inventory reports yet.</p>
                <a href="create_inventory_report.php" class="ls-btn ls-btn-primary">
                    <i class="bi bi-plus-circle"></i> Create Your First Report
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Denied Reports Section -->
    <?php if ($statusCounts['denied'] > 0): ?>
    <div class="ls-card" style="margin-top: 20px; border-left: 4px solid #e74c3c;">
        <div class="ls-card-header" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;">
            <i class="bi bi-x-circle-fill"></i> Denied Reports - Action Required
        </div>
        <div class="ls-card-body">
            <p style="margin-bottom: 15px;">The following reports were denied and need your attention:</p>
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Date</th>
                        <th>Reviewed By</th>
                        <th>Denial Reason</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $allReports->data_seek(0);
                    while ($r = $allReports->fetch_assoc()): 
                        if ($r['status'] === 'denied'):
                    ?>
                    <tr>
                        <td><strong>#<?= $r['report_id'] ?></strong></td>
                        <td><?= date('M d, Y', strtotime($r['report_date'])) ?></td>
                        <td><?= htmlspecialchars($r['reviewed_by'] ?: 'Unknown') ?></td>
                        <td>
                            <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                <?= htmlspecialchars($r['denial_remarks'] ?: 'No reason provided') ?>
                            </div>
                        </td>
                        <td>
                            <a href="view_report_details.php?report_id=<?= $r['report_id'] ?>" class="ls-btn ls-btn-sm ls-btn-primary">
                                <i class="bi bi-eye"></i> Review
                            </a>
                            <a href="update_report.php?report_id=<?= $r['report_id'] ?>" class="ls-btn ls-btn-sm ls-btn-success">
                                <i class="bi bi-arrow-repeat"></i> Update & Resubmit
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endif;
                    endwhile; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Denial Reason Modal -->
<div id="denialModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div class="ls-card" style="width:500px;max-width:90%">
        <div class="ls-card-header" style="background: #e74c3c; color: white;">
            <i class="bi bi-x-circle-fill"></i> Report Denied - Reason
        </div>
        <div class="ls-card-body">
            <p id="denialReasonText" style="line-height: 1.6;"></p>
        </div>
        <div class="ls-card-footer">
            <button type="button" class="ls-btn ls-btn-secondary" onclick="document.getElementById('denialModal').style.display='none'">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function showDenialReason(reportId, reason) {
    document.getElementById('denialReasonText').innerHTML = '<strong>Report #' + reportId + ':</strong><br><br>' + reason;
    document.getElementById('denialModal').style.display = 'flex';
}
</script>

<?php $conn->close(); ?>
