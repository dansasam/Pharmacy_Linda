<?php
require_once __DIR__ . '/common.php';
if (empty($_SESSION['google_pending'])) {
    header('Location: index.php');
    exit;
}

$pending = $_SESSION['google_pending'];
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'Intern';
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$pending['email']]);
    if ($stmt->fetch()) {
        $message = 'This account already exists. Please login.';
    } else {
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
        redirect_role_dashboard();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Complete Google Sign-In</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <h1>Google Sign-In</h1>
            <p>Welcome, <?php echo sanitize_text($pending['full_name']); ?>. Select your internship role to continue.</p>
            <form method="post" class="auth-form">
                <label>Email</label>
                <input type="email" value="<?php echo sanitize_text($pending['email']); ?>" disabled />
                <label>Select Role</label>
                <select name="role" required>
                    <option value="Intern">Intern</option>
                    <option value="HR Personnel">HR Personnel</option>
                    <option value="Pharmacist">Pharmacist</option>
                    <option value="Pharmacy Technician">Pharmacy Technician</option>
                </select>
                <button type="submit" class="btn btn-primary">Continue</button>
            </form>
            <?php if ($message): ?>
            <div class="message error"><?php echo sanitize_text($message); ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
