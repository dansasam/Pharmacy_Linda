<?php
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<h2>Report Status Check</h2>";

$result = $conn->query("SELECT report_id, report_date, ward, created_by, status, remarks FROM p1014_inventory_reports ORDER BY report_id DESC");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse'>";
    echo "<tr style='background:#333;color:#fff'>";
    echo "<th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Status (raw)</th><th>Status Length</th><th>Remarks</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>#" . $row['report_id'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['report_date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['ward']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_by']) . "</td>";
        echo "<td style='background:#fee'>[" . htmlspecialchars($row['status']) . "]</td>";
        echo "<td>" . strlen($row['status']) . " chars</td>";
        echo "<td>" . htmlspecialchars($row['remarks'] ?: '—') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>Status Values Found:</h3>";
    $statuses = $conn->query("SELECT DISTINCT status FROM p1014_inventory_reports");
    while ($s = $statuses->fetch_assoc()) {
        echo "<p>Status: [" . htmlspecialchars($s['status']) . "] (length: " . strlen($s['status']) . ")</p>";
    }
} else {
    echo "<p>No reports found</p>";
}

$conn->close();
?>

<style>
body { font-family: Arial; padding: 20px; background: #1a1a1a; color: #fff; }
table { width: 100%; margin: 20px 0; background: #2a2a2a; }
th { text-align: left; padding: 10px; }
td { padding: 10px; background: #2a2a2a; border-bottom: 1px solid #3a3a3a; }
</style>
