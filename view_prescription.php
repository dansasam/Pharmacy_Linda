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
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { echo '<p class="text-danger">Invalid request.</p>'; exit; }

$rx = $conn->query("SELECT * FROM p1014_prescriptions WHERE prescription_id=$id")->fetch_assoc();
if (!$rx) { echo '<p class="text-danger">Prescription not found.</p>'; exit; }

$items = $conn->query("SELECT pi.*, p.category FROM p1014_prescription_items pi LEFT JOIN p1014_products p ON pi.product_id = p.product_id WHERE pi.prescription_id=$id");
?>
<div class="row g-2 small mb-3">
    <div class="col-sm-6"><strong>Patient:</strong> <?= htmlspecialchars($rx['patient_name']) ?></div>
    <div class="col-sm-3"><strong>Age:</strong> <?= $rx['patient_age'] ?></div>
    <div class="col-sm-3"><strong>Gender:</strong> <?= $rx['patient_gender'] ?></div>
    <div class="col-sm-6"><strong>Doctor:</strong> <?= htmlspecialchars($rx['doctor_name']) ?></div>
    <div class="col-sm-6"><strong>License No.:</strong> <?= htmlspecialchars($rx['doctor_license'] ?: 'N/A') ?></div>
    <div class="col-sm-6"><strong>Clinic:</strong> <?= htmlspecialchars($rx['clinic_address'] ?: 'N/A') ?></div>
    <div class="col-sm-6"><strong>Date:</strong> <?= date('F d, Y', strtotime($rx['prescription_date'])) ?></div>
    <div class="col-sm-6"><strong>Encoded By:</strong> <?= htmlspecialchars($rx['encoded_by']) ?></div>
    <?php if ($rx['notes']): ?>
    <div class="col-12"><strong>Notes:</strong> <?= htmlspecialchars($rx['notes']) ?></div>
    <?php endif; ?>
    <div class="col-12">
        <strong>Status:</strong>
        <?php
        $badge = match($rx['status']) { 'dispensed' => 'success', 'cancelled' => 'danger', default => 'warning text-dark' };
        echo '<span class="badge bg-'.$badge.'">'.strtoupper($rx['status']).'</span>';
        ?>
    </div>
</div>

<table class="table table-bordered table-sm small">
    <thead class="table-secondary">
        <tr><th>#</th><th>Medicine</th><th>Dosage</th><th class="text-center">Qty</th><th>Instructions</th></tr>
    </thead>
    <tbody>
    <?php $n = 1; while ($item = $items->fetch_assoc()): ?>
        <tr>
            <td><?= $n++ ?></td>
            <td><?= htmlspecialchars($item['medicine_name']) ?></td>
            <td><?= htmlspecialchars($item['dosage'] ?: '—') ?></td>
            <td class="text-center"><?= $item['quantity'] ?></td>
            <td><?= htmlspecialchars($item['instructions'] ?: '—') ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>