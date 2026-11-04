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

// Inline SVG flags for the language picker (Windows renders flag emoji as "VN"/"GB" text).
$FLAG_VN = '<svg class="np-flag" viewBox="0 0 30 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect width="30" height="20" fill="#da251d"/><path fill="#ffff00" d="M15,5 L16.18,8.38 L19.76,8.46 L16.9,10.62 L17.94,14.05 L15,12 L12.06,14.05 L13.1,10.62 L10.24,8.46 L13.82,8.38 Z"/></svg>';
$FLAG_GB = '<svg class="np-flag" viewBox="0 0 60 30" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><clipPath id="ukflag"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath><rect width="60" height="30" fill="#012169"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#ukflag)" stroke="#C8102E" stroke-width="4"/><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/><path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/></svg>';

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
                <span class="brand-logo"><i class="ri-graduation-cap-line"></i></span>
                <span class="brand-text"><strong><?= htmlspecialchars(APP_NAME) ?></strong><small><?= t('nav.subtitle') ?></small></span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-group">
                <div class="nav-group-title"><?= t('nav.grp_study') ?></div>
                <?php
                $sideLink('dashboard.php',    '<i class="ri-home-5-line"></i>', t('nav.dashboard'));
                $sideLink('schedule.php',     '<i class="ri-calendar-schedule-line"></i>', t('nav.timetable'));
                $sideLink('exams.php',        '<i class="ri-file-list-3-line"></i>', t('nav.exams'));
                $sideLink('attendance.php',   '<i class="ri-checkbox-circle-line"></i>', t('nav.attendance'));
                $sideLink('courses.php',      '<i class="ri-book-open-line"></i>', t('nav.courses'));
                $sideLink('course_lookup.php','<i class="ri-file-search-line"></i>', t('nav.course_info'));
                if ($role === 'student') { $sideLink('transcript.php', '<i class="ri-file-text-line"></i>', t('nav.transcript')); }
                ?>
            </div>

            <?php if ($role === 'student'): ?>
            <div class="nav-group">
                <div class="nav-group-title"><?= t('nav.grp_finance') ?></div>
                <?php $sideLink('tuition.php', '<i class="ri-bank-card-line"></i>', t('nav.tuition'));
                      $sideLink('store.php',   '<i class="ri-shopping-cart-2-line"></i>', t('nav.store')); ?>
            </div>
            <?php elseif ($role === 'teacher'): ?>
            <div class="nav-group">
                <div class="nav-group-title"><?= t('nav.grp_teaching') ?></div>
                <?php $sideLink('grades.php', '<i class="ri-edit-2-line"></i>', t('nav.grading')); ?>
            </div>
            <?php elseif ($role === 'admin'): ?>
            <div class="nav-group">
                <div class="nav-group-title"><?= t('nav.grp_admin') ?></div>
                <?php $sideLink('admin.php', '<i class="ri-settings-3-line"></i>', t('nav.admin'));
                      $sideLink('audit.php', '<i class="ri-clipboard-line"></i>', t('nav.audit')); ?>
            </div>
            <?php endif; ?>

            <div class="nav-group">
                <div class="nav-group-title"><?= t('nav.grp_tools') ?></div>
                <?php $sideLink('search.php', '<i class="ri-search-line"></i>', t('nav.search')); ?>
            </div>

            <div class="nav-group">
                <div class="nav-group-title"><?= t('nav.grp_account') ?></div>
                <?php $sideLink('profile.php',       '<i class="ri-user-line"></i>', t('nav.my_profile'));
                      $sideLink('notifications.php', '<i class="ri-notification-3-line"></i>', t('nav.notifications')); ?>
                <a class="side-link side-logout" href="<?= APP_BASE ?>/logout.php" title="<?= t('nav.logout') ?>">
                    <span class="side-ico"><i class="ri-logout-box-r-line"></i></span><span class="side-txt"><?= t('nav.logout') ?></span>
                </a>
            </div>
        </nav>
    </aside>

    <div class="sidebar-scrim" id="sidebar-scrim" onclick="portalCloseSidebar()"></div>

    <div class="app-body">
        <header class="topbar">
            <button type="button" class="topbar-burger" onclick="portalToggleSidebar()" aria-label="<?= t('ctl.menu') ?>"><i class="ri-menu-line"></i></button>

            <div class="topbar-right">
                <!-- Home campus (fixed per student; read-only) -->
                <details class="nav-pop nav-campus">
                    <summary aria-label="<?= t('ctl.campus') ?>">
                        <span class="ctl-chip"><i class="ri-map-pin-line"></i> <span class="ctl-hide-sm"><?= htmlspecialchars(tf('ctl.campus_fmt', $campusName, $campusCode)) ?></span> <span class="ctl-caret">▾</span></span>
                    </summary>
                    <div class="nav-pop-panel">
                        <div class="nav-pop-head"><?= t('ctl.campus') ?></div>
                        <div class="nav-pop-item np-campus">
                            <span class="np-campus-ico"><i class="ri-building-line"></i></span>
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
                        <span class="ctl-chip"><i class="ri-global-line"></i> <span class="ctl-hide-sm"><?= $lang === 'vi' ? 'Tiếng Việt' : 'English' ?></span> <span class="ctl-caret">▾</span></span>
                    </summary>
                    <div class="nav-pop-panel">
                        <div class="nav-pop-head"><?= t('ctl.language') ?></div>
                        <?php foreach ($GLOBALS['PORTAL_LANGS'] as $code => $name): ?>
                            <button type="button" class="nav-pop-item np-choice<?= $code === $lang ? ' np-active' : '' ?>" onclick="portalSetLang('<?= $code ?>')">
                                <span class="np-lang-opt"><?= $code === 'vi' ? $FLAG_VN : $FLAG_GB ?> <?= htmlspecialchars($name) ?></span>
                                <span class="np-check"><i class="ri-check-line"></i></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </details>

                <!-- Notification bell -->
                <details class="nav-pop nav-bell">
                    <summary aria-label="<?= t('nav.notifications') ?>">
                        <i class="ri-notification-3-line"></i><?php if ($navUnread > 0): ?><span class="nav-count"><?= $navUnread > 9 ? '9+' : $navUnread ?></span><?php endif; ?>
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
                    <span class="nav-theme-icon"><?= $theme === 'dark' ? '<i class="ri-sun-line"></i>' : '<i class="ri-moon-line"></i>' ?></span>
                </button>

                <!-- Fullscreen -->
                <button type="button" class="nav-theme nav-fs" onclick="portalToggleFullscreen()" title="<?= t('ctl.fullscreen') ?>" aria-label="<?= t('ctl.fullscreen') ?>"><i class="ri-fullscreen-line"></i></button>

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
                        <a class="nav-pop-item" href="<?= APP_BASE ?>/profile.php"><i class="ri-user-line"></i> <?= t('nav.my_profile') ?></a>
                        <a class="nav-pop-item" href="<?= APP_BASE ?>/notifications.php"><i class="ri-notification-3-line"></i> <?= t('nav.notifications') ?></a>
                        <?php if ($role === 'admin'): ?>
                            <a class="nav-pop-item" href="<?= APP_BASE ?>/admin.php"><i class="ri-settings-3-line"></i> <?= t('nav.admin_panel') ?></a>
                        <?php endif; ?>
                        <a class="nav-pop-item np-logout" href="<?= APP_BASE ?>/logout.php"><i class="ri-logout-box-r-line"></i> <?= t('nav.logout') ?></a>
                    </div>
                </details>
            </div>
        </header>

        <main class="app-content">
