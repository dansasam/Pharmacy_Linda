<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role('Pharmacy Technician');

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Add timestamp to all queries to prevent MySQL query cache
$cache_buster = time();

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');
$success=$error='';

// Check if just approved
if (isset($_GET['approved'])) {
    $approved_id = (int)$_GET['approved'];
    $success = "Report #$approved_id has been approved! You can now create a requisition request below.";
}

// Check if just denied
if (isset($_GET['denied'])) {
    $denied_id = (int)$_GET['denied'];
    $success = "Report #$denied_id has been denied. The intern has been notified and can resubmit after corrections.";
}

// Handle approve/deny actions
if (isset($_GET['action']) && isset($_GET['report_id'])) {
    $report_id = (int)$_GET['report_id'];
    $action = $_GET['action'];
    $current_user = current_user();
    
    if ($action === 'approve') {
        // First verify the report exists and get intern_id
        $checkReport = $conn->query("SELECT report_id, status, intern_id, created_by FROM p1014_inventory_reports WHERE report_id=$report_id");
        
        if ($checkReport && $checkReport->num_rows > 0) {
            $currentReport = $checkReport->fetch_assoc();
            
            // Update the status
            $reviewer_name = esc($conn, $current_user['full_name']);
            $updateResult = $conn->query("UPDATE p1014_inventory_reports SET status='approved', reviewed_by='$reviewer_name', reviewed_at=NOW() WHERE report_id=$report_id");
            
            // Check if update was successful
            if ($updateResult) {
                // Send notification to intern
                if ($currentReport['intern_id']) {
                    $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
                    $title = 'Inventory Report Approved';
                    $message = "Your inventory report #$report_id has been approved by {$current_user['full_name']}. You can now proceed with requisition.";
                    $notifStmt->execute([$currentReport['intern_id'], $title, $message]);
                }
                
                // Add timestamp to prevent caching
                $timestamp = time();
                header("Location: view_inventory_report.php?approved=$report_id&t=$timestamp");
                exit;
            } else {
                $error = "Failed to approve report. Database error: " . $conn->error;
            }
        } else {
            $error = "Report #$report_id not found.";
        }
    }
}

// Handle deny with remarks
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['deny_report'])) {
    $report_id = (int)$_POST['report_id'];
    $deny_remarks = esc($conn, $_POST['deny_remarks']);
    $current_user = current_user();
    
    if (!$deny_remarks) {
        $error = "Please provide remarks explaining why the report is being denied.";
    } else {
        // Get intern_id before updating
        $reportInfo = $conn->query("SELECT intern_id, created_by FROM p1014_inventory_reports WHERE report_id=$report_id")->fetch_assoc();
        
        // Update status to denied
        $reviewer_name = esc($conn, $current_user['full_name']);
        $updateResult = $conn->query("UPDATE p1014_inventory_reports SET status='denied', denial_remarks='$deny_remarks', reviewed_by='$reviewer_name', reviewed_at=NOW() WHERE report_id=$report_id");
        
        if ($updateResult) {
            // Send notification to intern
            if ($reportInfo && $reportInfo['intern_id']) {
                $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
                $title = 'Inventory Report Denied';
                $message = "Your inventory report #$report_id has been denied by {$current_user['full_name']}. Reason: $deny_remarks. Please review and resubmit.";
                $notifStmt->execute([$reportInfo['intern_id'], $title, $message]);
            }
            
            $success = "Report #$report_id has been denied. The intern has been notified.";
            header("Location: view_inventory_report.php?denied=$report_id");
            exit;
        } else {
            $error = "Failed to deny report. Database error: " . $conn->error;
        }
    }
}

$rid=isset($_GET['report_id'])?(int)$_GET['report_id']:0;
$selected_report=null; $items_arr=[];
if ($rid && !isset($_GET['action'])) {
    $selected_report=$conn->query("SELECT * FROM p1014_inventory_reports WHERE report_id=$rid")->fetch_assoc();
    if ($selected_report) {
        $res=$conn->query("SELECT i.*,p.drug_name AS item_name,p.manufacturer AS category,p.current_inventory AS reorder_level FROM p1014_inventory_report_items i JOIN product_inventory p ON i.product_id=p.product_id WHERE i.report_id=$rid ORDER BY p.manufacturer,p.drug_name");
        while ($row=$res->fetch_assoc()) $items_arr[]=$row;
    }
}

// Get all reports (submitted, approved, denied) - handle NULL status
$allReports = $conn->query("SELECT r.*, COUNT(i.item_id) as item_count,
    (SELECT COUNT(*) FROM p1014_inventory_report_items i2 JOIN product_inventory p ON i2.product_id = p.product_id 
     WHERE i2.report_id = r.report_id AND (i2.stock_on_hand = 0 OR i2.stock_on_hand <= p.current_inventory)) as critical_items
    FROM p1014_inventory_reports r 
    LEFT JOIN p1014_inventory_report_items i ON r.report_id = i.report_id 
    WHERE r.status IN ('submitted', 'approved', 'denied') OR r.status IS NULL OR r.status = ''
    GROUP BY r.report_id 
    ORDER BY r.report_date DESC, r.report_id DESC");
?>
<?php navBar('View Inventory Reports'); ?>
<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title"><i class="bi bi-clipboard-data" style="color:#3498db"></i> View Inventory Reports</div>
    </div>

    <?php if ($success): ?><div class="ls-alert ls-alert-success"><i class="bi bi-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="ls-alert ls-alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if (!$rid): ?>
    
    <?php 
    // Get approved reports ready for requisition
    $approvedReports = $conn->query("SELECT r.*, COUNT(i.item_id) as item_count,
        (SELECT COUNT(*) FROM p1014_inventory_report_items i2 JOIN product_inventory p ON i2.product_id = p.product_id 
         WHERE i2.report_id = r.report_id AND (i2.stock_on_hand = 0 OR i2.stock_on_hand <= p.current_inventory)) as critical_items
        FROM p1014_inventory_reports r 
        LEFT JOIN p1014_inventory_report_items i ON r.report_id = i.report_id 
        WHERE r.status = 'approved'
        GROUP BY r.report_id 
        ORDER BY r.report_date DESC, r.report_id DESC");
    
    if ($approvedReports && $approvedReports->num_rows > 0):
    ?>
    <!-- Approved Reports Ready for Requisition -->
    <div class="ls-card" style="margin-bottom:20px;border-left:4px solid #2ecc71">
        <div class="ls-card-header" style="background:rgba(46,204,113,0.1);color:#2ecc71">
            <i class="bi bi-check-circle-fill"></i> Approved Reports - Ready for Requisition
        </div>
        <div class="ls-card-body-flush">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>Report #</th>
                        <th>Date</th>
                        <th>Ward</th>
                        <th>Submitted By</th>
                        <th style="text-align:center">Items</th>
                        <th style="text-align:center">Critical Items</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $approvedReports->data_seek(0); // Reset pointer
                    while ($r = $approvedReports->fetch_assoc()): 
                        $isNewlyApproved = isset($_GET['approved']) && (int)$_GET['approved'] === (int)$r['report_id'];
                        $rowClass = $isNewlyApproved ? 'style="background: rgba(46, 204, 113, 0.15);"' : '';
                    ?>
                        <tr <?= $rowClass ?>>
                            <td><strong>#<?= $r['report_id'] ?></strong></td>
                            <td><?= htmlspecialchars($r['report_date']) ?></td>
                            <td><?= htmlspecialchars($r['ward']) ?></td>
                            <td><?= htmlspecialchars($r['created_by']) ?></td>
                            <td style="text-align:center"><?= (int)$r['item_count'] ?></td>
                            <td style="text-align:center">
                                <?php if ($r['critical_items'] > 0): ?>
                                    <span class="ls-badge ls-badge-danger"><?= $r['critical_items'] ?></span>
                                <?php else: ?>
                                    <span class="ls-badge ls-badge-success">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="?report_id=<?= $r['report_id'] ?>" class="ls-btn ls-btn-primary ls-btn-sm">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="requisition_form.php?report_id=<?= $r['report_id'] ?>" class="ls-btn ls-btn-danger ls-btn-sm" style="font-weight:700">
                                    <i class="bi bi-cart-plus"></i> Create Request
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Show all submitted reports in a table -->
    <div class="ls-card" style="margin-bottom:20px">
        <div class="ls-card-header">All Inventory Reports (Submitted, Approved, Denied)</div>
        <div class="ls-card-body-flush">
            <?php if ($allReports && $allReports->num_rows > 0): ?>
                <table class="ls-table">
                    <thead>
                        <tr>
                            <th>Report #</th>
                            <th>Date</th>
                            <th>Ward</th>
                            <th>Submitted By</th>
                            <th style="text-align:center">Items</th>
                            <th style="text-align:center">Critical Items</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $allReports->fetch_assoc()): 
                            // Handle NULL or empty status
                            $status = $r['status'] ?: 'submitted';
                            $statusBadge = $status === 'approved' ? 'ls-badge-success' : ($status === 'denied' ? 'ls-badge-danger' : 'ls-badge-warning');
                            // Highlight newly approved report
                            $isNewlyApproved = isset($_GET['approved']) && (int)$_GET['approved'] === (int)$r['report_id'];
                            $rowClass = $isNewlyApproved ? 'style="background: rgba(46, 204, 113, 0.1); border-left: 4px solid #2ecc71;"' : '';
                        ?>
                            <tr <?= $rowClass ?>>
                                <td>#<?= $r['report_id'] ?></td>
                                <td><?= htmlspecialchars($r['report_date']) ?></td>
                                <td><?= htmlspecialchars($r['ward']) ?></td>
                                <td><?= htmlspecialchars($r['created_by']) ?></td>
                                <td style="text-align:center"><?= (int)$r['item_count'] ?></td>
                                <td style="text-align:center">
                                    <?php if ($r['critical_items'] > 0): ?>
                                        <span class="ls-badge ls-badge-danger"><?= $r['critical_items'] ?></span>
                                    <?php else: ?>
                                        <span class="ls-badge ls-badge-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="ls-badge <?= $statusBadge ?>"><?= strtoupper($status) ?></span></td>
                                <td>
                                    <a href="?report_id=<?= $r['report_id'] ?>" class="ls-btn ls-btn-primary ls-btn-sm">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <?php if ($status === 'approved'): ?>
                                        <a href="requisition_form.php?report_id=<?= $r['report_id'] ?>" class="ls-btn ls-btn-danger ls-btn-sm">
                                            <i class="bi bi-cart-plus"></i> Request
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="ls-empty"><i class="bi bi-inbox"></i> No submitted reports available.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($selected_report&&count($items_arr)>0): ?>
    <div style="margin-bottom:16px">
        <a href="view_inventory_report.php" class="ls-btn ls-btn-ghost ls-btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Reports List
        </a>
    </div>

    <div class="ls-card" style="margin-bottom:20px">
        <div class="ls-card-header">
            <span>Report #<?= $selected_report['report_id'] ?> — <?= htmlspecialchars($selected_report['ward']) ?> — <?= $selected_report['report_date'] ?></span>
        </div>
        <div class="ls-card-body">
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;background:#f8fafc;padding:16px;border-radius:8px;margin-bottom:20px">
                <div>
                    <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Report Date</label>
                    <div style="font-weight:600"><?= date('M d, Y', strtotime($selected_report['report_date'])) ?></div>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Ward</label>
                    <div style="font-weight:600"><?= htmlspecialchars($selected_report['ward']) ?></div>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Created By</label>
                    <div style="font-weight:600"><?= htmlspecialchars($selected_report['created_by']) ?></div>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Status</label>
                    <?php 
                        $statusBadge = $selected_report['status'] === 'approved' ? 'ls-badge-success' : ($selected_report['status'] === 'denied' ? 'ls-badge-danger' : 'ls-badge-warning');
                    ?>
                    <span class="ls-badge <?= $statusBadge ?>"><?= strtoupper($selected_report['status']) ?></span>
                </div>
            </div>
            <?php if ($selected_report['remarks']): ?>
            <div style="background:#f8fafc;padding:14px;border-radius:8px;margin-bottom:20px">
                <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:6px">Remarks</label>
                <div><?= nl2br(htmlspecialchars($selected_report['remarks'])) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ls-card" style="margin-bottom:20px">
        <div class="ls-card-header">Inventory Items</div>
        <div class="ls-card-body-flush">
            <table class="ls-table">
                <thead><tr>
                    <th>Drug Name</th><th>Manufacturer</th>
                    <th style="text-align:center">Sold</th>
                    <th style="text-align:center">New Stock</th>
                    <th style="text-align:center">Old Stock</th>
                    <th style="text-align:center">Balance Stock</th>
                    <th style="text-align:center">Physical Count</th>
                    <th>Expiry Date</th><th>Status</th>
                </tr></thead>
                <tbody>
                <?php foreach ($items_arr as $item):
                    if ($item['stock_on_hand']==0) $badge='<span class="ls-badge ls-badge-danger">Out of Stock</span>';
                    elseif ($item['stock_on_hand']<=$item['reorder_level']) $badge='<span class="ls-badge ls-badge-warning">Low Stock</span>';
                    else $badge='<span class="ls-badge ls-badge-success">OK</span>';
                    $rc = $item['stock_on_hand']==0 ? 'row-danger' : ($item['stock_on_hand']<=$item['reorder_level'] ? 'row-warn' : '');
                    // Handle both old and new schema
                    $sold = isset($item['sold']) ? (int)$item['sold'] : 0;
                    $new_stock = isset($item['new_stock']) ? (int)$item['new_stock'] : 0;
                    $old_stock = isset($item['old_stock']) ? (int)$item['old_stock'] : 0;
                    $balance_stock = isset($item['balance_stock']) ? (int)$item['balance_stock'] : 0;
                ?>
                    <tr class="<?= $rc ?>">
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td style="color:rgba(255,255,255,0.35)"><?= htmlspecialchars($item['category']) ?></td>
                        <td style="text-align:center"><?= $sold ?></td>
                        <td style="text-align:center"><?= $new_stock ?></td>
                        <td style="text-align:center"><?= $old_stock ?></td>
                        <td style="text-align:center;font-weight:700;color:<?= $balance_stock<0?'#e74c3c':($balance_stock==0?'#f1c40f':'#2ecc71') ?>">
                            <?= $balance_stock ?>
                        </td>
                        <td style="text-align:center;font-weight:700"><?= $item['stock_on_hand'] ?></td>
                        <td><?= $item['expiration_date']?date('M d, Y',strtotime($item['expiration_date'])):'—' ?></td>
                        <td><?= $badge ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="view_inventory_report.php" class="ls-btn ls-btn-ghost">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
        <?php if ($selected_report['status'] === 'submitted'): ?>
            <button type="button" class="ls-btn ls-btn-danger" onclick="document.getElementById('denyModal').style.display='flex'">
                <i class="bi bi-x-circle"></i> Deny Report
            </button>
            <a href="?report_id=<?= $selected_report['report_id'] ?>&action=approve" class="ls-btn ls-btn-success">
                <i class="bi bi-check-circle"></i> Approve Report
            </a>
        <?php elseif ($selected_report['status'] === 'approved'): ?>
            <a href="requisition_form.php?report_id=<?= $selected_report['report_id'] ?>" class="ls-btn ls-btn-danger">
                <i class="bi bi-cart-plus"></i> Create Requisition Request
            </a>
        <?php elseif ($selected_report['status'] === 'denied'): ?>
            <span class="ls-badge ls-badge-danger" style="padding:10px 16px;font-size:0.9rem">This report has been denied</span>
        <?php endif; ?>
    </div>

    <!-- Deny Modal -->
    <?php if ($selected_report['status'] === 'submitted'): ?>
    <div id="denyModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
        <div class="ls-card" style="width:500px;max-width:90%">
            <div class="ls-card-header">Deny Report #<?= $selected_report['report_id'] ?></div>
            <form method="POST">
                <input type="hidden" name="report_id" value="<?= $selected_report['report_id'] ?>">
                <input type="hidden" name="deny_report" value="1">
                <div class="ls-card-body">
                    <label class="ls-label">Remarks / Reason for Denial *</label>
                    <textarea name="deny_remarks" class="ls-textarea" rows="4" placeholder="Explain what needs to be corrected..." required></textarea>
                    <p style="color:#94a3b8;font-size:0.85rem;margin-top:8px">The intern will see these remarks and can resubmit the report.</p>
                </div>
                <div class="ls-card-body" style="display:flex;gap:12px;justify-content:flex-end;border-top:1px solid rgba(148,163,184,0.18)">
                    <button type="button" class="ls-btn ls-btn-ghost" onclick="document.getElementById('denyModal').style.display='none'">
                        Cancel
                    </button>
                    <button type="submit" class="ls-btn ls-btn-danger">
                        <i class="bi bi-x-circle"></i> Deny Report
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <?php elseif ($rid): ?>
        <div class="ls-alert ls-alert-info"><i class="bi bi-info-circle"></i> Report not found.</div>
        <a href="view_inventory_report.php" class="ls-btn ls-btn-primary">
            <i class="bi bi-arrow-left"></i> Back to Reports List
        </a>
    <?php endif; ?>
</div>
</body>
</html>
