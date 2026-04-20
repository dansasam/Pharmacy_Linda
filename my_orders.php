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

$customer_id = $_SESSION['user_id'];

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Get filter
$filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$where = ["s.customer_id = $customer_id"];
if ($filter === 'pending') {
    $where[] = "s.payment_status = 'pending'";
} elseif ($filter === 'paid') {
    $where[] = "s.payment_status = 'paid'";
} elseif ($filter === 'completed') {
    $where[] = "s.processed_by IS NOT NULL";
} elseif ($filter === 'cancelled') {
    $where[] = "s.payment_status IN ('cancelled', 'failed')";
}

$where_clause = 'WHERE ' . implode(' AND ', $where);

// Get orders
$query = "
    SELECT s.*, 
           p.full_name AS processed_by_name,
           COUNT(si.item_id) AS item_count
    FROM sales s
    LEFT JOIN users p ON s.processed_by = p.id
    LEFT JOIN sale_items si ON s.sale_id = si.sale_id
    $where_clause
    GROUP BY s.sale_id
    ORDER BY s.created_at DESC
";

$orders = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get counts
$cnt_all = $conn->query("SELECT COUNT(*) AS c FROM sales WHERE customer_id = $customer_id")->fetch_assoc()['c'];
$cnt_pending = $conn->query("SELECT COUNT(*) AS c FROM sales WHERE customer_id = $customer_id AND payment_status = 'pending'")->fetch_assoc()['c'];
$cnt_paid = $conn->query("SELECT COUNT(*) AS c FROM sales WHERE customer_id = $customer_id AND payment_status = 'paid'")->fetch_assoc()['c'];
$cnt_completed = $conn->query("SELECT COUNT(*) AS c FROM sales WHERE customer_id = $customer_id AND processed_by IS NOT NULL")->fetch_assoc()['c'];
?>
<?php navBar('My Orders'); ?>
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
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
}

.order-detail-value {
    font-weight: 600;
    color: #1e293b;
}
</style>

<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-receipt" style="color:#3498db"></i> My Orders
        </div>
        <a href="browse_products.php" class="ls-btn ls-btn-primary">
            <i class="bi bi-shop"></i> Continue Shopping
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

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
        <a href="?status=all" style="text-decoration:none">
            <div class="ls-stat">
                <div class="ls-stat-num" style="color:#3498db"><?= $cnt_all ?></div>
                <div class="ls-stat-label">All Orders</div>
            </div>
        </a>
        <a href="?status=pending" style="text-decoration:none">
            <div class="ls-stat">
                <div class="ls-stat-num" style="color:#f39c12"><?= $cnt_pending ?></div>
                <div class="ls-stat-label">Pending Payment</div>
            </div>
        </a>
        <a href="?status=paid" style="text-decoration:none">
            <div class="ls-stat">
                <div class="ls-stat-num" style="color:#3498db"><?= $cnt_paid ?></div>
                <div class="ls-stat-label">Being Prepared</div>
            </div>
        </a>
        <a href="?status=completed" style="text-decoration:none">
            <div class="ls-stat">
                <div class="ls-stat-num" style="color:#2ecc71"><?= $cnt_completed ?></div>
                <div class="ls-stat-label">Completed</div>
            </div>
        </a>
    </div>

    <!-- Filter -->
    <div class="ls-filter-bar" style="margin-bottom: 20px;">
        <a href="?status=all" class="ls-filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All Orders</a>
        <a href="?status=pending" class="ls-filter-btn <?= $filter === 'pending' ? 'active' : '' ?>">Pending Payment</a>
        <a href="?status=paid" class="ls-filter-btn <?= $filter === 'paid' ? 'active' : '' ?>">Being Prepared</a>
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
                <a href="browse_products.php" class="ls-btn ls-btn-primary" style="margin-top: 16px;">
                    <i class="bi bi-shop"></i> Start Shopping
                </a>
            </div>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($orders as $order): ?>
    <?php
    $status_badge = match($order['payment_status']) {
        'paid' => $order['processed_by'] ? 'ls-badge-success' : 'ls-badge-info',
        'pending' => 'ls-badge-warning',
        'cancelled', 'failed' => 'ls-badge-danger',
        default => 'ls-badge-secondary'
    };
    
    $status_text = match($order['payment_status']) {
        'paid' => $order['processed_by'] ? 'COMPLETED' : 'BEING PREPARED',
        'pending' => 'PENDING PAYMENT',
        'cancelled' => 'CANCELLED',
        'failed' => 'PAYMENT FAILED',
        default => strtoupper($order['payment_status'])
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
                <?= $status_text ?>
            </span>
        </div>

        <div class="order-details">
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
                <div class="order-detail-value"><?= strtoupper($order['payment_method']) ?></div>
            </div>

            <?php if ($order['processed_by_name']): ?>
            <div class="order-detail-item">
                <div class="order-detail-label">Dispensed By</div>
                <div class="order-detail-value"><?= htmlspecialchars($order['processed_by_name']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div style="display: flex; gap: 8px;">
            <a href="view_order.php?sale_id=<?= $order['sale_id'] ?>" class="ls-btn ls-btn-primary ls-btn-sm">
                <i class="bi bi-eye"></i> View Details
            </a>

            <?php if ($order['payment_status'] === 'pending'): ?>
            <a href="choose_payment.php?sale_id=<?= $order['sale_id'] ?>" class="ls-btn ls-btn-success ls-btn-sm">
                <i class="bi bi-credit-card"></i> Choose Payment Method
            </a>
            <?php endif; ?>

            <?php if ($order['payment_status'] === 'paid'): ?>
            <a href="receipt.php?sale_id=<?= $order['sale_id'] ?>" class="ls-btn ls-btn-secondary ls-btn-sm">
                <i class="bi bi-receipt"></i> View Receipt
            </a>
            <?php endif; ?>

            <?php if ($order['payment_status'] === 'pending'): ?>
            <div style="flex: 1; text-align: right;">
                <span style="font-size: 0.85rem; color: #f39c12;">
                    <i class="bi bi-clock"></i> Awaiting payment selection
                </span>
            </div>
            <?php elseif ($order['payment_status'] === 'paid' && !$order['processed_by']): ?>
            <div style="flex: 1; text-align: right;">
                <span style="font-size: 0.85rem; color: #3498db;">
                    <i class="bi bi-box-seam"></i> Being prepared for pickup
                </span>
            </div>
            <?php elseif ($order['processed_by']): ?>
            <div style="flex: 1; text-align: right;">
                <span style="font-size: 0.85rem; color: #2ecc71;">
                    <i class="bi bi-check-circle"></i> Ready for pickup
                </span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php $conn->close(); ?>
