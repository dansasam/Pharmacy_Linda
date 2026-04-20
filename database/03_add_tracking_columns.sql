-- Add tracking columns to product_inventory and denial_remarks to reports
-- Run this migration to add sold, new_stock columns and denial tracking

USE pharmacy_internship;

-- Step 1: Add sold and new_stock columns to product_inventory table
ALTER TABLE product_inventory
ADD COLUMN sold INT DEFAULT 0 COMMENT 'Items sold/used' AFTER current_inventory,
ADD COLUMN new_stock INT DEFAULT 0 COMMENT 'New items received' AFTER sold;

-- Step 2: Add denial_remarks column to p1014_inventory_reports table
ALTER TABLE p1014_inventory_reports
ADD COLUMN denial_remarks TEXT NULL COMMENT 'Technician remarks when denying report' AFTER remarks;

-- Step 3: Verify the changes
DESCRIBE product_inventory;
DESCRIBE p1014_inventory_reports;

-- Expected columns in product_inventory:
-- product_id, drug_name, manufacturer, current_inventory, sold, new_stock, date, invoice, comments

-- Expected columns in p1014_inventory_reports:
-- report_id, report_date, ward, created_by, remarks, denial_remarks, status, created_at
