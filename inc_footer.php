<?php
// inc_footer.php  –  Shared site footer (purely cosmetic; no app logic)
// Included near </body> on authenticated portal pages.
$year = date('Y');
?>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-col footer-brand">
            <span class="footer-logo">🎓 <?= htmlspecialchars(APP_NAME) ?></span>
            <p class="footer-tagline">DBS401 – Database Security Lab. For authorized training &amp; CTF use only.</p>
        </div>
        <div class="footer-col">
            <h4>Academics</h4>
            <a href="<?= APP_BASE ?>/courses.php">Course Catalog</a>
            <a href="<?= APP_BASE ?>/schedule.php">Class Timetable</a>
            <a href="<?= APP_BASE ?>/transcript.php">Grade Transcript</a>
        </div>
        <div class="footer-col">
            <h4>Services</h4>
            <a href="<?= APP_BASE ?>/tuition.php">Tuition &amp; Fees</a>
            <a href="<?= APP_BASE ?>/store.php">Material Store</a>
            <a href="<?= APP_BASE ?>/profile.php">My Profile</a>
        </div>
        <div class="footer-col">
            <h4>Support</h4>
            <a href="#">IT Helpdesk</a>
            <a href="#">Student Handbook</a>
            <a href="#">Contact Office</a>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; <?= $year ?> <?= htmlspecialchars(APP_NAME) ?>. All rights reserved.</span>
        <span class="footer-meta">Oracle Database 23c &middot; PHP 8.3 &middot; v<?= htmlspecialchars(APP_VERSION) ?></span>
    </div>
</footer>
