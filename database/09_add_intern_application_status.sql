-- Add intern application status tracking to existing users table
-- Run this migration to add the new columns for intern workflow

USE pharmacy_internship;

-- Step 1: Add new columns to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS application_status ENUM('pending','requirements_submitted','approved','scheduled','active','completed','rejected') DEFAULT 'pending' AFTER google_id,
ADD COLUMN IF NOT EXISTS requirements_completed BOOLEAN DEFAULT FALSE AFTER application_status,
ADD COLUMN IF NOT EXISTS schedule_assigned BOOLEAN DEFAULT FALSE AFTER requirements_completed;

-- Step 2: Add indexes for better performance
ALTER TABLE users 
ADD INDEX IF NOT EXISTS idx_application_status (application_status);

-- Step 3: Update existing users based on their current data
-- Set active status for existing interns who already have schedules and approved requirements
UPDATE users u 
SET 
    application_status = 'active',
    requirements_completed = TRUE,
    schedule_assigned = TRUE
WHERE u.role = 'Intern' 
AND EXISTS (
    SELECT 1 FROM internship_schedules s 
    WHERE s.intern_id = u.id AND s.total_hours > 0
);

-- Set approved status for interns with all requirements approved but no schedule
UPDATE users u 
SET 
    application_status = 'approved',
    requirements_completed = TRUE,
    schedule_assigned = FALSE
WHERE u.role = 'Intern' 
AND application_status = 'pending'
AND (
    SELECT COUNT(*) FROM intern_submissions s 
    WHERE s.user_id = u.id AND s.status = 'Approved'
) = (
    SELECT COUNT(*) FROM internship_requirements
);

-- Set requirements_submitted status for interns with some submissions but not all approved
UPDATE users u 
SET 
    application_status = 'requirements_submitted',
    requirements_completed = FALSE,
    schedule_assigned = FALSE
WHERE u.role = 'Intern' 
AND application_status = 'pending'
AND EXISTS (
    SELECT 1 FROM intern_submissions s 
    WHERE s.user_id = u.id
);

-- Set active status for non-intern roles (HR, Pharmacist, Technician)
UPDATE users 
SET 
    application_status = 'active',
    requirements_completed = TRUE,
    schedule_assigned = TRUE
WHERE role IN ('HR Personnel', 'Pharmacist', 'Pharmacy Technician');

-- Step 4: Verify the changes
SELECT 
    role,
    application_status,
    COUNT(*) as count
FROM users 
GROUP BY role, application_status
ORDER BY role, application_status;

-- Expected output should show the distribution of users by role and status