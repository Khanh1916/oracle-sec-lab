<?php

require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php'); exit;
}

$results  = [];
$errMsg   = '';
$keyword  = '';
$searched = false;

if (isset($_GET['q'])) {
    $keyword  = $_GET['q'];
    $searched = true;

    $ddlBlacklist = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'CREATE', 'ALTER', 'TRUNCATE'];
    $inputUpper   = strtoupper($keyword);
    $blocked      = false;
    foreach ($ddlBlacklist as $bad) {
        if (strpos($inputUpper, $bad) !== false) {
            $blocked = true;
            break;
        }
    }

    if ($blocked) {
        $errMsg = t('se.err_blocked');
    } elseif (strlen($keyword) > 200) {
        $errMsg = t('se.err_long');
    } else {
        $conn = getDbConnection();

        $sql = "SELECT student_id, full_name, major FROM STUDENTS WHERE (full_name LIKE '%$keyword%' OR major LIKE '%$keyword%') AND hidden_marker = 'NORMAL' AND ROWNUM <= 5";

        $stmt = oci_parse($conn, $sql);

        $execResult = @oci_execute($stmt);

        if (!$execResult) {
            $errMsg = t('se.err_fail');
        } else {
            while ($row = oci_fetch_assoc($stmt)) {
                $results[] = $row;
            }
            logAction(
                $_SESSION['user_id'],
                'STUDENT_SEARCH',
                json_encode(['q' => substr($keyword, 0, 100)])
            );
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
    <title><?= t('se.title') ?> – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2><i class="ri-search-line"></i> <?= t('se.title') ?></h2>
    <p class="text-muted"><?= t('se.intro') ?></p>

    <form method="GET" action="">
        <div class="search-bar">
            <input type="text" name="q"
                   value="<?= htmlspecialchars($keyword) ?>"
                   placeholder="<?= htmlspecialchars(t('se.placeholder')) ?>"
                   class="search-input">
            <button type="submit" class="btn btn-primary"><?= t('se.btn') ?></button>
        </div>
    </form>

    <?php if ($errMsg): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($errMsg) ?></div>
    <?php endif; ?>

    <?php if ($searched && !$errMsg): ?>
        <div class="results-section">
            <h3><?= t('se.results') ?> <?php if ($keyword): ?><?= t('se.for') ?> "<em><?= htmlspecialchars(substr($keyword,0,50)) ?></em>"<?php endif; ?></h3>

            <?php if (count($results) === 0): ?>
                <p class="no-results"><?= t('se.none') ?></p>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?= t('db.f_sid') ?></th>
                        <th><?= t('db.f_fullname') ?></th>
                        <th><?= t('db.f_major') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['STUDENT_ID'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['FULL_NAME']  ?? '') ?></td>
                        <td><?= htmlspecialchars($row['MAJOR']      ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="hint-box">
        <details>
            <summary>ℹ️ <?= t('se.tips') ?></summary>
            <ul>
                <li><?= t('se.tip1') ?></li>
                <li><?= t('se.tip2') ?></li>
                <li><?= t('se.tip3') ?></li>
            </ul>
        </details>
    </div>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
