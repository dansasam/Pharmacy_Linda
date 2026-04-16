-- Pharmacy Internship Management System
-- Create the database and tables for the prototype.

CREATE DATABASE IF NOT EXISTS pharmacy_internship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy_internship;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) DEFAULT NULL,
    role ENUM('Intern','HR Personnel','Pharmacist','Pharmacy Technician') NULL,
    google_id VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX(role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO users (full_name, email, password_hash, role, created_at) VALUES
('Alice Santos', 'alice@example.com', NULL, 'Intern', NOW()),
('Miguel Reyes', 'miguel@example.com', NULL, 'Intern', NOW()),
('Karla dela Cruz', 'karla@example.com', NULL, 'Pharmacist', NOW()),
('Rosa Navarro', 'rosa@example.com', NULL, 'HR Personnel', NOW());

CREATE TABLE IF NOT EXISTS internship_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(150) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS intern_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    requirement_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    remarks TEXT DEFAULT NULL,
    uploaded_at DATETIME NOT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (requirement_id) REFERENCES internship_requirements(id) ON DELETE CASCADE,
    UNIQUE KEY user_requirement_unique (user_id, requirement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Note: If using complete_database.sql, skip this INSERT section
-- This is only for individual file setup
INSERT IGNORE INTO internship_requirements (title, description, created_at) VALUES
('Proof of Enrollment', 'Upload a valid school enrollment letter or student ID.', NOW()),
('Birth Certificate', 'Upload a copy of your birth certificate.', NOW()),
('Pre-Internship Requirements Clearance', 'Upload the pre-internship requirements clearance form.', NOW()),
('Medical Certificate', 'Upload a valid medical certificate confirming fitness for internship.', NOW()),
('Notarized Parental/Guardian Consent Form', 'Upload the notarized parental or guardian consent form.', NOW());

INSERT IGNORE INTO policies (category, title, content, created_at) VALUES
('General Pharmacy Operations', 'Workplace Conduct', 'All interns must follow pharmacy policies, maintain professionalism, and ask questions when needed.', NOW()),
('Patient Safety and Medication Use', 'Medication Safety', 'Follow proper handling, labeling, and storage protocols for medications and inventory.', NOW()),
('Pharmacist and Staff Responsibilities', 'Attendance & Punctuality', 'Arrive on time, notify HR for absences, and complete assigned daily tasks.', NOW());

CREATE TABLE IF NOT EXISTS employee_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intern_id INT NOT NULL,
    interview_date DATE,
    position_applied VARCHAR(100),
    business_unit VARCHAR(100),
    academic_qualifications INT DEFAULT 1,
    work_experience INT DEFAULT 1,
    technical_knowledge INT DEFAULT 1,
    industry_knowledge INT DEFAULT 1,
    communication_skills INT DEFAULT 1,
    potential_for_growth INT DEFAULT 1,
    people_management INT DEFAULT 1,
    culture_fit INT DEFAULT 1,
    problem_solving INT DEFAULT 1,
    interviewer_comments TEXT,
    hiring_status ENUM('Recommended', 'With Reservations', 'Not Recommended', 'Further Interview') DEFAULT 'Not Recommended',
    panel_member_name VARCHAR(100),
    expected_salary_benefits TEXT,
    total_rating INT DEFAULT 0,
    FOREIGN KEY (intern_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS internship_schedules (
    sched_id INT AUTO_INCREMENT PRIMARY KEY,
    intern_id INT NOT NULL,
    monday VARCHAR(50) DEFAULT 'Off',
    tuesday VARCHAR(50) DEFAULT 'Off',
    wednesday VARCHAR(50) DEFAULT 'Off',
    thursday VARCHAR(50) DEFAULT 'Off',
    friday VARCHAR(50) DEFAULT 'Off',
    saturday VARCHAR(50) DEFAULT 'Off',
    sunday VARCHAR(50) DEFAULT 'Off',
    total_hours INT DEFAULT 0,
    notes TEXT,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (intern_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT IGNORE INTO internship_schedules (intern_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday, total_hours, notes) VALUES
((SELECT id FROM users WHERE email = 'alice@example.com'), '08:00-12:00', '08:00-12:00', '08:00-12:00', '08:00-12:00', '08:00-12:00', 'Off', 'Off', 25, 'Morning intern shift'),
((SELECT id FROM users WHERE email = 'miguel@example.com'), '13:00-17:00', '13:00-17:00', '13:00-17:00', '13:00-17:00', '13:00-17:00', 'Off', 'Off', 25, 'Afternoon intern shift'),
((SELECT id FROM users WHERE email = 'karla@example.com'), '09:00-18:00', '09:00-18:00', '09:00-18:00', '09:00-18:00', '09:00-18:00', 'Off', 'Off', 45, 'Pharmacist coverage'),
((SELECT id FROM users WHERE email = 'rosa@example.com'), '09:00-16:00', 'Off', '09:00-16:00', 'Off', '09:00-16:00', 'Off', 'Off', 21, 'HR support schedule');

CREATE TABLE IF NOT EXISTS pending_applicants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intern_id INT NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    position_applied VARCHAR(100) NOT NULL,
    status ENUM('Pending Interview', 'Interviewed', 'Hired', 'Rejected') DEFAULT 'Pending Interview',
    interview_date DATETIME DEFAULT NULL,
    interview_mode ENUM('Online', 'Face to Face') DEFAULT 'Online',
    interview_location VARCHAR(255) DEFAULT NULL,
    interview_link VARCHAR(255) DEFAULT NULL,
    notification_message TEXT DEFAULT NULL,
    date_applied TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (intern_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS internship_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    intern_id INT NOT NULL,
    company_rep_name VARCHAR(100),
    school_name VARCHAR(100) DEFAULT 'Davao Central College',
    end_date DATE,
    required_hours INT,
    moa_file_path VARCHAR(255),
    is_moa_signed BOOLEAN DEFAULT FALSE,
    is_notarized BOOLEAN DEFAULT FALSE,
    verification_status ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (intern_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS staff_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_name VARCHAR(100) NOT NULL,
    role VARCHAR(100) NOT NULL,
    monday VARCHAR(50) DEFAULT 'Off',
    tuesday VARCHAR(50) DEFAULT 'Off',
    wednesday VARCHAR(50) DEFAULT 'Off',
    thursday VARCHAR(50) DEFAULT 'Off',
    friday VARCHAR(50) DEFAULT 'Off',
    saturday VARCHAR(50) DEFAULT 'Off',
    sunday VARCHAR(50) DEFAULT 'Off',
    total_hours INT DEFAULT 0,
    notes TEXT,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
