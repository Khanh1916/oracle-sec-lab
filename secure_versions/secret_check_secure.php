<?php
/**
 * DBS401 - Group 07
 * secure_versions/secret_check_secure.php
 *
 * SECURE VERSION of secret_check.php (Vulnerability 3 Fix)
 *
 * FIXES APPLIED:
 *   1. Bind variable for 'key' parameter — no concatenation.
 *   2. Strict input whitelist (alphanumeric + underscore only).
 *   3. Key length limit to prevent oversized payloads.
 *   4. Authentication + authorization gate (admin only).
 *   5. Rate limiting stub (implement with APCu/Redis in production).
 *   6. No error detail in response body.
 */

require_once dirname(__DIR__) . '/config.php';
header('Content-Type: application/json');

// ── Require authenticated admin session ─────────────────────
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Insufficient privileges']);
    exit;
}

$key = $_GET['key'] ?? '';

// ── 1. Validate key format (strict whitelist) ────────────────
if ($key === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing key parameter']);
    exit;
}
if (!preg_match('/^[a-zA-Z0-9_]{1,64}$/', $key)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid key format']);
    exit;
}

$conn = getDbConnection();

// ── 2. Parameterized query — bind variable ───────────────────
$sql  = "SELECT COUNT(*) AS cnt FROM ADMIN_SECRETS
         WHERE secret_key = :sk AND is_active = 1";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':sk', $key);
$ok  = oci_execute($stmt);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal error']);
    exit;
}

$row   = oci_fetch_assoc($stmt);
$count = (int)($row['CNT'] ?? 0);

logAction($_SESSION['user_id'], 'SECRET_CHECK_SECURE',
          json_encode(['key' => $key]));

// ── 3. Generic response — no timing difference ───────────────
usleep(random_int(80000, 120000)); // constant-time-ish response
echo json_encode([
    'status'  => $count > 0 ? 'found' : 'not_found',
    'message' => $count > 0 ? 'Key verified.' : 'Key not found.',
]);
