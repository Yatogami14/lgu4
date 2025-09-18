-- Example INSERT queries for businesses, inspection_types, and checklist_templates
-- Note: Assuming inspection_type_id in checklist_templates corresponds to IDs 1-10 from inspection_types

-- Insert into inspection_types
INSERT INTO inspection_types (name, description) VALUES
('Fire Safety Inspection', 'Checks for fire hazards and safety measures'),
('Health and Sanitation', 'Ensures hygiene and health standards'),
('Building Code Compliance', 'Verifies adherence to building regulations'),
('Electrical Safety', 'Inspects electrical systems for safety'),
('Plumbing Inspection', 'Checks plumbing installations'),
('Environmental Compliance', 'Assesses environmental impact'),
('Occupational Safety', 'Reviews workplace safety protocols'),
('Food Service Hygiene', 'For restaurants and food establishments'),
('Elevator Maintenance', 'Inspects elevators and lifts'),
('Parking Lot Safety', 'Checks parking areas for safety'),
('Waste Management', 'Reviews waste disposal practices'),
('Accessibility Compliance', 'Ensures facilities are accessible to all');

-- Insert into businesses
INSERT INTO businesses (name, address, contact_number, email, business_type, registration_number, establishment_date, inspection_frequency) VALUES
('ABC Restaurant', '123 Main St, City A', '123-456-7890', 'abc@restaurant.com', 'Restaurant', 'REG001', '2020-01-01', 'Monthly'),
('XYZ Cafe', '456 Elm St, City B', '234-567-8901', 'xyz@cafe.com', 'Cafe', 'REG002', '2019-05-15', 'Quarterly'),
('Tech Solutions Inc', '789 Oak Ave, City C', '345-678-9012', 'info@techsolutions.com', 'IT Services', 'REG003', '2018-03-20', 'Annually'),
('Green Grocery', '101 Pine Rd, City D', '456-789-0123', 'green@grocery.com', 'Grocery Store', 'REG004', '2021-07-10', 'Bi-Monthly'),
('Auto Repair Shop', '202 Maple Ln, City E', '567-890-1234', 'auto@repair.com', 'Automotive', 'REG005', '2017-11-05', 'Semi-Annually'),
('Fashion Boutique', '303 Cedar St, City F', '678-901-2345', 'fashion@boutique.com', 'Retail', 'REG006', '2022-02-28', 'Monthly'),
('Pharmacy Plus', '404 Birch Blvd, City G', '789-012-3456', 'pharmacy@plus.com', 'Pharmacy', 'REG007', '2016-09-12', 'Quarterly'),
('Fitness Center', '505 Walnut Dr, City H', '890-123-4567', 'fitness@center.com', 'Gym', 'REG008', '2019-12-01', 'Monthly'),
('Bookstore Corner', '606 Spruce St, City I', '901-234-5678', 'books@corner.com', 'Bookstore', 'REG009', '2020-06-18', 'Annually'),
('Hotel Grand', '707 Fir Ave, City J', '012-345-6789', 'hotel@grand.com', 'Hospitality', 'REG010', '2015-04-25', 'Bi-Monthly'),
('Bakery Delights', '808 Ash Rd, City K', '123-456-7891', 'bakery@delights.com', 'Bakery', 'REG011', '2021-08-30', 'Monthly'),
('Pet Clinic', '909 Poplar Ln, City L', '234-567-8902', 'pet@clinic.com', 'Veterinary', 'REG012', '2018-10-14', 'Quarterly');

-- Insert into checklist_templates
INSERT INTO checklist_templates (inspection_type_id, category, question, required, input_type) VALUES
(1, 'Fire Equipment', 'Is fire extinguisher present and accessible?', 1, 'checkbox'),
(2, 'Sanitation', 'Are floors and surfaces clean and free of debris?', 1, 'checkbox'),
(3, 'Structural Integrity', 'Are walls and ceilings in good condition?', 1, 'checkbox'),
(4, 'Electrical Systems', 'Are outlets and wiring properly installed?', 1, 'checkbox'),
(5, 'Plumbing', 'Are pipes and fixtures leak-free?', 1, 'checkbox'),
(6, 'Environmental', 'Is there proper waste segregation?', 1, 'checkbox'),
(7, 'Safety Protocols', 'Are safety signs posted appropriately?', 1, 'checkbox'),
(8, 'Food Handling', 'Is food stored at correct temperatures?', 1, 'checkbox'),
(9, 'Mechanical', 'Are elevator controls functioning?', 1, 'checkbox'),
(10, 'Parking', 'Are parking lines and lighting adequate?', 1, 'checkbox'),
(11, 'Waste Disposal', 'Are recycling bins available and used?', 1, 'checkbox'),
(12, 'Accessibility', 'Are ramps and doors accessible for wheelchairs?', 1, 'checkbox');
