# Database Setup Guide - UPDATED ✅

## 📁 **Files Overview**

### **Main File (Use This!):**

**complete_database.sql** ⭐
- **Purpose:** Complete database with ALL enhancements
- **Includes:** All tables, RIS numbers, receipts, stock thresholds, notifications
- **Last Updated:** April 16, 2026
- **Status:** ✅ Production Ready

---

## 🚀 **Quick Start (Fresh Installation)**

### **Step 1: Drop existing database (if any)**
```sql
source database/drop_and_recreate.sql
```

### **Step 2: Import complete database**
```sql
source database/complete_database.sql
```

**That's it!** ✅ You now have everything:
- All tables created
- All enhancements applied (RIS numbers, receipts, stock thresholds)
- Demo accounts ready
- Sample data loaded

---

## 📊 **What's Included**

### **All Enhancements (April 16, 2026):**
- ✅ **RIS Numbers:** RIS-20260416-0001 format
- ✅ **Receipt System:** RCPT-20260416-0001 format
- ✅ **Stock Thresholds:** Very Low (10), Low (30), Reorder (50)
- ✅ **Units:** PCS, BOX, ROLL, BOTTLE, PACK support
- ✅ **Notifications:** Complete notification system
- ✅ **Approval Workflow:** Intern → Tech → Pharmacist

### **All Tables:**
- users (with application workflow)
- product_inventory (with stock thresholds)
- p1014_inventory_reports (with approval workflow)
- p1014_requisition_requests (with RIS numbers)
- p1014_requisition_items (with units)
- p1014_purchase_orders
- p1014_purchase_receipts (NEW!)
- notifications
- And 20+ more tables...

### **Demo Accounts:**
- Intern: intern@demo.com / password
- HR: hr@demo.com / password
- Pharmacist: ph@demo.com / password
- Technician: tech@demo.com / password
- Assistant: pha@demo.com / password

---

## 🔄 **For Existing Database**

If you already have the database and just want the latest enhancements:

```sql
source database/11_enhance_requisition_system.sql
```

This adds:
- RIS number format
- Receipt generation system
- Stock alert thresholds
- Unit of measurement

---

## ⚠️ **Important**

### **Moving to Another Server:**
1. Run `drop_and_recreate.sql` on new server
2. Run `complete_database.sql` on new server
3. Done! Everything is included in one file

### **Backup First:**
- `drop_and_recreate.sql` DELETES ALL DATA
- Always backup before running

---

## ✅ **Verification**

After import, verify everything works:

```sql
-- Check tables
SHOW TABLES;

-- Check demo accounts
SELECT email, role FROM users;

-- Check RIS numbers
SELECT requisition_id, ris_number FROM p1014_requisition_requests;

-- Check receipts table
DESCRIBE p1014_purchase_receipts;

-- Check stock thresholds
SELECT drug_name, very_low_threshold, low_threshold 
FROM product_inventory LIMIT 5;
```

---

**Last Updated:** April 16, 2026  
**Version:** 2.0 (Complete with all enhancements)  
**Status:** ✅ Ready for Production
