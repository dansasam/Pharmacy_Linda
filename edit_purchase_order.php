<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role('Pharmacist');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

$req_id = isset($_GET['req_id']) ? (int)$_GET['req_id'] : 0;

if (!$req_id) {
    header('Location: requisition_approval.php');
    exit;
}

// Get requisition and PO details
$req = $conn->query("SELECT rq.*, po.po_id, po.po_number, po.supplier_name, po.delivery_date, po.approved_by 
    FROM p1014_requisition_requests rq 
    LEFT JOIN p1014_purchase_orders po ON rq.requisition_id = po.requisition_id 
    WHERE rq.requisition_id = $req_id AND rq.status = 'approved'")->fetch_assoc();

if (!$req) {
    header('Location: requisition_approval.php?error=Requisition not found or not approved');
    exit;
}

$po_id = $req['po_id'];

// Get requisition items
$items = $conn->query("SELECT ri.*, p.drug_name, p.manufacturer, p.unit as default_unit 
    FROM p1014_requisition_items ri 
    JOIN product_inventory p ON ri.product_id = p.product_id 
    WHERE ri.requisition_id = $req_id 
    ORDER BY p.manufacturer, p.drug_name");

$items_arr = [];
while ($row = $items->fetch_assoc()) {
    $items_arr[] = $row;
}

// Get all products for adding new items
$products = $conn->query("SELECT product_id, drug_name, manufacturer, unit FROM product_inventory ORDER BY manufacturer, drug_name");

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_name = esc($conn, $_POST['supplier_name'] ?? '');
    $delivery_date = esc($conn, $_POST['delivery_date'] ?? '');
    $approved_by = esc($conn, $_POST['approved_by'] ?? '');
    
    $conn->begin_transaction();
    try {
        // Update PO header
        $del_val = $delivery_date ? "'$delivery_date'" : "NULL";
        $conn->query("UPDATE p1014_purchase_orders 
            SET supplier_name='$supplier_name', delivery_date=$del_val, approved_by='$approved_by' 
            WHERE po_id=$po_id");
        
        // Delete old items
        $conn->query("DELETE FROM p1014_requisition_items WHERE requisition_id=$req_id");
        
        // Insert updated items
        $pids = $_POST['product_id'] ?? [];
        $qtys = $_POST['quantity'] ?? [];
        $units = $_POST['unit'] ?? [];
        $prices = $_POST['unit_price'] ?? [];
        $oos = $_POST['out_of_stock'] ?? [];
        
        $grand_total = 0;
        
        foreach ($pids as $idx => $pid) {
            $pid = (int)$pid;
            if ($pid <= 0) continue;
            
            $qty = (int)($qtys[$idx] ?? 0);
            $unit = esc($conn, $units[$idx] ?? 'PCS');
            $price = (float)($prices[$idx] ?? 0);
            $is_oos = isset($oos[$idx]) ? 1 : 0;
            
            if ($qty <= 0) continue;
            
            $line_total = $qty * $price;
            $grand_total += $line_total;
            
            $conn->query("INSERT INTO p1014_requisition_items 
                (requisition_id, product_id, quantity_requested, unit, unit_price, is_out_of_stock) 
                VALUES ($req_id, $pid, $qty, '$unit', $price, $is_oos)");
        }
        
        // Update totals
        $conn->query("UPDATE p1014_requisition_requests SET total_amount=$grand_total WHERE requisition_id=$req_id");
        $conn->query("UPDATE p1014_purchase_orders SET total_amount=$grand_total WHERE po_id=$po_id");
        
        $conn->commit();
        
        // Notify technicians about PO update
        $ris_number = $req['ris_number'] ?: "Requisition #$req_id";
        $techStmt = $pdo->prepare('SELECT id FROM users WHERE role = "Pharmacy Technician"');
        $techStmt->execute();
        $technicians = $techStmt->fetchAll();
        
        if ($technicians) {
            $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
            $title = 'Purchase Order Updated';
            $message = "Purchase Order {$req['po_number']} for $ris_number has been updated by $approved_by. New total: ₱" . number_format($grand_total, 2);
            
            foreach ($technicians as $tech) {
                $notifStmt->execute([$tech['id'], $title, $message]);
            }
        }
        
        header("Location: purchase_order.php?req_id=$req_id&success=PO updated successfully");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Failed to update PO: " . $e->getMessage();
    }
}
?>
<?php navBar('Edit Purchase Order'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">
<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-pencil-square" style="color:#3498db"></i> 
            Edit Purchase Order - <?= htmlspecialchars($req['po_number']) ?>
        </div>
        <a href="purchase_order.php?req_id=<?= $req_id ?>" class="ls-btn ls-btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to PO
        </a>
    </div>

    <?php if ($success): ?>
    <div class="ls-alert ls-alert-success">
        <i class="bi bi-check-circle-fill"></i> <?= $success ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="ls-alert ls-alert-danger">
        <i class="bi bi-x-circle-fill"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="ls-alert ls-alert-info" style="margin-bottom: 20px;">
        <i class="bi bi-info-circle"></i>
        <div>
            <strong>Edit Purchase Order:</strong> You can adjust quantities, prices, add or remove items. 
            The system will automatically recalculate totals. Changes will be saved and technicians will be notified.
        </div>
    </div>

    <form method="POST" id="poForm">
        <!-- PO Header -->
        <div class="ls-card" style="margin-bottom: 20px;">
            <div class="ls-card-header">
                <i class="bi bi-info-circle"></i> Purchase Order Information
            </div>
            <div class="ls-card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                    <div>
                        <label class="ls-label">PO Number</label>
                        <input type="text" class="ls-input" value="<?= htmlspecialchars($req['po_number']) ?>" readonly style="background: #f8fafc;">
                    </div>
                    <div>
                        <label class="ls-label">RIS Number</label>
                        <input type="text" class="ls-input" value="<?= htmlspecialchars($req['ris_number'] ?: '#' . $req_id) ?>" readonly style="background: #f8fafc;">
                    </div>
                    <div>
                        <label class="ls-label">Requested By</label>
                        <input type="text" class="ls-input" value="<?= htmlspecialchars($req['requested_by']) ?>" readonly style="background: #f8fafc;">
                    </div>
                    <div>
                        <label class="ls-label">Department</label>
                        <input type="text" class="ls-input" value="<?= htmlspecialchars($req['department'] ?: 'N/A') ?>" readonly style="background: #f8fafc;">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-top: 16px;">
                    <div>
                        <label class="ls-label">Approved By (Pharmacist) *</label>
                        <input type="text" name="approved_by" class="ls-input" value="<?= htmlspecialchars($req['approved_by']) ?>" required>
                    </div>
                    <div>
                        <label class="ls-label">Main Supplier Name</label>
                        <input type="text" name="supplier_name" class="ls-input" value="<?= htmlspecialchars($req['supplier_name'] ?: '') ?>" placeholder="Enter supplier name">
                    </div>
                    <div>
                        <label class="ls-label">Expected Delivery Date</label>
                        <input type="date" name="delivery_date" class="ls-input" value="<?= $req['delivery_date'] ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Items -->
        <div class="ls-card" style="margin-bottom: 20px;">
            <div class="ls-card-header">
                <i class="bi bi-box-seam"></i> Purchase Order Items
            </div>
            <div class="ls-card-body">
                <div id="items-container">
                    <?php foreach ($items_arr as $idx => $item): ?>
                    <div class="item-row" style="border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; margin-bottom: 12px; background: #ffffff;">
                        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
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
                                <label class="ls-label" style="font-size: 0.85rem;">Quantity</label>
                                <input type="number" name="quantity[]" class="ls-input qty-input" style="padding: 8px; font-size: 0.9rem;" value="<?= $item['quantity_requested'] ?>" min="1" required>
                            </div>
                            <div>
                                <label class="ls-label" style="font-size: 0.85rem;">Unit</label>
                                <select name="unit[]" class="ls-select" style="padding: 8px; font-size: 0.9rem;">
                                    <?php 
                                    $units = ['PCS', 'BOX', 'ROLL', 'BOTTLE', 'PACK', 'VIAL', 'TUBE', 'SACHET'];
                                    foreach ($units as $u): 
                                    ?>
                                    <option value="<?= $u ?>" <?= ($item['unit'] ?: $item['default_unit']) == $u ? 'selected' : '' ?>><?= $u ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="ls-label" style="font-size: 0.85rem;">Unit Price (₱)</label>
                                <input type="number" name="unit_price[]" class="ls-input price-input" style="padding: 8px; font-size: 0.9rem;" value="<?= $item['unit_price'] ?>" min="0" step="0.01" required>
                            </div>
                            <div>
                                <label class="ls-label" style="font-size: 0.85rem;">Total (₱)</label>
                                <input type="text" class="ls-input row-total" style="padding: 8px; font-size: 0.9rem; background: #f8fafc;" readonly value="₱0.00">
                            </div>
                            <div>
                                <button type="button" class="ls-btn ls-btn-sm" style="background: #ef4444; color: white; padding: 8px 12px; font-size: 0.85rem;" onclick="this.parentElement.parentElement.parentElement.remove(); calculateTotals();">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div style="margin-top: 8px;">
                            <label class="ls-label" style="font-size: 0.85rem;">
                                <input type="checkbox" name="out_of_stock[<?= $idx ?>]" <?= $item['is_out_of_stock'] ? 'checked' : '' ?>>
                                Out of Stock
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="ls-btn ls-btn-secondary" style="padding: 10px 16px; font-size: 0.9rem;" onclick="addItemRow()">
                    <i class="bi bi-plus-circle"></i> Add Another Item
                </button>
            </div>
        </div>

        <!-- Grand Total -->
        <div class="ls-card" style="margin-bottom: 20px;">
            <div class="ls-card-body" style="background: #f8fafc;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 1.1rem; font-weight: 600;">Grand Total:</div>
                    <div id="grandTotal" style="font-size: 1.5rem; font-weight: 700; color: #0d9488;">₱0.00</div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display: flex; gap: 12px; justify-content: flex-end; padding: 20px; background: #f8fafc; border-radius: 12px;">
            <a href="purchase_order.php?req_id=<?= $req_id ?>" class="ls-btn ls-btn-secondary" style="padding: 12px 24px;">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
            <button type="submit" class="ls-btn ls-btn-success" style="padding: 12px 24px; font-size: 1rem;">
                <i class="bi bi-check-circle"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<script>
// Calculate totals
function calculateTotals() {
    let grand = 0;
    document.querySelectorAll('#items-container .item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input')?.value) || 0;
        const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
        const total = qty * price;
        grand += total;
        
        const totalDisplay = row.querySelector('.row-total');
        if (totalDisplay) {
            totalDisplay.value = '₱' + total.toFixed(2);
        }
    });
    
    document.getElementById('grandTotal').textContent = '₱' + grand.toFixed(2);
}

// Add item row
function addItemRow() {
    const container = document.getElementById('items-container');
    const newRow = document.createElement('div');
    newRow.className = 'item-row';
    newRow.style.cssText = 'border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; margin-bottom: 12px; background: #ffffff;';
    newRow.innerHTML = `
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
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
                <label class="ls-label" style="font-size: 0.85rem;">Quantity</label>
                <input type="number" name="quantity[]" class="ls-input qty-input" style="padding: 8px; font-size: 0.9rem;" value="1" min="1" required>
            </div>
            <div>
                <label class="ls-label" style="font-size: 0.85rem;">Unit</label>
                <select name="unit[]" class="ls-select" style="padding: 8px; font-size: 0.9rem;">
                    <option value="PCS">PCS</option>
                    <option value="BOX">BOX</option>
                    <option value="ROLL">ROLL</option>
                    <option value="BOTTLE">BOTTLE</option>
                    <option value="PACK">PACK</option>
                    <option value="VIAL">VIAL</option>
                    <option value="TUBE">TUBE</option>
                    <option value="SACHET">SACHET</option>
                </select>
            </div>
            <div>
                <label class="ls-label" style="font-size: 0.85rem;">Unit Price (₱)</label>
                <input type="number" name="unit_price[]" class="ls-input price-input" style="padding: 8px; font-size: 0.9rem;" value="0" min="0" step="0.01" required>
            </div>
            <div>
                <label class="ls-label" style="font-size: 0.85rem;">Total (₱)</label>
                <input type="text" class="ls-input row-total" style="padding: 8px; font-size: 0.9rem; background: #f8fafc;" readonly value="₱0.00">
            </div>
            <div>
                <button type="button" class="ls-btn ls-btn-sm" style="background: #ef4444; color: white; padding: 8px 12px; font-size: 0.85rem;" onclick="this.parentElement.parentElement.parentElement.remove(); calculateTotals();">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <div style="margin-top: 8px;">
            <label class="ls-label" style="font-size: 0.85rem;">
                <input type="checkbox" name="out_of_stock[]">
                Out of Stock
            </label>
        </div>
    `;
    container.appendChild(newRow);
    
    // Attach event listeners to new inputs
    newRow.querySelectorAll('.qty-input, .price-input').forEach(input => {
        input.addEventListener('input', calculateTotals);
    });
    
    calculateTotals();
}

// Attach event listeners
document.querySelectorAll('.qty-input, .price-input').forEach(input => {
    input.addEventListener('input', calculateTotals);
});

// Initial calculation
calculateTotals();

// Confirmation before submit
document.getElementById('poForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const confirmMsg = 'Are you sure you want to save these changes to the Purchase Order?\n\n' +
                      'This will:\n' +
                      '✓ Update all item quantities and prices\n' +
                      '✓ Update the grand total\n' +
                      '✓ Notify technicians about the changes\n\n' +
                      'The updated PO will be ready for printing.';
    
    if (confirm(confirmMsg)) {
        this.submit();
    }
});
</script>

<?php $conn->close(); ?>
