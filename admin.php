<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location:'.APP_BASE.'/login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { http_response_code(403); echo '<h2>403 Forbidden</h2>'; exit; }

$conn    = getDbConnection();
$secrets = [];
$configs = [];

$sql  = "SELECT secret_id, secret_key, note, is_active, created_at FROM ADMIN_SECRETS ORDER BY secret_id";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
while ($r = oci_fetch_assoc($stmt)) $secrets[] = $r;

$sql  = "SELECT config_key, config_value, is_public, updated_at FROM CONFIG_STORE ORDER BY config_key";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
while ($r = oci_fetch_assoc($stmt)) $configs[] = $r;

$sql  = "SELECT u.user_id, u.username, u.role, u.status, u.created_at FROM USERS u ORDER BY u.user_id";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$users = [];
while ($r = oci_fetch_assoc($stmt)) $users[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>⚙️ Admin Panel</h2>
    <p class="alert alert-info">This panel is restricted to admin users only.</p>

    <div class="info-card">
        <h3>👥 Users</h3>
        <table class="data-table">
            <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int)$u['USER_ID'] ?></td>
                <td><?= htmlspecialchars($u['USERNAME']) ?></td>
                <td><span class="badge badge-<?= $u['ROLE'] ?>"><?= htmlspecialchars($u['ROLE']) ?></span></td>
                <td><?= htmlspecialchars($u['STATUS']) ?></td>
                <td><small><?= htmlspecialchars($u['CREATED_AT']) ?></small></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="info-card">
        <h3>🔐 Admin Secrets (key names only – values masked)</h3>
        <table class="data-table">
            <thead><tr><th>ID</th><th>Key Name</th><th>Note</th><th>Active</th><th>Created</th></tr></thead>
            <tbody>
            <?php foreach ($secrets as $s): ?>
            <tr>
                <td><?= (int)$s['SECRET_ID'] ?></td>
                <td><code><?= htmlspecialchars($s['SECRET_KEY']) ?></code></td>
                <td><?= htmlspecialchars($s['NOTE']) ?></td>
                <td><?= $s['IS_ACTIVE'] ? '✅' : '❌' ?></td>
                <td><small><?= htmlspecialchars($s['CREATED_AT']) ?></small></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <small class="text-muted">Secret values are not displayed here. Use secret_check.php API to verify.</small>
    </div>

    <div class="info-card">
        <h3>⚙️ Configuration Store</h3>
        <table class="data-table">
            <thead><tr><th>Key</th><th>Value</th><th>Public</th><th>Updated</th></tr></thead>
            <tbody>
            <?php foreach ($configs as $c): ?>
            <tr>
                <td><code><?= htmlspecialchars($c['CONFIG_KEY']) ?></code></td>
                <td><code><?= htmlspecialchars(substr($c['CONFIG_VALUE'],0,40)) ?><?= strlen($c['CONFIG_VALUE'])>40 ? '...' : '' ?></code></td>
                <td><?= $c['IS_PUBLIC'] ? '🌐' : '🔒' ?></td>
                <td><small><?= htmlspecialchars($c['UPDATED_AT']) ?></small></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="info-card">
        <h3>🔗 Quick Links</h3>
        <a href="<?= APP_BASE ?>/secret_check.php?key=sys_master_key" class="btn btn-secondary">
            Test Secret API
        </a>
        <a href="<?= APP_BASE ?>/audit.php" class="btn btn-secondary">View Audit Logs</a>
    </div>
</div>
</body>
</html>
