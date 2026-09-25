# 🎓 OracleSecLab – Vulnerable Oracle Web Application
### Enterprise Oracle Database Security & Penetration Testing Practice Lab

<p align="center">
  <a href="#-tech-stack"><img src="https://img.shields.io/badge/PHP-8.1-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.1" /></a>
  <a href="#-tech-stack"><img src="https://img.shields.io/badge/Oracle_DB-XE_21c_%7C_23c_Free-F80000?style=for-the-badge&logo=oracle&logoColor=white" alt="Oracle Database" /></a>
  <a href="#-tech-stack"><img src="https://img.shields.io/badge/Apache-2.4-D22128?style=for-the-badge&logo=apache&logoColor=white" alt="Apache 2.4" /></a>
  <a href="#-tech-stack"><img src="https://img.shields.io/badge/Ubuntu-22.04_LTS-E95420?style=for-the-badge&logo=ubuntu&logoColor=white" alt="Ubuntu Linux" /></a>
  <a href="#-tech-stack"><img src="https://img.shields.io/badge/Python-3.x-3776AB?style=for-the-badge&logo=python&logoColor=white" alt="Python 3" /></a>
  <a href="#-vulnerability--ctf-challenge-matrix"><img src="https://img.shields.io/badge/OWASP-Top_10_2021-00599C?style=for-the-badge&logo=owasp&logoColor=white" alt="OWASP Top 10" /></a>
  <a href="#-vulnerability--ctf-challenge-matrix"><img src="https://img.shields.io/badge/Vulnerabilities-SQLi_%7C_Logic_%7C_Supply_Chain-blueviolet?style=for-the-badge" alt="Vulnerabilities" /></a>
  <a href="#-license--attribution"><img src="https://img.shields.io/badge/License-MIT-success?style=for-the-badge" alt="License MIT" /></a>
</p>

> ⚠️ **LEGAL & ETHICAL DISCLAIMER:**  
> This application is deliberately vulnerable and is intended solely for educational, penetration testing, and security training purposes in private, isolated lab environments. Do **NOT** deploy this application to public internet-facing servers or production infrastructure.

---

## 📖 Overview

**OracleSecLab** is an enterprise-modeled web application designed specifically for **Oracle Database Security** research, penetration testing practice, and CTF (Capture The Flag) competitions. 

While many popular vulnerable web applications (like DVWA, WebGoat, or Juice Shop) focus on MySQL, PostgreSQL, or SQLite, **Oracle Database** has unique architectural characteristics, SQL dialect specifics (`DUAL`, `ROWNUM`, `FETCH FIRST`, `USER_TABLES`, `SYS_CONTEXT`), and distinct security challenges. 

This project simulates a realistic university portal (**FPT Student Portal**), featuring complete academic workflows alongside three intentional, deeply layered vulnerabilities.

---

## 💻 Tech Stack

| Component | Technology | Description |
|:---|:---|:---|
| **Backend Language** | PHP 8.1+ | Server-side scripting runtime with session management |
| **Database Engine** | Oracle Database XE 21c / 23c Free | Enterprise RDBMS running on Pluggable Database (`XEPDB1` / `FREEPDB1`) |
| **Database Driver** | PHP OCI8 & Oracle Instant Client 21c | Native C-level Oracle Call Interface extension |
| **Web Server** | Apache 2.4 (`mod_php`, `mod_rewrite`) | HTTP server with directory alias & access control |
| **Operating System** | Ubuntu 20.04 / 22.04 / 24.04 LTS | Linux target environment |
| **Exploit Tooling** | Python 3 (`requests`, `urllib3`) | Automated PoC exploits and flag assembly decoders |
| **Security Standards** | OWASP Top 10 (A01: BAC, A03: Injection, A06: Supply Chain) | Realistic enterprise attack chains and secure remediations |

---

## 📁 Repository Structure

```
oracle-sec-lab/
├── config.php                  # Database connection (OCI8) & global configuration
├── index.php                   # Portal landing & role redirection
├── login.php                   # Authentication (bcrypt, session management)
├── logout.php                  # Session destruction & cookie cleanup
├── dashboard.php               # Student / Faculty / Admin dashboard & notice board
├── courses.php                 # Academic course catalog & registration (Enroll/Drop)
├── schedule.php                # Weekly class timetable & room schedule
├── grades.php                  # Faculty grading portal & roster scoring
├── tuition.php                 # Tuition fee billing, e-Invoices & virtual accounts
├── search.php                  # [VULN 1] Oracle SQL Injection (UNION-based)
├── profile.php                 # User & student profile viewer
├── store.php                   # [VULN 2] Business Logic Flaw (Negative Quantity)
├── transcript.php              # Official academic transcript record viewer
├── audit.php                   # Comprehensive security audit trail & event logger
├── partner_config.php          # [VULN 3A] Hidden partner configuration (Broken Access Control)
├── admin.php                   # [VULN 3B] Admin panel & Supply Chain update trigger
├── secret_check.php            # Secret key API (Legacy decoy challenge)
├── inc_navbar.php              # Shared role-aware navigation bar
├── style.css                   # Responsive UI stylesheet
├── database/
│   ├── schema.sql              # Oracle DDL schema definition
│   ├── seed.sql                # Seed data, courses, enrollments & fragmented flags
│   ├── fix_refs.sql            # Audit log foreign key alignment helper
│   └── init_passwords.php      # Password hash generator (bcrypt cost 12)
├── secure_versions/
│   ├── search_secure.php       # Patched Vuln 1 (Bind variables & character whitelist)
│   ├── store_secure.php        # Patched Vuln 2 (Server-side positive quantity validation)
│   ├── partner_config_secure.php # Patched Vuln 3A (Strict RBAC & approved URL allowlist)
│   ├── admin_update_secure.php # Patched Vuln 3B (Update source allowlist & signature check)
│   ├── transcript_secure.php   # IDOR-protected transcript viewer
│   └── secret_check_secure.php # Parameterized secret verification API
├── tools/
│   ├── exploit_flag3_local.py  # Fully automated PoC exploit for Vulnerability 3
│   ├── decode_helper.py        # Multi-stage flag decoding & assembly utility
│   └── verify_setup.php        # Pre-flight environment & challenge verification
├── docs/
│   ├── ARCHITECTURE.md         # Comprehensive system & data flow architecture
│   ├── DEPLOYMENT_GUIDE.md     # Step-by-step deployment guide for Ubuntu Linux
│   ├── ANSWER_KEY.md           # Official challenge walkthrough & solution guide
│   └── ORACLE_PAYLOAD_CHEATSHEET.md # Oracle-specific SQL injection reference
├── setup.sh                    # Automated installation & deployment script
├── LICENSE                     # MIT License terms and copyright
└── README.md                   # This repository documentation
```

---

## 🎯 Vulnerability & CTF Challenge Matrix

The application features **3 core vulnerabilities** with multi-step CTF flags, alongside multiple decoys to challenge automated scanners:

| Challenge | Vulnerability Type | Vulnerable Endpoint | Difficulty (Bug) | Flag Complexity | Flag Format |
|:---|:---|:---|:---:|:---:|:---|
| **Challenge 1** | Oracle UNION SQL Injection | `search.php?q=` | Easy | Very Hard | `DBS401{SQL_1nj3ct10n_0r4cl3!}` |
| **Challenge 2** | Insecure Business Logic | `store.php` (POST) | Hard | Very Hard | `DBS401{LOGIC_GURU_2024}` |
| **Challenge 3** | Access Control & Supply Chain | `partner_config.php` + `admin.php` | Hard | Very Hard | `DBS401{5upp1y_Ch41n_P0150n1ng_0912}` |

### Summary of Challenges:
1. **Challenge 1 – Fragmented Oracle SQL Injection:**
   * Vulnerable parameter `?q=` at `search.php` uses string concatenation.
   * Weak DDL blacklist (`DROP`, `ALTER`) can be easily bypassed using `UNION SELECT`.
   * Flag 1 is split across three distinct tables with different encodings:
     * **Part A (Hex):** In table `FLAGS` (`FL1_PART_A`).
     * **Part B (Reverse String):** In table `AUDIT_LOGS` (`metadata_note`).
     * **Part C (Base64):** In table `CONFIG_STORE` (`sys_alpha_marker`).
2. **Challenge 2 – Credits Manipulation (Negative Quantity):**
   * Course material store (`store.php`) validates `credits >= cost`, but forgets to validate `quantity > 0`.
   * Ordering a negative quantity (`-20,000`) converts deduction into credit addition (`credits - (-2,000,000) = credits + 2,000,000`).
   * Accumulating credits allows purchasing the restricted "Exam Leak 2024 (CLASSIFIED)" item to reveal Flag 2.
3. **Challenge 3 – Supply Chain Poisoning via Broken Access Control:**
   * Hidden endpoint `partner_config.php` fails to enforce admin authorization, allowing any student to overwrite `CONFIG_STORE.update_url`.
   * When an administrator checks for updates (`admin.php?check_updates=1`), the server fetches an attacker-controlled JSON manifest.
   * If the manifest version is higher than `APP_VERSION`, `admin.php` combines the manifest's hex fragment with a server-side hardcoded suffix, revealing Flag 3.

---

## 🏛️ Application Modules

The application is structured as a full-featured university portal:
* **Academic Services:**
  * **Course Catalog & Registration (`courses.php`):** Browse curriculum, enroll in open courses, and drop courses before midterm.
  * **Weekly Class Timetable (`schedule.php`):** Interactive weekly schedule grid (Monday–Saturday, Slots 1–4) with classroom allocation.
  * **Faculty Grading Portal (`grades.php`):** Instructors can view student class rosters and publish official grades.
  * **Tuition & Billing (`tuition.php`):** Credit-based fee calculation, e-Invoices, and virtual bank account integration.
  * **Transcript Viewer (`transcript.php`):** Secure transcript lookup with reference IDs.
* **Administrative Services:**
  * **User Management (`admin.php`):** Full CRUD for students, teachers, and admins with cascade integrity.
  * **Security Audit Trail (`audit.php`):** System and user action audit log with IP and metadata tracking.
* **Campus Life:**
  * **Material Store (`store.php`):** Academic books and digital guides exchange.
  * **Student Directory (`search.php`):** Peer lookup.

---

## 👤 Pre-configured Accounts

| Username | Password | Role | Description |
|:---|:---|:---:|:---|
| `admin` | `Admin@DBS401!2024` | **Admin** | Full system administration, user management, update trigger |
| `teacher1` | `Teacher@123` | **Teacher** | Faculty instructor, course grading portal, student search |
| `student1` | `Student@123` | **Student** | Active student with 150 credits, enrolled in core courses |
| `student2` | `Student@123` | **Student** | Active student with 50 credits |
| `student3` | `Student@123` | **Student** | Active student with 200 credits |

---

## 🚀 Quick Start & Installation

### Prerequisites
* **Operating System:** Ubuntu 20.04 / 22.04 / 24.04 LTS
* **RAM:** Minimum 4GB (Oracle XE requires ~2GB)
* **Storage:** Minimum 20GB free disk space
* **Core Components:** Apache2, PHP 8.1+ with OCI8 extension, Oracle Database XE 21c (or Oracle 23c Free), Python 3.

### Automated Setup
Clone the repository and execute the installer with administrative privileges:
```bash
git clone https://github.com/Khanh1916/oracle-sec-lab.git /var/www/html/dbs401-oracle-app
cd /var/www/html/dbs401-oracle-app
sudo bash setup.sh
```

### Manual Database Import
If setting up Oracle Database manually:
```bash
# Connect as sysdba and create the database user
sqlplus sys/YOUR_SYS_PASSWORD@localhost:1539/XEPDB1 as sysdba << 'EOF'
CREATE USER dbs401_user IDENTIFIED BY dbs401_pass;
GRANT CONNECT, RESOURCE, CREATE SESSION TO dbs401_user;
GRANT CREATE TABLE, CREATE SEQUENCE, CREATE VIEW TO dbs401_user;
ALTER USER dbs401_user QUOTA UNLIMITED ON USERS;
EXIT;
EOF

# Import schema and seed data
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/schema.sql
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/seed.sql

# Generate bcrypt password hashes
php database/init_passwords.php
```

### Accessing the Portal
* **Local:** `http://127.0.0.1/dbs401-oracle-app`
* **LAN:** `http://<SERVER-IP>/dbs401-oracle-app`

---

## 🛠️ Testing & Verification Tools

The `tools/` directory provides utilities to verify, exploit, and decode challenges:

```bash
# 1. Pre-flight verification (ensures environment & files are intact)
php tools/verify_setup.php

# 2. Automated Exploit for Challenge 3 (Supply Chain Poisoning)
python3 tools/exploit_flag3_local.py --target-host 127.0.0.1 --target-port 80

# 3. Decode & Assemble all CTF flags
python3 tools/decode_helper.py --assemble
```

---

## 🛡️ Secure Versions & Remediation

Each vulnerable endpoint has a corresponding hardened implementation in `secure_versions/`:

| Vulnerable Endpoint | Secure Implementation | Defense Mechanisms Applied |
|:---|:---|:---|
| `search.php` | `secure_versions/search_secure.php` | Parameterized queries with bind variables (`:kw`), input length constraint, strict regex whitelist (`^[\p{L}\p{N}\s\-_.]+$`). |
| `store.php` | `secure_versions/store_secure.php` | Server-side positive integer validation (`$qty > 0`), atomic credit verification. |
| `partner_config.php` | `secure_versions/partner_config_secure.php` | Role-based authorization (`$_SESSION['role'] === 'admin'`), strict domain allowlist. |
| `admin.php` | `secure_versions/admin_update_secure.php` | Update URL whitelist verification, rejection of untrusted third-party manifest payloads. |

---

## 📚 Technical Documentation

For in-depth architectural and exploitation details, consult the `docs/` directory:
* [System Architecture](docs/ARCHITECTURE.md) – Component interactions, network topology, and RBAC matrix.
* [Deployment Guide](docs/DEPLOYMENT_GUIDE.md) – Complete step-by-step installation instructions for Oracle XE and OCI8 on Ubuntu.
* [Challenge Solution Guide](docs/ANSWER_KEY.md) – Complete walkthroughs, SQL payloads, and decoy explanations.
* [Oracle SQLi Cheatsheet](docs/ORACLE_PAYLOAD_CHEATSHEET.md) – Reference sheet for Oracle-specific injection techniques.

---

## 👥 Authors & Maintainers

* **khanhnn** – Project Architecture & Security Research
* **Cao Thanh Lam** – Full-Stack Development & CTF Engineering

---

## 📄 License & Attribution

This project is licensed under the **MIT License** – see the [LICENSE](LICENSE) file for complete details.

Copyright (c) 2024-2026 **khanhnn**, **Cao Thanh Lam**. Released for database security education and offensive/defensive research.
