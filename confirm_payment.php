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

if (!$sale || $sale['payment_status'] !== 'pending') {
    header('Location: assistant_orders.php');
    exit;
}

// Get customer
$customer = $conn->query("SELECT * FROM users WHERE id = {$sale['customer_id']}")->fetch_assoc();

// Get items
$items = sales_get_items($conn, $sale_id);

// Handle confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_reference = esc($conn, $_POST['payment_reference'] ?? '');
    $notes = esc($conn, $_POST['notes'] ?? '');
    
    try {
        // Mark as paid
        sales_update_status($conn, $sale_id, 'paid', $payment_reference);
        
        // Add notes if provided
        if ($notes) {
            $conn->query("UPDATE sales SET notes = '$notes' WHERE sale_id = $sale_id");
        }
        
        // Notify customer
        notify_customer($pdo, $sale['customer_id'], 'Payment Confirmed', 
            "Your payment for Order #" . str_pad($sale_id, 6, '0', STR_PAD_LEFT) . " has been confirmed. Your order is being prepared.");
        
        header('Location: assistant_dispense.php?sale_id=' . $sale_id);
        exit;
        
    } catch (Exception $e) {
        $error = "Failed to confirm payment: " . $e->getMessage();
    }
}
?>
<?php navBar('Confirm Payment'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">

<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-check-circle" style="color:#2ecc71"></i> 
            Confirm Payment - Order #<?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?>
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

    <div style="max-width: 800px; margin: 0 auto;">
        <!-- Order Summary -->
        <div class="ls-card" style="margin-bottom: 20px;">
            <div class="ls-card-header">Order Summary</div>
            <div class="ls-card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Customer</div>
                        <div style="font-weight: 600;"><?= htmlspecialchars($customer['full_name']) ?></div>
                        <div style="font-size: 0.85rem; color: #64748b;"><?= htmlspecialchars($customer['email']) ?></div>
                    </div>

                    <div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Payment Method</div>
                        <div style="font-weight: 600; font-size: 1.1rem;">
                            <?= strtoupper($sale['payment_method']) ?>
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px solid #e2e8f0; padding-top: 16px;">
                    <table style="width: 100%;">
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td style="padding: 8px 0;">
                                <div style="font-weight: 600;"><?= htmlspecialchars($item['drug_name']) ?></div>
                                <div style="font-size: 0.85rem; color: #64748b;">
                                    <?= htmlspecialchars($item['manufacturer']) ?> × <?= $item['quantity'] ?>
                                </div>
                            </td>
                            <td style="text-align: right; padding: 8px 0; font-weight: 700;">
                                ₱<?= number_format($item['line_total'], 2) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="border-top: 2px solid #e2e8f0;">
                            <td style="padding: 12px 0; font-size: 1.1rem; font-weight: 600;">Total:</td>
                            <td style="text-align: right; padding: 12px 0; font-size: 1.5rem; font-weight: 700; color: #0d9488;">
                                ₱<?= number_format($sale['total_amount'], 2) ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Confirmation Form -->
        <form method="POST" id="confirmForm">
            <div class="ls-card" style="margin-bottom: 20px;">
                <div class="ls-card-header">Payment Confirmation</div>
                <div class="ls-card-body">
                    <div style="margin-bottom: 16px;">
                        <label class="ls-label">Payment Reference / Receipt Number</label>
                        <input type="text" name="payment_reference" class="ls-input" 
                               placeholder="e.g., OR-123456, Cash Receipt #789" required>
                        <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">
                            Enter the official receipt number or payment reference
                        </div>
                    </div>

                    <div>
                        <label class="ls-label">Notes (Optional)</label>
                        <textarea name="notes" class="ls-input" rows="3" 
                                  placeholder="Any additional notes about the payment..."></textarea>
                    </div>
                </div>
            </div>

            <div class="ls-alert ls-alert-warning" style="margin-bottom: 20px;">
                <i class="bi bi-exclamation-triangle"></i>
                <div>
                    <strong>Confirm Payment Receipt:</strong> By confirming, you acknowledge that you have received 
                    <strong>₱<?= number_format($sale['total_amount'], 2) ?></strong> from the customer via 
                    <strong><?= strtoupper($sale['payment_method']) ?></strong>.
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <a href="assistant_orders.php" class="ls-btn ls-btn-secondary" style="flex: 1;">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button type="submit" class="ls-btn ls-btn-success" style="flex: 1;">
                    <i class="bi bi-check-circle"></i> Confirm Payment Received
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('confirmForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const confirmMsg = 'Confirm that payment has been received?\n\n' +
                      'Amount: ₱<?= number_format($sale['total_amount'], 2) ?>\n' +
                      'Method: <?= strtoupper($sale['payment_method']) ?>\n\n' +
                      'This will mark the order as paid and ready for dispensing.';
    
    if (confirm(confirmMsg)) {
        this.submit();
    }
});
</script>

<?php $conn->close(); ?>
