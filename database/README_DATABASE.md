# 📁 Database Files Guide

## 🎯 **MAIN FILE TO USE** (Recommended)

### **`COMPLETE_DATABASE_WITH_SALES.sql`** ⭐⭐⭐
**USE THIS FILE FOR FRESH INSTALLATION**

This is the **COMPLETE, ALL-IN-ONE** database file that includes:
- ✅ All core tables (users, requirements, policies, schedules, etc.)
- ✅ All inventory tables (products, reports, requisitions, purchase orders)
- ✅ **All sales system tables** (sales, cart, prescriptions, product logs)
- ✅ All sample data and demo accounts
- ✅ All views, triggers, and indexes
- ✅ Notifications table
- ✅ PayMongo integration support

**How to Use:**
```sql
-- In phpMyAdmin or MySQL client:
SOURCE C:/xampp/htdocs/Pharmacy_Linda/database/COMPLETE_DATABASE_WITH_SALES.sql;
```

**Or in phpMyAdmin:**
1. Open phpMyAdmin
2. Click "Import" tab
3. Choose file: `COMPLETE_DATABASE_WITH_SALES.sql`
4. Click "Go"
5. Done! ✅

---

## 📋 Other Database Files (For Reference)

### **`complete_database.sql`**
- Original complete database WITHOUT sales system
- Use only if you don't need the sales features

### **`12_sales_system_integration.sql`**
- Sales system ONLY (requires existing database)
- Use this if you already have the main database and just want to add sales features

### **`fix_inventory_report_status.sql`**
- Fixes for inventory report status issues
- Run this if you have problems with report approvals

### **`drop_and_recreate.sql`**
- Drops and recreates specific tables
- Use with caution! This deletes data

---

## 🗄️ Database Structure

### **Database Name:** `pharmacy_internship`

### **Total Tables:** 30+

#### **Core System Tables (8):**
1. `users` - All system users (Interns, HR, Pharmacists, Technicians, Assistants, Customers)
2. `internship_requirements` - Required documents for interns
3. `policies` - System policies
4. `intern_submissions` - Intern document submissions
5. `employee_profiles` - Interview and hiring data
6. `internship_schedules` - Intern work schedules
7. `pending_applicants` - Applicants awaiting interview
8. `internship_records` - MOA and internship records
9. `staff_schedules` - Staff work schedules
10. `notifications` - System notifications

#### **Inventory Tables (6):**
11. `product_inventory` - All products with prices and stock
12. `p1014_inventory_reports` - Inventory reports
13. `p1014_inventory_report_items` - Report line items
14. `p1014_prescriptions` - Medical prescriptions
15. `p1014_prescription_items` - Prescription line items
16. `p1014_requisition_requests` - Purchase requisitions (RIS)
17. `p1014_requisition_items` - Requisition line items
18. `p1014_purchase_orders` - Purchase orders (PO)
19. `p1014_purchase_receipts` - Purchase receipts

#### **Sales System Tables (5):** ⭐ NEW
20. `sales` - Customer orders with PayMongo support
21. `sale_items` - Order line items
22. `cart_items` - Shopping cart (persistent)
23. `prescriptions` - Customer prescriptions for Rx products
24. `product_logs` - Product activity audit trail

#### **Views (3):**
- `v_sales_summary` - Sales overview
- `v_low_stock_products` - Low stock alerts
- `v_pending_prescriptions` - Pending prescription approvals

#### **Triggers (3):**
- Auto-update sale totals when items change

---

## 👥 Demo Accounts

All accounts use password: **"password"**

### **Interns:**
- `alice@example.com` - Alice Santos (Pending)
- `miguel@example.com` - Miguel Reyes (Pending)

### **HR Personnel:**
- `rosa@example.com` - Rosa Navarro
- `hr@demo.com` - Maria Santos

### **Pharmacists:**
- `karla@example.com` - Karla dela Cruz
- `ph@demo.com` - Dr. Juan Mercado

### **Pharmacy Technicians:**
- `tech@demo.com` - Ana Rodriguez
- `tech2@demo.com` - Carlos Mendoza

### **Pharmacy Assistants:** ⭐ NEW
- `pha@demo.com` - Lisa Garcia
- `pha2@demo.com` - Mark Villanueva

### **Customers:** ⭐ NEW
- `customer@demo.com` - John Customer
- `customer2@demo.com` - Jane Buyer

---

## 🔧 After Import

### **1. Verify Tables Created:**
```sql
USE pharmacy_internship;
SHOW TABLES;
```

You should see 30+ tables.

### **2. Check Sample Data:**
```sql
-- Check users
SELECT role, COUNT(*) as count FROM users GROUP BY role;

-- Check products with prices
SELECT product_id, drug_name, unit_price, current_inventory 
FROM product_inventory 
LIMIT 10;

-- Check sales tables
SHOW TABLES LIKE '%sales%';
```

### **3. Test Login:**
- Go to your system login page
- Use any demo account above
- Password: `password`

---

## 🚀 Quick Start After Import

### **For Testing Sales System:**

1. **Login as Customer** (`customer@demo.com`)
   - Browse products: `browse_products.php`
   - Add to cart
   - Checkout

2. **Login as Pharmacy Assistant** (`pha@demo.com`)
   - View orders: `assistant_orders.php`
   - Confirm payment
   - Dispense order (inventory auto-deducts!)

3. **Verify Inventory Deducted:**
   ```sql
   SELECT product_id, drug_name, current_inventory 
   FROM product_inventory 
   WHERE product_id = [PRODUCT_ID];
   ```

---

## ⚠️ Important Notes

### **For Fresh Installation:**
- ✅ Use `COMPLETE_DATABASE_WITH_SALES.sql`
- ✅ This will create database and all tables
- ✅ Includes all sample data

### **For Existing Database:**
- ⚠️ If you already have data, backup first!
- ⚠️ The complete file uses `CREATE TABLE IF NOT EXISTS`
- ⚠️ Existing data will be preserved
- ⚠️ New tables will be added

### **For Migration Between Computers:**
1. Export your current database (if you have data)
2. On new computer, import `COMPLETE_DATABASE_WITH_SALES.sql`
3. If you had custom data, import your backup after

---

## 🔍 Troubleshooting

### **Issue: "Table already exists"**
- This is normal! The script uses `IF NOT EXISTS`
- Your existing data is safe

### **Issue: "Foreign key constraint fails"**
- Make sure you're importing the COMPLETE file
- Don't import partial files

### **Issue: "Unknown column 'unit_price'"**
- You're using old database structure
- Import `COMPLETE_DATABASE_WITH_SALES.sql` fresh

### **Issue: "Database not found"**
- The script creates the database automatically
- Just import the file

---

## 📊 Database Size

**Approximate Size:**
- Empty database: ~500 KB
- With sample data: ~1 MB
- With 1000 products: ~5 MB
- With 10000 sales: ~20 MB

---

## 🎯 Summary

**For New Installation:**
```
1. Import: COMPLETE_DATABASE_WITH_SALES.sql
2. Done! ✅
```

**For Adding Sales to Existing Database:**
```
1. Backup your database first
2. Import: 12_sales_system_integration.sql
3. Done! ✅
```

**For Migration:**
```
1. Copy: COMPLETE_DATABASE_WITH_SALES.sql
2. Import on new computer
3. Done! ✅
```

---

**Tapos na! Usa ra ka file, import lang dayon! 🎉**

**File to Use:** `COMPLETE_DATABASE_WITH_SALES.sql`  
**Size:** ~500 KB  
**Tables:** 30+  
**Demo Accounts:** 13  
**Ready:** ✅
