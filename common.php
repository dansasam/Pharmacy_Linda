<?php
// common.php
require_once __DIR__ . '/db.php';

session_start();

function send_json($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function current_user() {
    global $pdo;
    if (!is_logged_in()) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id, full_name, email, role, google_id FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

function require_role($role) {
    $user = current_user();
    if (!$user || ($user['role'] !== $role && $user['role'] !== null)) {
        header('Location: index.php');
        exit;
    }
    if ($user['role'] === null) {
        header('Location: choose_role.php');
        exit;
    }
}

function redirect_role_dashboard() {
    $user = current_user();
    if (!$user) {
        header('Location: index.php');
        exit;
    }
    if ($user['role'] === null) {
        header('Location: choose_role.php');
        exit;
    }
    switch ($user['role']) {
        case 'Intern':
            header('Location: dashboard_intern.php');
            break;
        case 'HR Personnel':
            header('Location: dashboard_hr.php');
            break;
        case 'Pharmacist':
            header('Location: dashboard_pharmacist.php');
            break;
        default:
            header('Location: index.php');
    }
    exit;
}

function sanitize_text($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function get_requirements() {
    global $pdo;
    $stmt = $pdo->query('SELECT * FROM internship_requirements ORDER BY id ASC');
    return $stmt->fetchAll();
}

function get_policies() {
    global $pdo;
    $stmt = $pdo->query('SELECT * FROM policies ORDER BY category ASC, id DESC');
    return $stmt->fetchAll();
}
