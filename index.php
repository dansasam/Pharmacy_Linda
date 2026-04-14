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
    <title>Pharmacy Internship Login</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <h1>Pharmacy Internship System</h1>
            <p>Login to continue with your role</p>
            <form id="login-form" class="auth-form">
                <label>Email</label>
                <input type="email" name="email" required />
                <label>Password</label>
                <input type="password" name="password" required />
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            <button id="google-login-btn" class="btn btn-google">Login with Google</button>
            <p class="small">Don't have an account? <a href="register.php">Register here</a>.</p>
            <div id="login-message" class="message"></div>
        </div>
    </div>
    <script src="assets/js/app.js"></script>
</body>
</html>
