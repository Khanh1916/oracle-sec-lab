<?php
/**
 * DBS401 - Group 07
 * secure_versions/search_secure.php
 *
 * SECURE VERSION of search.php (Vulnerability 1 Fix)
 *
 * FIXES APPLIED:
 *   1. Bind variables (OCI8 oci_bind_by_name) → no string concatenation.
 *   2. Server-side input validation (length, character whitelist).
 *   3. Remove weak blacklist — not a real defense against SQLi.
 *   4. Oracle user privilege review (read-only role for web user).
 *
 * COMPARE WITH VULNERABLE VERSION:
 *   Vulnerable:  ... WHERE full_name LIKE '%$keyword%' ...  ← concatenation
 *   Secure:      ... WHERE full_name LIKE :kw ...           ← bind variable
 */

require_once dirname(__DIR__) . '/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php'); exit;
}

$results  = [];
$errMsg   = '';
$keyword  = '';
$searched = false;

if (isset($_GET['q'])) {
    $keyword  = trim($_GET['q']);
    $searched = true;

    // ── 1. Validate length and character set ────────────────
    if (strlen($keyword) < 2) {
        $errMsg = 'Search keyword must be at least 2 characters.';
    } elseif (strlen($keyword) > 100) {
        $errMsg = 'Search keyword is too long (max 100 characters).';
    } elseif (!preg_match('/^[\p{L}\p{N}\s\-_.]+$/u', $keyword)) {
        // Allow Unicode letters, numbers, spaces, hyphens, underscores, dots
        $errMsg = 'Search keyword contains invalid characters.';
    } else {
        $conn = getDbConnection();

        // ── 2. Parameterized query with bind variable ───────
        $searchParam = '%' . $keyword . '%';
        $sql  = "SELECT student_id, full_name, major
                 FROM STUDENTS
                 WHERE (full_name LIKE :kw OR major LIKE :kw2)
                   AND hidden_marker = 'NORMAL'
                   AND ROWNUM <= 10";

        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':kw',  $searchParam);
        oci_bind_by_name($stmt, ':kw2', $searchParam);
        oci_execute($stmt);

        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $row;
        }

        logAction(
            $_SESSION['user_id'],
            'STUDENT_SEARCH_SECURE',
            json_encode(['q' => substr($keyword, 0, 60)])
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>[SECURE] Student Search – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include dirname(__DIR__) . '/inc_navbar.php'; ?>
<div class="container">
    <h2>🔍 [SECURE] Student Search</h2>
    <div class="alert alert-info" style="border-left-color:#2d6a4f;background:#f0fff4;">
        ✅ This is the <strong>patched version</strong>. SQL Injection is mitigated via bind variables.
    </div>

    <form method="GET" action="">
        <div class="search-bar">
            <input type="text" name="q"
                   value="<?= htmlspecialchars($keyword) ?>"
                   placeholder="Enter student name or major..."
                   class="search-input">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>

    <?php if ($errMsg): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($errMsg) ?></div>
    <?php endif; ?>

    <?php if ($searched && !$errMsg): ?>
        <div class="results-section">
            <h3>Results <?php if ($keyword): ?>for "<em><?= htmlspecialchars($keyword) ?></em>"<?php endif; ?></h3>
            <?php if (!$results): ?>
                <p class="no-results">No students found.</p>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Student ID</th><th>Full Name</th><th>Major</th></tr></thead>
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

    <div class="info-card" style="margin-top:24px;border-left:4px solid #2d6a4f;">
        <h3>🛡️ Security Fix Summary</h3>
        <table class="info-table">
            <tr><th>Issue</th><td>UNION-based SQL Injection via unparameterized query</td></tr>
            <tr><th>Fix</th><td>Replace string concatenation with OCI8 bind variables (<code>oci_bind_by_name</code>)</td></tr>
            <tr><th>Extra</th><td>Input whitelist validation (Unicode letters/numbers only)</td></tr>
            <tr><th>DB Layer</th><td>Grant only SELECT on STUDENTS to web DB user; revoke access to FLAGS, ADMIN_SECRETS, etc.</td></tr>
        </table>
        <pre style="margin-top:12px;background:#1a1a2e;color:#e8e8e8;padding:14px;border-radius:8px;font-size:0.82rem;overflow-x:auto;">
<span style="color:#f4a261;">// VULNERABLE:</span>
$sql = "SELECT ... WHERE full_name LIKE '%<span style="color:#d62828;">$keyword</span>%' ...";

<span style="color:#52b788;">// SECURE:</span>
$searchParam = '%' . $keyword . '%';
$sql  = "SELECT ... WHERE full_name LIKE <span style="color:#52b788;">:kw</span> ...";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':kw', $searchParam);  <span style="color:#aaa;">// value never interpreted as SQL</span>
oci_execute($stmt);
        </pre>
    </div>
</div>
</body>
</html>
