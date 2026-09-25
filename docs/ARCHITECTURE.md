# Kiến Trúc Hệ Thống – DBS401 Group 02
## FPT Student Portal – Oracle Security Lab

---

## 1. Stack Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT LAYER                            │
│   Browser / Burp Suite / curl / Python exploit script           │
│                    HTTP GET & POST requests                     │
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
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐     │
│  │config.php│  │login.php │  │search.php│  │store.php     │     │
│  │(session, │  │(secure – │  │[VULN 1]  │  │[VULN 2]      │     │
│  │ db conn) │  │ bind var)│  │SQLi      │  │Bus. Logic    │     │
│  └────┬─────┘  └──────────┘  └────┬─────┘  └──────┬───────┘     │
│       │                           │               │             │
│  ┌────┴───────────────────────────┴───────────────┴────────┐    │
│  │              OCI8 PHP Extension                         │    │
│  │     oci_connect() / oci_parse() / oci_execute()         │    │
│  └────────────────────────┬────────────────────────────────┘    │
│                           │                                     │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │             courses.php, schedule.php, tuition.php       │   │
│  │             grades.php (teacher/admin portal)            │   │
│  │             partner_config.php [VULN 3A] (hidden config) │   │
│  │             admin.php [VULN 3B], profile.php, dashboard  │   │
│  │             audit.php, secret_check.php (Legacy API)     │   │
│  └──────────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────────┘
                             │ TNS: localhost:1539/XEPDB1
                             │ User: dbs401_user / dbs401_pass
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              Oracle Instant Client (OCI8 Driver)                │
│              /usr/lib/oracle/21/client64/lib                    │
└────────────────────────────┬────────────────────────────────────┘
                             │ Oracle Net Protocol (TNS)
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│          Oracle Database XE 21c (or 23c Free)                   │
│          Service: XE  |  Port: 1539  |  Host: localhost         │
│                                                                 │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────────┐    │
│  │    USERS      │  │   STUDENTS    │  │     COURSES       │    │
│  │(auth + roles) │  │(profiles +    │  │(course catalog)   │    │
│  │               │  │ hidden_marker)│  │                   │    │
│  └───────────────┘  └───────────────┘  └───────────────────┘    │
│                                                                 │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────────┐    │
│  │ ENROLLMENTS   │  │  AUDIT_LOGS   │  │      FLAGS        │    │
│  │(transcript_ref│  │(metadata_note │  │(hex-encoded       │    │
│  │ internal_note │  │ = flag part B)│  │ flag part A)      │    │
│  │ admin_ref_id) │  │               │  │                   │    │
│  └───────────────┘  └───────────────┘  └───────────────────┘    │
│                                                                 │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────────┐    │
│  │ ADMIN_SECRETS │  │ CONFIG_STORE  │  │    FAKE_FLAGS     │    │
│  │(flag3 primary │  │(flag1 part C  │  │(decoys for        │    │
│  │ + fake keys)  │  │ flag3 suffix) │  │ all vulns)        │    │
│  └───────────────┘  └───────────────┘  └───────────────────┘    │
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
Browser                     PHP                       Oracle DB
  │                          │                             │
  │── POST login.php ───────►│                             │
  │   {username, password}   │                             │
  │                          │── SELECT username,          │
  │                          │   password_hash FROM        │
  │                          │   USERS WHERE username=:u ─►│
  │                          │◄─ row (hash) ───────────────│
  │                          │                             │
  │                          │── password_verify()         │
  │                          │   (bcrypt check)            │
  │                          │                             │
  │                          │── session_start()           │
  │                          │   $_SESSION['user_id']      │
  │                          │   $_SESSION['role']         │
  │                          │                             │
  │◄─ 302 → dashboard.php ───│                             │
  │   Set-Cookie: DBS401_SESSION=...                       │
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

## 4. Vulnerability 2 – Business Logic (Negative Quantity) Data Flow

```
+Attacker Browser               store.php               Oracle DB
      │                            │                      │
      │── POST quantity=-20000 ───►│                      │
      │                            │                      │
      │                            │  // Vulnerable logic:│
      │                            │  $cost = $qty * 100; │
      │                            │  // cost is -2M      │
      │                            │                      │
      │                            │── UPDATE CREDITS ───►│
      │                            │   credits - (-2M)    │
      │                            │◄─ success ───────────│
      │                            │                      │
      │◄─ Credits increased! ──────│                      │
      │                            │                      │
      │── Buy "Exam Leak" ────────►│                      │
      │◄─ HTML with Flag 2 ────────│                      │
      │                            │                      │
      ▼ Attacker result:
        FLAG 2 = "DBS401{LOGIC_GURU_2024}"
```

---

## 5. Vulnerability 3 – Broken Access Control + Supply Chain Data Flow

```
Attacker Student          partner_config.php       Oracle DB
     │                          │                      │
     │── Login as student ─────►│                      │
     │                          │                      │
     │── POST manifest_url ────►│                      │
     │   http://attacker:8081   │── UPDATE config ────►│
     │                          │   update_url         │
     │◄─ URL updated ───────────│                      │
     │
     │    Attacker HTTP Server
     │    └── /manifest.json
     │        { version: 9.9.9,
     │          flag_part: first_hex_half }
     │
Admin Browser              admin.php              Partner/Attacker
     │                          │                      │
     │── ?check_updates=1 ─────►│                      │
     │                          │── SELECT update_url ►│ Oracle DB
     │                          │◄─ attacker URL ──────│
     │                          │── Fetch Manifest ───►│
     │                          │◄─ JSON version 9.9.9 │
     │                          │                      │
     │                          │── combine:           │
     │                          │   manifest flag_part │
     │                          │ + server-side suffix │
     │                          │── hex2bin()          │
     │◄─ Display plaintext flag │                      │
     │
     ▼ Attacker/Admin result:
       DBS401{5upp1y_Ch41n_P0150n1ng_0912}
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
│   └── action=SECRET_VAULT_ACCESS                ← DECOY log with fake data
│
├── CONFIG_STORE
│   ├── sys_alpha_marker
│   │   └── config_value = "MHI0Y2wzIX0="  ← base64("0r4cl3!}")
│   └── update_url (VULN 3 Target)          ← Target of Supply Chain Poisoning
│
├── STORE ITEMS (Logic)
│   └── "Exam Leak 2024" 
│       └── Contains: DBS401{LOGIC_GURU_2024}
│
├── PARTNER MANIFEST (External)
│   └── flag_part = "4442533430317b3575707031795f436834"
│       └── first hex half only
│
├── admin.php (server-side only)
│   └── FLAG3_LOCAL_HEX_SUFFIX = "316e5f50303135306e316e675f303931327d"
│       └── combine + hex_decode → DBS401{5upp1y_Ch41n_P0150n1ng_0912}
│
├── ENROLLMENTS
│   └── (Standard academic records, ownership checks enforced)
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
| courses.php | ✅ (enroll/drop) | ✅ (view) | ✅ (view) | None (Secure) |
| schedule.php | ✅ (own) | ✅ (view) | ✅ (view) | None (Secure) |
| grades.php | ❌ (redirect) | ✅ (grading) | ✅ (grading) | None (Secure) |
| tuition.php | ✅ (own) | ✅ (view) | ✅ (view) | None (Secure) |
| search.php | ✅ | ✅ | ✅ | **VULN 1** SQLi |
| profile.php | ✅ (own) | ✅ | ✅ | None |
| store.php | ✅ | ✅ | ✅ | **VULN 2** Business Logic |
| transcript.php | ✅ (own) | ✅ | ✅ | None (Secure) |
| audit.php | ✅ (own) | ❌ | ✅ (all) | None (Secure) |
| partner_config.php | ✅ (misconfigured) | ✅ | ✅ | **VULN 3A** Broken Access Control |
| admin.php | ❌ | ❌ | ✅ | **VULN 3B** Supply Chain |
| secret_check.php | ✅ | ✅ | ✅ | None (Legacy Decoy API) |

---

## 8. Network Configuration

```
┌─────────────────────────────────────────────┐
│              Ubuntu Machine                 │
│                                             │
│  eth0/ens33: 192.168.x.x (LAN IP)           │
│  lo:         127.0.0.1                      │
│                                             │
│  Apache2     : 0.0.0.0:80                   │
│  Oracle XE   : 127.0.0.1:1539               │
│  Oracle APEX : (optional) :5500             │
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

Oracle chỉ bind localhost (1539) → không expose ra ngoài LAN → an toàn cho lab.
