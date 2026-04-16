<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role('Pharmacy Technician');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');
$success=$error='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $report_id=(int)$_POST['report_id'];
    $req_date=esc($conn,$_POST['requisition_date']); $requested_by=esc($conn,$_POST['requested_by']);
    $department=esc($conn,$_POST['department']??''); $vendor=esc($conn,$_POST['suggested_vendor']??'');
    $delivery_point=esc($conn,$_POST['delivery_point']??''); $delivery_date=esc($conn,$_POST['delivery_date']??'');
    $finance_code=esc($conn,$_POST['finance_code']??''); $justification=esc($conn,$_POST['justification']??'');
    if (!$req_date||!$requested_by) { $error="Please fill in all required fields."; }
    else {
        $conn->begin_transaction();
        try {
            $del_val=$delivery_date?"'$delivery_date'":"NULL";
            $conn->query("INSERT INTO p1014_requisition_requests (report_id,requisition_date,requested_by,department,suggested_vendor,delivery_point,delivery_date,finance_code,justification,status) VALUES ($report_id,'$req_date','$requested_by','$department','$vendor','$delivery_point',$del_val,'$finance_code','$justification','pending')");
            $req_id=$conn->insert_id;
            $pids=$_POST['product_id']??[]; $qtys=$_POST['quantity']??[]; $prices=$_POST['unit_price']??[]; $oos=$_POST['out_of_stock']??[]; $include=$_POST['include_item']??[];
            $grand=0;
            $stmt=$conn->prepare("INSERT INTO p1014_requisition_items (requisition_id,product_id,quantity_requested,unit_price,is_out_of_stock) VALUES (?,?,?,?,?)");
            foreach ($pids as $i=>$pid) {
                if (!isset($include[$i])||$qtys[$i]<=0) continue;
                $pv=(int)$pid; $qv=(int)$qtys[$i]; $prv=(float)$prices[$i]; $ov=isset($oos[$i])?1:0;
                $grand+=$qv*$prv;
                $stmt->bind_param("iiidi",$req_id,$pv,$qv,$prv,$ov); $stmt->execute();
            }
            $stmt->close();
            $conn->query("UPDATE p1014_requisition_requests SET total_amount=$grand WHERE requisition_id=$req_id");
            
            // Generate RIS number
            $ris_number = 'RIS-' . date('Ymd') . '-' . str_pad($req_id, 4, '0', STR_PAD_LEFT);
            $conn->query("UPDATE p1014_requisition_requests SET ris_number='$ris_number' WHERE requisition_id=$req_id");
            
            $conn->commit();
            
            // Notify all pharmacists about new requisition
            $pharmStmt = $pdo->prepare('SELECT id FROM users WHERE role = "Pharmacist"');
            $pharmStmt->execute();
            $pharmacists = $pharmStmt->fetchAll();
            
            if ($pharmacists) {
                $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
                $title = 'New Requisition Request';
                $message = "Requisition $ris_number has been submitted by {$requested_by}. Total: ₱" . number_format($grand, 2) . ". Please review and approve/deny.";
                
                foreach ($pharmacists as $pharm) {
                    $notifStmt->execute([$pharm['id'], $title, $message]);
                }
            }
            
            $success="Requisition #$req_id ($ris_number) submitted. Total: ₱".number_format($grand,2).". <a href='requisition_approval.php' style='color:inherit;font-weight:700'>Go to Approval →</a>";
        } catch(Exception $e) { $conn->rollback(); $error="Error: ".$e->getMessage(); }
    }
}
$rid=isset($_GET['report_id'])?(int)$_GET['report_id']:0;
$selected_report=null; $report_items=[];
if ($rid) {
    $selected_report=$conn->query("SELECT * FROM p1014_inventory_reports WHERE report_id=$rid")->fetch_assoc();
    if ($selected_report) {
        $res=$conn->query("SELECT i.*,p.drug_name AS item_name,p.manufacturer AS category,p.current_inventory AS reorder_level FROM p1014_inventory_report_items i JOIN product_inventory p ON i.product_id=p.product_id WHERE i.report_id=$rid ORDER BY p.manufacturer,p.drug_name");
        while ($row=$res->fetch_assoc()) $report_items[]=$row;
    }
}

// Get all approved reports for the table
$allReports = $conn->query("SELECT r.*, 
    (SELECT COUNT(*) FROM p1014_inventory_report_items WHERE report_id = r.report_id) as item_count,
    (SELECT COUNT(*) FROM p1014_inventory_report_items i JOIN product_inventory p ON i.product_id = p.product_id 
     WHERE i.report_id = r.report_id AND (i.stock_on_hand = 0 OR i.stock_on_hand <= p.current_inventory)) as critical_items
    FROM p1014_inventory_reports r 
    WHERE r.status='approved'
    ORDER BY r.report_date DESC, r.report_id DESC");
?>
<?php navBar('Requisition Request Form'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">
<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title"><i class="bi bi-cart-plus" style="color:#e74c3c"></i> Requisition Request Form</div>
    </div>

    <?php if ($success): ?><div class="ls-alert ls-alert-success"><i class="bi bi-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="ls-alert ls-alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if (!$rid): ?>
    <!-- Show all approved reports in a table -->
    <div class="ls-card" style="margin-bottom:20px">
        <div class="ls-card-header">Approved Inventory Reports - Select to Create Requisition</div>
        <div class="ls-card-body-flush">
            <?php if ($allReports && $allReports->num_rows > 0): ?>
                <table class="ls-table">
                    <thead>
                        <tr>
                            <th>Report #</th>
                            <th>Report Date</th>
                            <th>Ward</th>
                            <th>Created By</th>
                            <th style="text-align:center">Items</th>
                            <th style="text-align:center">Critical Items</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $allReports->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $r['report_id'] ?></td>
                                <td><?= date('M d, Y', strtotime($r['report_date'])) ?></td>
                                <td><?= htmlspecialchars($r['ward']) ?></td>
                                <td><?= htmlspecialchars($r['created_by']) ?></td>
                                <td style="text-align:center"><?= (int)$r['item_count'] ?></td>
                                <td style="text-align:center">
                                    <?php if ($r['critical_items'] > 0): ?>
                                        <span class="ls-badge ls-badge-danger"><?= $r['critical_items'] ?></span>
                                    <?php else: ?>
                                        <span class="ls-badge ls-badge-success">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view_inventory_report.php?report_id=<?= $r['report_id'] ?>" class="ls-btn ls-btn-primary ls-btn-sm" target="_blank">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="?report_id=<?= $r['report_id'] ?>" class="ls-btn ls-btn-danger ls-btn-sm">
                                        <i class="bi bi-cart-plus"></i> Create Request
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="ls-empty"><i class="bi bi-inbox"></i> No approved reports available. Approve a report first.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($selected_report&&count($report_items)>0): ?>
    <div style="margin-bottom:16px">
        <a href="requisition_form.php" class="ls-btn ls-btn-ghost ls-btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Reports List
        </a>
    </div>

    <form method="POST" id="reqForm">
        <input type="hidden" name="report_id" value="<?= $selected_report['report_id'] ?>">

        <div class="ls-card" style="margin-bottom:20px">
            <div class="ls-card-header">Inventory Report Information</div>
            <div class="ls-card-body">
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;background:#f8fafc;padding:16px;border-radius:8px">
                    <div>
                        <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Report ID</label>
                        <div style="font-weight:700">#<?= $selected_report['report_id'] ?></div>
                    </div>
                    <div>
                        <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Report Date</label>
                        <div style="font-weight:600"><?= date('M d, Y', strtotime($selected_report['report_date'])) ?></div>
                    </div>
                    <div>
                        <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Ward</label>
                        <div style="font-weight:600"><?= htmlspecialchars($selected_report['ward']) ?></div>
                    </div>
                    <div>
                        <label style="font-size:0.8rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:4px">Created By</label>
                        <div style="font-weight:600"><?= htmlspecialchars($selected_report['created_by']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ls-card" style="margin-bottom:20px">
            <div class="ls-card-header">Requestor Information</div>
            <div class="ls-card-body">
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px">
                    <div><label class="ls-label">Requisition Date *</label><input type="date" name="requisition_date" class="ls-input" value="<?= date('Y-m-d') ?>" required></div>
                    <div><label class="ls-label">Requested By *</label><input type="text" name="requested_by" class="ls-input" placeholder="Name" required></div>
                    <div><label class="ls-label">Department</label><input type="text" name="department" class="ls-input" placeholder="Department"></div>
                    <div><label class="ls-label">Finance Code</label><input type="text" name="finance_code" class="ls-input" placeholder="Finance Code"></div>
                    <div><label class="ls-label">Suggested Vendor</label><input type="text" name="suggested_vendor" class="ls-input" placeholder="Vendor (if known)"></div>
                    <div><label class="ls-label">Delivery Point</label><input type="text" name="delivery_point" class="ls-input" placeholder="Delivery Point"></div>
                    <div><label class="ls-label">Delivery Date</label><input type="date" name="delivery_date" class="ls-input"></div>
                </div>
            </div>
        </div>

        <div class="ls-card" style="margin-bottom:20px">
            <div class="ls-card-header">Items to Request</div>
            <div class="ls-card-body-flush">
                <table class="ls-table">
                    <thead><tr>
                        <th style="text-align:center">Include</th>
                        <th>Drug Name</th><th>Manufacturer</th>
                        <th style="text-align:center">Sold</th>
                        <th style="text-align:center">New Stock</th>
                        <th style="text-align:center">Old Stock</th>
                        <th style="text-align:center">Balance Stock</th>
                        <th style="text-align:center">Physical Count</th><th>Status</th>
                        <th>Qty</th><th>Unit Price (₱)</th>
                        <th style="text-align:right">Total</th>
                        <th style="text-align:center">Out of Stock</th>
                    </tr></thead>
                    <tbody id="itemsBody">
                    <?php foreach ($report_items as $i=>$item):
                        $isOut=$item['stock_on_hand']==0; $isLow=!$isOut&&$item['stock_on_hand']<=$item['reorder_level'];
                        $rc=$isOut?'row-danger':($isLow?'row-warn':'');
                        if ($isOut) $sb='<span class="ls-badge ls-badge-danger">Out of Stock</span>';
                        elseif ($isLow) $sb='<span class="ls-badge ls-badge-warning">Low Stock</span>';
                        else $sb='<span class="ls-badge ls-badge-success">OK</span>';
                        $dq=$isOut?10:($isLow?5:0); $chk=($isOut||$isLow)?'checked':'';
                        // Handle both old and new schema
                        $sold = isset($item['sold']) ? (int)$item['sold'] : 0;
                        $new_stock = isset($item['new_stock']) ? (int)$item['new_stock'] : 0;
                        $old_stock = isset($item['old_stock']) ? (int)$item['old_stock'] : 0;
                        $balance_stock = isset($item['balance_stock']) ? (int)$item['balance_stock'] : 0;
                    ?>
                        <tr class="<?= $rc ?>">
                            <td style="text-align:center">
                                <input type="checkbox" name="include_item[<?= $i ?>]" class="ls-check" <?= $chk ?>>
                                <input type="hidden" name="product_id[<?= $i ?>]" value="<?= $item['product_id'] ?>">
                            </td>
                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                            <td><?= htmlspecialchars($item['category']) ?></td>
                            <td style="text-align:center"><?= $sold ?></td>
                            <td style="text-align:center"><?= $new_stock ?></td>
                            <td style="text-align:center"><?= $old_stock ?></td>
                            <td style="text-align:center;font-weight:700;color:<?= $balance_stock<0?'#e74c3c':($balance_stock==0?'#f1c40f':'#2ecc71') ?>">
                                <?= $balance_stock ?>
                            </td>
                            <td style="text-align:center;font-weight:700"><?= $item['stock_on_hand'] ?></td>
                            <td><?= $sb ?></td>
                            <td><input type="number" name="quantity[<?= $i ?>]" class="ls-input ls-input-sm qty-input" value="<?= $dq ?>" min="0" style="width:70px"></td>
                            <td><input type="number" name="unit_price[<?= $i ?>]" class="ls-input ls-input-sm price-input" value="0.00" min="0" step="0.01" style="width:95px"></td>
                            <td style="text-align:right" class="row-total">₱0.00</td>
                            <td style="text-align:center"><input type="checkbox" name="out_of_stock[<?= $i ?>]" class="ls-check" <?= $isOut?'checked':'' ?>></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="11" style="text-align:right;font-weight:700">Grand Total:</td>
                        <td style="text-align:right;color:#2ecc71;font-size:1rem;font-weight:700" id="grandTotal">₱0.00</td><td></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="ls-card" style="margin-bottom:20px">
            <div class="ls-card-body">
                <label class="ls-label">Justification / Reason *</label>
                <select name="justification" class="ls-select" required>
                    <option value="">-- Select reason for request --</option>
                    <option value="Out of Stock Items">Out of Stock Items</option>
                    <option value="Low Stock Level">Low Stock Level</option>
                    <option value="Expired Medications">Expired Medications</option>
                    <option value="Damaged Items">Damaged Items</option>
                    <option value="High Demand">High Demand</option>
                    <option value="Seasonal Requirements">Seasonal Requirements</option>
                    <option value="Emergency Replenishment">Emergency Replenishment</option>
                    <option value="Routine Restocking">Routine Restocking</option>
                    <option value="New Product Request">New Product Request</option>
                    <option value="Other">Other (Specify in notes)</option>
                </select>
            </div>
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end">
            <a href="requisition_form.php" class="ls-btn ls-btn-ghost">
                <i class="bi bi-x"></i> Cancel
            </a>
            <button type="submit" class="ls-btn ls-btn-danger"><i class="bi bi-send"></i> Submit Requisition</button>
        </div>
    </form>
    <script>
    function calcTotals() {
        let grand=0;
        document.querySelectorAll('#itemsBody tr').forEach(row=>{
            const q=parseFloat(row.querySelector('.qty-input')?.value)||0;
            const p=parseFloat(row.querySelector('.price-input')?.value)||0;
            const t=q*p; grand+=t;
            const c=row.querySelector('.row-total'); if(c) c.textContent='₱'+t.toFixed(2);
        });
        document.getElementById('grandTotal').textContent='₱'+grand.toFixed(2);
    }
    document.querySelectorAll('.qty-input,.price-input').forEach(el=>el.addEventListener('input',calcTotals));
    calcTotals();
    </script>
    <?php elseif ($rid): ?>
        <div class="ls-alert ls-alert-info"><i class="bi bi-info-circle"></i> Report not found.</div>
        <a href="requisition_form.php" class="ls-btn ls-btn-primary">
            <i class="bi bi-arrow-left"></i> Back to Reports List
        </a>
    <?php endif; ?>
</div>
</body>
</html>
