-- ============================================================
-- OracleSecLab - Vulnerable Oracle Web Application
-- database/create_oracle_user.sql
-- Run as SYS/SYSTEM to provision the Oracle lab database user
-- ============================================================
-- Usage:
--   sqlplus sys/YOUR_PASSWORD@localhost:1539/XEPDB1 as sysdba @create_oracle_user.sql
-- ============================================================

PROMPT Creating OracleSecLab application database user...

-- Drop existing user if rebuilding from scratch (optional)
-- DROP USER dbs401_user CASCADE;

-- Create application user
CREATE USER dbs401_user IDENTIFIED BY dbs401_pass
    DEFAULT TABLESPACE USERS
    TEMPORARY TABLESPACE TEMP
    ACCOUNT UNLOCK;

-- Grant required application privileges
GRANT CREATE SESSION         TO dbs401_user;
GRANT CONNECT                TO dbs401_user;
GRANT RESOURCE               TO dbs401_user;
GRANT CREATE TABLE           TO dbs401_user;
GRANT CREATE SEQUENCE        TO dbs401_user;
GRANT CREATE VIEW            TO dbs401_user;
GRANT CREATE PROCEDURE       TO dbs401_user;
GRANT CREATE TRIGGER         TO dbs401_user;
GRANT UNLIMITED TABLESPACE   TO dbs401_user;

-- Set tablespace quota
ALTER USER dbs401_user QUOTA UNLIMITED ON USERS;

-- Confirm user status
SELECT username, account_status, created
FROM DBA_USERS
WHERE username = 'DBS401_USER';

PROMPT User dbs401_user created successfully.
PROMPT Next execution steps:
PROMPT   sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/schema.sql
PROMPT   sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/seed.sql
PROMPT   sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/fix_refs.sql
PROMPT   php database/init_passwords.php

COMMIT;
EXIT;
