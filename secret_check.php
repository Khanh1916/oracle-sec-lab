<?php
/**
 * DBS401 - Group 02
 * secret_check.php  –  VULNERABILITY 3: Oracle Boolean-Based Blind SQL Injection
 *
 * Severity (DBS401 report): Hard
 * Flag difficulty          : Very Hard
 *
 * PURPOSE (legitimate lab feature):
 *   Checks whether a given secret key exists and is active.
 *   Used internally by the system to verify configuration tokens.
 *
 * WHY VULNERABLE:
 *   - Input 'key' is concatenated into SQL without parameterization.
 *   - Blacklist blocks '--' and '/*' but NOT:
 *       AND, OR, SUBSTR, ASCII, LENGTH, SELECT, FROM, WHERE, ROWNUM
 *   - The endpoint only returns two states: "found" or "not_found"
 *   - No data is returned directly → forces boolean-based blind extraction.
 *
 * WHY FLAG IS VERY HARD:
 *   - No UNION output possible (COUNT only).
 *   - Must use boolean condition: ASCII(SUBSTR(...))=N
 *   - Must determine correct secret_key ('oracle_flag_3_primary' not obvious).
 *   - Fake keys exist (oracle_flag_3_backup) with decoy values.
 *   - After extraction: value is partial → must combine with hex-encoded suffix from CONFIG_STORE.
 *   - CONFIG_STORE suffix discoverable via Vuln 1 SQLi or SYSTEM_HINTS.
 *   - Must decode hex suffix and concatenate to assemble final flag.
 *
 * ACCESSIBLE AT:
 *   GET /dbs401-oracle-app/secret_check.php?key=SOME_KEY
 *   Returns JSON: {"status":"found","message":"..."} or {"status":"not_found","message":"..."}
 */

require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit;
}

header('Content-Type: application/json');

$key = $_GET['key'] ?? '';

if ($key === '') {
    echo json_encode(['status' => 'error', 'message' => 'Missing key parameter']);
    exit;
}

// =========================================================
// INTENTIONALLY WEAK BLACKLIST
// Blocks comment syntax only — NOT injection logic keywords
// =========================================================
$weakBlacklist = ['--', '/*', '*/', 'xp_', 'exec(', 'execute('];
$keyLower      = strtolower($key);
foreach ($weakBlacklist as $bad) {
    if (strpos($keyLower, $bad) !== false) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid characters in key']);
        exit;
    }
}

if (strlen($key) > 512) {
    echo json_encode(['status' => 'error', 'message' => 'Key too long']);
    exit;
}

$conn = getDbConnection();

// =========================================================
// VULNERABLE QUERY — input concatenated directly
// Attacker can inject: oracle_flag_3_primary' AND ASCII(SUBSTR(encrypted_value,1,1))=68 AND '1'='1
// =========================================================
$sql = "SELECT COUNT(*) AS cnt FROM ADMIN_SECRETS
        WHERE secret_key = '$key' AND is_active = 1";

$stmt       = oci_parse($conn, $sql);
$execResult = @oci_execute($stmt);

if (!$execResult) {
    // Suppress all SQL error details
    echo json_encode(['status' => 'error', 'message' => 'Query execution failed']);
    exit;
}

$row   = oci_fetch_assoc($stmt);
$count = (int)($row['CNT'] ?? 0);

logAction(
    $_SESSION['user_id'],
    'SECRET_CHECK',
    json_encode(['key_prefix' => substr($key, 0, 30)])
);

if ($count > 0) {
    echo json_encode([
        'status'  => 'found',
        'message' => 'Secret key verified. Record exists.',
    ]);
} else {
    echo json_encode([
        'status'  => 'not_found',
        'message' => 'Invalid or inactive secret key.',
    ]);
}
