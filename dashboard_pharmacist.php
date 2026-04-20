<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_login();
require_role('Pharmacist');
$user = current_user();

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

// Get pending requisitions count
$pendingReqs = $conn->query("SELECT COUNT(*) AS c FROM p1014_requisition_requests WHERE status='pending'")->fetch_assoc()['c'];

// Get recent pending requisitions
$recentReqs = $conn->query("SELECT rq.*, COUNT(ri.req_item_id) AS total_items, COALESCE(SUM(ri.is_out_of_stock),0) AS oos_count FROM p1014_requisition_requests rq LEFT JOIN p1014_requisition_items ri ON rq.requisition_id=ri.requisition_id WHERE rq.status='pending' GROUP BY rq.requisition_id ORDER BY rq.created_at DESC LIMIT 5");

// Get stock statistics from latest report
$latest_id = $conn->query("SELECT MAX(report_id) AS rid FROM p1014_inventory_reports")->fetch_assoc()['rid'] ?? 0;
$total = $conn->query("SELECT COUNT(*) AS c FROM product_inventory")->fetch_assoc()['c'];
if ($latest_id) {
    $out = $conn->query("SELECT COUNT(*) AS c FROM p1014_inventory_report_items i JOIN product_inventory p ON i.product_id=p.product_id WHERE i.report_id=$latest_id AND i.stock_on_hand=0")->fetch_assoc()['c'];
    $low = $conn->query("SELECT COUNT(*) AS c FROM p1014_inventory_report_items i JOIN product_inventory p ON i.product_id=p.product_id WHERE i.report_id=$latest_id AND i.stock_on_hand>0 AND i.stock_on_hand<=p.current_inventory")->fetch_assoc()['c'];
    $ok  = $conn->query("SELECT COUNT(*) AS c FROM p1014_inventory_report_items i JOIN product_inventory p ON i.product_id=p.product_id WHERE i.report_id=$latest_id AND i.stock_on_hand>p.current_inventory")->fetch_assoc()['c'];
} else { $out = $low = $ok = 0; }

// Get approved and rejected counts
$approvedCount = $conn->query("SELECT COUNT(*) AS c FROM p1014_requisition_requests WHERE status='approved'")->fetch_assoc()['c'];
$rejectedCount = $conn->query("SELECT COUNT(*) AS c FROM p1014_requisition_requests WHERE status='rejected'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pharmacist Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="#home" class="active">Dashboard</a>
                <a href="#requisitions">Requisition Requests</a>
                <a href="#stock-reports">Stock Reports</a>
                <a href="requisition_approval.php">Manage Requisitions</a>
                <a href="stock_report_dashboard.php">Full Stock Report</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Pharmacist Dashboard</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>
            
            <section id="home" class="section-card">
                <div class="section-header">
                    <h2>Pharmacist Overview</h2>
                </div>
                <p>Review and approve requisition requests from technicians and monitor pharmacy stock levels.</p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <span>Pending Requisitions</span>
                        <strong><?php echo $pendingReqs; ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Approved Requisitions</span>
                        <strong><?php echo $approvedCount; ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Out of Stock Items</span>
                        <strong><?php echo $out; ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Total Products</span>
                        <strong><?php echo $total; ?></strong>
                    </div>
                </div>
            </section>

            <section id="requisitions" class="section-card">
                <div class="section-header">
                    <h2>Pending Requisition Requests</h2>
                </div>
                <p class="section-copy">Review and approve requisition requests submitted by pharmacy technicians.</p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <span>Pending Approval</span>
                        <strong><?php echo $pendingReqs; ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Approved</span>
                        <strong><?php echo $approvedCount; ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Rejected</span>
                        <strong><?php echo $rejectedCount; ?></strong>
                    </div>
                </div>

                <?php if ($recentReqs && $recentReqs->num_rows > 0): ?>
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Requested By</th>
                                    <th>Department</th>
                                    <th style="text-align:center">Items</th>
                                    <th style="text-align:center">OOS</th>
                                    <th style="text-align:right">Total (₱)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = $recentReqs->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?= $r['requisition_id'] ?></td>
                                        <td><?= $r['requisition_date'] ?></td>
                                        <td><?= htmlspecialchars($r['requested_by']) ?></td>
                                        <td><?= htmlspecialchars($r['department']?:'—') ?></td>
                                        <td style="text-align:center"><?= $r['total_items'] ?></td>
                                        <td style="text-align:center">
                                            <?= $r['oos_count']>0?$r['oos_count'].' OOS':'—' ?>
                                        </td>
                                        <td style="text-align:right">₱<?= number_format($r['total_amount'],2) ?></td>
                                        <td>
                                            <a href="view_requisition.php?req_id=<?= $r['requisition_id'] ?>" class="btn btn-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        No pending requisition requests at this time.
                    </div>
                <?php endif; ?>
            </section>

            <section id="stock-reports" class="section-card">
                <div class="section-header">
                    <h2>Stock Status Overview</h2>
                </div>
                <p class="section-copy">Current pharmacy inventory status based on the latest stock report.</p>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <span>Total Products</span>
                        <strong><?php echo $total; ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Out of Stock</span>
                        <strong><?php echo $out; ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Low Stock</span>
                        <strong><?php echo $low; ?></strong>
                    </div>
                    <div class="stat-card">
                        <span>Sufficient Stock</span>
                        <strong><?php echo $ok; ?></strong>
                    </div>
                </div>

                <?php if ($latest_id): 
                    // Get critical items (out of stock or low stock)
                    $criticalItems = $conn->query("SELECT p.drug_name AS item_name, p.manufacturer AS category, i.stock_on_hand, p.current_inventory AS reorder_level FROM p1014_inventory_report_items i JOIN product_inventory p ON i.product_id=p.product_id WHERE i.report_id=$latest_id AND (i.stock_on_hand=0 OR i.stock_on_hand<=p.current_inventory) ORDER BY i.stock_on_hand ASC, p.drug_name LIMIT 10");
                    
                    if ($criticalItems && $criticalItems->num_rows > 0):
                ?>
                    <div style="margin-top:24px;">
                        <h3 style="font-size:1rem;font-weight:700;margin-bottom:16px;color:var(--text-dark);">Critical Stock Items</h3>
                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Drug Name</th>
                                        <th>Manufacturer</th>
                                        <th style="text-align:center">Current Stock</th>
                                        <th style="text-align:center">Reorder Level</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = $criticalItems->fetch_assoc()): 
                                        if ($item['stock_on_hand']==0) { 
                                            $st='OUT OF STOCK'; 
                                        } else { 
                                            $st='LOW STOCK'; 
                                        }
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                                            <td><?= htmlspecialchars($item['category']) ?></td>
                                            <td style="text-align:center"><?= $item['stock_on_hand'] ?></td>
                                            <td style="text-align:center"><?= $item['reorder_level'] ?></td>
                                            <td><?= $st ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; endif; ?>

                <div class="action-grid">
                    <a class="action-card" href="requisition_approval.php">
                        <strong>Manage Requisitions</strong>
                        <span>Review, approve, or deny requisition requests from technicians.</span>
                    </a>
                    <a class="action-card" href="stock_report_dashboard.php">
                        <strong>Full Stock Report</strong>
                        <span>View detailed inventory levels across all products.</span>
                    </a>
                </div>
            </section>
        </main>
    </div>
    <script>
        window.pageData = { role: 'Pharmacist' };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>
