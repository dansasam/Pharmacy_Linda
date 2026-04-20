-- Enhancement: Requisition System Improvements
-- Date: April 16, 2026
-- Purpose: Add receipt system, stock thresholds, and RIS numbers

-- 1. Add stock alert thresholds to product_inventory
ALTER TABLE product_inventory 
ADD COLUMN IF NOT EXISTS very_low_threshold INT DEFAULT 10 COMMENT 'Very low stock alert level',
ADD COLUMN IF NOT EXISTS low_threshold INT DEFAULT 30 COMMENT 'Low stock alert level',
ADD COLUMN IF NOT EXISTS reorder_threshold INT DEFAULT 50 COMMENT 'Reorder soon alert level',
ADD COLUMN IF NOT EXISTS unit VARCHAR(20) DEFAULT 'PCS' COMMENT 'Unit of measurement (BOX, ROLL, PCS, BOTTLE, PACK, etc.)';

-- 2. Add RIS number to requisition requests
ALTER TABLE p1014_requisition_requests
ADD COLUMN IF NOT EXISTS ris_number VARCHAR(50) UNIQUE COMMENT 'RIS number format: RIS-YYYYMMDD-XXXX';

-- 3. Add unit to requisition items
ALTER TABLE p1014_requisition_items
ADD COLUMN IF NOT EXISTS unit VARCHAR(20) DEFAULT 'PCS' COMMENT 'Unit of measurement';

-- 4. Create purchase receipts table
CREATE TABLE IF NOT EXISTS p1014_purchase_receipts (
    receipt_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    receipt_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Format: RCPT-YYYYMMDD-XXXX',
    receipt_date DATE NOT NULL,
    received_by VARCHAR(255) NOT NULL COMMENT 'Pharmacist who received the items',
    receipt_notes TEXT COMMENT 'Notes about the receipt (e.g., condition of items)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES p1014_purchase_orders(po_id) ON DELETE CASCADE,
    INDEX idx_receipt_number (receipt_number),
    INDEX idx_receipt_date (receipt_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Purchase receipts for received orders';

-- 5. Update existing products with default thresholds based on current inventory
UPDATE product_inventory 
SET 
    very_low_threshold = 10,
    low_threshold = 30,
    reorder_threshold = 50,
    unit = 'PCS'
WHERE very_low_threshold IS NULL;

-- 6. Generate RIS numbers for existing requisitions (if any)
UPDATE p1014_requisition_requests
SET ris_number = CONCAT('RIS-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(requisition_id, 4, '0'))
WHERE ris_number IS NULL;

-- 7. Set default units for existing requisition items
UPDATE p1014_requisition_items
SET unit = 'PCS'
WHERE unit IS NULL;

-- Verification queries
SELECT 'Product Inventory Thresholds Added' AS status, COUNT(*) AS count 
FROM product_inventory 
WHERE very_low_threshold IS NOT NULL;

SELECT 'RIS Numbers Generated' AS status, COUNT(*) AS count 
FROM p1014_requisition_requests 
WHERE ris_number IS NOT NULL;

SELECT 'Purchase Receipts Table Created' AS status, 
    COUNT(*) AS count 
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
    AND table_name = 'p1014_purchase_receipts';

-- Done!
SELECT '✅ Requisition System Enhancement Complete!' AS message;
