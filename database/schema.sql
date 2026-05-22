-- ============================================================
-- DBS401 - Strengthening Database Security with Oracle Database
-- Group 07 - Schema Definition
-- Oracle Database XE 21c / Oracle Database 23c Free
-- ============================================================
-- Run as: sqlplus dbs401_user/dbs401_pass@XE @schema.sql
-- ============================================================

-- ============================================================
-- 1. USERS TABLE
-- ============================================================
CREATE TABLE USERS (
    user_id       NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    username      VARCHAR2(64)  NOT NULL UNIQUE,
    password_hash VARCHAR2(256) NOT NULL,
    role          VARCHAR2(20)  DEFAULT 'student'
                  CONSTRAINT chk_role CHECK (role IN ('student','teacher','admin')),
    status        VARCHAR2(20)  DEFAULT 'active'
                  CONSTRAINT chk_status CHECK (status IN ('active','inactive','locked')),
    created_at    TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- ============================================================
-- 2. STUDENTS TABLE
-- ============================================================
CREATE TABLE STUDENTS (
    student_id    NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id       NUMBER REFERENCES USERS(user_id),
    full_name     VARCHAR2(128) NOT NULL,
    email         VARCHAR2(128),
    major         VARCHAR2(64),
    gpa           NUMBER(4,2),
    phone         VARCHAR2(20),
    address       VARCHAR2(256),
    hidden_marker VARCHAR2(64) DEFAULT 'NORMAL',
    created_at    TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- ============================================================
-- 3. COURSES TABLE
-- ============================================================
CREATE TABLE COURSES (
    course_id     NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    course_code   VARCHAR2(16) NOT NULL UNIQUE,
    course_name   VARCHAR2(128) NOT NULL,
    teacher_name  VARCHAR2(128),
    credits       NUMBER(2) DEFAULT 3,
    semester      VARCHAR2(16)
);

-- ============================================================
-- 4. ENROLLMENTS TABLE
-- ============================================================
CREATE TABLE ENROLLMENTS (
    enrollment_id  NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    student_id     NUMBER REFERENCES STUDENTS(student_id),
    course_id      NUMBER REFERENCES COURSES(course_id),
    score          NUMBER(5,2),
    semester       VARCHAR2(16),
    transcript_ref VARCHAR2(64) NOT NULL UNIQUE,
    internal_note  VARCHAR2(512),
    admin_ref_id   NUMBER DEFAULT NULL
);

-- ============================================================
-- 5. AUDIT_LOGS TABLE
-- ============================================================
CREATE TABLE AUDIT_LOGS (
    log_id        NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id       NUMBER,
    action        VARCHAR2(128),
    ip_address    VARCHAR2(45),
    user_agent    VARCHAR2(256),
    created_at    TIMESTAMP DEFAULT SYSTIMESTAMP,
    metadata_note VARCHAR2(1024)
);

-- ============================================================
-- 6. FLAGS TABLE  (Intentionally fragmented - no direct flag here)
-- ============================================================
CREATE TABLE FLAGS (
    flag_id       NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    flag_code     VARCHAR2(64) NOT NULL,
    flag_part     VARCHAR2(512),
    part_order    NUMBER(2),
    hint          VARCHAR2(256),
    difficulty    VARCHAR2(32),
    is_active     NUMBER(1) DEFAULT 1,
    created_at    TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- ============================================================
-- 7. ADMIN_SECRETS TABLE
-- ============================================================
CREATE TABLE ADMIN_SECRETS (
    secret_id       NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    secret_key      VARCHAR2(128) NOT NULL UNIQUE,
    encrypted_value VARCHAR2(512),
    note            VARCHAR2(256),
    is_active       NUMBER(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- ============================================================
-- 8. CONFIG_STORE TABLE
-- ============================================================
CREATE TABLE CONFIG_STORE (
    config_key    VARCHAR2(128) PRIMARY KEY,
    config_value  VARCHAR2(512),
    is_public     NUMBER(1) DEFAULT 0,
    updated_at    TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- ============================================================
-- 9. FAKE_FLAGS TABLE  (Decoys)
-- ============================================================
CREATE TABLE FAKE_FLAGS (
    fake_id       NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    fake_code     VARCHAR2(64),
    fake_value    VARCHAR2(256),
    reason        VARCHAR2(128)
);

-- ============================================================
-- 10. SYSTEM_HINTS TABLE
-- ============================================================
CREATE TABLE SYSTEM_HINTS (
    hint_id       NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    hint_key      VARCHAR2(128) NOT NULL UNIQUE,
    hint_value    VARCHAR2(512),
    related_vuln  VARCHAR2(32)
);

-- ============================================================
-- Decoy table to confuse attackers (looks like flags table)
-- ============================================================
CREATE TABLE FLAG_ARCHIVE (
    archive_id    NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    archive_code  VARCHAR2(64),
    archive_data  VARCHAR2(256),
    archived_at   TIMESTAMP DEFAULT SYSTIMESTAMP
);

-- ============================================================
-- Grant permissions
-- ============================================================
GRANT SELECT, INSERT, UPDATE ON USERS          TO PUBLIC;
GRANT SELECT, INSERT, UPDATE ON STUDENTS       TO PUBLIC;
GRANT SELECT, INSERT, UPDATE ON COURSES        TO PUBLIC;
GRANT SELECT, INSERT, UPDATE ON ENROLLMENTS    TO PUBLIC;
GRANT SELECT, INSERT        ON AUDIT_LOGS      TO PUBLIC;
GRANT SELECT                ON FLAGS           TO PUBLIC;
GRANT SELECT                ON ADMIN_SECRETS   TO PUBLIC;
GRANT SELECT                ON CONFIG_STORE    TO PUBLIC;
GRANT SELECT                ON FAKE_FLAGS      TO PUBLIC;
GRANT SELECT                ON SYSTEM_HINTS    TO PUBLIC;
GRANT SELECT                ON FLAG_ARCHIVE    TO PUBLIC;

COMMIT;
/
