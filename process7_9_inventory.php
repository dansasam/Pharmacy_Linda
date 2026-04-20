<?php
require_once __DIR__ . '/process7_9_helpers.php';
require_login();
require_role('Intern');
ensure_process7_9_inventory_table();
ensure_process7_9_tasks_table();
ensure_process7_9_notifications_table();

$currentUser = current_user();
$userId = (int) $currentUser['id'];

$message = sanitize_text($_GET['message'] ?? '');

if (isset($_GET['delete_id'])) {
    $deleteId = (int) $_GET['delete_id'];
    $stmt = $pdo->prepare('DELETE FROM product_inventory WHERE product_id = ?');
    $stmt->execute([$deleteId]);
    header('Location: process7_9_inventory.php?message=' . urlencode('Inventory entry removed.'));
    exit;
}

$notifications = get_process7_9_notifications_for_user($userId);
mark_process7_9_notifications_read($userId);

$products = $pdo->query('SELECT product_id, drug_name, manufacturer, record_date, invoice_no, current_inventory, initial_comments FROM product_inventory ORDER BY product_id DESC')->fetchAll();
$tasks = $pdo->query('SELECT task_id, employee_name, task_name, description, status, start_date, deadline, attachment_path FROM tasks ORDER BY task_id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inventory & Task Tracker</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="dashboard_intern.php">Intern Dashboard</a>
                <a href="process7_9_inventory_add.php">Add Inventory</a>
                <a href="process7_9_orientation.php">Orientation Tracker</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <div>
                    <h1>Inventory & Tasks</h1>
                    <div>Welcome, <?php echo sanitize_text($currentUser['full_name']); ?></div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    <a href="create_inventory_report.php" class="btn btn-primary">Create Inventory Report</a>
                </div>
            </header>
            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <section class="section-card">
                <div class="section-header">
                    <h2>Task Notifications</h2>
                </div>
                <?php if (count($notifications) > 0): ?>
                    <div class="message-list">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-card">
                                <strong><?php echo sanitize_text($notification['title']); ?></strong>
                                <p><?php echo sanitize_text($notification['message']); ?></p>
                                <span class="muted-text"><?php echo sanitize_text($notification['created_at']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="muted-text">No new task notifications.</p>
                <?php endif; ?>
            </section>
            <section class="section-card">
                <div class="section-header">
                    <h2>Assigned Tasks</h2>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Task ID</th>
                                <th>Employee</th>
                                <th>Task</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Start Date</th>
                                <th>Deadline</th>
                                <th>File</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($tasks) > 0): ?>
                                <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td><?php echo (int) $task['task_id']; ?></td>
                                        <td><?php echo sanitize_text($task['employee_name']); ?></td>
                                        <td><?php echo sanitize_text($task['task_name']); ?></td>
                                        <td><?php echo sanitize_text($task['description']); ?></td>
                                        <td><?php echo sanitize_text($task['status']); ?></td>
                                        <td><?php echo sanitize_text($task['start_date']); ?></td>
                                        <td><?php echo sanitize_text($task['deadline']); ?></td>
                                        <td>
                                            <?php if (!empty($task['attachment_path'])): ?>
                                                <a class="action-btn" href="<?php echo sanitize_text($task['attachment_path']); ?>" target="_blank">View File</a>
                                            <?php else: ?>
                                                <span class="muted-text">No file</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="muted-text">No tasks available yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="section-card">
                <div class="section-header">
                    <h2>Product Inventory</h2>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Drug ID</th>
                                <th>Name</th>
                                <th>Manufacturer</th>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th>Current Inventory</th>
                                <th>Comments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($products) > 0): ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo (int) $product['product_id']; ?></td>
                                        <td><?php echo sanitize_text($product['drug_name']); ?></td>
                                        <td><?php echo sanitize_text($product['manufacturer']); ?></td>
                                        <td><?php echo sanitize_text($product['record_date']); ?></td>
                                        <td><?php echo sanitize_text($product['invoice_no']); ?></td>
                                        <td><?php echo (int) $product['current_inventory']; ?></td>
                                        <td><?php echo sanitize_text($product['initial_comments']); ?></td>
                                        <td>
                                            <a class="action-btn" href="process7_9_inventory_edit.php?drug_id=<?php echo (int) $product['product_id']; ?>">Edit</a>
                                            <a class="action-btn danger-btn" href="process7_9_inventory.php?delete_id=<?php echo (int) $product['product_id']; ?>" onclick="return confirm('Remove this inventory entry?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="muted-text">No inventory records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
