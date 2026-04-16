<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role(['Pharmacy Assistant', 'Pharmacist Assistant', 'Pharmacist']);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

// PDO connection for notifications
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('PDO connection error: ' . htmlspecialchars($e->getMessage()));
}

$prescription_id = isset($_GET['prescription_id']) ? (int)$_GET['prescription_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (!$prescription_id || !in_array($action, ['verify', 'reject'])) {
    header('Location: assistant_prescriptions.php?error=Invalid request');
    exit;
}

// Get prescription details
$stmt = $conn->prepare("SELECT p.*, u.full_name AS customer_name, u.id AS customer_id FROM prescriptions p JOIN users u ON p.customer_id = u.id WHERE p.prescription_id = ?");
$stmt->bind_param('i', $prescription_id);
$stmt->execute();
$prescription = $stmt->get_result()->fetch_assoc();

if (!$prescription) {
    header('Location: assistant_prescriptions.php?error=Prescription not found');
    exit;
}

// Update prescription status
$new_status = ($action === 'verify') ? 'verified' : 'rejected';
$stmt = $conn->prepare("UPDATE prescriptions SET status = ?, verified_at = NOW() WHERE prescription_id = ?");
$stmt->bind_param('si', $new_status, $prescription_id);
$stmt->execute();

// Notify customer
$customer_id = $prescription['customer_id'];
if ($action === 'verify') {
    $title = 'Prescription Verified';
    $message = "Your prescription has been verified by our pharmacy assistant. You can now proceed with your order.";
} else {
    $title = 'Prescription Rejected';
    $message = "Your prescription has been rejected. Please contact the pharmacy for more information or upload a new prescription.";
}

$notif_stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
$notif_stmt->execute([$customer_id, $title, $message]);

$conn->close();

// Redirect back with success message
$success_msg = ($action === 'verify') ? 'Prescription verified successfully' : 'Prescription rejected';
header('Location: assistant_prescriptions.php?success=' . urlencode($success_msg));
exit;
?>
