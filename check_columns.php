<?php
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<h2>Checking product_inventory table structure:</h2>";

$result = $conn->query("SHOW COLUMNS FROM product_inventory");

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>" . $row['Field'] . "</strong></td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Check if sold and new_stock columns exist
$sold_exists = $conn->query("SHOW COLUMNS FROM product_inventory LIKE 'sold'")->num_rows > 0;
$new_stock_exists = $conn->query("SHOW COLUMNS FROM product_inventory LIKE 'new_stock'")->num_rows > 0;

echo "<h3>Column Status:</h3>";
echo "<p><strong>sold column:</strong> " . ($sold_exists ? "✅ EXISTS" : "❌ MISSING - Run migration!") . "</p>";
echo "<p><strong>new_stock column:</strong> " . ($new_stock_exists ? "✅ EXISTS" : "❌ MISSING - Run migration!") . "</p>";

if (!$sold_exists || !$new_stock_exists) {
    echo "<div style='background:#fee;padding:20px;border:2px solid #f00;margin:20px 0'>";
    echo "<h3 style='color:#f00'>⚠️ MIGRATION REQUIRED!</h3>";
    echo "<p>The <code>sold</code> and <code>new_stock</code> columns are missing.</p>";
    echo "<p><strong>Solution:</strong> Run the SQL migration file: <code>add_tracking_columns.sql</code></p>";
    echo "<ol>";
    echo "<li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
    echo "<li>Select database: <strong>pharmacy_internship</strong></li>";
    echo "<li>Click <strong>SQL</strong> tab</li>";
    echo "<li>Copy and paste content from <code>add_tracking_columns.sql</code></li>";
    echo "<li>Click <strong>Go</strong> button</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div style='background:#efe;padding:20px;border:2px solid #0f0;margin:20px 0'>";
    echo "<h3 style='color:#0a0'>✅ All columns exist!</h3>";
    echo "<p>The database is properly configured. Updates should work now.</p>";
    echo "</div>";
}

$conn->close();
?>
