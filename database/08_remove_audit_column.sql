-- Remove audit_id column from p1014_requisition_requests table
-- This column is no longer needed since we removed the audit workflow

USE pharmacy_internship;

-- Check current structure
DESCRIBE p1014_requisition_requests;

-- Remove audit_id column if it exists
ALTER TABLE p1014_requisition_requests 
DROP COLUMN IF EXISTS audit_id;

-- Verify the change
DESCRIBE p1014_requisition_requests;

-- Optional: Drop the entire audit table if you want to completely remove it
-- Uncomment the line below if you want to delete the audit table
-- DROP TABLE IF EXISTS p1014_inventory_audits;
