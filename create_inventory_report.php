<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_once __DIR__ . '/intern_access_control.php';
require_login();
require_role('Intern');
require_active_intern(); // This will redirect if not active

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection error: ' . htmlspecialchars($conn->connect_error));
}
$conn->set_charset('utf8mb4');
$success = $error = '';

// Get current user info
$user = current_user();
$intern_id = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_date = esc($conn,$_POST['report_date']);
    $ward        = esc($conn,$_POST['ward']);
    $created_by  = esc($conn,$_POST['created_by']);
    $remarks     = esc($conn,$_POST['remarks']??'');
    if (!$report_date||!$ward||!$created_by) { $error="Please fill in all required fields."; }
    else {
        $conn->begin_transaction();
        try {
            $conn->query("INSERT INTO p1014_inventory_reports (report_date,ward,created_by,intern_id,remarks,status) VALUES ('$report_date','$ward','$created_by',$intern_id,'$remarks','submitted')");
            $report_id = $conn->insert_id;
            $pids = $_POST['product_id'] ?? [];
            $sold = $_POST['sold'] ?? [];
            $new_stock = $_POST['new_stock'] ?? [];
            $old_stock = $_POST['old_stock'] ?? [];
            $stocks = $_POST['current_inventory'] ?? [];
            $exps = $_POST['expiration_date'] ?? [];
            $lots = $_POST['lot_number'] ?? [];
            $rems = $_POST['item_remarks'] ?? [];
            $stmt=$conn->prepare("INSERT INTO p1014_inventory_report_items (report_id,product_id,sold,new_stock,old_stock,stock_on_hand,expiration_date,lot_number,remarks) VALUES (?,?,?,?,?,?,?,?,?)");
            foreach ($pids as $i=>$pid) {
                $p=(int)$pid;
                $sold_qty=(int)($sold[$i]??0);
                $new_qty=(int)($new_stock[$i]??0);
                $old_qty=(int)($old_stock[$i]??0);
                $current=(int)($stocks[$i]??0);
                $e=!empty($exps[$i])?$exps[$i]:null;
                $l=trim($lots[$i]??'');
                $r=trim($rems[$i]??'');
                $stmt->bind_param("iiiiissss",$report_id,$p,$sold_qty,$new_qty,$old_qty,$current,$e,$l,$r);
                $stmt->execute();
            }
            $stmt->close();
            $conn->commit();
            
            // Notify all technicians about new report
            $techStmt = $pdo->prepare('SELECT id FROM users WHERE role = "Pharmacy Technician"');
            $techStmt->execute();
            $technicians = $techStmt->fetchAll();
            
            if ($technicians) {
                $notifStmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
                $title = 'New Inventory Report Submitted';
                $message = "Report #$report_id has been submitted by {$user['full_name']} for review. Please check and approve/deny.";
                
                foreach ($technicians as $tech) {
                    $notifStmt->execute([$tech['id'], $title, $message]);
                }
            }
            
            $user = current_user();
            $dashboardLink = ($user['role'] ?? '') === 'Intern' ? 'dashboard_intern.php' : 'stock_report_dashboard.php';
            $success="Report #$report_id created and sent to the technician for review. <a href='" . $dashboardLink . "' style='color:inherit;font-weight:700'>View Dashboard</a>";
        } catch(Exception $e) { $conn->rollback(); $error="Error: ".$e->getMessage(); }
    }
}
$products = $conn->query("SELECT product_id, drug_name, manufacturer, current_inventory, 
    COALESCE(sold, 0) as sold, 
    COALESCE(new_stock, 0) as new_stock 
    FROM product_inventory ORDER BY drug_name");
?>
<?php navBar('Create Inventory Report','dashboard_intern.php'); ?>
<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title"><i class="bi bi-clipboard-plus" style="color:#2ecc71"></i> Create Inventory Report</div>
    </div>

    <?php if ($success): ?><div class="ls-alert ls-alert-success"><i class="bi bi-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="ls-alert ls-alert-danger"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST">
        <div class="ls-card" style="margin-bottom:20px">
            <div class="ls-card-header">Report Header</div>
            <div class="ls-card-body">
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px">
                    <div><label class="ls-label">Report Date *</label><input type="date" name="report_date" class="ls-input" value="<?= date('Y-m-d') ?>" required></div>
                    <div><label class="ls-label">Pharmacy<input type="text" name="ward" class="ls-input" placeholder="Pharmacy Name" required></div>
                    <div><label class="ls-label">Created By *</label><input type="text" name="created_by" class="ls-input" placeholder="Name" required></div>
                    <div><label class="ls-label">Remarks</label><input type="text" name="remarks" class="ls-input" placeholder="Optional"></div>
                </div>
            </div>
        </div>

        <div class="ls-card">
            <div class="ls-card-header">Inventory Items</div>
            <div class="ls-card-body-flush">
                <table class="ls-table">
                    <thead><tr>
                        <th>Drug Name</th><th>Manufacturer</th>
                        <th style="text-align:center">Sold</th>
                        <th style="text-align:center">New Stock</th>
                        <th style="text-align:center">Old Stock</th>
                        <th style="text-align:center">Balance Stock</th>
                        <th>Expiry Date</th><th>Lot No.</th><th>Remarks</th>
                    </tr></thead>
                    <tbody id="inventoryBody">
                    <?php while ($p=$products->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['drug_name']) ?>
                                <input type="hidden" name="product_id[]" value="<?= $p['product_id'] ?>">
                                <input type="hidden" name="current_inventory[]" value="<?= (int)$p['current_inventory'] ?>" class="current-inv"></td>
                            <td style="color:rgba(255,255,255,0.35)"><?= htmlspecialchars($p['manufacturer']) ?></td>
                            <td><input type="number" name="sold[]" class="ls-input ls-input-sm sold-input" value="<?= (int)($p['sold']??0) ?>" min="0" style="width:75px" required></td>
                            <td><input type="number" name="new_stock[]" class="ls-input ls-input-sm new-stock-input" value="<?= (int)($p['new_stock']??0) ?>" min="0" style="width:75px" required></td>
                            <td><input type="number" name="old_stock[]" class="ls-input ls-input-sm old-stock-input" value="<?= (int)$p['current_inventory'] ?>" min="0" style="width:75px" required></td>
                            <td style="text-align:center;font-weight:700" class="balance-display">0</td>
                            <td><input type="date" name="expiration_date[]" class="ls-input ls-input-sm"></td>
                            <td><input type="text" name="lot_number[]" class="ls-input ls-input-sm" placeholder="Lot #"></td>
                            <td><input type="text" name="item_remarks[]" class="ls-input ls-input-sm" placeholder="Notes"></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="text-align:right;margin-top:16px">
            <button type="submit" class="ls-btn ls-btn-success"><i class="bi bi-save"></i> Submit Report</button>
        </div>
    </form>

    <script>
    // Calculate balance stock automatically: Old Stock + New Stock - Sold
    function calculateBalance() {
        document.querySelectorAll('#inventoryBody tr').forEach(row => {
            const sold = parseFloat(row.querySelector('.sold-input')?.value) || 0;
            const newStock = parseFloat(row.querySelector('.new-stock-input')?.value) || 0;
            const oldStock = parseFloat(row.querySelector('.old-stock-input')?.value) || 0;
            const balance = oldStock + newStock - sold;
            const balanceDisplay = row.querySelector('.balance-display');
            if (balanceDisplay) {
                balanceDisplay.textContent = balance;
                // Update current_inventory hidden field with balance
                const currentInvInput = row.querySelector('.current-inv');
                if (currentInvInput) {
                    currentInvInput.value = balance;
                }
                // Color code negative balances
                if (balance < 0) {
                    balanceDisplay.style.color = '#e74c3c';
                } else if (balance === 0) {
                    balanceDisplay.style.color = '#f1c40f';
                } else {
                    balanceDisplay.style.color = '#2ecc71';
                }
            }
        });
    }
    
    // Attach event listeners
    document.querySelectorAll('.sold-input, .new-stock-input, .old-stock-input').forEach(input => {
        input.addEventListener('input', calculateBalance);
    });
    
    // Initial calculation
    calculateBalance();
    </script>
</div>
</body>
</html>