<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Diagnostic Tools</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            color: #fff;
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 {
            color: #3498db;
            font-size: 2.5em;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #94a3b8;
            margin-bottom: 40px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .card {
            background: #2a2a2a;
            border-radius: 12px;
            padding: 30px;
            border: 2px solid #3a3a3a;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #fff;
            display: block;
        }
        .card:hover {
            border-color: #3498db;
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(52, 152, 219, 0.3);
        }
        .card-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        .card-title {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 10px;
            color: #3498db;
        }
        .card-desc {
            color: #94a3b8;
            line-height: 1.6;
        }
        .section {
            background: #2a2a2a;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            border-left: 5px solid #2ecc71;
        }
        .section h2 {
            color: #2ecc71;
            margin-top: 0;
        }
        .issue {
            background: #3a2a2a;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #e74c3c;
        }
        .issue-title {
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .solution {
            color: #94a3b8;
            margin-top: 5px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            margin: 5px;
        }
        .btn:hover {
            background: #2980b9;
            transform: scale(1.05);
        }
        .btn-success {
            background: #2ecc71;
        }
        .btn-success:hover {
            background: #27ae60;
        }
        code {
            background: #3a3a3a;
            padding: 3px 8px;
            border-radius: 4px;
            color: #3498db;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Diagnostic Tools</h1>
        <p class="subtitle">Troubleshoot your Pharmacy Inventory System</p>

        <div class="section">
            <h2>🚨 Current Issues</h2>
            <div class="issue">
                <div class="issue-title">1. Sample products not showing in Create Inventory Report</div>
                <div class="solution">
                    <strong>Possible causes:</strong> Missing database columns (sold, new_stock) or no products in database
                </div>
            </div>
            <div class="issue">
                <div class="issue-title">2. Deleted reports still showing on view page</div>
                <div class="solution">
                    <strong>Possible cause:</strong> Browser caching issue - try Ctrl+F5 to hard refresh
                </div>
            </div>
        </div>

        <h2 style="color: #3498db; margin-top: 40px; margin-bottom: 20px;">🛠️ Diagnostic Tools</h2>
        
        <div class="grid">
            <a href="quick_check.php" class="card">
                <div class="card-icon">⚡</div>
                <div class="card-title">Quick Check</div>
                <div class="card-desc">
                    Fast overview of your database status. Shows product count, report count, and tests the main query.
                    <br><br><strong>Start here!</strong>
                </div>
            </a>

            <a href="diagnostic_full.php" class="card">
                <div class="card-icon">🔍</div>
                <div class="card-title">Full Diagnostic</div>
                <div class="card-desc">
                    Complete system check with detailed analysis. Shows all columns, tests queries, and provides specific recommendations.
                </div>
            </a>

            <a href="check_products.php" class="card">
                <div class="card-icon">📦</div>
                <div class="card-title">Product Inventory</div>
                <div class="card-desc">
                    View all products in the database with their current inventory, sold, and new stock values.
                </div>
            </a>

            <a href="check_reports.php" class="card">
                <div class="card-icon">📋</div>
                <div class="card-title">Inventory Reports</div>
                <div class="card-desc">
                    View all reports in the database with delete functionality. Use this to verify reports are actually deleted.
                </div>
            </a>

            <a href="check_columns.php" class="card">
                <div class="card-icon">🗂️</div>
                <div class="card-title">Column Check</div>
                <div class="card-desc">
                    Verify if required columns (sold, new_stock, denial_remarks) exist in your database tables.
                </div>
            </a>
        </div>

        <div class="section" style="border-left-color: #f1c40f;">
            <h2 style="color: #f1c40f;">📋 Required SQL Scripts</h2>
            <p>If diagnostic tools show missing columns or no products, run these SQL scripts in phpMyAdmin:</p>
            
            <div style="margin: 20px 0;">
                <h3 style="color: #3498db;">1. Add Tracking Columns</h3>
                <p>File: <code>add_tracking_columns.sql</code></p>
                <p>Adds: sold, new_stock columns to product_inventory and denial_remarks to reports</p>
            </div>

            <div style="margin: 20px 0;">
                <h3 style="color: #3498db;">2. Add Sample Products</h3>
                <p>File: <code>add_sample_products.sql</code></p>
                <p>Adds: Sample products with quantities for testing</p>
            </div>

            <p style="margin-top: 20px; color: #94a3b8;">
                <strong>How to run:</strong> Open phpMyAdmin → Select database "pharmacy_internship" → Click "SQL" tab → Copy/paste script → Click "Go"
            </p>
        </div>

        <div class="section" style="border-left-color: #e74c3c;">
            <h2 style="color: #e74c3c;">🔄 Browser Cache Issues</h2>
            <p>If reports still show after deletion, try these steps:</p>
            <ol style="color: #94a3b8; line-height: 1.8;">
                <li><strong>Hard Refresh:</strong> Press Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)</li>
                <li><strong>Clear Cache:</strong> Press Ctrl+Shift+Delete, select "Cached images and files", click "Clear data"</li>
                <li><strong>Incognito Mode:</strong> Press Ctrl+Shift+N (Chrome) or Ctrl+Shift+P (Firefox)</li>
                <li><strong>Different Browser:</strong> Try Firefox, Chrome, or Edge</li>
            </ol>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="create_inventory_report.php" class="btn btn-success">
                ➡️ Go to Create Inventory Report
            </a>
            <a href="manage_product_inventory.php" class="btn btn-success">
                📦 Go to Manage Products
            </a>
            <a href="view_inventory_report.php" class="btn btn-success">
                📊 Go to View Reports
            </a>
        </div>

        <div style="text-align: center; margin-top: 40px; padding: 20px; background: #2a2a2a; border-radius: 8px;">
            <p style="color: #94a3b8; margin: 0;">
                📖 For detailed instructions, see <code>TROUBLESHOOTING_GUIDE.md</code>
            </p>
        </div>
    </div>
</body>
</html>
