-- ============================================================
-- OracleSecLab - Vulnerable Oracle Web Application
-- Database Schema Definition (Oracle XE 21c / 23c Free)
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
    user_id       NUMBER REFERENCES USERS(user_id) ON DELETE CASCADE,
    full_name     VARCHAR2(128) NOT NULL,
    email         VARCHAR2(128),
    major         VARCHAR2(64),
    gpa           NUMBER(4,2),
    phone         VARCHAR2(20),
    address       VARCHAR2(256),
    hidden_marker VARCHAR2(64) DEFAULT 'NORMAL',
    credits       NUMBER(10)   DEFAULT 0,
    campus        VARCHAR2(8)  DEFAULT 'HN',   -- fixed home campus: HN|DN|CT|HCM|QN
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
    student_id     NUMBER REFERENCES STUDENTS(student_id) ON DELETE CASCADE,
    course_id      NUMBER REFERENCES COURSES(course_id) ON DELETE CASCADE,
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
-- PLSQL_VAULT  (Challenge 4 - PL/SQL injection target)
-- Holds the Flag 4 secret. No web endpoint queries this table directly;
-- it is only reachable by injecting into the FN_COURSE_LOOKUP function's
-- dynamic SQL. Intentionally NOT granted to PUBLIC.
-- ============================================================
CREATE TABLE PLSQL_VAULT (
    vault_id    NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    label       VARCHAR2(64),
    secret_flag VARCHAR2(256)
);

-- ============================================================
-- NOTIFICATIONS  (portal announcements + per-user read state)
-- Cosmetic portal feature - NOT part of any challenge, holds no flags.
-- ============================================================
CREATE TABLE NOTIFICATIONS (
    notification_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    category        VARCHAR2(20)  DEFAULT 'notice',
    title           VARCHAR2(200) NOT NULL,
    body            VARCHAR2(1000),
    audience        VARCHAR2(20)  DEFAULT 'all',   -- all | student | teacher | admin
    created_at      TIMESTAMP DEFAULT SYSTIMESTAMP
);

CREATE TABLE NOTIFICATION_READS (
    user_id         NUMBER NOT NULL,
    notification_id NUMBER NOT NULL,
    read_at         TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT pk_notif_read PRIMARY KEY (user_id, notification_id)
);

-- ============================================================
-- EXAMS  (examination timetable per course)
-- Cosmetic portal feature - NOT part of any challenge, holds no flags.
-- ============================================================
CREATE TABLE EXAMS (
    exam_id      NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    course_id    NUMBER REFERENCES COURSES(course_id) ON DELETE CASCADE,
    exam_type    VARCHAR2(40)  DEFAULT 'Final Exam',   -- Progress Test | Practical Exam | Final Exam
    exam_date    DATE,
    start_time   VARCHAR2(10),                          -- 'HH24:MI' display string
    duration_min NUMBER(4)     DEFAULT 90,
    room         VARCHAR2(20),
    exam_form    VARCHAR2(30)  DEFAULT 'On paper'       -- On paper | On computer
);

-- ============================================================
-- ATTENDANCE  (per-student, per-session class attendance)
-- Cosmetic portal feature - NOT part of any challenge, holds no flags.
-- ============================================================
CREATE TABLE ATTENDANCE (
    attendance_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    student_id    NUMBER REFERENCES STUDENTS(student_id) ON DELETE CASCADE,
    course_id     NUMBER REFERENCES COURSES(course_id)   ON DELETE CASCADE,
    session_date  DATE,
    slot          VARCHAR2(16),
    status        VARCHAR2(10) DEFAULT 'present'          -- present | late | absent
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

-- ============================================================
-- FN_COURSE_LOOKUP  (Challenge 4 - vulnerable stored function)
-- ============================================================
-- Looks up a course name by code. VULNERABLE: it builds the query by string
-- concatenation and runs it with EXECUTE IMMEDIATE (definer's rights), so a
-- crafted course code injects into the PL/SQL dynamic SQL. Note that callers
-- using a bind variable for the argument are NOT protected - the injection
-- happens inside this function, not at the call site.
CREATE OR REPLACE FUNCTION FN_COURSE_LOOKUP(p_code IN VARCHAR2) RETURN VARCHAR2 AS
    v_result VARCHAR2(4000);
    v_sql    VARCHAR2(4000);
BEGIN
    v_sql := 'SELECT course_name FROM COURSES WHERE course_code = ''' || p_code || '''';
    EXECUTE IMMEDIATE v_sql INTO v_result;
    RETURN v_result;
EXCEPTION
    WHEN NO_DATA_FOUND  THEN RETURN 'No course found for that code.';
    WHEN TOO_MANY_ROWS  THEN RETURN 'Ambiguous result (more than one row).';
    WHEN OTHERS         THEN RETURN 'Lookup error.';
END;
/
