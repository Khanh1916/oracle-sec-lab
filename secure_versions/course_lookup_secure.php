<?php
/**
 * DBS401 - Group 02
 * secure_versions/course_lookup_secure.php
 *
 * SECURE VERSION of course_lookup.php (Vulnerability 4 Fix)
 *
 * ROOT CAUSE:
 *   Binding the argument at the PHP call site is NOT enough. The vulnerable
 *   stored function FN_COURSE_LOOKUP builds its query by string concatenation
 *   inside EXECUTE IMMEDIATE, so the injection happens in PL/SQL regardless of
 *   how carefully the caller binds.
 *
 * FIXES APPLIED:
 *   1. Fix the stored program, not just the call site. Prefer static SQL so no
 *      dynamic query is built from user input:
 *
 *        CREATE OR REPLACE FUNCTION FN_COURSE_LOOKUP_SECURE(p_code IN VARCHAR2)
 *          RETURN VARCHAR2 AS
 *          v_result VARCHAR2(4000);
 *        BEGIN
 *          SELECT course_name INTO v_result
 *            FROM COURSES WHERE course_code = p_code;   -- static SQL, no concat
 *          RETURN v_result;
 *        EXCEPTION
 *          WHEN NO_DATA_FOUND THEN RETURN 'No course found for that code.';
 *        END;
 *        /
 *
 *      If dynamic SQL is genuinely required, bind the value with USING:
 *        EXECUTE IMMEDIATE
 *          'SELECT course_name FROM COURSES WHERE course_code = :c'
 *          INTO v_result USING p_code;                  -- bound, not concatenated
 *
 *   2. Use AUTHID CURRENT_USER (invoker's rights) so the function cannot read
 *      objects the caller is not entitled to, and grant EXECUTE narrowly.
 *   3. Keep secrets out of reach: the web/database role should not be able to
 *      read PLSQL_VAULT at all.
 *
 * This PHP simply calls the fixed function; the security lives in the database.
 */

require_once dirname(__DIR__) . '/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php'); exit;
}

$code     = '';
$info     = null;
$searched = false;

if (isset($_GET['code'])) {
    $code     = trim($_GET['code']);
    $searched = true;

    // Optional defence-in-depth: course codes are short and alphanumeric.
    if (!preg_match('/^[A-Za-z0-9]{1,16}$/', $code)) {
        $info = 'Invalid course code format.';
    } else {
        $conn = getDbConnection();
        $sql  = "SELECT FN_COURSE_LOOKUP_SECURE(:code) AS info FROM DUAL";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':code', $code);
        @oci_execute($stmt);
        $row  = oci_fetch_assoc($stmt);
        $info = $row ? $row['INFO'] : 'No course found for that code.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Info (Secure) – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include dirname(__DIR__) . '/inc_navbar.php'; ?>
<div class="container">
    <h2>Course Information Lookup (Secure)</h2>
    <form method="GET" action="">
        <div class="search-bar">
            <input type="text" name="code" value="<?= htmlspecialchars($code) ?>"
                   placeholder="Enter course code..." class="search-input">
            <button type="submit" class="btn btn-primary">Look up</button>
        </div>
    </form>
    <?php if ($searched): ?>
        <div class="info-card">
            <strong>Course Name:</strong> <?= htmlspecialchars($info ?? '') ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
