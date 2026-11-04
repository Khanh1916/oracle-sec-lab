<?php
// handbook.php – Student Handbook (academic regulations, policies, lab/CTF usage).
// Cosmetic content page linked from the footer. No challenge logic here.
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
    <title><?= t('hb.title') ?> &ndash; FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
    <style>
        .hb-list { margin: 8px 0 0; padding-left: 20px; line-height: 1.95; }
        .hb-list li { margin-bottom: 4px; }
        .hb-toc { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 22px; }
        .hb-toc a { font-size: 0.82rem; font-weight: 600; padding: 6px 12px; border-radius: 99px; background: rgba(124,58,237,0.1); color: var(--primary); text-decoration: none; }
        .hb-toc a:hover { background: rgba(124,58,237,0.18); text-decoration: none; }
    </style>
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2><i class="ri-book-marked-line"></i> <?= t('hb.title') ?></h2>
    <p class="text-muted"><?= t('hb.subtitle') ?></p>

    <div class="hb-toc">
        <a href="#regulations"><?= t('hb.s1_title') ?></a>
        <a href="#attendance"><?= t('hb.s2_title') ?></a>
        <a href="#exams"><?= t('hb.s3_title') ?></a>
        <a href="#integrity"><?= t('hb.s4_title') ?></a>
        <a href="#lab"><?= t('hb.s5_title') ?></a>
        <a href="#help"><?= t('hb.s6_title') ?></a>
    </div>

    <div class="info-card" id="regulations">
        <h3><i class="ri-government-line"></i> <?= t('hb.s1_title') ?></h3>
        <ul class="hb-list">
            <li><?= t('hb.s1_1') ?></li>
            <li><?= t('hb.s1_2') ?></li>
            <li><?= t('hb.s1_3') ?></li>
            <li><?= t('hb.s1_4') ?></li>
        </ul>
    </div>

    <div class="info-card" id="attendance">
        <h3><i class="ri-calendar-check-line"></i> <?= t('hb.s2_title') ?></h3>
        <ul class="hb-list">
            <li><?= t('hb.s2_1') ?></li>
            <li><?= t('hb.s2_2') ?></li>
            <li><?= t('hb.s2_3') ?></li>
        </ul>
    </div>

    <div class="info-card" id="exams">
        <h3><i class="ri-file-list-3-line"></i> <?= t('hb.s3_title') ?></h3>
        <ul class="hb-list">
            <li><?= t('hb.s3_1') ?></li>
            <li><?= t('hb.s3_2') ?></li>
            <li><?= t('hb.s3_3') ?></li>
        </ul>
    </div>

    <div class="info-card" id="integrity">
        <h3><i class="ri-shield-check-line"></i> <?= t('hb.s4_title') ?></h3>
        <ul class="hb-list">
            <li><?= t('hb.s4_1') ?></li>
            <li><?= t('hb.s4_2') ?></li>
        </ul>
    </div>

    <div class="info-card" id="lab">
        <h3><i class="ri-flask-line"></i> <?= t('hb.s5_title') ?></h3>
        <ul class="hb-list">
            <li><?= t('hb.s5_1') ?></li>
            <li><?= t('hb.s5_2') ?></li>
            <li><?= t('hb.s5_3') ?></li>
            <li><?= t('hb.s5_4') ?></li>
        </ul>
    </div>

    <div class="info-card" id="help">
        <h3><i class="ri-customer-service-2-line"></i> <?= t('hb.s6_title') ?></h3>
        <ul class="hb-list">
            <li><?= t('hb.s6_1') ?></li>
            <li><?= t('hb.s6_2') ?></li>
        </ul>
        <a class="btn btn-secondary" href="<?= APP_BASE ?>/support.php" style="margin-top:12px; padding:6px 14px; font-size:0.82rem;"><?= t('hb.s6_link') ?> &rarr;</a>
    </div>

    <p class="text-muted" style="font-size:0.8rem;"><i class="ri-information-line"></i> <?= t('hb.note') ?></p>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
