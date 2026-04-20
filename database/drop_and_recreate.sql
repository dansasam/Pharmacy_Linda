-- Drop and Recreate Database (Clean Slate)
-- This will completely remove the existing database and recreate it fresh

-- Step 1: Drop the entire database
DROP DATABASE IF EXISTS pharmacy_internship;

-- Step 2: Recreate the database
CREATE DATABASE pharmacy_internship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Step 3: Use the new database
USE pharmacy_internship;

-- Step 4: Confirmation message
SELECT 'Database dropped and recreated successfully!' as status;
SELECT 'Now run: source database/complete_database.sql' as next_step;