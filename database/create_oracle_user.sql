-- ============================================================
-- DBS401 - Group 02
-- database/create_oracle_user.sql
-- Chạy với SYS/SYSTEM để tạo Oracle user cho lab
-- ============================================================
-- sqlplus sys/YOUR_PASSWORD@localhost:1539/XEPDB1 as sysdba @create_oracle_user.sql
-- ============================================================

PROMPT Creating DBS401 lab user...

-- Xóa user cũ nếu có (tùy chọn)
-- DROP USER dbs401_user CASCADE;

-- Tạo user
CREATE USER dbs401_user IDENTIFIED BY dbs401_pass
    DEFAULT TABLESPACE USERS
    TEMPORARY TABLESPACE TEMP
    ACCOUNT UNLOCK;

-- Gán quyền cần thiết
GRANT CREATE SESSION         TO dbs401_user;
GRANT CONNECT                TO dbs401_user;
GRANT RESOURCE               TO dbs401_user;
GRANT CREATE TABLE           TO dbs401_user;
GRANT CREATE SEQUENCE        TO dbs401_user;
GRANT CREATE VIEW            TO dbs401_user;
GRANT CREATE PROCEDURE       TO dbs401_user;
GRANT CREATE TRIGGER         TO dbs401_user;
GRANT UNLIMITED TABLESPACE   TO dbs401_user;

-- Quota trên tablespace
ALTER USER dbs401_user QUOTA UNLIMITED ON USERS;

-- Xác nhận
SELECT username, account_status, created
FROM DBA_USERS
WHERE username = 'DBS401_USER';

PROMPT User dbs401_user created successfully.
PROMPT Next steps:
PROMPT   sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/schema.sql
PROMPT   sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/seed.sql
PROMPT   sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/fix_refs.sql
PROMPT   php database/init_passwords.php

COMMIT;
EXIT;
