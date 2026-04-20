-- Update Inventory Report Schema
-- Add new columns: sold, new_stock, old_stock, balance_stock
-- Remove: stock_at_hims (no longer needed)

USE pharmacy_internship;

-- Step 1: Add new columns to p1014_inventory_report_items table
ALTER TABLE p1014_inventory_report_items
ADD COLUMN sold INT DEFAULT 0 COMMENT 'Items used/sold' AFTER product_id,
ADD COLUMN new_stock INT DEFAULT 0 COMMENT 'New items received' AFTER sold,
ADD COLUMN old_stock INT DEFAULT 0 COMMENT 'Previous inventory level' AFTER new_stock,
ADD COLUMN balance_stock INT GENERATED ALWAYS AS (old_stock + new_stock - sold) STORED COMMENT 'Calculated: Old + New - Sold' AFTER stock_on_hand;

-- Step 2: (Optional) Drop stock_at_hims column if no longer needed
-- Uncomment the line below if you want to remove the old column completely
-- ALTER TABLE p1014_inventory_report_items DROP COLUMN stock_at_hims;

-- Step 3: Verify the changes
DESCRIBE p1014_inventory_report_items;

-- Expected columns after migration:
-- item_id, report_id, product_id, sold, new_stock, old_stock, stock_on_hand, balance_stock, 
-- expiration_date, lot_number, remarks, created_at

-- Note: The balance_stock column is a GENERATED/COMPUTED column that automatically calculates:
-- balance_stock = old_stock + new_stock - sold
