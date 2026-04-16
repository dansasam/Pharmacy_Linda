-- Clean Reset of Requirements (Simple Approach)
-- This will ensure exactly 5 requirements exist

USE pharmacy_internship;

-- Step 1: Backup existing submissions (in case we need to restore)
CREATE TABLE IF NOT EXISTS intern_submissions_backup AS 
SELECT * FROM intern_submissions;

-- Step 2: Clear all requirements and submissions
DELETE FROM intern_submissions;
DELETE FROM internship_requirements;

-- Step 3: Reset auto-increment
ALTER TABLE internship_requirements AUTO_INCREMENT = 1;

-- Step 4: Insert exactly 5 requirements
INSERT INTO internship_requirements (title, description, created_at) VALUES
('Proof of Enrollment', 'Upload a valid school enrollment letter or student ID.', NOW()),
('Birth Certificate', 'Upload a copy of your birth certificate.', NOW()),
('Pre-Internship Requirements Clearance', 'Upload the pre-internship requirements clearance form.', NOW()),
('Medical Certificate', 'Upload a valid medical certificate confirming fitness for internship.', NOW()),
('Notarized Parental/Guardian Consent Form', 'Upload the notarized parental or guardian consent form.', NOW());

-- Step 5: Verify we have exactly 5
SELECT 'Total Requirements:' as info, COUNT(*) as count FROM internship_requirements;
SELECT id, title FROM internship_requirements ORDER BY id;

-- Step 6: Update user application status to pending for all interns
UPDATE users 
SET 
    application_status = 'pending',
    requirements_completed = FALSE,
    schedule_assigned = FALSE
WHERE role = 'Intern';

-- Step 7: Show final status
SELECT 
    u.full_name,
    u.role,
    u.application_status,
    COUNT(s.id) as submitted_requirements
FROM users u
LEFT JOIN intern_submissions s ON u.id = s.user_id
WHERE u.role = 'Intern'
GROUP BY u.id, u.full_name, u.role, u.application_status;

-- Note: This will reset all intern progress, but ensures clean state
-- Users will need to re-upload their requirements