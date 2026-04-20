<?php
require_once __DIR__ . '/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Fix All Reports</title>";
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
.btn { display: inline-block; padding: 15px 30px; background: #2ecc71; color: #fff; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: bold; font-size: 1.1em; }
code { background: #3a3a3a; padding: 3px 8px; border-radius: 4px; color: #3498db; }
</style></head><body>";

echo "<h1>🔧 Fix All Reports Status</h1>";

// Step 1: Show all reports
echo "<div class='box'>";
echo "<h2>Step 1: Current Reports in Database</h2>";
$allReports = $conn->query("SELECT report_id, report_date, ward, created_by, status FROM p1014_inventory_reports ORDER BY report_id DESC");

if ($allReports && $allReports->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Status</th></tr>";
    
    while ($r = $allReports->fetch_assoc()) {
        $statusColor = $r['status'] === 'approved' ? '#2ecc71' : ($r['status'] === 'submitted' ? '#f1c40f' : '#e74c3c');
        echo "<tr>";
        echo "<td><strong>#" . $r['report_id'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($r['report_date']) . "</td>";
        echo "<td>" . htmlspecialchars($r['ward']) . "</td>";
        echo "<td>" . htmlspecialchars($r['created_by']) . "</td>";
        echo "<td style='background:$statusColor;color:#fff;font-weight:bold;text-align:center'>" . strtoupper($r['status'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No reports found</p>";
}
echo "</div>";

// Step 2: Update all submitted reports to approved
echo "<div class='box success'>";
echo "<h2>Step 2: Updating All SUBMITTED Reports to APPROVED</h2>";

$updateQuery = "UPDATE p1014_inventory_reports SET status = 'approved' WHERE status = 'submitted' OR status IS NULL OR status = ''";
echo "<p><code>$updateQuery</code></p>";

$result = $conn->query($updateQuery);

if ($result) {
    echo "<p style='color:#2ecc71;font-size:1.2em'>✅ UPDATE executed successfully</p>";
    echo "<p>Affected rows: <strong>" . $conn->affected_rows . "</strong></p>";
    
    if ($conn->affected_rows > 0) {
        echo "<p style='color:#2ecc71;font-weight:bold'>✅ " . $conn->affected_rows . " report(s) updated to APPROVED!</p>";
    } else {
        echo "<p style='color:#f1c40f'>⚠️ No rows affected (reports might already be approved)</p>";
    }
} else {
    echo "<p style='color:#e74c3c'>❌ UPDATE failed: " . $conn->error . "</p>";
}
echo "</div>";

// Step 3: Show updated reports
echo "<div class='box success'>";
echo "<h2>Step 3: Updated Reports</h2>";
$updatedReports = $conn->query("SELECT report_id, report_date, ward, created_by, status FROM p1014_inventory_reports ORDER BY report_id DESC");

if ($updatedReports && $updatedReports->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Report #</th><th>Date</th><th>Ward</th><th>Created By</th><th>Status</th></tr>";
    
    $approvedCount = 0;
    while ($r = $updatedReports->fetch_assoc()) {
        $statusColor = $r['status'] === 'approved' ? '#2ecc71' : ($r['status'] === 'submitted' ? '#f1c40f' : '#e74c3c');
        if ($r['status'] === 'approved') $approvedCount++;
        
        echo "<tr>";
        echo "<td><strong>#" . $r['report_id'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($r['report_date']) . "</td>";
        echo "<td>" . htmlspecialchars($r['ward']) . "</td>";
        echo "<td>" . htmlspecialchars($r['created_by']) . "</td>";
        echo "<td style='background:$statusColor;color:#fff;font-weight:bold;text-align:center'>" . strtoupper($r['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color:#2ecc71;font-size:1.3em;font-weight:bold'>✅ Total APPROVED reports: $approvedCount</p>";
} else {
    echo "<p>No reports found</p>";
}
echo "</div>";

// Step 4: Instructions
echo "<div class='box'>";
echo "<h2>Step 4: Clear Browser Cache & Test</h2>";
echo "<p style='font-size:1.1em'><strong>⚠️ IMPORTANT: You MUST clear your browser cache!</strong></p>";
echo "<ol style='font-size:1.05em;line-height:1.8'>";
echo "<li><strong>Clear Cache:</strong> Press <code>Ctrl + Shift + Delete</code></li>";
echo "<li>Select <strong>'Cached images and files'</strong></li>";
echo "<li>Click <strong>'Clear data'</strong></li>";
echo "<li><strong>OR</strong> just press <code>Ctrl + F5</code> multiple times</li>";
echo "<li><strong>OR</strong> open in Incognito mode: <code>Ctrl + Shift + N</code></li>";
echo "</ol>";

echo "<p style='margin-top:30px'><a href='view_inventory_report.php' class='btn'>→ Go to View Inventory Reports</a></p>";

echo "<div style='background:#3a2a2a;padding:15px;border-radius:8px;margin-top:20px;border-left:4px solid #2ecc71'>";
echo "<h3 style='color:#2ecc71;margin-top:0'>What You Should See:</h3>";
echo "<ul style='line-height:1.8'>";
echo "<li>✅ Green section at top: <strong>'Approved Reports - Ready for Requisition'</strong></li>";
echo "<li>✅ Report #8 and #9 with <strong>green 'APPROVED'</strong> badge</li>";
echo "<li>✅ <strong>'Request'</strong> or <strong>'Create Request'</strong> button next to each approved report</li>";
echo "<li>✅ Click the button to go to requisition form</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

$conn->close();
echo "</body></html>";
?>
