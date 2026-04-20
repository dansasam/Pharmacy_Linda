<?php
require_once __DIR__ . '/common.php';
require_login();
require_role('Intern');
$user = current_user();
$requirements = get_requirements();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Upload Requirements</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="dashboard_intern.php">Home</a>
                <a href="requirements_upload.php" class="active">Upload Requirements</a>
                <a href="checklist.php">Checklist</a>
                <a href="policies.php">Policies</a>
                <a href="intern_moa_management.php">MOA Management</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Upload Requirements</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>

            <section class="section-card">
                <div class="section-header">
                    <h2>Internship Requirements</h2>
                </div>
                <div id="requirements-list" class="table-scroll"></div>
            </section>
        </main>
    </div>
    <script>
        window.pageData = { role: 'Intern', userId: <?php echo $user['id']; ?> };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>