<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php'); exit;
}
$role     = $_SESSION['role'];
$username = $_SESSION['username'];
$userId   = $_SESSION['user_id'];

$conn = getDbConnection();

// Fetch student record if role = student
$studentRow = null;
if ($role === 'student') {
    $sql  = "SELECT s.* FROM STUDENTS s JOIN USERS u ON s.user_id = u.user_id WHERE u.user_id = :uid";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':uid', $userId);
    oci_execute($stmt);
    $studentRow = oci_fetch_assoc($stmt);
}

// Fetch recent enrollments
$enrollments = [];
if ($studentRow) {
    $sid  = $studentRow['STUDENT_ID'];
    $sql  = "SELECT e.transcript_ref, e.semester, e.score, c.course_name
             FROM ENROLLMENTS e JOIN COURSES c ON e.course_id = c.course_id
             WHERE e.student_id = :sid ORDER BY e.enrollment_id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':sid', $sid);
    oci_execute($stmt);
    while ($r = oci_fetch_assoc($stmt)) $enrollments[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>Welcome, <?= htmlspecialchars($username) ?> 
        <span class="badge badge-<?= $role ?>"><?= strtoupper($role) ?></span>
    </h2>

    <?php if ($studentRow): ?>
    <div class="info-card">
        <h3>📋 Student Information</h3>
        <table class="info-table">
            <tr><th>Full Name</th><td><?= htmlspecialchars($studentRow['FULL_NAME']) ?></td></tr>
            <tr><th>Email</th>    <td><?= htmlspecialchars($studentRow['EMAIL']) ?></td></tr>
            <tr><th>Major</th>    <td><?= htmlspecialchars($studentRow['MAJOR']) ?></td></tr>
            <tr><th>GPA</th>      <td><?= htmlspecialchars($studentRow['GPA']) ?></td></tr>
        </table>
    </div>

    <div class="info-card">
        <h3>📚 My Enrollments</h3>
        <?php if ($enrollments): ?>
        <table class="data-table">
            <thead><tr>
                <th>Course</th><th>Semester</th><th>Score</th><th>Transcript</th>
            </tr></thead>
            <tbody>
            <?php foreach ($enrollments as $e): ?>
            <tr>
                <td><?= htmlspecialchars($e['COURSE_NAME']) ?></td>
                <td><?= htmlspecialchars($e['SEMESTER']) ?></td>
                <td><?= htmlspecialchars($e['SCORE'] ?? 'N/A') ?></td>
                <td>
                    <a href="<?= APP_BASE ?>/transcript.php?ref=<?= urlencode($e['TRANSCRIPT_REF']) ?>">
                        <?= htmlspecialchars($e['TRANSCRIPT_REF']) ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>No enrollments found.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
    <div class="info-card">
        <h3>⚙️ Admin Actions</h3>
        <a href="<?= APP_BASE ?>/admin.php" class="btn btn-warning">Admin Panel</a>
        <a href="<?= APP_BASE ?>/audit.php" class="btn btn-secondary">View Audit Logs</a>
    </div>
    <?php endif; ?>

    <?php if ($role === 'teacher'): ?>
    <div class="info-card">
        <h3>📊 Teacher Actions</h3>
        <a href="<?= APP_BASE ?>/search.php" class="btn btn-primary">Search Students</a>
    </div>
    <?php endif; ?>

    <div class="info-card">
        <h3>🔍 Student Search</h3>
        <p>Search for students by name or major.</p>
        <a href="<?= APP_BASE ?>/search.php" class="btn btn-primary">Go to Search</a>
    </div>
</div>
</body>
</html>
