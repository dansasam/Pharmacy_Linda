-- Add source and nationality columns to employee_profiles table
-- Run this migration to add the new assessment form fields

USE pharmacy_internship;

-- Step 1: Add source and nationality columns
ALTER TABLE employee_profiles 
ADD COLUMN IF NOT EXISTS source VARCHAR(100) AFTER business_unit,
ADD COLUMN IF NOT EXISTS nationality VARCHAR(100) AFTER source;

-- Step 2: Verify the changes
DESCRIBE employee_profiles;

-- Expected new columns:
-- source VARCHAR(100)
-- nationality VARCHAR(100)

-- Step 3: Show current structure
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'pharmacy_internship' 
AND TABLE_NAME = 'employee_profiles'
ORDER BY ORDINAL_POSITION;