<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role(['Intern', 'Pharmacist', 'Pharmacy Technician']);
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)$_POST['prescription_id'];
    $status = esc($conn, $_POST['status']);
    $conn->query("UPDATE p1014_prescriptions SET status='$status' WHERE prescription_id=$id");
    $success = "Prescription #$id updated to " . strtoupper($status) . ".";
}

$filter = esc($conn, $_GET['status'] ?? '');
$where  = $filter ? "WHERE status='$filter'" : '';
$prescriptions = $conn->query("SELECT * FROM p1014_prescriptions $where ORDER BY created_at DESC");

$cnt_pending   = $conn->query("SELECT COUNT(*) AS c FROM p1014_prescriptions WHERE status='pending'")->fetch_assoc()['c'];
$cnt_dispensed = $conn->query("SELECT COUNT(*) AS c FROM p1014_prescriptions WHERE status='dispensed'")->fetch_assoc()['c'];
$cnt_cancelled = $conn->query("SELECT COUNT(*) AS c FROM p1014_prescriptions WHERE status='cancelled'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prescription Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-list-ul text-info"></i> Prescription Records</h4>
        <div>
            <a href="prescription_form.php" class="btn btn-info text-white btn-sm me-2"><i class="bi bi-plus-circle"></i> New</a>
            <a href="dashboard.php" class="btn btn-secondary btn-sm"><i class="bi bi-house"></i> Home</a>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <a href="?status=pending" class="text-decoration-none">
                <div class="card text-center border-0 shadow-sm bg-warning">
                    <div class="card-body"><div class="fs-2 fw-bold"><?= $cnt_pending ?></div><div class="small">Pending</div></div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="?status=dispensed" class="text-decoration-none">
                <div class="card text-center border-0 shadow-sm bg-success text-white">
                    <div class="card-body"><div class="fs-2 fw-bold"><?= $cnt_dispensed ?></div><div class="small">Dispensed</div></div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="?status=cancelled" class="text-decoration-none">
                <div class="card text-center border-0 shadow-sm bg-danger text-white">
                    <div class="card-body"><div class="fs-2 fw-bold"><?= $cnt_cancelled ?></div><div class="small">Cancelled</div></div>
                </div>
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="mb-3 d-flex gap-2">
        <a href="?" class="btn btn-sm <?= !$filter ? 'btn-dark' : 'btn-outline-dark' ?>">All</a>
        <a href="?status=pending"   class="btn btn-sm <?= $filter=='pending'   ? 'btn-warning'  : 'btn-outline-warning' ?>">Pending</a>
        <a href="?status=dispensed" class="btn btn-sm <?= $filter=='dispensed' ? 'btn-success'  : 'btn-outline-success' ?>">Dispensed</a>
        <a href="?status=cancelled" class="btn btn-sm <?= $filter=='cancelled' ? 'btn-danger'   : 'btn-outline-danger' ?>">Cancelled</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-bordered small mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th><th>Date</th><th>Patient</th><th>Age/Gender</th>
                        <th>Doctor</th><th>Encoded By</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($prescriptions && $prescriptions->num_rows > 0):
                    while ($rx = $prescriptions->fetch_assoc()):
                        $badge = match($rx['status']) {
                            'dispensed' => 'success', 'cancelled' => 'danger', default => 'warning text-dark'
                        };
                ?>
                    <tr>
                        <td><?= $rx['prescription_id'] ?></td>
                        <td><?= $rx['prescription_date'] ?></td>
                        <td><?= htmlspecialchars($rx['patient_name']) ?></td>
                        <td><?= $rx['patient_age'] ?> / <?= $rx['patient_gender'] ?></td>
                        <td><?= htmlspecialchars($rx['doctor_name']) ?></td>
                        <td><?= htmlspecialchars($rx['encoded_by']) ?></td>
                        <td><span class="badge bg-<?= $badge ?>"><?= strtoupper($rx['status']) ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-info" onclick="viewRx(<?= $rx['prescription_id'] ?>)">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <?php if ($rx['status'] === 'pending'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="prescription_id" value="<?= $rx['prescription_id'] ?>">
                                <input type="hidden" name="status" value="dispensed">
                                <button class="btn btn-sm btn-success" title="Mark Dispensed">
                                    <i class="bi bi-check-lg"></i> Dispense
                                </button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="prescription_id" value="<?= $rx['prescription_id'] ?>">
                                <input type="hidden" name="status" value="cancelled">
                                <button class="btn btn-sm btn-danger" title="Cancel"
                                        onclick="return confirm('Cancel this prescription?')">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="8" class="text-center text-muted py-3">No prescriptions found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="rxModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-file-medical"></i> Prescription Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="rxModalBody">
                <div class="text-center py-3"><div class="spinner-border text-info"></div></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewRx(id) {
    document.getElementById('rxModalBody').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-info"></div></div>';
    new bootstrap.Modal(document.getElementById('rxModal')).show();
    fetch('view_prescription.php?id=' + id)
        .then(r => r.text())
        .then(html => { document.getElementById('rxModalBody').innerHTML = html; });
}
</script>
</body>
</html>