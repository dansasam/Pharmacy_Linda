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

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Get filter
$filter = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Build query
$where = [];
if ($filter === 'pending') {
    $where[] = "s.payment_status = 'pending'";
} elseif ($filter === 'paid') {
    $where[] = "s.payment_status = 'paid' AND s.processed_by IS NULL";
} elseif ($filter === 'completed') {
    $where[] = "s.processed_by IS NOT NULL";
} elseif ($filter === 'cancelled') {
    $where[] = "s.payment_status IN ('cancelled', 'failed')";
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get orders
$query = "
    SELECT s.*, u.full_name AS customer_name, u.email AS customer_email,
           p.full_name AS processed_by_name,
           COUNT(si.item_id) AS item_count
    FROM sales s
    JOIN users u ON s.customer_id = u.id
    LEFT JOIN users p ON s.processed_by = p.id
    LEFT JOIN sale_items si ON s.sale_id = si.sale_id
    $where_clause
    GROUP BY s.sale_id
    ORDER BY s.created_at DESC
";

$orders = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get counts
$cnt_pending = $conn->query("SELECT COUNT(*) AS c FROM sales WHERE payment_status = 'pending'")->fetch_assoc()['c'];
$cnt_paid = $conn->query("SELECT COUNT(*) AS c FROM sales WHERE payment_status = 'paid' AND processed_by IS NULL")->fetch_assoc()['c'];
$cnt_completed = $conn->query("SELECT COUNT(*) AS c FROM sales WHERE processed_by IS NOT NULL")->fetch_assoc()['c'];
$cnt_cancelled = $conn->query("SELECT COUNT(*) AS c FROM sales WHERE payment_status IN ('cancelled', 'failed')")->fetch_assoc()['c'];
?>
<?php navBar('Order Management'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">
<style>
.order-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.2s;
}

.order-card:hover {
    border-color: #3498db;
    box-shadow: 0 2px 8px rgba(52, 152, 219, 0.1);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e2e8f0;
}

.order-number {
    font-size: 1.1rem;
    font-weight: 700;
    color: #3498db;
}

.order-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-bottom: 12px;
}

.order-detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.order-detail-label {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.order-detail-value {
    font-weight: 600;
    color: #1e293b;
}

.order-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
</style>

<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-receipt" style="color:#3498db"></i> Order Management
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

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
        <a href="?status=pending" style="text-decoration:none">
            <div class="ls-stat">
                <div class="ls-stat-num" style="color:#f39c12"><?= $cnt_pending ?></div>
                <div class="ls-stat-label">Pending Payment</div>
            </div>
        </a>
        <a href="?status=paid" style="text-decoration:none">
            <div class="ls-stat">
                <div class="ls-stat-num" style="color:#3498db"><?= $cnt_paid ?></div>
                <div class="ls-stat-label">Ready to Dispense</div>
            </div>
        </a>
        <a href="?status=completed" style="text-decoration:none">
            <div class="ls-stat">
                <div class="ls-stat-num" style="color:#2ecc71"><?= $cnt_completed ?></div>
                <div class="ls-stat-label">Completed</div>
            </div>
        </a>
        <a href="?status=cancelled" style="text-decoration:none">
            <div class="ls-stat">
                <div class="ls-stat-num" style="color:#e74c3c"><?= $cnt_cancelled ?></div>
                <div class="ls-stat-label">Cancelled</div>
            </div>
        </a>
    </div>

    <!-- Filter -->
    <div class="ls-filter-bar" style="margin-bottom: 20px;">
        <a href="?status=pending" class="ls-filter-btn <?= $filter === 'pending' ? 'active' : '' ?>">Pending Payment</a>
        <a href="?status=paid" class="ls-filter-btn <?= $filter === 'paid' ? 'active' : '' ?>">Ready to Dispense</a>
        <a href="?status=completed" class="ls-filter-btn <?= $filter === 'completed' ? 'active' : '' ?>">Completed</a>
        <a href="?status=cancelled" class="ls-filter-btn <?= $filter === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
    </div>

    <!-- Orders List -->
    <?php if (empty($orders)): ?>
    <div class="ls-card">
        <div class="ls-card-body">
            <div class="ls-empty">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e1;"></i>
                <p style="margin-top: 12px; color: #64748b;">No orders found.</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($orders as $order): ?>
    <?php
    $status_badge = match($order['payment_status']) {
        'paid' => 'ls-badge-success',
        'pending' => 'ls-badge-warning',
        'cancelled', 'failed' => 'ls-badge-danger',
        default => 'ls-badge-secondary'
    };
    
    $payment_method_icon = match($order['payment_method']) {
        'cash' => 'bi-cash-coin',
        'card' => 'bi-credit-card',
        'gcash' => 'bi-phone',
        'card_online' => 'bi-credit-card-2-front',
        'philhealth' => 'bi-hospital',
        default => 'bi-wallet2'
    };
    ?>
    <div class="order-card">
        <div class="order-header">
            <div>
                <div class="order-number">Order #<?= str_pad($order['sale_id'], 6, '0', STR_PAD_LEFT) ?></div>
                <div style="font-size: 0.85rem; color: #64748b;">
                    <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?>
                </div>
            </div>
            <span class="ls-badge <?= $status_badge ?>">
                <?= strtoupper($order['payment_status']) ?>
            </span>
        </div>

        <div class="order-details">
            <div class="order-detail-item">
                <div class="order-detail-label">Customer</div>
                <div class="order-detail-value"><?= htmlspecialchars($order['customer_name']) ?></div>
            </div>

            <div class="order-detail-item">
                <div class="order-detail-label">Items</div>
                <div class="order-detail-value"><?= $order['item_count'] ?> item(s)</div>
            </div>

            <div class="order-detail-item">
                <div class="order-detail-label">Total Amount</div>
                <div class="order-detail-value" style="color: #0d9488;">₱<?= number_format($order['total_amount'], 2) ?></div>
            </div>

            <div class="order-detail-item">
                <div class="order-detail-label">Payment Method</div>
                <div class="order-detail-value">
                    <i class="<?= $payment_method_icon ?>"></i> <?= strtoupper($order['payment_method']) ?>
                </div>
            </div>

            <?php if ($order['processed_by_name']): ?>
            <div class="order-detail-item">
                <div class="order-detail-label">Processed By</div>
                <div class="order-detail-value"><?= htmlspecialchars($order['processed_by_name']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="order-actions">
            <a href="view_order.php?sale_id=<?= $order['sale_id'] ?>" class="ls-btn ls-btn-primary ls-btn-sm">
                <i class="bi bi-eye"></i> View Details
            </a>

            <?php if ($order['payment_status'] === 'pending' && ($order['payment_method'] === 'cash' || $order['payment_method'] === 'card' || $order['payment_method'] === 'philhealth')): ?>
            <a href="confirm_payment.php?sale_id=<?= $order['sale_id'] ?>" class="ls-btn ls-btn-success ls-btn-sm">
                <i class="bi bi-check-circle"></i> Confirm Payment
            </a>
            <?php endif; ?>

            <?php if ($order['payment_status'] === 'paid' && !$order['processed_by']): ?>
            <a href="assistant_dispense.php?sale_id=<?= $order['sale_id'] ?>" class="ls-btn ls-btn-success ls-btn-sm">
                <i class="bi bi-box-seam"></i> Dispense Order
            </a>
            <?php endif; ?>

            <?php if ($order['payment_status'] === 'paid'): ?>
            <a href="receipt.php?sale_id=<?= $order['sale_id'] ?>" class="ls-btn ls-btn-secondary ls-btn-sm">
                <i class="bi bi-receipt"></i> View Receipt
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php $conn->close(); ?>
