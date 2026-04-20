<?php
require_once __DIR__ . '/../common.php';
$action = $_GET['action'] ?? '';

function parse_body() {
    $data = [];
    if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            $data = $input;
        }
    } else {
        $data = $_POST;
    }
    return $data;
}

switch ($action) {
    case 'register':
        $data = parse_body();
        $full_name = trim($data['full_name'] ?? '');
        $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $data['password'] ?? '';
        $confirm_password = $data['confirm_password'] ?? '';
        if (!$full_name || !$email || !$password || !$confirm_password || $password !== $confirm_password) {
            send_json(['success' => false, 'message' => 'Please complete all fields and make sure passwords match.'], 400);
        }
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            send_json(['success' => false, 'message' => 'Email already exists.'], 409);
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, created_at) VALUES (?, ?, ?, NULL, NOW())');
        $stmt->execute([$full_name, $email, $password_hash]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        send_json(['success' => true]);
        break;

    case 'login':
        $data = parse_body();
        $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $data['password'] ?? '';
        if (!$email || !$password) {
            send_json(['success' => false, 'message' => 'Email and password are required.'], 400);
        }
        $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !$user['password_hash'] || !password_verify($password, $user['password_hash'])) {
            send_json(['success' => false, 'message' => 'Invalid login credentials.'], 401);
        }
        $_SESSION['user_id'] = $user['id'];
        send_json(['success' => true, 'role' => $user['role']]);
        break;

    case 'current_user':
        if (!is_logged_in()) {
            send_json(['logged_in' => false]);
        }
        $user = current_user();
        send_json(['logged_in' => true, 'user' => $user]);
        break;

    case 'logout':
        session_unset();
        session_destroy();
        send_json(['success' => true]);
        break;

    case 'google_register':
        if (empty($_SESSION['google_pending'])) {
            send_json(['success' => false, 'message' => 'Google registration session expired.'], 400);
        }
        $data = parse_body();
        $role = in_array($data['role'] ?? '', ['Intern', 'HR Personnel', 'Pharmacist', 'Pharmacy Technician']) ? $data['role'] : 'Intern';
        $pending = $_SESSION['google_pending'];
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$pending['email']]);
        if ($stmt->fetch()) {
            send_json(['success' => false, 'message' => 'Account already exists.'], 409);
        }
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, google_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            $pending['full_name'],
            $pending['email'],
            null,
            $role,
            $pending['google_id'],
        ]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        unset($_SESSION['google_pending']);
        send_json(['success' => true, 'role' => $role]);
        break;

    default:
        send_json(['success' => false, 'message' => 'Invalid auth action.'], 400);
}
