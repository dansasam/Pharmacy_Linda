<?php
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<h1>🧪 Test Approve Functionality</h1>";

// Get report 8
$report = $conn->query("SELECT * FROM p1014_inventory_reports WHERE report_id = 8")->fetch_assoc();

if (!$report) {
    echo "<p style='color:#f00'>Report #8 not found!</p>";
    exit;
}

echo "<h2>Current Report Status:</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse;margin:20px 0'>";
echo "<tr style='background:#333;color:#fff'><th>Field</th><th>Value</th></tr>";
foreach ($report as $key => $value) {
    echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
}
echo "</table>";

// Test the UPDATE query
echo "<h2>Testing UPDATE Query:</h2>";
echo "<p>Running: <code>UPDATE p1014_inventory_reports SET status='approved' WHERE report_id=8</code></p>";

$result = $conn->query("UPDATE p1014_inventory_reports SET status='approved' WHERE report_id=8");

if ($result) {
    echo "<p style='color:#0f0'>✅ Query executed successfully</p>";
    echo "<p>Affected rows: <strong>" . $conn->affected_rows . "</strong></p>";
    
    if ($conn->affected_rows > 0) {
        echo "<p style='color:#0f0'>✅ Status was changed!</p>";
    } else {
        echo "<p style='color:#f90'>⚠️ No rows affected (status might already be 'approved')</p>";
    }
} else {
    echo "<p style='color:#f00'>❌ Query failed!</p>";
    echo "<p>Error: " . $conn->error . "</p>";
}

// Check updated status
$updated = $conn->query("SELECT * FROM p1014_inventory_reports WHERE report_id = 8")->fetch_assoc();

echo "<h2>Updated Report Status:</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse;margin:20px 0'>";
echo "<tr style='background:#333;color:#fff'><th>Field</th><th>Value</th></tr>";
foreach ($updated as $key => $value) {
    $highlight = ($key === 'status') ? 'style="background:#efe;font-weight:bold"' : '';
    echo "<tr $highlight><td><strong>$key</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
}
echo "</table>";

// Show what the button URL should be
echo "<h2>Approve Button URL:</h2>";
echo "<p>When viewing report #8, the approve button should link to:</p>";
echo "<code style='background:#2a2a2a;padding:10px;display:block;margin:10px 0'>view_inventory_report.php?report_id=8&action=approve</code>";

echo "<h2>Test the Approve Action:</h2>";
echo "<p><a href='view_inventory_report.php?report_id=8&action=approve' style='background:#2ecc71;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block'>Click Here to Approve Report #8</a></p>";

$conn->close();
?>

<style>
body { font-family: Arial; padding: 20px; background: #1a1a1a; color: #fff; }
table { width: 100%; background: #2a2a2a; }
th { text-align: left; padding: 10px; }
td { padding: 10px; background: #2a2a2a; border-bottom: 1px solid #3a3a3a; }
h1 { color: #3498db; }
h2 { color: #2ecc71; margin-top: 30px; }
code { background: #2a2a2a; padding: 3px 8px; border-radius: 4px; color: #3498db; }
</style>

<div style='margin-top:30px;padding:20px;background:#2a2a2a;border-radius:8px'>
    <h3>Instructions:</h3>
    <ol>
        <li>Check the current status above</li>
        <li>Click the "Click Here to Approve Report #8" button</li>
        <li>You should be redirected to view_inventory_report.php</li>
        <li>The report should show as APPROVED with a "Request" button</li>
    </ol>
    <p><a href='view_inventory_report.php' style='color:#3498db'>→ Go to View Inventory Reports</a></p>
</div>
