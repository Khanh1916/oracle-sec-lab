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
        $errMsg = 'Input contains blocked keywords.';
    } elseif (strlen($keyword) > 200) {
        $errMsg = 'Search keyword too long.';
    } else {
        $conn = getDbConnection();

        $sql = "SELECT student_id, full_name, major FROM STUDENTS WHERE (full_name LIKE '%$keyword%' OR major LIKE '%$keyword%') AND hidden_marker = 'NORMAL' AND ROWNUM <= 5";

        $stmt = oci_parse($conn, $sql);

        $execResult = @oci_execute($stmt);

        if (!$execResult) {
            $errMsg = 'Search failed. Please check your input.';
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Search – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>🔍 Student Search</h2>
    <p class="text-muted">Search students by name or major. Results limited to 5 records.</p>

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
            <h3>Results <?php if ($keyword): ?>for "<em><?= htmlspecialchars(substr($keyword,0,50)) ?></em>"<?php endif; ?></h3>

            <?php if (count($results) === 0): ?>
                <p class="no-results">No students found matching your search.</p>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Major</th>
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
            <summary>ℹ️ Search Tips</summary>
            <ul>
                <li>Try searching by major: <code>Software Engineering</code></li>
                <li>Try partial name: <code>Nguyen</code></li>
                <li>Advanced operators may be supported for special queries.</li>
            </ul>
        </details>
    </div>
</div>
</body>
</html>
