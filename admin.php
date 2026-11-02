<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location:'.APP_BASE.'/login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { http_response_code(403); echo '<h2>403 Forbidden</h2>'; exit; }

$conn    = getDbConnection();
$secrets = [];
$configs = [];
$msg     = '';
$msgType = 'info';

define('FLAG3_LOCAL_HEX_SUFFIX', getenv('FLAG3_SUFFIX_HEX') ?: '316e5f50303135306e316e675f303931327d');

function decodeHexFlagFragment(string $manifestHexPart): string {
    $manifestHexPart = trim($manifestHexPart);

    if ($manifestHexPart === '') {
        return "No 'flag_part' field found in manifest.";
    }

    $combinedHex = $manifestHexPart . FLAG3_LOCAL_HEX_SUFFIX;

    if (strlen($combinedHex) % 2 !== 0 || !ctype_xdigit($combinedHex)) {
        return 'Invalid flag fragment format. Expected hex.';
    }

    $decoded = hex2bin($combinedHex);

    if ($decoded === false) {
        return 'Could not decode flag fragment.';
    }

    return $decoded;
}

// --- Handle Supply Chain Update Check (VULNERABILITY 3) ---
if (isset($_GET['check_updates'])) {
    $q = oci_parse($conn, "SELECT config_value FROM CONFIG_STORE WHERE config_key = 'update_url'");
    oci_execute($q);
    $conf = oci_fetch_assoc($q);
    $url = $conf['CONFIG_VALUE'] ?? DEFAULT_UPDATE_URL;
    
    // VULNERABLE: Trusting URL from DB without validation or digital signature
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $jsonData = @file_get_contents($url, false, $ctx);
    if ($jsonData) {
        $manifest = json_decode($jsonData, true);
        if (isset($manifest['version'])) {
            // Version comparison: only trigger if the partner version is strictly greater than APP_VERSION
            if (version_compare($manifest['version'], APP_VERSION, '>')) {
                $msg = tf('ad.msg_upd_ok_fmt', htmlspecialchars($manifest['version']));
                $msgType = "success";
                $manifestFlagPart = $manifest['flag_part'] ?? '';
                $flag3 = decodeHexFlagFragment($manifestFlagPart);
            } else {
                $msg = tf('ad.msg_uptodate_fmt', APP_VERSION, $manifest['version']);
                $msgType = "info";
            }
        } else {
            $msg = t('ad.msg_bad_manifest'); $msgType = "danger";
        }
    } else {
        $msg = tf('ad.msg_no_connect_fmt', htmlspecialchars($url)); $msgType = "danger";
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
                $msg = tf('ad.msg_user_ok_fmt', $uname); $msgType = 'success';
                logAction($_SESSION['user_id'], 'ADMIN_CREATE_USER', "Created user: $uname");
            } else {
                $e = oci_error($stmt); $msg = tf('ad.msg_error_fmt', $e['message']); $msgType = 'danger';
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
            $msg = tf('ad.msg_status_fmt', $uid, $status);
            logAction($_SESSION['user_id'], 'ADMIN_UPDATE_USER', "Status change for UID $uid to $status");
        }
    } elseif ($action === 'delete_user') {
        $uid  = (int)($_POST['user_id'] ?? 0);
        if ($uid !== (int)$_SESSION['user_id']) { // Prevent self-deletion
            // Xoa ban ghi con trong ENROLLMENTS va STUDENTS de tranh loi khoa ngoai ORA-02292
            @oci_execute(oci_parse($conn, "DELETE FROM ENROLLMENTS WHERE student_id IN (SELECT student_id FROM STUDENTS WHERE user_id = $uid)"));
            @oci_execute(oci_parse($conn, "DELETE FROM STUDENTS WHERE user_id = $uid"));

            $sql  = "DELETE FROM USERS WHERE user_id = " . $uid;
            $stmt = oci_parse($conn, $sql);
            //oci_bind_by_name($stmt, ':uid', $uid);
            if (@oci_execute($stmt)) {
                $msg = tf('ad.msg_deleted_fmt', $uid); $msgType = 'warning';
                logAction($_SESSION['user_id'], 'ADMIN_DELETE_USER', "Deleted UID $uid");
            } else {
                $e = oci_error($stmt); $msg = tf('ad.msg_del_fail_fmt', $e['message']); $msgType = 'danger';
            }
        }
    }
}

$sql  = "SELECT secret_id, secret_key, note, is_active, created_at FROM ADMIN_SECRETS ORDER BY secret_id";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
while ($r = oci_fetch_assoc($stmt)) $secrets[] = $r;

// HACKER HINT: Displays only public configurations.
// To discover sensitive 'update_url' (is_public=0), candidates must use SQL Injection at search.php
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
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('ad.title') ?> – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>⚙️ <?= t('ad.title') ?></h2>
    <p class="alert alert-info"><?= t('ad.restricted') ?></p>

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
        <h3>👥 <?= t('ad.user_mgmt') ?></h3>
        <div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
            <h4><?= t('ad.add_user') ?></h4>
            <form method="POST" action="" style="display: flex; gap: 10px; align-items: flex-end;">
                <input type="hidden" name="action" value="create_user">
                <div>
                    <label style="display:block; font-size:12px;"><?= t('p.username') ?></label>
                    <input type="text" name="username" required style="padding:5px;">
                </div>
                <div>
                    <label style="display:block; font-size:12px;"><?= t('lg.password') ?></label>
                    <input type="password" name="password" required style="padding:5px;">
                </div>
                <div>
                    <label style="display:block; font-size:12px;"><?= t('ad.role') ?></label>
                    <select name="role" style="padding:5px;">
                        <option value="student"><?= t('ad.role_student') ?></option>
                        <option value="teacher"><?= t('ad.role_teacher') ?></option>
                        <option value="admin"><?= t('ad.role_admin') ?></option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 6px 12px;"><?= t('ad.btn_add') ?></button>
            </form>
        </div>

        <table class="data-table">
            <thead><tr><th><?= t('ad.h_id') ?></th><th><?= t('p.username') ?></th><th><?= t('ad.role') ?></th><th><?= t('p.status') ?></th><th><?= t('ad.h_created') ?></th><th><?= t('ad.h_actions') ?></th></tr></thead>
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
                        <button type="submit" class="btn btn-secondary" style="padding:2px 5px; font-size:11px;"><?= t('ad.toggle') ?></button>
                    </form>
                    <?php if ($u['USER_ID'] != $_SESSION['user_id']): ?>
                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('<?= t('ad.confirm') ?>');">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= $u['USER_ID'] ?>">
                        <button type="submit" class="btn btn-logout" style="padding:2px 5px; font-size:11px; background:#d9534f;"><?= t('ad.delete') ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="info-card">
        <h3>🔐 <?= t('ad.secrets') ?></h3>
        <table class="data-table">
            <thead><tr><th><?= t('ad.h_id') ?></th><th><?= t('ad.h_keyname') ?></th><th><?= t('ad.h_note') ?></th><th><?= t('ad.h_active') ?></th><th><?= t('ad.h_created') ?></th></tr></thead>
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
        <small class="text-muted"><?= t('ad.secrets_note') ?></small>
    </div>

    <div class="info-card">
        <h3>⚙️ <?= t('ad.config_store') ?></h3>
        <table class="data-table">
            <thead><tr><th><?= t('ad.h_key') ?></th><th><?= t('ad.h_value') ?></th><th><?= t('ad.h_public') ?></th><th><?= t('ad.h_updated') ?></th></tr></thead>
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
        <h3>🔗 <?= t('ad.quick_links') ?></h3>
        <a href="<?= APP_BASE ?>/audit.php" class="btn btn-secondary"><?= t('ad.view_audit') ?></a>
    </div>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
