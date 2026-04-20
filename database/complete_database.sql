-- =====================================================
-- PHARMACY INTERNSHIP MANAGEMENT SYSTEM
-- Complete Database Schema (Consolidated)
-- =====================================================
-- This file contains all database tables, data, and migrations
-- Run this file to set up the complete system from scratch
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS pharmacy_internship CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy_internship;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) DEFAULT NULL,
    role ENUM('Intern','HR Personnel','Pharmacist','Pharmacy Technician','Customer','Pharmacist Assistant') NULL,
    google_id VARCHAR(255) DEFAULT NULL,
    application_status ENUM('pending','requirements_submitted','approved','scheduled','active','completed','rejected') DEFAULT 'pending',
    requirements_completed BOOLEAN DEFAULT FALSE,
    schedule_assigned BOOLEAN DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    INDEX(role),
    INDEX(application_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Internship requirements table
CREATE TABLE IF NOT EXISTS internship_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Policies table
CREATE TABLE IF NOT EXISTS policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(150) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Intern submissions table
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

-- Employee profiles table
CREATE TABLE IF NOT EXISTS employee_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intern_id INT NOT NULL,
    interview_date DATETIME,
    position_applied VARCHAR(100),
    business_unit VARCHAR(100),
    source VARCHAR(100),
    nationality VARCHAR(100),
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Internship schedules table
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

-- Pending applicants table
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

-- Internship records table
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

-- Staff schedules table
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

-- =====================================================
-- INVENTORY & PRODUCT MANAGEMENT TABLES
-- =====================================================

-- Product inventory table (unified)
CREATE TABLE IF NOT EXISTS product_inventory (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    drug_name VARCHAR(255) NOT NULL,
    manufacturer VARCHAR(255) DEFAULT NULL,
    dosage VARCHAR(100) DEFAULT NULL,
    unit VARCHAR(50) DEFAULT 'PCS' COMMENT 'Unit of measurement (BOX, ROLL, PCS, BOTTLE, PACK, etc.)',
    current_inventory INT DEFAULT 0,
    sold INT DEFAULT 0 COMMENT 'Items sold/used',
    new_stock INT DEFAULT 0 COMMENT 'New items received',
    reorder_level INT DEFAULT 5,
    very_low_threshold INT DEFAULT 10 COMMENT 'Very low stock alert level',
    low_threshold INT DEFAULT 30 COMMENT 'Low stock alert level',
    reorder_threshold INT DEFAULT 50 COMMENT 'Reorder soon alert level',
    record_date DATE DEFAULT NULL,
    invoice_no VARCHAR(100) DEFAULT NULL,
    initial_comments TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- PROCESS 10-14 TABLES (Inventory Reports & Prescriptions)
-- =====================================================

-- Inventory reports table
CREATE TABLE IF NOT EXISTS p1014_inventory_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    ward VARCHAR(100) DEFAULT NULL,
    created_by VARCHAR(100) DEFAULT NULL,
    intern_id INT DEFAULT NULL COMMENT 'Foreign key to users table',
    status ENUM('draft','submitted','approved','denied') DEFAULT 'submitted',
    remarks TEXT DEFAULT NULL,
    denial_remarks TEXT NULL COMMENT 'Technician remarks when denying report',
    reviewed_by VARCHAR(255) NULL COMMENT 'Technician who reviewed the report',
    reviewed_at DATETIME NULL COMMENT 'When the report was reviewed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (intern_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_intern_id (intern_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inventory report items table
CREATE TABLE IF NOT EXISTS p1014_inventory_report_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    product_id INT NOT NULL,
    sold INT DEFAULT 0 COMMENT 'Items used/sold',
    new_stock INT DEFAULT 0 COMMENT 'New items received',
    old_stock INT DEFAULT 0 COMMENT 'Previous inventory level',
    stock_on_hand INT DEFAULT 0,
    balance_stock INT GENERATED ALWAYS AS (old_stock + new_stock - sold) STORED COMMENT 'Calculated: Old + New - Sold',
    expiration_date DATE DEFAULT NULL,
    lot_number VARCHAR(100) DEFAULT NULL,
    on_purchase_order INT DEFAULT 0,
    on_back_order INT DEFAULT 0,
    remarks VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (report_id) REFERENCES p1014_inventory_reports(report_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product_inventory(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Prescriptions table
CREATE TABLE IF NOT EXISTS p1014_prescriptions (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_name VARCHAR(100) NOT NULL,
    patient_age INT DEFAULT NULL,
    patient_gender ENUM('Male','Female','Other') DEFAULT NULL,
    doctor_name VARCHAR(100) NOT NULL,
    doctor_license VARCHAR(50) DEFAULT NULL,
    clinic_address TEXT DEFAULT NULL,
    prescription_date DATE NOT NULL,
    encoded_by VARCHAR(100) DEFAULT NULL,
    status ENUM('pending','dispensed','cancelled') DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Prescription items table
CREATE TABLE IF NOT EXISTS p1014_prescription_items (
    presc_item_id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    medicine_name VARCHAR(255) NOT NULL,
    dosage VARCHAR(100) DEFAULT NULL,
    quantity INT DEFAULT 1,
    instructions VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (prescription_id) REFERENCES p1014_prescriptions(prescription_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product_inventory(product_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Requisition requests table
CREATE TABLE IF NOT EXISTS p1014_requisition_requests (
    requisition_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT DEFAULT NULL,
    ris_number VARCHAR(50) UNIQUE COMMENT 'RIS number format: RIS-YYYYMMDD-XXXX',
    requisition_date DATE NOT NULL,
    requested_by VARCHAR(100) DEFAULT NULL,
    department VARCHAR(100) DEFAULT NULL,
    suggested_vendor VARCHAR(100) DEFAULT NULL,
    delivery_point VARCHAR(100) DEFAULT NULL,
    delivery_date DATE DEFAULT NULL,
    finance_code VARCHAR(50) DEFAULT NULL,
    status ENUM('pending','approved','rejected','ordered') DEFAULT 'pending',
    justification TEXT DEFAULT NULL,
    manager_signature VARCHAR(100) DEFAULT NULL,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES p1014_inventory_reports(report_id) ON DELETE SET NULL,
    INDEX idx_ris_number (ris_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Requisition items table
CREATE TABLE IF NOT EXISTS p1014_requisition_items (
    req_item_id INT AUTO_INCREMENT PRIMARY KEY,
    requisition_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity_requested INT NOT NULL,
    unit VARCHAR(20) DEFAULT 'PCS' COMMENT 'Unit of measurement',
    unit_price DECIMAL(10,2) DEFAULT 0.00,
    total_price DECIMAL(10,2) GENERATED ALWAYS AS (quantity_requested * unit_price) STORED,
    is_out_of_stock TINYINT(1) DEFAULT 0,
    FOREIGN KEY (requisition_id) REFERENCES p1014_requisition_requests(requisition_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product_inventory(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Purchase orders table
CREATE TABLE IF NOT EXISTS p1014_purchase_orders (
    po_id INT AUTO_INCREMENT PRIMARY KEY,
    requisition_id INT NOT NULL,
    po_number VARCHAR(50) DEFAULT NULL,
    po_date DATE NOT NULL,
    approved_by VARCHAR(100) DEFAULT NULL,
    supplier_name VARCHAR(100) DEFAULT NULL,
    supplier_address TEXT DEFAULT NULL,
    delivery_date DATE DEFAULT NULL,
    status ENUM('draft','approved','denied','ordered') DEFAULT 'draft',
    denial_reason TEXT DEFAULT NULL,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requisition_id) REFERENCES p1014_requisition_requests(requisition_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Purchase receipts table
CREATE TABLE IF NOT EXISTS p1014_purchase_receipts (
    receipt_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    receipt_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Format: RCPT-YYYYMMDD-XXXX',
    receipt_date DATE NOT NULL,
    received_by VARCHAR(255) NOT NULL COMMENT 'Pharmacist who received the items',
    receipt_notes TEXT COMMENT 'Notes about the receipt (e.g., condition of items)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES p1014_purchase_orders(po_id) ON DELETE CASCADE,
    INDEX idx_receipt_number (receipt_number),
    INDEX idx_receipt_date (receipt_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Purchase receipts for received orders';

-- =====================================================
-- DEFAULT DATA INSERTS
-- =====================================================

-- Clear any existing requirements first (to prevent duplicates)
DELETE FROM intern_submissions;
DELETE FROM internship_requirements;
ALTER TABLE internship_requirements AUTO_INCREMENT = 1;

-- Insert demo users for all roles (with demo passwords for easy access)
INSERT INTO users (full_name, email, password_hash, role, application_status, requirements_completed, schedule_assigned, created_at) VALUES
-- Interns (start fresh)
('Miguel Reyes', 'miguel@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Intern', 'pending', FALSE, FALSE, NOW()),

-- HR Personnel
('Maria Santos', 'hr@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HR Personnel', 'active', TRUE, TRUE, NOW()),

-- Pharmacists
('Dr. Juan Mercado', 'ph@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pharmacist', 'active', TRUE, TRUE, NOW()),

-- Pharmacy Technicians
('Ana Rodriguez', 'tech@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pharmacy Technician', 'active', TRUE, TRUE, NOW()),

-- Pharmacist Assistants (new role)
('Lisa Garcia', 'pha@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pharmacist Assistant', 'active', TRUE, TRUE, NOW()),

-- Customers (new role)
('John Customer', 'customer@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer', 'active', TRUE, TRUE, NOW()),
('Jane Buyer', 'customer2@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Customer', 'active', TRUE, TRUE, NOW());

-- Insert exactly 5 internship requirements
INSERT INTO internship_requirements (title, description, created_at) VALUES
('Proof of Enrollment', 'Upload a valid school enrollment letter or student ID.', NOW()),
('Birth Certificate', 'Upload a copy of your birth certificate.', NOW()),
('Pre-Internship Requirements Clearance', 'Upload the pre-internship requirements clearance form.', NOW()),
('Medical Certificate', 'Upload a valid medical certificate confirming fitness for internship.', NOW()),
('Notarized Parental/Guardian Consent Form', 'Upload the notarized parental or guardian consent form.', NOW());

-- Insert default policies
INSERT IGNORE INTO policies (category, title, content, created_at) VALUES
('General Pharmacy Operations', 'Workplace Conduct', 'All interns must follow pharmacy policies, maintain professionalism, and ask questions when needed.', NOW()),
('Patient Safety and Medication Use', 'Medication Safety', 'Follow proper handling, labeling, and storage protocols for medications and inventory.', NOW()),
('Pharmacist and Staff Responsibilities', 'Attendance & Punctuality', 'Arrive on time, notify HR for absences, and complete assigned daily tasks.', NOW());

-- Insert default schedules for all staff
INSERT IGNORE INTO internship_schedules (intern_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday, total_hours, notes) VALUES
-- Interns (will be updated when they get active)
((SELECT id FROM users WHERE email = 'alice@example.com'), 'Off', 'Off', 'Off', 'Off', 'Off', 'Off', 'Off', 0, 'Pending schedule assignment'),
((SELECT id FROM users WHERE email = 'miguel@example.com'), 'Off', 'Off', 'Off', 'Off', 'Off', 'Off', 'Off', 0, 'Pending schedule assignment'),

-- HR Personnel
((SELECT id FROM users WHERE email = 'rosa@example.com'), '09:00-16:00', 'Off', '09:00-16:00', 'Off', '09:00-16:00', 'Off', 'Off', 21, 'HR support schedule'),
((SELECT id FROM users WHERE email = 'hr@demo.com'), '08:00-17:00', '08:00-17:00', '08:00-17:00', '08:00-17:00', '08:00-17:00', 'Off', 'Off', 45, 'Full-time HR staff'),

-- Pharmacists
((SELECT id FROM users WHERE email = 'karla@example.com'), '09:00-18:00', '09:00-18:00', '09:00-18:00', '09:00-18:00', '09:00-18:00', 'Off', 'Off', 45, 'Senior Pharmacist'),
((SELECT id FROM users WHERE email = 'ph@demo.com'), '07:00-16:00', '07:00-16:00', '07:00-16:00', '07:00-16:00', '07:00-16:00', '07:00-12:00', 'Off', 50, 'Head Pharmacist'),

-- Pharmacy Technicians
((SELECT id FROM users WHERE email = 'tech@demo.com'), '08:00-17:00', '08:00-17:00', '08:00-17:00', '08:00-17:00', '08:00-17:00', 'Off', 'Off', 45, 'Senior Technician'),
((SELECT id FROM users WHERE email = 'tech2@demo.com'), '13:00-22:00', '13:00-22:00', '13:00-22:00', '13:00-22:00', '13:00-22:00', 'Off', 'Off', 45, 'Evening shift technician'),

-- Pharmacist Assistants
((SELECT id FROM users WHERE email = 'pha@demo.com'), '08:00-16:00', '08:00-16:00', '08:00-16:00', '08:00-16:00', '08:00-16:00', 'Off', 'Off', 40, 'Morning assistant'),
((SELECT id FROM users WHERE email = 'pha2@demo.com'), '14:00-22:00', '14:00-22:00', '14:00-22:00', '14:00-22:00', '14:00-22:00', 'Off', 'Off', 40, 'Afternoon assistant');

-- Insert sample products
INSERT IGNORE INTO product_inventory (drug_name, manufacturer, dosage, unit, current_inventory, reorder_level) VALUES
('Paracetamol', 'Unilab', '500mg', 'TAB', 150, 10),
('Ibuprofen', 'Pfizer', '400mg', 'TAB', 200, 15),
('Ambroxol', 'Boehringer', '30mg', 'TAB', 180, 12),
('Cetirizine', 'GSK', '10mg', 'TAB', 220, 20),
('Mefenamic Acid', 'Unilab', '500mg', 'TAB', 130, 10),
('Loperamide', 'Janssen', '2mg', 'TAB', 95, 8),
('Omeprazole', 'AstraZeneca', '20mg', 'CAP', 175, 15),
('Metformin', 'Merck', '500mg', 'TAB', 300, 25),
('Losartan', 'MSD', '50mg', 'TAB', 160, 12),
('Amlodipine', 'Pfizer', '5mg', 'TAB', 190, 15),
('Acetaminophen', 'Generic', '500mg', 'TAB', 279, 20),
('Amoxicillin', 'Generic', '250mg', 'CAP', 241, 18),
('Biogesic', 'Unilab', '500mg', 'TAB', 160, 15);

-- Insert specialized products for hospital use
INSERT IGNORE INTO product_inventory (drug_name, manufacturer, dosage, unit, current_inventory, reorder_level) VALUES
('DIAZEPAM', 'Generic', '5MG/ML 2ML (IV)', 'AMP', 10, 3),
('FENTANYL', 'Generic', '50mcg/mL 2mL (IV)', 'AMP', 5, 3),
('MIDAZOLAM', 'Generic', '5mg/mL 1mL (IM,IV)', 'AMP', 8, 3),
('MORPHINE', 'Generic', '10mg/mL 1mL (IM,IV,SC)', 'AMP', 6, 3),
('NALBUPHINE', 'Generic', '', 'AMP', 4, 3),
('DIPHENHYDRAMINE', 'Generic', '50MG/ML 1ML (IM,IV)', 'AMP', 12, 3),
('HYDROCORTISONE', 'Generic', '100mg POWDER (IV)', 'VIAL', 8, 3),
('CLOXACILLIN', 'Generic', '500MG', 'CAP', 50, 5),
('MEBENDAZOLE', 'Generic', '500MG', 'TAB', 30, 5),
('LEVETIRACETAM', 'Generic', 'VIAL 500MG', 'VIAL', 6, 3),
('MAGNESIUM SULFATE', 'Generic', '250MG/ML 20ML', 'AMP', 10, 3),
('PHENOBARBITAL', 'Generic', '30MG', 'TAB', 25, 5);

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Show table counts
SELECT 'Users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'Internship Requirements', COUNT(*) FROM internship_requirements
UNION ALL
SELECT 'Policies', COUNT(*) FROM policies
UNION ALL
SELECT 'Product Inventory', COUNT(*) FROM product_inventory
UNION ALL
SELECT 'Internship Schedules', COUNT(*) FROM internship_schedules;

-- Show users by role
SELECT 
    role,
    COUNT(*) as count,
    GROUP_CONCAT(full_name SEPARATOR ', ') as users
FROM users 
GROUP BY role 
ORDER BY role;

-- =====================================================
-- DEMO ACCOUNTS & LOGIN CREDENTIALS
-- =====================================================

/*
DEMO ACCOUNTS FOR TESTING:
All accounts use password: "password" (hashed as $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)

INTERNS (Status: Pending - need to complete requirements):
- alice@example.com (Alice Santos)
- miguel@example.com (Miguel Reyes)

HR PERSONNEL (Full access to manage interns, requirements, schedules):
- rosa@example.com (Rosa Navarro)
- hr@demo.com (Maria Santos)

PHARMACISTS (Full access to inventory, reports, prescriptions):
- karla@example.com (Karla dela Cruz)
- ph@demo.com (Dr. Juan Mercado)

PHARMACY TECHNICIANS (Access to inventory, reports):
- tech@demo.com (Ana Rodriguez)
- tech2@demo.com (Carlos Mendoza)

PHARMACIST ASSISTANTS (Limited access, assist pharmacists):
- pha@demo.com (Lisa Garcia)
- pha2@demo.com (Mark Villanueva)

CUSTOMERS (External users, prescription access):
- customer@demo.com (John Customer)
- customer2@demo.com (Jane Buyer)

WORKFLOW TEST:
1. Login as alice@example.com → Upload requirements → Status: "Requirements Submitted"
2. Login as rosa@example.com → Approve Alice's requirements → Alice status: "Approved"
3. Rosa assigns schedule to Alice → Alice status: "Active"
4. Alice can now access inventory and reports!
*/

-- =====================================================
-- SETUP COMPLETE
-- =====================================================
-- Database setup completed successfully!
-- You can now use the pharmacy internship management system.
-- =====================================================