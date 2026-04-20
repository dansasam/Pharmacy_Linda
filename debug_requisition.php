<?php
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Debug Requisition</title>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #1a1a1a; color: #fff; }
.box { background: #2a2a2a; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 5px solid #3498db; }
.success { border-color: #2ecc71; }
.error { border-color: #e74c3c; }
h1 { color: #3498db; }
h2 { color: #2ecc71; margin-top: 0; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; background: #2a2a2a; }
th { background: #333; padding: 10px; text-align: left; color: #fff; }
td { padding: 10px; border-bottom: 1px solid #3a3a3a; }
code { background: #3a3a3a; padding: 3px 8px; border-radius: 4px; color: #3498db; }
.btn { display: inline-block; padding: 15px 30px; background: #2ecc71; color: #fff; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: bold; font-size: 1.1em; }
</style></head><body>";

echo "<h1>🔍 Debug Requisition Form Query</h1>";

// Test 1: Show ALL reports
echo "<div class='box'>";
echo "<h2>Test 1: ALL Reports in Database</h2>";
$all = $conn->query("SELECT report_id, report_date, ward, created_by, status FROM p1014_inventory_reports ORDER BY report_id DESC");

if ($all && $all->num_rows > 0) {
    echo "<p>Total reports: <strong>" . $all->num_rows . "</strong></p>";
    echo "<table>";
    echo "<tr><th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Status (raw)</th><th>Status Length</th></tr>";
    
    while ($r = $all->fetch_assoc()) {
        $statusColor = $r['status'] === 'approved' ? '#2ecc71' : ($r['status'] === 'submitted' ? '#f1c40f' : '#e74c3c');
        echo "<tr>";
        echo "<td><strong>#" . $r['report_id'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($r['report_date']) . "</td>";
        echo "<td>" . htmlspecialchars($r['ward']) . "</td>";
        echo "<td>" . htmlspecialchars($r['created_by']) . "</td>";
        echo "<td style='background:$statusColor;color:#fff;font-weight:bold;text-align:center'>[" . $r['status'] . "]</td>";
        echo "<td>" . strlen($r['status']) . " chars</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:#e74c3c'>No reports found!</p>";
}
echo "</div>";

// Test 2: Test the EXACT query from requisition_form.php
echo "<div class='box'>";
echo "<h2>Test 2: Approved Reports Query (from requisition_form.php)</h2>";

$query = "SELECT r.*, 
    (SELECT COUNT(*) FROM p1014_inventory_report_items WHERE report_id = r.report_id) as item_count,
    (SELECT COUNT(*) FROM p1014_inventory_report_items i JOIN product_inventory p ON i.product_id = p.product_id 
     WHERE i.report_id = r.report_id AND (i.stock_on_hand = 0 OR i.stock_on_hand <= p.current_inventory)) as critical_items
    FROM p1014_inventory_reports r 
    WHERE r.status='approved'
    ORDER BY r.report_date DESC, r.report_id DESC";

echo "<p><strong>Query:</strong></p>";
echo "<pre style='background:#3a3a3a;padding:15px;border-radius:8px;overflow-x:auto'>" . htmlspecialchars($query) . "</pre>";

$approved = $conn->query($query);

if ($approved === false) {
    echo "<p style='color:#e74c3c;font-size:1.2em'>❌ QUERY FAILED!</p>";
    echo "<p><strong>Error:</strong> " . $conn->error . "</p>";
} else {
    echo "<p style='color:#2ecc71;font-size:1.2em'>✅ Query executed successfully</p>";
    echo "<p>Found <strong>" . $approved->num_rows . "</strong> approved reports</p>";
    
    if ($approved->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Items</th><th>Critical Items</th><th>Status</th></tr>";
        
        while ($r = $approved->fetch_assoc()) {
            echo "<tr>";
            echo "<td><strong>#" . $r['report_id'] . "</strong></td>";
            echo "<td>" . htmlspecialchars($r['report_date']) . "</td>";
            echo "<td>" . htmlspecialchars($r['ward']) . "</td>";
            echo "<td>" . htmlspecialchars($r['created_by']) . "</td>";
            echo "<td style='text-align:center'>" . $r['item_count'] . "</td>";
            echo "<td style='text-align:center'>" . $r['critical_items'] . "</td>";
            echo "<td style='background:#2ecc71;color:#fff;font-weight:bold;text-align:center'>" . strtoupper($r['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background:#1e4620;padding:15px;border-radius:8px;margin-top:20px;border-left:4px solid #2ecc71'>";
        echo "<p style='color:#2ecc71;font-size:1.2em;font-weight:bold'>✅ These reports SHOULD appear in requisition_form.php!</p>";
        echo "</div>";
    } else {
        echo "<div style='background:#4a1e1e;padding:15px;border-radius:8px;margin-top:20px;border-left:4px solid #e74c3c'>";
        echo "<p style='color:#e74c3c;font-size:1.2em;font-weight:bold'>❌ NO APPROVED REPORTS FOUND!</p>";
        echo "<p>This is why requisition_form.php shows 'No approved reports available'</p>";
        echo "</div>";
    }
}
echo "</div>";

// Test 3: Check for status issues
echo "<div class='box'>";
echo "<h2>Test 3: Status Value Analysis</h2>";

$statuses = $conn->query("SELECT DISTINCT status, COUNT(*) as count FROM p1014_inventory_reports GROUP BY status");

echo "<table>";
echo "<tr><th>Status Value</th><th>Count</th><th>Matches 'approved'?</th></tr>";

while ($s = $statuses->fetch_assoc()) {
    $matches = ($s['status'] === 'approved') ? '✅ YES' : '❌ NO';
    $matchColor = ($s['status'] === 'approved') ? '#2ecc71' : '#e74c3c';
    echo "<tr>";
    echo "<td><strong>[" . htmlspecialchars($s['status']) . "]</strong> (length: " . strlen($s['status']) . ")</td>";
    echo "<td>" . $s['count'] . "</td>";
    echo "<td style='background:$matchColor;color:#fff;font-weight:bold;text-align:center'>$matches</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Test 4: Force update and retest
echo "<div class='box success'>";
echo "<h2>Test 4: Force Update to 'approved'</h2>";

$updateQuery = "UPDATE p1014_inventory_reports SET status = 'approved' WHERE status != 'approved' OR status IS NULL";
echo "<p><code>$updateQuery</code></p>";

$result = $conn->query($updateQuery);

if ($result) {
    echo "<p style='color:#2ecc71'>✅ UPDATE executed</p>";
    echo "<p>Affected rows: <strong>" . $conn->affected_rows . "</strong></p>";
    
    // Retest the query
    $retestApproved = $conn->query("SELECT COUNT(*) as count FROM p1014_inventory_reports WHERE status='approved'")->fetch_assoc();
    echo "<p style='color:#2ecc71;font-size:1.3em;font-weight:bold'>✅ Now " . $retestApproved['count'] . " reports are approved!</p>";
} else {
    echo "<p style='color:#e74c3c'>❌ UPDATE failed: " . $conn->error . "</p>";
}
echo "</div>";

// Next steps
echo "<div class='box'>";
echo "<h2>Next Steps</h2>";
echo "<ol style='font-size:1.05em;line-height:1.8'>";
echo "<li><strong>Clear browser cache:</strong> Ctrl + Shift + Delete</li>";
echo "<li><strong>Hard refresh:</strong> Ctrl + F5</li>";
echo "<li><strong>Go to requisition form:</strong> <a href='requisition_form.php' class='btn'>Open Requisition Form</a></li>";
echo "</ol>";
echo "</div>";

$conn->close();
echo "</body></html>";
?>
