<?php
/**
 * DBS401 - Group 02
 * audit.php  –  IDOR on audit log (supports Vulnerability 2)
 *
 * This page is also vulnerable to IDOR:
 * - Any logged-in user can view any audit log entry by changing log_id.
 * - For admin: intended to see all logs.
 * - For student: should only see own logs (but no ownership check!).
 *
 * Flag 2 Part B is embedded in the metadata_note of a specific log entry.
 * The log_id is discoverable because it is referenced in transcript.php (admin_ref_id).
 */

require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php'); exit;
}

$role   = $_SESSION['role'];
$conn   = getDbConnection();
$logId  = isset($_GET['log_id']) ? (int)$_GET['log_id'] : 0;
$logEntry  = null;
$allLogs   = [];
$errMsg    = '';

if ($logId > 0) {
    // =========================================================
    // VULNERABLE: No ownership check for log entries
    // Students should only see their own logs
    // =========================================================
    $sql  = "SELECT * FROM AUDIT_LOGS WHERE log_id = :lid";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':lid', $logId);
    oci_execute($stmt);
    $logEntry = oci_fetch_assoc($stmt);
    if (!$logEntry) {
        $errMsg = 'Log entry not found.';
    }
} elseif ($role === 'admin') {
    // Admin can list recent logs (parameterized, not vulnerable)
    $sql  = "SELECT * FROM AUDIT_LOGS ORDER BY created_at DESC FETCH FIRST 20 ROWS ONLY";
    $stmt = oci_parse($conn, $sql);
    oci_execute($stmt);
    while ($r = oci_fetch_assoc($stmt)) $allLogs[] = $r;
} else {
    // Non-admin, no log_id → show own logs only
    $uid  = $_SESSION['user_id'];
    $sql  = "SELECT * FROM AUDIT_LOGS WHERE user_id = :uid ORDER BY created_at DESC FETCH FIRST 10 ROWS ONLY";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':uid', $uid);
    oci_execute($stmt);
    while ($r = oci_fetch_assoc($stmt)) $allLogs[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Logs – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>📋 Audit Logs</h2>

    <?php if ($logId > 0): ?>
    <form method="GET" action="">
        <div class="search-bar">
            <input type="number" name="log_id" value="<?= $logId ?>"
                   placeholder="Log ID" class="search-input" style="max-width:200px;">
            <button type="submit" class="btn btn-primary">View Log</button>
        </div>
    </form>
    <?php endif; ?>

    <?php if ($errMsg): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($errMsg) ?></div>
    <?php endif; ?>

    <?php if ($logEntry): ?>
    <div class="info-card">
        <h3>Log Entry #<?= (int)$logEntry['LOG_ID'] ?></h3>
        <table class="info-table">
            <tr><th>Log ID</th>       <td><?= (int)$logEntry['LOG_ID'] ?></td></tr>
            <tr><th>User ID</th>      <td><?= htmlspecialchars($logEntry['USER_ID'] ?? 'SYSTEM') ?></td></tr>
            <tr><th>Action</th>       <td><code><?= htmlspecialchars($logEntry['ACTION']) ?></code></td></tr>
            <tr><th>IP Address</th>   <td><?= htmlspecialchars($logEntry['IP_ADDRESS']) ?></td></tr>
            <tr><th>User Agent</th>   <td><small><?= htmlspecialchars(substr($logEntry['USER_AGENT'],0,80)) ?></small></td></tr>
            <tr><th>Timestamp</th>    <td><?= htmlspecialchars($logEntry['CREATED_AT']) ?></td></tr>
            <tr>
                <th>Metadata</th>
                <td class="system-note">
                    <code><?= htmlspecialchars($logEntry['METADATA_NOTE'] ?? 'N/A') ?></code>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($allLogs): ?>
    <div class="info-card">
        <h3><?= $role === 'admin' ? 'Recent System Logs' : 'My Activity Logs' ?></h3>
        <table class="data-table">
            <thead><tr>
                <th>ID</th><th>User</th><th>Action</th><th>IP</th><th>Time</th><th>Detail</th>
            </tr></thead>
            <tbody>
            <?php foreach ($allLogs as $log): ?>
            <tr>
                <td><?= (int)$log['LOG_ID'] ?></td>
                <td><?= htmlspecialchars($log['USER_ID'] ?? 'SYS') ?></td>
                <td><code><?= htmlspecialchars($log['ACTION']) ?></code></td>
                <td><?= htmlspecialchars($log['IP_ADDRESS']) ?></td>
                <td><small><?= htmlspecialchars($log['CREATED_AT']) ?></small></td>
                <td><a href="?log_id=<?= (int)$log['LOG_ID'] ?>">View</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
