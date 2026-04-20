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
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name   = esc($conn, $_POST['patient_name']);
    $patient_age    = (int)($_POST['patient_age'] ?? 0);
    $patient_gender = esc($conn, $_POST['patient_gender']);
    $doctor_name    = esc($conn, $_POST['doctor_name']);
    $doctor_license = esc($conn, $_POST['doctor_license'] ?? '');
    $clinic_address = esc($conn, $_POST['clinic_address'] ?? '');
    $presc_date     = esc($conn, $_POST['prescription_date']);
    $encoded_by     = esc($conn, $_POST['encoded_by']);
    $notes          = esc($conn, $_POST['notes'] ?? '');

    if (!$patient_name || !$doctor_name || !$presc_date || !$encoded_by) {
        $error = "Please fill in all required fields.";
    } else {
        $conn->begin_transaction();
        try {
            $conn->query("INSERT INTO p1014_prescriptions
                (patient_name, patient_age, patient_gender, doctor_name, doctor_license,
                 clinic_address, prescription_date, encoded_by, notes, status)
                VALUES ('$patient_name', $patient_age, '$patient_gender', '$doctor_name',
                        '$doctor_license', '$clinic_address', '$presc_date', '$encoded_by', '$notes', 'pending')");
            $presc_id = $conn->insert_id;

            $meds   = $_POST['medicine_name'] ?? [];
            $dosages= $_POST['med_dosage']    ?? [];
            $qtys   = $_POST['med_qty']       ?? [];
            $instrs = $_POST['instructions']  ?? [];
            $pids   = $_POST['product_id']    ?? [];

            foreach ($meds as $i => $med) {
                if (empty(trim($med))) continue;
                $med_v   = esc($conn, $med);
                $dos_v   = esc($conn, $dosages[$i] ?? '');
                $qty_v   = max(1, (int)($qtys[$i] ?? 1));
                $instr_v = esc($conn, $instrs[$i] ?? '');
                $pid_sql = !empty($pids[$i]) ? (int)$pids[$i] : "NULL";
                $conn->query("INSERT INTO p1014_prescription_items (prescription_id, product_id, medicine_name, dosage, quantity, instructions)
                              VALUES ($presc_id, $pid_sql, '$med_v', '$dos_v', $qty_v, '$instr_v')");
            }
            $conn->commit();
            $success = "Prescription #$presc_id saved. <a href='prescription_list.php'>View All</a>";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}

$products = $conn->query("SELECT product_id, item_name, dosage FROM p1014_products ORDER BY category, item_name");
$prod_opts = '<option value="">-- Match Product (optional) --</option>';
while ($p = $products->fetch_assoc()) {
    $prod_opts .= '<option value="'.$p['product_id'].'">'.htmlspecialchars($p['item_name'].' '.$p['dosage']).'</option>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Process 15 - Prescription Intake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-file-medical text-info"></i> Prescription Intake Form</h4>
        <div>
            <a href="prescription_list.php" class="btn btn-outline-info btn-sm me-2"><i class="bi bi-list-ul"></i> View All</a>
            <a href="dashboard.php" class="btn btn-secondary btn-sm"><i class="bi bi-house"></i> Home</a>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">Patient & Doctor Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Patient Name <span class="text-danger">*</span></label>
                        <input type="text" name="patient_name" class="form-control" placeholder="Full Name" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Age</label>
                        <input type="number" name="patient_age" class="form-control" min="0" max="150" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Gender</label>
                        <select name="patient_gender" class="form-select">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Prescription Date <span class="text-danger">*</span></label>
                        <input type="date" name="prescription_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Doctor's Name <span class="text-danger">*</span></label>
                        <input type="text" name="doctor_name" class="form-control" placeholder="Dr. Full Name" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">PRC License No.</label>
                        <input type="text" name="doctor_license" class="form-control" placeholder="License #">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Clinic / Hospital Address</label>
                        <input type="text" name="clinic_address" class="form-control" placeholder="Address">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Encoded By <span class="text-danger">*</span></label>
                        <input type="text" name="encoded_by" class="form-control" placeholder="Staff Name" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Additional notes">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <span>Prescribed Medicines</span>
                <button type="button" class="btn btn-sm btn-light" onclick="addRow()">
                    <i class="bi bi-plus-circle"></i> Add Row
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered small mb-0">
                    <thead class="table-secondary">
                        <tr>
                            <th>Medicine Name <span class="text-danger">*</span></th>
                            <th>Match Product</th>
                            <th>Dosage</th>
                            <th>Qty</th>
                            <th>Instructions</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="medBody">
                        <tr>
                            <td><input type="text" name="medicine_name[]" class="form-control form-control-sm" placeholder="Medicine name" required></td>
                            <td><select name="product_id[]" class="form-select form-select-sm"><?= $prod_opts ?></select></td>
                            <td><input type="text" name="med_dosage[]" class="form-control form-control-sm" placeholder="e.g. 500mg"></td>
                            <td><input type="number" name="med_qty[]" class="form-control form-control-sm" value="1" min="1" style="width:70px"></td>
                            <td><input type="text" name="instructions[]" class="form-control form-control-sm" placeholder="e.g. 1 tab TID x 7 days"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-info text-white"><i class="bi bi-save"></i> Save Prescription</button>
        </div>
    </form>
</div>

<script>
const prodOpts = `<?= addslashes($prod_opts) ?>`;
function addRow() {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="medicine_name[]" class="form-control form-control-sm" placeholder="Medicine name" required></td>
        <td><select name="product_id[]" class="form-select form-select-sm">${prodOpts}</select></td>
        <td><input type="text" name="med_dosage[]" class="form-control form-control-sm" placeholder="e.g. 500mg"></td>
        <td><input type="number" name="med_qty[]" class="form-control form-control-sm" value="1" min="1" style="width:70px"></td>
        <td><input type="text" name="instructions[]" class="form-control form-control-sm" placeholder="e.g. 1 tab TID x 7 days"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
    `;
    document.getElementById('medBody').appendChild(tr);
}
function removeRow(btn) {
    if (document.querySelectorAll('#medBody tr').length > 1) btn.closest('tr').remove();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>