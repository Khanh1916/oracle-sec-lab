-- ============================================================
-- DBS401 - Group 02
-- database/fix_refs.sql
-- ============================================================
-- Chạy SAU seed.sql để cập nhật admin_ref_id trong ENROLLMENTS
-- trỏ đúng vào log_id của bản ghi TRANSCRIPT_EXPORT_HIDDEN.
-- Lý do: log_id là IDENTITY (auto) nên không thể hardcode trong seed.sql.
-- ============================================================
-- Chạy:
--   sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/fix_refs.sql
-- ============================================================

-- Bước 1: Xem log_id thực tế
SELECT log_id, action, metadata_note
FROM AUDIT_LOGS
WHERE action = 'TRANSCRIPT_EXPORT_HIDDEN';

-- Bước 2: Cập nhật admin_ref_id trong ENROLLMENTS
-- Trỏ đến log_id thực tế của TRANSCRIPT_EXPORT_HIDDEN
UPDATE ENROLLMENTS
SET admin_ref_id = (
    SELECT log_id FROM AUDIT_LOGS
    WHERE action = 'TRANSCRIPT_EXPORT_HIDDEN'
    AND ROWNUM = 1
)
WHERE transcript_ref = 'TXN-099-2024-S1';

-- Bước 3: Xác nhận
SELECT e.enrollment_id,
       e.transcript_ref,
       e.admin_ref_id,
       a.log_id,
       a.action
FROM   ENROLLMENTS e
JOIN   AUDIT_LOGS  a ON e.admin_ref_id = a.log_id
WHERE  e.transcript_ref = 'TXN-099-2024-S1';

COMMIT;
/

-- ============================================================
-- Kiểm tra toàn bộ dữ liệu CTF
-- ============================================================
PROMPT ===== FLAGS TABLE =====
SELECT flag_id, flag_code, SUBSTR(flag_part,1,30) AS flag_part_preview,
       part_order, difficulty, is_active
FROM FLAGS ORDER BY flag_id;

PROMPT ===== ADMIN_SECRETS TABLE =====
SELECT secret_id, secret_key, SUBSTR(encrypted_value,1,25) AS value_preview,
       is_active
FROM ADMIN_SECRETS ORDER BY secret_id;

PROMPT ===== CONFIG_STORE (private only) =====
SELECT config_key, SUBSTR(config_value,1,40) AS value_preview, is_public
FROM CONFIG_STORE WHERE is_public = 0;

PROMPT ===== AUDIT_LOGS (system only) =====
SELECT log_id, action, SUBSTR(metadata_note,1,60) AS meta_preview
FROM AUDIT_LOGS
WHERE action IN ('SYSTEM_AUDIT_CHECK','TRANSCRIPT_EXPORT_HIDDEN','SECRET_VAULT_ACCESS');

PROMPT ===== ENROLLMENTS (hidden student) =====
SELECT enrollment_id, student_id, transcript_ref,
       SUBSTR(internal_note,1,50) AS note_preview,
       admin_ref_id
FROM ENROLLMENTS WHERE transcript_ref = 'TXN-099-2024-S1';

PROMPT ===== STUDENTS (hidden marker check) =====
SELECT student_id, full_name, hidden_marker FROM STUDENTS ORDER BY student_id;
