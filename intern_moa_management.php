<?php
require_once __DIR__ . '/common.php';
require_login();
require_role('Intern');

$user = current_user();
$intern_id = $user['id'];

// Fetch MOA record
$stmt = $pdo->prepare('SELECT ir.*, u.full_name FROM internship_records ir JOIN users u ON ir.intern_id = u.id WHERE ir.intern_id = ? LIMIT 1');
$stmt->execute([$intern_id]);
$moa_record = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle MOA file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['moa_file'])) {
    if ($moa_record) {
        $file = $_FILES['moa_file'];
        $file_error = '';
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $file_error = 'File upload error. Please try again.';
        } elseif ($file['size'] > 5242880) { // 5MB limit
            $file_error = 'File is too large. Maximum size is 5MB.';
        } elseif (!in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), ['pdf', 'doc', 'docx'])) {
            $file_error = 'Only PDF, DOC, and DOCX files are allowed.';
        }
        
        if (!$file_error) {
            // Create uploads directory if it doesn't exist
            if (!is_dir(__DIR__ . '/uploads')) {
                mkdir(__DIR__ . '/uploads', 0755, true);
            }
            
            // Generate unique filename
            $filename = 'moa_' . $intern_id . '_' . time() . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filepath = __DIR__ . '/uploads/' . $filename;
            $filepathDB = 'uploads/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update MOA record
                $is_signed = isset($_POST['is_signed']) ? 1 : 0;
                $is_notarized = isset($_POST['is_notarized']) ? 1 : 0;
                $verification_status = ($is_signed && $is_notarized) ? 'Verified' : 'Pending';
                $stmt = $pdo->prepare('UPDATE internship_records SET moa_file_path = ?, is_moa_signed = ?, is_notarized = ?, verification_status = ? WHERE intern_id = ?');
                $stmt->execute([$filepathDB, $is_signed, $is_notarized, $verification_status, $intern_id]);
                $_SESSION['moa_upload_success'] = true;
            } else {
                $file_error = 'Failed to save file. Please try again.';
            }
        }
        
        if ($file_error) {
            $_SESSION['moa_upload_error'] = $file_error;
        }
    }
    
    header('Location: intern_moa_management.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MOA Management</title>
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
                <a href="intern_moa_management.php" class="active">MOA Management</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>MOA Management</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>

            <section class="section-card">
                <?php if (isset($_SESSION['moa_upload_success'])): ?>
                    <div class="alert alert-success">MOA uploaded successfully! HR will verify and update the status.</div>
                    <?php unset($_SESSION['moa_upload_success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['moa_upload_error'])): ?>
                    <div class="alert alert-danger">✗ <?php echo sanitize_text($_SESSION['moa_upload_error']); ?></div>
                    <?php unset($_SESSION['moa_upload_error']); ?>
                <?php endif; ?>

                <div class="section-header">
                    <h2>MOA Management</h2>
                </div>

                <?php if ($moa_record && !empty($moa_record['moa_file_path'])): ?>
                    <p>Download the MOA sent by HR below. After signing, upload the returned MOA here.</p>
                    <p>
                        <a href="<?php echo sanitize_text($moa_record['moa_file_path']); ?>" class="btn btn-primary" download>
                            📥 Download MOA
                        </a>
                    </p>
                <?php elseif ($moa_record): ?>
                    <div class="section-card">
                        <strong>No MOA file has been sent yet.</strong>
                        <p>Please wait for HR to upload the MOA document.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">⚠️ No MOA record found. Please contact HR to initiate your MOA.</div>
                <?php endif; ?>

                <?php if ($moa_record): ?>
                    <div class="section-card">
                        <strong>Current Status</strong>
                        <div class="profile-info">
                            <div>
                                <strong>MOA Signed</strong><br>
                                <span><?php echo $moa_record['is_moa_signed'] ? 'Yes' : 'No'; ?></span>
                            </div>
                            <div>
                                <strong>Notarized</strong><br>
                                <span><?php echo $moa_record['is_notarized'] ? 'Yes' : 'No'; ?></span>
                            </div>
                            <div>
                                <strong>Verification</strong><br>
                                <span><?php echo sanitize_text($moa_record['verification_status']); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if (!$moa_record['is_moa_signed']): ?>
                        <div class="section-card">
                            <form method="POST" enctype="multipart/form-data" class="compact-form">
                                <label>Upload Signed MOA</label>
                                <input type="file" name="moa_file" accept=".pdf,.doc,.docx" required />
                                <label><input type="checkbox" name="is_signed" /> MOA is signed</label><br>
                                <label><input type="checkbox" name="is_notarized" /> MOA is notarized</label><br>
                                <button type="submit" class="btn btn-primary">Upload Signed MOA</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="section-card">
                            <strong>Your signed MOA has been received.</strong>
                            <p>HR will verify it and update the status.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="section-card">
                    <a href="dashboard_intern.php" class="btn btn-secondary">← Back to Dashboard</a>
                </div>
            </section>
        </main>
    </div>

</body>
</html>
