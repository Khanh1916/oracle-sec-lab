<?php
/**
 * OracleSecLab
 * tools/verify_setup.php
 *
 * Pre-flight verification script to check environment and challenge status:
 *   - Vuln 1: search.php SQL Injection
 *   - Vuln 2: store.php Negative Quantity / Credits Manipulation
 *   - Vuln 3: partner_config.php Broken Access Control + admin.php Supply Chain trigger
 *
 * CLI Execution: php tools/verify_setup.php
 * ⚠️ Restrict or remove this file in public CTF competitive environments.
 */

$isCli = (php_sapi_name() === 'cli');
$baseDir = dirname(__DIR__);

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

$passCount = 0;
$failCount = 0;
$warnCount = 0;

function check(bool $condition, string $passMsg, string $failMsg, bool $critical = true): void {
    global $passCount, $failCount, $warnCount;
    if ($condition) {
        out($passMsg, 'ok');
        $passCount++;
    } elseif ($critical) {
        out($failMsg, 'fail');
        $failCount++;
    } else {
        out($failMsg, 'warn');
        $warnCount++;
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>OracleSecLab Setup Verification</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#f8f9fa}";
    echo "h1{color:#023e8a} h2{color:#e85d04;border-bottom:2px solid #e85d04;padding-bottom:4px}";
    echo ".section{background:#fff;border-radius:8px;padding:20px;margin:16px 0;box-shadow:0 2px 8px rgba(0,0,0,.1)}</style></head><body>";
    echo "<h1>🔍 OracleSecLab – Pre-flight Verification</h1>";
}

// ─── 1. PHP Environment ─────────────────────────────────────────
if (!$isCli) echo "<div class='section'><h2>1. PHP Environment</h2>";
else out("═══ PHP Environment ═══", 'head');

check(version_compare(PHP_VERSION, '8.0.0', '>='),
    'PHP Version: ' . PHP_VERSION,
    'PHP Version too old: ' . PHP_VERSION . ' (need 8.0+)');
check(extension_loaded('oci8'), 'OCI8 extension: LOADED', 'OCI8 extension: NOT LOADED');
check(extension_loaded('session'), 'session extension: OK', 'session extension: MISSING');
check(extension_loaded('json'), 'json extension: OK', 'json extension: MISSING');

if (!$isCli) echo "</div>";

// ─── 2. Config + Oracle ─────────────────────────────────────────
if (!$isCli) echo "<div class='section'><h2>2. App Config & Oracle</h2>";
else out("═══ App Config & Oracle ═══", 'head');

$configPath = $baseDir . '/config.php';
check(file_exists($configPath), 'config.php: FOUND', 'config.php: MISSING');

$conn = null;
if (file_exists($configPath) && extension_loaded('oci8')) {
    require_once $configPath;
    check(DB_SERVICE === 'XEPDB1' || DB_SERVICE === 'FREE', 'DB_SERVICE: ' . DB_SERVICE, 'DB_SERVICE unexpected: ' . DB_SERVICE, false);
    check(APP_VERSION === '3.1.0', 'APP_VERSION: 3.1.0', 'APP_VERSION should be 3.1.0 for Vuln 3 version_compare');
    check(DEFAULT_UPDATE_URL === 'http://127.0.0.1:8081/manifest.json',
        'DEFAULT_UPDATE_URL: baseline partner manifest',
        'DEFAULT_UPDATE_URL mismatch');

    $conn = @getDbConnection();
    check((bool)$conn,
        'Oracle connection: SUCCESS (' . DB_USER . '@' . DB_HOST . ':' . DB_PORT . '/' . DB_SERVICE . ')',
        'Oracle connection: FAILED');
} else {
    out('Oracle connection: SKIPPED (missing config or OCI8)', 'warn');
    $warnCount++;
}

if (!$isCli) echo "</div>";

// ─── 3. Database Tables ─────────────────────────────────────────
if (!$isCli) echo "<div class='section'><h2>3. Database Tables</h2>";
else out("═══ Database Tables ═══", 'head');

$requiredTables = [
    'USERS', 'STUDENTS', 'COURSES', 'ENROLLMENTS', 'AUDIT_LOGS',
    'FLAGS', 'ADMIN_SECRETS', 'CONFIG_STORE', 'FAKE_FLAGS',
    'SYSTEM_HINTS', 'FLAG_ARCHIVE'
];

if ($conn) {
    foreach ($requiredTables as $table) {
        $stmt = oci_parse($conn, "SELECT COUNT(*) AS cnt FROM USER_TABLES WHERE table_name = :tn");
        oci_bind_by_name($stmt, ':tn', $table);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        check((int)($row['CNT'] ?? 0) > 0, "Table $table: EXISTS", "Table $table: MISSING – run schema.sql");
    }
} else {
    out('Table checks: SKIPPED', 'warn');
    $warnCount++;
}

if (!$isCli) echo "</div>";

// ─── 4. CTF Data Integrity ──────────────────────────────────────
if (!$isCli) echo "<div class='section'><h2>4. CTF Data Integrity</h2>";
else out("═══ CTF Data Integrity ═══", 'head');

if ($conn) {
    $checks = [
        ["SELECT COUNT(*) AS cnt FROM USERS WHERE username IN ('admin','student1','teacher1')", 3, 'Demo accounts'],
        ["SELECT COUNT(*) AS cnt FROM FLAGS WHERE flag_code = 'FL1_PART_A' AND is_active = 1", 1, 'Flag 1 Part A in FLAGS'],
        ["SELECT COUNT(*) AS cnt FROM AUDIT_LOGS WHERE action = 'SYSTEM_AUDIT_CHECK'", 1, 'Flag 1 Part B in AUDIT_LOGS'],
        ["SELECT COUNT(*) AS cnt FROM CONFIG_STORE WHERE config_key = 'sys_alpha_marker'", 1, 'Flag 1 Part C in CONFIG_STORE'],
        ["SELECT COUNT(*) AS cnt FROM CONFIG_STORE WHERE config_key = 'update_url' AND config_value = 'http://127.0.0.1:8081/manifest.json'", 1, 'Vuln 3 baseline update_url'],
        ["SELECT COUNT(*) AS cnt FROM CONFIG_STORE WHERE config_key = 'app_version' AND config_value = '3.1.0'", 1, 'Vuln 3 app_version'],
        ["SELECT COUNT(*) AS cnt FROM SYSTEM_HINTS WHERE related_vuln = 'VULN3'", 2, 'Vuln 3 hints'],
    ];
    foreach ($checks as [$sql, $min, $label]) {
        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        $cnt = (int)($row['CNT'] ?? 0);
        check($cnt >= $min, "$label: OK ($cnt)", "$label: MISSING/LOW ($cnt)");
    }

    $stmt = oci_parse($conn, "SELECT credits FROM STUDENTS WHERE user_id = (SELECT user_id FROM USERS WHERE username='student1')");
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    check($row !== false && (int)$row['CREDITS'] < 999999,
        'student1 baseline credits below Exam Leak price',
        'student1 credits already high; reset DB before demo',
        false);
} else {
    out('CTF data checks: SKIPPED', 'warn');
    $warnCount++;
}

if (!$isCli) echo "</div>";

// ─── 5. Source Consistency ──────────────────────────────────────
if (!$isCli) echo "<div class='section'><h2>5. Source Consistency</h2>";
else out("═══ Source Consistency ═══", 'head');

$adminSource = @file_get_contents($baseDir . '/admin.php') ?: '';
$partnerSource = @file_get_contents($baseDir . '/partner_config.php') ?: '';
$storeSource = @file_get_contents($baseDir . '/store.php') ?: '';

check(str_contains($adminSource, 'FLAG3_LOCAL_HEX_SUFFIX'),
    'admin.php: server-side Flag 3 suffix present',
    'admin.php: FLAG3_LOCAL_HEX_SUFFIX missing');
check(str_contains($adminSource, 'decodeHexFlagFragment') && str_contains($adminSource, "['flag_part']"),
    'admin.php: combines manifest flag_part with server-side suffix',
    'admin.php: manifest flag_part decode flow missing');
check(str_contains($partnerSource, "\$_SESSION['role']") === false && str_contains($partnerSource, "CONFIG_STORE"),
    'partner_config.php: intentionally missing role check and updates CONFIG_STORE',
    'partner_config.php: vulnerability changed or missing');
check(str_contains($storeSource, '$cost = $qty * $price') && !str_contains($storeSource, '$qty <= 0'),
    'store.php: negative quantity vulnerability still present',
    'store.php: negative quantity vulnerability appears patched; demo may fail',
    false);

if (!$isCli) echo "</div>";

// ─── 6. File Structure ──────────────────────────────────────────
if (!$isCli) echo "<div class='section'><h2>6. File Structure</h2>";
else out("═══ File Structure ═══", 'head');

$requiredFiles = [
    'config.php', 'index.php', 'login.php', 'logout.php',
    'dashboard.php', 'courses.php', 'schedule.php', 'grades.php', 'tuition.php',
    'search.php', 'store.php', 'partner_config.php',
    'profile.php', 'transcript.php', 'audit.php', 'admin.php',
    'secret_check.php', 'inc_navbar.php', 'style.css', '.htaccess',
    'database/schema.sql', 'database/seed.sql', 'database/fix_refs.sql', 'database/init_passwords.php',
    'secure_versions/search_secure.php', 'secure_versions/store_secure.php',
    'secure_versions/partner_config_secure.php', 'secure_versions/admin_update_secure.php',
    'tools/exploit_flag3_local.py', 'tools/decode_helper.py',
    'setup.sh', 'docs/ANSWER_KEY.md', 'README.md', 'LICENSE',
];
foreach ($requiredFiles as $file) {
    $path = $baseDir . '/' . $file;
    $isSql = str_ends_with($file, '.sql');
    check(file_exists($path), "File exists: $file", "File MISSING: $file", !$isSql);
}

if (!$isCli) echo "</div>";

// ─── 7. Quick Links ─────────────────────────────────────────────
if (!$isCli) {
    echo "<div class='section'><h2>7. Quick Links</h2>";
    $links = [
        'login.php' => 'Login Page',
        'dashboard.php' => 'Dashboard',
        'courses.php' => 'Course Catalog & Registration',
        'schedule.php' => 'Weekly Timetable',
        'grades.php' => 'Faculty Grading Portal',
        'tuition.php' => 'Tuition & Fee Status',
        'search.php' => 'Search [VULN 1: SQLi]',
        'store.php' => 'Store [VULN 2: Negative Quantity]',
        'partner_config.php' => 'Hidden Partner Config [VULN 3A: Broken Access Control]',
        'admin.php?check_updates=1' => 'Admin Update Trigger [VULN 3B: Supply Chain]',
        'audit.php' => 'Audit Logs',
    ];
    foreach ($links as $url => $label) {
        echo "<div style='margin:4px 0'><a href='../$url' target='_blank' style='color:#e85d04'>$label</a> – <code>../$url</code></div>\n";
    }
    echo "</div>";
}

// ─── Summary ───────────────────────────────────────────────────
if (!$isCli) echo "<div class='section'><h2>Summary</h2>";
else out("═══ Summary ═══", 'head');

$total = $passCount + $failCount + $warnCount;
out("Total checks : $total", 'info');
out("Passed       : $passCount", 'ok');
if ($failCount > 0) out("Failed       : $failCount ← fix before demo", 'fail');
if ($warnCount > 0) out("Warnings     : $warnCount", 'warn');

if ($failCount === 0) out("🎉 All critical checks PASSED!", 'ok');
else out("⚠️  Critical issue(s) found.", 'warn');

if ($conn) oci_close($conn);
if (!$isCli) echo "</div></body></html>";
