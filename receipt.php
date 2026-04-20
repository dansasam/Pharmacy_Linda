<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/sales_helpers.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();

// Allow Customer and all assistant/pharmacist roles
$allowed_roles = ['Customer', 'Pharmacy Assistant', 'Pharmacist Assistant', 'Pharmacist'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: index.php');
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

$sale_id = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

if (!$sale_id) {
    header('Location: ' . ($_SESSION['role'] === 'Customer' ? 'my_orders.php' : 'assistant_orders.php'));
    exit;
}

// Get sale
$sale = sales_get_by_id($conn, $sale_id);

// Allow viewing receipt if payment is confirmed (paid) or already dispensed
if (!$sale || ($sale['payment_status'] !== 'paid' && !$sale['processed_by'])) {
    header('Location: ' . ($_SESSION['role'] === 'Customer' ? 'my_orders.php' : 'assistant_orders.php'));
    exit;
}

// Check access - customers can only view their own receipts
if ($_SESSION['role'] === 'Customer' && $sale['customer_id'] != $_SESSION['user_id']) {
    header('Location: my_orders.php');
    exit;
}

// Get customer
$customer = $conn->query("SELECT * FROM users WHERE id = {$sale['customer_id']}")->fetch_assoc();

// Get items
$items = sales_get_items($conn, $sale_id);

// Get processed by info (if dispensed)
$processed_by = null;
if ($sale['processed_by']) {
    $processed_by = $conn->query("SELECT * FROM users WHERE id = {$sale['processed_by']}")->fetch_assoc();
}

// Generate receipt number
$receipt_number = 'RCPT-' . date('Ymd', strtotime($sale['created_at'])) . '-' . str_pad($sale_id, 4, '0', STR_PAD_LEFT);

// Determine receipt status
$is_dispensed = !empty($sale['processed_by']);
$receipt_status = $is_dispensed ? 'OFFICIAL RECEIPT' : 'PAYMENT RECEIPT';
$receipt_subtitle = $is_dispensed ? 'Order Completed & Dispensed' : 'Payment Confirmed - Awaiting Dispensing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?= $receipt_number ?> — Linda Pharmacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8fafc; color: #1e293b; }

        /* No-print toolbar */
        .toolbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 32px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .tb-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-right: auto;
        }
        .tb-logo {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #2ecc71;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.9rem;
        }
        .tb-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: #1e293b;
        }
        .tb-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .tb-btn-print {
            background: #2ecc71;
            color: #fff;
        }
        .tb-btn-print:hover {
            background: #27ae60;
        }
        .tb-btn-back {
            background: #95a5a6;
            color: #fff;
        }
        .tb-btn-back:hover {
            background: #7f8c8d;
        }

        /* Receipt */
        .receipt-wrap {
            max-width: 800px;
            margin: 32px auto;
            padding: 0 24px 48px;
        }
        .receipt {
            background: #fff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #2ecc71;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .receipt-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #2ecc71;
            margin-bottom: 4px;
        }
        .receipt-subtitle {
            font-size: 0.9rem;
            color: #64748b;
        }
        .receipt-number {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 12px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .info-section {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
        }
        .info-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: #2ecc71;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .info-row {
            font-size: 0.85rem;
            margin-bottom: 4px;
        }
        .info-row strong {
            color: #1e293b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        thead tr {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }
        thead th {
            padding: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }
        tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }
        tbody td {
            padding: 12px;
            font-size: 0.875rem;
        }
        tfoot td {
            padding: 12px;
            font-weight: 700;
            background: #f0fdf4;
            border-top: 2px solid #2ecc71;
        }

        .footer {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.85rem;
        }

        .success-msg {
            background: #d5f5e3;
            border-left: 4px solid #2ecc71;
            padding: 12px 16px;
            border-radius: 8px;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media print {
            .toolbar, .success-msg { display: none !important; }
            body { background: #fff !important; }
            .receipt-wrap { margin: 0; padding: 0; max-width: 100%; }
            .receipt { box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <div class="tb-brand">
        <div class="tb-logo"><i class="bi bi-receipt"></i></div>
        <div class="tb-title">Linda Pharmacy — Receipt</div>
    </div>
    <button onclick="window.print()" class="tb-btn tb-btn-print">
        <i class="bi bi-printer"></i> Print Receipt
    </button>
    <a href="<?= $_SESSION['role'] === 'Customer' ? 'my_orders.php' : 'assistant_orders.php' ?>" class="tb-btn tb-btn-back">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if ($success): ?>
<div class="receipt-wrap">
    <div class="success-msg no-print">
        <i class="bi bi-check-circle-fill" style="font-size: 1.5rem; color: #2ecc71;"></i>
        <div><?= $success ?></div>
    </div>
</div>
<?php endif; ?>

<div class="receipt-wrap">
    <div class="receipt">
        <!-- Header -->
        <div class="receipt-header">
            <div class="receipt-title"><?= $receipt_status ?></div>
            <div class="receipt-subtitle"><?= $receipt_subtitle ?></div>
            <div class="receipt-number">Receipt #<?= $receipt_number ?></div>
            <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">
                <?= date('F d, Y h:i A', strtotime($sale['updated_at'])) ?>
            </div>
        </div>

        <!-- Info -->
        <div class="info-grid">
            <div class="info-section">
                <div class="info-title">Customer Information</div>
                <div class="info-row"><strong>Name:</strong> <?= htmlspecialchars($customer['full_name']) ?></div>
                <div class="info-row"><strong>Email:</strong> <?= htmlspecialchars($customer['email']) ?></div>
                <div class="info-row"><strong>Order #:</strong> <?= str_pad($sale_id, 6, '0', STR_PAD_LEFT) ?></div>
            </div>

            <div class="info-section">
                <div class="info-title">Payment Details</div>
                <div class="info-row"><strong>Method:</strong> <?= strtoupper($sale['payment_method']) ?></div>
                <div class="info-row"><strong>Status:</strong> <span style="color: #2ecc71;">PAID</span></div>
                <?php if ($sale['payment_reference']): ?>
                <div class="info-row"><strong>Reference:</strong> <?= htmlspecialchars($sale['payment_reference']) ?></div>
                <?php endif; ?>
                <?php if ($processed_by): ?>
                <div class="info-row"><strong>Dispensed By:</strong> <?= htmlspecialchars($processed_by['full_name']) ?></div>
                <?php else: ?>
                <div class="info-row"><strong>Status:</strong> <span style="color: #f39c12;">Awaiting Dispensing</span></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items -->
        <table>
            <thead>
                <tr>
                    <th style="text-align: left;">Item</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Unit Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div style="font-weight: 600;"><?= htmlspecialchars($item['drug_name']) ?></div>
                        <div style="font-size: 0.8rem; color: #64748b;">
                            <?= htmlspecialchars($item['manufacturer']) ?>
                        </div>
                    </td>
                    <td style="text-align: center;"><?= $item['quantity'] ?></td>
                    <td style="text-align: right;">₱<?= number_format($item['unit_price'], 2) ?></td>
                    <td style="text-align: right; font-weight: 600;">₱<?= number_format($item['line_total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; color: #2ecc71; font-size: 1.1rem;">TOTAL AMOUNT:</td>
                    <td style="text-align: right; color: #2ecc71; font-size: 1.3rem;">₱<?= number_format($sale['total_amount'], 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p style="margin-bottom: 8px;"><strong>Thank you for your purchase!</strong></p>
            <p>This is an official receipt. Please keep for your records.</p>
            <p style="margin-top: 12px; font-size: 0.8rem;">
                Linda Pharmacy System | Pharmacy Internship Management<br>
                Generated on <?= date('F d, Y h:i A') ?>
            </p>
        </div>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
