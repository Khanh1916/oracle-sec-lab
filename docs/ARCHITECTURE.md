# Kiến Trúc Hệ Thống – DBS401 Group 07
## FPT Student Portal – Oracle Security Lab

---

## 1. Stack Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT LAYER                            │
│   Browser / Burp Suite / curl / Python exploit script           │
│                    HTTP GET & POST requests                      │
└────────────────────────────┬────────────────────────────────────┘
                             │ Port 80 (HTTP)
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      WEB SERVER LAYER                           │
│               Apache 2.4 (Ubuntu 22.04)                         │
│   Alias: /dbs401-oracle-app → /var/www/html/dbs401-oracle-app   │
│   mod_php8.1, mod_rewrite, .htaccess                            │
└────────────────────────────┬────────────────────────────────────┘
                             │ PHP file execution
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER (PHP 8.1)                  │
│                                                                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐   │
│  │config.php│  │login.php │  │search.php│  │transcript.php│   │
│  │(session, │  │(secure – │  │[VULN 1]  │  │[VULN 2]      │   │
│  │ db conn) │  │ bind var)│  │SQLi      │  │IDOR          │   │
│  └────┬─────┘  └──────────┘  └────┬─────┘  └──────┬───────┘   │
│       │                           │               │            │
│  ┌────┴────────────────────────────┴───────────────┴────────┐   │
│  │              OCI8 PHP Extension                          │   │
│  │     oci_connect() / oci_parse() / oci_execute()          │   │
│  └────────────────────────┬─────────────────────────────────┘   │
│                           │                                     │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │             secret_check.php [VULN 3]                    │   │
│  │             audit.php  [VULN 2 secondary]                │   │
│  │             admin.php, profile.php, dashboard.php        │   │
│  └──────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │ TNS: localhost:1521/XE
                             │ User: dbs401_user / dbs401_pass
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              Oracle Instant Client (OCI8 Driver)                │
│              /usr/lib/oracle/21/client64/lib                     │
└────────────────────────────┬────────────────────────────────────┘
                             │ Oracle Net Protocol (TNS)
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│          Oracle Database XE 21c (or 23c Free)                   │
│          Service: XE  |  Port: 1521  |  Host: localhost          │
│                                                                 │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────────┐   │
│  │    USERS      │  │   STUDENTS    │  │     COURSES       │   │
│  │(auth + roles) │  │(profiles +    │  │(course catalog)   │   │
│  │               │  │ hidden_marker)│  │                   │   │
│  └───────────────┘  └───────────────┘  └───────────────────┘   │
│                                                                 │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────────┐   │
│  │ ENROLLMENTS   │  │  AUDIT_LOGS   │  │      FLAGS        │   │
│  │(transcript_ref│  │(metadata_note │  │(hex-encoded       │   │
│  │ internal_note │  │ = flag part B)│  │ flag part A)      │   │
│  │ admin_ref_id) │  │               │  │                   │   │
│  └───────────────┘  └───────────────┘  └───────────────────┘   │
│                                                                 │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────────┐   │
│  │ ADMIN_SECRETS │  │ CONFIG_STORE  │  │    FAKE_FLAGS     │   │
│  │(flag3 primary │  │(flag1 part C  │  │(decoys for        │   │
│  │ + fake keys)  │  │ flag3 suffix) │  │ all vulns)        │   │
│  └───────────────┘  └───────────────┘  └───────────────────┘   │
│                                                                 │
│  ┌───────────────┐  ┌───────────────────────────────────────┐   │
│  │ SYSTEM_HINTS  │  │         FLAG_ARCHIVE (DECOY)          │   │
│  │(indirect CTF  │  │   (looks like flags, contains fakes)  │   │
│  │ hints)        │  │                                       │   │
│  └───────────────┘  └───────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Authentication & Session Flow

```
Browser                    PHP                       Oracle DB
  │                         │                            │
  │── POST login.php ───────►│                            │
  │   {username, password}  │                            │
  │                         │── SELECT username,         │
  │                         │   password_hash FROM       │
  │                         │   USERS WHERE username=:u ─►│
  │                         │◄─ row (hash) ──────────────│
  │                         │                            │
  │                         │── password_verify()        │
  │                         │   (bcrypt check)           │
  │                         │                            │
  │                         │── session_start()          │
  │                         │   $_SESSION['user_id']     │
  │                         │   $_SESSION['role']        │
  │                         │                            │
  │◄─ 302 → dashboard.php ──│                            │
  │   Set-Cookie: DBS401_SESSION=...                     │
```

---

## 3. Vulnerability 1 – SQL Injection Data Flow

```
Attacker Browser               search.php              Oracle DB
     │                              │                      │
     │── GET ?q=' UNION... ────────►│                      │
     │                              │                      │
     │                              │  // Vulnerable:      │
     │                              │  $sql = "SELECT ...  │
     │                              │  WHERE name LIKE     │
     │                              │  '%' UNION ...'";    │
     │                              │                      │
     │                              │── Injected SQL ─────►│
     │                              │                      │ ← Queries FLAGS,
     │                              │                      │   AUDIT_LOGS,
     │                              │                      │   CONFIG_STORE
     │                              │◄─ flag_parts ────────│
     │◄─ HTML with data ────────────│                      │
     │                              │                      │
     ▼ Attacker decode:             │                      │
       hex_decode(Part A) = "DBS401{SQL_"
       reverse(Part B)    = "1nj3ct10n_"
       b64_decode(Part C) = "0r4cl3!}"
       FLAG 1 = "DBS401{SQL_1nj3ct10n_0r4cl3!}"
```

---

## 4. Vulnerability 2 – IDOR Data Flow

```
Attacker Browser            transcript.php           Oracle DB
     │                            │                      │
     │── GET ?ref=TXN-001-... ───►│                      │
     │   (observe own transcript) │                      │
     │◄─ TXN-001-2024-S1 data ───│                      │
     │                            │                      │
     │ Infer pattern:             │                      │
     │ TXN-{id:3}-{year}-S{sem}  │                      │
     │                            │                      │
     │── GET ?ref=TXN-099-... ───►│                      │
     │                            │── SELECT e.*, s.*    │
     │                            │   WHERE              │
     │                            │   transcript_ref=:ref│
     │                            │   (NO user_id check!)│
     │                            │◄─ hidden student row ─│
     │◄─ internal_note (b64) ─────│                      │
     │◄─ admin_ref_id = N ─────── │                      │
     │                            │                      │
     │── GET audit.php?log_id=N ─►│                      │
     │                            │── SELECT * FROM      │
     │                            │   AUDIT_LOGS         │
     │                            │   WHERE log_id=:lid  │
     │                            │   (NO ownership!)    │
     │                            │◄─ metadata_note ──── │
     │◄─ fragment_b reversed ─────│                      │
     │                            │                      │
     ▼ Attacker decode:
       b64_decode(Part A) = "DBS401{1DOR_Tr4ns_"
       reverse(Part B)    = "4cc3ss_Fl4w!}"
       FLAG 2 = "DBS401{1DOR_Tr4ns_4cc3ss_Fl4w!}"
```

---

## 5. Vulnerability 3 – Blind SQLi Data Flow

```
Attacker Browser         secret_check.php          Oracle DB
     │                          │                      │
     │── GET ?key=X' AND ... ──►│                      │
     │                          │                      │
     │                          │  // Vulnerable:      │
     │                          │  $sql = "SELECT CNT  │
     │                          │  FROM ADMIN_SECRETS  │
     │                          │  WHERE key='$key'    │
     │                          │  AND is_active=1";   │
     │                          │── injected SQL ─────►│
     │                          │◄─ count (0 or 1) ── │
     │◄─ {"status":"found"} ────│                      │ ← TRUE condition
     │                          │                      │
     │ Repeat for each char position:
     │── GET ?key=oracle_flag_3_primary'               │
     │         AND ASCII(SUBSTR(encrypted_value,N,1))  │
     │         =M AND '1'='1 ──────────────────────────►│
     │◄─ found / not_found ────────────────────────── │
     │                          │                      │
     ▼ After 18 iterations:
       Part A = "DBS401{Bl1nd_B00l_"  (from ADMIN_SECRETS)
       Part B = hex_decode(CONFIG_STORE.oracle_flag_3_suffix)
             = "0r4cl3_X3rt!}"
       FLAG 3 = "DBS401{Bl1nd_B00l_0r4cl3_X3rt!}"
```

---

## 6. Flag Storage Map

```
Oracle Database (dbs401_user schema)
│
├── FLAGS
│   ├── FL1_PART_A (is_active=1)
│   │   └── flag_part = "4442533430317B53514C5F"  ← hex("DBS401{SQL_")
│   ├── FL_FAKE_01 (is_active=0)                  ← DECOY (inactive)
│   └── FL_DECOY_B (is_active=1)                  ← DECOY (active, lures attacker)
│
├── AUDIT_LOGS
│   ├── action=SYSTEM_AUDIT_CHECK
│   │   └── metadata_note.fragment = "_n01tc3jn1"  ← reverse("1nj3ct10n_")
│   ├── action=TRANSCRIPT_EXPORT_HIDDEN
│   │   └── metadata_note.fragment_b = "}!w4lF_ss3cc4"  ← reverse("4cc3ss_Fl4w!}")
│   └── action=SECRET_VAULT_ACCESS                ← DECOY log with fake data
│
├── CONFIG_STORE
│   ├── sys_alpha_marker
│   │   └── config_value = "MHI0Y2wzIX0="  ← base64("0r4cl3!}")
│   ├── sys_beta_marker                     ← DECOY key
│   └── oracle_flag_3_suffix
│       └── config_value = "307234636C335F58337274217D"  ← hex("0r4cl3_X3rt!}")
│
├── ADMIN_SECRETS
│   ├── sys_master_key (FAKE)              ← DECOY
│   ├── backup_recovery_key (FAKE, inactive) ← DECOY
│   ├── oracle_flag_3_primary (REAL)
│   │   └── encrypted_value = "DBS401{Bl1nd_B00l_"  ← extract via blind SQLi
│   └── oracle_flag_3_backup (FAKE)        ← DECOY (same name pattern)
│
├── ENROLLMENTS
│   └── TXN-099-2024-S1 (hidden student)
│       ├── internal_note = "CLASSIFIED_DATA: REJTNDAxezFET1JfVHI0bnNf"
│       │   └── base64_decode → "DBS401{1DOR_Tr4ns_"
│       └── admin_ref_id → AUDIT_LOGS.log_id (TRANSCRIPT_EXPORT_HIDDEN)
│
├── FAKE_FLAGS (table)        ← FF001..FF005 decoys
├── FLAG_ARCHIVE (table)      ← DECOY TABLE (looks like FLAGS)
└── SYSTEM_HINTS (table)      ← indirect hints for CTF players
```

---

## 7. Role-Based Access Control

| Endpoint | student | teacher | admin | Lỗ hổng |
|----------|---------|---------|-------|---------|
| login.php | ✅ | ✅ | ✅ | None (secure) |
| dashboard.php | ✅ | ✅ | ✅ | None |
| search.php | ✅ | ✅ | ✅ | **VULN 1** SQLi |
| profile.php | ✅ (own) | ✅ | ✅ | None |
| transcript.php | ✅ | ✅ | ✅ | **VULN 2** IDOR |
| audit.php | ✅ (own) | ❌ | ✅ (all) | **VULN 2** IDOR |
| admin.php | ❌ | ❌ | ✅ | None |
| secret_check.php | ✅ | ✅ | ✅ | **VULN 3** Blind SQLi |

---

## 8. Network Configuration

```
┌─────────────────────────────────────────────┐
│              Ubuntu Machine                 │
│                                             │
│  eth0/ens33: 192.168.x.x (LAN IP)          │
│  lo:         127.0.0.1                      │
│                                             │
│  Apache2     : 0.0.0.0:80                  │
│  Oracle XE   : 127.0.0.1:1521              │
│  Oracle APEX : (optional) :5500            │
│                                             │
└──────────────┬──────────────────────────────┘
               │
               │ LAN (192.168.x.0/24)
               │
  ┌────────────┴────────────┐
  │  Other PCs in same LAN  │
  │  Access: http://192.168.x.x/dbs401-oracle-app
  └─────────────────────────┘
```

**Firewall rule** (if UFW enabled):
```bash
sudo ufw allow 80/tcp comment 'DBS401 lab'
```

Oracle chỉ bind localhost (1521) → không expose ra ngoài LAN → an toàn cho lab.
