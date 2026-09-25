#!/bin/bash
# =============================================================
# OracleSecLab - setup.sh
# Vulnerable Oracle Database Web Application Lab
# =============================================================
# Supports Ubuntu 20.04 / 22.04 / 24.04 LTS
# Usage: sudo bash setup.sh
# =============================================================
# IMPORTANT:
#   Oracle Database XE and OCI8 cannot be fully installed automatically.
#   This script checks dependencies and provides manual guidance if needed.
# =============================================================

set -e
BOLD="\033[1m"
RED="\033[31m"
GRN="\033[32m"
YEL="\033[33m"
BLU="\033[34m"
RST="\033[0m"

# ─── Configuration ───────────────────────────────────────────
REPO_URL=""                                      # Leave empty to copy local workspace source
APP_DIR="/var/www/html/dbs401-oracle-app"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ORA_SERVICE="XEPDB1"                                 # or "FREEPDB1" for Oracle 23c Free
ORA_USER="dbs401_user"
ORA_PASS="dbs401_pass"
ORA_SYS_PASS="oracle"                            # SYS password for Oracle XE
PHP_INI_CLI="/etc/php/8.1/cli/php.ini"
PHP_INI_APACHE="/etc/php/8.1/apache2/php.ini"
# ────────────────────────────────────────────────────────────

log_info()  { echo -e "${BLU}[INFO]${RST}  $1"; }
log_ok()    { echo -e "${GRN}[OK]${RST}    $1"; }
log_warn()  { echo -e "${YEL}[WARN]${RST}  $1"; }
log_err()   { echo -e "${RED}[ERROR]${RST} $1"; }
log_step()  { echo -e "\n${BOLD}${BLU}══ $1 ══${RST}"; }
require_root() { [ "$(id -u)" -eq 0 ] || { log_err "Root privileges required. Run with: sudo bash setup.sh"; exit 1; }; }

# ─── Banner ─────────────────────────────────────────────────
echo -e "${BOLD}"
cat << 'EOF'
  ╔═══════════════════════════════════════════════════════╗
  ║   OracleSecLab – Vulnerable Oracle Web Application    ║
  ║   Penetration Testing & Security Lab Environment      ║
  ╚═══════════════════════════════════════════════════════╝
EOF
echo -e "${RST}"

require_root

# ─── 1. Check Apache Web Server ──────────────────────────────
log_step "STEP 1: Apache Web Server"
if ! command -v apache2 &>/dev/null; then
    log_warn "Apache2 is not installed. Installing..."
    apt-get update -qq
    apt-get install -y apache2
fi
systemctl enable apache2 --quiet
systemctl start  apache2
log_ok "Apache2: OK ($(apache2 -v 2>&1 | head -1))"

# ─── 2. Check PHP ─────────────────────────────────────────────
log_step "STEP 2: PHP 8.1"
if ! command -v php &>/dev/null; then
    log_warn "PHP is not installed. Installing PHP 8.1..."
    apt-get install -y software-properties-common
    add-apt-repository -y ppa:ondrej/php
    apt-get update -qq
    apt-get install -y php8.1 libapache2-mod-php8.1 php8.1-cli php8.1-common
fi
PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
log_ok "PHP Version: ${PHP_VER}"

# ─── 3. Check Oracle Instant Client ──────────────────────────
log_step "STEP 3: Oracle Instant Client"
IC_FOUND=0
for dir in /usr/lib/oracle/*/client64/lib /opt/oracle/instantclient_*; do
    [ -d "$dir" ] && IC_FOUND=1 && IC_DIR="$dir" && break
done
if [ $IC_FOUND -eq 0 ]; then
    log_warn "Oracle Instant Client NOT detected!"
    echo -e "${YEL}"
    cat << 'MANUAL'
─── MANUAL INSTALLATION: Oracle Instant Client ───────────────
1. Download packages from:
   https://www.oracle.com/database/technologies/instant-client/linux-x86-64-downloads.html
   - oracle-instantclient-basiclite-21.x.x.x-1.x86_64.rpm (or .deb)
   - oracle-instantclient-devel-21.x.x.x-1.x86_64.rpm

2. Install RPM on Ubuntu:
   sudo apt-get install -y alien libaio1
   sudo alien --to-deb oracle-instantclient-basiclite-21.*.rpm
   sudo alien --to-deb oracle-instantclient-devel-21.*.rpm
   sudo dpkg -i oracle-instantclient*.deb

3. Configure dynamic linker (ldconfig):
   echo /usr/lib/oracle/21/client64/lib | sudo tee /etc/ld.so.conf.d/oracle-21.conf
   sudo ldconfig

4. Set environment variables (e.g. in /etc/environment):
   ORACLE_HOME=/usr/lib/oracle/21/client64
   LD_LIBRARY_PATH=/usr/lib/oracle/21/client64/lib:$LD_LIBRARY_PATH
──────────────────────────────────────────────────────────────
MANUAL
    echo -e "${RST}"
else
    log_ok "Oracle Instant Client: found at ${IC_DIR}"
fi

# ─── 4. Check PHP OCI8 Extension ─────────────────────────────
log_step "STEP 4: PHP OCI8 Extension"
if php -m 2>/dev/null | grep -q oci8; then
    log_ok "OCI8 extension: LOADED"
else
    log_warn "OCI8 extension NOT loaded!"
    echo -e "${YEL}"
    cat << 'MANUAL'
─── MANUAL INSTALLATION: OCI8 PHP Extension ──────────────────
After installing Oracle Instant Client, execute:

  sudo apt-get install -y php8.1-dev php-pear build-essential libaio1
  export ORACLE_HOME=/usr/lib/oracle/21/client64
  sudo pecl install oci8
  # When prompted: "Please provide the path to ORACLE_HOME directory":
  # Enter: instantclient,/usr/lib/oracle/21/client64/lib

  # Then enable the extension in php.ini:
  echo "extension=oci8.so" | sudo tee -a /etc/php/8.1/apache2/php.ini
  echo "extension=oci8.so" | sudo tee -a /etc/php/8.1/cli/php.ini
  sudo systemctl restart apache2

  # Verify module loading:
  php -m | grep oci8
──────────────────────────────────────────────────────────────
MANUAL
    echo -e "${RST}"
fi

# ─── 5. Check Oracle Database XE ─────────────────────────────
log_step "STEP 5: Oracle Database XE"
if command -v sqlplus &>/dev/null; then
    log_ok "SQLPlus: $(sqlplus -V 2>/dev/null | head -1)"
else
    log_warn "SQLPlus not found in system PATH!"
    echo -e "${YEL}"
    cat << 'MANUAL'
─── MANUAL INSTALLATION: Oracle Database XE 21c ──────────────
1. Download Oracle XE 21c from:
   https://www.oracle.com/database/technologies/xe-downloads.html
   File: oracle-database-xe-21c-1.0-1.ol8.x86_64.rpm

2. Install on Ubuntu:
   sudo apt-get install -y alien libaio1 bc
   sudo alien --to-deb --scripts oracle-database-xe-21c-1.0-1.ol8.x86_64.rpm
   sudo dpkg -i oracle-database-xe-21c*.deb

3. Configure Oracle XE instance:
   sudo /etc/init.d/oracle-xe-21c configure
   # Set SYS/SYSTEM password: oracle (or your chosen password)
   # Default listener port: 1539

4. Enable and start database service:
   sudo systemctl start oracle-xe-21c
   sudo systemctl enable oracle-xe-21c

5. Set user environment variables (in ~/.bashrc):
   export ORACLE_HOME=/opt/oracle/product/21c/dbhomeXE
   export ORACLE_SID=XE
   export PATH=$ORACLE_HOME/bin:$PATH

OR use Oracle Database 23c Free:
   https://www.oracle.com/database/free/
──────────────────────────────────────────────────────────────
MANUAL
    echo -e "${RST}"
fi

# ─── 6. Copy Source Code ─────────────────────────────────────
log_step "STEP 6: Deploy Source Code"
if [ -n "$REPO_URL" ]; then
    log_info "REPO_URL configured. Cloning via git..."
    if ! command -v git &>/dev/null; then
        apt-get install -y git
    fi
    rm -rf "$APP_DIR"
    git clone "$REPO_URL" "$APP_DIR"
    log_ok "Cloned from $REPO_URL"
else
    log_info "REPO_URL empty. Copying source from local directory: $SCRIPT_DIR"
    if [ "$SCRIPT_DIR" = "$APP_DIR" ]; then
        log_ok "Source is already at target path: $APP_DIR"
    else
        mkdir -p "$APP_DIR"
        cp -r "$SCRIPT_DIR/." "$APP_DIR/"
        log_ok "Source copied to: $APP_DIR"
    fi
fi

# ─── 7. Permissions ──────────────────────────────────────────
log_step "STEP 7: File Permissions"
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod 640 "$APP_DIR/config.php"
log_ok "Permissions configured successfully"

# ─── 8. Apache Virtual Host / Alias Configuration ─────────────
log_step "STEP 8: Apache Configuration"
a2enmod rewrite --quiet 2>/dev/null || true
ALIAS_CONF="/etc/apache2/conf-available/dbs401.conf"
cat > "$ALIAS_CONF" << CONF
Alias /dbs401-oracle-app ${APP_DIR}
<Directory ${APP_DIR}>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.php
</Directory>
CONF
a2enconf dbs401 --quiet 2>/dev/null || true
systemctl reload apache2
log_ok "Apache alias /dbs401-oracle-app → $APP_DIR configured"

# ─── 8b. Partner Server Simulation (Nginx on Port 8081) ───────
log_step "STEP 8b: Partner Server Simulation (Nginx on Port 8081)"
apt-get install -y nginx --quiet
PARTNER_DIR="/var/www/partner-api"
mkdir -p "$PARTNER_DIR"

# Create baseline manifest containing ONLY the first hex half of Flag 3.
# Version 3.0.5 is lower than APP_VERSION (3.1.0), so baseline check does not trigger flag disclosure.
# Candidates must alter update_url via partner_config.php to serve a higher version.
echo '{"version":"3.0.5","status":"stable","checksum":"a8b9c1","flag_part":"4442533430317b3575707031795f436834"}' > "$PARTNER_DIR/manifest.json"

cat > /etc/nginx/sites-available/partner-simulation << EOF
server {
    listen 8081;
    root $PARTNER_DIR;
    index manifest.json;
    location / {
        add_header Content-Type application/json;
        try_files \$uri \$uri/ =404;
    }
}
EOF

ln -sf /etc/nginx/sites-available/partner-simulation /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
systemctl restart nginx
log_ok "Partner Server running at http://127.0.0.1:8081/manifest.json"

# ─── 9. Oracle User + Database Import Guidance ────────────────
log_step "STEP 9: Oracle Database Setup (Execute manually if DB is not ready)"

if command -v sqlplus &>/dev/null; then
cat << SQLINFO

─── Create Oracle Lab User (Manual Step) ─────────────────────
Open a terminal and execute:

  sqlplus sys/${ORA_SYS_PASS}@localhost:1539/${ORA_SERVICE} as sysdba

Inside SQL*Plus:
  CREATE USER ${ORA_USER} IDENTIFIED BY ${ORA_PASS};
  GRANT CONNECT, RESOURCE, CREATE SESSION TO ${ORA_USER};
  GRANT CREATE TABLE, CREATE SEQUENCE, CREATE VIEW TO ${ORA_USER};
  ALTER USER ${ORA_USER} QUOTA UNLIMITED ON USERS;
  EXIT;

Import Schema & Seed Data:
  sqlplus ${ORA_USER}/${ORA_PASS}@localhost:1539/${ORA_SERVICE} @${APP_DIR}/database/schema.sql
  sqlplus ${ORA_USER}/${ORA_PASS}@localhost:1539/${ORA_SERVICE} @${APP_DIR}/database/seed.sql
  sqlplus ${ORA_USER}/${ORA_PASS}@localhost:1539/${ORA_SERVICE} @${APP_DIR}/database/fix_refs.sql

Generate Bcrypt Password Hashes:
  php ${APP_DIR}/database/init_passwords.php
──────────────────────────────────────────────────────────────
SQLINFO
else
    log_warn "SQLPlus not in PATH. Follow Oracle installation guide above."
fi

# ─── 10. Summary & Endpoints ─────────────────────────────────
log_step "SETUP COMPLETE"
LAN_IP=$(hostname -I 2>/dev/null | awk '{print $1}')
[ -z "$LAN_IP" ] && LAN_IP="<HOST_IP>"

echo ""
echo -e "${GRN}${BOLD}Web Application Access Endpoints:${RST}"
echo -e "  Local :  http://127.0.0.1/dbs401-oracle-app"
echo -e "  LAN   :  http://${LAN_IP}/dbs401-oracle-app"
echo ""
echo -e "${YEL}Default Demo Accounts:${RST}"
echo -e "  admin    / Admin@DBS401!2024 (System Administrator)"
echo -e "  teacher1 / Teacher@123       (Faculty Instructor)"
echo -e "  student1 / Student@123       (Student Portal User)"
echo ""
echo -e "${YEL}Note:${RST} If OCI8 or Oracle XE is not running, database connection errors will occur."
echo -e "Refer to docs/DEPLOYMENT_GUIDE.md for detailed troubleshooting steps."
echo ""
