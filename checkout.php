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

$success = $error = '';

// Get cart details
$cart_data = cart_get_details($conn);
$items = $cart_data['items'] ?? [];
$total = $cart_data['total'] ?? 0;

if (empty($items)) {
    header('Location: cart.php');
    exit;
}

// Check if prescription required
$has_prescription_items = false;
$prescription_required_products = [];
foreach ($items as $item) {
    if ($item['product']['requires_prescription']) {
        $has_prescription_items = true;
        $prescription_required_products[] = $item['product']['drug_name'];
    }
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    $prescription_id = isset($_POST['prescription_id']) ? (int)$_POST['prescription_id'] : null;
    
    // BLOCK checkout if prescription required but not provided
    if ($has_prescription_items && !$prescription_id) {
        $error = "Cannot checkout! The following items require a verified prescription: " . implode(', ', $prescription_required_products) . ". Please upload your prescription first.";
    } else {
        try {
            $customer_id = $_SESSION['user_id'];
            $customer_name = $_SESSION['full_name'] ?? 'Customer';
            
            // Create sale
            $sale_id = sales_create($conn, $customer_id, $payment_method, $prescription_id);
            
            // Add items
            $cart = cart_get_all();
            sales_add_items($conn, $sale_id, $cart);
            
            // Handle payment method
            if ($payment_method === 'gcash' || $payment_method === 'card_online') {
                // PayMongo checkout
                try {
                    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
                    $success_url = $base_url . "/Pharmacy_Linda/checkout_success.php?sale_id=" . $sale_id;
                    $cancel_url = $base_url . "/Pharmacy_Linda/checkout_cancel.php?sale_id=" . $sale_id;
                    
                    $paymongo_data = paymongo_create_session($sale_id, $items, $total, $success_url, $cancel_url);
                    
                    // Attach session to sale
                    sales_attach_paymongo($conn, $sale_id, $paymongo_data['session_id']);
                    
                    // Redirect to PayMongo
                    header('Location: ' . $paymongo_data['checkout_url']);
                    exit;
                    
                } catch (Exception $e) {
                    sales_update_status($conn, $sale_id, 'failed', 'paymongo_error');
                    $error = "Payment gateway error: " . $e->getMessage();
                }
            } else {
                // Cash/Card/PhilHealth - pending payment
                // Notify pharmacy assistants
                notify_new_order($conn, $pdo, $sale_id, $customer_name, $total);
                
                // Clear cart
                cart_clear();
                
                // Redirect to success
                header('Location: checkout_success.php?sale_id=' . $sale_id);
                exit;
            }
            
        } catch (Exception $e) {
            $error = "Checkout failed: " . $e->getMessage();
        }
    }
}

// Get customer's prescriptions
$customer_id = $_SESSION['user_id'];
$prescriptions = $conn->query("SELECT * FROM prescriptions WHERE customer_id = $customer_id AND status = 'verified' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<?php navBar('Checkout'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">

<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-credit-card" style="color:#3498db"></i> Checkout
        </div>
        <a href="cart.php" class="ls-btn ls-btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Cart
        </a>
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

    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 20px;">
        <!-- Left: Order Details & Payment -->
        <div>
            <!-- Order Items -->
            <div class="ls-card" style="margin-bottom: 20px;">
                <div class="ls-card-header">Order Items</div>
                <div class="ls-card-body">
                    <?php foreach ($items as $item): ?>
                    <div style="display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600;"><?= htmlspecialchars($item['product']['drug_name']) ?></div>
                            <div style="font-size: 0.85rem; color: #64748b;"><?= htmlspecialchars($item['product']['manufacturer']) ?></div>
                            <?php if ($item['product']['requires_prescription']): ?>
                            <span class="ls-badge ls-badge-warning" style="font-size: 0.7rem; margin-top: 4px;">
                                <i class="bi bi-file-medical"></i> Requires Prescription
                            </span>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.85rem; color: #64748b;">₱<?= number_format($item['unit_price'], 2) ?> × <?= $item['quantity'] ?></div>
                            <div style="font-weight: 700; color: #0d9488;">₱<?= number_format($item['line_total'], 2) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Payment Method -->
            <form method="POST" id="checkoutForm">
                <div class="ls-card" style="margin-bottom: 20px;">
                    <div class="ls-card-header">Payment Method</div>
                    <div class="ls-card-body">
                        <div style="display: grid; gap: 12px;">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="cash" required>
                                <div class="payment-label">
                                    <i class="bi bi-cash-coin" style="font-size: 1.5rem; color: #2ecc71;"></i>
                                    <div>
                                        <div style="font-weight: 600;">Cash</div>
                                        <div style="font-size: 0.85rem; color: #64748b;">Pay at pharmacy counter</div>
                                    </div>
                                </div>
                            </label>

                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="card" required>
                                <div class="payment-label">
                                    <i class="bi bi-credit-card" style="font-size: 1.5rem; color: #3498db;"></i>
                                    <div>
                                        <div style="font-weight: 600;">Card</div>
                                        <div style="font-size: 0.85rem; color: #64748b;">Pay at pharmacy counter</div>
                                    </div>
                                </div>
                            </label>

                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="gcash" required>
                                <div class="payment-label">
                                    <i class="bi bi-phone" style="font-size: 1.5rem; color: #0066ff;"></i>
                                    <div>
                                        <div style="font-weight: 600;">GCash</div>
                                        <div style="font-size: 0.85rem; color: #64748b;">Pay online via PayMongo</div>
                                    </div>
                                </div>
                            </label>

                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="card_online" required>
                                <div class="payment-label">
                                    <i class="bi bi-credit-card-2-front" style="font-size: 1.5rem; color: #f39c12;"></i>
                                    <div>
                                        <div style="font-weight: 600;">Card Online</div>
                                        <div style="font-size: 0.85rem; color: #64748b;">Pay online via PayMongo</div>
                                    </div>
                                </div>
                            </label>

                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="philhealth" required>
                                <div class="payment-label">
                                    <i class="bi bi-hospital" style="font-size: 1.5rem; color: #27ae60;"></i>
                                    <div>
                                        <div style="font-weight: 600;">PhilHealth</div>
                                        <div style="font-size: 0.85rem; color: #64748b;">Process at pharmacy counter</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Prescription Selection -->
                <?php if ($has_prescription_items): ?>
                <div class="ls-card" style="margin-bottom: 20px;">
                    <div class="ls-card-header">Prescription Required</div>
                    <div class="ls-card-body">
                        <?php if (empty($prescriptions)): ?>
                        <div class="ls-alert ls-alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <div>
                                <strong>No verified prescription found.</strong> 
                                Please <a href="prescription_upload.php" style="color: inherit; font-weight: 700;">upload your prescription</a> first.
                            </div>
                        </div>
                        <?php else: ?>
                        <label class="ls-label">Select Prescription *</label>
                        <select name="prescription_id" class="ls-select" required>
                            <option value="">Choose prescription...</option>
                            <?php foreach ($prescriptions as $rx): ?>
                            <option value="<?= $rx['prescription_id'] ?>">
                                <?= htmlspecialchars($rx['patient_name']) ?> - <?= htmlspecialchars($rx['physician_name']) ?> (<?= date('M d, Y', strtotime($rx['created_at'])) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div style="margin-top: 8px;">
                            <a href="prescription_upload.php" style="font-size: 0.85rem; color: #3498db;">
                                <i class="bi bi-plus-circle"></i> Upload new prescription
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" class="ls-btn ls-btn-success" style="width: 100%; padding: 14px; font-size: 1rem;">
                    <i class="bi bi-check-circle"></i> Place Order
                </button>
            </form>
        </div>

        <!-- Right: Order Summary -->
        <div>
            <div class="ls-card" style="position: sticky; top: 20px;">
                <div class="ls-card-header">Order Summary</div>
                <div class="ls-card-body">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #64748b;">Items (<?= count($items) ?>):</span>
                        <span>₱<?= number_format($total, 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #64748b;">Delivery:</span>
                        <span>FREE</span>
                    </div>
                    <div style="border-top: 2px solid #e2e8f0; margin: 16px 0;"></div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                        <span style="font-size: 1.1rem; font-weight: 600;">Total:</span>
                        <span style="font-size: 1.5rem; font-weight: 700; color: #0d9488;">₱<?= number_format($total, 2) ?></span>
                    </div>

                    <div class="ls-alert ls-alert-info" style="font-size: 0.85rem;">
                        <i class="bi bi-info-circle"></i>
                        <div>
                            <strong>Note:</strong> For cash/card payments, you will pay at the pharmacy counter when picking up your order.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.payment-option:hover {
    border-color: #3498db;
    background: #f8fafc;
}

.payment-option input[type="radio"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.payment-option input[type="radio"]:checked + .payment-label {
    color: #3498db;
}

.payment-option:has(input:checked) {
    border-color: #3498db;
    background: #eff6ff;
}

.payment-label {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}
</style>

<script>
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    
    if (!paymentMethod) {
        e.preventDefault();
        alert('Please select a payment method');
        return;
    }
    
    const method = paymentMethod.value;
    
    if (method === 'gcash' || method === 'card_online') {
        // PayMongo - will redirect
        return true;
    } else {
        // Confirm for counter payment
        const confirmMsg = 'Place order for counter payment?\n\n' +
                          'You will pay at the pharmacy counter when picking up your order.';
        
        if (!confirm(confirmMsg)) {
            e.preventDefault();
        }
    }
});
</script>

<?php $conn->close(); ?>
