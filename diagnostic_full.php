<?php
require_once __DIR__ . '/config.php';

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<h1>🔍 Complete Database Diagnostic</h1>";
echo "<p><small>Generated: " . date('Y-m-d H:i:s') . "</small></p>";

// ============================================
// 1. CHECK PRODUCT_INVENTORY TABLE
// ============================================
echo "<div class='section'>";
echo "<h2>1️⃣ Product Inventory Table</h2>";

// Check if table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'product_inventory'")->num_rows > 0;
echo "<p><strong>Table exists:</strong> " . ($tableExists ? "✅ YES" : "❌ NO") . "</p>";

if ($tableExists) {
    // Check columns
    echo "<h3>Columns:</h3>";
    $columns = $conn->query("SHOW COLUMNS FROM product_inventory");
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;margin-bottom:20px'>";
    echo "<tr style='background:#333;color:#fff'><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    $hasCurrentInventory = $hasSold = $hasNewStock = false;
    while ($col = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
        if ($col['Field'] === 'current_inventory') $hasCurrentInventory = true;
        if ($col['Field'] === 'sold') $hasSold = true;
        if ($col['Field'] === 'new_stock') $hasNewStock = true;
    }
    echo "</table>";
    
    echo "<p><strong>Required columns status:</strong></p>";
    echo "<ul>";
    echo "<li>current_inventory: " . ($hasCurrentInventory ? "✅ EXISTS" : "❌ MISSING") . "</li>";
    echo "<li>sold: " . ($hasSold ? "✅ EXISTS" : "❌ MISSING - Run add_tracking_columns.sql") . "</li>";
    echo "<li>new_stock: " . ($hasNewStock ? "✅ EXISTS" : "❌ MISSING - Run add_tracking_columns.sql") . "</li>";
    echo "</ul>";
    
    // Check products
    echo "<h3>Products in Database:</h3>";
    $products = $conn->query("SELECT product_id, drug_name, manufacturer, current_inventory" . 
        ($hasSold ? ", COALESCE(sold, 0) as sold" : "") . 
        ($hasNewStock ? ", COALESCE(new_stock, 0) as new_stock" : "") . 
        " FROM product_inventory ORDER BY drug_name");
    
    if ($products && $products->num_rows > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;margin-bottom:20px'>";
        echo "<tr style='background:#333;color:#fff'>";
        echo "<th>ID</th><th>Drug Name</th><th>Manufacturer</th><th>Current Inventory</th>";
        if ($hasSold) echo "<th>Sold</th>";
        if ($hasNewStock) echo "<th>New Stock</th>";
        echo "</tr>";
        
        while ($p = $products->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $p['product_id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($p['drug_name']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($p['manufacturer']) . "</td>";
            echo "<td style='text-align:center;font-weight:bold'>" . $p['current_inventory'] . "</td>";
            if ($hasSold) echo "<td style='text-align:center'>" . $p['sold'] . "</td>";
            if ($hasNewStock) echo "<td style='text-align:center'>" . $p['new_stock'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><strong>✅ Total products: " . $products->num_rows . "</strong></p>";
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<h3>❌ NO PRODUCTS FOUND!</h3>";
        echo "<p>The product_inventory table is empty.</p>";
        echo "<p><strong>Action required:</strong> Run <code>add_sample_products.sql</code> in phpMyAdmin</p>";
        echo "</div>";
    }
}
echo "</div>";

// ============================================
// 2. CHECK INVENTORY REPORTS TABLE
// ============================================
echo "<div class='section'>";
echo "<h2>2️⃣ Inventory Reports Table</h2>";

$reportsTableExists = $conn->query("SHOW TABLES LIKE 'p1014_inventory_reports'")->num_rows > 0;
echo "<p><strong>Table exists:</strong> " . ($reportsTableExists ? "✅ YES" : "❌ NO") . "</p>";

if ($reportsTableExists) {
    // Check columns
    echo "<h3>Columns:</h3>";
    $columns = $conn->query("SHOW COLUMNS FROM p1014_inventory_reports");
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;margin-bottom:20px'>";
    echo "<tr style='background:#333;color:#fff'><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    $hasDenialRemarks = false;
    while ($col = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
        if ($col['Field'] === 'denial_remarks') $hasDenialRemarks = true;
    }
    echo "</table>";
    
    echo "<p><strong>denial_remarks column: " . ($hasDenialRemarks ? "✅ EXISTS" : "❌ MISSING - Run add_tracking_columns.sql") . "</strong></p>";
    
    // Check reports
    echo "<h3>Reports in Database:</h3>";
    $reports = $conn->query("SELECT report_id, report_date, ward, created_by, status, remarks" . 
        ($hasDenialRemarks ? ", denial_remarks" : "") . 
        " FROM p1014_inventory_reports ORDER BY report_id DESC");
    
    if ($reports && $reports->num_rows > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;margin-bottom:20px'>";
        echo "<tr style='background:#333;color:#fff'>";
        echo "<th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Status</th><th>Remarks</th>";
        if ($hasDenialRemarks) echo "<th>Denial Remarks</th>";
        echo "</tr>";
        
        while ($r = $reports->fetch_assoc()) {
            $statusColor = $r['status'] === 'submitted' ? '#f1c40f' : ($r['status'] === 'approved' ? '#2ecc71' : '#e74c3c');
            echo "<tr>";
            echo "<td><strong>#" . $r['report_id'] . "</strong></td>";
            echo "<td>" . htmlspecialchars($r['report_date']) . "</td>";
            echo "<td>" . htmlspecialchars($r['ward']) . "</td>";
            echo "<td>" . htmlspecialchars($r['created_by']) . "</td>";
            echo "<td style='background:$statusColor;color:#fff;font-weight:bold;text-align:center'>" . strtoupper($r['status']) . "</td>";
            echo "<td>" . htmlspecialchars($r['remarks'] ?: '—') . "</td>";
            if ($hasDenialRemarks) echo "<td>" . htmlspecialchars($r['denial_remarks'] ?: '—') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><strong>Total reports: " . $reports->num_rows . "</strong></p>";
    } else {
        echo "<div class='alert alert-success'>";
        echo "<h3>✅ NO REPORTS IN DATABASE</h3>";
        echo "<p>The p1014_inventory_reports table is empty. This is normal if you haven't created any reports yet.</p>";
        echo "</div>";
    }
}
echo "</div>";

// ============================================
// 3. TEST THE EXACT QUERY FROM create_inventory_report.php
// ============================================
echo "<div class='section'>";
echo "<h2>3️⃣ Test Query from create_inventory_report.php</h2>";

$testQuery = "SELECT product_id, drug_name, manufacturer, current_inventory, 
    COALESCE(sold, 0) as sold, 
    COALESCE(new_stock, 0) as new_stock 
    FROM product_inventory ORDER BY drug_name";

echo "<p><strong>Query:</strong></p>";
echo "<pre style='background:#2a2a2a;padding:15px;border-radius:8px;overflow-x:auto'>" . htmlspecialchars($testQuery) . "</pre>";

$testResult = $conn->query($testQuery);
if ($testResult === false) {
    echo "<div class='alert alert-danger'>";
    echo "<h3>❌ QUERY FAILED!</h3>";
    echo "<p><strong>Error:</strong> " . $conn->error . "</p>";
    echo "<p>This means the columns 'sold' or 'new_stock' don't exist in product_inventory table.</p>";
    echo "<p><strong>Action required:</strong> Run <code>add_tracking_columns.sql</code> in phpMyAdmin</p>";
    echo "</div>";
} else {
    if ($testResult->num_rows > 0) {
        echo "<div class='alert alert-success'>";
        echo "<h3>✅ QUERY SUCCESSFUL!</h3>";
        echo "<p>Found <strong>" . $testResult->num_rows . "</strong> products.</p>";
        echo "<p>These products SHOULD appear in the Create Inventory Report page.</p>";
        echo "</div>";
        
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;width:100%;margin-bottom:20px'>";
        echo "<tr style='background:#333;color:#fff'>";
        echo "<th>ID</th><th>Drug Name</th><th>Manufacturer</th><th>Current Inv</th><th>Sold</th><th>New Stock</th>";
        echo "</tr>";
        
        while ($p = $testResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $p['product_id'] . "</td>";
            echo "<td><strong>" . htmlspecialchars($p['drug_name']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($p['manufacturer']) . "</td>";
            echo "<td style='text-align:center;font-weight:bold'>" . $p['current_inventory'] . "</td>";
            echo "<td style='text-align:center'>" . $p['sold'] . "</td>";
            echo "<td style='text-align:center'>" . $p['new_stock'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<h3>⚠️ QUERY SUCCESSFUL BUT NO RESULTS</h3>";
        echo "<p>The query ran successfully but returned 0 products.</p>";
        echo "<p><strong>Action required:</strong> Run <code>add_sample_products.sql</code> in phpMyAdmin</p>";
        echo "</div>";
    }
}
echo "</div>";

// ============================================
// 4. RECOMMENDATIONS
// ============================================
echo "<div class='section'>";
echo "<h2>4️⃣ Recommendations</h2>";

$issues = [];
$actions = [];

if (!$tableExists) {
    $issues[] = "product_inventory table doesn't exist";
    $actions[] = "Create the product_inventory table in your database";
}

if ($tableExists && (!$hasSold || !$hasNewStock)) {
    $issues[] = "Missing 'sold' or 'new_stock' columns in product_inventory";
    $actions[] = "Run <code>add_tracking_columns.sql</code> in phpMyAdmin";
}

if ($tableExists && $products && $products->num_rows === 0) {
    $issues[] = "No products in database";
    $actions[] = "Run <code>add_sample_products.sql</code> in phpMyAdmin";
}

if ($reportsTableExists && !$hasDenialRemarks) {
    $issues[] = "Missing 'denial_remarks' column in p1014_inventory_reports";
    $actions[] = "Run <code>add_tracking_columns.sql</code> in phpMyAdmin";
}

if (count($issues) > 0) {
    echo "<div class='alert alert-danger'>";
    echo "<h3>⚠️ Issues Found:</h3>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>" . $issue . "</li>";
    }
    echo "</ul>";
    echo "<h3>📋 Required Actions:</h3>";
    echo "<ol>";
    foreach ($actions as $action) {
        echo "<li>" . $action . "</li>";
    }
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='alert alert-success'>";
    echo "<h3>✅ All Checks Passed!</h3>";
    echo "<p>Your database schema is correct and has data.</p>";
    echo "<p>If products still don't show in Create Inventory Report:</p>";
    echo "<ol>";
    echo "<li>Clear your browser cache (Ctrl+Shift+Delete)</li>";
    echo "<li>Hard refresh the page (Ctrl+F5)</li>";
    echo "<li>Try a different browser or incognito mode</li>";
    echo "</ol>";
    echo "</div>";
}
echo "</div>";

$conn->close();
?>

<style>
body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    padding: 30px; 
    background: #1a1a1a; 
    color: #fff; 
    max-width: 1400px;
    margin: 0 auto;
}
h1 { 
    color: #3498db; 
    border-bottom: 3px solid #3498db; 
    padding-bottom: 10px;
    margin-bottom: 20px;
}
h2 { 
    color: #2ecc71; 
    margin-top: 30px;
    padding: 10px;
    background: #2a2a2a;
    border-left: 4px solid #2ecc71;
}
h3 { 
    color: #f1c40f; 
    margin-top: 20px;
}
.section {
    background: #2a2a2a;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    border: 1px solid #3a3a3a;
}
table { 
    width: 100%; 
    margin: 20px 0;
    background: #2a2a2a;
}
th { 
    text-align: left; 
    padding: 12px;
    background: #333 !important;
    color: #fff !important;
}
td { 
    padding: 10px; 
    background: #2a2a2a;
    border-bottom: 1px solid #3a3a3a;
}
tr:hover td { 
    background: #3a3a3a; 
}
.alert {
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    border-left: 5px solid;
}
.alert-success {
    background: #1e4620;
    border-color: #2ecc71;
    color: #2ecc71;
}
.alert-danger {
    background: #4a1e1e;
    border-color: #e74c3c;
    color: #e74c3c;
}
.alert-warning {
    background: #4a3e1e;
    border-color: #f1c40f;
    color: #f1c40f;
}
.alert h3 {
    margin-top: 0;
    color: inherit;
}
.alert p, .alert ul, .alert ol {
    color: #fff;
}
code {
    background: #3a3a3a;
    padding: 3px 8px;
    border-radius: 4px;
    color: #3498db;
    font-family: 'Courier New', monospace;
}
pre {
    background: #2a2a2a;
    padding: 15px;
    border-radius: 8px;
    overflow-x: auto;
    border: 1px solid #3a3a3a;
}
</style>
