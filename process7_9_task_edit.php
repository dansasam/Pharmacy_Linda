<?php
require_once __DIR__ . '/process7_9_helpers.php';
require_login();
require_role('HR Personnel');
ensure_process7_9_tasks_table();

$taskId = (int) ($_GET['task_id'] ?? 0);
if ($taskId <= 0) {
    header('Location: process7_9_tasks.php?message=' . urlencode('Invalid task selected.'));
    exit;
}

$stmt = $pdo->prepare('SELECT task_id, employee_name, task_name, description, status, start_date, deadline, attachment_path FROM tasks WHERE task_id = ?');
$stmt->execute([$taskId]);
$task = $stmt->fetch();
if (!$task) {
    header('Location: process7_9_tasks.php?message=' . urlencode('Task not found.'));
    exit;
}

// Get all users who can be assigned tasks
$employeesStmt = $pdo->query("
    SELECT id, full_name, role, email 
    FROM users 
    WHERE role IN ('Intern', 'Pharmacist', 'Pharmacy Technician', 'Pharmacist Assistant') 
    ORDER BY role, full_name
");
$employees = $employeesStmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = intval($_POST['employee_id'] ?? 0);
    $taskName = sanitize_text($_POST['task_name'] ?? '');
    $description = sanitize_text($_POST['description'] ?? '');
    $status = sanitize_text($_POST['status'] ?? '');
    $startDate = sanitize_text($_POST['start_date'] ?? '');
    $deadline = sanitize_text($_POST['deadline'] ?? '');
    $attachmentPath = $task['attachment_path'];

    if ($employeeId <= 0 || $taskName === '' || $description === '' || $status === '' || $startDate === '' || $deadline === '') {
        $error = 'All fields are required.';
    } elseif (!in_array($status, ['To Do', 'In Progress', 'Done'], true)) {
        $error = 'Invalid status selected.';
    } elseif (strtotime($deadline) < strtotime($startDate)) {
        $error = 'Deadline must be on or after the start date.';
    } else {
        // Get employee name from database
        $empStmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $empStmt->execute([$employeeId]);
        $employeeName = $empStmt->fetchColumn();
        
        if (!$employeeName) {
            $error = 'Selected employee not found.';
        } else {
            if (!empty($_FILES['task_file']['name'])) {
                $allowedExtensions = ['pdf', 'doc', 'docx', 'xlsx', 'xls', 'png', 'jpg', 'jpeg', 'txt'];
                $fileName = $_FILES['task_file']['name'];
                $fileTmp = $_FILES['task_file']['tmp_name'];
                $fileError = (int) ($_FILES['task_file']['error'] ?? UPLOAD_ERR_OK);
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if ($fileError !== UPLOAD_ERR_OK) {
                    $error = 'Failed to upload task file.';
                } elseif (!in_array($fileExtension, $allowedExtensions, true)) {
                    $error = 'Invalid file type. Allowed: pdf, doc, docx, xlsx, xls, png, jpg, jpeg, txt.';
                } else {
                    $uploadDir = __DIR__ . '/uploads/task_files';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $safeFileName = uniqid('task_', true) . '.' . $fileExtension;
                    $targetFile = $uploadDir . DIRECTORY_SEPARATOR . $safeFileName;
                    if (!move_uploaded_file($fileTmp, $targetFile)) {
                        $error = 'Could not save the uploaded file.';
                    } else {
                        $attachmentPath = 'uploads/task_files/' . $safeFileName;
                    }
                }
            }

            if ($error === '') {
                $update = $pdo->prepare('UPDATE tasks SET employee_name = ?, task_name = ?, description = ?, status = ?, start_date = ?, deadline = ?, attachment_path = ? WHERE task_id = ?');
                $update->execute([$employeeName, $taskName, $description, $status, $startDate, $deadline, $attachmentPath, $taskId]);
                
                // Notify the assigned employee
                $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
                $title = 'Task Updated';
                $message = "Task '{$taskName}' has been updated. Status: {$status}. Deadline: {$deadline}";
                $notifStmt->execute([$employeeId, $title, $message]);
                
                header('Location: process7_9_tasks.php?message=' . urlencode('Task updated successfully.'));
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Task</title>
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
                <h1>Edit Task</h1>
                <div>Welcome, <?php echo sanitize_text(current_user()['full_name']); ?></div>
            </header>
            <section class="section-card">
                <div class="section-header">
                    <h2>Edit Task #<?php echo $taskId; ?></h2>
                </div>
                <?php if ($error !== ''): ?>
                    <div class="message error"><?php echo sanitize_text($error); ?></div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data" class="compact-form">
                    <label>Assign To Employee</label>
                    <select name="employee_id" required>
                        <option value="">-- Select Employee --</option>
                        <?php 
                        $currentRole = '';
                        foreach ($employees as $emp): 
                            // Check if this employee is currently assigned
                            $isSelected = ($emp['full_name'] === $task['employee_name']);
                            
                            if ($currentRole !== $emp['role']) {
                                if ($currentRole !== '') {
                                    echo '</optgroup>';
                                }
                                $currentRole = $emp['role'];
                                echo '<optgroup label="' . htmlspecialchars($currentRole) . '">';
                            }
                        ?>
                            <option value="<?php echo intval($emp['id']); ?>" <?php echo ($isSelected || (isset($_POST['employee_id']) && intval($_POST['employee_id']) === intval($emp['id']))) ? 'selected' : ''; ?>>
                                <?php echo sanitize_text($emp['full_name']); ?> (<?php echo sanitize_text($emp['email']); ?>)
                            </option>
                        <?php 
                        endforeach; 
                        if ($currentRole !== '') {
                            echo '</optgroup>';
                        }
                        ?>
                    </select>
                    <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                        Currently assigned to: <strong><?php echo sanitize_text($task['employee_name']); ?></strong>
                    </small>

                    <label>Task Name</label>
                    <input type="text" name="task_name" value="<?php echo sanitize_text($_POST['task_name'] ?? $task['task_name']); ?>" required />

                    <label>Description</label>
                    <textarea name="description" rows="4" required><?php echo sanitize_text($_POST['description'] ?? $task['description']); ?></textarea>

                    <label>Status</label>
                    <select name="status" required>
                        <option value="">-- Choose Status --</option>
                        <option value="To Do"<?php echo (($_POST['status'] ?? $task['status']) === 'To Do') ? ' selected' : ''; ?>>To Do</option>
                        <option value="In Progress"<?php echo (($_POST['status'] ?? $task['status']) === 'In Progress') ? ' selected' : ''; ?>>In Progress</option>
                        <option value="Done"<?php echo (($_POST['status'] ?? $task['status']) === 'Done') ? ' selected' : ''; ?>>Done</option>
                    </select>

                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo sanitize_text($_POST['start_date'] ?? $task['start_date']); ?>" required />

                    <label>Deadline</label>
                    <input type="date" name="deadline" value="<?php echo sanitize_text($_POST['deadline'] ?? $task['deadline']); ?>" required />

                    <label>Upload New File (Optional)</label>
                    <input type="file" name="task_file" accept=".pdf,.doc,.docx,.xlsx,.xls,.png,.jpg,.jpeg,.txt" />

                    <?php if (!empty($task['attachment_path'])): ?>
                        <div style="background: #f3f4f6; padding: 10px; border-radius: 4px; margin-top: 8px;">
                            <strong>Current file:</strong> <a class="action-btn" href="<?php echo sanitize_text($task['attachment_path']); ?>" target="_blank">View File</a>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="process7_9_tasks.php" class="btn" style="text-decoration: none; display: inline-block; text-align: center;">Cancel</a>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
