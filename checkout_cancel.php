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

if ($sale_id) {
    // Mark as cancelled
    sales_update_status($conn, $sale_id, 'cancelled');
}
?>
<?php navBar('Payment Cancelled'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">

<div class="ls-page">
    <div style="max-width: 600px; margin: 40px auto;">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="width: 80px; height: 80px; background: #fde8e8; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="bi bi-x-circle-fill" style="font-size: 3rem; color: #e74c3c;"></i>
            </div>
            <h2 style="color: #1e293b; margin-bottom: 8px;">Payment Cancelled</h2>
            <p style="color: #64748b; font-size: 1.1rem;">Your order has been cancelled</p>
        </div>

        <div class="ls-alert ls-alert-info">
            <i class="bi bi-info-circle"></i>
            <div>
                Your payment was cancelled and no charges were made. Your cart items are still saved if you'd like to try again.
            </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 20px;">
            <a href="cart.php" class="ls-btn ls-btn-primary" style="flex: 1;">
                <i class="bi bi-cart3"></i> Return to Cart
            </a>
            <a href="browse_products.php" class="ls-btn ls-btn-secondary" style="flex: 1;">
                <i class="bi bi-shop"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>

<?php $conn->close(); ?>
