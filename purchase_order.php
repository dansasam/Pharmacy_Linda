<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role('Pharmacist');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

$req_id=isset($_GET['req_id'])?(int)$_GET['req_id']:0;
if (!$req_id) { header('Location: requisition_approval.php'); exit; }

$success_msg = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

$po=$conn->query("SELECT * FROM p1014_purchase_orders WHERE requisition_id=$req_id AND status='approved'")->fetch_assoc();
if (!$po) { echo '<body style="background:#0f1923;color:#fff;font-family:Segoe UI;display:flex;align-items:center;justify-content:center;min-height:100vh"><div style="text-align:center"><div style="color:#e74c3c;font-size:2rem">⚠</div><p>No approved PO found.</p><a href="requisition_approval.php" style="color:#3498db">Go back</a></div></body>'; exit; }
$rq=$conn->query("SELECT * FROM p1014_requisition_requests WHERE requisition_id=$req_id")->fetch_assoc();
$items=$conn->query("SELECT ri.quantity_requested,ri.unit_price,ri.is_out_of_stock,(ri.quantity_requested*ri.unit_price) AS line_total,p.drug_name AS item_name,p.manufacturer AS category,'N/A' AS unit FROM p1014_requisition_items ri JOIN product_inventory p ON ri.product_id=p.product_id WHERE ri.requisition_id=$req_id ORDER BY p.manufacturer,p.drug_name");
$items_arr=[]; while ($row=$items->fetch_assoc()) $items_arr[]=$row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PO <?= htmlspecialchars($po['po_number']) ?> — Linda System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin:0; font-family:'Segoe UI',sans-serif; background:#0f1923; color:#e0e6ed; }

        /* No-print toolbar */
        .toolbar { background:rgba(255,255,255,0.03); border-bottom:1px solid rgba(255,255,255,0.06); padding:12px 32px; display:flex; gap:10px; align-items:center; }
        .tb-brand { display:flex; align-items:center; gap:8px; margin-right:auto; }
        .tb-logo { width:32px;height:32px;border-radius:8px;background:#2ecc71;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.9rem; }
        .tb-title { font-weight:700; font-size:0.9rem; color:#fff; }
        .tb-btn { display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:8px;font-size:0.8rem;font-weight:600;border:none;cursor:pointer;text-decoration:none;transition:all 0.2s; }
        .tb-btn-print { background:#2ecc71;color:#fff; }
        .tb-btn-back  { background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.6); }
        .tb-btn-back:hover { background:rgba(255,255,255,0.1);color:#fff; }

        /* PO Document */
        .po-wrap { max-width:860px; margin:32px auto; padding:0 24px 48px; }
        .po-doc  { background:#fff; color:#1a1a2e; border-radius:12px; padding:48px; box-shadow:0 8px 32px rgba(0,0,0,0.4); }

        .po-header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #1a6b3c; padding-bottom:20px; margin-bottom:28px; }
        .po-brand-name { font-size:1.6rem; font-weight:800; color:#1a6b3c; letter-spacing:-0.5px; }
        .po-brand-sub  { font-size:0.75rem; color:#888; margin-top:2px; }
        .po-num   { font-size:1.1rem; font-weight:700; color:#1a1a2e; text-align:right; }
        .po-date  { font-size:0.78rem; color:#888; text-align:right; }
        .po-badge { display:inline-block;padding:3px 12px;border-radius:20px;background:#d5f5e3;color:#1a6b3c;font-size:0.7rem;font-weight:700;margin-top:4px; }

        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:28px; }
        .info-box  { background:#f8f9fa; border-radius:8px; padding:16px; }
        .info-box-title { font-size:0.7rem; font-weight:700; color:#1a6b3c; letter-spacing:1px; text-transform:uppercase; margin-bottom:10px; }
        .info-row  { font-size:0.82rem; color:#444; margin-bottom:4px; }
        .info-row strong { color:#1a1a2e; }

        table.po-table { width:100%; border-collapse:collapse; font-size:0.82rem; margin-bottom:24px; }
        .po-table thead tr { background:#1a6b3c; color:#fff; }
        .po-table thead th { padding:10px 12px; font-weight:600; font-size:0.72rem; letter-spacing:0.3px; }
        .po-table tbody tr { border-bottom:1px solid #eee; }
        .po-table tbody tr:hover { background:#f8f9fa; }
        .po-table tbody td { padding:9px 12px; color:#333; }
        .po-table tfoot td { padding:10px 12px; font-weight:700; background:#f0faf4; border-top:2px solid #1a6b3c; }

        .just-box { background:#f8f9fa; border-left:3px solid #1a6b3c; padding:12px 16px; border-radius:0 8px 8px 0; font-size:0.82rem; color:#444; margin-bottom:28px; }

        .sig-row { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-top:40px; }
        .sig-box  { text-align:center; }
        .sig-line { border-top:1px solid #333; margin:36px auto 6px; width:80%; }
        .sig-name { font-size:0.78rem; font-weight:700; color:#1a1a2e; }
        .sig-role { font-size:0.7rem; color:#888; }

        @media print {
            .toolbar { display:none !important; }
            body { background:#fff !important; }
            .po-wrap { margin:0; padding:0; max-width:100%; }
            .po-doc  { box-shadow:none; border-radius:0; }
        }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <div class="tb-brand">
        <div class="tb-logo"><i class="bi bi-box-seam"></i></div>
        <div class="tb-title">Linda System — Purchase Order</div>
    </div>
    <a href="edit_purchase_order.php?req_id=<?= $req_id ?>" class="tb-btn" style="background:#f39c12;color:#fff"><i class="bi bi-pencil-square"></i> Edit PO</a>
    <button onclick="window.print()" class="tb-btn tb-btn-print"><i class="bi bi-printer"></i> Print / Save PDF</button>
    <a href="requisition_approval.php" class="tb-btn tb-btn-back"><i class="bi bi-arrow-left"></i> Back</a>
    <a href="dashboard.php" class="tb-btn tb-btn-back"><i class="bi bi-speedometer2"></i> Dashboard</a>
</div>

<?php if ($success_msg): ?>
<div style="max-width:860px;margin:16px auto;padding:0 24px;">
    <div style="background:#d5f5e3;border-left:4px solid #1a6b3c;padding:12px 16px;border-radius:8px;color:#1a6b3c;font-size:0.9rem;">
        <i class="bi bi-check-circle-fill"></i> <?= $success_msg ?>
    </div>
</div>
<?php endif; ?>

<div class="po-wrap">
    <div class="po-doc">
        <!-- Header -->
        <div class="po-header">
            <div>
                <div class="po-brand-name">PURCHASE ORDER</div>
                <div class="po-brand-sub">Linda System — Pharmacy Inventory Management</div>
            </div>
            <div>
                <div class="po-num"><?= htmlspecialchars($po['po_number']) ?></div>
                <div class="po-date">Date: <?= date('F d, Y',strtotime($po['po_date'])) ?></div>
                <div><span class="po-badge">✓ APPROVED</span></div>
            </div>
        </div>

        <!-- Info -->
        <div class="info-grid">
            <div class="info-box">
                <div class="info-box-title">Supplier Information</div>
                <div class="info-row"><strong>Supplier:</strong> <?= htmlspecialchars($po['supplier_name']?:'N/A') ?></div>
                <div class="info-row"><strong>Address:</strong> <?= htmlspecialchars($po['supplier_address']?:'N/A') ?></div>
                <div class="info-row"><strong>Delivery Date:</strong> <?= $po['delivery_date']?date('F d, Y',strtotime($po['delivery_date'])):'N/A' ?></div>
            </div>
            <div class="info-box">
                <div class="info-box-title">Order Details</div>
                <div class="info-row"><strong>Requested By:</strong> <?= htmlspecialchars($rq['requested_by']) ?></div>
                <div class="info-row"><strong>Department:</strong> <?= htmlspecialchars($rq['department']?:'N/A') ?></div>
                <div class="info-row"><strong>Finance Code:</strong> <?= htmlspecialchars($rq['finance_code']?:'N/A') ?></div>
                <div class="info-row"><strong>Delivery Point:</strong> <?= htmlspecialchars($rq['delivery_point']?:'N/A') ?></div>
                <div class="info-row"><strong>Approved By:</strong> <?= htmlspecialchars($po['approved_by']) ?></div>
            </div>
        </div>

        <!-- Items -->
        <table class="po-table">
            <thead><tr>
                <th>#</th><th>Item / Dosage</th><th>Unit</th>
                <th style="text-align:center">Qty</th>
                <th style="text-align:right">Unit Price (₱)</th>
                <th style="text-align:right">Total (₱)</th>
                <th style="text-align:center">OOS</th>
            </tr></thead>
            <tbody>
            <?php $n=1; foreach ($items_arr as $item): ?>
                <tr>
                    <td style="color:#888"><?= $n++ ?></td>
                    <td><?= htmlspecialchars($item['item_name']) ?> <span style="color:#888"><?= htmlspecialchars($item['category']) ?></span></td>
                    <td><?= htmlspecialchars($item['unit']) ?></td>
                    <td style="text-align:center"><?= $item['quantity_requested'] ?></td>
                    <td style="text-align:right">₱<?= number_format($item['unit_price'],2) ?></td>
                    <td style="text-align:right;font-weight:600">₱<?= number_format($item['line_total'],2) ?></td>
                    <td style="text-align:center"><?= $item['is_out_of_stock']?'<span style="background:#fde8e8;color:#c0392b;padding:2px 8px;border-radius:10px;font-size:0.68rem;font-weight:700">OOS</span>':'—' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right;color:#1a6b3c">GRAND TOTAL:</td>
                    <td style="text-align:right;color:#1a6b3c;font-size:1.1rem">₱<?= number_format($po['total_amount'],2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <?php if (!empty($rq['justification'])): ?>
        <div class="just-box"><strong>Justification:</strong> <?= htmlspecialchars($rq['justification']) ?></div>
        <?php endif; ?>

        <!-- Signatures -->
        <div class="sig-row">
            <div class="sig-box"><div class="sig-line"></div><div class="sig-name"><?= htmlspecialchars($rq['requested_by']) ?></div><div class="sig-role">Requested By</div></div>
            <div class="sig-box"><div class="sig-line"></div><div class="sig-name"><?= htmlspecialchars($po['approved_by']) ?></div><div class="sig-role">Approved By (Pharmacist)</div></div>
            <div class="sig-box"><div class="sig-line"></div><div class="sig-name">&nbsp;</div><div class="sig-role">Manager's Signature</div></div>
        </div>
    </div>
</div>
</body>
</html>