<?php
// inc_footer.php  –  Shared site footer (purely cosmetic; no app logic)
// Included near </body> on authenticated portal pages.
$year = date('Y');
?>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-col footer-brand">
            <span class="footer-logo">🎓 <?= htmlspecialchars(APP_NAME) ?></span>
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
