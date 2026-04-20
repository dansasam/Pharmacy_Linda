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

$req_id = isset($_GET['req_id']) ? (int)$_GET['req_id'] : 0;

if (!$req_id) {
    header('Location: requisition_approval.php');
    exit;
}

// Get requisition details
$req = $conn->query("SELECT rq.*, r.report_date, r.ward 
    FROM p1014_requisition_requests rq 
    LEFT JOIN p1014_inventory_reports r ON rq.report_id = r.report_id 
    WHERE rq.requisition_id = $req_id")->fetch_assoc();

if (!$req) {
    header('Location: requisition_approval.php');
    exit;
}

// Get requisition items
$items = $conn->query("SELECT ri.*, p.drug_name, p.manufacturer 
    FROM p1014_requisition_items ri 
    JOIN product_inventory p ON ri.product_id = p.product_id 
    WHERE ri.requisition_id = $req_id 
    ORDER BY p.manufacturer, p.drug_name");

$bc = match($req['status']) {
    'approved' => 'ls-badge-success',
    'rejected' => 'ls-badge-danger',
    'ordered' => 'ls-badge-info',
    default => 'ls-badge-warning'
};
?>
<?php navBar('View Requisition Request'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">
<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-file-text" style="color:#3498db"></i> 
            <?= $req['ris_number'] ? htmlspecialchars($req['ris_number']) : 'Requisition #' . $req['requisition_id'] ?>
        </div>
        <a href="requisition_approval.php" class="ls-btn ls-btn-ghost ls-btn-sm">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    <!-- Requisition Header Info -->
    <div class="ls-card" style="margin-bottom:20px">
        <div class="ls-card-header">Requisition Information</div>
        <div class="ls-card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px">
                <div>
                    <label style="font-size:0.85rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:6px">RIS Number</label>
                    <div style="font-weight:700;font-size:1.1rem"><?= $req['ris_number'] ? htmlspecialchars($req['ris_number']) : '#' . $req['requisition_id'] ?></div>
                </div>
                <div>
                    <label style="font-size:0.85rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:6px">Status</label>
                    <span class="ls-badge <?= $bc ?>"><?= strtoupper($req['status']) ?></span>
                </div>
                <div>
                    <label style="font-size:0.85rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:6px">Requisition Date</label>
                    <div style="font-weight:600"><?= date('M d, Y', strtotime($req['requisition_date'])) ?></div>
                </div>
                <div>
                    <label style="font-size:0.85rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:6px">Requested By</label>
                    <div style="font-weight:600"><?= htmlspecialchars($req['requested_by']) ?></div>
                </div>
                <div>
                    <label style="font-size:0.85rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:6px">Department</label>
                    <div style="font-weight:600"><?= htmlspecialchars($req['department'] ?: '—') ?></div>
                </div>
                <div>
                    <label style="font-size:0.85rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:6px">Finance Code</label>
                    <div style="font-weight:600"><?= htmlspecialchars($req['finance_code'] ?: '—') ?></div>
                </div>
                <div>
                    <label style="font-size:0.85rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:6px">Suggested Vendor</label>
                    <div style="font-weight:600"><?= htmlspecialchars($req['suggested_vendor'] ?: '—') ?></div>
                </div>
                <div>
                    <label style="font-size:0.85rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:6px">Delivery Point</label>
                    <div style="font-weight:600"><?= htmlspecialchars($req['delivery_point'] ?: '—') ?></div>
                </div>
                <div>
                    <label style="font-size:0.85rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:6px">Delivery Date</label>
                    <div style="font-weight:600"><?= $req['delivery_date'] ? date('M d, Y', strtotime($req['delivery_date'])) : '—' ?></div>
                </div>
            </div>

            <?php if ($req['justification']): ?>
            <div style="margin-top:20px;padding-top:20px;border-top:1px solid rgba(148,163,184,0.18)">
                <label style="font-size:0.85rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:8px">Justification / Reason</label>
                <div style="background:#f8fafc;padding:14px;border-radius:8px;line-height:1.6">
                    <?= nl2br(htmlspecialchars($req['justification'])) ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($req['report_date']): ?>
            <div style="margin-top:20px;padding-top:20px;border-top:1px solid rgba(148,163,184,0.18)">
                <label style="font-size:0.85rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:8px">Based on Inventory Report</label>
                <div style="background:#f8fafc;padding:14px;border-radius:8px">
                    <strong>Report Date:</strong> <?= date('M d, Y', strtotime($req['report_date'])) ?> | 
                    <strong>Ward:</strong> <?= htmlspecialchars($req['ward']) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Requisition Items -->
    <div class="ls-card" style="margin-bottom:20px">
        <div class="ls-card-header">Requested Items</div>
        <div class="ls-card-body-flush">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th>Drug Name</th>
                        <th>Manufacturer</th>
                        <th style="text-align:center">Quantity</th>
                        <th style="text-align:right">Unit Price (₱)</th>
                        <th style="text-align:right">Total (₱)</th>
                        <th style="text-align:center">Out of Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total = 0;
                    if ($items && $items->num_rows > 0): 
                        while ($item = $items->fetch_assoc()): 
                            $total = $item['quantity_requested'] * $item['unit_price'];
                            $grand_total += $total;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($item['drug_name']) ?></td>
                            <td><?= htmlspecialchars($item['manufacturer']) ?></td>
                            <td style="text-align:center;font-weight:700"><?= $item['quantity_requested'] ?></td>
                            <td style="text-align:right">₱<?= number_format($item['unit_price'], 2) ?></td>
                            <td style="text-align:right;font-weight:700">₱<?= number_format($total, 2) ?></td>
                            <td style="text-align:center">
                                <?= $item['is_out_of_stock'] ? '<span class="ls-badge ls-badge-danger">YES</span>' : '—' ?>
                            </td>
                        </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8">No items found</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#f8fafc">
                        <td colspan="4" style="text-align:right;font-weight:700;font-size:1.05rem">Grand Total:</td>
                        <td style="text-align:right;font-weight:700;font-size:1.1rem;color:#0d9488">₱<?= number_format($grand_total, 2) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    <?php if ($req['status'] === 'pending'): ?>
    <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="requisition_approval.php" class="ls-btn ls-btn-ghost">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <button class="ls-btn ls-btn-success" onclick="window.location.href='requisition_approval.php?approve=<?= $req['requisition_id'] ?>'">
            <i class="bi bi-check-lg"></i> Proceed to Approve
        </button>
        <button class="ls-btn ls-btn-danger" onclick="window.location.href='requisition_approval.php?deny=<?= $req['requisition_id'] ?>'">
            <i class="bi bi-x-lg"></i> Proceed to Deny
        </button>
    </div>
    <?php else: ?>
    <div style="text-align:right">
        <a href="requisition_approval.php" class="ls-btn ls-btn-primary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
