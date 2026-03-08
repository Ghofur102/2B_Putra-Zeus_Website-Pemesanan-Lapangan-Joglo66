-- =====================================================
-- SCRIPT SETUP USER DATABASE JOGLO66
-- Jalankan script ini menggunakan user ROOT
-- =====================================================

-- 1. Buat User Khusus Migrasi (DDL)
CREATE USER 'joglo66_migration'@'localhost' IDENTIFIED BY 'putrazeus';
GRANT CREATE, ALTER, DROP, INDEX, REFERENCES, SELECT ON db_joglo66.* TO 'joglo66_migration'@'localhost';

-- 2. Buat User Khusus Aplikasi (DML)
CREATE USER 'joglo66_app_user'@'localhost' IDENTIFIED BY 'putrazeus';
GRANT SELECT, INSERT, UPDATE, DELETE ON db_joglo66.* TO 'joglo66_app_user'@'localhost';

-- 3. Terapkan Perubahan
FLUSH PRIVILEGES;
