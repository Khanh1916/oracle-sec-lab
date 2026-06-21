# DBS401 – Group 02 – FPT Student Portal
## Oracle Database Security Lab

> ⚠️ **Chỉ dùng trong môi trường lab DBS401 nội bộ. Không deploy lên internet.**

---

## 📁 Cấu trúc Project

```
dbs401-oracle-app/
├── config.php                  # Cấu hình kết nối Oracle
├── index.php                   # Redirect về login/dashboard
├── login.php                   # Đăng nhập
├── logout.php                  # Đăng xuất
├── dashboard.php               # Trang chính sau login
├── search.php                  # [VULN 1] SQL Injection
├── profile.php                 # Xem profile cá nhân
├── store.php                   # [VULN 2] Business Logic (Negative Quantity)
├── transcript.php              # Xem bảng điểm (Secure)
├── audit.php                   # Xem nhật ký hệ thống (Secure)
├── partner_config.php          # [VULN 3A] Hidden partner config (Broken Access Control)
├── admin.php                   # [VULN 3B] Partner update check / Supply Chain trigger
├── secret_check.php            # Secret key API (Legacy/decoy, không còn là Vuln 3)
├── inc_navbar.php              # Shared navbar component
├── style.css                   # CSS styles
├── database/
│   ├── schema.sql              # Tạo bảng Oracle
│   ├── seed.sql                # Insert dữ liệu mẫu + flag parts
│   └── init_passwords.php      # Tạo password hash (chạy 1 lần)
├── secure_versions/
│   ├── search_secure.php       # Vuln 1 đã vá
│   ├── store_secure.php        # Vuln 2 đã vá
│   ├── partner_config_secure.php # Vá lỗi quyền cấu hình Partner
│   └── admin_update_secure.php # Vá lỗi update check / Supply Chain
├── tools/
│   └── exploit_flag3_local.py  # Script khai thác Vuln 3 (lab only)
├── setup.sh                    # Script triển khai tự động
├── README.md                   # File này
├── REPORT_DBS401.md            # Báo cáo chính thức
└── docs/ANSWER_KEY.md          # Hướng dẫn nội bộ (không nộp công khai)
```

---

## 🚀 Hướng Dẫn Cài Đặt

### Yêu Cầu Hệ Thống

| Thành phần | Yêu cầu |
|-----------|---------|
| OS | Ubuntu 20.04 / 22.04 LTS |
| RAM | Tối thiểu 4GB (Oracle cần ~2GB) |
| Disk | Tối thiểu 20GB |
| Network | Local LAN (không cần internet sau khi cài) |

---

### BƯỚC 1 – Cài Apache2

```bash
sudo apt-get update
sudo apt-get install -y apache2
sudo systemctl enable apache2
sudo systemctl start apache2
# Kiểm tra:
curl -s http://127.0.0.1 | head -5
```

---

### BƯỚC 2 – Cài PHP 8.1

```bash
sudo apt-get install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update
sudo apt-get install -y php8.1 libapache2-mod-php8.1 php8.1-cli php8.1-common
sudo a2enmod php8.1
sudo systemctl restart apache2
# Kiểm tra:
php -v
```

---

### BƯỚC 3 – Cài Oracle Database XE 21c *(thủ công)*

```bash
# 1. Tải Oracle XE 21c RPM từ:
#    https://www.oracle.com/database/technologies/xe-downloads.html
#    File: oracle-database-xe-21c-1.0-1.ol8.x86_64.rpm

# 2. Cài dependencies
sudo apt-get install -y alien libaio1 bc

# 3. Convert RPM → DEB và cài
sudo alien --to-deb --scripts oracle-database-xe-21c-1.0-1.ol8.x86_64.rpm
sudo dpkg -i oracle-database-xe-21c*.deb

# 4. Cấu hình (set password cho SYS/SYSTEM)
sudo /etc/init.d/oracle-xe-21c configure

# 5. Đặt biến môi trường (thêm vào ~/.bashrc)
export ORACLE_HOME=/opt/oracle/product/21c/dbhomeXE
export ORACLE_SID=XE
export PATH=$ORACLE_HOME/bin:$PATH
source ~/.bashrc

# 6. Start Oracle
sudo systemctl enable oracle-xe-21c
sudo systemctl start oracle-xe-21c

# 7. Kiểm tra
sqlplus / as sysdba << 'EOF'
SELECT STATUS FROM V$INSTANCE;
EXIT;
EOF
```

> **Lưu ý:** Nếu không muốn cài Oracle XE đầy đủ, có thể dùng **Oracle 23c Free**:
> https://www.oracle.com/database/free/

---

### BƯỚC 4 – Cài Oracle Instant Client *(thủ công)*

```bash
# Tải từ: https://www.oracle.com/database/technologies/instant-client/linux-x86-64-downloads.html
# Cần 2 packages:
#   oracle-instantclient-basiclite-21.*.x86_64.rpm
#   oracle-instantclient-devel-21.*.x86_64.rpm

sudo apt-get install -y alien libaio1
sudo alien --to-deb oracle-instantclient-basiclite-21.*.rpm
sudo alien --to-deb oracle-instantclient-devel-21.*.rpm
sudo dpkg -i oracle-instantclient*.deb

# Cấu hình ldconfig
echo /usr/lib/oracle/21/client64/lib | sudo tee /etc/ld.so.conf.d/oracle-21.conf
sudo ldconfig

# Kiểm tra
ls /usr/lib/oracle/21/client64/lib/
```

---

### BƯỚC 5 – Cài OCI8 PHP Extension *(thủ công)*

```bash
sudo apt-get install -y php8.1-dev php-pear build-essential

# Cài qua PECL
export ORACLE_HOME=/usr/lib/oracle/21/client64
sudo pecl install oci8
# Khi hỏi path: nhập instantclient,/usr/lib/oracle/21/client64/lib

# Enable extension
echo "extension=oci8.so" | sudo tee -a /etc/php/8.1/apache2/php.ini
echo "extension=oci8.so" | sudo tee -a /etc/php/8.1/cli/php.ini

sudo systemctl restart apache2

# Kiểm tra
php -m | grep oci8
php -r "echo oci_client_version();"
```

---

### BƯỚC 6 – Tạo Oracle User và Import Database

```bash
# Kết nối SQLPlus với SYS
sqlplus sys/YOUR_SYS_PASSWORD@localhost:1539/XEPDB1 as sysdba
```

Trong SQLPlus:
```sql
-- Tạo user
CREATE USER dbs401_user IDENTIFIED BY dbs401_pass;
GRANT CONNECT, RESOURCE, CREATE SESSION TO dbs401_user;
GRANT CREATE TABLE, CREATE SEQUENCE, CREATE VIEW TO dbs401_user;
ALTER USER dbs401_user QUOTA UNLIMITED ON USERS;
EXIT;
```

Import schema và seed:
```bash
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/schema.sql
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/seed.sql
```

Khởi tạo password hash (PHP bcrypt):
```bash
php database/init_passwords.php
```

---

### BƯỚC 7 – Deploy Web App

```bash
# Chỉnh sửa setup.sh nếu cần (DB credentials, service name)
sudo bash setup.sh
```

Hoặc thủ công:
```bash
sudo mkdir -p /var/www/html/dbs401-oracle-app
sudo cp -r . /var/www/html/dbs401-oracle-app/
sudo chown -R www-data:www-data /var/www/html/dbs401-oracle-app
sudo chmod -R 755 /var/www/html/dbs401-oracle-app
sudo systemctl reload apache2
```

---

### BƯỚC 8 – Truy cập Web

```
Local:  http://127.0.0.1/dbs401-oracle-app
LAN:    http://<IP-LAN>/dbs401-oracle-app

# Lấy IP LAN:
hostname -I | awk '{print $1}'
```

---

## 👤 Tài Khoản Demo

| Username | Password | Role |
|----------|----------|------|
| `admin` | `Admin@DBS401!2024` | Admin – toàn quyền |
| `teacher1` | `Teacher@123` | Teacher |
| `student1` | `Student@123` | Student |
| `student2` | `Student@123` | Student |
| `student3` | `Student@123` | Student |

---

## 🔧 Troubleshooting

### ❌ OCI8 not loaded

```bash
php -m | grep oci8
# Nếu không có:
php -i | grep -i oci
# Check php.ini có extension=oci8.so chưa
php --ini
grep oci8 /etc/php/8.1/apache2/php.ini
```

### ❌ ORA-12514: TNS:listener does not currently know of service

```bash
# Kiểm tra Oracle listener
sudo systemctl status oracle-xe-21c
lsnrctl status

# Restart Oracle
sudo systemctl restart oracle-xe-21c
```

### ❌ ORA-01017: invalid username/password

```bash
# Reset password Oracle user
sqlplus sys/SYS_PASS@localhost:1539/XEPDB1 as sysdba
ALTER USER dbs401_user IDENTIFIED BY dbs401_pass;
EXIT;
```

### ❌ Blank page PHP (lỗi PHP không hiển thị)

```bash
# Xem PHP error log
tail -f /var/log/apache2/error.log
# Enable error display (chỉ để debug, tắt sau khi xong)
php -r "phpinfo();" | grep error_reporting
```

### ❌ Apache 403 Permission Denied

```bash
sudo chown -R www-data:www-data /var/www/html/dbs401-oracle-app
sudo chmod -R 755 /var/www/html/dbs401-oracle-app
sudo chmod 640 /var/www/html/dbs401-oracle-app/config.php
sudo systemctl restart apache2
```

### ❌ Connection refused (Port 1539)

```bash
# Check Oracle listener port
sudo netstat -tlnp | grep 1539
# Hoặc:
sudo ss -tlnp | grep 1539
# Start listener:
lsnrctl start
```

### ❌ SQLPlus không kết nối được

```bash
# Kiểm tra tnsnames.ora
cat $ORACLE_HOME/network/admin/tnsnames.ora
# Test connection:
tnsping XE
# Thử với EZConnect:
sqlplus dbs401_user/dbs401_pass@//localhost:1539/XEPDB1
```

### ❌ PHP không load extension OCI8 (Apache2)

```bash
# OCI8 cần thư viện Oracle trong LD_LIBRARY_PATH
# Thêm vào /etc/apache2/envvars:
echo 'export LD_LIBRARY_PATH=/usr/lib/oracle/21/client64/lib:$LD_LIBRARY_PATH' \
     | sudo tee -a /etc/apache2/envvars
sudo systemctl restart apache2
```

### Reset Database (nếu dữ liệu bị sai)

```bash
# Drop và recreate toàn bộ
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 << 'EOF'
DROP TABLE ENROLLMENTS CASCADE CONSTRAINTS;
DROP TABLE STUDENTS CASCADE CONSTRAINTS;
DROP TABLE COURSES CASCADE CONSTRAINTS;
DROP TABLE USERS CASCADE CONSTRAINTS;
DROP TABLE AUDIT_LOGS;
DROP TABLE FLAGS;
DROP TABLE ADMIN_SECRETS;
DROP TABLE CONFIG_STORE;
DROP TABLE FAKE_FLAGS;
DROP TABLE SYSTEM_HINTS;
DROP TABLE FLAG_ARCHIVE;
EXIT;
EOF

sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/schema.sql
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/seed.sql
php database/init_passwords.php
```

---

## 📋 Checklist Trước Khi Demo

- [ ] Oracle Database XE đang chạy (`systemctl status oracle-xe-21c`)
- [ ] Oracle Listener đang nghe port 1539 (`lsnrctl status`)
- [ ] PHP OCI8 extension loaded (`php -m | grep oci8`)
- [ ] Apache2 đang chạy (`systemctl status apache2`)
- [ ] Web truy cập được tại `http://127.0.0.1/dbs401-oracle-app`
- [ ] Login được với `student1 / Student@123`
- [ ] search.php hoạt động (thử tìm "Nguyen")
- [ ] transcript.php hoạt động (click từ dashboard)
- [ ] store.php hiển thị credit và có item “Exam Leak 2024”
- [ ] partner_config.php bị ẩn khỏi navbar nhưng user thường truy cập được sau login
- [ ] admin.php?check_updates=1 trả thông báo baseline khi dùng manifest mặc định
- [ ] Chụp màn hình baseline để so sánh trước/sau khai thác

---

## 🛡️ Phiên Bản Vá Lỗi (Secure Versions)

| File Vulnerable | File Secure | Thay đổi |
|----------------|-------------|---------|
| `search.php` | `secure_versions/search_secure.php` | Bind variables + input whitelist |
| `store.php` | `secure_versions/store_secure.php` | Kiểm tra giá trị dương cho số lượng |
| `partner_config.php` | `secure_versions/partner_config_secure.php` | Bắt buộc admin role + whitelist URL |
| `admin.php` | `secure_versions/admin_update_secure.php` | Whitelist URL cập nhật + xác thực manifest |

---

*DBS401 – Group 02 – FPT University*  
*⚠️ Lab use only. Do not deploy on internet or real systems.*
