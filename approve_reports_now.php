<?php
require_once __DIR__ . '/config.php';

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle approve action
if (isset($_POST['approve_report'])) {
    $report_id = (int)$_POST['report_id'];
    $conn->query("UPDATE p1014_inventory_reports SET status='approved' WHERE report_id=$report_id");
    // Redirect to same page to refresh
    header("Location: approve_reports_now.php?success=$report_id");
    exit;
}

$success_id = isset($_GET['success']) ? (int)$_GET['success'] : 0;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Approve Reports</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            padding: 30px; 
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            color: #fff; 
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 { 
            color: #3498db; 
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #94a3b8;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .alert {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 5px solid;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .alert-success {
            background: #1e4620;
            border-color: #2ecc71;
            color: #2ecc71;
        }
        .alert-info {
            background: #1e3a4a;
            border-color: #3498db;
            color: #3498db;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
            background: #2a2a2a;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        th { 
            background: #333; 
            padding: 15px; 
            text-align: left; 
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.05em;
        }
        td { 
            padding: 15px; 
            border-bottom: 1px solid #3a3a3a;
        }
        tr:hover td {
            background: #333;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85em;
            text-transform: uppercase;
        }
        .badge-submitted {
            background: #f1c40f;
            color: #000;
        }
        .badge-approved {
            background: #2ecc71;
            color: #fff;
        }
        .badge-denied {
            background: #e74c3c;
            color: #fff;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95em;
        }
        .btn-success {
            background: #2ecc71;
            color: #fff;
        }
        .btn-success:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(46, 204, 113, 0.3);
        }
        .btn-primary {
            background: #3498db;
            color: #fff;
        }
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }
        .btn-lg {
            padding: 15px 30px;
            font-size: 1.1em;
        }
        .actions {
            text-align: center;
            margin: 40px 0;
        }
        .card {
            background: #2a2a2a;
            border-radius: 8px;
            padding: 25px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Approve Reports</h1>
        <p class="subtitle">One-click approval for inventory reports</p>

        <?php if ($success_id > 0): ?>
        <div class="alert alert-success">
            <strong>✅ Success!</strong> Report #<?= $success_id ?> has been approved!
        </div>
        <?php endif; ?>

        <div class="card">
            <h2 style="color:#3498db;margin-top:0">Current Reports Status</h2>
            
            <?php
            $reports = $conn->query("SELECT report_id, report_date, ward, created_by, status FROM p1014_inventory_reports ORDER BY report_id DESC");
            
            if ($reports && $reports->num_rows > 0):
            ?>
                <table>
                    <thead>
                        <tr>
                            <th>Report #</th>
                            <th>Date</th>
                            <th>Ward</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $reports->fetch_assoc()): 
                            $badgeClass = $r['status'] === 'approved' ? 'badge-approved' : ($r['status'] === 'denied' ? 'badge-denied' : 'badge-submitted');
                        ?>
                        <tr>
                            <td><strong>#<?= $r['report_id'] ?></strong></td>
                            <td><?= htmlspecialchars($r['report_date']) ?></td>
                            <td><?= htmlspecialchars($r['ward']) ?></td>
                            <td><?= htmlspecialchars($r['created_by']) ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= strtoupper($r['status']) ?></span></td>
                            <td>
                                <?php if ($r['status'] !== 'approved'): ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                                    <button type="submit" name="approve_report" class="btn btn-success">
                                        ✓ Approve Now
                                    </button>
                                </form>
                                <?php else: ?>
                                <span style="color:#2ecc71">✓ Already Approved</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">
                    No reports found in database.
                </div>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a href="view_inventory_report.php?t=<?= time() ?>" class="btn btn-primary btn-lg">
                → Go to View Inventory Reports
            </a>
            <a href="requisition_form.php?t=<?= time() ?>" class="btn btn-success btn-lg">
                → Go to Requisition Form
            </a>
        </div>

        <div class="alert alert-info">
            <strong>💡 Tip:</strong> After approving reports, the status will update immediately on this page. 
            When you go to other pages, press <strong>Ctrl + F5</strong> to refresh and see the changes.
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
