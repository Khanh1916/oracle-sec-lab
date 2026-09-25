-- ============================================================
-- OracleSecLab - Vulnerable Oracle Web Application
-- database/reset_db.sql
-- Reset all database tables to initial baseline state
-- ============================================================
-- ⚠️  WARNING: This truncates all tables and purges application data!
-- Usage:
--   sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/reset_db.sql
-- ============================================================

PROMPT ================================================
PROMPT  OracleSecLab - Database Reset Routine
PROMPT  WARNING: This will TRUNCATE and DELETE all lab data!
PROMPT ================================================

-- Defer constraint verification for clean truncation
ALTER SESSION SET CONSTRAINT_CHECK_TIME = DEFERRED;

-- Truncate tables in dependency order (children first, parents last)
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

PROMPT Tables successfully cleared.
PROMPT Next steps:
PROMPT   sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/seed.sql
PROMPT   sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/fix_refs.sql
PROMPT   php database/init_passwords.php

COMMIT;
/
