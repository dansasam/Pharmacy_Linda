<?php
require_once __DIR__ . '/common.php';
require_once __DIR__ . '/process10_14_helpers.php';
require_login();
require_role('Customer');
$user = current_user();
?>
<?php navBar('Customer Dashboard'); ?>
<link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">

<div class="ls-page">
    <div class="ls-page-header">
        <div class="ls-page-title">
            <i class="bi bi-house" style="color:#3498db"></i> Welcome, <?php echo sanitize_text($user['full_name']); ?>!
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <a href="/Pharmacy_Linda/browse_products.php" class="ls-card" style="text-decoration: none; transition: all 0.2s; cursor: pointer;">
            <div class="ls-card-body" style="text-align: center;">
                <i class="bi bi-shop" style="font-size: 3rem; color: #3498db; margin-bottom: 12px;"></i>
                <h3 style="margin: 0 0 8px 0; color: #1e293b;">Browse Products</h3>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem;">Search and shop for medications</p>
            </div>
        </a>

        <a href="/Pharmacy_Linda/cart.php" class="ls-card" style="text-decoration: none; transition: all 0.2s; cursor: pointer;">
            <div class="ls-card-body" style="text-align: center;">
                <i class="bi bi-cart3" style="font-size: 3rem; color: #2ecc71; margin-bottom: 12px;"></i>
                <h3 style="margin: 0 0 8px 0; color: #1e293b;">My Cart</h3>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem;">View and manage your cart</p>
            </div>
        </a>

        <a href="/Pharmacy_Linda/my_orders.php" class="ls-card" style="text-decoration: none; transition: all 0.2s; cursor: pointer;">
            <div class="ls-card-body" style="text-align: center;">
                <i class="bi bi-receipt" style="font-size: 3rem; color: #f39c12; margin-bottom: 12px;"></i>
                <h3 style="margin: 0 0 8px 0; color: #1e293b;">My Orders</h3>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem;">Track your order history</p>
            </div>
        </a>

        <a href="/Pharmacy_Linda/prescription_upload.php" class="ls-card" style="text-decoration: none; transition: all 0.2s; cursor: pointer;">
            <div class="ls-card-body" style="text-align: center;">
                <i class="bi bi-file-medical" style="font-size: 3rem; color: #e74c3c; margin-bottom: 12px;"></i>
                <h3 style="margin: 0 0 8px 0; color: #1e293b;">Prescriptions</h3>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem;">Upload and manage prescriptions</p>
            </div>
        </a>
    </div>

    <!-- Pharmacy Information -->
    <div class="ls-card">
        <div class="ls-card-header">
            <i class="bi bi-info-circle"></i> Pharmacy Information
        </div>
        <div class="ls-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
                <div>
                    <h4 style="margin: 0 0 8px 0; color: #1e293b; font-size: 0.9rem;">
                        <i class="bi bi-clock"></i> Operating Hours
                    </h4>
                    <p style="margin: 0; color: #64748b; font-size: 0.85rem; line-height: 1.6;">
                        Monday - Friday: 8:00 AM - 8:00 PM<br>
                        Saturday: 9:00 AM - 6:00 PM<br>
                        Sunday: Closed
                    </p>
                </div>
                <div>
                    <h4 style="margin: 0 0 8px 0; color: #1e293b; font-size: 0.9rem;">
                        <i class="bi bi-telephone"></i> Contact Information
                    </h4>
                    <p style="margin: 0; color: #64748b; font-size: 0.85rem; line-height: 1.6;">
                        Phone: (123) 456-7890<br>
                        Email: info@pharmacy.com
                    </p>
                </div>
                <div>
                    <h4 style="margin: 0 0 8px 0; color: #1e293b; font-size: 0.9rem;">
                        <i class="bi bi-geo-alt"></i> Location
                    </h4>
                    <p style="margin: 0; color: #64748b; font-size: 0.85rem; line-height: 1.6;">
                        123 Main Street<br>
                        Davao City, Philippines
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Message -->
    <div class="ls-alert ls-alert-info" style="margin-top: 20px;">
        <i class="bi bi-info-circle"></i>
        <div>
            <strong>Welcome to Pharmacy Linda!</strong> 
            You can now browse products, place orders, and manage your prescriptions online.
        </div>
    </div>
</div>

<style>
.ls-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>