<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role('Customer');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

$success = $error = '';
$customer_id = $_SESSION['user_id'];

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_name = esc($conn, $_POST['patient_name'] ?? '');
    $patient_age = isset($_POST['patient_age']) ? (int)$_POST['patient_age'] : null;
    $patient_sex = esc($conn, $_POST['patient_sex'] ?? '');
    $patient_address = esc($conn, $_POST['patient_address'] ?? '');
    $physician_name = esc($conn, $_POST['physician_name'] ?? '');
    $physician_license = esc($conn, $_POST['physician_license'] ?? '');
    $prescription_details = esc($conn, $_POST['prescription_details'] ?? '');
    
    if (!$patient_name || !$physician_name) {
        $error = "Please fill in all required fields.";
    } else {
        // Handle file upload
        $prescription_image = null;
        if (isset($_FILES['prescription_image']) && $_FILES['prescription_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/prescriptions/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['prescription_image']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $file_name = 'rx_' . $customer_id . '_' . time() . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['prescription_image']['tmp_name'], $file_path)) {
                    $prescription_image = 'uploads/prescriptions/' . $file_name;
                }
            } else {
                $error = "Invalid file type. Only JPG, PNG, and PDF are allowed.";
            }
        }
        
        if (!$error) {
            $stmt = $conn->prepare("INSERT INTO prescriptions (customer_id, patient_name, patient_age, patient_sex, patient_address, prescription_image, prescription_details, physician_name, physician_license, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param('isissssss', $customer_id, $patient_name, $patient_age, $patient_sex, $patient_address, $prescription_image, $prescription_details, $physician_name, $physician_license);
            
            if ($stmt->execute()) {
                $success = "Prescription uploaded successfully! It will be verified by our pharmacist.";
            } else {
                $error = "Failed to upload prescription.";
            }
        }
    }
}

// Get customer's prescriptions
$prescriptions = $conn->query("SELECT * FROM prescriptions WHERE customer_id = $customer_id ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<?php navBar('Upload Prescription'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">

<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-file-medical" style="color:#3498db"></i> Upload Prescription
        </div>
        <a href="browse_products.php" class="ls-btn ls-btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Shopping
        </a>
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

    <div style="display: grid; grid-template-columns: 1fr 400px; gap: 20px;">
        <!-- Left: Upload Form -->
        <div>
            <div class="ls-card">
                <div class="ls-card-header">Upload New Prescription</div>
                <div class="ls-card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div style="margin-bottom: 16px;">
                            <label class="ls-label">Patient Name *</label>
                            <input type="text" name="patient_name" class="ls-input" required>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                            <div>
                                <label class="ls-label">Age</label>
                                <input type="number" name="patient_age" class="ls-input" min="1" max="150">
                            </div>
                            <div>
                                <label class="ls-label">Sex</label>
                                <select name="patient_sex" class="ls-select">
                                    <option value="">Select...</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label class="ls-label">Address</label>
                            <textarea name="patient_address" class="ls-input" rows="2"></textarea>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label class="ls-label">Physician Name *</label>
                            <input type="text" name="physician_name" class="ls-input" required>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label class="ls-label">Physician License Number</label>
                            <input type="text" name="physician_license" class="ls-input">
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label class="ls-label">Prescription Details</label>
                            <textarea name="prescription_details" class="ls-input" rows="3" placeholder="List medications, dosages, and instructions..."></textarea>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label class="ls-label">Upload Prescription Image/PDF</label>
                            <input type="file" name="prescription_image" class="ls-input" accept=".jpg,.jpeg,.png,.pdf">
                            <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">
                                Accepted formats: JPG, PNG, PDF (Max 5MB)
                            </div>
                        </div>

                        <button type="submit" class="ls-btn ls-btn-success" style="width: 100%;">
                            <i class="bi bi-upload"></i> Upload Prescription
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Info & History -->
        <div>
            <div class="ls-card" style="margin-bottom: 20px;">
                <div class="ls-card-header">Why Upload?</div>
                <div class="ls-card-body">
                    <div class="ls-alert ls-alert-info" style="font-size: 0.85rem;">
                        <i class="bi bi-info-circle"></i>
                        <div>
                            Some medications require a valid prescription. Upload your prescription to purchase these items.
                        </div>
                    </div>
                    <ul style="margin: 12px 0 0 20px; padding: 0; font-size: 0.85rem; color: #64748b;">
                        <li>Prescription will be verified by our pharmacist</li>
                        <li>You'll be notified once verified</li>
                        <li>Use verified prescriptions during checkout</li>
                    </ul>
                </div>
            </div>

            <div class="ls-card">
                <div class="ls-card-header">My Prescriptions</div>
                <div class="ls-card-body" style="padding: 0;">
                    <?php if (empty($prescriptions)): ?>
                    <div style="padding: 20px; text-align: center; color: #64748b;">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                        <p style="margin-top: 8px; font-size: 0.85rem;">No prescriptions uploaded yet</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($prescriptions as $rx): ?>
                    <?php
                    $status_badge = match($rx['status']) {
                        'verified' => 'ls-badge-success',
                        'rejected' => 'ls-badge-danger',
                        'dispensed' => 'ls-badge-info',
                        default => 'ls-badge-warning'
                    };
                    ?>
                    <div style="padding: 12px; border-bottom: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 4px;">
                            <div style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($rx['patient_name']) ?></div>
                            <span class="ls-badge <?= $status_badge ?>" style="font-size: 0.7rem;">
                                <?= strtoupper($rx['status']) ?>
                            </span>
                        </div>
                        <div style="font-size: 0.8rem; color: #64748b;">
                            Dr. <?= htmlspecialchars($rx['physician_name']) ?>
                        </div>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 4px;">
                            <?= date('M d, Y', strtotime($rx['created_at'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); ?>
