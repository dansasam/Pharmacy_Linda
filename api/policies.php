<?php
require_once __DIR__ . '/../common.php';
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $stmt = $pdo->query('SELECT * FROM policies ORDER BY category ASC, id DESC');
        send_json(['success' => true, 'policies' => $stmt->fetchAll()]);
        break;

    case 'create':
        require_login();
        require_role('HR Personnel');
        $category = trim($_POST['category'] ?? 'General');
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if (!$title || !$content) {
            send_json(['success' => false, 'message' => 'Title and content are required.'], 400);
        }
        $stmt = $pdo->prepare('INSERT INTO policies (category, title, content, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$category, $title, $content]);
        send_json(['success' => true]);
        break;

    case 'update':
        require_login();
        require_role('HR Personnel');
        $id = intval($_POST['id'] ?? 0);
        $category = trim($_POST['category'] ?? 'General');
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if (!$id || !$title || !$content) {
            send_json(['success' => false, 'message' => 'Invalid policy data.'], 400);
        }
        $stmt = $pdo->prepare('UPDATE policies SET category = ?, title = ?, content = ? WHERE id = ?');
        $stmt->execute([$category, $title, $content, $id]);
        send_json(['success' => true]);
        break;

    case 'delete':
        require_login();
        require_role('HR Personnel');
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            send_json(['success' => false, 'message' => 'Invalid policy id.'], 400);
        }
        $stmt = $pdo->prepare('DELETE FROM policies WHERE id = ?');
        $stmt->execute([$id]);
        send_json(['success' => true]);
        break;

    default:
        send_json(['success' => false, 'message' => 'Invalid action.'], 400);
}
