-- Fix reports with NULL or empty status
-- Run this in phpMyAdmin to set default status to 'submitted'

USE pharmacy_internship;

-- Update reports with NULL status
UPDATE p1014_inventory_reports 
SET status = 'submitted' 
WHERE status IS NULL OR status = '';

-- Verify the fix
SELECT report_id, report_date, ward, created_by, status 
FROM p1014_inventory_reports 
ORDER BY report_id DESC;
