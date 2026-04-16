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

$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;
$checkout_session_id = isset($_GET['checkout_session_id']) ? $_GET['checkout_session_id'] : '';

if (!$sale_id) {
    header('Location: browse_products.php');
    exit;
}

// Get sale
$sale = sales_get_by_id($conn, $sale_id);

if (!$sale || $sale['customer_id'] != $_SESSION['user_id']) {
    header('Location: browse_products.php');
    exit;
}

// If PayMongo payment, verify it
if (($sale['payment_method'] === 'gcash' || $sale['payment_method'] === 'card_online') && $sale['payment_status'] === 'pending') {
    $session_id = $checkout_session_id ?: $sale['paymongo_checkout_session_id'];
    
    if ($session_id) {
        $is_paid = paymongo_verify_payment($session_id);
        
        if ($is_paid) {
            // Mark as paid
            sales_update_status($conn, $sale_id, 'paid', $session_id);
            
            // Clear cart
            cart_clear();
            
            // Notify pharmacy assistants
            $customer_name = $_SESSION['full_name'] ?? 'Customer';
            notify_new_order($conn, $pdo, $sale_id, $customer_name, $sale['total_amount']);
            
            // Reload sale
            $sale = sales_get_by_id($conn, $sale_id);
        }
    }
}

$is_paid = $sale['payment_status'] === 'paid';
$is_pending = $sale['payment_status'] === 'pending';
?>
<?php navBar('Order Confirmation'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">

<div class="ls-page">
    <div style="max-width: 600px; margin: 40px auto;">
        <?php if ($is_paid): ?>
        <!-- Payment Successful -->
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="width: 80px; height: 80px; background: #d5f5e3; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="bi bi-check-circle-fill" style="font-size: 3rem; color: #2ecc71;"></i>
            </div>
            <h2 style="color: #1e293b; margin-bottom: 8px;">Payment Successful!</h2>
            <p style="color: #64748b; font-size: 1.1rem;">Your order has been confirmed</p>
        </div>

        <div class="ls-card" style="margin-bottom: 20px;">
            <div class="ls-card-body">
                <div style="text-align: center; padding: 20px 0;">
                    <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 4px;">Order Number</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: #3498db;">#<?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?></div>
                </div>
                
                <div style="border-top: 1px solid #e2e8f0; padding-top: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #64748b;">Total Amount:</span>
                        <span style="font-weight: 700; font-size: 1.1rem; color: #0d9488;">₱<?= number_format($sale['total_amount'], 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #64748b;">Payment Method:</span>
                        <span style="font-weight: 600;"><?= strtoupper($sale['payment_method']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Status:</span>
                        <span class="ls-badge ls-badge-success">PAID</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="ls-alert ls-alert-success">
            <i class="bi bi-info-circle"></i>
            <div>
                <strong>What's Next?</strong><br>
                Our pharmacy assistant will prepare your order. You will receive a notification when your order is ready for pickup.
            </div>
        </div>

        <?php elseif ($is_pending): ?>
        <!-- Payment Pending -->
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="width: 80px; height: 80px; background: #fff3cd; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="bi bi-clock-fill" style="font-size: 3rem; color: #f39c12;"></i>
            </div>
            <h2 style="color: #1e293b; margin-bottom: 8px;">Order Placed!</h2>
            <p style="color: #64748b; font-size: 1.1rem;">Awaiting payment confirmation</p>
        </div>

        <div class="ls-card" style="margin-bottom: 20px;">
            <div class="ls-card-body">
                <div style="text-align: center; padding: 20px 0;">
                    <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 4px;">Order Number</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: #3498db;">#<?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?></div>
                </div>
                
                <div style="border-top: 1px solid #e2e8f0; padding-top: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #64748b;">Total Amount:</span>
                        <span style="font-weight: 700; font-size: 1.1rem; color: #0d9488;">₱<?= number_format($sale['total_amount'], 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #64748b;">Payment Method:</span>
                        <span style="font-weight: 600;"><?= strtoupper($sale['payment_method']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #64748b;">Status:</span>
                        <span class="ls-badge ls-badge-warning">PENDING PAYMENT</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="ls-alert ls-alert-info">
            <i class="bi bi-info-circle"></i>
            <div>
                <strong>Payment Instructions:</strong><br>
                Please proceed to the pharmacy counter to complete your payment. Bring your order number for reference.
            </div>
        </div>
        <?php endif; ?>

        <div style="display: flex; gap: 12px; margin-top: 20px;">
            <a href="my_orders.php" class="ls-btn ls-btn-primary" style="flex: 1;">
                <i class="bi bi-receipt"></i> View My Orders
            </a>
            <a href="browse_products.php" class="ls-btn ls-btn-secondary" style="flex: 1;">
                <i class="bi bi-shop"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php $conn->close(); ?>
