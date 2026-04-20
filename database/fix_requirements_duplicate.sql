-- Fix Requirements Duplication Issue
-- This script will clean up duplicate requirements and fix the count

USE pharmacy_internship;

-- Step 1: Check current state
SELECT 'Current Requirements Count:' as info, COUNT(*) as count FROM internship_requirements;

-- Step 2: Show all requirements
SELECT id, title, 'Current' as status FROM internship_requirements ORDER BY id;

-- Step 3: Check for duplicates by title
SELECT title, COUNT(*) as count 
FROM internship_requirements 
GROUP BY title 
HAVING COUNT(*) > 1;

-- Step 4: If duplicates exist, keep only the first occurrence of each
-- Create a temporary table with unique requirements
CREATE TEMPORARY TABLE temp_unique_requirements AS
SELECT MIN(id) as id, title, description, created_at
FROM internship_requirements
GROUP BY title;

-- Step 5: Update intern_submissions to reference the correct requirement IDs
-- First, create a mapping of old IDs to new IDs
CREATE TEMPORARY TABLE id_mapping AS
SELECT 
    old_req.id as old_id,
    new_req.id as new_id
FROM internship_requirements old_req
JOIN temp_unique_requirements new_req ON old_req.title = new_req.title
WHERE old_req.id != new_req.id;

-- Update submissions to use the correct requirement IDs
UPDATE intern_submissions s
JOIN id_mapping m ON s.requirement_id = m.old_id
SET s.requirement_id = m.new_id;

-- Step 6: Delete duplicate requirements (keep only the ones in temp table)
DELETE FROM internship_requirements 
WHERE id NOT IN (SELECT id FROM temp_unique_requirements);

-- Step 7: Verify the fix
SELECT 'After Cleanup - Requirements Count:' as info, COUNT(*) as count FROM internship_requirements;
SELECT id, title, 'After Cleanup' as status FROM internship_requirements ORDER BY id;

-- Step 8: Check if any submissions are orphaned
SELECT 'Orphaned Submissions:' as info, COUNT(*) as count 
FROM intern_submissions s 
LEFT JOIN internship_requirements r ON s.requirement_id = r.id 
WHERE r.id IS NULL;

-- Step 9: Show final requirements list
SELECT 
    r.id,
    r.title,
    COUNT(s.id) as total_submissions,
    SUM(CASE WHEN s.status = 'Approved' THEN 1 ELSE 0 END) as approved_submissions
FROM internship_requirements r
LEFT JOIN intern_submissions s ON r.id = s.requirement_id
GROUP BY r.id, r.title
ORDER BY r.id;

-- Clean up temporary tables
DROP TEMPORARY TABLE temp_unique_requirements;
DROP TEMPORARY TABLE id_mapping;