<?php
require_once __DIR__ . '/common.php';
$user = current_user();
if (!$user || $user['role'] !== null) {
    redirect_role_dashboard();
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    if (!in_array($role, ['Intern', 'HR Personnel', 'Pharmacist', 'Pharmacy Technician', 'Customer', 'Pharmacist Assistant'])) {
        $message = 'Invalid role selected.';
    } else {
        $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $user['id']]);
        redirect_role_dashboard();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Choose Role | Pharmacy Internship</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <h1>Choose Your Role</h1>
            <p>Welcome, <?php echo sanitize_text($user['full_name']); ?>. Please select your role in the internship system.</p>
            <form method="post" class="auth-form">
                <label>Select Role</label>
                <select name="role" required>
                    <option value="">-- Choose your role --</option>
                    <option value="Intern">Intern</option>
                    <option value="HR Personnel">HR Personnel</option>
                    <option value="Pharmacist">Pharmacist</option>
                    <option value="Pharmacy Technician">Pharmacy Technician</option>
                    <option value="Pharmacist Assistant">Pharmacist Assistant</option>
                    <option value="Customer">Customer</option>
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