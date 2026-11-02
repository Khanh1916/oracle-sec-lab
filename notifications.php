<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location: ' . APP_BASE . '/login.php'); exit; }

$role   = $_SESSION['role'];
$userId = (int)$_SESSION['user_id'];
$conn   = getDbConnection();

// Handle read-state actions (Post/Redirect/Get to avoid resubmission).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_all') {
        markAllNotificationsRead($conn, $userId, $role);
        $_SESSION['notif_flash'] = t('n.flash_all');
    } elseif ($action === 'mark_one' && !empty($_POST['id'])) {
        markNotificationRead($conn, $userId, (int)$_POST['id']);
    }
    logAction($userId, 'VIEW_NOTIFICATIONS', $action);
    header('Location: ' . APP_BASE . '/notifications.php');
    exit;
}

$flash = $_SESSION['notif_flash'] ?? '';
unset($_SESSION['notif_flash']);

$notifs = getUserNotifications($conn, $userId, $role);
$unread = 0;
foreach ($notifs as $n) { if (!(int)$n['IS_READ']) { $unread++; } }
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('n.title') ?> – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:20px;">
        <h2 style="margin:0;">🔔 <?= t('n.title') ?></h2>
        <?php if ($unread > 0): ?>
            <form method="POST" action="" style="margin:0;">
                <input type="hidden" name="action" value="mark_all">
                <button type="submit" class="btn btn-secondary" style="padding:6px 14px; font-size:0.82rem;">
                    <?= tf('n.mark_all_fmt', $unread) ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <p class="text-muted" style="font-size:0.85rem; margin-bottom:16px;">
        <?= tf('n.summary_fmt', count($notifs), $unread) ?>
    </p>

    <div class="info-card" style="padding:0; overflow:hidden;">
        <?php if (!$notifs): ?>
            <p class="no-results" style="padding:24px;"><?= t('n.none') ?></p>
        <?php else: ?>
            <?php foreach ($notifs as $n):
                [$tagLabel, $tagClass] = notifCategoryMeta($n['CATEGORY']);
                $isRead = (int)$n['IS_READ'];
            ?>
            <div class="notif-row<?= $isRead ? '' : ' notif-unread' ?>">
                <div class="notif-dot" aria-hidden="true"></div>
                <div class="notif-main">
                    <div class="notif-head">
                        <span class="np-tag <?= $tagClass ?>"><?= $tagLabel ?></span>
                        <span class="notif-title"><?= htmlspecialchars($n['TITLE']) ?></span>
                        <?php if (!$isRead): ?><span class="notif-badge-new"><?= t('db.new') ?></span><?php endif; ?>
                    </div>
                    <p class="notif-body"><?= htmlspecialchars($n['BODY']) ?></p>
                    <div class="notif-meta">🕒 <?= htmlspecialchars($n['CREATED_DISP']) ?></div>
                </div>
                <?php if (!$isRead): ?>
                <form method="POST" action="" class="notif-action">
                    <input type="hidden" name="action" value="mark_one">
                    <input type="hidden" name="id" value="<?= (int)$n['NOTIFICATION_ID'] ?>">
                    <button type="submit" class="btn btn-secondary" style="padding:4px 10px; font-size:0.72rem;"><?= t('n.mark_read') ?></button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
