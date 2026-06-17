<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location:'.APP_BASE.'/login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { http_response_code(403); echo '<h2>403 Forbidden</h2>'; exit; }

$conn    = getDbConnection();
$secrets = [];
$configs = [];
$msg     = '';
$msgType = 'info';

// --- Handle Supply Chain Update Check (VULNERABILITY 3) ---
if (isset($_GET['check_updates'])) {
    $q = oci_parse($conn, "SELECT config_value FROM CONFIG_STORE WHERE config_key = 'update_url'");
    oci_execute($q);
    $conf = oci_fetch_assoc($q);
    $url = $conf['CONFIG_VALUE'] ?? DEFAULT_UPDATE_URL;
    
    // VULNERABLE: Trusting URL from DB without validation or digital signature
    $jsonData = @file_get_contents($url);
    if ($jsonData) {
        $manifest = json_decode($jsonData, true);
        if (isset($manifest['version'])) {
            // So sánh phiên bản: Nếu từ Partner > App hiện tại thì mới trigger thành công
            if (version_compare($manifest['version'], APP_VERSION, '>')) {
                $msg = "🎉 Update Successful! System upgraded to version " . htmlspecialchars($manifest['version']);
                $msgType = "success";
                $flag3 = $manifest['flag_part'] ?? 'No flag found in manifest';
            } else {
                $msg = "System is up to date (Current: " . APP_VERSION . ", Partner: " . $manifest['version'] . ")";
                $msgType = "info";
            }
        } else {
            $msg = "Invalid manifest format from provider."; $msgType = "danger";
        }
    } else {
        $msg = "Could not connect to update server at: " . htmlspecialchars($url); $msgType = "danger";
    }
}

// --- Handle CRUD Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create_user') {
        $uname = trim($_POST['username'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $role  = $_POST['role'] ?? 'student';
        
        if ($uname && $pass) {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $sql  = "INSERT INTO USERS (username, password_hash, role, status) VALUES (:un, :pw, :rl, 'active')";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':un', $uname);
            oci_bind_by_name($stmt, ':pw', $hash);
            oci_bind_by_name($stmt, ':rl', $role);
            if (@oci_execute($stmt)) {
                $msg = "User '$uname' created successfully."; $msgType = 'success';
                logAction($_SESSION['user_id'], 'ADMIN_CREATE_USER', "Created user: $uname");
            } else {
                $e = oci_error($stmt); $msg = "Error: " . $e['message']; $msgType = 'danger';
            }
        }
    } elseif ($action === 'update_status') {
        $uid    = (int)($_POST['user_id'] ?? 0);
        $status = $_POST['status'] === 'active' ? 'inactive' : 'active';
        $sql    = "UPDATE USERS SET status = :st WHERE user_id = " . $uid;
        $stmt   = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':st', $status);
        //oci_bind_by_name($stmt, ':uid', $uid);
        if (oci_execute($stmt)) {
            $msg = "User ID $uid status updated to $status.";
            logAction($_SESSION['user_id'], 'ADMIN_UPDATE_USER', "Status change for UID $uid to $status");
        }
    } elseif ($action === 'delete_user') {
        $uid  = (int)($_POST['user_id'] ?? 0);
        if ($uid !== (int)$_SESSION['user_id']) { // Prevent self-deletion
            // Note: In a real Oracle DB, you might need to handle child records in STUDENTS/ENROLLMENTS first
            $sql  = "DELETE FROM USERS WHERE user_id = " . $uid;
            $stmt = oci_parse($conn, $sql);
            //oci_bind_by_name($stmt, ':uid', $uid);
            if (@oci_execute($stmt)) {
                $msg = "User ID $uid deleted."; $msgType = 'warning';
                logAction($_SESSION['user_id'], 'ADMIN_DELETE_USER', "Deleted UID $uid");
            } else {
                $e = oci_error($stmt); $msg = "Delete failed: " . $e['message'] . " (Check foreign key constraints)"; $msgType = 'danger';
            }
        }
    }
}

$sql  = "SELECT secret_id, secret_key, note, is_active, created_at FROM ADMIN_SECRETS ORDER BY secret_id";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
while ($r = oci_fetch_assoc($stmt)) $secrets[] = $r;

// HACKER HINT: Chỉ hiển thị các cấu hình công khai.
// Muốn tìm 'update_url' nhạy cảm (is_public=0), hacker phải sử dụng SQL Injection ở search.php
$sql  = "SELECT config_key, config_value, is_public, updated_at FROM CONFIG_STORE WHERE is_public = 1 ORDER BY config_key";
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

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if (isset($flag3)): ?>
        <div class="alert alert-success" style="border: 2px dashed #2d6a4f;">
            <strong>🚀 PARTNER_MANIFEST_FLAG:</strong> 
            <code><?= htmlspecialchars($flag3) ?></code>
        </div>
    <?php endif; ?>

    <div class="info-card">
        <h3>👥 User Management</h3>
        <div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
            <h4>Add New User</h4>
            <form method="POST" action="" style="display: flex; gap: 10px; align-items: flex-end;">
                <input type="hidden" name="action" value="create_user">
                <div>
                    <label style="display:block; font-size:12px;">Username</label>
                    <input type="text" name="username" required style="padding:5px;">
                </div>
                <div>
                    <label style="display:block; font-size:12px;">Password</label>
                    <input type="password" name="password" required style="padding:5px;">
                </div>
                <div>
                    <label style="display:block; font-size:12px;">Role</label>
                    <select name="role" style="padding:5px;">
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 6px 12px;">Add User</button>
            </form>
        </div>

        <table class="data-table">
            <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int)$u['USER_ID'] ?></td>
                <td><?= htmlspecialchars($u['USERNAME']) ?></td>
                <td><span class="badge badge-<?= $u['ROLE'] ?>"><?= htmlspecialchars($u['ROLE']) ?></span></td>
                <td><?= htmlspecialchars($u['STATUS']) ?></td>
                <td><small><?= htmlspecialchars($u['CREATED_AT']) ?></small></td>
                <td>
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="user_id" value="<?= $u['USER_ID'] ?>">
                        <input type="hidden" name="status" value="<?= $u['STATUS'] ?>">
                        <button type="submit" class="btn btn-secondary" style="padding:2px 5px; font-size:11px;">Toggle Status</button>
                    </form>
                    <?php if ($u['USER_ID'] != $_SESSION['user_id']): ?>
                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= $u['USER_ID'] ?>">
                        <button type="submit" class="btn btn-logout" style="padding:2px 5px; font-size:11px; background:#d9534f;">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
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
        <a href="<?= APP_BASE ?>/secret_check.php?key=sys_master_key" class="btn btn-secondary" title="This endpoint was part of an old vulnerability scenario.">
            Test Secret API
        </a>
        <a href="<?= APP_BASE ?>/audit.php" class="btn btn-secondary">View Audit Logs</a>
    </div>
</div>
</body>
</html>
