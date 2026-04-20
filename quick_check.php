<?php
require_once __DIR__ . '/config.php';

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Content-Type: text/html; charset=utf-8");

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('❌ Connection failed: ' . $conn->connect_error);
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Quick Check</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #1a1a1a; color: #fff; }
.box { background: #2a2a2a; padding: 20px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #3498db; }
.success { border-color: #2ecc71; }
.error { border-color: #e74c3c; }
.warning { border-color: #f1c40f; }
h1 { color: #3498db; }
h2 { color: #2ecc71; margin-top: 0; }
.count { font-size: 2em; font-weight: bold; color: #3498db; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th { background: #333; padding: 10px; text-align: left; }
td { padding: 8px; border-bottom: 1px solid #3a3a3a; }
.btn { display: inline-block; padding: 10px 20px; background: #3498db; color: #fff; text-decoration: none; border-radius: 5px; margin: 5px; }
.btn:hover { background: #2980b9; }
</style></head><body>";

echo "<h1>⚡ Quick Database Check</h1>";
echo "<p><small>Last updated: " . date('Y-m-d H:i:s') . " | <a href='javascript:location.reload()' style='color:#3498db'>🔄 Refresh</a></small></p>";

// Check 1: Products
echo "<div class='box'>";
echo "<h2>📦 Products in Database</h2>";
$productCount = $conn->query("SELECT COUNT(*) as cnt FROM product_inventory")->fetch_assoc()['cnt'];
echo "<div class='count'>" . $productCount . "</div>";
echo "<p>products found in product_inventory table</p>";

if ($productCount > 0) {
    // Check if sold/new_stock columns exist
    $hasSold = $conn->query("SHOW COLUMNS FROM product_inventory LIKE 'sold'")->num_rows > 0;
    $hasNewStock = $conn->query("SHOW COLUMNS FROM product_inventory LIKE 'new_stock'")->num_rows > 0;
    
    if ($hasSold && $hasNewStock) {
        echo "<p style='color:#2ecc71'>✅ Columns 'sold' and 'new_stock' exist</p>";
        
        // Show sample products
        $products = $conn->query("SELECT product_id, drug_name, current_inventory, 
            COALESCE(sold, 0) as sold, COALESCE(new_stock, 0) as new_stock 
            FROM product_inventory ORDER BY drug_name LIMIT 5");
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Drug Name</th><th>Current Inv</th><th>Sold</th><th>New Stock</th></tr>";
        while ($p = $products->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $p['product_id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($p['drug_name']) . "</strong></td>";
            echo "<td>" . $p['current_inventory'] . "</td>";
            echo "<td>" . $p['sold'] . "</td>";
            echo "<td>" . $p['new_stock'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><small>Showing first 5 products</small></p>";
    } else {
        echo "<p style='color:#e74c3c'>❌ Missing columns: ";
        if (!$hasSold) echo "'sold' ";
        if (!$hasNewStock) echo "'new_stock' ";
        echo "</p>";
        echo "<p><strong>Action:</strong> Run add_tracking_columns.sql in phpMyAdmin</p>";
    }
} else {
    echo "<p style='color:#e74c3c'>❌ No products found</p>";
    echo "<p><strong>Action:</strong> Run add_sample_products.sql in phpMyAdmin</p>";
}
echo "</div>";

// Check 2: Reports
echo "<div class='box'>";
echo "<h2>📋 Reports in Database</h2>";
$reportCount = $conn->query("SELECT COUNT(*) as cnt FROM p1014_inventory_reports")->fetch_assoc()['cnt'];
echo "<div class='count'>" . $reportCount . "</div>";
echo "<p>reports found in p1014_inventory_reports table</p>";

if ($reportCount > 0) {
    $reports = $conn->query("SELECT report_id, report_date, ward, status FROM p1014_inventory_reports ORDER BY report_id DESC LIMIT 5");
    echo "<table>";
    echo "<tr><th>Report #</th><th>Date</th><th>Ward</th><th>Status</th></tr>";
    while ($r = $reports->fetch_assoc()) {
        $color = $r['status'] === 'approved' ? '#2ecc71' : ($r['status'] === 'denied' ? '#e74c3c' : '#f1c40f');
        echo "<tr>";
        echo "<td><strong>#" . $r['report_id'] . "</strong></td>";
        echo "<td>" . $r['report_date'] . "</td>";
        echo "<td>" . htmlspecialchars($r['ward']) . "</td>";
        echo "<td style='color:$color;font-weight:bold'>" . strtoupper($r['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><small>Showing last 5 reports</small></p>";
} else {
    echo "<p style='color:#2ecc71'>✅ No reports in database (clean slate)</p>";
}
echo "</div>";

// Check 3: Test the exact query from create_inventory_report.php
echo "<div class='box'>";
echo "<h2>🧪 Test Create Report Query</h2>";
$testQuery = "SELECT product_id, drug_name, manufacturer, current_inventory, 
    COALESCE(sold, 0) as sold, 
    COALESCE(new_stock, 0) as new_stock 
    FROM product_inventory ORDER BY drug_name";

$testResult = $conn->query($testQuery);
if ($testResult === false) {
    echo "<p style='color:#e74c3c;font-size:1.2em'>❌ QUERY FAILED</p>";
    echo "<p><strong>Error:</strong> " . $conn->error . "</p>";
    echo "<p>This is why products don't show in Create Inventory Report!</p>";
    echo "<p><strong>Action:</strong> Run add_tracking_columns.sql in phpMyAdmin</p>";
} else {
    echo "<p style='color:#2ecc71;font-size:1.2em'>✅ QUERY SUCCESSFUL</p>";
    echo "<div class='count'>" . $testResult->num_rows . "</div>";
    echo "<p>products will appear in Create Inventory Report page</p>";
    
    if ($testResult->num_rows === 0) {
        echo "<p style='color:#f1c40f'>⚠️ Query works but no products found</p>";
        echo "<p><strong>Action:</strong> Run add_sample_products.sql in phpMyAdmin</p>";
    }
}
echo "</div>";

// Summary
echo "<div class='box " . ($productCount > 0 && $testResult && $testResult->num_rows > 0 ? "success" : "error") . "'>";
echo "<h2>📊 Summary</h2>";

if ($productCount > 0 && $testResult && $testResult->num_rows > 0) {
    echo "<p style='color:#2ecc71;font-size:1.2em'>✅ Everything looks good!</p>";
    echo "<p>Products should appear in Create Inventory Report page.</p>";
    echo "<p><strong>If they still don't show:</strong></p>";
    echo "<ol>";
    echo "<li>Clear browser cache (Ctrl+Shift+Delete)</li>";
    echo "<li>Hard refresh (Ctrl+F5)</li>";
    echo "<li>Try incognito mode (Ctrl+Shift+N)</li>";
    echo "</ol>";
} else {
    echo "<p style='color:#e74c3c;font-size:1.2em'>❌ Issues found</p>";
    echo "<p><strong>Required actions:</strong></p>";
    echo "<ol>";
    if ($testResult === false) {
        echo "<li>Run <code>add_tracking_columns.sql</code> in phpMyAdmin</li>";
    }
    if ($productCount === 0) {
        echo "<li>Run <code>add_sample_products.sql</code> in phpMyAdmin</li>";
    }
    echo "</ol>";
}
echo "</div>";

// Action buttons
echo "<div style='margin-top:20px;text-align:center'>";
echo "<a href='diagnostic_full.php' class='btn'>📋 Full Diagnostic Report</a>";
echo "<a href='check_products.php' class='btn'>📦 View All Products</a>";
echo "<a href='check_reports.php' class='btn'>📊 View All Reports</a>";
echo "<a href='create_inventory_report.php' class='btn' style='background:#2ecc71'>➡️ Go to Create Report</a>";
echo "</div>";

$conn->close();
echo "</body></html>";
?>
