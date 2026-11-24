-- KOMIKIN Phase 9: add Employee role
-- Backup the database before applying. Run once in phpMyAdmin.
ALTER TABLE users
    MODIFY COLUMN role ENUM('admin', 'employee', 'customer') NOT NULL DEFAULT 'customer';

-- Existing users retain their current role. No user data or rental records are changed.
