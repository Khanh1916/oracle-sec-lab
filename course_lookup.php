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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Info – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>📘 Course Information Lookup</h2>
    <p class="text-muted">Look up an official course name by its course code (e.g. <code>DBS401</code>).</p>

    <form method="GET" action="">
        <div class="search-bar">
            <input type="text" name="code"
                   value="<?= htmlspecialchars($code) ?>"
                   placeholder="Enter course code..."
                   class="search-input">
            <button type="submit" class="btn btn-primary">Look up</button>
        </div>
    </form>

    <?php if ($searched): ?>
        <div class="results-section">
            <h3>Result for "<em><?= htmlspecialchars(substr($code, 0, 80)) ?></em>"</h3>
            <div class="info-card">
                <table class="data-table">
                    <tbody>
                        <tr>
                            <th style="width:160px;">Course Name</th>
                            <td><?= htmlspecialchars($info ?? 'No course found for that code.') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="hint-box">
        <details>
            <summary>ℹ️ Lookup Tips</summary>
            <ul>
                <li>Course codes are short identifiers such as <code>DBS401</code> or <code>NET201</code>.</li>
                <li>The lookup is backed by an Oracle stored function.</li>
            </ul>
        </details>
    </div>
</div>
</body>
</html>
