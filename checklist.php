<?php
require_once __DIR__ . '/common.php';
require_login();
require_role('Intern');
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Checklist Status</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="dashboard_intern.php">Home</a>
                <a href="requirements_upload.php">Upload Requirements</a>
                <a href="checklist.php" class="active">Checklist</a>
                <a href="policies.php">Policies</a>
                <a href="intern_moa_management.php">MOA Management</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Checklist Status</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>

            <section class="section-card">
                <div class="section-header">
                    <h2>Requirements Checklist</h2>
                </div>
                <div id="checklist-table" class="table-scroll"></div>
            </section>
        </main>
    </div>
    <script>
        window.pageData = { role: 'Intern', userId: <?php echo $user['id']; ?> };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>