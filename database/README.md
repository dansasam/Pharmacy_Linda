# Database Setup Guide

Kini nga folder nag-contain sa tanan nga database schema ug migration files para sa Pharmacy Internship Management System.

## 🚀 RECOMMENDED: Fresh Start (Complete Reset)

### Step 1: Drop Existing Database
```sql
-- Run this first to clean everything:
source database/drop_and_recreate.sql;
```

### Step 2: Create Complete Database
```sql
-- Then run this for complete setup:
source database/complete_database.sql;
```

**This approach:**
- ✅ Completely clean slate
- ✅ No conflicts or duplicates
- ✅ Exactly 5 requirements
- ✅ All features working
- ✅ **12 demo accounts** for all roles

## 👥 Demo Accounts

After setup, you'll have demo accounts for testing:

| Role | Email | Password | Access Level |
|------|-------|----------|--------------|
| **Intern** | `alice@example.com` | `password` | Pending (needs requirements) |
| **Intern** | `miguel@example.com` | `password` | Pending (needs requirements) |
| **HR Personnel** | `rosa@example.com` | `password` | Full management |
| **HR Personnel** | `hr@demo.com` | `password` | Full management |
| **Pharmacist** | `karla@example.com` | `password` | Full clinical access |
| **Pharmacist** | `ph@demo.com` | `password` | Full clinical access |
| **Pharmacy Technician** | `tech@demo.com` | `password` | Inventory & reports |
| **Pharmacy Technician** | `tech2@demo.com` | `password` | Inventory & reports |
| **Pharmacist Assistant** | `pha@demo.com` | `password` | Limited access |
| **Pharmacist Assistant** | `pha2@demo.com` | `password` | Limited access |
| **Customer** | `customer@demo.com` | `password` | External user |
| **Customer** | `customer2@demo.com` | `password` | External user |

📋 **See `database/DEMO_ACCOUNTS.md` for detailed role descriptions and testing workflows.**

## 🔧 Alternative: Fix Existing Database

### If you want to keep some data:
```sql
source database/reset_requirements_clean.sql;
```

## 📁 Individual Files (Not Recommended)

**⚠️ WARNING:** Don't mix individual files with complete_database.sql - it will cause duplicates!

The individual files are kept for reference only. Use the complete database approach instead.

### 2. Sample Data (Optional)
```sql
-- Adds sample products data
source database/02_add_sample_products.sql
```

### 3. Schema Updates (Run in order)
```sql
-- Adds tracking columns
source database/03_add_tracking_columns.sql

-- Migrates products to unified structure
source database/04_migrate_products_unified.sql

-- Integrates process 10-14 features
source database/05_process10-14_integration.sql

-- Updates inventory report schema
source database/06_update_inventory_report_schema.sql

-- Fixes report status issues
source database/07_fix_report_status.sql

-- Removes audit columns (if needed)
source database/08_remove_audit_column.sql
```

## Quick Setup (Individual files)
Para sa fresh installation, pwede nimo i-run ang tanan:

```bash
# Sa MySQL command line:
source database/01_schema.sql;
source database/02_add_sample_products.sql;
source database/03_add_tracking_columns.sql;
source database/04_migrate_products_unified.sql;
source database/05_process10-14_integration.sql;
source database/06_update_inventory_report_schema.sql;
source database/07_fix_report_status.sql;
```

## Default Users Created
- **Alice Santos** (alice@example.com) - Intern
- **Miguel Reyes** (miguel@example.com) - Intern  
- **Karla dela Cruz** (karla@example.com) - Pharmacist
- **Rosa Navarro** (rosa@example.com) - HR Personnel

## Default Internship Requirements
1. Proof of Enrollment
2. Birth Certificate
3. Pre-Internship Requirements Clearance
4. Medical Certificate
5. Notarized Parental/Guardian Consent Form

## Notes
- Ang database name: `pharmacy_internship`
- Character set: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`
- Ang mga migration files naka-number para sa proper order