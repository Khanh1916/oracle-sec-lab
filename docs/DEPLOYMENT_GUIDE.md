# Hướng Dẫn Triển Khai Chi Tiết – DBS401 Group 02
## FPT Student Portal – Oracle Security Lab

> Dành cho người mới, viết từng bước cụ thể.

---

## MỤC LỤC NHANH

| Bước | Nội dung | Thời gian ước tính |
|------|---------|-------------------|
| 1 | Chuẩn bị Ubuntu | 10 phút |
| 2 | Cài Apache2 | 5 phút |
| 3 | Cài PHP 8.1 | 5 phút |
| 4 | Cài Oracle XE 21c | 30–60 phút |
| 5 | Cài Instant Client + OCI8 | 20 phút |
| 6 | Tạo Oracle user + Import DB | 10 phút |
| 7 | Deploy Web App | 5 phút |
| 8 | Test & Verify | 10 phút |

---

## BƯỚC 1 – Chuẩn bị Ubuntu

Khuyến nghị: Ubuntu 22.04 LTS trên VirtualBox hoặc VMware.

```bash
# Update hệ thống
sudo apt-get update && sudo apt-get upgrade -y

# Cài các công cụ cần thiết
sudo apt-get install -y curl wget unzip git net-tools lsof
```

Kiểm tra phiên bản Ubuntu:
```bash
lsb_release -a
```

---

## BƯỚC 2 – Cài Apache2

```bash
sudo apt-get install -y apache2

# Khởi động và enable
sudo systemctl start apache2
sudo systemctl enable apache2

# Kiểm tra
sudo systemctl status apache2
curl -s http://127.0.0.1 | grep -o 'Apache.*HTML'
```

✅ Nếu thấy "Apache" → thành công.

---

## BƯỚC 3 – Cài PHP 8.1

```bash
# Thêm repo PHP ondrej (hỗ trợ PHP 8.1 trên Ubuntu 22.04)
sudo apt-get install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update

# Cài PHP 8.1 và các module cần
sudo apt-get install -y \
    php8.1 \
    libapache2-mod-php8.1 \
    php8.1-cli \
    php8.1-common \
    php8.1-dev \
    php-pear \
    build-essential

# Enable PHP mod
sudo a2enmod php8.1
sudo systemctl restart apache2

# Kiểm tra
php -v
php -m | grep -i "json\|session\|mbstring"
```

✅ Phải thấy `PHP 8.1.x` trong output.

---

## BƯỚC 4 – Cài Oracle Database XE 21c *(Manual – ~30-60 phút)*

### 4.1 Tải Oracle XE

Truy cập: https://www.oracle.com/database/technologies/xe-downloads.html
Tải file: `oracle-database-xe-21c-1.0-1.ol8.x86_64.rpm`

> Cần tạo tài khoản Oracle miễn phí để tải.

**Hoặc dùng Oracle 23c Free (nhẹ hơn):**
https://www.oracle.com/database/free/

### 4.2 Cài đặt

```bash
# Cài dependencies
sudo apt-get install -y alien libaio1 bc libaio-dev

# Chuyển RPM → DEB (mất 5-10 phút)
sudo alien --to-deb --scripts oracle-database-xe-21c-1.0-1.ol8.x86_64.rpm

# Cài DEB (mất 10-20 phút)
sudo dpkg -i oracle-database-xe-21c_21.0-2_amd64.deb

# Nếu bị lỗi dependency:
sudo apt-get install -f
```

### 4.3 Cấu hình Oracle XE

```bash
sudo /etc/init.d/oracle-xe-21c configure
```

Nhập khi được hỏi:
- Oracle Database XE password: **oracle** (hoặc tùy chọn, nhớ lại)
- Confirm password: **oracle**
- Port: **1539** (nhấn Enter giữ mặc định)

### 4.4 Cấu hình biến môi trường

Thêm vào `~/.bashrc`:
```bash
cat >> ~/.bashrc << 'EOF'

# Oracle XE Environment
export ORACLE_HOME=/opt/oracle/product/21c/dbhomeXE
export ORACLE_SID=XE
export PATH=$ORACLE_HOME/bin:$PATH
export LD_LIBRARY_PATH=$ORACLE_HOME/lib:$LD_LIBRARY_PATH
EOF

source ~/.bashrc
```

### 4.5 Khởi động Oracle

```bash
sudo systemctl start oracle-xe-21c
sudo systemctl enable oracle-xe-21c

# Kiểm tra listener
lsnrctl status

# Kiểm tra kết nối
sqlplus / as sysdba << 'EOF'
SELECT STATUS FROM V$INSTANCE;
EXIT;
EOF
```

✅ Phải thấy `STATUS: OPEN`

---

## BƯỚC 5 – Cài Oracle Instant Client + OCI8 *(Manual)*

### 5.1 Tải Oracle Instant Client

Truy cập: https://www.oracle.com/database/technologies/instant-client/linux-x86-64-downloads.html

Tải 2 file (phiên bản 21.x):
- `oracle-instantclient-basiclite-21.x.x.x-1.x86_64.rpm`
- `oracle-instantclient-devel-21.x.x.x-1.x86_64.rpm`

### 5.2 Cài đặt

```bash
# Chuyển RPM → DEB
sudo alien --to-deb oracle-instantclient-basiclite-21.*.rpm
sudo alien --to-deb oracle-instantclient-devel-21.*.rpm

# Cài
sudo dpkg -i oracle-instantclient-basiclite_*.deb
sudo dpkg -i oracle-instantclient-devel_*.deb

# Cấu hình ld
echo "/usr/lib/oracle/21/client64/lib" | sudo tee /etc/ld.so.conf.d/oracle-21.conf
sudo ldconfig

# Kiểm tra
ls /usr/lib/oracle/21/client64/lib/
```

### 5.3 Cài OCI8 PHP Extension

```bash
export ORACLE_HOME=/usr/lib/oracle/21/client64

# Cài qua PECL
sudo pecl install oci8

# Khi được hỏi:
# "Please provide the path to ORACLE_HOME directory:"
# Nhập: instantclient,/usr/lib/oracle/21/client64/lib
```

### 5.4 Enable OCI8

```bash
# Thêm extension vào php.ini (cả CLI và Apache)
echo "extension=oci8.so" | sudo tee -a /etc/php/8.1/apache2/php.ini
echo "extension=oci8.so" | sudo tee -a /etc/php/8.1/cli/php.ini

# Thêm LD_LIBRARY_PATH cho Apache
echo 'export LD_LIBRARY_PATH=/usr/lib/oracle/21/client64/lib:$LD_LIBRARY_PATH' \
    | sudo tee -a /etc/apache2/envvars

# Restart Apache
sudo systemctl restart apache2

# Kiểm tra
php -m | grep oci8
# → Phải thấy: oci8
php -r "echo oci_client_version() . PHP_EOL;"
# → Phải thấy version string của Oracle client
```

✅ Nếu thấy `oci8` trong `php -m` → thành công.

---

## BƯỚC 6 – Tạo Oracle User + Import Database

### 6.1 Tạo Oracle User DBS401

```bash
sqlplus sys/oracle@localhost:1539/XEPDB1 as sysdba
```

Trong SQL*Plus:
```sql
-- Tạo user
CREATE USER dbs401_user IDENTIFIED BY dbs401_pass;

-- Gán quyền cần thiết
GRANT CONNECT, RESOURCE, CREATE SESSION TO dbs401_user;
GRANT CREATE TABLE, CREATE SEQUENCE, CREATE VIEW TO dbs401_user;
GRANT CREATE PROCEDURE, CREATE TRIGGER TO dbs401_user;
ALTER USER dbs401_user QUOTA UNLIMITED ON USERS;

-- Xác nhận
SELECT username, account_status FROM DBA_USERS WHERE username = 'DBS401_USER';

EXIT;
```

### 6.2 Import Schema

```bash
cd /path/to/dbs401-oracle-app/

sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/schema.sql
```

✅ Nếu không có lỗi ORA- → thành công.

### 6.3 Import Seed Data

```bash
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/seed.sql
```

### 6.4 Fix References (quan trọng)

```bash
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 @database/fix_refs.sql
```

> Script này cập nhật `admin_ref_id` trong ENROLLMENTS để trỏ đúng đến log_id thực tế.

### 6.5 Khởi tạo Password Hash

```bash
php database/init_passwords.php
```

✅ Phải thấy "Updated password for: admin", "Updated password for: student1", ...

---

## BƯỚC 7 – Deploy Web App

### 7.1 Chạy setup.sh

```bash
sudo bash setup.sh
```

Hoặc thủ công:
```bash
# Copy source
sudo mkdir -p /var/www/html/dbs401-oracle-app
sudo cp -r . /var/www/html/dbs401-oracle-app/
sudo chown -R www-data:www-data /var/www/html/dbs401-oracle-app
sudo chmod -R 755 /var/www/html/dbs401-oracle-app
sudo chmod 640 /var/www/html/dbs401-oracle-app/config.php

# Cấu hình Apache alias
sudo bash -c 'cat > /etc/apache2/conf-available/dbs401.conf << CONF
Alias /dbs401-oracle-app /var/www/html/dbs401-oracle-app
<Directory /var/www/html/dbs401-oracle-app>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.php
</Directory>
CONF'

sudo a2enconf dbs401
sudo a2enmod rewrite
sudo systemctl reload apache2
```

---

## BƯỚC 8 – Test & Verify

### 8.1 Truy cập Web

```bash
# Lấy IP LAN
ip addr show | grep 'inet ' | grep -v '127.0.0.1'
# hoặc:
hostname -I | awk '{print $1}'
```

```
http://127.0.0.1/dbs401-oracle-app      ← chỉ từ máy host
http://192.168.x.x/dbs401-oracle-app   ← từ máy khác trong LAN
```

### 8.2 Test Login

| Account | Password | Expect |
|---------|----------|--------|
| student1 | Student@123 | Dashboard với enrollment list |
| teacher1 | Teacher@123 | Dashboard với search link |
| admin | Admin@DBS401!2024 | Dashboard với Admin Panel link |

### 8.3 Test Từng Chức Năng

```bash
# Test search (Vuln 1)
curl "http://127.0.0.1/dbs401-oracle-app/search.php?q=Nguyen" \
     -b "DBS401_SESSION=..." | grep student_id

# Test store credits (Vuln 2)
curl -X POST "http://127.0.0.1/dbs401-oracle-app/store.php" \
     -d "buy=1&quantity=-1000" \
     -b "DBS401_SESSION=..."

# Test update check (Vuln 3)
curl "http://127.0.0.1/dbs401-oracle-app/admin.php?check_updates=1" \
     -b "DBS401_SESSION=..."
```

### 8.4 Verify Database Data

```bash
sqlplus dbs401_user/dbs401_pass@localhost:1539/XEPDB1 << 'EOF'
-- Kiểm tra bảng FLAGS
SELECT flag_id, flag_code, is_active FROM FLAGS;

-- Kiểm tra ADMIN_SECRETS
SELECT secret_id, secret_key, is_active FROM ADMIN_SECRETS;

-- Kiểm tra hidden student
SELECT student_id, full_name, hidden_marker FROM STUDENTS;

-- Kiểm tra admin_ref_id đã được cập nhật
SELECT e.transcript_ref, e.admin_ref_id, a.action
FROM ENROLLMENTS e JOIN AUDIT_LOGS a ON e.admin_ref_id = a.log_id
WHERE e.transcript_ref = 'TXN-099-2024-S1';

EXIT;
EOF
```

---

## TROUBLESHOOTING NHANH

| Lỗi | Nguyên nhân | Giải pháp |
|-----|-------------|-----------|
| `OCI8 not loaded` | Extension chưa enable | Thêm `extension=oci8.so` vào php.ini, restart Apache |
| `ORA-12514` | Oracle listener không nhận service XE | `lsnrctl reload`, check tnsnames.ora |
| `ORA-01017` | Sai user/password Oracle | Kiểm tra `dbs401_user/dbs401_pass`, reset nếu cần |
| `403 Forbidden` | Permission sai | `sudo chown -R www-data:www-data /var/www/html/dbs401-oracle-app` |
| Blank page PHP | PHP error ẩn | `tail -f /var/log/apache2/error.log` |
| `Connection refused 1539` | Oracle chưa chạy | `sudo systemctl start oracle-xe-21c` |
| OCI8 load OK nhưng connect lỗi | LD_LIBRARY_PATH | Thêm vào `/etc/apache2/envvars`, restart Apache |

---

## LẤY IP LAN ĐỂ NHÓM KHÁC TEST

```bash
# Cách 1
hostname -I | awk '{print $1}'

# Cách 2
ip route get 8.8.8.8 | grep -oP '(?<=src )\S+'

# Cách 3
ifconfig eth0 | grep 'inet ' | awk '{print $2}'
```

Sau khi có IP (ví dụ: `192.168.1.25`):
```
http://192.168.1.25/dbs401-oracle-app
```

> Máy host phải tắt firewall hoặc cho phép port 80:
> ```bash
> sudo ufw allow 80/tcp
> ```

---

*DBS401 – Group 02 – Hướng dẫn triển khai nội bộ*
