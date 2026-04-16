<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role('Intern');

$user = current_user();
$intern_id = $user['id'];

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;

if (!$report_id) {
    header('Location: manage_reports.php?error=Invalid report ID');
    exit;
}

// Get report - verify it belongs to this intern
$intern_name = esc($conn, $user['full_name']);
$report = $conn->query("SELECT * FROM p1014_inventory_reports 
    WHERE report_id=$report_id AND (intern_id=$intern_id OR created_by='$intern_name')")->fetch_assoc();

if (!$report) {
    header('Location: manage_reports.php?error=Report not found or access denied');
    exit;
}

// Only allow editing if status is draft, submitted, or denied
if (!in_array($report['status'], ['draft', 'submitted', 'denied'])) {
    header('Location: manage_reports.php?error=Cannot edit approved reports');
    exit;
}

// Get existing report items
$existingItems = [];
$itemsResult = $conn->query("SELECT i.*, p.drug_name, p.manufacturer 
    FROM p1014_inventory_report_items i 
    JOIN product_inventory p ON i.product_id = p.product_id 
    WHERE i.report_id=$report_id");
while ($row = $itemsResult->fetch_assoc()) {
    $existingItems[] = $row;
}

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_date = esc($conn, $_POST['report_date']);
    $ward = esc($conn, $_POST['ward']);
    $remarks = esc($conn, $_POST['remarks'] ?? '');
    
    if (!$report_date || !$ward) {
        $error = "Please fill in all required fields.";
    } else {
        $conn->begin_transaction();
        try {
            // Update report
            $updateQuery = "UPDATE p1014_inventory_reports 
                SET report_date='$report_date', ward='$ward', remarks='$remarks', 
                    status='submitted', denial_remarks=NULL, reviewed_by=NULL, reviewed_at=NULL 
                WHERE report_id=$report_id";
            
            if (!$conn->query($updateQuery)) {
                throw new Exception("Failed to update report: " . $conn->error);
            }
            
            // Delete old items
            if (!$conn->query("DELETE FROM p1014_inventory_report_items WHERE report_id=$report_id")) {
                throw new Exception("Failed to delete old items: " . $conn->error);
            }
            
            // Insert updated items
            $pids = $_POST['product_id'] ?? [];
            $sold = $_POST['sold'] ?? [];
            $new_stock = $_POST['new_stock'] ?? [];
            $old_stock = $_POST['old_stock'] ?? [];
            $stock_on_hand = $_POST['stock_on_hand'] ?? [];
            $expiration_date = $_POST['expiration_date'] ?? [];
            $lot_number = $_POST['lot_number'] ?? [];
            $on_purchase_order = $_POST['on_purchase_order'] ?? [];
            $on_back_order = $_POST['on_back_order'] ?? [];
            $item_remarks = $_POST['item_remarks'] ?? [];
            
            foreach ($pids as $idx => $pid) {
                $pid = (int)$pid;
                if ($pid <= 0) continue;
                
                $s = (int)($sold[$idx] ?? 0);
                $ns = (int)($new_stock[$idx] ?? 0);
                $os = (int)($old_stock[$idx] ?? 0);
                $soh = (int)($stock_on_hand[$idx] ?? 0);
                $exp = esc($conn, $expiration_date[$idx] ?? '');
                $lot = esc($conn, $lot_number[$idx] ?? '');
                $po = (int)($on_purchase_order[$idx] ?? 0);
                $bo = (int)($on_back_order[$idx] ?? 0);
                $ir = esc($conn, $item_remarks[$idx] ?? '');
                
                $insertQuery = "INSERT INTO p1014_inventory_report_items 
                    (report_id, product_id, sold, new_stock, old_stock, stock_on_hand, expiration_date, lot_number, on_purchase_order, on_back_order, remarks) 
                    VALUES ($report_id, $pid, $s, $ns, $os, $soh, " . ($exp ? "'$exp'" : "NULL") . ", '$lot', $po, $bo, '$ir')";
                
                if (!$conn->query($insertQuery)) {
                    throw new Exception("Failed to insert item: " . $conn->error);
                }
            }
            
            $conn->commit();
            
            // Verify the update worked
            $checkResult = $conn->query("SELECT status FROM p1014_inventory_reports WHERE report_id=$report_id");
            $checkRow = $checkResult->fetch_assoc();
            
            if ($checkRow['status'] !== 'submitted') {
                throw new Exception("Status update verification failed. Current status: " . $checkRow['status']);
            }
            
            // Notify technicians about resubmission
            $techStmt = $pdo->prepare('SELECT id FROM users WHERE role = "Pharmacy Technician"');
            $techStmt->execute();
            $technicians = $techStmt->fetchAll();
            
            if ($technicians) {
                $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
                $title = 'Inventory Report Resubmitted';
                $message = "Report #$report_id has been updated and resubmitted by {$user['full_name']}. Please review the changes.";
                
                foreach ($technicians as $tech) {
                    $notifStmt->execute([$tech['id'], $title, $message]);
                }
            }
            
            $success = "Report updated and resubmitted successfully!";
            
            // Redirect after success
            header("Location: manage_reports.php?success=Report #$report_id updated and resubmitted");
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to update report: " . $e->getMessage();
        }
    }
}

// Get all products for dropdown
$products = $conn->query("SELECT product_id, drug_name, manufacturer, current_inventory FROM product_inventory ORDER BY manufacturer, drug_name");
?>
<?php navBar('Update Report'); ?>
<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-pencil-square" style="color:#3498db"></i> Update Report #<?= $report_id ?>
        </div>
        <a href="manage_reports.php" class="ls-btn ls-btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to My Reports
        </a>
    </div>

    <?php if ($success): ?>
    <div class="ls-alert ls-alert-success">
        <i class="bi bi-check-circle-fill"></i> <?= $success ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="ls-alert ls-alert-danger">
        <i class="bi bi-x-circle-fill"></i> <?= $error ?>
    </div>
    <?php endif; ?>

    <?php if ($report['status'] === 'denied' && $report['denial_remarks']): ?>
    <div class="ls-alert ls-alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <strong>This report was denied.</strong> Please address the following feedback:
            <p style="margin-top: 8px; font-weight: 500;"><?= nl2br(htmlspecialchars($report['denial_remarks'])) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="ls-card">
            <div class="ls-card-header">
                <i class="bi bi-info-circle"></i> Report Information
            </div>
            <div class="ls-card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                    <div>
                        <label class="ls-label">Report Date *</label>
                        <input type="date" name="report_date" class="ls-input" value="<?= htmlspecialchars($report['report_date']) ?>" required>
                    </div>
                    <div>
                        <label class="ls-label">Ward *</label>
                        <input type="text" name="ward" class="ls-input" value="<?= htmlspecialchars($report['ward']) ?>" required>
                    </div>
                </div>
                <div style="margin-top: 16px;">
                    <label class="ls-label">Remarks (Optional)</label>
                    <textarea name="remarks" class="ls-textarea" rows="3"><?= htmlspecialchars($report['remarks']) ?></textarea>
                </div>
            </div>
        </div>

    <div class="ls-card" style="margin-top: 20px;">
        <div class="ls-card-header">
            <i class="bi bi-box-seam"></i> Inventory Items
        </div>
        <div class="ls-card-body">
            <div id="items-container">
                <?php foreach ($existingItems as $idx => $item): ?>
                <div class="item-row" style="border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; margin-bottom: 12px; background: #ffffff;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">
                        <div>
                            <label class="ls-label" style="font-size: 0.85rem;">Product</label>
                            <select name="product_id[]" class="ls-select" style="padding: 8px; font-size: 0.9rem;" required>
                                <option value="">Select Product</option>
                                <?php 
                                $products->data_seek(0);
                                while ($p = $products->fetch_assoc()): 
                                ?>
                                <option value="<?= $p['product_id'] ?>" <?= $p['product_id'] == $item['product_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['drug_name']) ?> (<?= htmlspecialchars($p['manufacturer']) ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="ls-label" style="font-size: 0.85rem;">Old Stock</label>
                            <input type="number" name="old_stock[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="<?= $item['old_stock'] ?>" min="0">
                        </div>
                        <div>
                            <label class="ls-label" style="font-size: 0.85rem;">New Stock</label>
                            <input type="number" name="new_stock[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="<?= $item['new_stock'] ?>" min="0">
                        </div>
                        <div>
                            <label class="ls-label" style="font-size: 0.85rem;">Sold</label>
                            <input type="number" name="sold[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="<?= $item['sold'] ?>" min="0">
                        </div>
                        <div>
                            <label class="ls-label" style="font-size: 0.85rem;">Stock on Hand</label>
                            <input type="number" name="stock_on_hand[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="<?= $item['stock_on_hand'] ?>" min="0">
                        </div>
                        <div>
                            <label class="ls-label" style="font-size: 0.85rem;">Expiration</label>
                            <input type="date" name="expiration_date[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="<?= $item['expiration_date'] ?>">
                        </div>
                        <div>
                            <label class="ls-label" style="font-size: 0.85rem;">Lot #</label>
                            <input type="text" name="lot_number[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="<?= htmlspecialchars($item['lot_number']) ?>">
                        </div>
                        <div>
                            <label class="ls-label" style="font-size: 0.85rem;">On PO</label>
                            <input type="number" name="on_purchase_order[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="<?= $item['on_purchase_order'] ?>" min="0">
                        </div>
                        <div>
                            <label class="ls-label" style="font-size: 0.85rem;">On BO</label>
                            <input type="number" name="on_back_order[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="<?= $item['on_back_order'] ?>" min="0">
                        </div>
                    </div>
                    <div style="margin-top: 10px;">
                        <label class="ls-label" style="font-size: 0.85rem;">Item Remarks</label>
                        <input type="text" name="item_remarks[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="<?= htmlspecialchars($item['remarks']) ?>">
                    </div>
                    <button type="button" class="ls-btn ls-btn-sm" style="margin-top: 10px; background: #ef4444; color: white; padding: 6px 12px; font-size: 0.85rem;" onclick="this.parentElement.remove()">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="ls-btn ls-btn-secondary" style="padding: 10px 16px; font-size: 0.9rem;" onclick="addItemRow()">
                <i class="bi bi-plus-circle"></i> Add Another Item
            </button>
        </div>
    </div>

    <div style="margin-top: 20px; text-align: center; padding: 20px; background: #f8fafc; border-radius: 12px;">
        <button type="submit" class="ls-btn ls-btn-success" style="font-size: 1rem; padding: 12px 24px;">
            <i class="bi bi-check-circle"></i> Update & Resubmit Report
        </button>
        <a href="manage_reports.php" class="ls-btn ls-btn-secondary" style="padding: 12px 24px;">
            <i class="bi bi-x-circle"></i> Cancel
        </a>
    </div>
    </form>
</div>

<script>
// Confirmation before submit
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const reportId = <?= $report_id ?>;
    const confirmMsg = `Are you sure you want to update and resubmit Report #${reportId}?\n\n` +
                      `This will:\n` +
                      `✓ Update all report information\n` +
                      `✓ Change status to "Submitted"\n` +
                      `✓ Send notification to technician\n` +
                      `✓ Clear previous denial remarks\n\n` +
                      `The technician will review your updated report.`;
    
    if (confirm(confirmMsg)) {
        this.submit();
    }
});

function addItemRow() {
    const container = document.getElementById('items-container');
    const newRow = document.createElement('div');
    newRow.className = 'item-row';
    newRow.style.cssText = 'border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; margin-bottom: 12px; background: #ffffff;';
    newRow.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">
            <div>
                <label class="ls-label" style="font-size: 0.85rem;">Product</label>
                <select name="product_id[]" class="ls-select" style="padding: 8px; font-size: 0.9rem;" required>
                    <option value="">Select Product</option>
                    <?php 
                    $products->data_seek(0);
                    while ($p = $products->fetch_assoc()): 
                    ?>
                    <option value="<?= $p['product_id'] ?>">
                        <?= htmlspecialchars($p['drug_name']) ?> (<?= htmlspecialchars($p['manufacturer']) ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="ls-label" style="font-size: 0.85rem;">Old Stock</label>
                <input type="number" name="old_stock[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="0" min="0">
            </div>
            <div>
                <label class="ls-label" style="font-size: 0.85rem;">New Stock</label>
                <input type="number" name="new_stock[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="0" min="0">
            </div>
            <div>
                <label class="ls-label" style="font-size: 0.85rem;">Sold</label>
                <input type="number" name="sold[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="0" min="0">
            </div>
            <div>
                <label class="ls-label" style="font-size: 0.85rem;">Stock on Hand</label>
                <input type="number" name="stock_on_hand[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="0" min="0">
            </div>
            <div>
                <label class="ls-label" style="font-size: 0.85rem;">Expiration</label>
                <input type="date" name="expiration_date[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;">
            </div>
            <div>
                <label class="ls-label" style="font-size: 0.85rem;">Lot #</label>
                <input type="text" name="lot_number[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;">
            </div>
            <div>
                <label class="ls-label" style="font-size: 0.85rem;">On PO</label>
                <input type="number" name="on_purchase_order[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="0" min="0">
            </div>
            <div>
                <label class="ls-label" style="font-size: 0.85rem;">On BO</label>
                <input type="number" name="on_back_order[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;" value="0" min="0">
            </div>
        </div>
        <div style="margin-top: 10px;">
            <label class="ls-label" style="font-size: 0.85rem;">Item Remarks</label>
            <input type="text" name="item_remarks[]" class="ls-input" style="padding: 8px; font-size: 0.9rem;">
        </div>
        <button type="button" class="ls-btn ls-btn-sm" style="margin-top: 10px; background: #ef4444; color: white; padding: 6px 12px; font-size: 0.85rem;" onclick="this.parentElement.remove()">
            <i class="bi bi-trash"></i> Remove
        </button>
    `;
    container.appendChild(newRow);
}
</script>

<?php $conn->close(); ?>
