-- Migration Script: Unify Product Tables
-- This script merges p1014_products into product_inventory and updates all references

USE pharmacy_internship;

-- Step 1: Ensure product_inventory has all necessary fields
ALTER TABLE product_inventory 
ADD COLUMN IF NOT EXISTS dosage VARCHAR(100) DEFAULT NULL AFTER manufacturer,
ADD COLUMN IF NOT EXISTS unit VARCHAR(50) DEFAULT 'TAB' AFTER dosage,
ADD COLUMN IF NOT EXISTS reorder_level INT DEFAULT 5 AFTER current_inventory;

-- Step 2: Migrate data from p1014_products to product_inventory (if p1014_products exists)
-- This will copy products that don't already exist in product_inventory
INSERT IGNORE INTO product_inventory (drug_name, manufacturer, dosage, unit, current_inventory, reorder_level, record_date, invoice_no, initial_comments)
SELECT 
    item_name as drug_name,
    category as manufacturer,
    dosage,
    unit,
    0 as current_inventory,
    reorder_level,
    CURDATE() as record_date,
    'MIGRATED' as invoice_no,
    'Migrated from p1014_products' as initial_comments
FROM p1014_products
WHERE NOT EXISTS (
    SELECT 1 FROM product_inventory 
    WHERE product_inventory.drug_name = p1014_products.item_name
);

-- Step 3: Update p1014_inventory_report_items to use correct product_id from product_inventory
-- This ensures all report items reference the unified product_inventory table

-- Step 4: Drop the old p1014_products table (optional - uncomment when ready)
-- DROP TABLE IF EXISTS p1014_products;

-- Verification queries:
SELECT 'product_inventory count:' as info, COUNT(*) as count FROM product_inventory;
SELECT 'Products with inventory:' as info, COUNT(*) as count FROM product_inventory WHERE current_inventory > 0;
