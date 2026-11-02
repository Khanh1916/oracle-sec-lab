<?php
// inc_navbar.php  –  Shared navigation bar (top app bar)
if (empty($_SESSION['user_id'])) return;
$role     = $_SESSION['role']     ?? 'student';
$username = $_SESSION['username'] ?? '';
// Avatar initials from the username (purely cosmetic).
$clean    = preg_replace('/[^A-Za-z0-9]/', '', $username);
$initials = strtoupper(substr($clean !== '' ? $clean : 'U', 0, 2));

// Portal UI preferences (cosmetic shell).
$theme       = $GLOBALS['PORTAL_THEME']  ?? 'light';
$lang        = $GLOBALS['PORTAL_LANG']   ?? 'en';
$campuses    = $GLOBALS['PORTAL_CAMPUSES'] ?? ['HN' => 'Hà Nội'];
$campusCode  = 'HN'; // each student has one fixed home campus (resolved below)

// Notification Center feed for the bell + the student's home campus
// (cosmetic; never fatal).
$navUnread = 0;
$navNotifs = [];
try {
    $navConn   = getDbConnection();
    $navUserId = (int)($_SESSION['user_id'] ?? 0);
    $navUnread = countUnreadNotifications($navConn, $navUserId, $role);
    $navNotifs = getUserNotifications($navConn, $navUserId, $role, 4);
    if ($role === 'student') {
        $cq = oci_parse($navConn, "SELECT campus FROM STUDENTS WHERE user_id = :b_uid");
        oci_bind_by_name($cq, ':b_uid', $navUserId);
        oci_execute($cq);
        if ($cr = oci_fetch_assoc($cq)) {
            $code = trim((string)($cr['CAMPUS'] ?? ''));
            if (isset($campuses[$code])) { $campusCode = $code; }
        }
    }
} catch (Throwable $e) {
    // Notifications / campus are optional chrome; swallow errors so the navbar always renders.
}
$campusName = $campuses[$campusCode] ?? reset($campuses);
?>
<script>
// Apply the saved theme before the body paints (no flash of the wrong theme).
document.documentElement.setAttribute('data-theme', <?= json_encode($theme) ?>);
</script>
<nav class="navbar">
    <div class="navbar-brand">
        <a href="<?= APP_BASE ?>/dashboard.php">🎓 <?= htmlspecialchars(APP_NAME) ?></a>
        <span class="nav-subtitle"><?= t('nav.subtitle') ?></span>
    </div>

    <input type="checkbox" id="nav-toggle" class="nav-toggle">
    <label for="nav-toggle" class="nav-burger" aria-label="Menu">&#9776;</label>

    <div class="navbar-links">
        <a href="<?= APP_BASE ?>/dashboard.php"><?= t('nav.dashboard') ?></a>
        <a href="<?= APP_BASE ?>/courses.php"><?= t('nav.courses') ?></a>
        <a href="<?= APP_BASE ?>/course_lookup.php"><?= t('nav.course_info') ?></a>
        <a href="<?= APP_BASE ?>/schedule.php"><?= t('nav.timetable') ?></a>
        <a href="<?= APP_BASE ?>/exams.php"><?= t('nav.exams') ?></a>
        <a href="<?= APP_BASE ?>/attendance.php"><?= t('nav.attendance') ?></a>
        <?php if ($role === 'student'): ?>
            <a href="<?= APP_BASE ?>/transcript.php"><?= t('nav.transcript') ?></a>
            <a href="<?= APP_BASE ?>/tuition.php"><?= t('nav.tuition') ?></a>
            <a href="<?= APP_BASE ?>/store.php"><?= t('nav.store') ?></a>
        <?php elseif ($role === 'teacher'): ?>
            <a href="<?= APP_BASE ?>/grades.php"><?= t('nav.grading') ?></a>
        <?php endif; ?>
        <a href="<?= APP_BASE ?>/search.php"><?= t('nav.search') ?></a>
        <?php if ($role === 'admin'): ?>
            <a href="<?= APP_BASE ?>/admin.php"><?= t('nav.admin') ?></a>
            <a href="<?= APP_BASE ?>/audit.php"><?= t('nav.audit') ?></a>
        <?php endif; ?>

        <!-- Home campus (fixed per student; read-only) -->
        <details class="nav-pop nav-campus">
            <summary aria-label="<?= t('ctl.campus') ?>">
                <span class="ctl-chip">📍 <?= htmlspecialchars(tf('ctl.campus_fmt', $campusName, $campusCode)) ?> <span class="ctl-caret">▾</span></span>
            </summary>
            <div class="nav-pop-panel">
                <div class="nav-pop-head"><?= t('ctl.campus') ?></div>
                <div class="nav-pop-item np-campus">
                    <span class="np-campus-ico">🏢</span>
                    <span class="np-campus-text">
                        <strong><?= htmlspecialchars(tf('ctl.campus_fmt', $campusName, $campusCode)) ?></strong>
                        <span class="np-campus-sub"><?= htmlspecialchars($campusCode) ?> · <?= t('ctl.primary') ?></span>
                    </span>
                </div>
            </div>
        </details>

        <!-- Language selector -->
        <details class="nav-pop nav-lang">
            <summary aria-label="<?= t('ctl.language') ?>">
                <span class="ctl-chip">🌐 <?= strtoupper($lang) ?> <span class="ctl-caret">▾</span></span>
            </summary>
            <div class="nav-pop-panel">
                <div class="nav-pop-head"><?= t('ctl.language') ?></div>
                <?php foreach ($GLOBALS['PORTAL_LANGS'] as $code => $name): ?>
                    <button type="button" class="nav-pop-item np-choice<?= $code === $lang ? ' np-active' : '' ?>"
                            onclick="portalSetLang('<?= $code ?>')">
                        <span><?= $code === 'vi' ? '🇻🇳' : '🇬🇧' ?> <?= htmlspecialchars($name) ?></span>
                        <span class="np-check">✓</span>
                    </button>
                <?php endforeach; ?>
            </div>
        </details>

        <!-- Dark mode toggle -->
        <button type="button" class="nav-theme" id="nav-theme-btn" onclick="portalToggleTheme()"
                title="<?= t('ctl.theme') ?>" aria-label="<?= t('ctl.theme') ?>">
            <span class="nav-theme-icon"><?= $theme === 'dark' ? '☀️' : '🌙' ?></span>
        </button>

        <!-- Notification bell -->
        <details class="nav-pop nav-bell">
            <summary aria-label="<?= t('nav.notifications') ?>">
                🔔<?php if ($navUnread > 0): ?><span class="nav-count"><?= $navUnread > 9 ? '9+' : $navUnread ?></span><?php endif; ?>
            </summary>
            <div class="nav-pop-panel">
                <div class="nav-pop-head"><?= t('nav.notif_head') ?><?php if ($navUnread > 0): ?> · <?= tf('nav.notif_new_fmt', $navUnread) ?><?php endif; ?></div>
                <?php if (!$navNotifs): ?>
                    <div class="nav-pop-item" style="color:var(--muted);"><?= t('nav.notif_none') ?></div>
                <?php else: foreach ($navNotifs as $n):
                    [$tagLabel, $tagClass] = notifCategoryMeta($n['CATEGORY']);
                ?>
                    <a class="nav-pop-item np-notif<?= (int)$n['IS_READ'] ? '' : ' np-unread' ?>" href="<?= APP_BASE ?>/notifications.php">
                        <span class="np-tag <?= $tagClass ?>"><?= $tagLabel ?></span>
                        <span><?= htmlspecialchars(mb_strimwidth($n['TITLE'], 0, 54, '…')) ?></span>
                    </a>
                <?php endforeach; endif; ?>
                <a class="nav-pop-all" href="<?= APP_BASE ?>/notifications.php"><?= t('nav.notif_viewall') ?> &rarr;</a>
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
                <a class="nav-pop-item" href="<?= APP_BASE ?>/profile.php">👤 <?= t('nav.my_profile') ?></a>
                <a class="nav-pop-item" href="<?= APP_BASE ?>/notifications.php">🔔 <?= t('nav.notifications') ?></a>
                <?php if ($role === 'admin'): ?>
                    <a class="nav-pop-item" href="<?= APP_BASE ?>/admin.php">⚙️ <?= t('nav.admin_panel') ?></a>
                <?php endif; ?>
                <a class="nav-pop-item np-logout" href="<?= APP_BASE ?>/logout.php">&#8617; <?= t('nav.logout') ?></a>
            </div>
        </details>
    </div>
</nav>
<script>
// ── Portal shell preferences (theme / language / campus) ───────────────────
(function () {
    var BASE = <?= json_encode(APP_BASE) ?>;
    function setPref(k, v) {
        document.cookie = k + '=' + encodeURIComponent(v) + '; path=' + BASE + '; max-age=31536000; samesite=Lax';
    }
    window.portalToggleTheme = function () {
        var el = document.documentElement;
        var next = el.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        el.setAttribute('data-theme', next);
        setPref('portal_theme', next);
        var icon = document.querySelector('#nav-theme-btn .nav-theme-icon');
        if (icon) icon.textContent = next === 'dark' ? '☀️' : '🌙';
    };
    window.portalSetLang = function (code) {
        setPref('portal_lang', code);
        location.reload();
    };

    // Close any other open nav dropdown, and close on outside click (no framework).
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
