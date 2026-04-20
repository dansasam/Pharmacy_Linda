<?php
require_once __DIR__ . '/process7_9_helpers.php';
require_login();
require_role('HR Personnel');
ensure_process7_9_tasks_table();

$message = sanitize_text($_GET['message'] ?? '');

if (isset($_GET['delete_id'])) {
    $deleteId = (int) $_GET['delete_id'];
    $stmt = $pdo->prepare('DELETE FROM tasks WHERE task_id = ?');
    $stmt->execute([$deleteId]);
    header('Location: process7_9_tasks.php?message=' . urlencode('Task deleted successfully.'));
    exit;
}

$tasks = $pdo->query('SELECT task_id, employee_name, task_name, description, status, start_date, deadline, attachment_path FROM tasks ORDER BY task_id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HR Task Management</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="dashboard_hr.php">Home</a>
                <a href="dashboard_hr.php#requirements">Manage Requirements</a>
                <a href="dashboard_hr.php#policies">Manage Policies</a>
                <a href="dashboard_hr.php#reviews">Review Submissions</a>
                <a href="dashboard_hr.php#approve">Approve Applicants</a>
                <a href="interview_management.php">Interview Management</a>
                <a href="schedule_management.php">Schedule Management</a>
                <a href="process7_9_tasks.php" class="active">Task Management</a>
                <a href="moa_management.php">MOA Management</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Task Management</h1>
                <div>Welcome, <?php echo sanitize_text(current_user()['full_name']); ?></div>
            </header>
            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <section class="section-card">
                <div class="section-header">
                    <h2>Active Tasks</h2>
                    <a href="process7_9_task_add.php" class="btn btn-primary">+ Add New Task</a>
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
                                <th>Actions</th>
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
                                        <td>
                                            <a class="action-btn" href="process7_9_task_edit.php?task_id=<?php echo (int) $task['task_id']; ?>">Edit</a>
                                            <a class="action-btn danger-btn" href="process7_9_tasks.php?delete_id=<?php echo (int) $task['task_id']; ?>" onclick="return confirm('Delete this task?');">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="muted-text">No tasks have been created yet.</td>
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
