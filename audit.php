<?php


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
   
    $sql  = "SELECT al.*, u.user_id as log_user_id FROM AUDIT_LOGS al LEFT JOIN USERS u ON al.user_id = u.user_id WHERE al.log_id = :lid";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':lid', $logId);
    oci_execute($stmt);
    $logEntry = oci_fetch_assoc($stmt);
    if (!$logEntry) {
        $errMsg = t('au.err_notfound');
    } elseif ($logEntry['LOG_USER_ID'] !== $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
        // SECURE: Ownership check - only admin or owner can view
        http_response_code(403);
        $errMsg = t('au.err_denied');
        $logEntry = null; // Clear data to prevent display
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
    $sql  = "SELECT * FROM AUDIT_LOGS WHERE user_id = " . $uid . " ORDER BY created_at DESC FETCH FIRST 10 ROWS ONLY";
    $stmt = oci_parse($conn, $sql);
    //oci_bind_by_name($stmt, ':uid', $uid);
    oci_execute($stmt);
    while ($r = oci_fetch_assoc($stmt)) $allLogs[] = $r;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('au.title') ?> – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>📋 <?= t('au.title') ?></h2>

    <?php if ($logId > 0): ?>
    <form method="GET" action="">
        <div class="search-bar">
            <input type="number" name="log_id" value="<?= $logId ?>"
                   placeholder="Log ID" class="search-input" style="max-width:200px;">
            <button type="submit" class="btn btn-primary"><?= t('au.view') ?></button>
        </div>
    </form>
    <?php endif; ?>

    <?php if ($errMsg): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($errMsg) ?></div>
    <?php endif; ?>

    <?php if ($logEntry): ?>
    <div class="info-card">
        <h3><?= tf('au.entry_fmt', (int)$logEntry['LOG_ID']) ?></h3>
        <table class="info-table">
            <tr><th><?= t('au.f_logid') ?></th>       <td><?= (int)$logEntry['LOG_ID'] ?></td></tr>
            <tr><th><?= t('au.f_userid') ?></th>      <td><?= htmlspecialchars($logEntry['USER_ID'] ?? 'SYSTEM') ?></td></tr>
            <tr><th><?= t('au.f_action') ?></th>       <td><code><?= htmlspecialchars($logEntry['ACTION']) ?></code></td></tr>
            <tr><th><?= t('au.f_ip') ?></th>   <td><?= htmlspecialchars($logEntry['IP_ADDRESS']) ?></td></tr>
            <tr><th><?= t('au.f_ua') ?></th>   <td><small><?= htmlspecialchars(substr($logEntry['USER_AGENT'],0,80)) ?></small></td></tr>
            <tr><th><?= t('au.f_time') ?></th>    <td><?= htmlspecialchars($logEntry['CREATED_AT']) ?></td></tr>
            <tr>
                <th><?= t('au.f_meta') ?></th>
                <td class="system-note">
                    <code><?= htmlspecialchars($logEntry['METADATA_NOTE'] ?? t('p.na')) ?></code>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($allLogs): ?>
    <div class="info-card">
        <h3><?= $role === 'admin' ? t('au.recent') : t('au.mine') ?></h3>
        <table class="data-table">
            <thead><tr>
                <th><?= t('au.h_id') ?></th><th><?= t('au.h_user') ?></th><th><?= t('au.f_action') ?></th><th><?= t('au.h_ip') ?></th><th><?= t('au.h_time') ?></th><th><?= t('au.h_detail') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($allLogs as $log): ?>
            <tr>
                <td><?= (int)$log['LOG_ID'] ?></td>
                <td><?= htmlspecialchars($log['USER_ID'] ?? 'SYS') ?></td>
                <td><code><?= htmlspecialchars($log['ACTION']) ?></code></td>
                <td><?= htmlspecialchars($log['IP_ADDRESS']) ?></td>
                <td><small><?= htmlspecialchars($log['CREATED_AT']) ?></small></td>
                <td><a href="?log_id=<?= (int)$log['LOG_ID'] ?>"><?= t('au.view_link') ?></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
