<?php
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<h1>🔧 Fix Report Status</h1>";

// Check reports with NULL or empty status
$result = $conn->query("SELECT report_id, report_date, ward, created_by, status FROM p1014_inventory_reports WHERE status IS NULL OR status = ''");

if ($result && $result->num_rows > 0) {
    echo "<h2>Found " . $result->num_rows . " reports with NULL/empty status</h2>";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;margin:20px 0'>";
    echo "<tr style='background:#333;color:#fff'>";
    echo "<th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Current Status</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>#" . $row['report_id'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['report_date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['ward']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_by']) . "</td>";
        echo "<td style='background:#fee;color:#f00'>[" . htmlspecialchars($row['status']) . "] (NULL/EMPTY)</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Fix the status
    $conn->query("UPDATE p1014_inventory_reports SET status = 'submitted' WHERE status IS NULL OR status = ''");
    
    echo "<div style='background:#efe;padding:20px;border:2px solid #0f0;margin:20px 0;border-radius:8px'>";
    echo "<h2 style='color:#0a0'>✅ FIXED!</h2>";
    echo "<p>All reports with NULL/empty status have been set to 'submitted'</p>";
    echo "</div>";
    
    // Show updated reports
    echo "<h2>Updated Reports:</h2>";
    $updated = $conn->query("SELECT report_id, report_date, ward, created_by, status FROM p1014_inventory_reports ORDER BY report_id DESC");
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;margin:20px 0'>";
    echo "<tr style='background:#333;color:#fff'>";
    echo "<th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Status</th>";
    echo "</tr>";
    
    while ($row = $updated->fetch_assoc()) {
        $statusColor = $row['status'] === 'submitted' ? '#f1c40f' : ($row['status'] === 'approved' ? '#2ecc71' : '#e74c3c');
        echo "<tr>";
        echo "<td><strong>#" . $row['report_id'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['report_date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['ward']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_by']) . "</td>";
        echo "<td style='background:$statusColor;color:#fff;font-weight:bold;text-align:center'>" . strtoupper($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<div style='background:#efe;padding:20px;border:2px solid #0f0;margin:20px 0;border-radius:8px'>";
    echo "<h2 style='color:#0a0'>✅ All Good!</h2>";
    echo "<p>No reports with NULL/empty status found. All reports have valid status values.</p>";
    echo "</div>";
    
    // Show all reports
    echo "<h2>All Reports:</h2>";
    $all = $conn->query("SELECT report_id, report_date, ward, created_by, status FROM p1014_inventory_reports ORDER BY report_id DESC");
    
    if ($all && $all->num_rows > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse:collapse;margin:20px 0'>";
        echo "<tr style='background:#333;color:#fff'>";
        echo "<th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Status</th>";
        echo "</tr>";
        
        while ($row = $all->fetch_assoc()) {
            $statusColor = $row['status'] === 'submitted' ? '#f1c40f' : ($row['status'] === 'approved' ? '#2ecc71' : '#e74c3c');
            echo "<tr>";
            echo "<td><strong>#" . $row['report_id'] . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['report_date']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ward']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_by']) . "</td>";
            echo "<td style='background:$statusColor;color:#fff;font-weight:bold;text-align:center'>" . strtoupper($row['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No reports in database.</p>";
    }
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
    <p>The report should now appear in the table!</p>
</div>
