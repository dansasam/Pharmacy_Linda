<?php
require_once __DIR__ . '/process7_9_helpers.php';
require_login();
require_role('Intern');
ensure_process7_9_orientation_table();

$currentUser = current_user();
$userId = (int) $currentUser['id'];
$items = get_process7_9_orientation_items();
$message = sanitize_text($_GET['message'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $completedItems = array_keys($_POST['checklist'] ?? []);
    $stmt = $pdo->prepare('INSERT INTO orientation_progress (user_id, item_key, completed) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE completed = VALUES(completed), updated_at = CURRENT_TIMESTAMP');
    foreach ($items as $itemKey => $itemLabel) {
        $completed = in_array($itemKey, $completedItems, true) ? 1 : 0;
        $stmt->execute([$userId, $itemKey, $completed]);
    }
    header('Location: process7_9_orientation.php?message=' . urlencode('Orientation progress updated.'));
    exit;
}

$progress = [];
$stmt = $pdo->prepare('SELECT item_key, completed FROM orientation_progress WHERE user_id = ?');
$stmt->execute([$userId]);
while ($row = $stmt->fetch()) {
    $progress[$row['item_key']] = (int) $row['completed'];
}

$completedCount = 0;
foreach ($items as $itemKey => $itemLabel) {
    if (!empty($progress[$itemKey])) {
        $completedCount++;
    }
}
$totalItems = count($items);
$percentComplete = $totalItems > 0 ? round(($completedCount / $totalItems) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Orientation Progress Tracker</title>
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
                <a href="policies.php">Policies</a>
                <a href="intern_moa_management.php">MOA Management</a>
                <a href="process7_9_inventory.php">Inventory & Tasks</a>
                <a href="process7_9_orientation.php" class="active">Orientation Tracker</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Orientation Progress Tracker</h1>
                <div>Welcome, <?php echo sanitize_text($currentUser['full_name']); ?></div>
            </header>
            <?php if ($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            <section class="section-card">
                <div class="section-header">
                    <h2>Progress</h2>
                </div>
                <div class="progress-card">
                    <div class="progress-label"><?php echo $percentComplete; ?>% complete</div>
                    <div class="progress-bar-wrap">
                        <div class="progress-bar" style="width: <?php echo $percentComplete; ?>%;"></div>
                    </div>
                    <div class="small-note"><?php echo $completedCount; ?> of <?php echo $totalItems; ?> items completed</div>
                </div>
                <form method="post" class="compact-form">
                    <?php foreach ($items as $itemKey => $itemLabel): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="checklist[<?php echo sanitize_text($itemKey); ?>]" value="1" <?php echo !empty($progress[$itemKey]) ? 'checked' : ''; ?> />
                            <?php echo sanitize_text($itemLabel); ?>
                        </label>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary">Save Progress</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
