<?php
require_once __DIR__ . '/common.php';
require_login();
require_role('Intern');
$user = current_user();
$requirements = get_requirements();
$policies = get_policies();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Intern Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="#home" class="active">Home</a>
                <a href="#upload">Upload Requirements</a>
                <a href="#checklist">Checklist</a>
                <a href="#policies">Policies</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Intern Dashboard</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>
            <section id="home" class="section-card">
                <div class="section-header">
                    <h2>Progress Overview</h2>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <span>Total requirements</span>
                        <strong id="stat-total">0</strong>
                    </div>
                    <div class="stat-card">
                        <span>Uploaded</span>
                        <strong id="stat-uploaded">0</strong>
                    </div>
                    <div class="stat-card">
                        <span>Approved</span>
                        <strong id="stat-approved">0</strong>
                    </div>
                    <div class="stat-card">
                        <span>Missing</span>
                        <strong id="stat-missing">0</strong>
                    </div>
                </div>
                <div class="progress-card">
                    <div class="progress-label">Completion</div>
                    <div class="progress-bar-wrap">
                        <div id="progress-bar" class="progress-bar"></div>
                    </div>
                    <div id="progress-text">0% complete</div>
                </div>
            </section>
            <section id="upload" class="section-card">
                <div class="section-header">
                    <h2>Upload Requirements</h2>
                </div>
                <div id="requirements-list" class="table-scroll"></div>
            </section>
            <section id="checklist" class="section-card">
                <div class="section-header">
                    <h2>Checklist Status</h2>
                </div>
                <div id="checklist-table" class="table-scroll"></div>
            </section>
            <section id="policies" class="section-card">
                <div class="section-header">
                    <h2>Pharmacy Policies</h2>
                </div>
                <div id="policies-list"></div>
            </section>
        </main>
    </div>
    <script>
        window.pageData = { role: 'Intern' };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>
