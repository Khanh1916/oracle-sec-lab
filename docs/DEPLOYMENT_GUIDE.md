# Deployment Guide – OracleSecLab
## Vulnerable Oracle Database Web Application Lab

> Detailed laboratory deployment and configuration guide for Ubuntu Linux environments.

---

## 📋 Quick Table of Contents

| Step | Section | Estimated Time |
|:---:|:---|:---:|
| **1** | [Ubuntu Environment Preparation](#step-1--ubuntu-environment-preparation) | 10 mins |
| **2** | [Apache2 Web Server Installation](#step-2--apache2-web-server-installation) | 5 mins |
| **3** | [PHP 8.1 & Extensions Installation](#step-3--php-81--extensions-installation) | 5 mins |
| **4** | [Oracle Database XE 21c Installation](#step-4--oracle-database-xe-21c-installation) | 30–60 mins |
| **5** | [Oracle Instant Client & OCI8 Setup](#step-5--oracle-instant-client--oci8-setup) | 20 mins |
| **6** | [Oracle User Provisioning & Database Import](#step-6--oracle-user-provisioning--database-import) | 10 mins |
| **7** | [Web Application Deployment](#step-7--web-application-deployment) | 5 mins |
| **8** | [Testing & Verification](#step-8--testing--verification) | 10 mins |
| **–** | [Quick Troubleshooting Guide](#quick-troubleshooting-guide) | Reference |
| **–** | [Finding Host LAN IP for Remote Lab Testing](#finding-host-lan-ip-for-remote-lab-testing) | Reference |

---

## STEP 1 – Ubuntu Environment Preparation

**Recommended Operating System:** Ubuntu 22.04 LTS (x86_64) running on VirtualBox, VMware, or bare-metal hardware.

```bash
# Update package repositories and installed packages
sudo apt-get update && sudo apt-get upgrade -y

# Install essential system utilities
sudo apt-get install -y curl wget unzip git net-tools lsof
```

Verify your Ubuntu release version:
```bash
lsb_release -a
```

---

## STEP 2 – Apache2 Web Server Installation

```bash
# Install Apache2 HTTP Server
sudo apt-get install -y apache2

# Start and enable Apache service on boot
sudo systemctl start apache2
sudo systemctl enable apache2

# Verify Apache status
sudo systemctl status apache2
curl -s http://127.0.0.1 | grep -o 'Apache.*HTML'
```

✅ **Verification:** Output containing `"Apache...HTML"` confirms that the server is operational.

---

## STEP 3 – PHP 8.1 & Extensions Installation

```bash
# Add the Ondrej Sury PHP PPA (ensures PHP 8.1 support on Ubuntu)
sudo apt-get install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update

# Install PHP 8.1 and required modules for development and OCI8 compilation
sudo apt-get install -y \
    php8.1 \
    libapache2-mod-php8.1 \
    php8.1-cli \
    php8.1-common \
    php8.1-dev \
    php-pear \
    build-essential

# Enable PHP 8.1 Apache module
sudo a2enmod php8.1
sudo systemctl restart apache2

# Verify PHP installation
php -v
php -m | grep -i "json\|session\|mbstring"
```

✅ **Verification:** `php -v` must report `PHP 8.1.x`.

---

## STEP 4 – Oracle Database XE 21c Installation

### 4.1 Download Oracle Database XE
Navigate to: [Oracle Database XE Downloads](https://www.oracle.com/database/technologies/xe-downloads.html)  
Download file: `oracle-database-xe-21c-1.0-1.ol8.x86_64.rpm`

*(An Oracle free single-sign-on account is required to download).*

> **Alternative:** [Oracle Database 23c Free](https://www.oracle.com/database/free/) may also be utilized as a lightweight modern alternative.

### 4.2 Convert and Install RPM Package on Ubuntu

```bash
# Install package conversion and native asynchronous I/O dependencies
sudo apt-get install -y alien libaio1 bc libaio-dev

# Convert RPM to Debian package format (~5-10 minutes)
sudo alien --to-deb --scripts oracle-database-xe-21c-1.0-1.ol8.x86_64.rpm

# Install generated DEB package (~10-20 minutes)
sudo dpkg -i oracle-database-xe-21c_21.0-2_amd64.deb

# Resolve any missing package dependencies if prompted:
sudo apt-get install -f
```

### 4.3 Configure Oracle XE

```bash
sudo /etc/init.d/oracle-xe-21c configure
```

Specify values when prompted:
* **Oracle Database XE password:** `oracle` (or your preferred admin password; record this for database creation).
* **Confirm password:** `oracle`
* **Listener Port:** `1539` (or press Enter to retain the default).

### 4.4 Set System Environment Variables

Append Oracle environment exports to `~/.bashrc`:
```bash
cat >> ~/.bashrc << 'EOF'

# Oracle Database XE Environment Variables
export ORACLE_HOME=/opt/oracle/product/21c/dbhomeXE
export ORACLE_SID=XE
export PATH=$ORACLE_HOME/bin:$PATH
export LD_LIBRARY_PATH=$ORACLE_HOME/lib:$LD_LIBRARY_PATH
EOF

source ~/.bashrc
```

### 4.5 Start and Verify Oracle Database Service

```bash
# Enable and start Oracle XE service
sudo systemctl start oracle-xe-21c
sudo systemctl enable oracle-xe-21c

# Verify listener status
lsnrctl status

# Test local DBA connectivity
sqlplus / as sysdba << 'EOF'
SELECT STATUS FROM V$INSTANCE;
EXIT;
EOF
```

✅ **Verification:** Output must report `STATUS: OPEN`.

---

## STEP 5 – Oracle Instant Client & OCI8 Setup

### 5.1 Download Oracle Instant Client Packages
Visit: [Oracle Instant Client for Linux x86-64](https://www.oracle.com/database/technologies/instant-client/linux-x86-64-downloads.html)

Download both packages (version 21.x):
* `oracle-instantclient-basiclite-21.x.x.x-1.x86_64.rpm`
* `oracle-instantclient-devel-21.x.x.x-1.x86_64.rpm`

### 5.2 Convert and Install Client Packages

```bash
# Convert packages to DEB format
sudo alien --to-deb oracle-instantclient-basiclite-21.*.rpm
sudo alien --to-deb oracle-instantclient-devel-21.*.rpm

# Install Debian packages
sudo dpkg -i oracle-instantclient-basiclite_*.deb
sudo dpkg -i oracle-instantclient-devel_*.deb

# Configure dynamic linker path for Oracle client libraries
echo "/usr/lib/oracle/21/client64/lib" | sudo tee /etc/ld.so.conf.d/oracle-21.conf
sudo ldconfig

# Verify client libraries presence
ls -la /usr/lib/oracle/21/client64/lib/
```

### 5.3 Compile and Install OCI8 PHP Extension via PECL

```bash
export ORACLE_HOME=/usr/lib/oracle/21/client64

# Install OCI8 via PECL
sudo pecl install oci8

# When prompted:
# "Please provide the path to ORACLE_HOME directory:"
# Type: instantclient,/usr/lib/oracle/21/client64/lib
```

### 5.4 Enable OCI8 in PHP Configuration

```bash
# Register extension in CLI and Apache php.ini configurations
echo "extension=oci8.so" | sudo tee -a /etc/php/8.1/apache2/php.ini
echo "extension=oci8.so" | sudo tee -a /etc/php/8.1/cli/php.ini

# Set LD_LIBRARY_PATH environment variable for the Apache process
echo 'export LD_LIBRARY_PATH=/usr/lib/oracle/21/client64/lib:$LD_LIBRARY_PATH' \
    | sudo tee -a /etc/apache2/envvars

# Restart Apache web server
sudo systemctl restart apache2

# Verify extension loading
php -m | grep oci8
php -r "echo 'OCI8 Client Version: ' . oci_client_version() . PHP_EOL;"
```

✅ **Verification:** Output must show `oci8` in module list and print the Oracle client version string.

---

## STEP 6 – Oracle User Provisioning & Database Import

### 6.1 Create the Lab Database User (`dbs401_user`)

```bash
sqlplus sys/oracle@localhost:1539/XEPDB1 as sysdba
```

Inside the SQL*Plus shell:
```sql
-- Create application user
CREATE USER dbs401_user IDENTIFIED BY dbs401_pass;

-- Grant required administrative privileges
GRANT CONNECT, RESOURCE, CREATE SESSION TO dbs401_user;
GRANT CREATE TABLE, CREATE SEQUENCE, CREATE VIEW TO dbs401_user;
GRANT CREATE PROCEDURE, CREATE TRIGGER TO dbs401_user;
ALTER USER dbs401_user QUOTA UNLIMITED ON USERS;

-- Confirm user status
SELECT username, account_status FROM DBA_USERS WHERE username = 'DBS401_USER';

EXIT;
```

### 6.2 Import Schema DDL

```bash
cd /path/to/oracle-sec-lab/

sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/schema.sql
```

✅ **Verification:** Tables and sequences created without `ORA-` errors.

### 6.3 Import Seed Data

```bash
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/seed.sql
```

### 6.4 Align Audit References

```bash
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/fix_refs.sql
```

> **Note:** This script synchronizes `admin_ref_id` references in the `ENROLLMENTS` table to point accurately to corresponding `AUDIT_LOGS` records.

### 6.5 Initialize Bcrypt Password Hashes

```bash
php database/init_passwords.php
```

✅ **Verification:** Output should display `"Updated password for: admin"`, `"Updated password for: student1"`, etc.

---

## STEP 7 – Web Application Deployment

### 7.1 Automated Deployment via `setup.sh`

```bash
sudo bash setup.sh
```

### 7.2 Manual Deployment (Alternative)

```bash
# Copy project source tree to Apache webroot
sudo mkdir -p /var/www/html/dbs401-oracle-app
sudo cp -r . /var/www/html/dbs401-oracle-app/

# Set appropriate directory ownership and permissions
sudo chown -R www-data:www-data /var/www/html/dbs401-oracle-app
sudo chmod -R 755 /var/www/html/dbs401-oracle-app
sudo chmod 640 /var/www/html/dbs401-oracle-app/config.php

# Configure Apache virtual alias
sudo bash -c 'cat > /etc/apache2/conf-available/dbs401.conf << CONF
Alias /dbs401-oracle-app /var/www/html/dbs401-oracle-app
<Directory /var/www/html/dbs401-oracle-app>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.php
</Directory>
CONF'

# Enable configuration and rewrite module
sudo a2enconf dbs401
sudo a2enmod rewrite
sudo systemctl reload apache2
```

---

## STEP 8 – Testing & Verification

### 8.1 Accessing the Application

* **Local Machine:** `http://127.0.0.1/dbs401-oracle-app`
* **Network / LAN:** `http://<SERVER_IP>/dbs401-oracle-app`

### 8.2 Testing Authentication

| Account | Password | Role | Expected Landing Behavior |
|:---|:---|:---:|:---|
| `student1` | `Student@123` | **Student** | Student Dashboard with enrollments, course registration, timetable, tuition |
| `teacher1` | `Teacher@123` | **Teacher** | Faculty Dashboard with assigned courses and grading portal |
| `admin` | `Admin@DBS401!2024` | **Admin** | Admin Dashboard with system oversight, user management, audit logs |

### 8.3 Vulnerability Endpoint Functional Sanity Tests

```bash
# Test Student Search Endpoint (Vulnerability 1)
curl -s "http://127.0.0.1/dbs401-oracle-app/search.php?q=Nguyen" \
     -b "DBS401_SESSION=<COOKIE_VALUE>" | grep -i "student_id"

# Test Store Endpoint Negative Quantity Processing (Vulnerability 2)
curl -s -X POST "http://127.0.0.1/dbs401-oracle-app/store.php" \
     -d "buy=1&quantity=-1000" \
     -b "DBS401_SESSION=<COOKIE_VALUE>"

# Test Hidden Partner Configuration Access (Vulnerability 3A)
curl -s "http://127.0.0.1/dbs401-oracle-app/partner_config.php" \
     -b "DBS401_SESSION=<COOKIE_VALUE>" | grep -i "Partner Integration"

# Test Admin Update Check Baseline (Vulnerability 3B)
curl -s "http://127.0.0.1/dbs401-oracle-app/admin.php?check_updates=1" \
     -b "DBS401_SESSION=<ADMIN_COOKIE_VALUE>"
```

### 8.4 Verifying Seed Data in Oracle Database

```bash
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 << 'EOF'
-- Verify FLAGS table integrity
SELECT flag_id, flag_code, is_active FROM FLAGS;

-- Verify CONFIG_STORE entries
SELECT config_key, config_value, is_public FROM CONFIG_STORE
WHERE config_key IN ('update_url','app_version','sys_alpha_marker');

-- Verify SYSTEM_HINTS
SELECT hint_key, hint_value FROM SYSTEM_HINTS WHERE related_vuln = 'VULN3';

-- Verify Student Baseline Credits
SELECT full_name, credits FROM STUDENTS
WHERE user_id = (SELECT user_id FROM USERS WHERE username='student1');

EXIT;
EOF
```

---

## Quick Troubleshooting Guide

| Issue / Error | Likely Root Cause | Recommended Remediation |
|:---|:---|:---|
| `OCI8 not loaded` | PHP OCI8 extension not enabled | Add `extension=oci8.so` to `/etc/php/8.1/apache2/php.ini` and `/etc/php/8.1/cli/php.ini`, then restart Apache. |
| `ORA-12514: TNS:listener does not currently know of service` | Oracle listener has not registered `XEPDB1` | Run `lsnrctl reload` and check database instance status with `sqlplus / as sysdba`. |
| `ORA-01017: invalid username/password` | Incorrect database credentials | Re-verify `dbs401_user` / `dbs401_pass` credentials in `config.php`. |
| `403 Forbidden` on Web | Incorrect file permissions | Run `sudo chown -R www-data:www-data /var/www/html/dbs401-oracle-app`. |
| Blank PHP Page | Suppressed PHP error display | Check error log: `sudo tail -f /var/log/apache2/error.log`. |
| `Connection refused (port 1539)` | Oracle database service stopped | Start service: `sudo systemctl start oracle-xe-21c`. |
| OCI8 loaded in CLI but fails in Apache | Missing `LD_LIBRARY_PATH` for Apache | Add `export LD_LIBRARY_PATH=/usr/lib/oracle/21/client64/lib:$LD_LIBRARY_PATH` to `/etc/apache2/envvars` and restart Apache. |

---

## Finding Host LAN IP for Remote Lab Testing

To allow other machines on the local network (such as Kali Linux attacker machines or CTF participants) to connect:

```bash
# Method 1 (Quickest)
hostname -I | awk '{print $1}'

# Method 2 (Route-based)
ip route get 8.8.8.8 | grep -oP '(?<=src )\S+'

# Method 3 (Standard interface check)
ip addr show | grep 'inet ' | grep -v '127.0.0.1'
```

Once the host LAN IP is identified (e.g. `192.168.1.150`), access the portal from client machines:
```
http://192.168.1.150/dbs401-oracle-app
```

> **Firewall Note:** Ensure the Ubuntu host firewall allows inbound HTTP traffic on port 80:
> ```bash
> sudo ufw allow 80/tcp comment 'OracleSecLab HTTP'
> ```

---

*OracleSecLab – Educational Penetration Testing Lab – Designed for authorized security research.*
