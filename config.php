<?php
/**
 * DBS401 - Group 07
 * config.php - Database connection configuration
 *
 * IMPORTANT: This file intentionally stores credentials in plaintext
 * for lab demonstration purposes only.
 * In production: use environment variables or a secrets manager.
 */

define('DB_HOST',    'localhost');
define('DB_PORT',    '1521');
define('DB_SERVICE', 'XE');          // Change to 'FREE' for Oracle 23c Free
define('DB_USER',    'dbs401_user');
define('DB_PASS',    'dbs401_pass');
define('DB_CHARSET', 'AL32UTF8');

// TNS connection string
define('DB_TNS', sprintf(
    '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=%s)(PORT=%s))(CONNECT_DATA=(SERVICE_NAME=%s)))',
    DB_HOST, DB_PORT, DB_SERVICE
));

// App settings
define('APP_NAME',    'FPT Student Portal');
define('APP_BASE',    '/dbs401-oracle-app');
define('APP_VERSION', '2.4.1');
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
                 VALUES (:uid, :act, :ip, :ua, :meta)";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':uid',  $userId);
        oci_bind_by_name($stmt, ':act',  $action);
        oci_bind_by_name($stmt, ':ip',   $ip);
        oci_bind_by_name($stmt, ':ua',   $ua);
        oci_bind_by_name($stmt, ':meta', $metaNote);
        oci_execute($stmt);
    } catch (Exception $e) {
        error_log('[DBS401] logAction failed: ' . $e->getMessage());
    }
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
