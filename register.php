<?php
require_once __DIR__ . '/common.php';
if (is_logged_in()) {
    redirect_role_dashboard();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register | Pharmacy Internship</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <h1>Register Account</h1>
            <p>Create a new account for the internship system</p>
            <form id="register-form" class="auth-form">
                <label>Full Name</label>
                <input type="text" name="full_name" required />
                <label>Email</label>
                <input type="email" name="email" required />
                <label>Password</label>
                <input type="password" name="password" required minlength="6" />
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required minlength="6" />
                <label>Role</label>
                <select name="role" required>
                    <option value="Intern">Intern</option>
                    <option value="HR Personnel">HR Personnel</option>
                    <option value="Pharmacist">Pharmacist</option>
                </select>
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
            <p class="small">Already registered? <a href="index.php">Login here</a>.</p>
            <div id="register-message" class="message"></div>
        </div>
    </div>
    <script src="assets/js/app.js"></script>
</body>
</html>
