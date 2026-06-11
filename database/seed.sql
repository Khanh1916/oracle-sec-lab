-- ============================================================
-- DBS401 - Group 02 - Seed Data
-- ============================================================
-- FLAG DESIGN (INTERNAL - DO NOT INCLUDE IN PUBLIC REPORT):
--
-- FLAG 1: DBS401{SQL_1nj3ct10n_0r4cl3!}
--   Part A (FLAGS, hex)  : 4442533430317B53514C5F  = hex("DBS401{SQL_")
--   Part B (AUDIT_LOGS)  : _n01tc3jn1              = reverse("1nj3ct10n_")
--   Part C (CONFIG_STORE): MHI0Y2wzIX0=            = base64("0r4cl3!}")
-- FLAG 2: DBS401{LOGIC_GURU_2024}
-- FLAG 3: DBS401{5upp1y_Ch41n_P0150n1ng_0912}
-- ============================================================

-- ============================================================
-- USERS
-- Passwords stored as SHA-256 hex (for demo; real: bcrypt)
-- admin     : Admin@DBS401!2024   -> use PHP password_hash
-- teacher1  : Teacher@123
-- student1  : Student@123
-- student2  : Student@123
-- student3  : Student@123
-- ============================================================
-- NOTE: In PHP we use password_hash/password_verify.
--       For SQLPlus seeding, store placeholder; PHP login still works.

INSERT INTO USERS (username, password_hash, role, status) VALUES
  ('admin',    '$2y$12$xxxxxxxxxxxxxxxxxxxxxxxxxxx.AdminHashPlaceholder',    'admin',   'active');
INSERT INTO USERS (username, password_hash, role, status) VALUES
  ('teacher1', '$2y$12$xxxxxxxxxxxxxxxxxxxxxxxxxxx.TeacherHashPlaceholder',  'teacher', 'active');
INSERT INTO USERS (username, password_hash, role, status) VALUES
  ('student1', '$2y$12$xxxxxxxxxxxxxxxxxxxxxxxxxxx.Student1HashPlaceholder', 'student', 'active');
INSERT INTO USERS (username, password_hash, role, status) VALUES
  ('student2', '$2y$12$xxxxxxxxxxxxxxxxxxxxxxxxxxx.Student2HashPlaceholder', 'student', 'active');
INSERT INTO USERS (username, password_hash, role, status) VALUES
  ('student3', '$2y$12$xxxxxxxxxxxxxxxxxxxxxxxxxxx.Student3HashPlaceholder', 'student', 'active');

-- ============================================================
-- STUDENTS (normal)
-- student_id sequences may vary; using INSERT with explicit mapping
-- ============================================================
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (3, 'Nguyen Van An',   'an.nv2021@fpt.edu.vn',   'Software Engineering', 3.20, '0901234567', 'Ha Noi', 'NORMAL', 150);
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (4, 'Tran Thi Binh',   'binh.tt2021@fpt.edu.vn', 'Information Security',  3.55, '0912345678', 'Ho Chi Minh', 'NORMAL', 50);
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (5, 'Le Quoc Cuong',   'cuong.lq2021@fpt.edu.vn','Artificial Intelligence',2.90, '0923456789', 'Da Nang', 'NORMAL', 200);
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (6, 'Hoang Minh Duc',  'duc.hm2022@fpt.edu.vn',  'Digital Marketing', 3.10, '0933112233', 'Ha Noi', 'NORMAL', 100);
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (7, 'Vu Thuy Linh',    'linh.vt2022@fpt.edu.vn', 'Software Engineering', 3.85, '0944556677', 'Hai Phong', 'NORMAL', 300);
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (8, 'Dang Minh Tuan',  'tuan.dm2021@fpt.edu.vn', 'Information Security', 2.75, '0988776655', 'Binh Duong', 'NORMAL', 120);
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (9, 'Phan Khanh Vy',   'vy.pk2023@fpt.edu.vn',   'Business Management', 3.40, '0977112233', 'Vung Tau', 'NORMAL', 80);

-- Decoy student (fake flag trap)
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker) VALUES
  (NULL, 'Pham Thi Dung', 'dung.pt2021@fpt.edu.vn', 'Business IT', 2.50, '0934567890', 'Can Tho', 'DECOY_42');

-- HIDDEN student - FLAG 2 target (no web login, hidden from normal listing)
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker) VALUES
  (NULL, 'CTF Hidden Target', 'hidden.ctf@internal.fpt', 'CLASSIFIED', 0.00, 'N/A', 'CLASSIFIED', 'HIDDEN_CTF_TARGET_99');

-- ============================================================
-- COURSES
-- ============================================================
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('DBS401', 'Database Security', 'Dr. Nguyen Minh Tuan', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('PRJ401', 'Software Project', 'MSc. Le Van Hung', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('SWD392', 'Software Architecture', 'Dr. Tran Bich Van', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('NET201', 'Network Security', 'MSc. Pham Quoc Bao', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('MLN122', 'Philosophy', 'Dr. Nguyen Van Tri', 2, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('MLN122', 'Philosophy', 'Dr. Nguyen Van Tri', 2, '2024-S1');

-- ============================================================
-- ENROLLMENTS
-- transcript_ref pattern: TXN-{student_id:03d}-{year}-S{sem}
-- ============================================================
-- Student 1 (student_id from sequence = 1)
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  (1, 1, 8.5, '2024-S1', 'TXN-001-2024-S1', 'Normal enrollment. No issues.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  (1, 2, 7.8, '2024-S1', 'TXN-001-2024-S2', 'Normal enrollment.', NULL);

-- Student 2
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  (2, 1, 9.0, '2024-S1', 'TXN-002-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  (2, 3, 8.2, '2024-S1', 'TXN-002-2024-S3', 'Normal enrollment.', NULL);

-- Student 3
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  (3, 4, 7.5, '2024-S1', 'TXN-003-2024-S1', 'Normal enrollment.', NULL);

-- Decoy student 4 (student_id=4) - has FAKE flag in internal_note
-- base64("DBS401{FAKE_IDOR_wrong_student}") => store as bait
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  (4, 2, 5.0, '2024-S1', 'TXN-004-2024-S1',
   'SYSTEM_REF: UkVKVE5EQXh4RFNYY2ZTS1JmVkhJMGJuTmZydzBsRklYMD0=',
   NULL);

-- HIDDEN student 5 (student_id=5 in sequence) - REAL FLAG 2 target
-- internal_note = base64("DBS401{1DOR_Tr4ns_")  => REJTNDAxezFET1JfVHI0bnNf
-- admin_ref_id = NULL initially → fix_refs.sql sẽ cập nhật đúng log_id thực tế
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  (5, 1, 0, '2024-S1', 'TXN-099-2024-S1',
   'CLASSIFIED_DATA: REJTNDAxezFET1JfVHI0bnNf',
   NULL);

-- ============================================================
-- AUDIT_LOGS (Chứa Flag 1 Part B - Reverse)
-- ============================================================
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, metadata_note) VALUES
  (1, 'SYSTEM_AUDIT_CHECK', '127.0.0.1', '{"sys_version":"v2.1","fragment":"_n01tc3jn1","note":"reverse_for_context","ref":"FLAG_COMPONENT_B"}');
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, metadata_note) VALUES
  (1, 'LOGIN_SUCCESS', '192.168.1.50', 'Admin session started');

-- ============================================================
-- FLAGS (Chứa Flag 1 Part A - Hex)
-- ============================================================
INSERT INTO FLAGS (flag_code, flag_part, part_order, hint, difficulty, is_active) VALUES
  ('FL1_PART_A', '4442533430317B53514C5F', 1, 'Look into hex values', 'Easy', 1);
INSERT INTO FLAGS (flag_code, flag_part, part_order, hint, difficulty, is_active) VALUES
  ('FL_DECOY_B', 'U09NRV9GQUtFX0ZMQUdfSEVSRQ==', 1, 'Is it base64?', 'Medium', 1);

-- ============================================================
-- CONFIG_STORE (Chứa Flag 1 Part C - Base64 và Vuln 3 URL)
-- ============================================================
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('sys_alpha_marker', 'MHI0Y2wzIX0=', 0);
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('update_url', 'http://127.0.0.1:8081/manifest.json', 0);
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('app_version', '3.1.0-ENTERPRISE', 1);
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('site_maintenance', 'false', 1);

-- ============================================================
-- ADMIN_SECRETS (Legacy Vuln 3 - Blind SQLi target)
-- ============================================================
INSERT INTO ADMIN_SECRETS (secret_key, encrypted_value, note, is_active) VALUES
  ('oracle_flag_3_primary', 'DBS401{Bl1nd_B00l_REDACTED}', 'Primary production key', 1);
INSERT INTO ADMIN_SECRETS (secret_key, encrypted_value, note, is_active) VALUES
  ('oracle_flag_3_backup', 'DBS401{FAKE_blind_wrong_key_xd}', 'Legacy backup key', 1);

-- ============================================================
-- FAKE_FLAGS (Decoys để gây nhiễu)
-- ============================================================
INSERT INTO FAKE_FLAGS (fake_code, fake_value, reason) VALUES
  ('FF001', 'DBS401{FAKE_union_select_lol}', 'SQLi Trap');
INSERT INTO FAKE_FLAGS (fake_code, fake_value, reason) VALUES
  ('FF002', 'DBS401{FAKE_IDOR_notreal}', 'IDOR Trap');

-- ============================================================
-- SYSTEM_HINTS (Gợi ý gián tiếp)
-- ============================================================
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('HINT_SQLI_01', 'Flags are not in one piece. Check Audit Logs and Config Store too.', 'VULN1');
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('HINT_SUPPLY_03', 'Admin update depends on the update_url in config.', 'VULN3');

-- ============================================================
-- FLAG_ARCHIVE (Decoy Table)
-- ============================================================
INSERT INTO FLAG_ARCHIVE (archive_code, archive_data) VALUES
  ('OLD_FLAG_2023', 'DBS401{FAKE_archived_flag_123}');

COMMIT;
-- Lưu ý: Sau khi nạp, hãy chạy file fix_refs.sql để đồng bộ admin_ref_id
-- và init_passwords.php để băm mật khẩu thực tế.
