<?php
// inc_footer.php  –  Footer + closes the app-shell wrappers opened in inc_navbar.
$year = date('Y');
?>
        <footer class="site-footer">
            <div class="footer-inner">
                <div class="footer-col footer-brand">
                    <span class="footer-logo"><i class="ri-graduation-cap-line"></i> <?= htmlspecialchars(APP_NAME) ?></span>
                    <p class="footer-tagline"><?= t('footer.tagline') ?></p>
                </div>
                <div class="footer-col">
                    <h4><?= t('footer.academics') ?></h4>
                    <a href="<?= APP_BASE ?>/courses.php"><?= t('footer.course_catalog') ?></a>
                    <a href="<?= APP_BASE ?>/schedule.php"><?= t('footer.class_timetable') ?></a>
                    <a href="<?= APP_BASE ?>/transcript.php"><?= t('footer.grade_transcript') ?></a>
                </div>
                <div class="footer-col">
                    <h4><?= t('footer.services') ?></h4>
                    <a href="<?= APP_BASE ?>/tuition.php"><?= t('footer.tuition_fees') ?></a>
                    <a href="<?= APP_BASE ?>/store.php"><?= t('footer.material_store') ?></a>
                    <a href="<?= APP_BASE ?>/profile.php"><?= t('footer.my_profile') ?></a>
                </div>
                <div class="footer-col">
                    <h4><?= t('footer.support') ?></h4>
                    <a href="#"><?= t('footer.it_helpdesk') ?></a>
                    <a href="#"><?= t('footer.student_handbook') ?></a>
                    <a href="#"><?= t('footer.contact_office') ?></a>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; <?= $year ?> <?= htmlspecialchars(APP_NAME) ?>. <?= t('footer.rights') ?></span>
                <span class="footer-meta">Oracle Database 23c &middot; PHP 8.3 &middot; v<?= htmlspecialchars(APP_VERSION) ?></span>
            </div>
        </footer>
        </main><!-- .app-content -->
    </div><!-- .app-body -->
</div><!-- .app-shell -->
<script>
// ── Portal shell: theme / language / sidebar / fullscreen ──────────────────
(function () {
    var BASE  = <?= json_encode(APP_BASE) ?>;
    var shell = document.getElementById('app-shell');
    function setPref(k, v) {
        document.cookie = k + '=' + encodeURIComponent(v) + '; path=' + BASE + '; max-age=31536000; samesite=Lax';
    }
    window.portalToggleTheme = function () {
        var el = document.documentElement;
        var next = el.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        el.setAttribute('data-theme', next);
        setPref('portal_theme', next);
        var icon = document.querySelector('#nav-theme-btn .nav-theme-icon');
        if (icon) icon.innerHTML = next === 'dark' ? '<i class="ri-sun-line"></i>' : '<i class="ri-moon-line"></i>';
    };
    window.portalSetLang = function (code) { setPref('portal_lang', code); location.reload(); };
    window.portalToggleSidebar = function () {
        if (!shell) return;
        if (window.matchMedia('(max-width: 900px)').matches) {
            shell.classList.toggle('mobile-open');
        } else {
            shell.classList.toggle('collapsed');
            setPref('portal_sidebar', shell.classList.contains('collapsed') ? 'collapsed' : 'expanded');
        }
    };
    window.portalCloseSidebar = function () { if (shell) shell.classList.remove('mobile-open'); };
    window.portalToggleFullscreen = function () {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
        }
    };
    // Close any other open topbar dropdown, and close on outside click.
    var pops = document.querySelectorAll('.topbar .nav-pop');
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
