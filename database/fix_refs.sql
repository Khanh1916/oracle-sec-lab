-- ============================================================
-- DBS401 - Group 02
-- database/fix_refs.sql
-- ============================================================
-- Chạy SAU seed.sql để:
--   1. Cập nhật admin_ref_id trong ENROLLMENTS trỏ đúng vào
--      log_id của bản ghi SYSTEM_AUDIT_CHECK (Flag 1 Part B hint).
--   2. Xác minh toàn bộ dữ liệu CTF.
-- ============================================================
-- Chạy:
--   sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/fix_refs.sql
-- ============================================================

PROMPT === fix_refs.sql: Verifying AUDIT_LOGS ===

SELECT log_id, action, SUBSTR(metadata_note, 1, 60) AS meta_preview
FROM AUDIT_LOGS
WHERE action IN ('SYSTEM_AUDIT_CHECK', 'SECRET_VAULT_ACCESS');

-- Không cần cập nhật admin_ref_id cho transcript nữa (kịch bản IDOR cũ đã bị bỏ).
-- Enrollment TXN-004-2024-S1 (decoy) giữ admin_ref_id = NULL là đúng.

PROMPT === Verifying FLAGS table ===
SELECT flag_id, flag_code, SUBSTR(flag_part, 1, 30) AS flag_part_preview, is_active
FROM FLAGS ORDER BY flag_id;

PROMPT === Verifying ADMIN_SECRETS table ===
SELECT secret_id, secret_key, is_active, note
FROM ADMIN_SECRETS ORDER BY secret_id;

PROMPT === Verifying CONFIG_STORE (private keys) ===
SELECT config_key, SUBSTR(config_value, 1, 40) AS value_preview, is_public
FROM CONFIG_STORE WHERE is_public = 0;

PROMPT === Verifying AUDIT_LOGS (CTF entries) ===
SELECT log_id, action, SUBSTR(metadata_note, 1, 70) AS meta_preview
FROM AUDIT_LOGS
WHERE action IN ('SYSTEM_AUDIT_CHECK', 'LOGIN_SUCCESS', 'SECRET_VAULT_ACCESS');

PROMPT === Verifying STUDENTS ===
SELECT student_id, full_name, hidden_marker, credits
FROM STUDENTS ORDER BY student_id;

PROMPT === Verifying ENROLLMENTS ===
SELECT enrollment_id, student_id, transcript_ref,
       SUBSTR(internal_note, 1, 50) AS note_preview,
       admin_ref_id
FROM ENROLLMENTS ORDER BY enrollment_id;

PROMPT === Verifying COURSES (check no duplicate course_code) ===
SELECT course_id, course_code, course_name FROM COURSES ORDER BY course_id;

PROMPT === fix_refs.sql DONE ===
PROMPT Next step: php database/init_passwords.php

COMMIT;
/