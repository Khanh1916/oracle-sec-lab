<?php

require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php'); exit;
}

$code     = '';
$info     = null;
$searched = false;

if (isset($_GET['code'])) {
    $code     = $_GET['code'];
    $searched = true;

    $conn = getDbConnection();

    /*
     * The PHP layer looks safe: the course code is passed as a BIND variable.
     * But FN_COURSE_LOOKUP builds its query by string concatenation inside
     * EXECUTE IMMEDIATE, so the injection happens in PL/SQL, not here.
     * Binding at the call site does NOT protect a vulnerable stored program.
     */
    $sql  = "SELECT FN_COURSE_LOOKUP(:code) AS info FROM DUAL";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':code', $code);
    @oci_execute($stmt);
    $row  = oci_fetch_assoc($stmt);
    $info = $row ? $row['INFO'] : null;

    logAction(
        $_SESSION['user_id'],
        'COURSE_LOOKUP',
        json_encode(['code' => substr($code, 0, 100)])
    );
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('cl.title') ?> – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>📘 <?= t('cl.heading') ?></h2>
    <p class="text-muted"><?= t('cl.intro') ?></p>

    <form method="GET" action="">
        <div class="search-bar">
            <input type="text" name="code"
                   value="<?= htmlspecialchars($code) ?>"
                   placeholder="<?= htmlspecialchars(t('cl.placeholder')) ?>"
                   class="search-input">
            <button type="submit" class="btn btn-primary"><?= t('cl.btn') ?></button>
        </div>
    </form>

    <?php if ($searched): ?>
        <div class="results-section">
            <h3><?= tf('cl.result_fmt', '<em>' . htmlspecialchars(substr($code, 0, 80)) . '</em>') ?></h3>
            <div class="info-card">
                <table class="data-table">
                    <tbody>
                        <tr>
                            <th style="width:160px;"><?= t('db.c_name') ?></th>
                            <td><?= htmlspecialchars($info ?? t('cl.not_found')) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="hint-box">
        <details>
            <summary>ℹ️ <?= t('cl.tips') ?></summary>
            <ul>
                <li><?= t('cl.tip1') ?></li>
                <li><?= t('cl.tip2') ?></li>
            </ul>
        </details>
    </div>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
