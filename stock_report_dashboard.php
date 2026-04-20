<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role(['Intern', 'Pharmacy Technician', 'Pharmacy Assistant', 'Pharmacist Assistant', 'Pharmacist']);
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');

$latest_id = $conn->query("SELECT MAX(report_id) AS rid FROM p1014_inventory_reports")->fetch_assoc()['rid'] ?? 0;
$total = $conn->query("SELECT COUNT(*) AS c FROM product_inventory")->fetch_assoc()['c'];
if ($latest_id) {
    $out = $conn->query("SELECT COUNT(*) AS c FROM p1014_inventory_report_items i JOIN product_inventory p ON i.product_id=p.product_id WHERE i.report_id=$latest_id AND i.stock_on_hand=0")->fetch_assoc()['c'];
    $low = $conn->query("SELECT COUNT(*) AS c FROM p1014_inventory_report_items i JOIN product_inventory p ON i.product_id=p.product_id WHERE i.report_id=$latest_id AND i.stock_on_hand>0 AND i.stock_on_hand<=p.current_inventory")->fetch_assoc()['c'];
    $ok  = $conn->query("SELECT COUNT(*) AS c FROM p1014_inventory_report_items i JOIN product_inventory p ON i.product_id=p.product_id WHERE i.report_id=$latest_id AND i.stock_on_hand>p.current_inventory")->fetch_assoc()['c'];
} else { $out = $low = $ok = 0; }
?>
<?php navBar('Stock Status Dashboard'); ?>
<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title"><i class="bi bi-bar-chart-line" style="color:#2ecc71"></i> Stock Status Dashboard</div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
        <div class="ls-stat"><div class="ls-stat-num" style="color:#e0e6ed"><?= $total ?></div><div class="ls-stat-label">Total Products</div></div>
        <div class="ls-stat"><div class="ls-stat-num" style="color:#e74c3c"><?= $out ?></div><div class="ls-stat-label">Out of Stock</div></div>
        <div class="ls-stat"><div class="ls-stat-num" style="color:#f1c40f"><?= $low ?></div><div class="ls-stat-label">Low Stock</div></div>
        <div class="ls-stat"><div class="ls-stat-num" style="color:#2ecc71"><?= $ok ?></div><div class="ls-stat-label">Sufficient</div></div>
    </div>

    <!-- Filters -->
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center">
        <select name="category" class="ls-select" style="width:200px">
            <option value="">All Manufacturers</option>
            <?php $cats = $conn->query("SELECT DISTINCT manufacturer FROM product_inventory ORDER BY manufacturer");
            while ($c = $cats->fetch_assoc()): $sel = (($_GET['category']??'')==$c['manufacturer'])?'selected':''; ?>
            <option value="<?= htmlspecialchars($c['manufacturer']) ?>" <?= $sel ?>><?= htmlspecialchars($c['manufacturer']) ?></option>
            <?php endwhile; ?>
        </select>
        <select name="status" class="ls-select" style="width:160px">
            <option value="">All Status</option>
            <option value="out" <?= (($_GET['status']??'')=='out')?'selected':'' ?>>Out of Stock</option>
            <option value="low" <?= (($_GET['status']??'')=='low')?'selected':'' ?>>Low Stock</option>
            <option value="ok"  <?= (($_GET['status']??'')=='ok') ?'selected':'' ?>>Sufficient</option>
        </select>
        <button type="submit" class="ls-btn ls-btn-ghost ls-btn-sm"><i class="bi bi-funnel"></i> Filter</button>
        <a href="stock_dashboard.php" class="ls-btn ls-btn-ghost ls-btn-sm"><i class="bi bi-x"></i> Clear</a>
    </form>

    <?php if (!$latest_id): ?>
        <div class="ls-alert ls-alert-info"><i class="bi bi-info-circle"></i> No inventory reports yet. Await the next report submission from the intern.</div>
    <?php else:
        $where = "WHERE i.report_id=$latest_id";
        if (!empty($_GET['category'])) $where .= " AND p.manufacturer='".esc($conn,$_GET['category'])."'";
        if (!empty($_GET['status'])) {
            if ($_GET['status']==='out') $where .= " AND i.stock_on_hand=0";
            elseif ($_GET['status']==='low') $where .= " AND i.stock_on_hand>0 AND i.stock_on_hand<=p.current_inventory";
            elseif ($_GET['status']==='ok')  $where .= " AND i.stock_on_hand>p.current_inventory";
        }
        $rows = $conn->query("SELECT p.manufacturer AS category,p.drug_name AS item_name,'' AS dosage,'N/A' AS unit,p.current_inventory AS reorder_level,i.sold,i.new_stock,i.old_stock,i.balance_stock,i.stock_on_hand,i.expiration_date,i.lot_number,i.remarks,r.report_date,r.ward FROM p1014_inventory_report_items i JOIN product_inventory p ON i.product_id=p.product_id JOIN p1014_inventory_reports r ON i.report_id=r.report_id $where ORDER BY p.manufacturer,p.drug_name");
    ?>
    <div class="ls-card">
        <div class="ls-card-header">Inventory Items — Latest Report</div>
        <div class="ls-card-body-flush">
            <table class="ls-table">
                <thead><tr>
                    <th>Drug Name</th><th>Manufacturer</th>
                    <th style="text-align:center">Sold</th>
                    <th style="text-align:center">New Stock</th>
                    <th style="text-align:center">Old Stock</th>
                    <th style="text-align:center">Balance Stock</th>
                    <th style="text-align:center">Physical Count</th>
                    <th>Expiry Date</th><th>Lot No.</th><th>Status</th><th>Remarks</th>
                </tr></thead>
                <tbody>
                <?php if ($rows && $rows->num_rows > 0):
                    while ($row = $rows->fetch_assoc()):
                        if ($row['stock_on_hand']==0) { $st='OUT OF STOCK'; $bc='ls-badge-danger'; $rc='row-danger'; }
                        elseif ($row['stock_on_hand']<=$row['reorder_level']) { $st='LOW STOCK'; $bc='ls-badge-warning'; $rc='row-warn'; }
                        else { $st='SUFFICIENT'; $bc='ls-badge-success'; $rc=''; }
                        // Handle both old and new schema
                        $sold = isset($row['sold']) ? (int)$row['sold'] : 0;
                        $new_stock = isset($row['new_stock']) ? (int)$row['new_stock'] : 0;
                        $old_stock = isset($row['old_stock']) ? (int)$row['old_stock'] : 0;
                        $balance_stock = isset($row['balance_stock']) ? (int)$row['balance_stock'] : 0;
                ?>
                    <tr class="<?= $rc ?>">
                        <td><?= htmlspecialchars($row['item_name']) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td style="text-align:center"><?= $sold ?></td>
                        <td style="text-align:center"><?= $new_stock ?></td>
                        <td style="text-align:center"><?= $old_stock ?></td>
                        <td style="text-align:center;font-weight:700;color:<?= $balance_stock<0?'#e74c3c':($balance_stock==0?'#f1c40f':'#2ecc71') ?>">
                            <?= $balance_stock ?>
                        </td>
                        <td style="text-align:center;font-weight:700"><?= $row['stock_on_hand'] ?></td>
                        <td><?= $row['expiration_date'] ? date('M d, Y',strtotime($row['expiration_date'])) : '—' ?></td>
                        <td><?= htmlspecialchars($row['lot_number']?:'—') ?></td>
                        <td><span class="ls-badge <?= $bc ?>"><?= $st ?></span></td>
                        <td style="color:rgba(255,255,255,0.35)"><?= htmlspecialchars($row['remarks']?:'—') ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="11" class="ls-empty"><i class="bi bi-inbox"></i>No records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>