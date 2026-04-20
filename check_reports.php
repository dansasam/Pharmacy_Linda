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

echo "<h2>Inventory Reports in Database (Real-time):</h2>";
echo "<p><small>Last checked: " . date('Y-m-d H:i:s') . "</small></p>";

$result = $conn->query("SELECT report_id, report_date, ward, created_by, status, remarks FROM p1014_inventory_reports ORDER BY report_id DESC");

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;width:100%'>";
    echo "<tr style='background:#333;color:#fff'>";
    echo "<th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Status</th><th>Remarks</th><th>Action</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        $statusColor = $row['status'] === 'submitted' ? '#f1c40f' : ($row['status'] === 'approved' ? '#2ecc71' : '#e74c3c');
        echo "<tr>";
        echo "<td><strong>#" . $row['report_id'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['report_date']) . "</td>";
        echo "<td>" . htmlspecialchars($row['ward']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_by']) . "</td>";
        echo "<td style='background:$statusColor;color:#fff;font-weight:bold;text-align:center'>" . strtoupper($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['remarks'] ?: '—') . "</td>";
        echo "<td>";
        echo "<form method='POST' style='display:inline' onsubmit='return confirm(\"Delete report #" . $row['report_id'] . "?\")'>";
        echo "<input type='hidden' name='delete_report_id' value='" . $row['report_id'] . "'>";
        echo "<button type='submit' style='background:#e74c3c;color:#fff;border:none;padding:5px 10px;cursor:pointer'>Delete</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p><strong>Total reports: " . $result->num_rows . "</strong></p>";
} else {
    echo "<div style='background:#efe;padding:20px;border:2px solid #0f0;margin:20px 0'>";
    echo "<h3 style='color:#0a0'>✅ NO REPORTS IN DATABASE</h3>";
    echo "<p>The p1014_inventory_reports table is empty. All reports have been deleted.</p>";
    echo "</div>";
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report_id'])) {
    $delete_id = (int)$_POST['delete_report_id'];
    
    // Delete report items first
    $conn->query("DELETE FROM p1014_inventory_report_items WHERE report_id=$delete_id");
    
    // Delete report
    $conn->query("DELETE FROM p1014_inventory_reports WHERE report_id=$delete_id");
    
    echo "<div style='background:#efe;padding:20px;border:2px solid #0f0;margin:20px 0'>";
    echo "<h3 style='color:#0a0'>✅ Report #$delete_id DELETED!</h3>";
    echo "<p>Refreshing page...</p>";
    echo "</div>";
    echo "<script>setTimeout(function(){ location.reload(); }, 1000);</script>";
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #1a1a1a; color: #fff; }
table { width: 100%; margin: 20px 0; }
th { text-align: left; padding: 10px; }
td { padding: 10px; background: #2a2a2a; }
tr:hover td { background: #3a3a3a; }
button { cursor: pointer; }
button:hover { opacity: 0.8; }
</style>

<div style='margin-top:30px;padding:20px;background:#2a2a2a;border-radius:8px'>
    <h3>Actions:</h3>
    <p><a href='view_inventory_report.php' style='color:#3498db'>← Back to View Inventory Reports</a></p>
    <p><a href='javascript:location.reload()' style='color:#2ecc71'>🔄 Refresh This Page</a></p>
    <p><strong>Note:</strong> If reports still show on other pages after deleting here, clear your browser cache (Ctrl+Shift+Delete)</p>
</div>
