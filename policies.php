<?php
require_once __DIR__ . '/common.php';
require_login();
require_role('Intern');
$user = current_user();
$policies = get_policies();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pharmacy Policies</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="dashboard_intern.php">Home</a>
                <a href="requirements_upload.php">Upload Requirements</a>
                <a href="checklist.php">Checklist</a>
                <a href="policies.php" class="active">Policies</a>
                <a href="intern_moa_management.php">MOA Management</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Pharmacy Policies</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>

            <section class="section-card">
                <div class="section-header">
                    <h2>Pharmacy Policies & Guidelines</h2>
                </div>
                <div id="policies-list"></div>
            </section>
        </main>
    </div>
    <script>
        window.pageData = { role: 'Intern', userId: <?php echo $user['id']; ?> };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>