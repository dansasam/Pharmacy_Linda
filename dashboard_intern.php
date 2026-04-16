<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/intern_access_control.php';
require_login();
require_role('Intern');
$user = current_user();

// Update intern status and get current status
$current_status = update_intern_status($user['id']);
$status_message = get_status_message($user);
$can_access_features = can_access_inventory($user);

$requirements = get_requirements();
$policies = get_policies();

$taskCount = 0;
$taskSummary = [];
try {
    $tableCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'tasks'");
    $tableCheck->execute();
    if ((int) $tableCheck->fetchColumn() > 0) {
        $taskCount = (int) $pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn();
        if ($taskCount > 0) {
            $taskSummary = $pdo->query('SELECT task_id, task_name, status, deadline FROM tasks ORDER BY task_id DESC LIMIT 5')->fetchAll();
        }
    }
} catch (PDOException $e) {
    // ignore missing tasks table
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Intern Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="#home" class="active">Home</a>
                <a href="requirements_upload.php">Upload Requirements</a>
                <a href="checklist.php">Checklist</a>
                <a href="policies.php">Policies</a>
                <a href="intern_moa_management.php">MOA Management</a>
                <?php if ($can_access_features): ?>
                <a href="manage_product_inventory.php">Manage Products</a>
                <a href="create_inventory_report.php">Create Inventory Report</a>
                <a href="manage_reports.php">Manage Reports</a>
                <a href="process7_9_tasks.php">My Tasks</a>
                <a href="process7_9_orientation.php">Orientation Tracker</a>
                <?php else: ?>
                <a href="#" class="disabled" title="Complete requirements and get schedule first">Manage Products</a>
                <a href="#" class="disabled" title="Complete requirements and get schedule first">Create Inventory Report</a>
                <a href="#" class="disabled" title="Complete requirements and get schedule first">Manage Reports</a>
                <a href="#" class="disabled" title="Complete requirements and get schedule first">My Tasks</a>
                <a href="#" class="disabled" title="Complete requirements and get schedule first">Orientation Tracker</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Intern Dashboard</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>
            <section id="home" class="section-card">
                <div class="section-header">
                    <h2>Application Status</h2>
                </div>
                
                <?php if ($current_status !== 'active'): ?>
                <div class="ls-alert ls-alert-info" style="margin-bottom:20px">
                    <i class="bi bi-info-circle"></i> <strong>Status: <?php echo ucwords(str_replace('_', ' ', $current_status)); ?></strong>
                    <div style="margin-top:8px"><?php echo $status_message; ?></div>
                </div>
                <?php else: ?>
                <div class="ls-alert ls-alert-success" style="margin-bottom:20px">
                    <i class="bi bi-check-circle"></i> <strong>Status: Active Intern</strong>
                    <div style="margin-top:8px"><?php echo $status_message; ?></div>
                </div>
                <?php endif; ?>

                <div class="section-header">
                    <h2>Progress Overview</h2>
                </div>

                <?php
                // Check for denied reports (with safety check for column existence)
                try {
                    $deniedReports = $pdo->query("SELECT report_id, report_date, ward, denial_remarks FROM p1014_inventory_reports WHERE status='denied' AND created_by='{$user['full_name']}' ORDER BY report_date DESC LIMIT 5")->fetchAll();
                } catch (PDOException $e) {
                    // Column doesn't exist yet, try without denial_remarks
                    $deniedReports = $pdo->query("SELECT report_id, report_date, ward, '' as denial_remarks FROM p1014_inventory_reports WHERE status='denied' AND created_by='{$user['full_name']}' ORDER BY report_date DESC LIMIT 5")->fetchAll();
                }
                
                if (count($deniedReports) > 0):
                ?>
                <div class="ls-alert ls-alert-danger" style="margin-bottom:20px">
                    <i class="bi bi-exclamation-triangle"></i> <strong>You have <?= count($deniedReports) ?> denied report(s)</strong>
                    <?php foreach ($deniedReports as $dr): ?>
                        <div style="margin-top:12px;padding:12px;background:rgba(0,0,0,0.2);border-radius:6px">
                            <strong>Report #<?= $dr['report_id'] ?></strong> (<?= $dr['ward'] ?> - <?= date('M d, Y', strtotime($dr['report_date'])) ?>)
                            <?php if (!empty($dr['denial_remarks'])): ?>
                            <div style="margin-top:6px;color:#fca5a5">
                                <strong>Reason:</strong> <?= htmlspecialchars($dr['denial_remarks']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div style="margin-top:12px">
                        <a href="create_inventory_report.php" style="color:#fff;text-decoration:underline">Create a new report</a> with the corrections.
                    </div>
                </div>
                <?php endif; ?>

                <div class="stats-grid">
                    <div class="stat-card">
                        <span>Total requirements</span>
                        <strong id="stat-total">0</strong>
                    </div>
                    <div class="stat-card">
                        <span>Uploaded</span>
                        <strong id="stat-uploaded">0</strong>
                    </div>
                    <div class="stat-card">
                        <span>Approved</span>
                        <strong id="stat-approved">0</strong>
                    </div>
                    <div class="stat-card">
                        <span>Missing</span>
                        <strong id="stat-missing">0</strong>
                    </div>
                </div>
                <div class="progress-card">
                    <div class="progress-label">Completion</div>
                    <div class="progress-bar-wrap">
                        <div id="progress-bar" class="progress-bar"></div>
                    </div>
                    <div id="progress-text">0% complete</div>
                </div>
            </section>
            <section id="schedule" class="section-card">
                <div class="section-header">
                    <h2>My Schedule</h2>
                </div>
                <div id="my-schedule"></div>
            </section>
            <section id="interview" class="section-card">
                <div class="section-header">
                    <h2>Interview Schedule</h2>
                </div>
                <div id="interview-schedule"></div>
            </section>
            <section id="tasks" class="section-card">
                <div class="section-header">
                    <h2>Assigned Tasks</h2>
                </div>
                <p class="section-copy">Your HR-assigned tasks are reflected here for quick reference.</p>
                <?php if ($taskCount > 0): ?>
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Task ID</th>
                                    <th>Task</th>
                                    <th>Status</th>
                                    <th>Deadline</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($taskSummary as $task): ?>
                                    <tr>
                                        <td><?php echo (int) $task['task_id']; ?></td>
                                        <td><?php echo sanitize_text($task['task_name']); ?></td>
                                        <td><?php echo sanitize_text($task['status']); ?></td>
                                        <td><?php echo sanitize_text($task['deadline']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top:16px;">
                        <a class="btn btn-secondary" href="process7_9_inventory.php">View all inventory & tasks</a>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        No tasks have been assigned yet. Visit <a href="process7_9_inventory.php">Inventory & Tasks</a> for details.
                    </div>
                <?php endif; ?>
            </section>
        
        </main>
    </div>
    <script>
        window.pageData = { role: 'Intern', userId: <?php echo $user['id']; ?> };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>
