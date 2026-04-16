<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sales_helpers.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role(['Pharmacy Assistant', 'Pharmacist Assistant', 'Pharmacist']);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

// PDO connection for notifications
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('PDO connection error: ' . htmlspecialchars($e->getMessage()));
}

$success = $error = '';
$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;

if (!$sale_id) {
    header('Location: assistant_orders.php');
    exit;
}

// Get sale
$sale = sales_get_by_id($conn, $sale_id);

if (!$sale || $sale['payment_status'] !== 'paid' || $sale['processed_by']) {
    header('Location: assistant_orders.php');
    exit;
}

// Get customer
$customer = $conn->query("SELECT * FROM users WHERE id = {$sale['customer_id']}")->fetch_assoc();

// Get items with stock info
$items = $conn->query("
    SELECT si.*, p.drug_name, p.manufacturer, p.current_inventory, p.unit
    FROM sale_items si
    JOIN product_inventory p ON si.product_id = p.product_id
    WHERE si.sale_id = $sale_id
    ORDER BY si.item_id
")->fetch_all(MYSQLI_ASSOC);

// Check stock availability
$stock_issues = [];
foreach ($items as $item) {
    if ($item['current_inventory'] < $item['quantity']) {
        $stock_issues[] = $item;
    }
}

// Handle dispensing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($stock_issues)) {
    try {
        $assistant_id = $_SESSION['user_id'];
        
        // Dispense products (deduct inventory)
        sales_dispense($conn, $sale_id, $assistant_id);
        
        // Notify customer
        notify_customer($pdo, $sale['customer_id'], 'Order Ready for Pickup', 
            "Your order #" . str_pad($sale_id, 6, '0', STR_PAD_LEFT) . " has been dispensed and is ready for pickup. Receipt: RCPT-" . date('Ymd') . "-" . str_pad($sale_id, 4, '0', STR_PAD_LEFT));
        
        header('Location: receipt.php?sale_id=' . $sale_id . '&success=Order dispensed successfully');
        exit;
        
    } catch (Exception $e) {
        $error = "Failed to dispense order: " . $e->getMessage();
    }
}
?>
<?php navBar('Dispense Order'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">

<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-box-seam" style="color:#2ecc71"></i> 
            Dispense Order #<?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?>
        </div>
        <a href="assistant_orders.php" class="ls-btn ls-btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($error): ?>
    <div class="ls-alert ls-alert-danger">
        <i class="bi bi-x-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($stock_issues)): ?>
    <div class="ls-alert ls-alert-danger">
        <i class="bi bi-exclamation-triangle"></i>
        <div>
            <strong>Insufficient Stock:</strong> Some items do not have enough stock to fulfill this order. 
            Please restock before dispensing.
        </div>
    </div>
    <?php endif; ?>

    <div style="max-width: 900px; margin: 0 auto;">
        <!-- Customer Info -->
        <div class="ls-card" style="margin-bottom: 20px;">
            <div class="ls-card-header">Customer Information</div>
            <div class="ls-card-body">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                    <div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Customer Name</div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($customer['full_name']) ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Email</div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($customer['email']) ?></div>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Payment Method</div>
                        <div style="font-weight: 600;"><?= strtoupper($sale['payment_method']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items to Dispense -->
        <div class="ls-card" style="margin-bottom: 20px;">
            <div class="ls-card-header">Items to Dispense</div>
            <div class="ls-card-body" style="padding: 0;">
                <table class="ls-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align: center;">Qty to Dispense</th>
                            <th style="text-align: center;">Current Stock</th>
                            <th style="text-align: center;">After Dispense</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <?php
                        $after_stock = $item['current_inventory'] - $item['quantity'];
                        $has_stock = $item['current_inventory'] >= $item['quantity'];
                        $stock_status = $has_stock ? 'Available' : 'Insufficient';
                        $stock_class = $has_stock ? 'ls-badge-success' : 'ls-badge-danger';
                        ?>
                        <tr style="<?= !$has_stock ? 'background: #fef2f2;' : '' ?>">
                            <td>
                                <div style="font-weight: 600;"><?= htmlspecialchars($item['drug_name']) ?></div>
                                <div style="font-size: 0.85rem; color: #64748b;">
                                    <?= htmlspecialchars($item['manufacturer']) ?>
                                </div>
                            </td>
                            <td style="text-align: center; font-weight: 700; font-size: 1.1rem;">
                                <?= $item['quantity'] ?> <?= htmlspecialchars($item['unit']) ?>
                            </td>
                            <td style="text-align: center; font-weight: 600;">
                                <?= $item['current_inventory'] ?> <?= htmlspecialchars($item['unit']) ?>
                            </td>
                            <td style="text-align: center; font-weight: 600; color: <?= $after_stock <= 10 ? '#e74c3c' : ($after_stock <= 30 ? '#f39c12' : '#2ecc71') ?>;">
                                <?= max(0, $after_stock) ?> <?= htmlspecialchars($item['unit']) ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="ls-badge <?= $stock_class ?>"><?= $stock_status ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Dispensing Instructions -->
        <div class="ls-alert ls-alert-info" style="margin-bottom: 20px;">
            <i class="bi bi-info-circle"></i>
            <div>
                <strong>Dispensing Instructions:</strong>
                <ol style="margin: 8px 0 0 20px; padding: 0;">
                    <li>Verify all items are physically available</li>
                    <li>Check expiry dates on all products</li>
                    <li>Package items properly</li>
                    <li>Confirm customer identity before handing over</li>
                    <li>System will automatically deduct inventory</li>
                </ol>
            </div>
        </div>

        <!-- Dispense Actions -->
        <form method="POST" id="dispenseForm">
            <div class="ls-card">
                <div class="ls-card-body">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <div>
                            <div style="font-size: 0.85rem; color: #64748b;">Total Amount</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #0d9488;">
                                ₱<?= number_format($sale['total_amount'], 2) ?>
                            </div>
                        </div>
                        <div>
                            <span class="ls-badge ls-badge-success" style="font-size: 1rem; padding: 8px 16px;">
                                PAID
                            </span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <a href="assistant_orders.php" class="ls-btn ls-btn-secondary" style="flex: 1;">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="ls-btn ls-btn-success" style="flex: 2;" <?= !empty($stock_issues) ? 'disabled' : '' ?>>
                            <i class="bi bi-check-circle"></i> Confirm Dispense & Update Inventory
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('dispenseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const confirmMsg = 'Confirm dispensing this order?\n\n' +
                      'This will:\n' +
                      '✓ Mark order as completed\n' +
                      '✓ Deduct items from inventory\n' +
                      '✓ Generate receipt\n' +
                      '✓ Notify customer\n\n' +
                      'This action cannot be undone.';
    
    if (confirm(confirmMsg)) {
        this.submit();
    }
});
</script>

<?php $conn->close(); ?>
