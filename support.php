<?php
// support.php – Help & Support page (IT Helpdesk / Student Handbook / Contact Office).
// Cosmetic content page; linked from the footer. No challenge logic here.
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('sup.title') ?> &ndash; FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2><i class="ri-customer-service-2-line"></i> <?= t('sup.title') ?></h2>
    <p class="text-muted"><?= t('sup.subtitle') ?></p>

    <div class="info-card" id="helpdesk">
        <h3><i class="ri-customer-service-line"></i> <?= t('sup.hd_title') ?></h3>
        <p class="text-muted"><?= t('sup.hd_desc') ?></p>
        <table class="info-table">
            <tr><th><?= t('sup.email_label') ?></th><td><a href="mailto:helpdesk@fpt.edu.vn">helpdesk@fpt.edu.vn</a></td></tr>
            <tr><th><?= t('sup.phone_label') ?></th><td>(024) 7300 1866</td></tr>
            <tr><th><?= t('sup.hours_label') ?></th><td><?= t('sup.hd_hours') ?></td></tr>
        </table>
    </div>

    <div class="info-card" id="office">
        <h3><i class="ri-building-line"></i> <?= t('sup.of_title') ?></h3>
        <p class="text-muted"><?= t('sup.of_desc') ?></p>
        <table class="info-table">
            <tr><th><?= t('sup.addr_label') ?></th><td><?= t('sup.of_addr') ?></td></tr>
            <tr><th><?= t('sup.email_label') ?></th><td><a href="mailto:student.office@fpt.edu.vn">student.office@fpt.edu.vn</a></td></tr>
            <tr><th><?= t('sup.phone_label') ?></th><td>(024) 7300 5588</td></tr>
        </table>
    </div>

    <p class="text-muted" style="font-size:0.8rem;"><i class="ri-information-line"></i> <?= t('sup.note') ?></p>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
