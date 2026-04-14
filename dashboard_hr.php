<?php
require_once __DIR__ . '/common.php';
require_login();
require_role('HR Personnel');
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HR Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="#home" class="active">Home</a>
                <a href="#requirements">Manage Requirements</a>
                <a href="#policies">Manage Policies</a>
                <a href="#reviews">Review Submissions</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>HR Personnel Dashboard</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>
            <section id="home" class="section-card">
                <div class="section-header">
                    <h2>HR Overview</h2>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <span>Total interns</span>
                        <strong id="hr-total-interns">0</strong>
                    </div>
                    <div class="stat-card">
                        <span>Pending submissions</span>
                        <strong id="hr-pending">0</strong>
                    </div>
                    <div class="stat-card">
                        <span>Completed interns</span>
                        <strong id="hr-completed">0</strong>
                    </div>
                </div>
            </section>
            <section id="requirements" class="section-card">
                <div class="section-header">
                    <h2>Internship Requirements</h2>
                </div>
                <form id="requirement-form" class="compact-form">
                    <input type="hidden" name="id" />
                    <label>Requirement Title</label>
                    <input type="text" name="title" required />
                    <label>Description</label>
                    <input type="text" name="description" required />
                    <button type="submit" class="btn btn-primary">Save Requirement</button>
                </form>
                <div id="requirements-admin-list" class="table-scroll"></div>
            </section>
            <section id="policies" class="section-card">
                <div class="section-header">
                    <h2>Policies & Guidelines</h2>
                </div>
                <form id="policy-form" class="compact-form">
                    <input type="hidden" name="id" />
                    <label>Category</label>
                    <input type="text" name="category" required />
                    <label>Title</label>
                    <input type="text" name="title" required />
                    <label>Content</label>
                    <textarea name="content" rows="3" required></textarea>
                    <button type="submit" class="btn btn-primary">Save Policy</button>
                </form>
                <div id="policies-admin-list" class="table-scroll"></div>
            </section>
            <section id="reviews" class="section-card">
                <div class="section-header">
                    <h2>Review Intern Submissions</h2>
                </div>
                <div id="submission-review-table" class="table-scroll"></div>
            </section>
        </main>
    </div>
    <script>
        window.pageData = { role: 'HR Personnel' };
    </script>
    <script src="assets/js/app.js"></script>
</body>
</html>
