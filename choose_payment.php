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

// PDO connection for notifications
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('PDO connection error: ' . htmlspecialchars($e->getMessage()));
}

$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;

if (!$sale_id) {
    header('Location: my_orders.php');
    exit;
}

// Get sale
$sale = sales_get_by_id($conn, $sale_id);

if (!$sale || $sale['customer_id'] != $_SESSION['user_id'] || $sale['payment_status'] !== 'pending') {
    header('Location: my_orders.php');
    exit;
}

// Get sale items
$items = sales_get_items($conn, $sale_id);

// Handle payment method selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (in_array($payment_method, ['cash', 'gcash', 'card'])) {
        // Update payment method
        $stmt = $conn->prepare("UPDATE sales SET payment_method = ? WHERE sale_id = ?");
        $stmt->bind_param('si', $payment_method, $sale_id);
        $stmt->execute();
        
        if ($payment_method === 'cash' || $payment_method === 'card') {
            // For cash/card, customer goes to counter
            $title = 'Please Proceed to Counter';
            $message = "Please proceed to the pharmacy counter to complete your payment for Order #" . str_pad($sale_id, 6, '0', STR_PAD_LEFT) . ". Total: ₱" . number_format($sale['total_amount'], 2);
            notify_customer($pdo, $_SESSION['user_id'], $title, $message);
            
            header('Location: my_orders.php?success=' . urlencode('Payment method selected. Please proceed to counter.'));
            exit;
            
        } elseif ($payment_method === 'gcash') {
            // Redirect to PayMongo checkout
            try {
                $success_url = 'http://' . $_SERVER['HTTP_HOST'] . '/Pharmacy_Linda/checkout_success.php?sale_id=' . $sale_id;
                $cancel_url = 'http://' . $_SERVER['HTTP_HOST'] . '/Pharmacy_Linda/checkout_cancel.php?sale_id=' . $sale_id;
                
                $paymongo_data = paymongo_create_session($sale_id, $items, $sale['total_amount'], $success_url, $cancel_url);
                
                // Save session ID
                sales_attach_paymongo($conn, $sale_id, $paymongo_data['session_id']);
                
                // Redirect to PayMongo
                header('Location: ' . $paymongo_data['checkout_url']);
                exit;
                
            } catch (Exception $e) {
                $error = 'PayMongo error: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Please select a valid payment method.';
    }
}
?>
<?php navBar('Choose Payment Method'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">

<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-credit-card" style="color:#3498db"></i> Choose Payment Method
        </div>
        <a href="/Pharmacy_Linda/my_orders.php" class="ls-btn ls-btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="ls-alert ls-alert-danger">
        <i class="bi bi-x-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 20px;">
        <!-- Left: Payment Methods -->
        <div>
            <div class="ls-card">
                <div class="ls-card-header">Select Payment Method</div>
                <div class="ls-card-body">
                    <form method="POST">
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            <!-- Cash Payment -->
                            <label style="display: flex; align-items: start; gap: 12px; padding: 16px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='#3498db'" onmouseout="this.style.borderColor='#e2e8f0'">
                                <input type="radio" name="payment_method" value="cash" required style="margin-top: 4px;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                        <i class="bi bi-cash-coin" style="font-size: 1.5rem; color: #2ecc71;"></i>
                                        <strong style="font-size: 1.1rem;">Cash Payment</strong>
                                    </div>
                                    <p style="margin: 0; color: #64748b; font-size: 0.9rem;">
                                        Pay at the pharmacy counter. Please bring exact amount or change will be provided.
                                    </p>
                                </div>
                            </label>

                            <!-- Card Payment -->
                            <label style="display: flex; align-items: start; gap: 12px; padding: 16px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='#3498db'" onmouseout="this.style.borderColor='#e2e8f0'">
                                <input type="radio" name="payment_method" value="card" required style="margin-top: 4px;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                        <i class="bi bi-credit-card" style="font-size: 1.5rem; color: #3498db;"></i>
                                        <strong style="font-size: 1.1rem;">Card Payment</strong>
                                    </div>
                                    <p style="margin: 0; color: #64748b; font-size: 0.9rem;">
                                        Pay using debit/credit card at the pharmacy counter. Card terminal available.
                                    </p>
                                </div>
                            </label>

                            <!-- GCash Payment -->
                            <label style="display: flex; align-items: start; gap: 12px; padding: 16px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor='#3498db'" onmouseout="this.style.borderColor='#e2e8f0'">
                                <input type="radio" name="payment_method" value="gcash" required style="margin-top: 4px;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                        <i class="bi bi-phone" style="font-size: 1.5rem; color: #007dff;"></i>
                                        <strong style="font-size: 1.1rem;">GCash / Online Payment</strong>
                                    </div>
                                    <p style="margin: 0; color: #64748b; font-size: 0.9rem;">
                                        Pay online using GCash, credit/debit card, or other payment methods via PayMongo.
                                    </p>
                                </div>
                            </label>
                        </div>

                        <div style="margin-top: 24px; display: flex; gap: 12px;">
                            <a href="/Pharmacy_Linda/my_orders.php" class="ls-btn ls-btn-secondary" style="flex: 1;">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="ls-btn ls-btn-success" style="flex: 2;">
                                <i class="bi bi-check-circle"></i> Proceed with Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Order Summary -->
        <div>
            <div class="ls-card" style="position: sticky; top: 20px;">
                <div class="ls-card-header">Order Summary</div>
                <div class="ls-card-body">
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">ORDER NUMBER</div>
                        <div style="font-weight: 700; font-size: 1.1rem; color: #3498db;">
                            #<?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?>
                        </div>
                    </div>

                    <div style="border-top: 1px solid #e2e8f0; padding-top: 16px; margin-bottom: 16px;">
                        <?php foreach ($items as $item): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($item['drug_name']) ?></div>
                                <div style="font-size: 0.85rem; color: #64748b;">
                                    <?= $item['quantity'] ?> × ₱<?= number_format($item['unit_price'], 2) ?>
                                </div>
                            </div>
                            <div style="font-weight: 600; color: #0d9488;">
                                ₱<?= number_format($item['line_total'], 2) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="border-top: 2px solid #e2e8f0; padding-top: 16px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-weight: 600;">Total Amount:</span>
                            <span style="font-size: 1.5rem; font-weight: 700; color: #0d9488;">
                                ₱<?= number_format($sale['total_amount'], 2) ?>
                            </span>
                        </div>
                    </div>

                    <div class="ls-alert ls-alert-info" style="font-size: 0.85rem; margin-top: 16px;">
                        <i class="bi bi-info-circle"></i>
                        <div>
                            Your order is ready. Please select a payment method to proceed.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); ?>
