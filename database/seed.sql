-- ============================================================
-- DBS401 - Group 02 - Seed Data
-- ============================================================
-- FLAG DESIGN (INTERNAL - DO NOT INCLUDE IN PUBLIC REPORT):
--
-- FLAG 1: DBS401{SQL_1nj3ct10n_0r4cl3!}
--   Part A (FLAGS, hex)  : 4442533430317B53514C5F  = hex("DBS401{SQL_")
--   Part B (AUDIT_LOGS)  : _n01tc3jn1              = reverse("1nj3ct10n_")
--   Part C (CONFIG_STORE): MHI0Y2wzIX0=            = base64("0r4cl3!}")
--
-- FLAG 2: DBS401{1DOR_Tr4ns_4cc3ss_Fl4w!}
--   Part A (ENROLLMENTS) : REJTNDAxezFET1JfVHI0bnNf = base64("DBS401{1DOR_Tr4ns_")
--   Part B (AUDIT_LOGS)  : }!w4lF_ss3cc4            = reverse("4cc3ss_Fl4w!}")
--
-- FLAG 3: DBS401{Bl1nd_B00l_0r4cl3_X3rt!}
--   Part A (ADMIN_SECRETS, blind extract): DBS401{Bl1nd_B00l_
--   Part B (CONFIG_STORE, hex): 307234636C335F58337274217D = hex("0r4cl3_X3rt!}")
--
-- FAKE FLAGS:
--   DBS401{FAKE_union_select_lol}
--   DBS401{FAKE_IDOR_wrong_student}
--   DBS401{FAKE_blind_wrong_key_xd}
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
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker) VALUES
  (3, 'Nguyen Van An',   'an.nv2021@fpt.edu.vn',   'Software Engineering', 3.20, '0901234567', 'Ha Noi', 'NORMAL');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker) VALUES
  (4, 'Tran Thi Binh',   'binh.tt2021@fpt.edu.vn', 'Information Security',  3.55, '0912345678', 'Ho Chi Minh', 'NORMAL');
INSERT INTO STUDENTS (user_id, full_name, email, major, gpa, phone, address, hidden_marker) VALUES
  (5, 'Le Quoc Cuong',   'cuong.lq2021@fpt.edu.vn','Artificial Intelligence',2.90, '0923456789', 'Da Nang', 'NORMAL');

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
-- AUDIT_LOGS
-- ============================================================
-- Normal login events
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, user_agent, metadata_note) VALUES
  (1, 'LOGIN_SUCCESS', '192.168.1.10', 'Mozilla/5.0', '{"event":"login","ts":"2024-01-15"}');
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, user_agent, metadata_note) VALUES
  (3, 'LOGIN_SUCCESS', '192.168.1.11', 'Mozilla/5.0', '{"event":"login","ts":"2024-01-15"}');
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, user_agent, metadata_note) VALUES
  (3, 'TRANSCRIPT_VIEW', '192.168.1.11', 'Mozilla/5.0', '{"ref":"TXN-001-2024-S1","ts":"2024-01-16"}');
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, user_agent, metadata_note) VALUES
  (4, 'LOGIN_SUCCESS', '192.168.1.12', 'Mozilla/5.0', '{"event":"login","ts":"2024-01-17"}');
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, user_agent, metadata_note) VALUES
  (1, 'ADMIN_CONFIG_UPDATE', '127.0.0.1', 'InternalScript/1.0', '{"config":"sys_update","ts":"2024-01-10"}');

-- FLAG 1 - Part B: AUDIT_LOGS metadata contains reversed "1nj3ct10n_"
-- action=SYSTEM_AUDIT_CHECK, metadata_note has JSON with fragment
-- log_id will be auto-assigned; we need it to be findable via SQLi
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, user_agent, metadata_note) VALUES
  (1, 'SYSTEM_AUDIT_CHECK', '127.0.0.1', 'System/2.0',
   '{"sys_version":"v2.1","fragment":"_n01tc3jn1","note":"reverse_for_context","ref":"FLAG_COMPONENT_B"}');

-- FLAG 2 - Part B: AUDIT_LOGS for hidden student transcript access
-- FLAG 2 Part B: log_id tự động (auto IDENTITY).
-- Sau khi seed xong, chạy fix_refs.sql để cập nhật admin_ref_id trong ENROLLMENTS.
-- Metadata chứa reversed "4cc3ss_Fl4w!}" = "}!w4lF_ss3cc4"
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, user_agent, metadata_note) VALUES
  (NULL, 'TRANSCRIPT_EXPORT_HIDDEN', '10.0.0.1', 'AutoExport/1.0',
   '{"export_ref":"EXP-HIDDEN-F2","fragment_b":"}!w4lF_ss3cc4","decode_hint":"reverse_this_part","related":"TXN-099-2024-S1"}');

-- Decoy audit log to confuse attackers
INSERT INTO AUDIT_LOGS (user_id, action, ip_address, user_agent, metadata_note) VALUES
  (1, 'SECRET_VAULT_ACCESS', '10.0.0.5', 'VaultClient/3.0',
   '{"vault":"open","data":"FAKE_DBS401{this_is_decoy_audit}","note":"red_herring"}');

INSERT INTO AUDIT_LOGS (user_id, action, ip_address, user_agent, metadata_note) VALUES
  (1, 'SYSTEM_HEALTH_CHECK', '127.0.0.1', 'HealthMonitor/1.0',
   '{"status":"ok","uptime":"99.9%","flag_placeholder":"not_here_lol"}');

-- ============================================================
-- FLAGS TABLE  (fragmented - no complete flag here)
-- ============================================================
-- Real Flag 1 Part A: hex("DBS401{SQL_") = 4442533430317B53514C5F
INSERT INTO FLAGS (flag_code, flag_part, part_order, hint, difficulty, is_active) VALUES
  ('FL1_PART_A',
   '4442533430317B53514C5F',
   1,
   'Hex encoded. Decode to get first segment. Check part_order.',
   'hard',
   1);

-- Fake flag in FLAGS (decoy)
INSERT INTO FLAGS (flag_code, flag_part, part_order, hint, difficulty, is_active) VALUES
  ('FL_FAKE_01',
   '4442533430317B46414B455F756E696F6E5F73656C6563745F6C6F6C7D',
   1,
   'This looks promising...',
   'easy',
   0);

-- Another decoy in FLAGS (is_active=1 to trick attackers who filter is_active=1)
INSERT INTO FLAGS (flag_code, flag_part, part_order, hint, difficulty, is_active) VALUES
  ('FL_DECOY_B',
   'REJTNDAX7bm90X3RoaXNfb25l',
   2,
   'Wrong direction. Keep looking.',
   'medium',
   1);

-- ============================================================
-- ADMIN_SECRETS
-- ============================================================
-- Fake secret (decoy)
INSERT INTO ADMIN_SECRETS (secret_key, encrypted_value, note, is_active) VALUES
  ('sys_master_key',
   'FAKE_DBS401{wrong_key_try_again}',
   'System master - DO NOT SHARE',
   1);

-- Another fake (is_active=0)
INSERT INTO ADMIN_SECRETS (secret_key, encrypted_value, note, is_active) VALUES
  ('backup_recovery_key',
   'FAKE_DBS401{backup_key_not_flag}',
   'Backup recovery passphrase',
   0);

-- REAL Flag 3 Part A (extracted via Blind SQLi) - is_active=1
-- Value: DBS401{Bl1nd_B00l_
INSERT INTO ADMIN_SECRETS (secret_key, encrypted_value, note, is_active) VALUES
  ('oracle_flag_3_primary',
   'DBS401{Bl1nd_B00l_',
   'Oracle internal verification token - primary',
   1);

-- Decoy with similar key name
INSERT INTO ADMIN_SECRETS (secret_key, encrypted_value, note, is_active) VALUES
  ('oracle_flag_3_backup',
   'FAKE_DBS401{blind_wrong_key_xd}',
   'Oracle internal verification token - backup',
   1);

-- ============================================================
-- CONFIG_STORE
-- ============================================================
-- Public configs (decoys)
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('app_name', 'FPT Student Portal', 1);
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('app_version', '2.4.1', 1);
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('maintenance_mode', '0', 1);
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('max_login_attempts', '5', 1);

-- Private configs (not shown to user)
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('db_backup_schedule', 'daily_02:00', 0);

-- FLAG 1 Part C: base64("0r4cl3!}") = MHI0Y2wzIX0=
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('sys_alpha_marker', 'MHI0Y2wzIX0=', 0);

-- Decoy config (fake flag in config)
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('sys_beta_marker', 'REJTNDAX7bm90X2ZsYWdfaGVyZQ==', 0);

-- FLAG 3 Part B: hex("0r4cl3_X3rt!}") = 307234636C335F58337274217D
INSERT INTO CONFIG_STORE (config_key, config_value, is_public) VALUES
  ('oracle_flag_3_suffix', '307234636C335F58337274217D', 0);

-- ============================================================
-- FAKE_FLAGS
-- ============================================================
INSERT INTO FAKE_FLAGS (fake_code, fake_value, reason) VALUES
  ('FF001', 'DBS401{FAKE_union_select_lol}', 'Decoy for UNION-based SQLi path');
INSERT INTO FAKE_FLAGS (fake_code, fake_value, reason) VALUES
  ('FF002', 'DBS401{FAKE_IDOR_wrong_student}', 'Decoy for wrong student ID');
INSERT INTO FAKE_FLAGS (fake_code, fake_value, reason) VALUES
  ('FF003', 'DBS401{FAKE_blind_wrong_key_xd}', 'Decoy for wrong secret key');
INSERT INTO FAKE_FLAGS (fake_code, fake_value, reason) VALUES
  ('FF004', 'DBS401{not_the_real_flag_haha}', 'Generic decoy');
INSERT INTO FAKE_FLAGS (fake_code, fake_value, reason) VALUES
  ('FF005', 'DBS401{this_looks_right_but_isnt}', 'Trap for impatient attackers');

-- ============================================================
-- SYSTEM_HINTS
-- ============================================================
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('flag1_structure',
   'The flag is split into 3 parts. Part A is hex-encoded. Part B is reversed. Part C is base64.',
   'VULN1');
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('flag1_tables',
   'Look in FLAGS (part_order=1), AUDIT_LOGS (action=SYSTEM_AUDIT_CHECK), and CONFIG_STORE (key starts with sys_alpha)',
   'VULN1');
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('flag2_hint',
   'transcript_ref follows a pattern. Hidden students may not appear in normal lists. Check audit logs for log references.',
   'VULN2');
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('flag3_suffix_hint',
   'CONFIG_STORE holds the suffix. Key: oracle_flag_3_suffix. Value is hex-encoded. Combine with blind-extracted primary.',
   'VULN3');
INSERT INTO SYSTEM_HINTS (hint_key, hint_value, related_vuln) VALUES
  ('flag3_target_key',
   'The real secret key to extract is oracle_flag_3_primary. Beware of oracle_flag_3_backup (fake).',
   'VULN3');

-- ============================================================
-- FLAG_ARCHIVE (decoy table - looks like a flags table)
-- ============================================================
INSERT INTO FLAG_ARCHIVE (archive_code, archive_data) VALUES
  ('ARCH001', 'DBS401{FAKE_archived_flag_123}');
INSERT INTO FLAG_ARCHIVE (archive_code, archive_data) VALUES
  ('ARCH002', '4e6f745f746865_72656c5f666c6167');
INSERT INTO FLAG_ARCHIVE (archive_code, archive_data) VALUES
  ('ARCH003', 'This archive is a red herring. Wrong table.');

COMMIT;
/
