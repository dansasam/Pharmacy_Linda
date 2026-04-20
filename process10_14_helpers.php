<?php
// Process 10-14 Helper Functions
// Common functions used by process10-14 integrated files

// Safe escape function for database queries
function esc($conn, $val) {
    return $conn->real_escape_string(trim($val));
}

// Navigation bar function for process10-14 pages
function navBar($pageTitle, $backUrl = null) {
    $currentUser = function_exists('current_user') ? current_user() : null;
    $role = $currentUser['role'] ?? $_SESSION['role'] ?? 'Unknown';
    $name = $currentUser['full_name'] ?? $_SESSION['full_name'] ?? 'User';

    // Build navigation based on role
    $nav = '';
    
    switch($role) {
        case 'Customer':
            $nav = '
                <a href="/Pharmacy_Linda/browse_products.php"><i class="bi bi-shop"></i> Browse Products</a>
                <a href="/Pharmacy_Linda/cart.php"><i class="bi bi-cart3"></i> My Cart</a>
                <a href="/Pharmacy_Linda/my_orders.php"><i class="bi bi-receipt"></i> My Orders</a>
                <a href="/Pharmacy_Linda/prescription_upload.php"><i class="bi bi-file-medical"></i> Prescriptions</a>
            ';
            break;
            
        case 'Pharmacy Assistant':
        case 'Pharmacist Assistant':
            $nav = '
                <a href="/Pharmacy_Linda/assistant_prescriptions.php"><i class="bi bi-file-medical"></i> Prescriptions</a>
                <a href="/Pharmacy_Linda/assistant_orders.php"><i class="bi bi-receipt"></i> Orders</a>
                <a href="/Pharmacy_Linda/stock_report_dashboard.php"><i class="bi bi-box-seam"></i> Product Inventory</a>
            ';
            break;
            
        case 'Pharmacist':
            $nav = '
                <a href="/Pharmacy_Linda/dashboard_pharmacist.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="/Pharmacy_Linda/assistant_orders.php"><i class="bi bi-receipt"></i> Orders</a>
                <a href="/Pharmacy_Linda/stock_report_dashboard.php"><i class="bi bi-box-seam"></i> Stock Status</a>
                <a href="/Pharmacy_Linda/requisition_approval.php"><i class="bi bi-clipboard-check"></i> Requisitions</a>
                <a href="/Pharmacy_Linda/purchase_order.php"><i class="bi bi-cart-check"></i> Purchase Orders</a>
            ';
            break;
            
        case 'Pharmacy Technician':
            $nav = '
                <a href="/Pharmacy_Linda/dashboard_technician.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="/Pharmacy_Linda/stock_report_dashboard.php"><i class="bi bi-box-seam"></i> Stock Status</a>
                <a href="/Pharmacy_Linda/requisition_form.php"><i class="bi bi-clipboard-check"></i> Requisition Form</a>
            ';
            break;
            
        case 'Intern':
            $nav = '
                <a href="/Pharmacy_Linda/create_inventory_report.php"><i class="bi bi-file-earmark-plus"></i> Create Report</a>
                <a href="/Pharmacy_Linda/stock_report_dashboard.php"><i class="bi bi-box-seam"></i> Stock Status</a>
            ';
            break;
            
        case 'HR Personnel':
            $nav = '
                <a href="/Pharmacy_Linda/dashboard_hr.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="/Pharmacy_Linda/manage_applicants.php"><i class="bi bi-people"></i> Applicants</a>
                <a href="/Pharmacy_Linda/manage_schedules.php"><i class="bi bi-calendar3"></i> Schedules</a>
            ';
            break;
            
        default:
            $nav = '<a href="/Pharmacy_Linda/index.php"><i class="bi bi-house"></i> Home</a>';
    }

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($pageTitle) . ' — Pharmacy Linda</title>
    <link rel="stylesheet" href="/Pharmacy_Linda/assets/css/style.css">
    <link rel="stylesheet" href="/Pharmacy_Linda/assets/css/clean-theme.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    </head><body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="bi bi-capsule"></i> Pharmacy Linda
            </div>
            <nav>
                ' . $nav . '
                <a href="/Pharmacy_Linda/logout.php" style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 12px;">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <div>
                    <h1>' . htmlspecialchars($pageTitle) . '</h1>' .
                    ($backUrl ? '<a href="/Pharmacy_Linda/' . ltrim($backUrl, '/') . '" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>' : '') . '
                </div>
                <div class="user-info">
                    <i class="bi bi-person-circle"></i> 
                    <span>' . htmlspecialchars($name) . '</span>
                    <span class="role-badge">' . htmlspecialchars($role) . '</span>
                </div>
            </header>';
}
