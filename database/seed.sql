-- Reset existing database records for clean seeding
DELETE FROM PLSQL_VAULT;
DELETE FROM ATTENDANCE;
DELETE FROM EXAMS;
DELETE FROM NOTIFICATION_READS;
DELETE FROM NOTIFICATIONS;
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
-- OracleSecLab - Vulnerable Oracle Web Application
-- Database Seed Data (Oracle XE 21c / 23c Free)
-- ============================================================
-- FLAG DESIGN:
--
-- FLAG 1: DBS401{SQL_1nj3ct10n_0r4cl3!}
--   Part A (FLAGS, hex)  : 4442533430317B53514C5F  = hex("DBS401{SQL_")
--   Part B (AUDIT_LOGS)  : _n01tc3jn1              = reverse("1nj3ct10n_")
--   Part C (CONFIG_STORE): MHI0Y2wzIX0=            = base64("0r4cl3!}")
--
-- FLAG 2: DBS401{LOGIC_GURU_2024}
--   Obtained from store.php via credit manipulation (negative quantity)
--
-- FLAG 3: DBS401{5upp1y_Ch41n_P0150n1ng_0912}
--   Obtained from admin.php by poisoning update_url in CONFIG_STORE
--   and triggering "Check for Partner Updates"
--
-- FLAG 4: DBS401{PL5QL_1nj3ct10n_D3f1n3r}
--   PL/SQL injection via EXECUTE IMMEDIATE inside FN_COURSE_LOOKUP
--   (course_lookup.php). Stored in PLSQL_VAULT (label='CHALLENGE_4').
-- ============================================================

-- ============================================================
-- USERS
-- NOTE: password_hash is a placeholder, run init_passwords.php to generate real bcrypt hashes
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
-- STUDENTS (SELECT subquery used to bind dynamic user_id)
-- ============================================================
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) SELECT u.user_id, 'Nguyen Van An', 'an.nv2021@fpt.edu.vn', 'Software Engineering', 3.20, '0901234567', 'Ha Noi', 'NORMAL', 150, 'HN'
FROM USERS u WHERE u.username = 'student1';

INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) SELECT u.user_id, 'Tran Thi Binh', 'binh.tt2021@fpt.edu.vn', 'Information Security', 3.55, '0912345678', 'Ho Chi Minh', 'NORMAL', 50, 'HCM'
FROM USERS u WHERE u.username = 'student2';

INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) SELECT u.user_id, 'Le Quoc Cuong', 'cuong.lq2021@fpt.edu.vn', 'Artificial Intelligence', 2.90, '0923456789', 'Da Nang', 'NORMAL', 200, 'DN'
FROM USERS u WHERE u.username = 'student3';

-- Additional demo student records without interactive user logins
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
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits) VALUES
  (NULL, 'Nguyen Minh Ngoc', 'ngoc.nm2007@st.vimaru.edu.vn', 'English Language', 3.20, '0911111116', 'Hai Phong', 'NORMAL', 10000015);

-- Decoy student (hidden_marker DECOY prevents appearance in standard searches)
-- Enrollment internal_note contains FAKE base64 flag to trap IDOR hunters
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker) VALUES
  (NULL, 'Pham Thi Dung', 'dung.pt2021@fpt.edu.vn', 'Business IT', 2.50, '0934567890', 'Can Tho', 'DECOY_42');

-- ============================================================
-- COURSES (Academic Curriculum)
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
  ('CSD201', 'Data Structures and Algorithms', 'Dr. Tran Thi Mai', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('PRN211', 'Basic Cross-Platform (.NET)', 'MSc. Hoang Van Nam', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('MAS291', 'Statistics and Probability', 'Dr. Le Van Khoa', 3, '2024-S1');

-- ============================================================
-- ENROLLMENTS (subqueries used to avoid hardcoded student_id)
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

-- Decoy student (Pham Thi Dung) - internal_note contains FAKE base64 flag to trap IDOR hunters
-- base64("DBS401{FAKE_IDOR_wrong_student}") = "REJTNDE...WRONG..."
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Pham Thi Dung'),
   (SELECT course_id FROM COURSES WHERE course_code='PRJ401'),
   5.0, '2024-S1', 'TXN-004-2024-S1',
   'SYSTEM_REF: REJTNDE5e1dST05HX2ZsYWdfaGVyZX0=',
   NULL);

-- ============================================================
-- AUDIT_LOGS (Flag 1 Part B - fragment requiring reversal)
-- ============================================================
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, metadata_note) VALUES
  (1, 'SYSTEM_AUDIT_CHECK', '127.0.0.1',
   '{"sys_version":"v2.1","fragment":"_n01tc3jn1","note":"reverse_for_context","ref":"FLAG_COMPONENT_B"}');

INSERT INTO AUDIT_LOGS (user_id, action, ip_address, metadata_note) VALUES
  (1, 'LOGIN_SUCCESS', '192.168.1.50', 'Admin session started');

-- Decoy log (traps hunters reading unrelated audit logs)
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, metadata_note) VALUES
  (NULL, 'SECRET_VAULT_ACCESS', '10.0.0.1',
   '{"note":"routine_backup","status":"ok","data":"REJTNDE5eFNFQ1JFVF9CQUNLVVBfRkxBR30="}');

-- ============================================================
-- FLAGS (Flag 1 Part A - hex encoded)
-- ============================================================
-- Real flag part A: hex("DBS401{SQL_") = 4442533430317B53514C5F
INSERT INTO FLAGS (flag_code, flag_part, part_order, hint, difficulty, is_active) VALUES
  ('FL1_PART_A', '4442533430317B53514C5F', 1, 'Try decoding this as hex bytes', 'Easy', 1);

-- Decoy: mimics flag fragment but contains garbage base64
INSERT INTO FLAGS (flag_code, flag_part, part_order, hint, difficulty, is_active) VALUES
  ('FL_DECOY_B', 'U09NRV9GQUtFX0ZMQUdfSEVSRQ==', 2, 'Is it base64?', 'Medium', 1);

-- ============================================================
-- CONFIG_STORE
-- ============================================================
-- Flag 1 Part C: base64("0r4cl3!}") = MHI0Y2wzIX0=
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('sys_alpha_marker', 'MHI0Y2wzIX0=', 0);

-- Decoy config (flag-like key name containing decoy base64)
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('sys_beta_marker', 'REJTNDE5eEZBS0VfQ09ORklHX01BUktFUn0=', 0);

-- Vuln 3 target: update URL poisoned by attacker to rogue manifest server
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('update_url', 'http://127.0.0.1:8081/manifest.json', 0);

-- Public configs (displayed in admin panel)
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('app_version', '3.1.0', 1);
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('site_maintenance', 'false', 1);
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('max_search_results', '5', 1);

-- ============================================================
-- ADMIN_SECRETS (retained for verification API and decoys)
-- ============================================================
-- Real secret (used to verify sys_master_key via secret_check.php)
INSERT INTO ADMIN_SECRETS (secret_key, encrypted_value, note, is_active) VALUES
  ('sys_master_key', 'SYS_MASTER_REDACTED_IN_PROD', 'System master key (verified only, no read)', 1);

-- Decoy key: traps blind SQLi attempts into returning a fake flag
INSERT INTO ADMIN_SECRETS (secret_key, encrypted_value, note, is_active) VALUES
  ('oracle_flag_3_backup', 'DBS401{FAKE_blind_wrong_key_xd}', 'Legacy backup - do not use', 1);

-- ============================================================
-- FAKE_FLAGS (decoy table)
-- ============================================================
INSERT INTO FAKE_FLAGS (fake_code, fake_value, reason) VALUES
  ('FF001', 'DBS401{FAKE_union_select_lol}', 'Traps naive automated UNION payloads');
INSERT INTO FAKE_FLAGS (fake_code, fake_value, reason) VALUES
  ('FF002', 'DBS401{FAKE_IDOR_wrong_student}', 'Traps hunters enumerating wrong student IDs');
INSERT INTO FAKE_FLAGS (fake_code, fake_value, reason) VALUES
  ('FF003', 'DBS401{FAKE_IDOR_notreal}', 'Traps IDOR stage 1 attempts');

-- ============================================================
-- SYSTEM_HINTS (indirect CTF hints)
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
-- FLAG_ARCHIVE (decoy table mimicking genuine flags table)
-- ============================================================
INSERT INTO FLAG_ARCHIVE (archive_code, archive_data) VALUES
  ('OLD_FLAG_2023', 'DBS401{FAKE_archived_flag_123}');
INSERT INTO FLAG_ARCHIVE (archive_code, archive_data) VALUES
  ('OLD_FLAG_2022', 'DBS401{FAKE_nothing_here_xd}');

-- ============================================================
-- PLSQL_VAULT (Challenge 4 - reachable only via PL/SQL injection)
-- ============================================================
-- Real Flag 4 lives in the CHALLENGE_4 row. The DECOY row has the same shape,
-- so a naive "UNION SELECT secret_flag FROM PLSQL_VAULT" returns two rows and
-- the function raises TOO_MANY_ROWS - the attacker must select a single row.
INSERT INTO PLSQL_VAULT (label, secret_flag) VALUES
  ('CHALLENGE_4', 'DBS401{PL5QL_1nj3ct10n_D3f1n3r}');
INSERT INTO PLSQL_VAULT (label, secret_flag) VALUES
  ('DECOY', 'DBS401{FAKE_plsql_not_this_row}');

-- ============================================================
-- NOTIFICATIONS  (portal announcements - cosmetic, no flags)
-- ============================================================
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('notice', 'Final Defense Schedule for DBS401 (Database Security) Released',
   'All project groups must submit their final documentation, demonstration environment, and slide deck by Friday. Presentations take place in Building Alpha, Room AL-204.',
   'all', SYSTIMESTAMP - NUMTODSINTERVAL(2, 'HOUR'));
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('academic', 'Summer 2024 Course Add/Drop Period Extended',
   'The Office of Academic Affairs has extended the course registration window by 48 hours. Adjust your enrollments from the Course Catalog module.',
   'all', SYSTIMESTAMP - NUMTODSINTERVAL(8, 'HOUR'));
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('exam', 'Mid-term Examination Timetable Published',
   'The mid-term examination timetable for Summer 2024 is now available. Check your seat number and room assignment before the first exam day.',
   'student', SYSTIMESTAMP - NUMTODSINTERVAL(1, 'DAY'));
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('finance', 'Tuition Payment Reminder - Installment 2',
   'Installment 2 of your Summer 2024 tuition is due by the end of this month. Settle outstanding balances via the Tuition and Fees module to avoid a late fee.',
   'student', SYSTIMESTAMP - NUMTODSINTERVAL(2, 'DAY'));
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('notice', 'Grade Submission Window Opens for Summer 2024',
   'Faculty may now enter and finalize grades for assigned course rosters. The submission window closes two weeks after the last examination.',
   'teacher', SYSTIMESTAMP - NUMTODSINTERVAL(3, 'DAY'));
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('system', 'Oracle Database Maintenance Window - Sunday 02:00-04:00',
   'Routine database backup and index optimization will occur this Sunday. Portal services may experience brief latency during this interval.',
   'all', SYSTIMESTAMP - NUMTODSINTERVAL(4, 'DAY'));
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('system', 'New Portal Features: Notification Center and Full Profile',
   'You can now manage all campus announcements from the Notification Center and update your contact details and password from My Profile.',
   'all', SYSTIMESTAMP - NUMTODSINTERVAL(5, 'DAY'));
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('notice', 'Library Extended Opening Hours During Examination Period',
   'The campus library will remain open until 23:00 on weekdays throughout the examination period to support student revision.',
   'all', SYSTIMESTAMP - NUMTODSINTERVAL(6, 'DAY'));

-- ============================================================
-- EXAMS  (examination timetable - cosmetic, no flags)
-- Dates are relative to SYSDATE: Progress Tests already sat, Final Exams ahead.
-- ============================================================
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 14, '07:30',  60, 'LAB-401', 'On computer' FROM COURSES WHERE course_code = 'DBS401';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam',    TRUNC(SYSDATE) +  7, '07:30', 120, 'BE-302',  'On paper'    FROM COURSES WHERE course_code = 'DBS401';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 12, '10:00',  60, 'CS-202',  'On computer' FROM COURSES WHERE course_code = 'PRJ401';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam',    TRUNC(SYSDATE) +  9, '10:00',  90, 'AL-204',  'On paper'    FROM COURSES WHERE course_code = 'PRJ401';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 10, '12:50',  60, 'NV-205',  'On paper'    FROM COURSES WHERE course_code = 'SWD392';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam',    TRUNC(SYSDATE) + 11, '12:50',  90, 'DE-101',  'On paper'    FROM COURSES WHERE course_code = 'SWD392';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) -  9, '15:20',  60, 'LAB-401', 'On computer' FROM COURSES WHERE course_code = 'NET201';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam',    TRUNC(SYSDATE) + 14, '07:30',  90, 'BE-302',  'On computer' FROM COURSES WHERE course_code = 'NET201';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) -  7, '07:30',  60, 'CS-202',  'On computer' FROM COURSES WHERE course_code = 'CSD201';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam',    TRUNC(SYSDATE) + 16, '10:00', 120, 'AL-204',  'On paper'    FROM COURSES WHERE course_code = 'CSD201';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) -  5, '10:00',  60, 'DE-101',  'On paper'    FROM COURSES WHERE course_code = 'MAS291';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam',    TRUNC(SYSDATE) + 18, '12:50',  90, 'NV-205',  'On paper'    FROM COURSES WHERE course_code = 'MAS291';

-- ============================================================
-- ATTENDANCE  (per-student class attendance - cosmetic, no flags)
-- 6 weekly sessions per enrolled course; one course dips below the 80% rule.
-- ============================================================
-- student1 - DBS401 (5/6 attended)
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 42, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'DBS401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 35, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'DBS401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 28, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'DBS401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 21, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'DBS401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 14, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'DBS401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 7, 'Slot 2', 'absent' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'DBS401';

-- student1 - PRJ401 (4/6 attended - below threshold)
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 42, 'Slot 3', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'PRJ401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 35, 'Slot 3', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'PRJ401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 28, 'Slot 3', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'PRJ401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 21, 'Slot 3', 'late' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'PRJ401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 14, 'Slot 3', 'absent' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'PRJ401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 7, 'Slot 3', 'absent' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'PRJ401';

-- student2 - DBS401 (6/6 attended)
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 42, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student2') AND c.course_code = 'DBS401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 35, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student2') AND c.course_code = 'DBS401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 28, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student2') AND c.course_code = 'DBS401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 21, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student2') AND c.course_code = 'DBS401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 14, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student2') AND c.course_code = 'DBS401';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 7, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student2') AND c.course_code = 'DBS401';

-- student2 - SWD392 (5/6 attended)
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 42, 'Slot 1', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student2') AND c.course_code = 'SWD392';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 35, 'Slot 1', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student2') AND c.course_code = 'SWD392';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 28, 'Slot 1', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student2') AND c.course_code = 'SWD392';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 21, 'Slot 1', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student2') AND c.course_code = 'SWD392';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 14, 'Slot 1', 'late' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student2') AND c.course_code = 'SWD392';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 7, 'Slot 1', 'absent' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student2') AND c.course_code = 'SWD392';

-- student3 - NET201 (5/6 attended)
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 42, 'Slot 4', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student3') AND c.course_code = 'NET201';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 35, 'Slot 4', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student3') AND c.course_code = 'NET201';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 28, 'Slot 4', 'late' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student3') AND c.course_code = 'NET201';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 21, 'Slot 4', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student3') AND c.course_code = 'NET201';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 14, 'Slot 4', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student3') AND c.course_code = 'NET201';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 7, 'Slot 4', 'absent' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student3') AND c.course_code = 'NET201';


-- ============================================================
-- ENRICHED_DATA_V1 : larp-cap-style academic dataset (cosmetic, no flags)
--   +12 courses, +18 students, extra enrollments/exams/attendance/news.
--   Challenge-critical rows (FLAGS/CONFIG_STORE/PLSQL_VAULT/decoys/logins)
--   are untouched; search uses non-matching terms so SQLi is unaffected.
-- ============================================================

-- additional COURSES
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('PRF192', 'Programming Fundamentals', 'Dr. Nguyen Thanh Son', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('MAD101', 'Discrete Mathematics', 'Dr. Pham Thi Lan', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('PRO192', 'Object-Oriented Programming', 'MSc. Tran Van Khanh', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('DBI202', 'Introduction to Databases', 'Dr. Le Hoang Minh', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('WED201', 'Web Design', 'MSc. Do Thi Thu', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('LAB211', 'OOP with Java Lab', 'MSc. Nguyen Quoc Dat', 1, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('JPD113', 'Elementary Japanese', 'MSc. Sato Yuki', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('SSL101', 'Academic Skills for University', 'MSc. Vu Mai Anh', 2, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('IOT102', 'Internet of Things', 'Dr. Hoang Van Phuc', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('AIG202', 'Artificial Intelligence', 'Dr. Tran Bich Van', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('MKT205', 'Digital Marketing Foundations', 'MSc. Pham Quoc Bao', 3, '2024-S1');
INSERT INTO COURSES (course_code, course_name, teacher_name, credits, semester) VALUES
  ('ENW302', 'Business English Writing', 'MSc. Jane Carter', 3, '2024-S1');

-- additional STUDENTS (demo records, no interactive login)
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Nguyen Hoang Long', 'long.nh2021@fpt.edu.vn', 'Software Engineering', 3.45, '0901100201', 'Ha Noi', 'NORMAL', 165, 'HN');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Tran Gia Han', 'han.tg2021@fpt.edu.vn', 'Artificial Intelligence', 3.72, '0901100202', 'Ho Chi Minh', 'NORMAL', 180, 'HCM');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Le Thi Mai Phuong', 'phuong.ltm2022@fpt.edu.vn', 'Information Security', 3.18, '0901100203', 'Da Nang', 'NORMAL', 120, 'DN');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Pham Duc Anh', 'anh.pd2021@fpt.edu.vn', 'Software Engineering', 2.88, '0901100204', 'Hai Phong', 'NORMAL', 140, 'HN');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Vo Thi Kim Ngan', 'ngan.vtk2022@fpt.edu.vn', 'Digital Marketing', 3.60, '0901100205', 'Can Tho', 'NORMAL', 95, 'CT');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Dang Quoc Bao', 'bao.dq2021@fpt.edu.vn', 'Information Security', 3.05, '0901100206', 'Quy Nhon', 'NORMAL', 155, 'QN');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Bui Thanh Tung', 'tung.bt2022@fpt.edu.vn', 'Internet of Things', 2.95, '0901100207', 'Ha Noi', 'NORMAL', 110, 'HN');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Nguyen Thi Thu Ha', 'ha.ntt2021@fpt.edu.vn', 'Business Management', 3.33, '0901100208', 'Ho Chi Minh', 'NORMAL', 175, 'HCM');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Hoang Gia Huy', 'huy.hg2023@fpt.edu.vn', 'Graphic Design', 3.50, '0901100209', 'Da Nang', 'NORMAL', 70, 'DN');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Tran Minh Quan', 'quan.tm2021@fpt.edu.vn', 'Artificial Intelligence', 3.80, '0901100210', 'Ha Noi', 'NORMAL', 190, 'HN');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Pham Thi Yen Nhi', 'nhi.pty2022@fpt.edu.vn', 'Software Engineering', 3.22, '0901100211', 'Hue', 'NORMAL', 130, 'HN');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Le Van Thanh', 'thanh.lv2021@fpt.edu.vn', 'Network Engineering', 2.70, '0901100212', 'Ho Chi Minh', 'NORMAL', 145, 'HCM');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Nguyen Ngoc Diep', 'diep.nn2022@fpt.edu.vn', 'International Business', 3.65, '0901100213', 'Can Tho', 'NORMAL', 100, 'CT');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Vu Hoang Nam', 'nam.vh2021@fpt.edu.vn', 'Software Engineering', 3.12, '0901100214', 'Da Nang', 'NORMAL', 160, 'DN');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Dinh Thi Quynh', 'quynh.dt2023@fpt.edu.vn', 'Multimedia Communications', 3.40, '0901100215', 'Ha Noi', 'NORMAL', 65, 'HN');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Truong Gia Bao', 'bao.tg2021@fpt.edu.vn', 'Data Science', 3.58, '0901100216', 'Ho Chi Minh', 'NORMAL', 185, 'HCM');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Nguyen Thi Lan Anh', 'anh.ntl2022@fpt.edu.vn', 'Japanese Language', 3.27, '0901100217', 'Quy Nhon', 'NORMAL', 115, 'QN');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker, credits, campus) VALUES
  (NULL, 'Pham Hoang Phuc', 'phuc.ph2021@fpt.edu.vn', 'Software Engineering', 2.82, '0901100218', 'Ha Noi', 'NORMAL', 150, 'HN');

-- additional ENROLLMENTS for login students (richer transcripts)
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student1')),
   (SELECT course_id FROM COURSES WHERE course_code='CSD201'),
   8.0, '2024-S1', 'TXN-201-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student1')),
   (SELECT course_id FROM COURSES WHERE course_code='PRN211'),
   7.3, '2024-S1', 'TXN-202-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student1')),
   (SELECT course_id FROM COURSES WHERE course_code='MAS291'),
   6.8, '2024-S1', 'TXN-203-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student1')),
   (SELECT course_id FROM COURSES WHERE course_code='MLN122'),
   9.2, '2024-S1', 'TXN-204-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student1')),
   (SELECT course_id FROM COURSES WHERE course_code='DBI202'),
   8.7, '2024-S1', 'TXN-205-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student1')),
   (SELECT course_id FROM COURSES WHERE course_code='PRF192'),
   9.4, '2024-S1', 'TXN-206-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student2')),
   (SELECT course_id FROM COURSES WHERE course_code='CSD201'),
   8.9, '2024-S1', 'TXN-207-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student2')),
   (SELECT course_id FROM COURSES WHERE course_code='PRO192'),
   8.1, '2024-S1', 'TXN-208-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student2')),
   (SELECT course_id FROM COURSES WHERE course_code='MAD101'),
   7.6, '2024-S1', 'TXN-209-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student3')),
   (SELECT course_id FROM COURSES WHERE course_code='AIG202'),
   8.4, '2024-S1', 'TXN-210-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student3')),
   (SELECT course_id FROM COURSES WHERE course_code='IOT102'),
   7.9, '2024-S1', 'TXN-211-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE user_id=(SELECT user_id FROM USERS WHERE username='student3')),
   (SELECT course_id FROM COURSES WHERE course_code='PRF192'),
   8.0, '2024-S1', 'TXN-212-2024-S1', 'Normal enrollment.', NULL);

-- additional ENROLLMENTS for new students
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Nguyen Hoang Long'),
   (SELECT course_id FROM COURSES WHERE course_code='PRF192'),
   5.5, '2024-S1', 'TXN-213-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Nguyen Hoang Long'),
   (SELECT course_id FROM COURSES WHERE course_code='MAD101'),
   5.8, '2024-S1', 'TXN-214-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Tran Gia Han'),
   (SELECT course_id FROM COURSES WHERE course_code='PRO192'),
   6.2, '2024-S1', 'TXN-215-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Tran Gia Han'),
   (SELECT course_id FROM COURSES WHERE course_code='DBI202'),
   6.5, '2024-S1', 'TXN-216-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Le Thi Mai Phuong'),
   (SELECT course_id FROM COURSES WHERE course_code='CSD201'),
   6.9, '2024-S1', 'TXN-217-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Le Thi Mai Phuong'),
   (SELECT course_id FROM COURSES WHERE course_code='WED201'),
   7.2, '2024-S1', 'TXN-218-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Pham Duc Anh'),
   (SELECT course_id FROM COURSES WHERE course_code='AIG202'),
   7.6, '2024-S1', 'TXN-219-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Pham Duc Anh'),
   (SELECT course_id FROM COURSES WHERE course_code='IOT102'),
   7.9, '2024-S1', 'TXN-220-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Vo Thi Kim Ngan'),
   (SELECT course_id FROM COURSES WHERE course_code='MKT205'),
   8.3, '2024-S1', 'TXN-221-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Vo Thi Kim Ngan'),
   (SELECT course_id FROM COURSES WHERE course_code='SSL101'),
   8.6, '2024-S1', 'TXN-222-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Dang Quoc Bao'),
   (SELECT course_id FROM COURSES WHERE course_code='JPD113'),
   9.0, '2024-S1', 'TXN-223-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Dang Quoc Bao'),
   (SELECT course_id FROM COURSES WHERE course_code='ENW302'),
   9.3, '2024-S1', 'TXN-224-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Bui Thanh Tung'),
   (SELECT course_id FROM COURSES WHERE course_code='DBS401'),
   9.7, '2024-S1', 'TXN-225-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Bui Thanh Tung'),
   (SELECT course_id FROM COURSES WHERE course_code='NET201'),
   5.5, '2024-S1', 'TXN-226-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Nguyen Thi Thu Ha'),
   (SELECT course_id FROM COURSES WHERE course_code='PRF192'),
   5.9, '2024-S1', 'TXN-227-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Nguyen Thi Thu Ha'),
   (SELECT course_id FROM COURSES WHERE course_code='MAD101'),
   6.2, '2024-S1', 'TXN-228-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Hoang Gia Huy'),
   (SELECT course_id FROM COURSES WHERE course_code='PRO192'),
   6.6, '2024-S1', 'TXN-229-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Hoang Gia Huy'),
   (SELECT course_id FROM COURSES WHERE course_code='DBI202'),
   6.9, '2024-S1', 'TXN-230-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Tran Minh Quan'),
   (SELECT course_id FROM COURSES WHERE course_code='CSD201'),
   7.3, '2024-S1', 'TXN-231-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Tran Minh Quan'),
   (SELECT course_id FROM COURSES WHERE course_code='WED201'),
   7.6, '2024-S1', 'TXN-232-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Pham Thi Yen Nhi'),
   (SELECT course_id FROM COURSES WHERE course_code='AIG202'),
   8.0, '2024-S1', 'TXN-233-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Pham Thi Yen Nhi'),
   (SELECT course_id FROM COURSES WHERE course_code='IOT102'),
   8.3, '2024-S1', 'TXN-234-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Le Van Thanh'),
   (SELECT course_id FROM COURSES WHERE course_code='MKT205'),
   8.7, '2024-S1', 'TXN-235-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Le Van Thanh'),
   (SELECT course_id FROM COURSES WHERE course_code='SSL101'),
   9.0, '2024-S1', 'TXN-236-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Nguyen Ngoc Diep'),
   (SELECT course_id FROM COURSES WHERE course_code='JPD113'),
   9.4, '2024-S1', 'TXN-237-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Nguyen Ngoc Diep'),
   (SELECT course_id FROM COURSES WHERE course_code='ENW302'),
   9.7, '2024-S1', 'TXN-238-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Vu Hoang Nam'),
   (SELECT course_id FROM COURSES WHERE course_code='DBS401'),
   5.6, '2024-S1', 'TXN-239-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Vu Hoang Nam'),
   (SELECT course_id FROM COURSES WHERE course_code='NET201'),
   5.9, '2024-S1', 'TXN-240-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Dinh Thi Quynh'),
   (SELECT course_id FROM COURSES WHERE course_code='PRF192'),
   6.3, '2024-S1', 'TXN-241-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Dinh Thi Quynh'),
   (SELECT course_id FROM COURSES WHERE course_code='MAD101'),
   6.6, '2024-S1', 'TXN-242-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Truong Gia Bao'),
   (SELECT course_id FROM COURSES WHERE course_code='PRO192'),
   7.0, '2024-S1', 'TXN-243-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Truong Gia Bao'),
   (SELECT course_id FROM COURSES WHERE course_code='DBI202'),
   7.3, '2024-S1', 'TXN-244-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Nguyen Thi Lan Anh'),
   (SELECT course_id FROM COURSES WHERE course_code='CSD201'),
   7.7, '2024-S1', 'TXN-245-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Nguyen Thi Lan Anh'),
   (SELECT course_id FROM COURSES WHERE course_code='WED201'),
   8.0, '2024-S1', 'TXN-246-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Pham Hoang Phuc'),
   (SELECT course_id FROM COURSES WHERE course_code='AIG202'),
   8.4, '2024-S1', 'TXN-247-2024-S1', 'Normal enrollment.', NULL);
INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note, admin_ref_id) VALUES
  ((SELECT student_id FROM STUDENTS WHERE full_name='Pham Hoang Phuc'),
   (SELECT course_id FROM COURSES WHERE course_code='IOT102'),
   8.7, '2024-S1', 'TXN-248-2024-S1', 'Normal enrollment.', NULL);

-- additional EXAMS for new courses
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 13, '07:30', 60, 'LAB-401', 'On computer' FROM COURSES WHERE course_code = 'PRF192';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam', TRUNC(SYSDATE) + 6, '10:00', 90, 'AL-204', 'On paper' FROM COURSES WHERE course_code = 'PRF192';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 12, '10:00', 60, 'CS-202', 'On computer' FROM COURSES WHERE course_code = 'MAD101';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam', TRUNC(SYSDATE) + 7, '12:50', 90, 'BE-302', 'On paper' FROM COURSES WHERE course_code = 'MAD101';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 11, '12:50', 60, 'AL-204', 'On computer' FROM COURSES WHERE course_code = 'PRO192';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam', TRUNC(SYSDATE) + 8, '15:20', 90, 'NV-205', 'On paper' FROM COURSES WHERE course_code = 'PRO192';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 10, '15:20', 60, 'BE-302', 'On computer' FROM COURSES WHERE course_code = 'DBI202';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam', TRUNC(SYSDATE) + 9, '07:30', 90, 'DE-101', 'On paper' FROM COURSES WHERE course_code = 'DBI202';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 9, '07:30', 60, 'NV-205', 'On computer' FROM COURSES WHERE course_code = 'WED201';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam', TRUNC(SYSDATE) + 10, '10:00', 90, 'LAB-401', 'On paper' FROM COURSES WHERE course_code = 'WED201';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 8, '10:00', 60, 'DE-101', 'On computer' FROM COURSES WHERE course_code = 'LAB211';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam', TRUNC(SYSDATE) + 11, '12:50', 90, 'CS-202', 'On paper' FROM COURSES WHERE course_code = 'LAB211';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 7, '12:50', 60, 'LAB-401', 'On computer' FROM COURSES WHERE course_code = 'JPD113';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam', TRUNC(SYSDATE) + 12, '15:20', 90, 'AL-204', 'On paper' FROM COURSES WHERE course_code = 'JPD113';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 6, '15:20', 60, 'CS-202', 'On computer' FROM COURSES WHERE course_code = 'SSL101';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam', TRUNC(SYSDATE) + 13, '07:30', 90, 'BE-302', 'On paper' FROM COURSES WHERE course_code = 'SSL101';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 5, '07:30', 60, 'AL-204', 'On computer' FROM COURSES WHERE course_code = 'IOT102';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam', TRUNC(SYSDATE) + 14, '10:00', 90, 'NV-205', 'On paper' FROM COURSES WHERE course_code = 'IOT102';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 13, '10:00', 60, 'BE-302', 'On computer' FROM COURSES WHERE course_code = 'AIG202';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam', TRUNC(SYSDATE) + 15, '12:50', 90, 'DE-101', 'On paper' FROM COURSES WHERE course_code = 'AIG202';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 12, '12:50', 60, 'NV-205', 'On computer' FROM COURSES WHERE course_code = 'MKT205';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam', TRUNC(SYSDATE) + 16, '15:20', 90, 'LAB-401', 'On paper' FROM COURSES WHERE course_code = 'MKT205';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Progress Test', TRUNC(SYSDATE) - 11, '15:20', 60, 'DE-101', 'On computer' FROM COURSES WHERE course_code = 'ENW302';
INSERT INTO EXAMS (course_id, exam_type, exam_date, start_time, duration_min, room, exam_form)
  SELECT course_id, 'Final Exam', TRUNC(SYSDATE) + 17, '07:30', 90, 'CS-202', 'On paper' FROM COURSES WHERE course_code = 'ENW302';

-- additional ATTENDANCE for student1 new courses (6 weekly sessions)
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 42, 'Slot 1', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'CSD201';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 35, 'Slot 1', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'CSD201';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 28, 'Slot 1', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'CSD201';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 21, 'Slot 1', 'late' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'CSD201';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 14, 'Slot 1', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'CSD201';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 7, 'Slot 1', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'CSD201';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 42, 'Slot 5', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'MLN122';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 35, 'Slot 5', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'MLN122';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 28, 'Slot 5', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'MLN122';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 21, 'Slot 5', 'late' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'MLN122';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 14, 'Slot 5', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'MLN122';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 7, 'Slot 5', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'MLN122';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 42, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'DBI202';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 35, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'DBI202';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 28, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'DBI202';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 21, 'Slot 2', 'late' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'DBI202';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 14, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'DBI202';
INSERT INTO ATTENDANCE (student_id, course_id, session_date, slot, status) SELECT s.student_id, c.course_id, TRUNC(SYSDATE) - 7, 'Slot 2', 'present' FROM STUDENTS s, COURSES c WHERE s.user_id = (SELECT user_id FROM USERS WHERE username = 'student1') AND c.course_code = 'DBI202';

-- additional NOTIFICATIONS (campus news)
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('academic', 'Scholarship Applications Open for Fall 2024', 'The Academic Excellence Scholarship is now accepting applications. Students with a GPA above 3.2 may apply through the Student Services portal before the end of the month.', 'student', SYSTIMESTAMP - NUMTODSINTERVAL(12, 'HOUR'));
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('notice', 'Spring Career Fair - 40+ Partner Companies', 'The annual Career Fair returns to Building Alpha with recruiters from leading technology and finance firms. Bring printed CVs and student ID for entry.', 'all', SYSTIMESTAMP - NUMTODSINTERVAL(30, 'HOUR'));
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('exam', 'Final Exam Seating Arrangements Published', 'Seat numbers and room assignments for the Summer 2024 final examinations are now available. Please arrive 15 minutes before your scheduled start time.', 'student', SYSTIMESTAMP - NUMTODSINTERVAL(36, 'HOUR'));
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('system', 'Single Sign-On Upgrade This Weekend', 'The identity service will be upgraded on Saturday night. You may be asked to sign in again on Monday. No action is required beforehand.', 'all', SYSTIMESTAMP - NUMTODSINTERVAL(50, 'HOUR'));
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('academic', 'English Placement Test for New Intake', 'Newly admitted students must complete the English placement test before course registration. Check your email for your assigned time slot.', 'student', SYSTIMESTAMP - NUMTODSINTERVAL(62, 'HOUR'));
INSERT INTO NOTIFICATIONS (category, title, body, audience, created_at) VALUES
  ('notice', 'Student Club Recruitment Week', 'Over 20 student clubs from the Coding Club to the Music Society are recruiting new members this week at the main hall.', 'all', SYSTIMESTAMP - NUMTODSINTERVAL(74, 'HOUR'));

COMMIT;
/

-- ============================================================
-- Post-seeding execution requirements:
--   1. sqlplus ... @database/fix_refs.sql   (verifies database state)
--   2. php database/init_passwords.php      (generates bcrypt hashes)
-- ============================================================
