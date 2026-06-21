-- xoa cac du lieu cu, tao moi tu dau de seed lai database
DELETE FROM FLAG_ARCHIVE;
DELETE FROM SYSTEM_HINTS;
DELETE FROM FAKE_FLAGS;
DELETE FROM CONFIG_STORE;
DELETE FROM ADMIN_SECRETS;
DELETE FROM FLAGS;
DELETE FROM AUDIT_LOGS;
DELETE FROM ENROLLMENTS;
DELETE FROM STUDENTS;
DELETE FROM COURSES;
DELETE FROM USERS;

-- ============================================================
-- DBS401 - Group 02 - Seed Data
-- ============================================================
-- FLAG DESIGN:
--
-- FLAG 1: DBS401{SQL_1nj3ct10n_0r4cl3!}
--   Part A (FLAGS, hex)  : 4442533430317B53514C5F  = hex("DBS401{SQL_")
--   Part B (AUDIT_LOGS)  : _n01tc3jn1              = reverse("1nj3ct10n_")
--   Part C (CONFIG_STORE): MHI0Y2wzIX0=            = base64("0r4cl3!}")
--
-- FLAG 2: DBS401{LOGIC_GURU_2024}
--   Lấy từ store.php bằng cách thao túng Credits (negative quantity)
--
-- FLAG 3: DBS401{5upp1y_Ch41n_P0150n1ng_0912}
--   Lấy từ admin.php bằng cách đầu độc update_url trong CONFIG_STORE
--   rồi trigger "Check for Partner Updates"
-- ============================================================

-- ============================================================
-- USERS
-- NOTE: password_hash là placeholder, chạy init_passwords.php để tạo bcrypt thật
-- ============================================================
INSERT INTO USERS (username, password_hash, role, status) VALUES
  ('admin',    '$2y$12$placeholder.AdminHash',    'admin',   'active');
INSERT INTO USERS (username, password_hash, role, status) VALUES
  ('teacher1', '$2y$12$placeholder.TeacherHash',  'teacher', 'active');
INSERT INTO USERS (username, password_hash, role, status) VALUES
  ('student1', '$2y$12$placeholder.Student1Hash', 'student', 'active');
INSERT INTO USERS (username, password_hash, role, status) VALUES
  ('student2', '$2y$12$placeholder.Student2Hash', 'student', 'active');
INSERT INTO USERS (username, password_hash, role, status) VALUES
  ('student3', '$2y$12$placeholder.Student3Hash', 'student', 'active');

-- ============================================================
-- STUDENTS (dùng subquery SELECT để tránh hardcode user_id)
-- ============================================================
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) SELECT u.user_id, 'Nguyen Van An', 'an.nv2021@fpt.edu.vn', 'Software Engineering', 3.20, '0901234567', 'Ha Noi', 'NORMAL', 150
FROM USERS u WHERE u.username = 'student1';

INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) SELECT u.user_id, 'Tran Thi Binh', 'binh.tt2021@fpt.edu.vn', 'Information Security', 3.55, '0912345678', 'Ho Chi Minh', 'NORMAL', 50
FROM USERS u WHERE u.username = 'student2';

INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) SELECT u.user_id, 'Le Quoc Cuong', 'cuong.lq2021@fpt.edu.vn', 'Artificial Intelligence', 2.90, '0923456789', 'Da Nang', 'NORMAL', 200
FROM USERS u WHERE u.username = 'student3';

-- Thêm sinh viên demo không có tài khoản đăng nhập
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (NULL, 'Hoang Minh Duc', 'duc.hm2022@fpt.edu.vn', 'Digital Marketing', 3.10, '0933112233', 'Ha Noi', 'NORMAL', 100);
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (NULL, 'Vu Thuy Linh', 'linh.vt2022@fpt.edu.vn', 'Software Engineering', 3.85, '0944556677', 'Hai Phong', 'NORMAL', 300);
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (NULL, 'Dang Minh Tuan', 'tuan.dm2021@fpt.edu.vn', 'Information Security', 2.75, '0988776655', 'Binh Duong', 'NORMAL', 120);
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (NULL, 'Phan Khanh Vy', 'vy.pk2023@fpt.edu.vn', 'Business Management', 3.40, '0977112233', 'Vung Tau', 'NORMAL', 80);
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (NULL, 'Nguyen Thi Binh Yen', 'yen.nt2006@tnue.edu.vn', 'Mathematics Education', 3.00, '0911111113', 'Bac Giang', 'NORMAL', 10000000);
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (NULL, 'Pham Thi Ngoc Anh', 'anh.pt2006@fpoly.edu.vn', 'Classical Music', 3.00, '0911111114', 'Ha Noi', 'NORMAL', 10000000);
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (NULL, 'Bui Khanh Huyen', 'huyen.bk2005@ulis.vnu.edu.vn', 'English Literature', 3.00, '0911111115', 'Nam Dinh', 'NORMAL', 10000000);

-- Decoy student (hidden_marker DECOY → không lộ trong search bình thường)
-- internal_note của enrollment chứa FAKE flag dạng base64 để bẫy IDOR hunter
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker) VALUES
  (NULL, 'Pham Thi Dung', 'dung.pt2021@fpt.edu.vn', 'Business IT', 2.50, '0934567890', 'Can Tho', 'DECOY_42');

-- ============================================================
-- COURSES (đã bỏ dòng duplicate MLN122)
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

-- ============================================================
-- ENROLLMENTS (dùng subquery để tránh hardcode student_id)
-- ============================================================
-- student1
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student1')),
   (SELECT course_id FROM COURSES WHERE course_code='DBS401'),
   8.5, '2024-S1', 'TXN-001-2024-S1', 'Normal enrollment.', NULL);

INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student1')),
   (SELECT course_id FROM COURSES WHERE course_code='PRJ401'),
   7.8, '2024-S1', 'TXN-001-2024-S2', 'Normal enrollment.', NULL);

-- student2
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student2')),
   (SELECT course_id FROM COURSES WHERE course_code='DBS401'),
   9.0, '2024-S1', 'TXN-002-2024-S1', 'Normal enrollment.', NULL);

INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student2')),
   (SELECT course_id FROM COURSES WHERE course_code='SWD392'),
   8.2, '2024-S1', 'TXN-002-2024-S3', 'Normal enrollment.', NULL);

-- student3
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student3')),
   (SELECT course_id FROM COURSES WHERE course_code='NET201'),
   7.5, '2024-S1', 'TXN-003-2024-S1', 'Normal enrollment.', NULL);

-- Decoy student (student Pham Thi Dung) - internal_note chứa FAKE flag dạng base64 để bẫy IDOR hunters
-- base64("DBS401{FAKE_IDOR_wrong_student}") = "REJTNDE...WRONG..."
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Pham Thi Dung'),
   (SELECT course_id FROM COURSES WHERE course_code='PRJ401'),
   5.0, '2024-S1', 'TXN-004-2024-S1',
   'SYSTEM_REF: REJTNDE5e1dST05HX2ZsYWdfaGVyZX0=',
   NULL);

-- ============================================================
-- AUDIT_LOGS (Flag 1 Part B - fragment cần reverse)
-- ============================================================
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, metadata_note) VALUES
  (1, 'SYSTEM_AUDIT_CHECK', '127.0.0.1',
   '{"sys_version":"v2.1","fragment":"_n01tc3jn1","note":"reverse_for_context","ref":"FLAG_COMPONENT_B"}');

INSERT INTO AUDIT_LOGS (user_id, action, ip_address, metadata_note) VALUES
  (1, 'LOGIN_SUCCESS', '192.168.1.50', 'Admin session started');

-- Log decoy (để bẫy người đọc sai log)
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, metadata_note) VALUES
  (NULL, 'SECRET_VAULT_ACCESS', '10.0.0.1',
   '{"note":"routine_backup","status":"ok","data":"REJTNDE5eFNFQ1JFVF9CQUNLVVBfRkxBR30="}');

-- ============================================================
-- FLAGS (Flag 1 Part A - hex encoded)
-- ============================================================
-- Real flag part A: hex("DBS401{SQL_") = 4442533430317B53514C5F
INSERT INTO FLAGS (flag_code, flag_part, part_order, hint, difficulty, is_active) VALUES
  ('FL1_PART_A', '4442533430317B53514C5F', 1, 'Try decoding this as hex bytes', 'Easy', 1);

-- Decoy: trông như flag part nhưng là garbage base64
INSERT INTO FLAGS (flag_code, flag_part, part_order, hint, difficulty, is_active) VALUES
  ('FL_DECOY_B', 'U09NRV9GQUtFX0ZMQUdfSEVSRQ==', 2, 'Is it base64?', 'Medium', 1);

-- ============================================================
-- CONFIG_STORE
-- ============================================================
-- Flag 1 Part C: base64("0r4cl3!}") = MHI0Y2wzIX0=
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('sys_alpha_marker', 'MHI0Y2wzIX0=', 0);

-- Decoy config (tên nghe giống flag nhưng là fake)
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('sys_beta_marker', 'REJTNDE5eEZBS0VfQ09ORklHX01BUktFUn0=', 0);

-- Vuln 3 target: URL này sẽ bị hacker đổi sang server độc hại
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('update_url', 'http://127.0.0.1:8081/manifest.json', 0);

-- Public configs (hiển thị ở admin panel)
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('app_version', '3.1.0', 1);
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('site_maintenance', 'false', 1);
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('max_search_results', '5', 1);

-- ============================================================
-- ADMIN_SECRETS (chỉ giữ các key hợp lý, bỏ Blind SQLi cũ)
-- ============================================================
-- Secret thật (dùng để verify sys_master_key qua secret_check.php)
INSERT INTO ADMIN_SECRETS (secret_key, encrypted_value, note, is_active) VALUES
  ('sys_master_key', 'SYS_MASTER_REDACTED_IN_PROD', 'System master key (verified only, no read)', 1);

-- Decoy key: bẫy người dùng Blind SQLi vào đây → ra fake flag
INSERT INTO ADMIN_SECRETS (secret_key, encrypted_value, note, is_active) VALUES
  ('oracle_flag_3_backup', 'DBS401{FAKE_blind_wrong_key_xd}', 'Legacy backup - do not use', 1);

-- ============================================================
-- FAKE_FLAGS (decoy table)
-- ============================================================
INSERT INTO FAKE_FLAGS (fake_code, fake_value, reason) VALUES
  ('FF001', 'DBS401{FAKE_union_select_lol}', 'Bẫy người dùng UNION đơn giản');
INSERT INTO FAKE_FLAGS (fake_code, fake_value, reason) VALUES
  ('FF002', 'DBS401{FAKE_IDOR_wrong_student}', 'Bẫy người tìm sai student ID');
INSERT INTO FAKE_FLAGS (fake_code, fake_value, reason) VALUES
  ('FF003', 'DBS401{FAKE_IDOR_notreal}', 'Bẫy IDOR stage 1');

-- ============================================================
-- SYSTEM_HINTS (gợi ý gián tiếp)
-- ============================================================
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('HINT_SQLI_01', 'Flags are not in one piece. Check AUDIT_LOGS and CONFIG_STORE too.', 'VULN1');
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('HINT_SQLI_02', 'Oracle uses USER_TABLES instead of information_schema.', 'VULN1');
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('HINT_LOGIC_01', 'What happens if quantity is a very large negative number?', 'VULN2');
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('HINT_SUPPLY_01', 'Admin update depends on the update_url stored in CONFIG_STORE.', 'VULN3');
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('HINT_SUPPLY_02', 'The update_url config has is_public=0. Can you find it another way?', 'VULN3');
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('HINT_SUPPLY_03', 'Partner configuration is hidden from the navbar, but not necessarily protected from authenticated users.', 'VULN3');
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('HINT_SUPPLY_04', 'The partner manifest needs a higher version and a flag_part hex fragment; the server completes the rest.', 'VULN3');

-- ============================================================
-- FLAG_ARCHIVE (decoy table - trông như bảng flag thật)
-- ============================================================
INSERT INTO FLAG_ARCHIVE (archive_code, archive_data) VALUES
  ('OLD_FLAG_2023', 'DBS401{FAKE_archived_flag_123}');
INSERT INTO FLAG_ARCHIVE (archive_code, archive_data) VALUES
  ('OLD_FLAG_2022', 'DBS401{FAKE_nothing_here_xd}');

COMMIT;
/

-- ============================================================
-- Sau khi seed xong, BẮT BUỘC chạy:
--   1. sqlplus ... @database/fix_refs.sql   (cập nhật admin_ref_id)
--   2. php database/init_passwords.php      (tạo bcrypt hash thật)
-- ============================================================