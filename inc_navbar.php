<?php
// inc_navbar.php  –  Shared navigation bar (top app bar)
if (empty($_SESSION['user_id'])) return;
$role     = $_SESSION['role']     ?? 'student';
$username = $_SESSION['username'] ?? '';
// Avatar initials from the username (purely cosmetic).
$clean    = preg_replace('/[^A-Za-z0-9]/', '', $username);
$initials = strtoupper(substr($clean !== '' ? $clean : 'U', 0, 2));

// Notification Center feed for the bell (cosmetic; never fatal).
$navUnread = 0;
$navNotifs = [];
try {
    $navConn   = getDbConnection();
    $navUserId = (int)($_SESSION['user_id'] ?? 0);
    $navUnread = countUnreadNotifications($navConn, $navUserId, $role);
    $navNotifs = getUserNotifications($navConn, $navUserId, $role, 4);
} catch (Throwable $e) {
    // Notifications are optional chrome; swallow errors so the navbar always renders.
}
?>
<nav class="navbar">
    <div class="navbar-brand">
        <a href="<?= APP_BASE ?>/dashboard.php">🎓 <?= htmlspecialchars(APP_NAME) ?></a>
        <span class="nav-subtitle">DBS401 Security Lab</span>
    </div>

    <input type="checkbox" id="nav-toggle" class="nav-toggle">
    <label for="nav-toggle" class="nav-burger" aria-label="Menu">&#9776;</label>

    <div class="navbar-links">
        <a href="<?= APP_BASE ?>/dashboard.php">Dashboard</a>
        <a href="<?= APP_BASE ?>/courses.php">Courses</a>
        <a href="<?= APP_BASE ?>/course_lookup.php">Course Info</a>
        <a href="<?= APP_BASE ?>/schedule.php">Timetable</a>
        <?php if ($role === 'student'): ?>
            <a href="<?= APP_BASE ?>/transcript.php">Transcript</a>
            <a href="<?= APP_BASE ?>/tuition.php">Tuition</a>
            <a href="<?= APP_BASE ?>/store.php">Store</a>
        <?php elseif ($role === 'teacher'): ?>
            <a href="<?= APP_BASE ?>/grades.php">Grading</a>
        <?php endif; ?>
        <a href="<?= APP_BASE ?>/search.php">Search</a>
        <?php if ($role === 'admin'): ?>
            <a href="<?= APP_BASE ?>/admin.php">Admin</a>
            <a href="<?= APP_BASE ?>/audit.php">Audit</a>
        <?php endif; ?>

        <!-- Notification bell -->
        <details class="nav-pop nav-bell">
            <summary aria-label="Notifications">
                🔔<?php if ($navUnread > 0): ?><span class="nav-count"><?= $navUnread > 9 ? '9+' : $navUnread ?></span><?php endif; ?>
            </summary>
            <div class="nav-pop-panel">
                <div class="nav-pop-head">Notifications<?php if ($navUnread > 0): ?> · <?= $navUnread ?> new<?php endif; ?></div>
                <?php if (!$navNotifs): ?>
                    <div class="nav-pop-item" style="color:var(--muted);">No notifications.</div>
                <?php else: foreach ($navNotifs as $n):
                    [$tagLabel, $tagClass] = notifCategoryMeta($n['CATEGORY']);
                ?>
                    <a class="nav-pop-item np-notif<?= (int)$n['IS_READ'] ? '' : ' np-unread' ?>" href="<?= APP_BASE ?>/notifications.php">
                        <span class="np-tag <?= $tagClass ?>"><?= $tagLabel ?></span>
                        <span><?= htmlspecialchars(mb_strimwidth($n['TITLE'], 0, 54, '…')) ?></span>
                    </a>
                <?php endforeach; endif; ?>
                <a class="nav-pop-all" href="<?= APP_BASE ?>/notifications.php">View all notifications &rarr;</a>
            </div>
        </details>

        <!-- Account menu -->
        <details class="nav-pop nav-avatar">
            <summary aria-label="Account">
                <span class="avatar-circle"><?= htmlspecialchars($initials) ?></span>
            </summary>
            <div class="nav-pop-panel">
                <div class="nav-pop-user">
                    <span class="avatar-circle lg"><?= htmlspecialchars($initials) ?></span>
                    <div>
                        <strong><?= htmlspecialchars($username) ?></strong><br>
                        <span class="badge-inline badge-<?= $role ?>"><?= $role ?></span>
                    </div>
                </div>
                <a class="nav-pop-item" href="<?= APP_BASE ?>/profile.php">👤 My Profile</a>
                <a class="nav-pop-item" href="<?= APP_BASE ?>/notifications.php">🔔 Notifications</a>
                <?php if ($role === 'admin'): ?>
                    <a class="nav-pop-item" href="<?= APP_BASE ?>/admin.php">⚙️ Admin Panel</a>
                <?php endif; ?>
                <a class="nav-pop-item np-logout" href="<?= APP_BASE ?>/logout.php">&#8617; Logout</a>
            </div>
        </details>
    </div>
</nav>
<script>
// Close any other open nav dropdown, and close on outside click (no framework).
(function () {
    var pops = document.querySelectorAll('.navbar .nav-pop');
    pops.forEach(function (d) {
        d.addEventListener('toggle', function () {
            if (d.open) pops.forEach(function (o) { if (o !== d) o.open = false; });
        });
    });
    document.addEventListener('click', function (e) {
        pops.forEach(function (d) { if (d.open && !d.contains(e.target)) d.open = false; });
    });
})();
</script>
