<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sales_helpers.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role('Customer');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

$success = $error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        if (cart_update($product_id, $quantity)) {
            $success = "Cart updated!";
        }
    } elseif ($action === 'remove') {
        $product_id = (int)$_POST['product_id'];
        
        if (cart_remove($product_id)) {
            // Log action
            $user_id = $_SESSION['user_id'];
            product_log($conn, $product_id, 'remove_from_cart', $user_id, null, null, 'Removed from cart');
            
            $success = "Item removed from cart!";
        }
    } elseif ($action === 'clear') {
        cart_clear();
        $success = "Cart cleared!";
    }
}

// Get cart details
$cart_data = cart_get_details($conn);
$items = $cart_data['items'] ?? [];
$total = $cart_data['total'] ?? 0;
$has_prescription_items = false;
$prescription_required_products = [];

foreach ($items as $item) {
    if ($item['product']['requires_prescription']) {
        $has_prescription_items = true;
        $prescription_required_products[] = $item['product']['drug_name'];
    }
}

// Get customer's verified prescriptions
$customer_id = $_SESSION['user_id'];
$prescriptions = $conn->query("SELECT * FROM prescriptions WHERE customer_id = $customer_id AND status = 'verified' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$has_verified_prescription = !empty($prescriptions);
?>
<?php navBar('Shopping Cart'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">

<div class="ls-page">
    <?php if ($has_prescription_items && !$has_verified_prescription): ?>
    <div class="ls-alert ls-alert-danger" style="margin-bottom: 20px;">
        <i class="bi bi-exclamation-triangle"></i>
        <div>
            <strong>⚠️ Prescription Required!</strong> 
            Your cart contains prescription medications: <strong><?= implode(', ', $prescription_required_products) ?></strong>. 
            You must <a href="/Pharmacy_Linda/prescription_upload.php" style="color: inherit; font-weight: 700; text-decoration: underline;">upload a prescription</a> before you can checkout.
        </div>
    </div>
    <?php endif; ?>
<style>
.cart-item {
    display: grid;
    grid-template-columns: 1fr 120px 120px 120px 80px;
    gap: 16px;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.cart-item:last-child {
    border-bottom: none;
}

.item-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.item-name {
    font-weight: 600;
    color: #1e293b;
}

.item-manufacturer {
    font-size: 0.85rem;
    color: #64748b;
}

.quantity-input {
    width: 80px;
    padding: 6px 8px;
}

@media (max-width: 768px) {
    .cart-item {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}
</style>

<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-cart3" style="color:#3498db"></i> Shopping Cart
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="browse_products.php" class="ls-btn ls-btn-secondary">
                <i class="bi bi-arrow-left"></i> Continue Shopping
            </a>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="ls-alert ls-alert-success">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="ls-alert ls-alert-danger">
        <i class="bi bi-x-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
    <!-- Empty Cart -->
    <div class="ls-card">
        <div class="ls-card-body">
            <div class="ls-empty">
                <i class="bi bi-cart-x" style="font-size: 3rem; color: #cbd5e1;"></i>
                <p style="margin-top: 12px; color: #64748b;">Your cart is empty.</p>
                <a href="browse_products.php" class="ls-btn ls-btn-primary" style="margin-top: 16px;">
                    <i class="bi bi-shop"></i> Browse Products
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Cart Items -->
    <div class="ls-card" style="margin-bottom: 20px;">
        <div class="ls-card-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>Cart Items (<?= count($items) ?>)</span>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="ls-btn ls-btn-danger ls-btn-sm" onclick="return confirm('Clear entire cart?')">
                        <i class="bi bi-trash"></i> Clear Cart
                    </button>
                </form>
            </div>
        </div>
        <div class="ls-card-body" style="padding: 0;">
            <?php foreach ($items as $item): ?>
            <div class="cart-item">
                <div class="item-info">
                    <div class="item-name"><?= htmlspecialchars($item['product']['drug_name']) ?></div>
                    <div class="item-manufacturer"><?= htmlspecialchars($item['product']['manufacturer']) ?></div>
                    <?php if ($item['product']['requires_prescription']): ?>
                    <span class="ls-badge ls-badge-warning" style="font-size: 0.7rem; width: fit-content;">
                        <i class="bi bi-file-medical"></i> Requires Prescription
                    </span>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Unit Price</div>
                    <div style="font-weight: 600;">₱<?= number_format($item['unit_price'], 2) ?></div>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Quantity</div>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                        <input type="number" name="quantity" class="ls-input quantity-input" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['product']['current_inventory'] ?>" onchange="this.form.submit()">
                    </form>
                </div>
                
                <div style="text-align: center;">
                    <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Total</div>
                    <div style="font-weight: 700; color: #0d9488;">₱<?= number_format($item['line_total'], 2) ?></div>
                </div>
                
                <div style="text-align: center;">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                        <button type="submit" class="ls-btn ls-btn-danger ls-btn-sm" onclick="return confirm('Remove this item?')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Prescription Warning -->
    <?php if ($has_prescription_items): ?>
    <div class="ls-alert ls-alert-warning" style="margin-bottom: 20px;">
        <i class="bi bi-exclamation-triangle"></i>
        <div>
            <strong>Prescription Required:</strong> Some items in your cart require a valid prescription. 
            You will need to upload your prescription during checkout.
        </div>
    </div>
    <?php endif; ?>

    <!-- Cart Summary -->
    <div class="ls-card">
        <div class="ls-card-header">Order Summary</div>
        <div class="ls-card-body">
            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                <span style="color: #64748b;">Subtotal:</span>
                <span style="font-weight: 600;">₱<?= number_format($total, 2) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; padding-top: 12px; border-top: 2px solid #e2e8f0;">
                <span style="font-size: 1.1rem; font-weight: 600;">Total:</span>
                <span style="font-size: 1.5rem; font-weight: 700; color: #0d9488;">₱<?= number_format($total, 2) ?></span>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <a href="browse_products.php" class="ls-btn ls-btn-secondary" style="flex: 1;">
                    <i class="bi bi-arrow-left"></i> Continue Shopping
                </a>
                <?php if ($has_prescription_items && !$has_verified_prescription): ?>
                <button type="button" class="ls-btn ls-btn-secondary" style="flex: 1; cursor: not-allowed;" disabled title="Upload prescription first">
                    <i class="bi bi-lock"></i> Checkout Blocked (Prescription Required)
                </button>
                <?php else: ?>
                <a href="/Pharmacy_Linda/checkout.php" class="ls-btn ls-btn-success" style="flex: 1;">
                    <i class="bi bi-credit-card"></i> Proceed to Checkout
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php $conn->close(); ?>
