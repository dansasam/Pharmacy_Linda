<?php
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<h2>Products in Database:</h2>";

$result = $conn->query("SELECT product_id, drug_name, manufacturer, current_inventory, 
    COALESCE(sold, 0) as sold, 
    COALESCE(new_stock, 0) as new_stock 
    FROM product_inventory ORDER BY drug_name");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse'>";
    echo "<tr style='background:#333;color:#fff'>";
    echo "<th>ID</th><th>Drug Name</th><th>Manufacturer</th><th>Current Inventory</th><th>Sold</th><th>New Stock</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['drug_name']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['manufacturer']) . "</td>";
        echo "<td style='text-align:center'><strong>" . $row['current_inventory'] . "</strong></td>";
        echo "<td style='text-align:center'>" . $row['sold'] . "</td>";
        echo "<td style='text-align:center'>" . $row['new_stock'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p><strong>Total products: " . $result->num_rows . "</strong></p>";
} else {
    echo "<div style='background:#fee;padding:20px;border:2px solid #f00;margin:20px 0'>";
    echo "<h3 style='color:#f00'>⚠️ NO PRODUCTS FOUND!</h3>";
    echo "<p>The product_inventory table is empty or doesn't exist.</p>";
    echo "<p><strong>Solution:</strong> Run the SQL script: <code>add_sample_products.sql</code></p>";
    echo "</div>";
}

// Check if sold and new_stock columns exist
$sold_exists = $conn->query("SHOW COLUMNS FROM product_inventory LIKE 'sold'")->num_rows > 0;
$new_stock_exists = $conn->query("SHOW COLUMNS FROM product_inventory LIKE 'new_stock'")->num_rows > 0;

echo "<h3>Column Status:</h3>";
echo "<p><strong>sold column:</strong> " . ($sold_exists ? "✅ EXISTS" : "❌ MISSING") . "</p>";
echo "<p><strong>new_stock column:</strong> " . ($new_stock_exists ? "✅ EXISTS" : "❌ MISSING") . "</p>";

if (!$sold_exists || !$new_stock_exists) {
    echo "<div style='background:#fee;padding:20px;border:2px solid #f00;margin:20px 0'>";
    echo "<h3 style='color:#f00'>⚠️ COLUMNS MISSING!</h3>";
    echo "<p>Run migration: <code>add_tracking_columns.sql</code></p>";
    echo "</div>";
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #1a1a1a; color: #fff; }
table { width: 100%; margin: 20px 0; }
th { text-align: left; padding: 10px; }
td { padding: 10px; background: #2a2a2a; }
tr:hover td { background: #3a3a3a; }
</style>
