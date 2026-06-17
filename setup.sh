#!/bin/bash
# =============================================================
# DBS401 - Group 02 - setup.sh
# FPT Student Portal – Oracle Security Lab
# =============================================================
# Hỗ trợ Ubuntu 20.04 / 22.04 / 24.04
# Chạy: sudo bash setup.sh
# =============================================================
# QUAN TRỌNG:
#   Oracle Database XE và OCI8 không thể cài hoàn toàn tự động.
#   Script sẽ kiểm tra và hướng dẫn bước thủ công nếu cần.
# =============================================================

set -e
BOLD="\033[1m"
RED="\033[31m"
GRN="\033[32m"
YEL="\033[33m"
BLU="\033[34m"
RST="\033[0m"

# ─── Cấu hình ───────────────────────────────────────────────
REPO_URL=""                                      # Để trống → copy local source
APP_DIR="/var/www/html/dbs401-oracle-app"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ORA_SERVICE="XEPDB1"                                 # hoặc "FREE" cho Oracle 23c
ORA_USER="dbs401_user"
ORA_PASS="dbs401_pass"
ORA_SYS_PASS="oracle"                            # SYS password của Oracle XE
PHP_INI_CLI="/etc/php/8.1/cli/php.ini"
PHP_INI_APACHE="/etc/php/8.1/apache2/php.ini"
# ────────────────────────────────────────────────────────────

log_info()  { echo -e "${BLU}[INFO]${RST}  $1"; }
log_ok()    { echo -e "${GRN}[OK]${RST}    $1"; }
log_warn()  { echo -e "${YEL}[WARN]${RST}  $1"; }
log_err()   { echo -e "${RED}[ERROR]${RST} $1"; }
log_step()  { echo -e "\n${BOLD}${BLU}══ $1 ══${RST}"; }
require_root() { [ "$(id -u)" -eq 0 ] || { log_err "Chạy với sudo. Ví dụ: sudo bash setup.sh"; exit 1; }; }

# ─── Banner ─────────────────────────────────────────────────
echo -e "${BOLD}"
cat << 'EOF'
  ╔═══════════════════════════════════════════════════════╗
  ║   DBS401 - Group 02 - FPT Student Portal Setup       ║
  ║   Oracle Database Security Lab                       ║
  ╚═══════════════════════════════════════════════════════╝
EOF
echo -e "${RST}"

require_root

# ─── 1. Kiểm tra Apache ──────────────────────────────────────
log_step "STEP 1: Apache Web Server"
if ! command -v apache2 &>/dev/null; then
    log_warn "Apache2 chưa cài. Đang cài..."
    apt-get update -qq
    apt-get install -y apache2
fi
systemctl enable apache2 --quiet
systemctl start  apache2
log_ok "Apache2: OK ($(apache2 -v 2>&1 | head -1))"

# ─── 2. Kiểm tra PHP ─────────────────────────────────────────
log_step "STEP 2: PHP 8"
if ! command -v php &>/dev/null; then
    log_warn "PHP chưa cài. Đang cài PHP 8.1..."
    apt-get install -y software-properties-common
    add-apt-repository -y ppa:ondrej/php
    apt-get update -qq
    apt-get install -y php8.1 libapache2-mod-php8.1 php8.1-cli php8.1-common
fi
PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
log_ok "PHP: ${PHP_VER}"

# ─── 3. Kiểm tra Oracle Instant Client ──────────────────────
log_step "STEP 3: Oracle Instant Client"
IC_FOUND=0
for dir in /usr/lib/oracle/*/client64/lib /opt/oracle/instantclient_*; do
    [ -d "$dir" ] && IC_FOUND=1 && IC_DIR="$dir" && break
done
if [ $IC_FOUND -eq 0 ]; then
    log_warn "Oracle Instant Client CHƯA được cài!"
    echo -e "${YEL}"
    cat << 'MANUAL'
─── CÀI THỦ CÔNG Oracle Instant Client ───────────────────────
1. Tải từ: https://www.oracle.com/database/technologies/instant-client/linux-x86-64-downloads.html
   - oracle-instantclient-basiclite-21.x.x.x-1.x86_64.rpm  (hoặc .deb)
   - oracle-instantclient-devel-21.x.x.x-1.x86_64.rpm

2. Cài .rpm trên Ubuntu:
   sudo apt-get install -y alien libaio1
   sudo alien --to-deb oracle-instantclient-basiclite-21.*.rpm
   sudo alien --to-deb oracle-instantclient-devel-21.*.rpm
   sudo dpkg -i oracle-instantclient*.deb

   Hoặc dùng .deb trực tiếp nếu Oracle cung cấp.

3. Cấu hình ldconfig:
   echo /usr/lib/oracle/21/client64/lib | sudo tee /etc/ld.so.conf.d/oracle-21.conf
   sudo ldconfig

4. Đặt biến môi trường (thêm vào /etc/environment):
   ORACLE_HOME=/usr/lib/oracle/21/client64
   LD_LIBRARY_PATH=/usr/lib/oracle/21/client64/lib:$LD_LIBRARY_PATH
──────────────────────────────────────────────────────────────
MANUAL
    echo -e "${RST}"
else
    log_ok "Oracle Instant Client: found at ${IC_DIR}"
fi

# ─── 4. Kiểm tra OCI8 PHP Extension ─────────────────────────
log_step "STEP 4: PHP OCI8 Extension"
if php -m 2>/dev/null | grep -q oci8; then
    log_ok "OCI8 extension: LOADED"
else
    log_warn "OCI8 CHƯA được load!"
    echo -e "${YEL}"
    cat << 'MANUAL'
─── CÀI OCI8 PHP Extension ────────────────────────────────────
Sau khi đã cài Oracle Instant Client, chạy:

  sudo apt-get install -y php8.1-dev php-pear build-essential libaio1
  export ORACLE_HOME=/usr/lib/oracle/21/client64
  sudo pecl install oci8
  # Khi được hỏi "Please provide the path to ORACLE_HOME directory":
  # Nhập: instantclient,/usr/lib/oracle/21/client64/lib

  # Sau đó thêm extension vào php.ini:
  echo "extension=oci8.so" | sudo tee -a /etc/php/8.1/apache2/php.ini
  echo "extension=oci8.so" | sudo tee -a /etc/php/8.1/cli/php.ini
  sudo systemctl restart apache2

  # Kiểm tra:
  php -m | grep oci8
──────────────────────────────────────────────────────────────
MANUAL
    echo -e "${RST}"
fi

# ─── 5. Kiểm tra Oracle Database ─────────────────────────────
log_step "STEP 5: Oracle Database XE"
if command -v sqlplus &>/dev/null; then
    log_ok "SQLPlus: $(sqlplus -V 2>/dev/null | head -1)"
else
    log_warn "SQLPlus không tìm thấy trong PATH!"
    echo -e "${YEL}"
    cat << 'MANUAL'
─── CÀI Oracle Database XE 21c ────────────────────────────────
1. Tải Oracle XE 21c từ:
   https://www.oracle.com/database/technologies/xe-downloads.html
   File: oracle-database-xe-21c-1.0-1.ol8.x86_64.rpm

2. Cài trên Ubuntu:
   sudo apt-get install -y alien libaio1 bc
   sudo alien --to-deb --scripts oracle-database-xe-21c-1.0-1.ol8.x86_64.rpm
   sudo dpkg -i oracle-database-xe-21c*.deb

3. Cấu hình Oracle XE:
   sudo /etc/init.d/oracle-xe-21c configure
   # Nhập SYS/SYSTEM password: oracle (hoặc tùy chọn)
   # Port mặc định: 1539

4. Khởi động:
   sudo systemctl start oracle-xe-21c
   sudo systemctl enable oracle-xe-21c

5. Đặt biến môi trường (thêm vào ~/.bashrc):
   export ORACLE_HOME=/opt/oracle/product/21c/dbhomeXE
   export ORACLE_SID=XE
   export PATH=$ORACLE_HOME/bin:$PATH

HOẶC dùng Oracle 23c Free (nhẹ hơn):
   https://www.oracle.com/database/free/
──────────────────────────────────────────────────────────────
MANUAL
    echo -e "${RST}"
fi

# ─── 6. Copy source code ─────────────────────────────────────
log_step "STEP 6: Deploy Source Code"
if [ -n "$REPO_URL" ]; then
    log_info "REPO_URL được cấu hình → git clone..."
    if ! command -v git &>/dev/null; then
        apt-get install -y git
    fi
    rm -rf "$APP_DIR"
    git clone "$REPO_URL" "$APP_DIR"
    log_ok "Cloned từ $REPO_URL"
else
    log_info "REPO_URL trống → copy source từ thư mục hiện tại: $SCRIPT_DIR"
    if [ "$SCRIPT_DIR" = "$APP_DIR" ]; then
        log_ok "Source đã ở đúng vị trí: $APP_DIR"
    else
        mkdir -p "$APP_DIR"
        cp -r "$SCRIPT_DIR/." "$APP_DIR/"
        log_ok "Source copied → $APP_DIR"
    fi
fi

# ─── 7. Permissions ─────────────────────────────────────────
log_step "STEP 7: File Permissions"
chown -R www-data:www-data "$APP_DIR"
chmod -R 755 "$APP_DIR"
chmod 640 "$APP_DIR/config.php"
log_ok "Permissions set"

# ─── 8. Apache Virtual Host / Alias ─────────────────────────
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

# ─── 8b. Partner Server Simulation (Nginx) ───────────────────
log_step "STEP 8b: Partner Server Simulation (Nginx on Port 8081)"
apt-get install -y nginx --quiet
PARTNER_DIR="/var/www/partner-api"
mkdir -p "$PARTNER_DIR"

# Tạo manifest file chứa mảnh Flag 3 (Mã hóa Hex để tăng độ khó)
# Hex của 'DBS401{5upp1y_Ch41n_P0150n1ng_0912}'
# Version 3.0.5 là thấp hơn APP_VERSION (3.1.0-ENTERPRISE) để hacker phải tự tạo manifest version cao hơn để trigger update
echo '{"version":"3.0.5","status":"stable","checksum":"a8b9c1","flag_part":"4442533430317b3575707031795f436834"}' > "$PARTNER_DIR/manifest.json"

# Cấu hình Nginx chạy trên port 8081
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

# ─── 9. Oracle User + Schema ─────────────────────────────────
log_step "STEP 9: Oracle Database Setup (thủ công nếu Oracle chưa chạy)"

if command -v sqlplus &>/dev/null; then
cat << SQLINFO

─── Tạo Oracle user DBS401 (chạy thủ công) ────────────────────
Mở terminal và chạy:

  sqlplus sys/${ORA_SYS_PASS}@localhost:1539/${ORA_SERVICE} as sysdba

Trong SQLPlus:
  CREATE USER ${ORA_USER} IDENTIFIED BY ${ORA_PASS};
  GRANT CONNECT, RESOURCE, CREATE SESSION TO ${ORA_USER};
  GRANT CREATE TABLE, CREATE SEQUENCE, CREATE VIEW TO ${ORA_USER};
  ALTER USER ${ORA_USER} QUOTA UNLIMITED ON USERS;
  EXIT;

Import schema + seed:
  sqlplus ${ORA_USER}/${ORA_PASS}@localhost:1539/${ORA_SERVICE} @${APP_DIR}/database/schema.sql
  sqlplus ${ORA_USER}/${ORA_PASS}@localhost:1539/${ORA_SERVICE} @${APP_DIR}/database/seed.sql

Tạo password hash (PHP bcrypt):
  php ${APP_DIR}/database/init_passwords.php
──────────────────────────────────────────────────────────────
SQLINFO
else
    log_warn "SQLPlus không có trong PATH. Xem hướng dẫn cài Oracle ở trên."
fi

# ─── 10.a. Final Cleanup (CTF Hardening) ───────────────────────
log_step "STEP 11: Final Cleanup & Hardening"
log_info "Removing database seed files to prevent direct flag discovery..."
# Xóa các file .sql để hacker không thể đọc schema/flags qua lỗi RCE hoặc File Read
# rm -f "$APP_DIR/database"/*.sql
# log_ok "Sensitive SQL files removed from $APP_DIR/database/"

# ─── 10.b. Hiển thị URL ────────────────────────────────────────
log_step "SETUP COMPLETE"
LAN_IP=$(hostname -I | awk '{print $1}')
echo ""
echo -e "${GRN}${BOLD}Web Application URLs:${RST}"
echo -e "  Local :  http://127.0.0.1/dbs401-oracle-app"
echo -e "  LAN   :  http://${LAN_IP}/dbs401-oracle-app"
echo ""
echo -e "${YEL}Demo accounts:${RST}"
echo -e "  admin    / Admin@DBS401!2024"
echo -e "  teacher1 / Teacher@123"
echo -e "  student1 / Student@123"
echo ""
echo -e "${YEL}Lưu ý:${RST} Nếu OCI8 hoặc Oracle chưa cài, web sẽ báo lỗi connection."
echo -e "Xem hướng dẫn thủ công ở README.md hoặc các bước WARN ở trên."
echo ""
