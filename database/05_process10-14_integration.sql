-- Process 10-14 Tables for Pharmacy Internship System
-- These tables will be added to the main pharmacy_internship database

CREATE TABLE IF NOT EXISTS p1014_inventory_audits (
  audit_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  report_id int(11) NOT NULL,
  audited_by varchar(100) DEFAULT NULL,
  audit_date timestamp NOT NULL DEFAULT current_timestamp(),
  audit_status enum('pending','verified','discrepancy_found') DEFAULT 'pending',
  discrepancy_notes text DEFAULT NULL,
  action_taken varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS p1014_inventory_reports (
  report_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  report_date date NOT NULL,
  ward varchar(100) DEFAULT NULL,
  created_by varchar(100) DEFAULT NULL,
  status enum('draft','submitted','reviewed') DEFAULT 'draft',
  remarks text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS p1014_inventory_report_items (
  item_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  report_id int(11) NOT NULL,
  product_id int(11) NOT NULL,
  stock_at_hims int(11) DEFAULT 0,
  stock_on_hand int(11) DEFAULT 0,
  expiration_date date DEFAULT NULL,
  lot_number varchar(100) DEFAULT NULL,
  on_purchase_order int(11) DEFAULT 0,
  on_back_order int(11) DEFAULT 0,
  remarks varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS p1014_prescriptions (
  prescription_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  patient_name varchar(100) NOT NULL,
  patient_age int(11) DEFAULT NULL,
  patient_gender enum('Male','Female','Other') DEFAULT NULL,
  doctor_name varchar(100) NOT NULL,
  doctor_license varchar(50) DEFAULT NULL,
  clinic_address text DEFAULT NULL,
  prescription_date date NOT NULL,
  encoded_by varchar(100) DEFAULT NULL,
  status enum('pending','dispensed','cancelled') DEFAULT 'pending',
  notes text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS p1014_prescription_items (
  presc_item_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  prescription_id int(11) NOT NULL,
  product_id int(11) DEFAULT NULL,
  medicine_name varchar(255) NOT NULL,
  dosage varchar(100) DEFAULT NULL,
  quantity int(11) DEFAULT 1,
  instructions varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS p1014_products (
  product_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  item_name varchar(255) NOT NULL,
  dosage varchar(100) DEFAULT NULL,
  category varchar(100) DEFAULT NULL,
  unit varchar(50) DEFAULT NULL,
  reorder_level int(11) DEFAULT 5,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO p1014_products (product_id, item_name, dosage, category, unit, reorder_level, created_at) VALUES
(1, 'DIAZEPAM', '5MG/ML 2ML (IV)', 'Anesthesia', 'AMP', 3, '2026-04-14 03:01:29'),
(2, 'FENTANYL', '50mcg/mL 2mL (IV)', 'Anesthesia', 'AMP', 3, '2026-04-14 03:01:29'),
(3, 'MIDAZOLAM', '5mg/mL 1mL (IM,IV)', 'Anesthesia', 'AMP', 3, '2026-04-14 03:01:29'),
(4, 'MORPHINE', '10mg/mL 1mL (IM,IV,SC)', 'Anesthesia', 'AMP', 3, '2026-04-14 03:01:29'),
(5, 'NALBUPHINE', '', 'Anesthesia', 'AMP', 3, '2026-04-14 03:01:29'),
(6, 'CETIRIZINE', '10mg', 'Anti-Allergy', 'TAB', 5, '2026-04-14 03:01:29'),
(7, 'DIPHENHYDRAMINE', '50MG/ML 1ML (IM,IV)', 'Anti-Allergy', 'AMP', 3, '2026-04-14 03:01:29'),
(8, 'HYDROCORTISONE', '100mg POWDER (IV)', 'Anti-Allergy', 'VIAL', 3, '2026-04-14 03:01:29'),
(9, 'CLOXACILLIN', '500MG', 'Anti-Infectives', 'CAP', 5, '2026-04-14 03:01:29'),
(10, 'MEBENDAZOLE', '500MG', 'Anti-Infectives', 'TAB', 5, '2026-04-14 03:01:29'),
(11, 'LEVETIRACETAM', 'VIAL 500MG', 'Anticonvulsant', 'VIAL', 3, '2026-04-14 03:01:29'),
(12, 'MAGNESIUM SULFATE', '250MG/ML 20ML', 'Anticonvulsant', 'AMP', 3, '2026-04-14 03:01:29'),
(13, 'PHENOBARBITAL', '30MG', 'Anticonvulsant', 'TAB', 5, '2026-04-14 03:01:29');

CREATE TABLE IF NOT EXISTS p1014_purchase_orders (
  po_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  requisition_id int(11) NOT NULL,
  po_number varchar(50) DEFAULT NULL,
  po_date date NOT NULL,
  approved_by varchar(100) DEFAULT NULL,
  supplier_name varchar(100) DEFAULT NULL,
  supplier_address text DEFAULT NULL,
  delivery_date date DEFAULT NULL,
  status enum('draft','approved','denied','ordered') DEFAULT 'draft',
  denial_reason text DEFAULT NULL,
  total_amount decimal(10,2) DEFAULT 0.00,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS p1014_requisition_items (
  req_item_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  requisition_id int(11) NOT NULL,
  product_id int(11) NOT NULL,
  quantity_requested int(11) NOT NULL,
  unit_price decimal(10,2) DEFAULT 0.00,
  total_price decimal(10,2) GENERATED ALWAYS AS (quantity_requested * unit_price) STORED,
  is_out_of_stock tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS p1014_requisition_requests (
  requisition_id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  audit_id int(11) DEFAULT NULL,
  report_id int(11) DEFAULT NULL,
  requisition_date date NOT NULL,
  requested_by varchar(100) DEFAULT NULL,
  department varchar(100) DEFAULT NULL,
  suggested_vendor varchar(100) DEFAULT NULL,
  delivery_point varchar(100) DEFAULT NULL,
  delivery_date date DEFAULT NULL,
  finance_code varchar(50) DEFAULT NULL,
  status enum('pending','approved','rejected','ordered') DEFAULT 'pending',
  justification text DEFAULT NULL,
  manager_signature varchar(100) DEFAULT NULL,
  total_amount decimal(10,2) DEFAULT 0.00,
  created_at timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;