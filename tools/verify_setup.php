<?php
/**
 * DBS401 - Group 02
 * tools/verify_setup.php
 *
 * Script kiểm tra toàn bộ cài đặt của project.
 * Chạy từ command line: php tools/verify_setup.php
 * Hoặc từ browser: http://127.0.0.1/dbs401-oracle-app/tools/verify_setup.php
 *
 * ⚠️  Xóa hoặc bảo vệ file này sau khi setup xong!
 */

// Allow CLI or browser
$isCli = (php_sapi_name() === 'cli');

function out(string $msg, string $level = 'info'): void {
    global $isCli;
    $icons = ['ok' => '✅', 'fail' => '❌', 'warn' => '⚠️ ', 'info' => 'ℹ️ ', 'head' => '━━'];
    $icon  = $icons[$level] ?? '  ';
    if ($isCli) {
        echo "$icon $msg\n";
    } else {
        $color = match($level) {
            'ok'   => '#2d6a4f',
            'fail' => '#d62828',
            'warn' => '#b45309',
            'head' => '#023e8a',
            default => '#333'
        };
        echo "<div style='font-family:monospace;padding:3px 0;color:$color'>$icon " . htmlspecialchars($msg) . "</div>\n";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>DBS401 Setup Verify</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#f8f9fa}";
    echo "h1{color:#023e8a} h2{color:#e85d04;border-bottom:2px solid #e85d04;padding-bottom:4px}";
    echo ".section{background:#fff;border-radius:8px;padding:20px;margin:16px 0;box-shadow:0 2px 8px rgba(0,0,0,.1)}</style></head><body>";
    echo "<h1>🔍 DBS401 Group 02 – Setup Verification</h1>";
}

$passCount = 0;
$failCount = 0;
$warnCount = 0;

function check(bool $condition, string $passMsg, string $failMsg, bool $critical = true): void {
    global $passCount, $failCount;
    if ($condition) { out($passMsg, 'ok'); $passCount++; }
    else            { out($failMsg, $critical ? 'fail' : 'warn'); $failCount++; }
}

// ─── 1. PHP Version ──────────────────────────────────────────────
if (!$isCli) echo "<div class='section'><h2>1. PHP Environment</h2>";
else out("═══ PHP Environment ═══", 'head');

check(version_compare(PHP_VERSION, '8.0.0', '>='),
    'PHP Version: ' . PHP_VERSION,
    'PHP Version too old: ' . PHP_VERSION . ' (need 8.0+)');

check(extension_loaded('oci8'),
    'OCI8 extension: LOADED (' . (function_exists('oci_client_version') ? oci_client_version() : 'unknown') . ')',
    'OCI8 extension: NOT LOADED! Run: pecl install oci8');

check(extension_loaded('session'),   'session extension: OK',   'session extension: MISSING');
check(extension_loaded('json'),      'json extension: OK',      'json extension: MISSING');
check(extension_loaded('mbstring'),  'mbstring extension: OK',  'mbstring extension: MISSING', false);

if (!$isCli) echo "</div>";

// ─── 2. Oracle Connection ────────────────────────────────────────
if (!$isCli) echo "<div class='section'><h2>2. Oracle Database Connection</h2>";
else out("═══ Oracle Database ═══", 'head');

$tns  = '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=localhost)(PORT=1539))(CONNECT_DATA=(SERVICE_NAME=XE)))';
$conn = null;
if (extension_loaded('oci8')) {
    $conn = @oci_connect('dbs401_user', 'dbs401_pass', $tns, 'AL32UTF8');
    if ($conn) {
        out('Oracle connection: SUCCESS (dbs401_user@localhost:1539/XEPDB1)', 'ok');
        $passCount++;
    } else {
        $e = oci_error();
        out('Oracle connection: FAILED – ' . ($e['message'] ?? 'unknown error'), 'fail');
        $failCount++;
    }
} else {
    out('Oracle connection: SKIPPED (OCI8 not loaded)', 'warn');
    $warnCount++;
}

if (!$isCli) echo "</div>";

// ─── 3. Database Tables ──────────────────────────────────────────
if (!$isCli) echo "<div class='section'><h2>3. Database Tables</h2>";
else out("═══ Database Tables ═══", 'head');

$requiredTables = [
    'USERS', 'STUDENTS', 'COURSES', 'ENROLLMENTS', 'AUDIT_LOGS',
    'FLAGS', 'ADMIN_SECRETS', 'CONFIG_STORE', 'FAKE_FLAGS',
    'SYSTEM_HINTS', 'FLAG_ARCHIVE'
];

if ($conn) {
    foreach ($requiredTables as $table) {
        $sql  = "SELECT COUNT(*) AS cnt FROM USER_TABLES WHERE table_name = :tn";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':tn', $table);
        oci_execute($stmt);
        $row  = oci_fetch_assoc($stmt);
        $exists = (int)($row['CNT'] ?? 0) > 0;
        check($exists, "Table $table: EXISTS", "Table $table: MISSING – run schema.sql");
    }
} else {
    out('Table checks: SKIPPED (no DB connection)', 'warn');
    $warnCount++;
}

if (!$isCli) echo "</div>";

// ─── 4. Data Integrity ───────────────────────────────────────────
if (!$isCli) echo "<div class='section'><h2>4. CTF Data Integrity</h2>";
else out("═══ CTF Data Integrity ═══", 'head');

if ($conn) {
    // Check users
    $sql = "SELECT COUNT(*) AS cnt FROM USERS";
    $stmt = oci_parse($conn, $sql); oci_execute($stmt);
    $row  = oci_fetch_assoc($stmt);
    check((int)$row['CNT'] >= 5, "USERS: " . $row['CNT'] . " accounts found", "USERS: Too few accounts – run seed.sql");

    // Check flags
    $sql = "SELECT COUNT(*) AS cnt FROM FLAGS WHERE is_active = 1";
    $stmt = oci_parse($conn, $sql); oci_execute($stmt);
    $row  = oci_fetch_assoc($stmt);
    check((int)$row['CNT'] >= 2, "FLAGS (active): " . $row['CNT'] . " records found", "FLAGS: No active flag data – run seed.sql");

    // Check admin_secrets
    $sql = "SELECT COUNT(*) AS cnt FROM ADMIN_SECRETS WHERE is_active = 1";
    $stmt = oci_parse($conn, $sql); oci_execute($stmt);
    $row  = oci_fetch_assoc($stmt);
    check((int)$row['CNT'] >= 2, "ADMIN_SECRETS (active): " . $row['CNT'] . " records", "ADMIN_SECRETS: Too few active records");

    // Check Flag 1 Part A
    $sql  = "SELECT COUNT(*) AS cnt FROM FLAGS WHERE flag_code = 'FL1_PART_A' AND is_active = 1";
    $stmt = oci_parse($conn, $sql); oci_execute($stmt);
    $row  = oci_fetch_assoc($stmt);
    check((int)$row['CNT'] > 0, "Flag 1 Part A: FOUND in FLAGS table", "Flag 1 Part A: MISSING – check seed.sql");

    // Check Flag 1 Part B
    $sql  = "SELECT COUNT(*) AS cnt FROM AUDIT_LOGS WHERE action = 'SYSTEM_AUDIT_CHECK'";
    $stmt = oci_parse($conn, $sql); oci_execute($stmt);
    $row  = oci_fetch_assoc($stmt);
    check((int)$row['CNT'] > 0, "Flag 1 Part B: FOUND in AUDIT_LOGS", "Flag 1 Part B: MISSING in AUDIT_LOGS");

    // Check Flag 1 Part C
    $sql  = "SELECT COUNT(*) AS cnt FROM CONFIG_STORE WHERE config_key = 'sys_alpha_marker'";
    $stmt = oci_parse($conn, $sql); oci_execute($stmt);
    $row  = oci_fetch_assoc($stmt);
    check((int)$row['CNT'] > 0, "Flag 1 Part C: FOUND in CONFIG_STORE", "Flag 1 Part C: MISSING – check seed.sql");

    // Check hidden student enrollment
    $sql  = "SELECT COUNT(*) AS cnt FROM ENROLLMENTS WHERE transcript_ref = 'TXN-099-2024-S1'";
    $stmt = oci_parse($conn, $sql); oci_execute($stmt);
    $row  = oci_fetch_assoc($stmt);
    check((int)$row['CNT'] > 0, "Hidden enrollment TXN-099-2024-S1: FOUND", "Hidden enrollment: MISSING – check seed.sql");

    // Check admin_ref_id linked correctly
    $sql  = "SELECT e.admin_ref_id FROM ENROLLMENTS e WHERE e.transcript_ref = 'TXN-099-2024-S1' AND e.admin_ref_id IS NOT NULL";
    $stmt = oci_parse($conn, $sql); oci_execute($stmt);
    $row  = oci_fetch_assoc($stmt);
    $refOk = $row !== false && !empty($row['ADMIN_REF_ID']);
    check($refOk,
        "admin_ref_id linked: LOG-" . ($row['ADMIN_REF_ID'] ?? 'N/A'),
        "admin_ref_id NOT linked! Run database/fix_refs.sql");

    // Check Flag 3 real key
    $sql  = "SELECT COUNT(*) AS cnt FROM ADMIN_SECRETS WHERE secret_key = 'oracle_flag_3_primary' AND is_active = 1";
    $stmt = oci_parse($conn, $sql); oci_execute($stmt);
    $row  = oci_fetch_assoc($stmt);
    check((int)$row['CNT'] > 0, "Flag 3 secret key: FOUND (oracle_flag_3_primary)", "Flag 3 secret key: MISSING");

    // Check Flag 3 suffix in config
    $sql  = "SELECT COUNT(*) AS cnt FROM CONFIG_STORE WHERE config_key = 'oracle_flag_3_suffix'";
    $stmt = oci_parse($conn, $sql); oci_execute($stmt);
    $row  = oci_fetch_assoc($stmt);
    check((int)$row['CNT'] > 0, "Flag 3 suffix: FOUND in CONFIG_STORE", "Flag 3 suffix: MISSING – check seed.sql");

    // Check password hashes updated (not placeholder)
    $sql  = "SELECT password_hash FROM USERS WHERE username = 'admin'";
    $stmt = oci_parse($conn, $sql); oci_execute($stmt);
    $row  = oci_fetch_assoc($stmt);
    $hashOk = !empty($row['PASSWORD_HASH']) && str_starts_with($row['PASSWORD_HASH'], '$2y$');
    check($hashOk,
        "Admin password hash: bcrypt format OK",
        "Admin password hash: Still placeholder! Run: php database/init_passwords.php");

} else {
    out('Data integrity checks: SKIPPED (no DB connection)', 'warn');
    $warnCount++;
}

if (!$isCli) echo "</div>";

// ─── 5. File Structure ───────────────────────────────────────────
if (!$isCli) echo "<div class='section'><h2>5. File Structure</h2>";
else out("═══ File Structure ═══", 'head');

$baseDir = dirname(__DIR__);
$requiredFiles = [
    'config.php', 'index.php', 'login.php', 'logout.php',
    'dashboard.php', 'search.php', 'profile.php', 'transcript.php',
    'audit.php', 'admin.php', 'secret_check.php', 'inc_navbar.php',
    'style.css', '.htaccess',
    'database/schema.sql', 'database/seed.sql', // Các file này có thể bị xóa sau hardening
    'database/fix_refs.sql', 'database/init_passwords.php',
    'secure_versions/search_secure.php',
    'secure_versions/transcript_secure.php',
    'secure_versions/secret_check_secure.php',
    'tools/exploit_flag3_local.py',
    'tools/decode_helper.py',
    'setup.sh',
    'ANSWER_KEY.md', 'REPORT_DBS401.md', 'README.md', 'CHECKLIST.md',
];

foreach ($requiredFiles as $file) {
    $path = $baseDir . '/' . $file;
    $isSql = str_ends_with($file, '.sql');
    // Nếu là file SQL và bị thiếu, chỉ báo WARN vì có thể đã bị setup.sh xóa để bảo mật
    check(file_exists($path), "File exists: $file", "File MISSING: $file", !$isSql);
}

if (!$isCli) echo "</div>";

// ─── 6. Web Endpoints ────────────────────────────────────────────
if (!$isCli) {
    echo "<div class='section'><h2>6. Quick Links</h2>";
    $links = [
        'index.php'           => 'Home (redirect)',
        'login.php'           => 'Login Page',
        'dashboard.php'       => 'Dashboard',
        'search.php'          => 'Search [VULN 1]',
        'transcript.php'      => 'Transcript [VULN 2]',
        'secret_check.php?key=sys_master_key' => 'Secret Check API [VULN 3]',
        'admin.php'           => 'Admin Panel',
        'audit.php'           => 'Audit Logs',
    ];
    foreach ($links as $url => $label) {
        echo "<div style='margin:4px 0'>";
        echo "<a href='../$url' target='_blank' style='color:#e85d04'>$label</a>";
        echo " – <code style='font-size:.85em'>../$url</code></div>\n";
    }
    echo "</div>";
}

// ─── Summary ─────────────────────────────────────────────────────
if (!$isCli) echo "<div class='section'><h2>Summary</h2>";
else out("═══ Summary ═══", 'head');

$total = $passCount + $failCount + $warnCount;
out("Total checks : $total", 'info');
out("Passed       : $passCount", 'ok');
if ($failCount > 0) out("Failed       : $failCount  ← Fix these before demo!", 'fail');
if ($warnCount > 0) out("Warnings     : $warnCount", 'warn');

if ($failCount === 0) {
    out("🎉 All critical checks PASSED! Project is ready for demo.", 'ok');
} else {
    out("⚠️  $failCount critical issue(s) found. See above for fix instructions.", 'warn');
}

if ($conn) oci_close($conn);

if (!$isCli) echo "</div></body></html>";
