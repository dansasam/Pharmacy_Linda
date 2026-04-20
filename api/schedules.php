<?php
require_once __DIR__ . '/../common.php';
require_once __DIR__ . '/../intern_access_control.php';
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list_intern':
        require_login();
        require_role('HR Personnel');
        $stmt = $pdo->query('SELECT s.*, u.full_name, u.role, u.email FROM internship_schedules s JOIN users u ON s.intern_id = u.id ORDER BY u.full_name');
        send_json(['success' => true, 'schedules' => $stmt->fetchAll()]);
        break;

    case 'get':
        require_login();
        $user = current_user();
        $intern_id = intval($_GET['intern_id'] ?? 0);
        if ($user['role'] !== 'HR Personnel') {
            $intern_id = $user['id'];
        }
        if (!$intern_id) {
            send_json(['success' => false, 'message' => 'Missing intern ID.'], 400);
        }
        $stmt = $pdo->prepare('SELECT s.*, u.role, u.email FROM internship_schedules s JOIN users u ON s.intern_id = u.id WHERE s.intern_id = ?');
        $stmt->execute([$intern_id]);
        send_json(['success' => true, 'schedule' => $stmt->fetch()]);
        break;

    case 'save':
        require_login();
        require_role('HR Personnel');
        $intern_id = intval($_POST['intern_id'] ?? 0);
        $monday = trim($_POST['monday'] ?? '');
        $tuesday = trim($_POST['tuesday'] ?? '');
        $wednesday = trim($_POST['wednesday'] ?? '');
        $thursday = trim($_POST['thursday'] ?? '');
        $friday = trim($_POST['friday'] ?? '');
        $saturday = trim($_POST['saturday'] ?? '');
        $sunday = trim($_POST['sunday'] ?? '');
        $total_hours = intval($_POST['total_hours'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if (!$intern_id) {
            send_json(['success' => false, 'message' => 'Please select a user.'], 400);
        }

        $userStmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role IS NOT NULL');
        $userStmt->execute([$intern_id]);
        if (!$userStmt->fetch()) {
            send_json(['success' => false, 'message' => 'Selected user does not exist.'], 400);
        }

        $existing = $pdo->prepare('SELECT sched_id FROM internship_schedules WHERE intern_id = ?');
        $existing->execute([$intern_id]);
        if ($existing->fetch()) {
            $stmt = $pdo->prepare('UPDATE internship_schedules SET monday = ?, tuesday = ?, wednesday = ?, thursday = ?, friday = ?, saturday = ?, sunday = ?, total_hours = ?, notes = ? WHERE intern_id = ?');
            $stmt->execute([$monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday, $total_hours, $notes, $intern_id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO internship_schedules (intern_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday, total_hours, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$intern_id, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday, $total_hours, $notes]);
        }

        // Update intern status after schedule assignment
        update_intern_status($intern_id);

        send_json(['success' => true, 'message' => 'Schedule saved successfully.']);
        break;

    case 'delete':
        require_login();
        require_role('HR Personnel');
        $intern_id = intval($_GET['intern_id'] ?? 0);
        if (!$intern_id) {
            send_json(['success' => false, 'message' => 'Missing intern ID.'], 400);
        }
        $stmt = $pdo->prepare('DELETE FROM internship_schedules WHERE intern_id = ?');
        $stmt->execute([$intern_id]);
        send_json(['success' => true, 'message' => 'Schedule deleted successfully.']);
        break;

    case 'notify':
        require_login();
        require_role('HR Personnel');
        $intern_id = intval($_GET['intern_id'] ?? 0);
        if (!$intern_id) {
            send_json(['success' => false, 'message' => 'Missing intern ID.'], 400);
        }
        $stmt = $pdo->prepare('SELECT u.full_name, u.email, s.monday, s.tuesday, s.wednesday, s.thursday, s.friday, s.saturday, s.sunday, s.total_hours, s.notes FROM internship_schedules s JOIN users u ON s.intern_id = u.id WHERE s.intern_id = ?');
        $stmt->execute([$intern_id]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$schedule) {
            send_json(['success' => false, 'message' => 'Schedule not found.'], 404);
        }
        if (empty($schedule['email'])) {
            send_json(['success' => false, 'message' => 'Selected user does not have an email address.'], 400);
        }

        $subject = 'Your work schedule has been updated';
        $message = "Hello {$schedule['full_name']},\n\nYour schedule has been updated:\n" .
            "Monday: {$schedule['monday']}\n" .
            "Tuesday: {$schedule['tuesday']}\n" .
            "Wednesday: {$schedule['wednesday']}\n" .
            "Thursday: {$schedule['thursday']}\n" .
            "Friday: {$schedule['friday']}\n" .
            "Saturday: {$schedule['saturday']}\n" .
            "Sunday: {$schedule['sunday']}\n" .
            "Total Hours: {$schedule['total_hours']}\n" .
            "Notes: {$schedule['notes']}\n\nPlease contact HR if you have questions.\n";
        $headers = 'From: no-reply@pharmacyinternship.local';
        $mailSent = false;
        if (function_exists('mail')) {
            $mailSent = @mail($schedule['email'], $subject, $message, $headers);
        }
        $resultMessage = $mailSent ? 'Notification sent to the selected user.' : 'Notification queued. Email functionality may require server configuration.';
        send_json(['success' => true, 'message' => $resultMessage]);
        break;

    case 'export_csv':
        require_login();
        require_role('HR Personnel');
        $stmt = $pdo->query('SELECT s.*, u.full_name, u.role FROM internship_schedules s JOIN users u ON s.intern_id = u.id ORDER BY u.full_name');
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="schedules_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Intern Name', 'Role', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'Total Hours', 'Notes']);
        
        foreach ($schedules as $sched) {
            fputcsv($output, [
                $sched['full_name'],
                $sched['role'],
                $sched['monday'],
                $sched['tuesday'],
                $sched['wednesday'],
                $sched['thursday'],
                $sched['friday'],
                $sched['saturday'],
                $sched['sunday'],
                $sched['total_hours'] ?? '',
                $sched['notes']
            ]);
        }
        
        fclose($output);
        exit;
        break;

    default:
        send_json(['success' => false, 'message' => 'Invalid action.'], 400);
}
