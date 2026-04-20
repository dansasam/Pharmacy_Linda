-- Fix Inventory Report Status and Add Notifications
-- This fixes the status enum and adds proper notification system

USE pharmacy_internship;

-- Step 1: Fix the status ENUM to include 'approved' and 'denied'
ALTER TABLE p1014_inventory_reports 
MODIFY COLUMN status ENUM('draft','submitted','approved','denied','reviewed') DEFAULT 'submitted';

-- Step 2: Add intern_id column to track who created the report
ALTER TABLE p1014_inventory_reports 
ADD COLUMN IF NOT EXISTS intern_id INT NULL AFTER created_by,
ADD FOREIGN KEY IF NOT EXISTS (intern_id) REFERENCES users(id) ON DELETE SET NULL;

-- Step 3: Update existing reports to link with intern if possible
UPDATE p1014_inventory_reports r
LEFT JOIN users u ON r.created_by = u.full_name AND u.role = 'Intern'
SET r.intern_id = u.id
WHERE r.intern_id IS NULL AND u.id IS NOT NULL;

-- Step 4: Add reviewed_by and reviewed_at columns
ALTER TABLE p1014_inventory_reports 
ADD COLUMN IF NOT EXISTS reviewed_by VARCHAR(100) NULL AFTER denial_remarks,
ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL AFTER reviewed_by;

-- Step 5: Verify the changes
DESCRIBE p1014_inventory_reports;

-- Step 6: Show current reports
SELECT report_id, report_date, created_by, intern_id, status, denial_remarks, reviewed_by, reviewed_at
FROM p1014_inventory_reports
ORDER BY report_id DESC
LIMIT 10;

-- Expected columns:
-- report_id, report_date, ward, created_by, intern_id, status (with approved/denied), 
-- remarks, denial_remarks, reviewed_by, reviewed_at, created_at
