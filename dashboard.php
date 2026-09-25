<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php');
    exit;
}
$role     = $_SESSION['role'];
$username = $_SESSION['username'];
$userId   = $_SESSION['user_id'];

$conn = getDbConnection();

// Fetch student record if role = student
$studentRow = null;
if ($role === 'student') {
    $sql  = "SELECT s.* FROM STUDENTS s JOIN USERS u ON s.user_id = u.user_id WHERE u.user_id = " . $userId;
    $stmt = oci_parse($conn, $sql);
    oci_execute($stmt);
    $studentRow = oci_fetch_assoc($stmt);
}

// Fetch enrollments
$enrollments = [];
$totalCredits = 0;
if ($studentRow) {
    $sid  = $studentRow['STUDENT_ID'];
    $sql  = "SELECT e.enrollment_id, e.transcript_ref, e.semester, e.score, c.course_name, c.course_code, c.credits, c.teacher_name
             FROM ENROLLMENTS e JOIN COURSES c ON e.course_id = c.course_id
             WHERE e.student_id = :sid ORDER BY e.enrollment_id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':sid', $sid);
    oci_execute($stmt);
    while ($r = oci_fetch_assoc($stmt)) {
        $enrollments[] = $r;
        $totalCredits += (int)$r['CREDITS'];
    }
}

// Fetch teacher courses if teacher
$teacherCourses = [];
if ($role === 'teacher') {
    $stmt = oci_parse($conn, "SELECT course_id, course_code, course_name, credits, semester, teacher_name FROM COURSES ORDER BY course_code");
    oci_execute($stmt);
    while ($r = oci_fetch_assoc($stmt)) {
        $teacherCourses[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
    <style>
        .portal-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .portal-btn {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 16px;
            text-align: center;
            text-decoration: none;
            color: var(--text);
            box-shadow: var(--shadow);
            transition: transform .15s, border-color .15s, box-shadow .15s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .portal-btn:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
            box-shadow: 0 4px 16px rgba(232,93,4,0.15);
            text-decoration: none;
        }
        .portal-btn .icon { font-size: 1.8rem; }
        .portal-btn .label { font-weight: 700; font-size: 0.95rem; color: var(--secondary); }
        .portal-btn .sub { font-size: 0.78rem; color: var(--muted); }
        .news-item { padding: 12px 0; border-bottom: 1px dashed var(--border); }
        .news-item:last-child { border-bottom: none; }
        .news-tag { font-size: 0.72rem; padding: 2px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; margin-right: 6px; }
        .tag-notice { background: #e0f2fe; color: #0369a1; }
        .tag-exam   { background: #fef3c7; color: #b45309; }
        .tag-system { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px;">
        <h2>Welcome back, <?= htmlspecialchars($studentRow['FULL_NAME'] ?? $username) ?>
            <span class="badge badge-<?= $role ?>"><?= strtoupper($role) ?></span>
        </h2>
        <span class="text-muted" style="font-size:0.85rem;">Semester: <strong>Summer 2024 (2024-S1)</strong></span>
    </div>

    <!-- Quick Academic Services Grid -->
    <div class="portal-grid">
        <?php if ($role === 'student'): ?>
            <a href="<?= APP_BASE ?>/courses.php" class="portal-btn">
                <span class="icon">📖</span>
                <span class="label">Course Catalog</span>
                <span class="sub">Register & Drop Courses</span>
            </a>
            <a href="<?= APP_BASE ?>/schedule.php" class="portal-btn">
                <span class="icon">📅</span>
                <span class="label">Class Timetable</span>
                <span class="sub">Weekly Lecture Schedule</span>
            </a>
            <a href="<?= APP_BASE ?>/transcript.php" class="portal-btn">
                <span class="icon">📄</span>
                <span class="label">Grade Transcript</span>
                <span class="sub">View Exam Scores & GPA</span>
            </a>
            <a href="<?= APP_BASE ?>/tuition.php" class="portal-btn">
                <span class="icon">💳</span>
                <span class="label">Tuition Fees</span>
                <span class="sub">Billing & Financial Status</span>
            </a>
            <a href="<?= APP_BASE ?>/store.php" class="portal-btn">
                <span class="icon">📚</span>
                <span class="label">Material Store</span>
                <span class="sub">Buy Guides & Materials</span>
            </a>
            <a href="<?= APP_BASE ?>/search.php" class="portal-btn">
                <span class="icon">🔍</span>
                <span class="label">Directory Search</span>
                <span class="sub">Lookup Peers & Majors</span>
            </a>
        <?php elseif ($role === 'teacher'): ?>
            <a href="<?= APP_BASE ?>/grades.php" class="portal-btn">
                <span class="icon">📝</span>
                <span class="label">Faculty Grading</span>
                <span class="sub">Score Class Rosters</span>
            </a>
            <a href="<?= APP_BASE ?>/courses.php" class="portal-btn">
                <span class="icon">📖</span>
                <span class="label">Course Catalog</span>
                <span class="sub">View All Departments</span>
            </a>
            <a href="<?= APP_BASE ?>/schedule.php" class="portal-btn">
                <span class="icon">📅</span>
                <span class="label">Teaching Schedule</span>
                <span class="sub">Assigned Lecture Slots</span>
            </a>
            <a href="<?= APP_BASE ?>/search.php" class="portal-btn">
                <span class="icon">🔍</span>
                <span class="label">Student Directory</span>
                <span class="sub">Search Student Records</span>
            </a>
        <?php elseif ($role === 'admin'): ?>
            <a href="<?= APP_BASE ?>/admin.php" class="portal-btn">
                <span class="icon">⚙️</span>
                <span class="label">Admin Panel</span>
                <span class="sub">User Management & Updates</span>
            </a>
            <a href="<?= APP_BASE ?>/audit.php" class="portal-btn">
                <span class="icon">📋</span>
                <span class="label">Audit Logs</span>
                <span class="sub">Security & Action Trail</span>
            </a>
            <a href="<?= APP_BASE ?>/courses.php" class="portal-btn">
                <span class="icon">📖</span>
                <span class="label">Curriculum</span>
                <span class="sub">Course Management</span>
            </a>
            <a href="<?= APP_BASE ?>/search.php" class="portal-btn">
                <span class="icon">🔍</span>
                <span class="label">Student Search</span>
                <span class="sub">Student Directory</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Student Information Overview -->
    <?php if ($studentRow): ?>
    <div class="info-card">
        <h3>📋 Student Academic Profile</h3>
        <table class="info-table">
            <tr><th>Full Name</th><td><strong><?= htmlspecialchars($studentRow['FULL_NAME']) ?></strong></td></tr>
            <tr><th>Student ID</th><td><code>SID-<?= str_pad((string)$studentRow['STUDENT_ID'], 3, '0', STR_PAD_LEFT) ?></code></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($studentRow['EMAIL']) ?></td></tr>
            <tr><th>Academic Major</th><td><?= htmlspecialchars($studentRow['MAJOR']) ?></td></tr>
            <tr><th>Cumulative GPA</th><td><span class="badge badge-student"><?= number_format((float)$studentRow['GPA'], 2) ?> / 4.00</span></td></tr>
            <tr><th>Course Material Credits</th><td><strong><?= number_format((int)$studentRow['CREDITS']) ?> credits</strong> (<a href="<?= APP_BASE ?>/store.php">Visit Store</a>)</td></tr>
        </table>
    </div>

    <div class="info-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3>📚 Active Semester Enrollments</h3>
            <a href="<?= APP_BASE ?>/courses.php" class="btn btn-primary" style="padding:4px 12px; font-size:0.8rem;">+ Add / Drop Course</a>
        </div>
        <?php if ($enrollments): ?>
        <table class="data-table">
            <thead><tr>
                <th>Code</th><th>Course Name</th><th>Credits</th><th>Instructor</th><th>Semester</th><th>Score</th><th>Transcript</th>
            </tr></thead>
            <tbody>
            <?php foreach ($enrollments as $e): ?>
            <tr>
                <td><strong><?= htmlspecialchars($e['COURSE_CODE']) ?></strong></td>
                <td><?= htmlspecialchars($e['COURSE_NAME']) ?></td>
                <td><?= (int)$e['CREDITS'] ?> cr</td>
                <td><small><?= htmlspecialchars($e['TEACHER_NAME'] ?? 'TBA') ?></small></td>
                <td><?= htmlspecialchars($e['SEMESTER']) ?></td>
                <td>
                    <?php if ($e['SCORE'] !== null): ?>
                        <strong><?= number_format((float)$e['SCORE'], 1) ?></strong>
                    <?php else: ?>
                        <span class="text-muted">In Progress</span>
                    <?php endif; ?>
                </td>
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
        <p class="no-results">No course enrollments found for the current semester. <a href="<?= APP_BASE ?>/courses.php">Register now &rarr;</a></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Teacher Courses Overview -->
    <?php if ($role === 'teacher'): ?>
    <div class="info-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3>👨‍🏫 Assigned Academic Courses</h3>
            <a href="<?= APP_BASE ?>/grades.php" class="btn btn-primary" style="padding:4px 12px; font-size:0.8rem;">Go to Grading &rarr;</a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th><th>Course Title</th><th>Credits</th><th>Semester</th><th>Lead Instructor</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teacherCourses as $tc): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($tc['COURSE_CODE']) ?></strong></td>
                    <td><?= htmlspecialchars($tc['COURSE_NAME']) ?></td>
                    <td><?= (int)$tc['CREDITS'] ?> cr</td>
                    <td><?= htmlspecialchars($tc['SEMESTER'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($tc['TEACHER_NAME'] ?? 'TBA') ?></td>
                    <td>
                        <a href="<?= APP_BASE ?>/grades.php?course_id=<?= (int)$tc['COURSE_ID'] ?>" class="btn btn-secondary" style="padding:3px 10px; font-size:0.75rem;">
                            Grade Roster
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Campus Bulletin / Notice Board -->
    <div class="info-card">
        <h3>📢 Campus Announcements & Academic Notices</h3>
        <div class="news-item">
            <span class="news-tag tag-notice">Notice</span>
            <strong>Final Defense Schedule for DBS401 (Database Security) Released</strong>
            <p style="font-size:0.85rem; color:var(--muted); margin-top:4px;">
                All project groups must submit their final documentation, demonstration environment, and slide deck by Friday. Presentations will take place in Building Alpha Room AL-204.
            </p>
        </div>
        <div class="news-item">
            <span class="news-tag tag-exam">Academic</span>
            <strong>Semester Summer 2024 Course Add/Drop Period Extension</strong>
            <p style="font-size:0.85rem; color:var(--muted); margin-top:4px;">
                The Office of Academic Affairs has extended the course registration window by 48 hours. Students can adjust course enrollments directly via the Course Catalog module.
            </p>
        </div>
        <div class="news-item">
            <span class="news-tag tag-system">IT System</span>
            <strong>Oracle XE 21c Database Maintenance Window</strong>
            <p style="font-size:0.85rem; color:var(--muted); margin-top:4px;">
                Routine database backup and index optimization will occur this Sunday from 02:00 AM to 04:00 AM. Portal services may experience brief latency during this interval.
            </p>
        </div>
    </div>
</div>
</body>
</html>
