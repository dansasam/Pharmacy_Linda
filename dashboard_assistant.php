<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role(['Pharmacist Assistant', 'Pharmacy Assistant']);
$user = current_user();
?>
<?php navBar('Assistant Dashboard'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">

<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-speedometer2" style="color:#3498db"></i> Welcome, <?php echo sanitize_text($user['full_name']); ?>!
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <a href="/Pharmacy_Linda/assistant_prescriptions.php" class="ls-card" style="text-decoration: none; transition: all 0.2s; cursor: pointer;">
            <div class="ls-card-body" style="text-align: center;">
                <i class="bi bi-file-medical" style="font-size: 3rem; color: #3498db; margin-bottom: 12px;"></i>
                <h3 style="margin: 0 0 8px 0; color: #1e293b;">Prescriptions</h3>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem;">Review customer prescriptions & check availability</p>
            </div>
        </a>

        <a href="/Pharmacy_Linda/assistant_orders.php" class="ls-card" style="text-decoration: none; transition: all 0.2s; cursor: pointer;">
            <div class="ls-card-body" style="text-align: center;">
                <i class="bi bi-receipt" style="font-size: 3rem; color: #2ecc71; margin-bottom: 12px;"></i>
                <h3 style="margin: 0 0 8px 0; color: #1e293b;">Orders</h3>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem;">Process orders & dispense products</p>
            </div>
        </a>

        <a href="/Pharmacy_Linda/stock_report_dashboard.php" class="ls-card" style="text-decoration: none; transition: all 0.2s; cursor: pointer;">
            <div class="ls-card-body" style="text-align: center;">
                <i class="bi bi-box-seam" style="font-size: 3rem; color: #f39c12; margin-bottom: 12px;"></i>
                <h3 style="margin: 0 0 8px 0; color: #1e293b;">Product Inventory</h3>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem;">Check product availability & stock levels</p>
            </div>
        </a>
    </div>

    <!-- Important Reminders -->
    <div class="ls-alert ls-alert-info">
        <i class="bi bi-info-circle"></i>
        <div>
            <strong>Daily Reminder:</strong> 
            Always verify customer identity before dispensing orders. Check prescription requirements for Rx products.
        </div>
    </div>
</div>

<style>
.ls-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>