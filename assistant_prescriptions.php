<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sales_helpers.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role(['Pharmacy Assistant', 'Pharmacist Assistant', 'Pharmacist']);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Get filter
$filter = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Build query
$where = [];
if ($filter === 'pending') {
    $where[] = "p.status = 'pending'";
} elseif ($filter === 'verified') {
    $where[] = "p.status = 'verified'";
} elseif ($filter === 'rejected') {
    $where[] = "p.status = 'rejected'";
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get prescriptions
$query = "
    SELECT p.*, u.full_name AS customer_name, u.email AS customer_email
    FROM prescriptions p
    JOIN users u ON p.customer_id = u.id
    $where_clause
    ORDER BY p.created_at DESC
";

$prescriptions = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// Get counts
$cnt_pending = $conn->query("SELECT COUNT(*) AS c FROM prescriptions WHERE status = 'pending'")->fetch_assoc()['c'];
$cnt_verified = $conn->query("SELECT COUNT(*) AS c FROM prescriptions WHERE status = 'verified'")->fetch_assoc()['c'];
$cnt_rejected = $conn->query("SELECT COUNT(*) AS c FROM prescriptions WHERE status = 'rejected'")->fetch_assoc()['c'];
?>
<?php navBar('Prescriptions'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">

<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-file-medical" style="color:#3498db"></i> Customer Prescriptions
        </div>
    </div>

    <?php if ($success): ?>
    <div class="ls-alert ls-alert-success">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="ls-alert ls-alert-danger">
        <i class="bi bi-x-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px">
        <a href="?status=pending" style="text-decoration:none">
            <div class="ls-stat">
                <div class="ls-stat-num" style="color:#f39c12"><?= $cnt_pending ?></div>
                <div class="ls-stat-label">Pending Review</div>
            </div>
        </a>
        <a href="?status=verified" style="text-decoration:none">
            <div class="ls-stat">
                <div class="ls-stat-num" style="color:#2ecc71"><?= $cnt_verified ?></div>
                <div class="ls-stat-label">Verified</div>
            </div>
        </a>
        <a href="?status=rejected" style="text-decoration:none">
            <div class="ls-stat">
                <div class="ls-stat-num" style="color:#e74c3c"><?= $cnt_rejected ?></div>
                <div class="ls-stat-label">Rejected</div>
            </div>
        </a>
    </div>

    <!-- Filter -->
    <div class="ls-filter-bar" style="margin-bottom: 20px;">
        <a href="?status=pending" class="ls-filter-btn <?= $filter === 'pending' ? 'active' : '' ?>">Pending Review</a>
        <a href="?status=verified" class="ls-filter-btn <?= $filter === 'verified' ? 'active' : '' ?>">Verified</a>
        <a href="?status=rejected" class="ls-filter-btn <?= $filter === 'rejected' ? 'active' : '' ?>">Rejected</a>
    </div>

    <!-- Prescriptions List -->
    <?php if (empty($prescriptions)): ?>
    <div class="ls-card">
        <div class="ls-card-body">
            <div class="ls-empty">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e1;"></i>
                <p style="margin-top: 12px; color: #64748b;">No prescriptions found.</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($prescriptions as $rx): ?>
    <div class="ls-card" style="margin-bottom: 16px;">
        <div class="ls-card-body">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
                <div>
                    <h3 style="margin: 0 0 4px 0; font-size: 1.1rem; color: #1e293b;">
                        Prescription #<?= str_pad($rx['prescription_id'], 4, '0', STR_PAD_LEFT) ?>
                    </h3>
                    <div style="font-size: 0.85rem; color: #64748b;">
                        Uploaded: <?= date('M d, Y h:i A', strtotime($rx['created_at'])) ?>
                    </div>
                </div>
                <span class="ls-badge <?= $rx['status'] === 'verified' ? 'ls-badge-success' : ($rx['status'] === 'rejected' ? 'ls-badge-danger' : 'ls-badge-warning') ?>">
                    <?= strtoupper($rx['status']) ?>
                </span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 16px;">
                <div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">CUSTOMER</div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($rx['customer_name']) ?></div>
                    <div style="font-size: 0.85rem; color: #64748b;"><?= htmlspecialchars($rx['customer_email']) ?></div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">PATIENT</div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($rx['patient_name']) ?></div>
                    <?php if ($rx['patient_age']): ?>
                    <div style="font-size: 0.85rem; color: #64748b;">
                        Age: <?= $rx['patient_age'] ?> | <?= $rx['patient_sex'] ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($rx['physician_name']): ?>
            <div style="margin-bottom: 16px;">
                <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">PHYSICIAN</div>
                <div style="font-weight: 600;"><?= htmlspecialchars($rx['physician_name']) ?></div>
                <?php if ($rx['physician_license']): ?>
                <div style="font-size: 0.85rem; color: #64748b;">License: <?= htmlspecialchars($rx['physician_license']) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($rx['prescription_details']): ?>
            <div style="margin-bottom: 16px;">
                <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px;">DETAILS</div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 6px; font-size: 0.9rem;">
                    <?= nl2br(htmlspecialchars($rx['prescription_details'])) ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($rx['prescription_image']): ?>
            <div style="margin-bottom: 16px;">
                <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 8px;">PRESCRIPTION IMAGE (Click to view full size)</div>
                <a href="<?= htmlspecialchars($rx['prescription_image']) ?>" target="_blank" style="display: block;">
                    <img src="<?= htmlspecialchars($rx['prescription_image']) ?>" alt="Prescription" style="max-width: 100%; max-height: 500px; border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                </a>
                <div style="margin-top: 8px; font-size: 0.85rem; color: #64748b;">
                    <i class="bi bi-zoom-in"></i> Click image to open in new tab for better viewing
                </div>
            </div>
            <?php endif; ?>

            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="check_availability.php?prescription_id=<?= $rx['prescription_id'] ?>" class="ls-btn ls-btn-primary ls-btn-sm">
                    <i class="bi bi-search"></i> Check Availability & Process Order
                </a>
                
                <?php if ($rx['status'] === 'pending'): ?>
                <a href="verify_prescription.php?prescription_id=<?= $rx['prescription_id'] ?>&action=verify" class="ls-btn ls-btn-success ls-btn-sm" onclick="return confirm('Verify this prescription?')">
                    <i class="bi bi-check-circle"></i> Verify
                </a>
                <a href="verify_prescription.php?prescription_id=<?= $rx['prescription_id'] ?>&action=reject" class="ls-btn ls-btn-danger ls-btn-sm" onclick="return confirm('Reject this prescription?')">
                    <i class="bi bi-x-circle"></i> Reject
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php $conn->close(); ?>
