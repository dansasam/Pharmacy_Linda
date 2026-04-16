-- Add sample products with quantities to product_inventory table
-- Run this in phpMyAdmin to add test data

USE pharmacy_internship;

-- Update existing products with quantities (if they exist)
UPDATE product_inventory SET current_inventory = 279 WHERE drug_name = 'Acetaminophen 500mg';
UPDATE product_inventory SET current_inventory = 241 WHERE drug_name = 'Amoxicillin 250mg';
UPDATE product_inventory SET current_inventory = 183 WHERE drug_name = 'Cetinzine 10mg';
UPDATE product_inventory SET current_inventory = 160 WHERE drug_name = 'Biogesic';
UPDATE product_inventory SET current_inventory = 200 WHERE drug_name = 'ceterizine';
UPDATE product_inventory SET current_inventory = 170 WHERE drug_name = 'adad';

-- Insert new sample products if they don't exist
INSERT IGNORE INTO product_inventory (drug_name, manufacturer, current_inventory) VALUES
('Paracetamol 500mg', 'Unilab', 150),
('Ibuprofen 400mg', 'Pfizer', 200),
('Ambroxol 30mg', 'Boehringer', 180),
('Cetirizine 10mg', 'GSK', 220),
('Mefenamic Acid 500mg', 'Unilab', 130),
('Loperamide 2mg', 'Janssen', 95),
('Omeprazole 20mg', 'AstraZeneca', 175),
('Metformin 500mg', 'Merck', 300),
('Losartan 50mg', 'MSD', 160),
('Amlodipine 5mg', 'Pfizer', 190);

-- Verify the data
SELECT product_id, drug_name, manufacturer, current_inventory FROM product_inventory ORDER BY drug_name;
