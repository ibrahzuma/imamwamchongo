-- ============================================================
-- PASSWORD FIX PATCH
-- Run this if you already imported pharmacy_db.sql with the old (broken) hashes.
-- This updates the demo accounts to use the password "password123".
-- ============================================================

USE pharmacy_db;

UPDATE users SET password = '$2y$10$EKdZ7d7GzEt9M1a2fbbNqekR1gvZMZ0oramPNX0OkuxZ2pt/8o40q'
WHERE username IN ('admin', 'pharmacist', 'cashier');

-- Verify:
SELECT id, username, role, is_active FROM users;
