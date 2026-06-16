<?php
/**
 * DBS401 - Group 02
 * secret_check.php  –  Legacy Secret Key API
 *
 * TRẠNG THÁI: Endpoint legacy, KHÔNG phải một trong 3 lỗ hổng chính của lab.
 *
 * Các lỗ hổng chính:
 *   VULN 1: search.php      (SQL Injection)
 *   VULN 2: store.php       (Business Logic – Negative Quantity)
 *   VULN 3: admin.php       (Supply Chain Poisoning)
 *
 * File này vẫn có lỗ hổng SQL Injection (input 'key' không được parameterize),
 * nhưng chỉ trả về found/not_found → không lộ dữ liệu trực tiếp.
 * Không có flag nào được lưu trong ADMIN_SECRETS liên quan đến kịch bản hiện tại.
 *
 * ACCESSIBLE AT:
 *   GET /dbs401-oracle-app/secret_check.php?key=sys_master_key
 *   Returns JSON: {"status":"found",...} or {"status":"not_found",...}
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

// Weak blacklist – blocks comment syntax only
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

// VULNERABLE: input concatenated directly (boolean-blind injectable)
// Nhưng không có flag nào có thể extract qua đây trong kịch bản hiện tại
$sql  = "SELECT COUNT(*) AS cnt FROM ADMIN_SECRETS
         WHERE secret_key = '$key' AND is_active = 1";
$stmt = oci_parse($conn, $sql);
$execResult = @oci_execute($stmt);

if (!$execResult) {
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