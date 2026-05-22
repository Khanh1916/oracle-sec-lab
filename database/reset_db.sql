-- ============================================================
-- DBS401 - Group 07
-- database/reset_db.sql
-- Reset toàn bộ database về trạng thái ban đầu cho demo
-- ============================================================
-- ⚠️  Xóa toàn bộ dữ liệu! Chỉ chạy khi cần reset.
-- Chạy: sqlplus dbs401_user/dbs401_pass@localhost:1521/XE @database/reset_db.sql
-- ============================================================

PROMPT ================================================
PROMPT  DBS401 - Group 07 - Database Reset
PROMPT  WARNING: This will DELETE all data!
PROMPT ================================================

-- Disable constraints tạm thời
ALTER SESSION SET CONSTRAINT_CHECK_TIME = DEFERRED;

-- Xóa theo thứ tự (child trước, parent sau)
TRUNCATE TABLE FLAG_ARCHIVE;
TRUNCATE TABLE SYSTEM_HINTS;
TRUNCATE TABLE FAKE_FLAGS;
TRUNCATE TABLE CONFIG_STORE;
TRUNCATE TABLE ADMIN_SECRETS;
TRUNCATE TABLE FLAGS;
TRUNCATE TABLE AUDIT_LOGS;
TRUNCATE TABLE ENROLLMENTS;
TRUNCATE TABLE STUDENTS;
TRUNCATE TABLE COURSES;
TRUNCATE TABLE USERS;

PROMPT Tables cleared. Reimporting seed data...

-- Re-import (phải chạy từ thư mục project)
-- @database/seed.sql

PROMPT Done. Now run:
PROMPT   sqlplus dbs401_user/dbs401_pass@localhost:1521/XE @database/seed.sql
PROMPT   sqlplus dbs401_user/dbs401_pass@localhost:1521/XE @database/fix_refs.sql
PROMPT   php database/init_passwords.php

COMMIT;
/
