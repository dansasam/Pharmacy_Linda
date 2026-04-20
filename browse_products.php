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

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($product_id > 0 && $quantity > 0) {
        // Check stock
        $stmt = $conn->prepare("SELECT drug_name, current_inventory FROM product_inventory WHERE product_id = ?");
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if ($product && $product['current_inventory'] >= $quantity) {
            cart_add($product_id, $quantity);
            
            // Log action
            $user_id = $_SESSION['user_id'];
            product_log($conn, $product_id, 'add_to_cart', $user_id, null, $quantity, 'Added to cart');
            
            $success = "Added {$quantity} x {$product['drug_name']} to cart!";
        } else {
            $error = "Insufficient stock or product not found.";
        }
    }
}

// Get search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Build query
$where = ["current_inventory > 0"]; // Only show in-stock items
$params = [];
$types = '';

if ($search) {
    $where[] = "(drug_name LIKE ? OR manufacturer LIKE ? OR generic_name LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($category) {
    $where[] = "manufacturer = ?";
    $params[] = $category;
    $types .= 's';
}

$where_clause = implode(' AND ', $where);

// Get products
$query = "SELECT * FROM product_inventory WHERE $where_clause ORDER BY drug_name ASC";
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get categories (manufacturers)
$categories = $conn->query("SELECT DISTINCT manufacturer FROM product_inventory WHERE manufacturer IS NOT NULL AND manufacturer != '' ORDER BY manufacturer ASC")->fetch_all(MYSQLI_ASSOC);

// Get cart count
$cart_count = cart_count();
?>
<?php navBar('Browse Products'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">
<style>
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.product-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    transition: all 0.2s;
}

.product-card:hover {
    border-color: #3498db;
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.1);
}

.product-name {
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
}

.product-manufacturer {
    font-size: 0.85rem;
    color: #64748b;
    margin-bottom: 8px;
}

.product-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0d9488;
    margin: 12px 0;
}

.product-stock {
    font-size: 0.85rem;
    margin-bottom: 12px;
}

.stock-good { color: #2ecc71; }
.stock-low { color: #f39c12; }
.stock-very-low { color: #e74c3c; }

.cart-badge {
    position: relative;
    display: inline-block;
}

.cart-badge .badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
}

.filter-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.filter-bar input, .filter-bar select {
    flex: 1;
    min-width: 200px;
}
</style>

<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-shop" style="color:#3498db"></i> Browse Products
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="cart.php" class="ls-btn ls-btn-primary cart-badge">
                <i class="bi bi-cart3"></i> View Cart
                <?php if ($cart_count > 0): ?>
                <span class="badge"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
            <a href="my_orders.php" class="ls-btn ls-btn-secondary">
                <i class="bi bi-receipt"></i> My Orders
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

    <!-- Search and Filter -->
    <div class="ls-card" style="margin-bottom: 20px;">
        <div class="ls-card-body">
            <form method="GET" class="filter-bar">
                <input type="text" name="search" class="ls-input" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                <select name="category" class="ls-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['manufacturer']) ?>" <?= $category === $cat['manufacturer'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['manufacturer']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="ls-btn ls-btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
                <?php if ($search || $category): ?>
                <a href="browse_products.php" class="ls-btn ls-btn-secondary">
                    <i class="bi bi-x"></i> Clear
                </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Products Grid -->
    <?php if (empty($products)): ?>
    <div class="ls-card">
        <div class="ls-card-body">
            <div class="ls-empty">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e1;"></i>
                <p style="margin-top: 12px; color: #64748b;">No products found.</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
        <?php
        $stock = (int)$product['current_inventory'];
        $stock_class = $stock <= 10 ? 'stock-very-low' : ($stock <= 30 ? 'stock-low' : 'stock-good');
        $stock_text = $stock <= 10 ? 'Very Low Stock' : ($stock <= 30 ? 'Low Stock' : 'In Stock');
        ?>
        <div class="product-card">
            <div class="product-name"><?= htmlspecialchars($product['drug_name']) ?></div>
            <div class="product-manufacturer"><?= htmlspecialchars($product['manufacturer']) ?></div>
            
            <?php if ($product['generic_name']): ?>
            <div style="font-size: 0.8rem; color: #94a3b8; margin-bottom: 8px;">
                Generic: <?= htmlspecialchars($product['generic_name']) ?>
            </div>
            <?php endif; ?>
            
            <div class="product-price">₱<?= number_format($product['unit_price'], 2) ?></div>
            
            <div class="product-stock <?= $stock_class ?>">
                <i class="bi bi-box-seam"></i> <?= $stock_text ?> (<?= $stock ?> available)
            </div>
            
            <?php if ($product['requires_prescription']): ?>
            <div style="margin-bottom: 12px;">
                <span class="ls-badge ls-badge-warning" style="font-size: 0.7rem;">
                    <i class="bi bi-file-medical"></i> Requires Prescription
                </span>
            </div>
            <?php endif; ?>
            
            <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                <input type="hidden" name="action" value="add_to_cart">
                <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                <input type="number" name="quantity" class="ls-input" value="1" min="1" max="<?= $stock ?>" style="width: 70px; padding: 6px 8px;">
                <button type="submit" class="ls-btn ls-btn-success" style="flex: 1;">
                    <i class="bi bi-cart-plus"></i> Add to Cart
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php $conn->close(); ?>
