<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sales_helpers.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();

// Allow Customer and all assistant/pharmacist roles
$allowed_roles = ['Customer', 'Pharmacy Assistant', 'Pharmacist Assistant', 'Pharmacist'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: index.php');
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;

if (!$sale_id) {
    header('Location: ' . ($_SESSION['role'] === 'Customer' ? 'my_orders.php' : 'assistant_orders.php'));
    exit;
}

// Get sale
$sale = sales_get_by_id($conn, $sale_id);

if (!$sale) {
    header('Location: ' . ($_SESSION['role'] === 'Customer' ? 'my_orders.php' : 'assistant_orders.php'));
    exit;
}

// Check access - customers can only view their own orders
if ($_SESSION['role'] === 'Customer' && $sale['customer_id'] != $_SESSION['user_id']) {
    header('Location: my_orders.php');
    exit;
}

// Get customer info
$customer = $conn->query("SELECT * FROM users WHERE id = {$sale['customer_id']}")->fetch_assoc();

// Get items
$items = sales_get_items($conn, $sale_id);

// Get prescription if exists
$prescription = null;
if ($sale['prescription_id']) {
    $prescription = $conn->query("SELECT * FROM prescriptions WHERE prescription_id = {$sale['prescription_id']}")->fetch_assoc();
}

// Get processed by info
$processed_by = null;
if ($sale['processed_by']) {
    $processed_by = $conn->query("SELECT * FROM users WHERE id = {$sale['processed_by']}")->fetch_assoc();
}
?>
<?php navBar('Order Details'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">

<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-receipt" style="color:#3498db"></i> 
            Order #<?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?>
        </div>
        <a href="<?= $_SESSION['role'] === 'Customer' ? 'my_orders.php' : 'assistant_orders.php' ?>" class="ls-btn ls-btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 20px;">
        <!-- Left: Order Details -->
        <div>
            <!-- Order Info -->
            <div class="ls-card" style="margin-bottom: 20px;">
                <div class="ls-card-header">Order Information</div>
                <div class="ls-card-body">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Order Number</div>
                            <div style="font-weight: 700; font-size: 1.1rem; color: #3498db;">
                                #<?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?>
                            </div>
                        </div>

                        <div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Order Date</div>
                            <div style="font-weight: 600;">
                                <?= date('M d, Y h:i A', strtotime($sale['created_at'])) ?>
                            </div>
                        </div>

                        <div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Customer</div>
                            <div style="font-weight: 600;"><?= htmlspecialchars($customer['full_name']) ?></div>
                            <div style="font-size: 0.85rem; color: #64748b;"><?= htmlspecialchars($customer['email']) ?></div>
                        </div>

                        <div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Payment Method</div>
                            <div style="font-weight: 600;"><?= strtoupper($sale['payment_method']) ?></div>
                        </div>

                        <?php if ($processed_by): ?>
                        <div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Processed By</div>
                            <div style="font-weight: 600;"><?= htmlspecialchars($processed_by['full_name']) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($sale['payment_reference']): ?>
                        <div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Payment Reference</div>
                            <div style="font-weight: 600; font-size: 0.85rem; font-family: monospace;">
                                <?= htmlspecialchars($sale['payment_reference']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="ls-card" style="margin-bottom: 20px;">
                <div class="ls-card-header">Order Items</div>
                <div class="ls-card-body" style="padding: 0;">
                    <table class="ls-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style="text-align: center;">Quantity</th>
                                <th style="text-align: right;">Unit Price</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($item['drug_name']) ?></div>
                                    <div style="font-size: 0.85rem; color: #64748b;">
                                        <?= htmlspecialchars($item['manufacturer']) ?>
                                    </div>
                                </td>
                                <td style="text-align: center;"><?= $item['quantity'] ?></td>
                                <td style="text-align: right;">₱<?= number_format($item['unit_price'], 2) ?></td>
                                <td style="text-align: right; font-weight: 700; color: #0d9488;">
                                    ₱<?= number_format($item['line_total'], 2) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right; font-weight: 600;">Total:</td>
                                <td style="text-align: right; font-weight: 700; font-size: 1.1rem; color: #0d9488;">
                                    ₱<?= number_format($sale['total_amount'], 2) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Prescription Info -->
            <?php if ($prescription): ?>
            <div class="ls-card">
                <div class="ls-card-header">Prescription Information</div>
                <div class="ls-card-body">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                        <div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Patient Name</div>
                            <div style="font-weight: 600;"><?= htmlspecialchars($prescription['patient_name']) ?></div>
                        </div>

                        <div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Physician</div>
                            <div style="font-weight: 600;"><?= htmlspecialchars($prescription['physician_name']) ?></div>
                        </div>

                        <div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">Status</div>
                            <span class="ls-badge ls-badge-success"><?= strtoupper($prescription['status']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: Status & Actions -->
        <div>
            <div class="ls-card" style="position: sticky; top: 20px;">
                <div class="ls-card-header">Order Status</div>
                <div class="ls-card-body">
                    <?php
                    $status_badge = match($sale['payment_status']) {
                        'paid' => 'ls-badge-success',
                        'pending' => 'ls-badge-warning',
                        'cancelled', 'failed' => 'ls-badge-danger',
                        default => 'ls-badge-secondary'
                    };
                    ?>
                    <div style="text-align: center; padding: 20px 0;">
                        <span class="ls-badge <?= $status_badge ?>" style="font-size: 1rem; padding: 8px 16px;">
                            <?= strtoupper($sale['payment_status']) ?>
                        </span>
                    </div>

                    <?php if ($sale['payment_status'] === 'pending'): ?>
                    <div class="ls-alert ls-alert-warning" style="font-size: 0.85rem;">
                        <i class="bi bi-clock"></i>
                        <div>Awaiting payment confirmation. Please proceed to the pharmacy counter to complete payment.</div>
                    </div>
                    <?php elseif ($sale['payment_status'] === 'paid' && !$sale['processed_by']): ?>
                    <div class="ls-alert ls-alert-info" style="font-size: 0.85rem;">
                        <i class="bi bi-box-seam"></i>
                        <div>Payment confirmed. Your order is being prepared for dispensing.</div>
                    </div>
                    <?php elseif ($sale['processed_by']): ?>
                    <div class="ls-alert ls-alert-success" style="font-size: 0.85rem;">
                        <i class="bi bi-check-circle"></i>
                        <div>Order completed and dispensed. Thank you!</div>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top: 16px; display: flex; flex-direction: column; gap: 8px;">
                        <?php if (in_array($_SESSION['role'], ['Pharmacy Assistant', 'Pharmacist Assistant', 'Pharmacist'])): ?>
                            <?php if ($sale['payment_status'] === 'pending' && ($sale['payment_method'] === 'cash' || $sale['payment_method'] === 'card' || $sale['payment_method'] === 'philhealth')): ?>
                            <a href="confirm_payment.php?sale_id=<?= $sale_id ?>" class="ls-btn ls-btn-success" style="width: 100%;">
                                <i class="bi bi-check-circle"></i> Confirm Payment
                            </a>
                            <?php endif; ?>

                            <?php if ($sale['payment_status'] === 'paid' && !$sale['processed_by']): ?>
                            <a href="assistant_dispense.php?sale_id=<?= $sale_id ?>" class="ls-btn ls-btn-success" style="width: 100%;">
                                <i class="bi bi-box-seam"></i> Dispense Order
                            </a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($sale['payment_status'] === 'paid'): ?>
                        <a href="receipt.php?sale_id=<?= $sale_id ?>" class="ls-btn ls-btn-primary" style="width: 100%;">
                            <i class="bi bi-receipt"></i> View Receipt
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); ?>
