<?php


// DB connection reads env vars when present (Docker), else falls back to
// the original VM defaults so setup.sh / manual installs are unaffected.
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT')    ?: '1539');
define('DB_SERVICE', getenv('DB_SERVICE') ?: 'XEPDB1');  // Change to 'FREE' for Oracle 23c Free
define('DB_USER',    getenv('DB_USER')    ?: 'dbs401_user');
define('DB_PASS',    getenv('DB_PASS')    ?: 'dbs401_pass');
define('DB_CHARSET', 'AL32UTF8');

// TNS connection string
define('DB_TNS', sprintf(
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=%s)(PORT=%s))(CONNECT_DATA=(SERVICE_NAME=%s)))',
    DB_HOST, DB_PORT, DB_SERVICE
));

// App settings
define('APP_NAME',    'FPT Student Portal');
define('APP_BASE',    '/dbs401-oracle-app');
define('APP_VERSION', '3.1.0'); // Consistent with seed.sql and setup.sh for version_compare
define('DEFAULT_UPDATE_URL', 'http://127.0.0.1:8081/manifest.json');
define('APP_DEBUG',   false);  // Set true only for local dev

// Session settings
define('SESSION_NAME',     'DBS401_SESSION');
define('SESSION_LIFETIME', 3600);

/**
 * Get Oracle DB connection (singleton pattern)
 */
function getDbConnection(): mixed {
    static $conn = null;
    if ($conn === null) {
        $conn = oci_connect(DB_USER, DB_PASS, DB_TNS, DB_CHARSET);
        if (!$conn) {
            $e = oci_error();
            error_log('[DBS401] DB connection failed: ' . $e['message']);
            die('<div style="color:red;font-family:monospace;padding:20px;">
                 <b>Database connection failed.</b><br>
                 Please ensure Oracle Database is running and OCI8 is loaded.<br>
                 Error: ' . htmlspecialchars($e['message']) . '
                 </div>');
        }
    }
    return $conn;
}

/**
 * Log user action to AUDIT_LOGS
 */
function logAction(int|null $userId, string $action, string $metaNote = ''): void {
    try {
        $conn = getDbConnection();
        $ip   = $_SERVER['REMOTE_ADDR']    ?? 'unknown';
        $ua   = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $sql  = "INSERT INTO AUDIT_LOGS (user_id, action, ip_address, user_agent, metadata_note)
                 VALUES (" . ($userId ?? 'NULL') . ", :act, :ip, :ua, :meta)";
        $stmt = oci_parse($conn, $sql);
        //oci_bind_by_name($stmt, ':b_uid',  $userId);
        oci_bind_by_name($stmt, ':act',  $action);
        oci_bind_by_name($stmt, ':ip',   $ip);
        oci_bind_by_name($stmt, ':ua',   $ua);
        oci_bind_by_name($stmt, ':meta', $metaNote);
        oci_execute($stmt);
    } catch (Exception $e) {
        error_log('[DBS401] logAction failed: ' . $e->getMessage());
    }
}

/**
 * Notification Center helpers (cosmetic feature; every query is parameterized).
 */
function notifCategoryMeta(string $cat): array {
    switch (strtolower($cat)) {
        case 'academic': return [t('cat.academic'), 'tag-academic'];
        case 'exam':     return [t('cat.exam'),     'tag-exam'];
        case 'finance':  return [t('cat.finance'),  'tag-finance'];
        case 'system':   return [t('cat.system'),   'tag-system'];
        default:         return [t('cat.notice'),   'tag-notice'];
    }
}

function getUserNotifications($conn, int $userId, string $role, int $limit = 0): array {
    $sql = "SELECT n.notification_id, n.category, n.title, n.body,
                   TO_CHAR(n.created_at, 'YYYY-MM-DD HH24:MI') AS created_disp,
                   CASE WHEN r.user_id IS NULL THEN 0 ELSE 1 END AS is_read
            FROM NOTIFICATIONS n
            LEFT JOIN NOTIFICATION_READS r
                   ON r.notification_id = n.notification_id AND r.user_id = :b_uid
            WHERE n.audience IN ('all', :b_role)
            ORDER BY n.created_at DESC, n.notification_id DESC";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':b_uid',  $userId);
    oci_bind_by_name($stmt, ':b_role', $role);
    oci_execute($stmt);
    $rows = [];
    while ($r = oci_fetch_assoc($stmt)) {
        $rows[] = $r;
        if ($limit > 0 && count($rows) >= $limit) break;
    }
    return $rows;
}

function countUnreadNotifications($conn, int $userId, string $role): int {
    $sql = "SELECT COUNT(*) AS c FROM NOTIFICATIONS n
            WHERE n.audience IN ('all', :b_role)
              AND NOT EXISTS (
                  SELECT 1 FROM NOTIFICATION_READS r
                  WHERE r.notification_id = n.notification_id AND r.user_id = :b_uid)";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':b_uid',  $userId);
    oci_bind_by_name($stmt, ':b_role', $role);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);
    return (int)($row['C'] ?? 0);
}

function markNotificationRead($conn, int $userId, int $notifId): void {
    $sql = "MERGE INTO NOTIFICATION_READS t
            USING (SELECT :b_uid AS user_id, :nid AS notification_id FROM DUAL) s
            ON (t.user_id = s.user_id AND t.notification_id = s.notification_id)
            WHEN NOT MATCHED THEN
                INSERT (user_id, notification_id) VALUES (s.user_id, s.notification_id)";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':b_uid', $userId);
    oci_bind_by_name($stmt, ':nid', $notifId);
    oci_execute($stmt);
}

function markAllNotificationsRead($conn, int $userId, string $role): void {
    $sql = "INSERT INTO NOTIFICATION_READS (user_id, notification_id)
            SELECT :b_uid, n.notification_id FROM NOTIFICATIONS n
            WHERE n.audience IN ('all', :b_role)
              AND NOT EXISTS (
                  SELECT 1 FROM NOTIFICATION_READS r
                  WHERE r.notification_id = n.notification_id AND r.user_id = :b_uid)";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':b_uid',  $userId);
    oci_bind_by_name($stmt, ':b_role', $role);
    oci_execute($stmt);
}

/**
 * Portal UI preferences (cosmetic shell).
 * Dark mode and language are user choices, persisted in client-side cookies
 * (written by JS in the navbar) and read here so the server renders the chosen
 * language and theme without a flash. Campus is NOT a choice: each student has
 * a single fixed home campus stored on their STUDENTS record (see the navbar).
 */
$GLOBALS['PORTAL_LANGS'] = ['en' => 'English', 'vi' => 'Tiếng Việt'];
$GLOBALS['PORTAL_CAMPUSES'] = [
    'HN'  => 'Hà Nội',
    'DN'  => 'Đà Nẵng',
    'CT'  => 'Cần Thơ',
    'HCM' => 'TP. Hồ Chí Minh',
    'QN'  => 'Quy Nhơn',
];
$cl = $_COOKIE['portal_lang'] ?? 'en';
$GLOBALS['PORTAL_LANG']   = isset($GLOBALS['PORTAL_LANGS'][$cl]) ? $cl : 'en';
$GLOBALS['PORTAL_THEME']  = (($_COOKIE['portal_theme'] ?? '') === 'dark') ? 'dark' : 'light';

require_once __DIR__ . '/lang.php';

/** Translate a shell UI key to the selected language (falls back to English, then the key). */
function t(string $key): string {
    $lang = $GLOBALS['PORTAL_LANG'] ?? 'en';
    $dict = $GLOBALS['I18N'][$lang] ?? [];
    if (isset($dict[$key])) return $dict[$key];
    return $GLOBALS['I18N']['en'][$key] ?? $key;
}

/** Translate then sprintf (for templated strings ending in _fmt). */
function tf(string $key, ...$args): string {
    return vsprintf(t($key), $args);
}

/** Localized weekday label for a 'YYYY-MM-DD' date (falls back to the raw input). */
function dowLabel(string $ymd): string {
    $ts = strtotime($ymd);
    if ($ts === false) return $ymd;
    return t('dow.' . date('N', $ts)); // date('N'): 1=Mon .. 7=Sun
}

// Start session
session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => APP_BASE,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();
