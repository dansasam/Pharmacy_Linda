<?php
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Force Approve</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #1a1a1a; color: #fff; }
.box { background: #2a2a2a; padding: 20px; margin: 20px 0; border-radius: 8px; }
.success { border-left: 5px solid #2ecc71; }
.error { border-left: 5px solid #e74c3c; }
h1 { color: #3498db; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th { background: #333; padding: 10px; text-align: left; color: #fff; }
td { padding: 10px; border-bottom: 1px solid #3a3a3a; }
.btn { display: inline-block; padding: 12px 24px; background: #3498db; color: #fff; text-decoration: none; border-radius: 5px; margin: 10px 5px; font-weight: bold; }
.btn-success { background: #2ecc71; }
</style></head><body>";

echo "<h1>🔧 Force Approve Report #8</h1>";

// Check current status
echo "<div class='box'>";
echo "<h2>Step 1: Current Status</h2>";
$before = $conn->query("SELECT report_id, report_date, ward, created_by, status FROM p1014_inventory_reports WHERE report_id = 8")->fetch_assoc();

if ($before) {
    echo "<table>";
    echo "<tr><th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Status</th></tr>";
    $statusColor = $before['status'] === 'approved' ? '#2ecc71' : '#f1c40f';
    echo "<tr>";
    echo "<td><strong>#" . $before['report_id'] . "</strong></td>";
    echo "<td>" . htmlspecialchars($before['report_date']) . "</td>";
    echo "<td>" . htmlspecialchars($before['ward']) . "</td>";
    echo "<td>" . htmlspecialchars($before['created_by']) . "</td>";
    echo "<td style='background:$statusColor;color:#fff;font-weight:bold;text-align:center'>" . strtoupper($before['status']) . "</td>";
    echo "</tr></table>";
} else {
    echo "<p style='color:#e74c3c'>❌ Report #8 not found!</p>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// Force update
echo "<div class='box success'>";
echo "<h2>Step 2: Forcing Status to 'approved'</h2>";

$updateQuery = "UPDATE p1014_inventory_reports SET status = 'approved' WHERE report_id = 8";
echo "<p><code>$updateQuery</code></p>";

$result = $conn->query($updateQuery);

if ($result) {
    echo "<p style='color:#2ecc71;font-size:1.2em'>✅ UPDATE query executed</p>";
    echo "<p>Affected rows: <strong>" . $conn->affected_rows . "</strong></p>";
} else {
    echo "<p style='color:#e74c3c'>❌ UPDATE failed: " . $conn->error . "</p>";
}
echo "</div>";

// Verify update
echo "<div class='box success'>";
echo "<h2>Step 3: Verify Update</h2>";
$after = $conn->query("SELECT report_id, report_date, ward, created_by, status FROM p1014_inventory_reports WHERE report_id = 8")->fetch_assoc();

echo "<table>";
echo "<tr><th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Status</th></tr>";
$statusColor = $after['status'] === 'approved' ? '#2ecc71' : '#f1c40f';
echo "<tr>";
echo "<td><strong>#" . $after['report_id'] . "</strong></td>";
echo "<td>" . htmlspecialchars($after['report_date']) . "</td>";
echo "<td>" . htmlspecialchars($after['ward']) . "</td>";
echo "<td>" . htmlspecialchars($after['created_by']) . "</td>";
echo "<td style='background:$statusColor;color:#fff;font-weight:bold;text-align:center'>" . strtoupper($after['status']) . "</td>";
echo "</tr></table>";

if ($after['status'] === 'approved') {
    echo "<p style='color:#2ecc71;font-size:1.3em;font-weight:bold'>✅ SUCCESS! Report #8 is now APPROVED!</p>";
} else {
    echo "<p style='color:#e74c3c;font-size:1.3em;font-weight:bold'>❌ FAILED! Status is still: " . strtoupper($after['status']) . "</p>";
}
echo "</div>";

// Next steps
echo "<div class='box'>";
echo "<h2>Step 4: Test the Page</h2>";
echo "<p><strong>IMPORTANT:</strong> Clear your browser cache before testing!</p>";
echo "<ol>";
echo "<li>Press <code>Ctrl + Shift + Delete</code></li>";
echo "<li>Select 'Cached images and files'</li>";
echo "<li>Click 'Clear data'</li>";
echo "<li>Or just press <code>Ctrl + F5</code> to hard refresh</li>";
echo "</ol>";
echo "<p><a href='view_inventory_report.php' class='btn btn-success'>→ Go to View Inventory Reports</a></p>";
echo "<p>You should now see:</p>";
echo "<ul>";
echo "<li>✅ Report #8 in the green 'Approved Reports' section at the top</li>";
echo "<li>✅ Status badge showing 'APPROVED' (green)</li>";
echo "<li>✅ 'Request' button next to 'View' button</li>";
echo "</ul>";
echo "</div>";

$conn->close();
echo "</body></html>";
?>
