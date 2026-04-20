<?php
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<h1>🔍 Check Report #8 Status</h1>";

// Check current status
$report = $conn->query("SELECT report_id, report_date, ward, created_by, status FROM p1014_inventory_reports WHERE report_id = 8")->fetch_assoc();

if ($report) {
    echo "<h2>Current Status:</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;margin:20px 0'>";
    echo "<tr style='background:#333;color:#fff'>";
    echo "<th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Status</th>";
    echo "</tr>";
    
    $statusColor = $report['status'] === 'submitted' ? '#f1c40f' : ($report['status'] === 'approved' ? '#2ecc71' : '#e74c3c');
    echo "<tr>";
    echo "<td><strong>#" . $report['report_id'] . "</strong></td>";
    echo "<td>" . htmlspecialchars($report['report_date']) . "</td>";
    echo "<td>" . htmlspecialchars($report['ward']) . "</td>";
    echo "<td>" . htmlspecialchars($report['created_by']) . "</td>";
    echo "<td style='background:$statusColor;color:#fff;font-weight:bold;text-align:center'>" . strtoupper($report['status']) . "</td>";
    echo "</tr>";
    echo "</table>";
    
    if ($report['status'] !== 'approved') {
        echo "<div style='background:#fee;padding:20px;border:2px solid #f00;margin:20px 0;border-radius:8px'>";
        echo "<h2 style='color:#f00'>⚠️ Status is NOT approved!</h2>";
        echo "<p>Current status: <strong>" . strtoupper($report['status']) . "</strong></p>";
        echo "<p>Changing to APPROVED...</p>";
        echo "</div>";
        
        // Update to approved
        $conn->query("UPDATE p1014_inventory_reports SET status = 'approved' WHERE report_id = 8");
        
        echo "<div style='background:#efe;padding:20px;border:2px solid #0f0;margin:20px 0;border-radius:8px'>";
        echo "<h2 style='color:#0a0'>✅ FIXED!</h2>";
        echo "<p>Report #8 status has been changed to APPROVED</p>";
        echo "</div>";
        
        // Show updated status
        $updated = $conn->query("SELECT report_id, report_date, ward, created_by, status FROM p1014_inventory_reports WHERE report_id = 8")->fetch_assoc();
        
        echo "<h2>Updated Status:</h2>";
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse;margin:20px 0'>";
        echo "<tr style='background:#333;color:#fff'>";
        echo "<th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Status</th>";
        echo "</tr>";
        
        $statusColor = '#2ecc71';
        echo "<tr>";
        echo "<td><strong>#" . $updated['report_id'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($updated['report_date']) . "</td>";
        echo "<td>" . htmlspecialchars($updated['ward']) . "</td>";
        echo "<td>" . htmlspecialchars($updated['created_by']) . "</td>";
        echo "<td style='background:$statusColor;color:#fff;font-weight:bold;text-align:center'>" . strtoupper($updated['status']) . "</td>";
        echo "</tr>";
        echo "</table>";
    } else {
        echo "<div style='background:#efe;padding:20px;border:2px solid #0f0;margin:20px 0;border-radius:8px'>";
        echo "<h2 style='color:#0a0'>✅ Already Approved!</h2>";
        echo "<p>Report #8 is already approved. No changes needed.</p>";
        echo "</div>";
    }
} else {
    echo "<div style='background:#fee;padding:20px;border:2px solid #f00;margin:20px 0;border-radius:8px'>";
    echo "<h2 style='color:#f00'>❌ Report #8 Not Found!</h2>";
    echo "<p>Report #8 does not exist in the database.</p>";
    echo "</div>";
}

$conn->close();
?>

<style>
body { font-family: Arial; padding: 20px; background: #1a1a1a; color: #fff; }
table { width: 100%; background: #2a2a2a; }
th { text-align: left; padding: 10px; }
td { padding: 10px; background: #2a2a2a; border-bottom: 1px solid #3a3a3a; }
h1 { color: #3498db; }
h2 { color: #2ecc71; }
</style>

<div style='margin-top:30px;padding:20px;background:#2a2a2a;border-radius:8px'>
    <h3>Next Steps:</h3>
    <p><a href='view_inventory_report.php' style='color:#3498db;font-size:1.2em'>→ Go to View Inventory Reports</a></p>
    <p>The report should now show as APPROVED with a "Request" button!</p>
    <p><strong>Remember to clear browser cache (Ctrl+F5) to see the changes!</strong></p>
</div>
