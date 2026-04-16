<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_once __DIR__ . '/intern_access_control.php';
require_login();
require_role('Intern');
require_active_intern(); // This will redirect if not active
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');
$success=$error='';

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success = "Operation completed successfully!";
}

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['add_product'])) {
        $drug_name=esc($conn,$_POST['drug_name']);
        $manufacturer=esc($conn,$_POST['manufacturer']);
        $current_inventory=(int)$_POST['current_inventory'];
        $sold=(int)($_POST['sold']??0);
        $new_stock=(int)($_POST['new_stock']??0);
        
        if (!$drug_name||!$manufacturer) { $error="Please fill in required fields."; }
        else {
            // Check if sold and new_stock columns exist
            $columns = $conn->query("SHOW COLUMNS FROM product_inventory LIKE 'sold'")->num_rows;
            if ($columns > 0) {
                $result = $conn->query("INSERT INTO product_inventory (drug_name,manufacturer,current_inventory,sold,new_stock) VALUES ('$drug_name','$manufacturer',$current_inventory,$sold,$new_stock)");
                if ($result) {
                    $success="Product added successfully!";
                    header("Location: manage_product_inventory.php?success=1");
                    exit;
                } else {
                    $error="Insert failed: " . $conn->error;
                }
            } else {
                // Old schema without sold/new_stock columns
                $result = $conn->query("INSERT INTO product_inventory (drug_name,manufacturer,current_inventory) VALUES ('$drug_name','$manufacturer',$current_inventory)");
                if ($result) {
                    $success="Product added successfully!";
                    header("Location: manage_product_inventory.php?success=1");
                    exit;
                } else {
                    $error="Insert failed: " . $conn->error;
                }
            }
        }
    }
    elseif (isset($_POST['edit_product'])) {
        $product_id=(int)$_POST['product_id'];
        $drug_name=esc($conn,$_POST['drug_name']);
        $manufacturer=esc($conn,$_POST['manufacturer']);
        $current_inventory=(int)$_POST['current_inventory'];
        $sold=(int)($_POST['sold']??0);
        $new_stock=(int)($_POST['new_stock']??0);
        
        if (!$drug_name||!$manufacturer) { $error="Please fill in required fields."; }
        else {
            // Calculate new inventory: current - sold + new_stock
            $updated_inventory = $current_inventory - $sold + $new_stock;
            
            // Prevent negative inventory
            if ($updated_inventory < 0) {
                $error = "Cannot sell more than available inventory. Current: $current_inventory, Sold: $sold";
            } else {
                // Check if sold and new_stock columns exist
                $columns = $conn->query("SHOW COLUMNS FROM product_inventory LIKE 'sold'")->num_rows;
                if ($columns > 0) {
                    $result = $conn->query("UPDATE product_inventory SET drug_name='$drug_name',manufacturer='$manufacturer',current_inventory=$updated_inventory,sold=$sold,new_stock=$new_stock WHERE product_id=$product_id");
                    if ($result) {
                        $success="Product updated! Inventory: $current_inventory - $sold (sold) + $new_stock (new) = $updated_inventory";
                        header("Location: manage_product_inventory.php?success=1");
                        exit;
                    } else {
                        $error="Update failed: " . $conn->error;
                    }
                } else {
                    // Old schema without sold/new_stock columns - just update inventory
                    $result = $conn->query("UPDATE product_inventory SET drug_name='$drug_name',manufacturer='$manufacturer',current_inventory=$updated_inventory WHERE product_id=$product_id");
                    if ($result) {
                        $success="Product updated! New inventory: $updated_inventory";
                        header("Location: manage_product_inventory.php?success=1");
                        exit;
                    } else {
                        $error="Update failed: " . $conn->error;
                    }
                }
            }
        }
    }
    elseif (isset($_POST['delete_product'])) {
        $product_id=(int)$_POST['product_id'];
        $result = $conn->query("DELETE FROM product_inventory WHERE product_id=$product_id");
        if ($result) {
            $success="Product deleted successfully!";
            header("Location: manage_product_inventory.php?success=1");
            exit;
        } else {
            $error="Delete failed: " . $conn->error;
        }
    }
}

$edit_id=isset($_GET['edit'])?(int)$_GET['edit']:0;
$edit_product=null;
if ($edit_id) {
    $edit_product=$conn->query("SELECT * FROM product_inventory WHERE product_id=$edit_id")->fetch_assoc();
}

$products=$conn->query("SELECT * FROM product_inventory ORDER BY drug_name");
?>
<?php navBar('Manage Product Inventory','dashboard_intern.php'); ?>
<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title"><i class="bi bi-box-seam" style="color:#2ecc71"></i> Manage Product Inventory</div>
    </div>

    <?php if ($success): ?><div class="ls-alert ls-alert-success"><i class="bi bi-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="ls-alert ls-alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Add/Edit Form -->
    <div class="ls-card" style="margin-bottom:20px">
        <div class="ls-card-header"><?= $edit_product ? 'Edit Product' : 'Add New Product' ?></div>
        <div class="ls-card-body">
            <form method="POST">
                <?php if ($edit_product): ?>
                    <input type="hidden" name="product_id" value="<?= $edit_product['product_id'] ?>">
                <?php endif; ?>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:14px">
                    <div>
                        <label class="ls-label">Drug Name *</label>
                        <input type="text" name="drug_name" class="ls-input" value="<?= $edit_product ? htmlspecialchars($edit_product['drug_name']) : '' ?>" required>
                    </div>
                    <div>
                        <label class="ls-label">Manufacturer *</label>
                        <input type="text" name="manufacturer" class="ls-input" value="<?= $edit_product ? htmlspecialchars($edit_product['manufacturer']) : '' ?>" required>
                    </div>
                    <div>
                        <label class="ls-label">Current Inventory *</label>
                        <input type="number" name="current_inventory" id="currentInv" class="ls-input" value="<?= $edit_product ? $edit_product['current_inventory'] : 0 ?>" min="0" required readonly>
                        <small style="color:#94a3b8;font-size:0.75rem">Auto-calculated from sold/new stock</small>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-bottom:14px">
                    <div>
                        <label class="ls-label">Sold (Items Used) - Will subtract from inventory</label>
                        <input type="number" name="sold" id="soldInput" class="ls-input" value="<?= $edit_product ? ($edit_product['sold']??0) : 0 ?>" min="0" onchange="calculateInventory()">
                    </div>
                    <div>
                        <label class="ls-label">New Stock (Items Received) - Will add to inventory</label>
                        <input type="number" name="new_stock" id="newStockInput" class="ls-input" value="<?= $edit_product ? ($edit_product['new_stock']??0) : 0 ?>" min="0" onchange="calculateInventory()">
                    </div>
                </div>
                <?php if ($edit_product): ?>
                <div style="background:rgba(238, 227, 227, 1);padding:14px;border-radius:8px;margin-bottom:14px;border:1px solid rgba(255,255,255,0.1)">
                    <strong style="color:#94a3b8">Calculation:</strong> 
                    <span id="calcDisplay">
                        <?= $edit_product['current_inventory'] ?> (current) - <span id="soldDisplay">0</span> (sold) + <span id="newDisplay">0</span> (new) = 
                        <strong id="resultDisplay"><?= $edit_product['current_inventory'] ?></strong>
                    </span>
                </div>
                <script>
                const originalInventory = <?= $edit_product['current_inventory'] ?>;
                function calculateInventory() {
                    const sold = parseInt(document.getElementById('soldInput').value) || 0;
                    const newStock = parseInt(document.getElementById('newStockInput').value) || 0;
                    const result = originalInventory - sold + newStock;
                    
                    document.getElementById('currentInv').value = result;
                    document.getElementById('soldDisplay').textContent = sold;
                    document.getElementById('newDisplay').textContent = newStock;
                    document.getElementById('resultDisplay').textContent = result;
                }
                calculateInventory();
                </script>
                <?php endif; ?>
                <div style="text-align:right">
                    <?php if ($edit_product): ?>
                        <a href="manage_product_inventory.php" class="ls-btn ls-btn-ghost" style="margin-right:8px">Cancel</a>
                        <button type="submit" name="edit_product" class="ls-btn ls-btn-primary">
                            <i class="bi bi-save"></i> Update Product
                        </button>
                    <?php else: ?>
                        <button type="submit" name="add_product" class="ls-btn ls-btn-success">
                            <i class="bi bi-plus-circle"></i> Add Product
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Products List -->
    <div class="ls-card">
        <div class="ls-card-header">Product Inventory</div>
        <div class="ls-card-body-flush">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>Drug ID</th>
                        <th>Name</th>
                        <th>Manufacturer</th>
                        <th style="text-align:center">Current Inventory</th>
                        <th style="text-align:center">Sold</th>
                        <th style="text-align:center">New Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products && $products->num_rows > 0):
                        while ($p = $products->fetch_assoc()): ?>
                            <tr>
                                <td><?= $p['product_id'] ?></td>
                                <td><?= htmlspecialchars($p['drug_name']) ?></td>
                                <td><?= htmlspecialchars($p['manufacturer']) ?></td>
                                <td style="text-align:center;font-weight:700"><?= $p['current_inventory'] ?></td>
                                <td style="text-align:center"><?= $p['sold']??0 ?></td>
                                <td style="text-align:center"><?= $p['new_stock']??0 ?></td>
                                <td>
                                    <a href="?edit=<?= $p['product_id'] ?>" class="ls-btn ls-btn-primary ls-btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this product?')">
                                        <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                                        <button type="submit" name="delete_product" class="ls-btn ls-btn-danger ls-btn-sm">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="7" class="ls-empty"><i class="bi bi-inbox"></i> No products yet. Add your first product above.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
