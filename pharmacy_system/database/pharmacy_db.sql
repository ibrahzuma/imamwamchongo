-- ============================================================
-- Pharmacy Management System - Database Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

DROP DATABASE IF EXISTS pharmacy_db;
CREATE DATABASE pharmacy_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy_db;

-- ============================================================
-- Branches (Multi-branch support)
-- ============================================================
CREATE TABLE branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Users (Admin, Pharmacist, Cashier)
-- ============================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT DEFAULT 1,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    role ENUM('admin','pharmacist','cashier') NOT NULL DEFAULT 'cashier',
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Categories
-- ============================================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Suppliers
-- ============================================================
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address VARCHAR(255),
    tax_id VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Medicines
-- ============================================================
CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(64) UNIQUE,
    name VARCHAR(150) NOT NULL,
    generic_name VARCHAR(150),
    category_id INT NULL,
    supplier_id INT NULL,
    unit VARCHAR(30) DEFAULT 'pcs',
    batch_number VARCHAR(80),
    expiry_date DATE,
    cost_price DECIMAL(12,2) DEFAULT 0.00,
    selling_price DECIMAL(12,2) DEFAULT 0.00,
    quantity INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    INDEX idx_name (name),
    INDEX idx_barcode (barcode),
    INDEX idx_expiry (expiry_date)
) ENGINE=InnoDB;

-- ============================================================
-- Stock Movements (audit trail for stock in/out)
-- ============================================================
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    movement_type ENUM('in','out','adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type VARCHAR(30),  -- 'purchase','sale','manual'
    reference_id INT,
    notes VARCHAR(255),
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Sales (Invoices)
-- ============================================================
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(30) UNIQUE NOT NULL,
    branch_id INT DEFAULT 1,
    user_id INT NOT NULL,
    customer_name VARCHAR(120),
    customer_phone VARCHAR(30),
    subtotal DECIMAL(12,2) DEFAULT 0,
    tax_rate DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    discount DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) DEFAULT 0,
    paid DECIMAL(12,2) DEFAULT 0,
    payment_method ENUM('cash','card','mobile') DEFAULT 'cash',
    status ENUM('completed','pending','cancelled') DEFAULT 'completed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
) ENGINE=InnoDB;

-- ============================================================
-- Purchases (from suppliers)
-- ============================================================
CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(30) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    user_id INT NOT NULL,
    branch_id INT DEFAULT 1,
    subtotal DECIMAL(12,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    discount DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) DEFAULT 0,
    status ENUM('received','pending','cancelled') DEFAULT 'received',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    batch_number VARCHAR(80),
    expiry_date DATE,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id)
) ENGINE=InnoDB;

-- ============================================================
-- Settings
-- ============================================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(80) UNIQUE NOT NULL,
    setting_value TEXT
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA
-- ============================================================
INSERT INTO branches (name, address, phone, email) VALUES
('Main Branch', '123 Main Street, Dar es Salaam', '+255700000001', 'main@pharmacy.com'),
('Downtown Branch', '45 Market Road, Dar es Salaam', '+255700000002', 'downtown@pharmacy.com');

-- Default password for ALL sample users is: password123
-- Hash generated with PHP password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO users (branch_id, username, password, full_name, email, phone, role) VALUES
(1, 'admin', '$2y$10$EKdZ7d7GzEt9M1a2fbbNqekR1gvZMZ0oramPNX0OkuxZ2pt/8o40q', 'System Administrator', 'admin@pharmacy.com', '+255700000010', 'admin'),
(1, 'pharmacist', '$2y$10$EKdZ7d7GzEt9M1a2fbbNqekR1gvZMZ0oramPNX0OkuxZ2pt/8o40q', 'John Pharmacist', 'pharmacist@pharmacy.com', '+255700000011', 'pharmacist'),
(1, 'cashier', '$2y$10$EKdZ7d7GzEt9M1a2fbbNqekR1gvZMZ0oramPNX0OkuxZ2pt/8o40q', 'Mary Cashier', 'cashier@pharmacy.com', '+255700000012', 'cashier');

INSERT INTO categories (name, description) VALUES
('Antibiotics', 'Drugs that fight bacterial infections'),
('Painkillers', 'Pain relief medications'),
('Vitamins', 'Vitamins and supplements'),
('Antimalarials', 'Medications for malaria treatment'),
('First Aid', 'First aid supplies');

INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES
('MediPlus Distributors', 'James Mwangi', '+255711000001', 'sales@mediplus.co.tz', 'Industrial Area, Dar es Salaam'),
('PharmaCare Ltd', 'Aisha Said', '+255711000002', 'info@pharmacare.co.tz', 'Kariakoo, Dar es Salaam'),
('HealthFirst Suppliers', 'Peter Banda', '+255711000003', 'orders@healthfirst.co.tz', 'Mwanza, Tanzania');

INSERT INTO medicines (barcode, name, generic_name, category_id, supplier_id, unit, batch_number, expiry_date, cost_price, selling_price, quantity, reorder_level) VALUES
('8901234500011', 'Amoxicillin 500mg', 'Amoxicillin', 1, 1, 'capsule', 'BATCH-AMX-001', '2026-12-31', 500.00, 800.00, 250, 30),
('8901234500028', 'Paracetamol 500mg', 'Paracetamol', 2, 1, 'tablet', 'BATCH-PCM-001', '2027-06-30', 100.00, 200.00, 1000, 100),
('8901234500035', 'Ibuprofen 400mg', 'Ibuprofen', 2, 2, 'tablet', 'BATCH-IBU-001', '2026-09-30', 150.00, 300.00, 500, 50),
('8901234500042', 'Vitamin C 1000mg', 'Ascorbic Acid', 3, 2, 'tablet', 'BATCH-VTC-001', '2027-03-31', 250.00, 500.00, 200, 20),
('8901234500059', 'Coartem 80/480mg', 'Artemether/Lumefantrine', 4, 3, 'tablet', 'BATCH-CRT-001', '2026-08-15', 3500.00, 5000.00, 80, 15),
('8901234500066', 'Bandage Roll', 'Cotton Bandage', 5, 1, 'roll', 'BATCH-BND-001', '2030-01-01', 800.00, 1500.00, 50, 10),
('8901234500073', 'Cough Syrup 100ml', 'Dextromethorphan', 2, 2, 'bottle', 'BATCH-CSY-001', '2026-04-30', 1200.00, 2000.00, 5, 15),
('8901234500080', 'Multivitamin', 'Multivitamin Complex', 3, 3, 'tablet', 'BATCH-MVT-001', '2027-11-30', 300.00, 700.00, 150, 20);

INSERT INTO settings (setting_key, setting_value) VALUES
('pharmacy_name', 'PharmaCare Plus'),
('pharmacy_address', '123 Health Street, Dar es Salaam, Tanzania'),
('pharmacy_phone', '+255 700 123 456'),
('pharmacy_email', 'info@pharmacareplus.com'),
('currency', 'TZS'),
('default_tax_rate', '18');
