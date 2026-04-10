CREATE DATABASE IF NOT EXISTS amuning_db_new;

USE amuning_db_new;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NULL, 
    user_type ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    register_as ENUM('email', 'google') NOT NULL DEFAULT 'email',
    full_name VARCHAR(100) DEFAULT NULL,
    mobile VARCHAR(20) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    profile VARCHAR(255) DEFAULT NULL,
    verification_code VARCHAR(6) DEFAULT NULL,
    verification_code_expires_at DATETIME DEFAULT NULL,
    is_verified TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (email, password, user_type, register_as, full_name)
VALUES('admin@gmail.com', '$2y$10$fJKn.IK0wgj8N/xciTuYm.k4FjUE10YmL64MGo2lmC2GtMHgBamyC', 'admin', 'email', 'Admin Rosa');
--admin@123

CREATE TABLE IF NOT EXISTS printing_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  full_name VARCHAR(255) NOT NULL,
  contact_number VARCHAR(20) NOT NULL,
  service VARCHAR(50),
  size VARCHAR(20),
  paper_type VARCHAR(20),
  quantity INT,
  price DECIMAL(10,2),
  image_path VARCHAR(255),
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(50) DEFAULT 'Pending',
  special_instruction VARCHAR(255),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS photo_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  mobile VARCHAR(20) NOT NULL,
  event_type VARCHAR(100) NOT NULL,
  product VARCHAR(100) NOT NULL,
  duration VARCHAR(50) NOT NULL,
  package_type VARCHAR(50) NOT NULL,
  event_date DATE NOT NULL,
  time_of_service TIME NOT NULL,
  venue VARCHAR(200) NOT NULL,
  street_address VARCHAR(200) NOT NULL,
  city VARCHAR(100) NOT NULL,
  region VARCHAR(100) NOT NULL,
  postal_code VARCHAR(20) NOT NULL,
  country VARCHAR(100) NOT NULL,
  remarks TEXT,
  estimated_price VARCHAR(20) NOT NULL,
  travel_fee VARCHAR(20) DEFAULT '0.00',
  booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(20) DEFAULT 'pending'
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    payment_type VARCHAR(50) NOT NULL COMMENT 'photo_booking or printing_order',
    reference_id INT NOT NULL COMMENT 'ID from photo_bookings or printing_orders table',
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_number VARCHAR(255),
    proof_of_payment VARCHAR(500),
    notes TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_reference_id (reference_id),
    INDEX idx_payment_type (payment_type),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS photo_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    base_price DECIMAL(10,2) NOT NULL,
    basic_price DECIMAL(10,2) NOT NULL,
    standard_price DECIMAL(10,2) NOT NULL,
    premium_price DECIMAL(10,2) NOT NULL,
    deluxe_price DECIMAL(10,2) NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS print_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    base_price DECIMAL(10,2) NOT NULL,
    paper_types TEXT COMMENT 'JSON array of available paper types',
    sizes TEXT COMMENT 'JSON array of available sizes',
    stock_quantity INT DEFAULT 0,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO photo_services (service_name, description, image_path, base_price, basic_price, standard_price, premium_price, deluxe_price, is_available) VALUES
('Birthday', 'Professional photography to capture your special birthday celebration', 'images/services_image/birthday.webp', 5000.00, 5000.00, 7500.00, 10000.00, 15000.00, TRUE),
('Wedding', 'Complete wedding day coverage with professional photographers', 'images/services_image/wedding.jpg', 25000.00, 25000.00, 35000.00, 50000.00, 75000.00, TRUE),
('Debut', 'Capture the elegance and beauty of your 18th birthday celebration', 'images/services_image/debut1.jpg', 8000.00, 8000.00, 12000.00, 18000.00, 25000.00, TRUE),
('Graduation', 'Professional graduation photos to remember your achievement', 'images/services_image/graduation.jpg', 3000.00, 3000.00, 4500.00, 6500.00, 9000.00, TRUE),
('Corporate', 'Document your corporate events, conferences, and company gatherings', 'images/services_image/corporate.webp', 10000.00, 10000.00, 15000.00, 22000.00, 35000.00, TRUE),
('Family', 'Create lasting memories with professional family portrait sessions', 'images/services_image/family.jpg', 4000.00, 4000.00, 6000.00, 9000.00, 12000.00, TRUE),
('Prenup', 'Beautiful pre-wedding photoshoot sessions at your chosen location', 'images/services_image/prenup.webp', 15000.00, 15000.00, 22000.00, 32000.00, 45000.00, TRUE),
('Christening', 'Document your childs special baptism and christening ceremony', 'images/services_image/christening.webp', 4500.00, 4500.00, 6500.00, 9500.00, 13000.00, TRUE);

INSERT INTO print_services (service_name, description, image_path, base_price, paper_types, sizes, stock_quantity, is_available) VALUES
('Standard Printing', 'High-quality document printing', 'images/services_image/standard_printing.jpg', 5.00, '["standard","glossy","matte"]', '["3x4","4x6","5x7","8x10","A4","Letter"]', 1000, TRUE),
('ID Printing', 'Professional ID cards with various options', 'images/services_image/id_printing.jpg', 15.00, '["standard","glossy","cardstock"]', '["3x4"]', 500, TRUE),
('Sticker Printing', 'Custom stickers with various finishes', 'images/services_image/sticker_printing.png', 10.00, '["glossy","matte"]', '["3x4","4x6","5x7"]', 750, TRUE),
('Photo Printing', 'High-resolution photo printing', 'images/services_image/photo_printing.png', 8.00, '["glossy","matte"]', '["3x4","4x6","5x7","8x10"]', 800, TRUE),
('Invitation Cards', 'Elegant cards for all your special events', 'images/services_image/invitation_cards.jpg', 20.00, '["glossy","matte","cardstock"]', '["5x7","A4"]', 300, TRUE),
('Flyer Printing', 'Eye-catching promotional flyers', 'images/services_image/flyer_printing.jpg', 15.00, '["glossy","matte","standard"]', '["A4","Letter"]', 600, TRUE),
('Lamination', 'Protect your documents with lamination', 'images/services_image/lamination.jpg', 12.00, '["standard"]', '["A4","Letter","3x4","4x6","5x7","8x10"]', 400, TRUE),
('Document Binding', 'Professional binding for reports and presentations', 'images/services_image/document_binding.jpg', 25.00, '["standard"]', '["A4","Letter"]', 200, TRUE);