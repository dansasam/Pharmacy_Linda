<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_login();
require_role('Pharmacy Technician');
$user = current_user();

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

global $pdo;
$pendingReports = $pdo->query("SELECT report_id, report_date, ward, created_by, remarks FROM p1014_inventory_reports WHERE status='submitted' ORDER BY report_date DESC, report_id DESC LIMIT 10")->fetchAll();
$pendingCount = count($pendingReports);
$reviewedCount = (int) $pdo->query("SELECT COUNT(*) FROM p1014_inventory_reports WHERE status='reviewed'")->fetchColumn();

// Get all submitted reports with details
$allReports = $conn->query("SELECT r.*, COUNT(i.item_id) as item_count 
    FROM p1014_inventory_reports r 
    LEFT JOIN p1014_inventory_report_items i ON r.report_id = i.report_id 
    WHERE r.status='submitted' 
    GROUP BY r.report_id 
    ORDER BY r.report_date DESC, r.report_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pharmacy Technician Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="#home" class="active">Dashboard</a>
                <a href="view_inventory_report.php">Inventory Reports</a>
                <a href="stock_report_dashboard.php">Stock Status</a>
                <a href="requisition_form.php">Create Requisition</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Pharmacy Technician Dashboard</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>
            <section id="home" class="section-card">
                <div class="section-header">
                    <h2>Technician Overview</h2>
                </div>
                <p>Manage inventory checks and requisitions from one focused dashboard.</p>
                <div class="stats-grid">
                    <div class="stat-card">
                        <span>Inventory Reports</span>
                        <strong>View & Check</strong>
                    </div>
                    <div class="stat-card">
                        <span>Stock Monitoring</span>
                        <strong>Track levels</strong>
                    </div>
                    <div class="stat-card">
                        <span>Requisitions</span>
                        <strong>Request supplies</strong>
                    </div>
                    <div class="stat-card">
                        <span>Critical Items</span>
                        <strong>Low/Out of stock</strong>
                    </div>
                </div>
            </section>
            <section id="pending-reports" class="section-card">
                <div class="section-header">
                    <h2>Submitted Inventory Reports</h2>
                </div>
                <p class="section-copy">Review submitted inventory reports from interns. Click "View" to see details or "Request" to create a requisition.</p>
                <div class="stats-grid">
                    <div class="stat-card">
                        <span>Pending Reports</span>
                        <strong><?php echo $pendingCount; ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Total Items</span>
                        <strong><?php 
                            $totalItems = 0;
                            foreach ($pendingReports as $rep) {
                                $cnt = $pdo->query("SELECT COUNT(*) FROM p1014_inventory_report_items WHERE report_id={$rep['report_id']}")->fetchColumn();
                                $totalItems += $cnt;
                            }
                            echo $totalItems;
                        ?></strong>
                    </div>
                </div>
                <?php if ($allReports && $allReports->num_rows > 0): ?>
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Report #</th>
                                    <th>Date</th>
                                    <th>Ward</th>
                                    <th>Submitted By</th>
                                    <th style="text-align:center">Items</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($report = $allReports->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo (int) $report['report_id']; ?></td>
                                        <td><?php echo htmlspecialchars($report['report_date']); ?></td>
                                        <td><?php echo htmlspecialchars($report['ward']); ?></td>
                                        <td><?php echo htmlspecialchars($report['created_by']); ?></td>
                                        <td style="text-align:center"><?php echo (int) $report['item_count']; ?></td>
                                        <td><?php echo htmlspecialchars($report['remarks'] ?: '—'); ?></td>
                                        <td>
                                            <a class="btn btn-primary" href="view_inventory_report.php?report_id=<?php echo (int) $report['report_id']; ?>">View</a>
                                            <a class="btn btn-danger" href="requisition_form.php?report_id=<?php echo (int) $report['report_id']; ?>">Request</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        No pending reports. Awaiting the next inventory report submission from the intern.
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script>
        window.pageData = { role: 'Pharmacy Technician' };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>
