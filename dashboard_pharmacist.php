<?php
require_once __DIR__ . '/common.php';
require_login();
require_role('Pharmacist');
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pharmacist Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="#home" class="active">Home</a>
                <a href="#interns">Intern Monitoring</a>
                <a href="#reports">Reports</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Pharmacist Dashboard</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>
            <section id="home" class="section-card">
                <div class="section-header">
                    <h2>Pharmacist Overview</h2>
                </div>
                <p>Monitor intern compliance and completion progress across all active trainees.</p>
            </section>
            <section id="interns" class="section-card">
                <div class="section-header">
                    <h2>Intern Monitoring</h2>
                </div>
                <div id="intern-monitoring-list" class="table-scroll"></div>
            </section>
            <section id="reports" class="section-card">
                <div class="section-header">
                    <h2>Completion Reports</h2>
                </div>
                <div id="intern-report-table" class="table-scroll"></div>
            </section>
        </main>
    </div>
    <script>
        window.pageData = { role: 'Pharmacist' };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>
