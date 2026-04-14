<?php
require_once __DIR__ . '/../common.php';
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'upload':
        require_login();
        $user = current_user();
        if (!isset($_POST['requirement_id']) || !isset($_FILES['document'])) {
            send_json(['success' => false, 'message' => 'Requirement and file upload are required.'], 400);
        }
        $requirement_id = intval($_POST['requirement_id']);
        $file = $_FILES['document'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            send_json(['success' => false, 'message' => 'Upload failed.'], 400);
        }
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            send_json(['success' => false, 'message' => 'File type not allowed.'], 400);
        }
        $name = uniqid('doc_') . '.' . $ext;
        $target = UPLOAD_DIR . $name;
        move_uploaded_file($file['tmp_name'], $target);
        $stmt = $pdo->prepare('SELECT id FROM intern_submissions WHERE user_id = ? AND requirement_id = ?');
        $stmt->execute([$user['id'], $requirement_id]);
        if ($stmt->fetch()) {
            $update = $pdo->prepare('UPDATE intern_submissions SET filename = ?, status = ?, remarks = NULL, uploaded_at = NOW() WHERE user_id = ? AND requirement_id = ?');
            $update->execute([$name, 'Pending', $user['id'], $requirement_id]);
        } else {
            $insert = $pdo->prepare('INSERT INTO intern_submissions (user_id, requirement_id, filename, status, uploaded_at) VALUES (?, ?, ?, ?, NOW())');
            $insert->execute([$user['id'], $requirement_id, $name, 'Pending']);
        }
        send_json(['success' => true]);
        break;

    case 'list_user':
        require_login();
        $user = current_user();
        $stmt = $pdo->prepare('SELECT r.id AS requirement_id, r.title, r.description, s.filename, COALESCE(s.status, "Missing") AS status, s.remarks, s.uploaded_at FROM internship_requirements r LEFT JOIN intern_submissions s ON r.id = s.requirement_id AND s.user_id = ? ORDER BY r.id ASC');
        $stmt->execute([$user['id']]);
        send_json(['success' => true, 'items' => $stmt->fetchAll()]);
        break;

    case 'list_all':
        require_login();
        require_role('HR Personnel');
        $stmt = $pdo->query('SELECT s.id, u.full_name, u.email, r.title, s.filename, s.status, s.remarks, s.uploaded_at FROM intern_submissions s JOIN users u ON s.user_id = u.id JOIN internship_requirements r ON s.requirement_id = r.id ORDER BY s.uploaded_at DESC');
        send_json(['success' => true, 'items' => $stmt->fetchAll()]);
        break;

    case 'intern_report':
        require_login();
        require_role('Pharmacist');
        $totalReq = $pdo->query('SELECT COUNT(*) FROM internship_requirements')->fetchColumn();
        $stmt = $pdo->query('SELECT u.id, u.full_name, u.email, SUM(s.status = "Approved") AS approved_count FROM users u CROSS JOIN internship_requirements r LEFT JOIN intern_submissions s ON s.user_id = u.id AND s.requirement_id = r.id WHERE u.role = "Intern" GROUP BY u.id ORDER BY u.full_name ASC');
        $interns = [];
        foreach ($stmt->fetchAll() as $row) {
            $approved = intval($row['approved_count']);
            $percentage = $totalReq ? round($approved * 100 / $totalReq) : 0;
            $status = $percentage === 100 ? 'Complete' : 'Incomplete';
            $interns[] = [
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'approved' => $approved,
                'percentage' => $percentage,
                'status' => $status,
            ];
        }
        send_json(['success' => true, 'interns' => $interns]);
        break;

    case 'review':
        require_login();
        require_role('HR Personnel');
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';
        $remarks = trim($_POST['remarks'] ?? '');
        if (!$id || !in_array($status, ['Approved', 'Rejected', 'Pending'])) {
            send_json(['success' => false, 'message' => 'Invalid review data.'], 400);
        }
        $stmt = $pdo->prepare('UPDATE intern_submissions SET status = ?, remarks = ?, reviewed_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $remarks, $id]);
        send_json(['success' => true]);
        break;

    case 'summary':
        require_login();
        require_role('HR Personnel');
        $stmt = $pdo->query('SELECT COUNT(DISTINCT u.id) FROM users u WHERE u.role = "Intern"');
        $totalInterns = intval($stmt->fetchColumn());
        $stmt = $pdo->query('SELECT COUNT(*) FROM intern_submissions WHERE status = "Pending"');
        $pending = intval($stmt->fetchColumn());
        $totalReq = $pdo->query('SELECT COUNT(*) FROM internship_requirements')->fetchColumn();
        $completeInterns = 0;
        if ($totalInterns && $totalReq) {
            $stmt = $pdo->query('SELECT u.id, SUM(s.status = "Approved") AS approved_count FROM users u CROSS JOIN internship_requirements r LEFT JOIN intern_submissions s ON s.user_id = u.id AND s.requirement_id = r.id WHERE u.role = "Intern" GROUP BY u.id');
            foreach ($stmt->fetchAll() as $row) {
                if (intval($row['approved_count']) === intval($totalReq)) {
                    $completeInterns++;
                }
            }
        }
        send_json(['success' => true, 'total_interns' => $totalInterns, 'pending_submissions' => $pending, 'completed_interns' => $completeInterns]);
        break;

    default:
        send_json(['success' => false, 'message' => 'Invalid submissions action.'], 400);
}
