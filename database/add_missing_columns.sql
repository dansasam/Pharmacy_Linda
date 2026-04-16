-- Add missing source and nationality columns to employee_profiles table
-- Run this if you get "Column not found: source" or "nationality" errors

USE pharmacy_internship;

-- Add source column if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'pharmacy_internship' 
     AND TABLE_NAME = 'employee_profiles' 
     AND COLUMN_NAME = 'source') = 0,
    'ALTER TABLE employee_profiles ADD COLUMN source VARCHAR(100) AFTER business_unit',
    'SELECT "source column already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add nationality column if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = 'pharmacy_internship' 
     AND TABLE_NAME = 'employee_profiles' 
     AND COLUMN_NAME = 'nationality') = 0,
    'ALTER TABLE employee_profiles ADD COLUMN nationality VARCHAR(100) AFTER source',
    'SELECT "nationality column already exists" as message'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the columns were added
SELECT 'Columns added successfully!' as status;
DESCRIBE employee_profiles;