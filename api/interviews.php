<?php
require_once __DIR__ . '/../common.php';
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list_pending':
        require_login();
        require_role('HR Personnel');
        $stmt = $pdo->query('SELECT pa.*, u.full_name FROM pending_applicants pa JOIN users u ON pa.intern_id = u.id WHERE pa.status = "Pending Interview" ORDER BY pa.date_applied DESC');
        send_json(['success' => true, 'applicants' => $stmt->fetchAll()]);
        break;

    case 'list_interviewed':
        require_login();
        require_role('HR Personnel');
        $stmt = $pdo->query('SELECT pa.*, u.full_name, ep.total_rating, ep.hiring_status, ep.interview_date, ep.position_applied FROM pending_applicants pa JOIN users u ON pa.intern_id = u.id LEFT JOIN employee_profiles ep ON pa.intern_id = ep.intern_id WHERE pa.status = "Interviewed" ORDER BY ep.interview_date DESC');
        send_json(['success' => true, 'applicants' => $stmt->fetchAll()]);
        break;

    case 'get_profile':
        require_login();
        require_role('HR Personnel');
        $intern_id = intval($_GET['intern_id'] ?? 0);
        if (!$intern_id) {
            send_json(['success' => false, 'message' => 'Invalid intern ID.'], 400);
        }
        $stmt = $pdo->prepare('SELECT ep.* FROM employee_profiles ep WHERE ep.intern_id = ? LIMIT 1');
        $stmt->execute([$intern_id]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        send_json(['success' => true, 'profile' => $profile]);
        break;

    case 'send_moa':
        require_login();
        require_role('HR Personnel');
        $data = json_decode(file_get_contents('php://input'), true);
        $intern_id = intval($data['intern_id'] ?? 0);
        if (!$intern_id) {
            send_json(['success' => false, 'message' => 'Invalid intern ID.'], 400);
        }
        
        // Get intern user to check for MOA file
        $stmt = $pdo->prepare('SELECT u.email, u.full_name FROM users u WHERE u.id = ?');
        $stmt->execute([$intern_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            send_json(['success' => false, 'message' => 'Intern not found.'], 404);
        }
        
        // Get MOA record
        $stmt = $pdo->prepare('SELECT ir.moa_file_path FROM internship_records ir WHERE ir.intern_id = ? LIMIT 1');
        $stmt->execute([$intern_id]);
        $moa_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$moa_record || !$moa_record['moa_file_path']) {
            send_json(['success' => false, 'message' => 'No MOA file is available. Upload MOA first.'], 400);
        }
        
        // Create a notification (in real system, send email with MOA)
        $message = "MOA copy has been prepared for " . $user['full_name'] . " (" . $user['email'] . ")";
        $message .= "\nMOA File: " . $moa_record['moa_file_path'];
        
        send_json(['success' => true, 'message' => $message]);
        break;

    case 'get_schedule':
        require_login();
        require_role('HR Personnel');
        $intern_id = intval($_GET['intern_id'] ?? 0);
        if (!$intern_id) {
            send_json(['success' => false, 'message' => 'Invalid intern ID.'], 400);
        }
        
        $stmt = $pdo->prepare('SELECT * FROM pending_applicants WHERE intern_id = ? LIMIT 1');
        $stmt->execute([$intern_id]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        send_json(['success' => true, 'schedule' => $schedule]);
        break;

    case 'schedule':
        require_login();
        require_role('HR Personnel');
        $intern_id = intval($_POST['intern_id'] ?? 0);
        $interview_date = $_POST['interview_date'] ?? null;
        $interview_mode = $_POST['interview_mode'] ?? 'Online';
        $interview_location = trim($_POST['interview_location'] ?? '');
        $interview_link = trim($_POST['interview_link'] ?? '');
        $notification_message = trim($_POST['notification_message'] ?? '');

        if (!$intern_id || !$interview_date) {
            send_json(['success' => false, 'message' => 'Invalid schedule data.'], 400);
        }

        $stmt = $pdo->prepare('UPDATE pending_applicants SET interview_date = ?, interview_mode = ?, interview_location = ?, interview_link = ?, notification_message = ? WHERE intern_id = ?');
        $stmt->execute([$interview_date, $interview_mode, $interview_location, $interview_link, $notification_message, $intern_id]);

        send_json(['success' => true]);
        break;

    case 'intern_schedule':
        require_login();
        require_role('Intern');
        $user = current_user();
        $stmt = $pdo->prepare('SELECT * FROM pending_applicants WHERE intern_id = ? LIMIT 1');
        $stmt->execute([$user['id']]);
        $schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        send_json(['success' => true, 'schedule' => $schedule]);
        break;

    case 'get_moa_status':
        require_login();
        $intern_id = intval($_GET['intern_id'] ?? 0);
        if (!$intern_id) {
            send_json(['success' => false, 'message' => 'Invalid intern ID.'], 400);
        }
        
        // Check if user is HR or the intern themselves
        $user = current_user();
        if ($user['role'] !== 'HR Personnel' && $user['id'] != $intern_id) {
            send_json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }
        
        $stmt = $pdo->prepare('SELECT ir.* FROM internship_records ir WHERE ir.intern_id = ? LIMIT 1');
        $stmt->execute([$intern_id]);
        $moa_record = $stmt->fetch(PDO::FETCH_ASSOC);
        send_json(['success' => true, 'moa' => $moa_record]);
        break;

    case 'verify_moa':
        require_login();
        require_role('HR Personnel');
        $intern_id = intval($_POST['intern_id'] ?? 0);
        if (!$intern_id) {
            send_json(['success' => false, 'message' => 'Invalid intern ID.'], 400);
        }
        
        $stmt = $pdo->prepare('UPDATE internship_records SET verification_status = "Verified" WHERE intern_id = ?');
        $stmt->execute([$intern_id]);
        send_json(['success' => true, 'message' => 'MOA verified successfully.']);
        break;

    default:
        send_json(['success' => false, 'message' => 'Invalid action.'], 400);
}
