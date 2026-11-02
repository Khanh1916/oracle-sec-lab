<?php
// inc_navbar.php  –  App shell: left sidebar + minimal top header.
// Opens .app-shell / .app-body / <main class="app-content"> which inc_footer closes.
if (empty($_SESSION['user_id'])) return;
$role     = $_SESSION['role']     ?? 'student';
$username = $_SESSION['username'] ?? '';

// Portal UI preferences (cosmetic shell).
$theme       = $GLOBALS['PORTAL_THEME']  ?? 'light';
$lang        = $GLOBALS['PORTAL_LANG']   ?? 'en';
$campuses    = $GLOBALS['PORTAL_CAMPUSES'] ?? ['HN' => 'Hà Nội'];
$campusCode  = 'HN';
$sidebar     = (($_COOKIE['portal_sidebar'] ?? '') === 'collapsed') ? 'collapsed' : 'expanded';

// Current page (for active highlighting).
$cur = basename($_SERVER['PHP_SELF'] ?? '');

// Notifications feed + the student's home campus / name (cosmetic; never fatal).
$navUnread = 0; $navNotifs = []; $displayName = $username; $subLine = strtoupper($role);
try {
    $navConn   = getDbConnection();
    $navUserId = (int)($_SESSION['user_id'] ?? 0);
    $navUnread = countUnreadNotifications($navConn, $navUserId, $role);
    $navNotifs = getUserNotifications($navConn, $navUserId, $role, 4);
    if ($role === 'student') {
        $cq = oci_parse($navConn, "SELECT campus, full_name, student_id FROM STUDENTS WHERE user_id = :b_uid");
        oci_bind_by_name($cq, ':b_uid', $navUserId);
        oci_execute($cq);
        if ($cr = oci_fetch_assoc($cq)) {
            $code = trim((string)($cr['CAMPUS'] ?? ''));
            if (isset($campuses[$code])) { $campusCode = $code; }
            if (!empty($cr['FULL_NAME'])) { $displayName = $cr['FULL_NAME']; }
            if (!empty($cr['STUDENT_ID'])) { $subLine = 'SID-' . str_pad((string)$cr['STUDENT_ID'], 3, '0', STR_PAD_LEFT); }
        }
    }
} catch (Throwable $e) {
    // Optional chrome; swallow errors so the shell always renders.
}
$campusName = $campuses[$campusCode] ?? reset($campuses);

$clean    = preg_replace('/[^A-Za-z0-9]/', '', $displayName !== '' ? $displayName : $username);
$initials = strtoupper(substr($clean !== '' ? $clean : 'U', 0, 2));

// Helper to render one sidebar link.
$sideLink = function (string $file, string $icon, string $label) use ($cur) {
    $active = ($file === $cur) ? ' active' : '';
    echo '<a class="side-link' . $active . '" href="' . APP_BASE . '/' . $file . '" title="' . htmlspecialchars($label) . '">'
       . '<span class="side-ico">' . $icon . '</span><span class="side-txt">' . htmlspecialchars($label) . '</span></a>';
};
?>
<script>
// Apply saved theme before paint (no flash of the wrong theme).
document.documentElement.setAttribute('data-theme', <?= json_encode($theme) ?>);
</script>
<div class="app-shell <?= $sidebar === 'collapsed' ? 'collapsed' : '' ?>" id="app-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <a href="<?= APP_BASE ?>/dashboard.php">
                <span class="brand-logo">🎓</span>
                <span class="brand-text"><strong><?= htmlspecialchars(APP_NAME) ?></strong><small><?= t('nav.subtitle') ?></small></span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-group">
                <div class="nav-group-title"><?= t('nav.grp_study') ?></div>
                <?php
                $sideLink('dashboard.php',    '🏠', t('nav.dashboard'));
                $sideLink('schedule.php',     '📅', t('nav.timetable'));
                $sideLink('exams.php',        '📝', t('nav.exams'));
                $sideLink('attendance.php',   '✅', t('nav.attendance'));
                $sideLink('courses.php',      '📖', t('nav.courses'));
                $sideLink('course_lookup.php','🔎', t('nav.course_info'));
                if ($role === 'student') { $sideLink('transcript.php', '📄', t('nav.transcript')); }
                ?>
            </div>

            <?php if ($role === 'student'): ?>
            <div class="nav-group">
                <div class="nav-group-title"><?= t('nav.grp_finance') ?></div>
                <?php $sideLink('tuition.php', '💳', t('nav.tuition'));
                      $sideLink('store.php',   '🛒', t('nav.store')); ?>
            </div>
            <?php elseif ($role === 'teacher'): ?>
            <div class="nav-group">
                <div class="nav-group-title"><?= t('nav.grp_teaching') ?></div>
                <?php $sideLink('grades.php', '✏️', t('nav.grading')); ?>
            </div>
            <?php elseif ($role === 'admin'): ?>
            <div class="nav-group">
                <div class="nav-group-title"><?= t('nav.grp_admin') ?></div>
                <?php $sideLink('admin.php', '⚙️', t('nav.admin'));
                      $sideLink('audit.php', '📋', t('nav.audit')); ?>
            </div>
            <?php endif; ?>

            <div class="nav-group">
                <div class="nav-group-title"><?= t('nav.grp_tools') ?></div>
                <?php $sideLink('search.php', '🔍', t('nav.search')); ?>
            </div>

            <div class="nav-group">
                <div class="nav-group-title"><?= t('nav.grp_account') ?></div>
                <?php $sideLink('profile.php',       '👤', t('nav.my_profile'));
                      $sideLink('notifications.php', '🔔', t('nav.notifications')); ?>
                <a class="side-link side-logout" href="<?= APP_BASE ?>/logout.php" title="<?= t('nav.logout') ?>">
                    <span class="side-ico">↩</span><span class="side-txt"><?= t('nav.logout') ?></span>
                </a>
            </div>
        </nav>
    </aside>

    <div class="sidebar-scrim" id="sidebar-scrim" onclick="portalCloseSidebar()"></div>

    <div class="app-body">
        <header class="topbar">
            <button type="button" class="topbar-burger" onclick="portalToggleSidebar()" aria-label="<?= t('ctl.menu') ?>">☰</button>

            <div class="topbar-right">
                <!-- Home campus (fixed per student; read-only) -->
                <details class="nav-pop nav-campus">
                    <summary aria-label="<?= t('ctl.campus') ?>">
                        <span class="ctl-chip">📍 <span class="ctl-hide-sm"><?= htmlspecialchars(tf('ctl.campus_fmt', $campusName, $campusCode)) ?></span> <span class="ctl-caret">▾</span></span>
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

                <!-- Language -->
                <details class="nav-pop nav-lang">
                    <summary aria-label="<?= t('ctl.language') ?>">
                        <span class="ctl-chip">🌐 <span class="ctl-hide-sm"><?= $lang === 'vi' ? 'Tiếng Việt' : 'English' ?></span> <span class="ctl-caret">▾</span></span>
                    </summary>
                    <div class="nav-pop-panel">
                        <div class="nav-pop-head"><?= t('ctl.language') ?></div>
                        <?php foreach ($GLOBALS['PORTAL_LANGS'] as $code => $name): ?>
                            <button type="button" class="nav-pop-item np-choice<?= $code === $lang ? ' np-active' : '' ?>" onclick="portalSetLang('<?= $code ?>')">
                                <span><?= $code === 'vi' ? '🇻🇳' : '🇬🇧' ?> <?= htmlspecialchars($name) ?></span>
                                <span class="np-check">✓</span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </details>

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
                            [$tagLabel, $tagClass] = notifCategoryMeta($n['CATEGORY']); ?>
                            <a class="nav-pop-item np-notif<?= (int)$n['IS_READ'] ? '' : ' np-unread' ?>" href="<?= APP_BASE ?>/notifications.php">
                                <span class="np-tag <?= $tagClass ?>"><?= $tagLabel ?></span>
                                <span><?= htmlspecialchars(mb_strimwidth($n['TITLE'], 0, 54, '…')) ?></span>
                            </a>
                        <?php endforeach; endif; ?>
                        <a class="nav-pop-all" href="<?= APP_BASE ?>/notifications.php"><?= t('nav.notif_viewall') ?> &rarr;</a>
                    </div>
                </details>

                <!-- Dark mode -->
                <button type="button" class="nav-theme" id="nav-theme-btn" onclick="portalToggleTheme()" title="<?= t('ctl.theme') ?>" aria-label="<?= t('ctl.theme') ?>">
                    <span class="nav-theme-icon"><?= $theme === 'dark' ? '☀️' : '🌙' ?></span>
                </button>

                <!-- Fullscreen -->
                <button type="button" class="nav-theme nav-fs" onclick="portalToggleFullscreen()" title="<?= t('ctl.fullscreen') ?>" aria-label="<?= t('ctl.fullscreen') ?>">⛶</button>

                <!-- Account -->
                <details class="nav-pop nav-avatar">
                    <summary aria-label="Account">
                        <span class="avatar-circle"><?= htmlspecialchars($initials) ?></span>
                        <span class="topbar-user ctl-hide-sm">
                            <strong><?= htmlspecialchars($displayName) ?></strong>
                            <small><?= htmlspecialchars($subLine) ?></small>
                        </span>
                    </summary>
                    <div class="nav-pop-panel">
                        <div class="nav-pop-user">
                            <span class="avatar-circle lg"><?= htmlspecialchars($initials) ?></span>
                            <div>
                                <strong><?= htmlspecialchars($displayName) ?></strong><br>
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
        </header>

        <main class="app-content">
