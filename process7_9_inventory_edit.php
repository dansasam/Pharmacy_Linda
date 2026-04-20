<?php
require_once __DIR__ . '/process7_9_helpers.php';
require_login();
require_role('Intern');
ensure_process7_9_inventory_table();

$productId = (int) ($_GET['drug_id'] ?? 0);
if ($productId <= 0) {
    header('Location: process7_9_inventory.php?message=' . urlencode('Invalid inventory item selected.'));
    exit;
}

$stmt = $pdo->prepare('SELECT product_id, drug_name, manufacturer, record_date, invoice_no, current_inventory, initial_comments FROM product_inventory WHERE product_id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product) {
    header('Location: process7_9_inventory.php?message=' . urlencode('Inventory item not found.'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $drugName = sanitize_text($_POST['drug_name'] ?? '');
    $manufacturer = sanitize_text($_POST['manufacturer'] ?? '');
    $recordDate = sanitize_text($_POST['record_date'] ?? '');
    $invoiceNo = sanitize_text($_POST['invoice_no'] ?? '');
    $currentInventory = (int) ($_POST['current_inventory'] ?? -1);
    $initialComments = sanitize_text($_POST['initial_comments'] ?? '');

    if ($drugName === '' || $manufacturer === '' || $recordDate === '' || $invoiceNo === '' || $currentInventory < 0) {
        $error = 'All required fields must be filled in and inventory must be 0 or greater.';
    } else {
        $update = $pdo->prepare('UPDATE product_inventory SET drug_name = ?, manufacturer = ?, record_date = ?, invoice_no = ?, current_inventory = ?, initial_comments = ? WHERE product_id = ?');
        $update->execute([$drugName, $manufacturer, $recordDate, $invoiceNo, $currentInventory, $initialComments, $productId]);

        header('Location: process7_9_inventory.php?message=' . urlencode('Inventory entry updated.'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Inventory</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="process7_9_inventory.php">Back to Inventory</a>
                <a href="dashboard_intern.php">Intern Dashboard</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Edit Inventory Entry</h1>
                <div>Welcome, <?php echo sanitize_text(current_user()['full_name']); ?></div>
            </header>
            <section class="section-card">
                <?php if ($error !== ''): ?>
                    <div class="message error"><?php echo sanitize_text($error); ?></div>
                <?php endif; ?>
                <form method="post" class="compact-form">
                    <label>Drug Name</label>
                    <input type="text" name="drug_name" value="<?php echo sanitize_text($_POST['drug_name'] ?? $product['drug_name']); ?>" required />

                    <label>Manufacturer</label>
                    <input type="text" name="manufacturer" value="<?php echo sanitize_text($_POST['manufacturer'] ?? $product['manufacturer']); ?>" required />

                    <label>Date</label>
                    <input type="date" name="record_date" value="<?php echo sanitize_text($_POST['record_date'] ?? $product['record_date']); ?>" required />

                    <label>Invoice Number</label>
                    <input type="text" name="invoice_no" value="<?php echo sanitize_text($_POST['invoice_no'] ?? $product['invoice_no']); ?>" required />

                    <label>Current Inventory</label>
                    <input type="number" min="0" name="current_inventory" value="<?php echo sanitize_text($_POST['current_inventory'] ?? $product['current_inventory']); ?>" required />

                    <label>Initial Comments</label>
                    <textarea name="initial_comments" rows="3"><?php echo sanitize_text($_POST['initial_comments'] ?? $product['initial_comments']); ?></textarea>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
