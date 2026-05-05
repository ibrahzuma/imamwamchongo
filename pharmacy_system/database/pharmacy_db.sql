-- ============================================================
-- Pharmacy Management System - Multi-Tenant Database Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.3+
--
-- Tenancy model:
--   pharmacies (id) is the tenant. Every business table carries
--   pharmacy_id and is filtered by it at the application layer.
--   `superadmin` users have pharmacy_id = NULL and can manage
--   all pharmacies.
-- ============================================================

DROP DATABASE IF EXISTS pharmacy_db;
CREATE DATABASE pharmacy_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmacy_db;

-- ============================================================
-- Pharmacies (tenants) — the top-level container
-- ============================================================
CREATE TABLE pharmacies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(80) UNIQUE NOT NULL,
    license_number VARCHAR(80),
    address VARCHAR(255),
    phone VARCHAR(30),
    email VARCHAR(120),
    logo_path VARCHAR(255),
    api_key VARCHAR(64) UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Branches (sub-locations within a pharmacy — optional)
-- ============================================================
CREATE TABLE branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE CASCADE,
    INDEX idx_branch_pharmacy (pharmacy_id)
) ENGINE=InnoDB;

-- ============================================================
-- Users (Superadmin / Admin / Pharmacist / Cashier)
-- Superadmin: pharmacy_id is NULL.
-- ============================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT NULL,
    branch_id INT DEFAULT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    role ENUM('superadmin','admin','pharmacist','cashier') NOT NULL DEFAULT 'cashier',
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    INDEX idx_user_pharmacy (pharmacy_id)
) ENGINE=InnoDB;

-- ============================================================
-- Categories (per-pharmacy)
-- ============================================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT NOT NULL,
    name VARCHAR(80) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE CASCADE,
    INDEX idx_cat_pharmacy (pharmacy_id)
) ENGINE=InnoDB;

-- ============================================================
-- Suppliers (per-pharmacy)
-- ============================================================
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address VARCHAR(255),
    tax_id VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE CASCADE,
    INDEX idx_sup_pharmacy (pharmacy_id)
) ENGINE=InnoDB;

-- ============================================================
-- Medicines (per-pharmacy)
-- Note: barcode is unique per-pharmacy (not globally), to allow
-- two pharmacies to carry the same product.
-- ============================================================
CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT NOT NULL,
    barcode VARCHAR(64),
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
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_pharmacy_barcode (pharmacy_id, barcode),
    INDEX idx_med_pharmacy (pharmacy_id),
    INDEX idx_name (name),
    INDEX idx_expiry (expiry_date)
) ENGINE=InnoDB;

-- ============================================================
-- Stock Movements (audit trail for stock in/out)
-- ============================================================
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT NOT NULL,
    medicine_id INT NOT NULL,
    movement_type ENUM('in','out','adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type VARCHAR(30),  -- 'purchase','sale','manual'
    reference_id INT,
    notes VARCHAR(255),
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_mov_pharmacy (pharmacy_id)
) ENGINE=InnoDB;

-- ============================================================
-- Sales (Invoices) — per-pharmacy
-- invoice_number is unique per-pharmacy.
-- ============================================================
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT NOT NULL,
    invoice_number VARCHAR(30) NOT NULL,
    branch_id INT DEFAULT NULL,
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
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_pharmacy_invoice (pharmacy_id, invoice_number),
    INDEX idx_sale_pharmacy (pharmacy_id),
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
-- Purchases (from suppliers) — per-pharmacy
-- ============================================================
CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT NOT NULL,
    reference_number VARCHAR(30) NOT NULL,
    supplier_id INT NOT NULL,
    user_id INT NOT NULL,
    branch_id INT DEFAULT NULL,
    subtotal DECIMAL(12,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    discount DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) DEFAULT 0,
    status ENUM('received','pending','cancelled') DEFAULT 'received',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_pharmacy_purchase_ref (pharmacy_id, reference_number),
    INDEX idx_pur_pharmacy (pharmacy_id)
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
-- Settings (per-pharmacy, plus global fallback when pharmacy_id IS NULL)
-- ============================================================
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT NULL,
    setting_key VARCHAR(80) NOT NULL,
    setting_value TEXT,
    UNIQUE KEY uniq_pharmacy_setting (pharmacy_id, setting_key),
    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Two demo pharmacies
INSERT INTO pharmacies (name, slug, license_number, address, phone, email, api_key) VALUES
('PharmaCare Plus', 'pharmacare-plus', 'LIC-001-2024', '123 Health Street, Dar es Salaam, Tanzania', '+255 700 123 456', 'info@pharmacareplus.com', 'pk_demo_pharmacare_plus_001'),
('MediLink Pharmacy', 'medilink', 'LIC-002-2024', '88 Uhuru Road, Arusha, Tanzania',         '+255 700 222 333', 'hello@medilink.co.tz',     'pk_demo_medilink_002');

-- Branches (each belongs to a pharmacy)
INSERT INTO branches (pharmacy_id, name, address, phone, email) VALUES
(1, 'Main Branch',     '123 Main Street, Dar es Salaam', '+255700000001', 'main@pharmacareplus.com'),
(1, 'Downtown Branch', '45 Market Road, Dar es Salaam',  '+255700000002', 'downtown@pharmacareplus.com'),
(2, 'Arusha Central',  '88 Uhuru Road, Arusha',          '+255700000003', 'central@medilink.co.tz');

-- Users
-- Default password for ALL sample users is: password123
-- Hash generated with PHP password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO users (pharmacy_id, branch_id, username, password, full_name, email, phone, role) VALUES
-- Platform-level superadmin (no pharmacy)
(NULL, NULL, 'superadmin', '$2y$10$EKdZ7d7GzEt9M1a2fbbNqekR1gvZMZ0oramPNX0OkuxZ2pt/8o40q', 'Platform Superadmin',  'super@platform.com',         '+255700000099', 'superadmin'),
-- Pharmacy 1 staff
(1,    1,    'admin',      '$2y$10$EKdZ7d7GzEt9M1a2fbbNqekR1gvZMZ0oramPNX0OkuxZ2pt/8o40q', 'PharmaCare Admin',     'admin@pharmacareplus.com',   '+255700000010', 'admin'),
(1,    1,    'pharmacist', '$2y$10$EKdZ7d7GzEt9M1a2fbbNqekR1gvZMZ0oramPNX0OkuxZ2pt/8o40q', 'John Pharmacist',      'pharmacist@pharmacareplus.com','+255700000011', 'pharmacist'),
(1,    1,    'cashier',    '$2y$10$EKdZ7d7GzEt9M1a2fbbNqekR1gvZMZ0oramPNX0OkuxZ2pt/8o40q', 'Mary Cashier',         'cashier@pharmacareplus.com', '+255700000012', 'cashier'),
-- Pharmacy 2 staff
(2,    3,    'medilink_admin', '$2y$10$EKdZ7d7GzEt9M1a2fbbNqekR1gvZMZ0oramPNX0OkuxZ2pt/8o40q', 'MediLink Admin',  'admin@medilink.co.tz',       '+255700000020', 'admin');

-- Categories (one set per pharmacy)
INSERT INTO categories (pharmacy_id, name, description) VALUES
(1, 'Antibiotics',  'Drugs that fight bacterial infections'),
(1, 'Painkillers',  'Pain relief medications'),
(1, 'Vitamins',     'Vitamins and supplements'),
(1, 'Antimalarials','Medications for malaria treatment'),
(1, 'First Aid',    'First aid supplies'),
(2, 'Antibiotics',  'Drugs that fight bacterial infections'),
(2, 'Painkillers',  'Pain relief medications');

-- Suppliers
INSERT INTO suppliers (pharmacy_id, name, contact_person, phone, email, address) VALUES
(1, 'MediPlus Distributors',  'James Mwangi', '+255711000001', 'sales@mediplus.co.tz',   'Industrial Area, Dar es Salaam'),
(1, 'PharmaCare Ltd',         'Aisha Said',   '+255711000002', 'info@pharmacare.co.tz',  'Kariakoo, Dar es Salaam'),
(1, 'HealthFirst Suppliers',  'Peter Banda',  '+255711000003', 'orders@healthfirst.co.tz','Mwanza, Tanzania'),
(2, 'Northern Pharma Supply', 'Grace Lema',   '+255711000004', 'info@northernpharma.co.tz','Arusha, Tanzania');

-- Medicines for pharmacy 1
INSERT INTO medicines (pharmacy_id, barcode, name, generic_name, category_id, supplier_id, unit, batch_number, expiry_date, cost_price, selling_price, quantity, reorder_level) VALUES
(1, '8901234500011', 'Amoxicillin 500mg',   'Amoxicillin',              1, 1, 'capsule', 'BATCH-AMX-001', '2026-12-31',  500.00,  800.00,  250, 30),
(1, '8901234500028', 'Paracetamol 500mg',   'Paracetamol',              2, 1, 'tablet',  'BATCH-PCM-001', '2027-06-30',  100.00,  200.00, 1000,100),
(1, '8901234500035', 'Ibuprofen 400mg',     'Ibuprofen',                2, 2, 'tablet',  'BATCH-IBU-001', '2026-09-30',  150.00,  300.00,  500, 50),
(1, '8901234500042', 'Vitamin C 1000mg',    'Ascorbic Acid',            3, 2, 'tablet',  'BATCH-VTC-001', '2027-03-31',  250.00,  500.00,  200, 20),
(1, '8901234500059', 'Coartem 80/480mg',    'Artemether/Lumefantrine',  4, 3, 'tablet',  'BATCH-CRT-001', '2026-08-15', 3500.00, 5000.00,   80, 15),
(1, '8901234500066', 'Bandage Roll',        'Cotton Bandage',           5, 1, 'roll',    'BATCH-BND-001', '2030-01-01',  800.00, 1500.00,   50, 10),
(1, '8901234500073', 'Cough Syrup 100ml',   'Dextromethorphan',         2, 2, 'bottle',  'BATCH-CSY-001', '2026-04-30', 1200.00, 2000.00,    5, 15),
(1, '8901234500080', 'Multivitamin',        'Multivitamin Complex',     3, 3, 'tablet',  'BATCH-MVT-001', '2027-11-30',  300.00,  700.00,  150, 20);

-- Medicines for pharmacy 2 (small starter set)
INSERT INTO medicines (pharmacy_id, barcode, name, generic_name, category_id, supplier_id, unit, batch_number, expiry_date, cost_price, selling_price, quantity, reorder_level) VALUES
(2, '8901234500011', 'Amoxicillin 500mg',   'Amoxicillin',              6, 4, 'capsule', 'ML-AMX-001', '2026-12-31',  520.00,  850.00, 100, 25),
(2, '8901234500028', 'Paracetamol 500mg',   'Paracetamol',              7, 4, 'tablet',  'ML-PCM-001', '2027-06-30',  110.00,  220.00, 600, 80);

-- Per-pharmacy settings
INSERT INTO settings (pharmacy_id, setting_key, setting_value) VALUES
(1, 'pharmacy_name',     'PharmaCare Plus'),
(1, 'pharmacy_address',  '123 Health Street, Dar es Salaam, Tanzania'),
(1, 'pharmacy_phone',    '+255 700 123 456'),
(1, 'pharmacy_email',    'info@pharmacareplus.com'),
(1, 'currency',          'TZS'),
(1, 'default_tax_rate',  '18'),
(2, 'pharmacy_name',     'MediLink Pharmacy'),
(2, 'pharmacy_address',  '88 Uhuru Road, Arusha, Tanzania'),
(2, 'pharmacy_phone',    '+255 700 222 333'),
(2, 'pharmacy_email',    'hello@medilink.co.tz'),
(2, 'currency',          'TZS'),
(2, 'default_tax_rate',  '18'),
-- Global fallback defaults (used when no pharmacy override)
(NULL, 'currency',         'TZS'),
(NULL, 'default_tax_rate', '18');
