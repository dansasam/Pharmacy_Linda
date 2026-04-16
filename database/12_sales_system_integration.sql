-- =====================================================
-- SALES SYSTEM INTEGRATION (Process 15-18)
-- =====================================================
-- This migration adds tables for customer sales, cart,
-- prescriptions, and product logs with PayMongo support
-- =====================================================

USE pharmacy_internship;

-- =====================================================
-- SALES TABLE (Orders)
-- =====================================================
CREATE TABLE IF NOT EXISTS sales (
    sale_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    prescription_id INT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash','card','gcash','card_online','philhealth') NOT NULL DEFAULT 'cash',
    payment_status ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
    payment_reference VARCHAR(255) NULL COMMENT 'PayMongo reference or manual reference',
    paymongo_checkout_session_id VARCHAR(255) NULL COMMENT 'PayMongo checkout session ID',
    processed_by INT NULL COMMENT 'Pharmacy Assistant user_id who processed the order',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_sales_customer (customer_id),
    INDEX idx_sales_status (payment_status),
    INDEX idx_sales_payment_method (payment_method),
    INDEX idx_sales_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Customer sales/orders with PayMongo support';

-- =====================================================
-- SALE ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS sale_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product_inventory(product_id) ON DELETE RESTRICT,
    INDEX idx_items_sale (sale_id),
    INDEX idx_items_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Line items for each sale';

-- =====================================================
-- CART ITEMS TABLE (Persistent Cart)
-- =====================================================
CREATE TABLE IF NOT EXISTS cart_items (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product_inventory(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (customer_id, product_id),
    INDEX idx_cart_customer (customer_id),
    INDEX idx_cart_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Shopping cart items';

-- =====================================================
-- PRESCRIPTIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    patient_name VARCHAR(200) NOT NULL,
    patient_age INT NULL,
    patient_sex ENUM('Male','Female','Other') NULL,
    patient_address TEXT NULL,
    prescription_image VARCHAR(255) NULL COMMENT 'Path to uploaded prescription image',
    prescription_details TEXT NULL COMMENT 'Prescription details/notes',
    physician_name VARCHAR(200) NULL,
    physician_license VARCHAR(100) NULL,
    physician_address TEXT NULL,
    status ENUM('pending','verified','dispensed','rejected') NOT NULL DEFAULT 'pending',
    verified_by INT NULL COMMENT 'Pharmacist user_id who verified',
    verified_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_prescriptions_customer (customer_id),
    INDEX idx_prescriptions_status (status),
    INDEX idx_prescriptions_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Customer prescriptions for Rx products';

-- =====================================================
-- PRODUCT LOGS TABLE (Availability checks, dispensing)
-- =====================================================
CREATE TABLE IF NOT EXISTS product_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sale_id INT NULL,
    action_type ENUM('availability_check','dispensed','returned','search','add_to_cart','remove_from_cart') NOT NULL,
    quantity INT NULL COMMENT 'Quantity for dispensed/returned actions',
    notes TEXT NULL,
    performed_by INT NOT NULL COMMENT 'user_id who performed the action',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES product_inventory(product_id) ON DELETE CASCADE,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE SET NULL,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_logs_product (product_id),
    INDEX idx_logs_sale (sale_id),
    INDEX idx_logs_performed (performed_by),
    INDEX idx_logs_action (action_type),
    INDEX idx_logs_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Product activity logs for audit trail';

-- =====================================================
-- UPDATE PRODUCT INVENTORY TABLE
-- =====================================================

-- Add unit_price column (check if exists first)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'pharmacy_internship' 
    AND TABLE_NAME = 'product_inventory' 
    AND COLUMN_NAME = 'unit_price');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE product_inventory ADD COLUMN unit_price DECIMAL(10,2) DEFAULT 0.00 AFTER current_inventory',
    'SELECT "Column unit_price already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add requires_prescription column (check if exists first)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'pharmacy_internship' 
    AND TABLE_NAME = 'product_inventory' 
    AND COLUMN_NAME = 'requires_prescription');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE product_inventory ADD COLUMN requires_prescription BOOLEAN DEFAULT FALSE AFTER unit_price',
    'SELECT "Column requires_prescription already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add stock alert threshold column (check if exists first)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'pharmacy_internship' 
    AND TABLE_NAME = 'product_inventory' 
    AND COLUMN_NAME = 'stock_alert_threshold');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE product_inventory ADD COLUMN stock_alert_threshold INT DEFAULT 30 AFTER requires_prescription',
    'SELECT "Column stock_alert_threshold already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add generic_name column for better product info (check if exists first)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'pharmacy_internship' 
    AND TABLE_NAME = 'product_inventory' 
    AND COLUMN_NAME = 'generic_name');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE product_inventory ADD COLUMN generic_name VARCHAR(255) NULL AFTER drug_name',
    'SELECT "Column generic_name already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- UPDATE USERS TABLE (Optional)
-- =====================================================
-- Add PayMongo API key for per-assistant configuration (check if exists first)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'pharmacy_internship' 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'paymongo_secret_key');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE users ADD COLUMN paymongo_secret_key VARCHAR(255) NULL AFTER role',
    'SELECT "Column paymongo_secret_key already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Set default prices for existing products (if unit_price is 0)
UPDATE product_inventory 
SET unit_price = CASE 
    WHEN drug_name LIKE '%Paracetamol%' THEN 5.00
    WHEN drug_name LIKE '%Amoxicillin%' THEN 18.00
    WHEN drug_name LIKE '%Cetirizine%' THEN 10.00
    WHEN drug_name LIKE '%Ibuprofen%' THEN 8.00
    WHEN drug_name LIKE '%Biogesic%' THEN 6.00
    WHEN drug_name LIKE '%Bioflu%' THEN 12.00
    WHEN drug_name LIKE '%Neozep%' THEN 7.00
    WHEN drug_name LIKE '%Ascof%' THEN 15.00
    WHEN drug_name LIKE '%Solmux%' THEN 14.00
    WHEN drug_name LIKE '%Kremil%' THEN 9.00
    ELSE 10.00
END
WHERE unit_price = 0 OR unit_price IS NULL;

-- Update some products to require prescription
UPDATE product_inventory 
SET requires_prescription = TRUE 
WHERE drug_name LIKE '%Amoxicillin%' 
   OR drug_name LIKE '%Antibiotic%'
   OR drug_name LIKE '%Azithromycin%'
   OR drug_name LIKE '%Ciprofloxacin%'
   OR drug_name LIKE '%Metronidazole%';

-- Set stock alert thresholds
UPDATE product_inventory 
SET stock_alert_threshold = 30 
WHERE stock_alert_threshold IS NULL OR stock_alert_threshold = 0;

-- =====================================================
-- VIEWS FOR REPORTING
-- =====================================================

-- View: Sales Summary
CREATE OR REPLACE VIEW v_sales_summary AS
SELECT 
    s.sale_id,
    s.customer_id,
    u.full_name AS customer_name,
    s.total_amount,
    s.payment_method,
    s.payment_status,
    s.payment_reference,
    s.processed_by,
    p.full_name AS processed_by_name,
    s.created_at,
    COUNT(si.item_id) AS total_items
FROM sales s
JOIN users u ON s.customer_id = u.id
LEFT JOIN users p ON s.processed_by = p.id
LEFT JOIN sale_items si ON s.sale_id = si.sale_id
GROUP BY s.sale_id
ORDER BY s.created_at DESC;

-- View: Low Stock Products
CREATE OR REPLACE VIEW v_low_stock_products AS
SELECT 
    product_id,
    drug_name,
    manufacturer,
    current_inventory,
    stock_alert_threshold,
    unit_price,
    requires_prescription,
    CASE 
        WHEN current_inventory = 0 THEN 'Out of Stock'
        WHEN current_inventory <= 10 THEN 'Very Low'
        WHEN current_inventory <= stock_alert_threshold THEN 'Low'
        ELSE 'Good'
    END AS stock_status
FROM product_inventory
WHERE current_inventory <= stock_alert_threshold
ORDER BY current_inventory ASC;

-- View: Pending Prescriptions
CREATE OR REPLACE VIEW v_pending_prescriptions AS
SELECT 
    p.prescription_id,
    p.customer_id,
    u.full_name AS customer_name,
    u.email AS customer_email,
    p.patient_name,
    p.physician_name,
    p.status,
    p.created_at,
    TIMESTAMPDIFF(HOUR, p.created_at, NOW()) AS hours_pending
FROM prescriptions p
JOIN users u ON p.customer_id = u.id
WHERE p.status = 'pending'
ORDER BY p.created_at ASC;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Drop triggers if they exist
DROP TRIGGER IF EXISTS trg_update_sale_total_after_insert;
DROP TRIGGER IF EXISTS trg_update_sale_total_after_update;
DROP TRIGGER IF EXISTS trg_update_sale_total_after_delete;

-- Trigger: Auto-update sale total when items change
DELIMITER //

CREATE TRIGGER trg_update_sale_total_after_insert
AFTER INSERT ON sale_items
FOR EACH ROW
BEGIN
    UPDATE sales 
    SET total_amount = (
        SELECT SUM(line_total) 
        FROM sale_items 
        WHERE sale_id = NEW.sale_id
    )
    WHERE sale_id = NEW.sale_id;
END//

CREATE TRIGGER trg_update_sale_total_after_update
AFTER UPDATE ON sale_items
FOR EACH ROW
BEGIN
    UPDATE sales 
    SET total_amount = (
        SELECT SUM(line_total) 
        FROM sale_items 
        WHERE sale_id = NEW.sale_id
    )
    WHERE sale_id = NEW.sale_id;
END//

CREATE TRIGGER trg_update_sale_total_after_delete
AFTER DELETE ON sale_items
FOR EACH ROW
BEGIN
    UPDATE sales 
    SET total_amount = (
        SELECT COALESCE(SUM(line_total), 0) 
        FROM sale_items 
        WHERE sale_id = OLD.sale_id
    )
    WHERE sale_id = OLD.sale_id;
END//

DELIMITER ;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional indexes for common queries (check if exists first)
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'pharmacy_internship' 
    AND TABLE_NAME = 'sales' 
    AND INDEX_NAME = 'idx_sales_date_status');

SET @sql = IF(@index_exists = 0, 
    'CREATE INDEX idx_sales_date_status ON sales(created_at, payment_status)',
    'SELECT "Index idx_sales_date_status already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'pharmacy_internship' 
    AND TABLE_NAME = 'product_inventory' 
    AND INDEX_NAME = 'idx_product_inventory_stock');

SET @sql = IF(@index_exists = 0, 
    'CREATE INDEX idx_product_inventory_stock ON product_inventory(current_inventory)',
    'SELECT "Index idx_product_inventory_stock already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = 'pharmacy_internship' 
    AND TABLE_NAME = 'product_inventory' 
    AND INDEX_NAME = 'idx_product_inventory_requires_rx');

SET @sql = IF(@index_exists = 0, 
    'CREATE INDEX idx_product_inventory_requires_rx ON product_inventory(requires_prescription)',
    'SELECT "Index idx_product_inventory_requires_rx already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
-- Tables created:
--   - sales (with PayMongo support)
--   - sale_items
--   - cart_items
--   - prescriptions
--   - product_logs
--
-- Tables updated:
--   - product_inventory (added requires_prescription, stock_alert_threshold)
--   - users (added paymongo_secret_key - optional)
--
-- Views created:
--   - v_sales_summary
--   - v_low_stock_products
--   - v_pending_prescriptions
--
-- Triggers created:
--   - Auto-update sale total when items change
-- =====================================================

SELECT 'Sales System Integration Migration Complete!' AS status;
