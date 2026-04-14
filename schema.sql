-- Pharmacy Internship Management System
-- Create the database and tables for the prototype.

CREATE DATABASE IF NOT EXISTS pharmacy_internship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy_internship;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) DEFAULT NULL,
    role ENUM('Intern','HR Personnel','Pharmacist') NULL,
    google_id VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX(role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

INSERT IGNORE INTO internship_requirements (title, description, created_at) VALUES
('Proof of Enrollment', 'Upload a valid school enrollment letter or student ID.', NOW()),
('Emergency Contact Form', 'Upload the completed internship emergency contact form.', NOW()),
('CV / Resume', 'Upload your current curriculum vitae or resume.', NOW()),
('Signed Internship Agreement', 'Upload the signed internship agreement document.', NOW());

INSERT IGNORE INTO policies (category, title, content, created_at) VALUES
('General', 'Workplace Conduct', 'All interns must follow pharmacy policies, maintain professionalism, and ask questions when needed.', NOW()),
('Safety', 'Medication Safety', 'Follow proper handling, labeling, and storage protocols for medications and inventory.', NOW()),
('Schedule', 'Attendance & Punctuality', 'Arrive on time, notify HR for absences, and complete assigned daily tasks.', NOW());
