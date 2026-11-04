<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location: ' . APP_BASE . '/login.php'); exit; }

$conn   = getDbConnection();
$userId = (int)$_SESSION['user_id'];
$role   = $_SESSION['role'];

// ---- Handle updates (Post/Redirect/Get) ------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $flash  = ['type' => 'danger', 'msg' => t('p.msg_unknown')];

    if ($action === 'update_contact' && $role === 'student') {
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash = ['type' => 'danger', 'msg' => t('p.msg_email')];
        } else {
            $sql  = "UPDATE STUDENTS SET email = :em, phone = :ph, address = :ad WHERE user_id = :p_uid";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':em',  $email);
            oci_bind_by_name($stmt, ':ph',  $phone);
            oci_bind_by_name($stmt, ':ad',  $address);
            oci_bind_by_name($stmt, ':p_uid', $userId);
            oci_execute($stmt);
            logAction($userId, 'PROFILE_UPDATE', 'contact details');
            $flash = ['type' => 'success', 'msg' => t('p.msg_contact_ok')];
        }
    } elseif ($action === 'change_password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new     = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        $sql  = "SELECT password_hash FROM USERS WHERE user_id = :p_uid";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':p_uid', $userId);
        oci_execute($stmt);
        $row  = oci_fetch_assoc($stmt);

        if (!$row || !password_verify($current, $row['PASSWORD_HASH'])) {
            $flash = ['type' => 'danger', 'msg' => t('p.msg_cur_wrong')];
        } elseif (strlen($new) < 8) {
            $flash = ['type' => 'danger', 'msg' => t('p.msg_min8')];
        } elseif ($new !== $confirm) {
            $flash = ['type' => 'danger', 'msg' => t('p.msg_mismatch')];
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
            $sql  = "UPDATE USERS SET password_hash = :h WHERE user_id = :p_uid";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':h',   $hash);
            oci_bind_by_name($stmt, ':p_uid', $userId);
            oci_execute($stmt);
            logAction($userId, 'PASSWORD_CHANGE', '');
            $flash = ['type' => 'success', 'msg' => t('p.msg_pw_ok')];
        }
    }

    $_SESSION['profile_flash'] = $flash;
    header('Location: ' . APP_BASE . '/profile.php');
    exit;
}

$flash = $_SESSION['profile_flash'] ?? null;
unset($_SESSION['profile_flash']);

// ---- Load profile ----------------------------------------------------------
$sql = "SELECT u.user_id, u.username, u.role, u.status,
               TO_CHAR(u.created_at, 'YYYY-MM-DD') AS member_since,
               s.student_id, s.full_name, s.email, s.major, s.gpa, s.phone, s.address, s.credits
        FROM USERS u LEFT JOIN STUDENTS s ON s.user_id = u.user_id
        WHERE u.user_id = :p_uid";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':p_uid', $userId);
oci_execute($stmt);
$p = oci_fetch_assoc($stmt);

$displayName = $p['FULL_NAME'] ?? $p['USERNAME'];
$clean = preg_replace('/[^A-Za-z0-9 ]/', '', $displayName);
$parts = preg_split('/\s+/', trim($clean));
$initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . (count($parts) > 1 ? substr(end($parts), 0, 1) : ''));
if ($initials === '') { $initials = 'U'; }
$na = t('p.na');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('p.title') ?> – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2><i class="ri-user-line"></i> <?= t('p.title') ?></h2>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <div class="profile-grid">
        <!-- Identity card -->
        <aside class="info-card profile-side">
            <div class="profile-avatar"><?= htmlspecialchars($initials) ?></div>
            <h3 class="profile-name"><?= htmlspecialchars($displayName) ?></h3>
            <div class="profile-role">
                <span class="badge badge-<?= $role ?>"><?= strtoupper($role) ?></span>
            </div>
            <ul class="profile-facts">
                <li><span><?= t('p.username') ?></span><strong><?= htmlspecialchars($p['USERNAME']) ?></strong></li>
                <?php if (!empty($p['STUDENT_ID'])): ?>
                <li><span><?= t('db.f_sid') ?></span><strong>SID-<?= str_pad((string)$p['STUDENT_ID'], 3, '0', STR_PAD_LEFT) ?></strong></li>
                <?php endif; ?>
                <li><span><?= t('p.status') ?></span><strong><?= htmlspecialchars($p['STATUS']) ?></strong></li>
                <li><span><?= t('p.member_since') ?></span><strong><?= htmlspecialchars($p['MEMBER_SINCE']) ?></strong></li>
            </ul>
        </aside>

        <!-- Detail + forms -->
        <div class="profile-main">
            <div class="info-card">
                <h3><i class="ri-clipboard-line"></i> <?= t('p.acct_info') ?></h3>
                <table class="info-table">
                    <tr><th><?= t('db.f_fullname') ?></th><td><?= htmlspecialchars($p['FULL_NAME'] ?? $na) ?></td></tr>
                    <tr><th><?= t('db.f_email') ?></th><td><?= htmlspecialchars($p['EMAIL'] ?? $na) ?></td></tr>
                    <tr><th><?= t('db.f_major') ?></th><td><?= htmlspecialchars($p['MAJOR'] ?? $na) ?></td></tr>
                    <?php if ($p['GPA'] !== null): ?>
                    <tr><th><?= t('db.gpa') ?></th><td><span class="badge badge-student"><?= number_format((float)$p['GPA'], 2) ?> / 4.00</span></td></tr>
                    <?php endif; ?>
                    <?php if ($p['CREDITS'] !== null): ?>
                    <tr><th><?= t('db.material') ?></th><td><?= number_format((int)$p['CREDITS']) ?> <?= t('db.credits') ?></td></tr>
                    <?php endif; ?>
                    <tr><th><?= t('p.phone') ?></th><td><?= htmlspecialchars($p['PHONE'] ?? $na) ?></td></tr>
                    <tr><th><?= t('p.address') ?></th><td><?= htmlspecialchars($p['ADDRESS'] ?? $na) ?></td></tr>
                </table>
            </div>

            <?php if ($role === 'student'): ?>
            <div class="info-card">
                <h3><i class="ri-edit-2-line"></i> <?= t('p.edit_contact') ?></h3>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_contact">
                    <div class="form-group">
                        <label for="email"><?= t('db.f_email') ?></label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($p['EMAIL'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone"><?= t('p.phone') ?></label>
                        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($p['PHONE'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="address"><?= t('p.address') ?></label>
                        <input type="text" id="address" name="address" value="<?= htmlspecialchars($p['ADDRESS'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary"><?= t('p.save') ?></button>
                </form>
            </div>
            <?php endif; ?>

            <div class="info-card">
                <h3><i class="ri-lock-line"></i> <?= t('p.change_pw') ?></h3>
                <form method="POST" action="" autocomplete="off">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label for="current_password"><?= t('p.cur_pw') ?></label>
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="form-group">
                        <label for="new_password"><?= t('p.new_pw') ?> <small class="text-muted"><?= t('p.min8') ?></small></label>
                        <input type="password" id="new_password" name="new_password" required autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password"><?= t('p.confirm_pw') ?></label>
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary"><?= t('p.update_pw') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
