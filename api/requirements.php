<?php
require_once __DIR__ . '/../common.php';
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $stmt = $pdo->query('SELECT * FROM internship_requirements ORDER BY id ASC');
        send_json(['success' => true, 'requirements' => $stmt->fetchAll()]);
        break;

    case 'list_for_user':
        require_login();
        $user = current_user();
        $stmt = $pdo->query('SELECT r.id, r.title, r.description, s.filename, s.status, s.remarks, s.uploaded_at FROM internship_requirements r LEFT JOIN intern_submissions s ON r.id = s.requirement_id AND s.user_id = ' . intval($user['id']) . ' ORDER BY r.id ASC');
        send_json(['success' => true, 'requirements' => $stmt->fetchAll()]);
        break;

    case 'create':
        require_login();
        require_role('HR Personnel');
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if (!$title || !$description) {
            send_json(['success' => false, 'message' => 'Title and description are required.'], 400);
        }
        $stmt = $pdo->prepare('INSERT INTO internship_requirements (title, description, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$title, $description]);
        send_json(['success' => true]);
        break;

    case 'update':
        require_login();
        require_role('HR Personnel');
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if (!$id || !$title || !$description) {
            send_json(['success' => false, 'message' => 'Invalid requirement data.'], 400);
        }
        $stmt = $pdo->prepare('UPDATE internship_requirements SET title = ?, description = ? WHERE id = ?');
        $stmt->execute([$title, $description, $id]);
        send_json(['success' => true]);
        break;

    case 'delete':
        require_login();
        require_role('HR Personnel');
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            send_json(['success' => false, 'message' => 'Invalid requirement id.'], 400);
        }
        $stmt = $pdo->prepare('DELETE FROM internship_requirements WHERE id = ?');
        $stmt->execute([$id]);
        send_json(['success' => true]);
        break;

    default:
        send_json(['success' => false, 'message' => 'Invalid action.'], 400);
}
