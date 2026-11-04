<?php

require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location:'.APP_BASE.'/login.php'); exit; }

$ref = trim($_GET['ref'] ?? '');
$transcriptData = null;
$errMsg = '';

if ($ref !== '') {
    if (!preg_match('/^TXN-\d{3}-\d{4}-S\d+$/', $ref)) {
        $errMsg = t('tr.err_format');
    } else {
        $conn = getDbConnection();
        $sql = "SELECT e.enrollment_id, e.student_id, e.transcript_ref,
                       e.semester, e.score,
                       s.full_name, s.major, s.email, u.user_id,
                       c.course_name, c.course_code
                FROM ENROLLMENTS e
                JOIN STUDENTS s ON e.student_id = s.student_id
                JOIN COURSES  c ON e.course_id  = c.course_id
                JOIN USERS    u ON s.user_id    = u.user_id
                WHERE e.transcript_ref = :ref";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':ref', $ref);
        if (!oci_execute($stmt)) {
            $errMsg = t('tr.err_fetch');
        } else {
            $transcriptData = oci_fetch_assoc($stmt);
            if (!$transcriptData) {
                $errMsg = t('tr.err_notfound');
            } elseif ($transcriptData['USER_ID'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
                http_response_code(403);
                $errMsg = t('tr.err_denied');
                $transcriptData = null;
            } else {
                logAction($_SESSION['user_id'], 'TRANSCRIPT_VIEW',
                          json_encode(['ref' => $ref, 'sid' => $transcriptData['STUDENT_ID']]));
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('tr.title') ?> – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2><i class="ri-file-text-line"></i> <?= t('tr.title') ?></h2>
    <form method="GET" action="">
        <div class="search-bar">
            <input type="text" name="ref" value="<?= htmlspecialchars($ref) ?>"
                   placeholder="e.g. TXN-001-2024-S1" class="search-input">
            <button type="submit" class="btn btn-primary"><?= t('tr.btn') ?></button>
        </div>
    </form>

    <?php if ($errMsg): ?>
        <div class="alert alert-<?= http_response_code() === 403 ? 'danger' : 'warning' ?>">
            <?= htmlspecialchars($errMsg) ?>
        </div>
    <?php endif; ?>

    <?php if ($transcriptData): ?>
    <div class="info-card transcript-card">
        <h3><?= t('tr.record') ?></h3>
        <table class="info-table">
            <tr><th><?= t('tr.f_ref') ?></th>   <td><code><?= htmlspecialchars($transcriptData['TRANSCRIPT_REF']) ?></code></td></tr>
            <tr><th><?= t('tr.f_student') ?></th><td><?= htmlspecialchars($transcriptData['FULL_NAME']) ?></td></tr>
            <tr><th><?= t('tr.f_email') ?></th>       <td><?= htmlspecialchars($transcriptData['EMAIL'] ?? t('p.na')) ?></td></tr>
            <tr><th><?= t('tr.f_major') ?></th>       <td><?= htmlspecialchars($transcriptData['MAJOR']) ?></td></tr>
            <tr><th><?= t('tr.f_course') ?></th>      <td><?= htmlspecialchars($transcriptData['COURSE_NAME']) ?> (<?= htmlspecialchars($transcriptData['COURSE_CODE']) ?>)</td></tr>
            <tr><th><?= t('tr.f_semester') ?></th>    <td><?= htmlspecialchars($transcriptData['SEMESTER']) ?></td></tr>
            <tr><th><?= t('tr.f_score') ?></th>       <td><?= htmlspecialchars($transcriptData['SCORE'] ?? t('p.na')) ?></td></tr>
        </table>
        <div class="transcript-footer">
            <small class="text-muted">
                <?= t('tr.enroll_id') ?>: <?= (int)$transcriptData['ENROLLMENT_ID'] ?>
                | <?= t('tr.student_id') ?>: <?= (int)$transcriptData['STUDENT_ID'] ?>
            </small>
        </div>
    </div>
    <?php endif; ?>

    <div class="hint-box">
        <details>
            <summary>ℹ️ <?= t('tr.fmt_title') ?></summary>
            <p>Format: <code>TXN-{ID:3digits}-{Year}-S{Semester}</code></p>
            <p>Example: <code>TXN-001-2024-S1</code></p>
        </details>
    </div>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>