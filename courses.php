<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php');
    exit;
}

$conn     = getDbConnection();
$userId   = $_SESSION['user_id'];
$role     = $_SESSION['role'];
$msg      = '';
$msgType  = 'info';

// Get student info if role is student
$student = null;
if ($role === 'student') {
    $stmt = oci_parse($conn, "SELECT student_id, full_name, major, credits FROM STUDENTS WHERE user_id = " . $userId);
    oci_execute($stmt);
    $student = oci_fetch_assoc($stmt);
}

// Handle Course Registration (Enroll / Drop)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $student) {
    $action   = $_POST['action'] ?? '';
    $sid      = (int)$student['STUDENT_ID'];
    $courseId = (int)($_POST['course_id'] ?? 0);

    if ($action === 'enroll' && $courseId > 0) {
        // Check if already enrolled
        $chk = oci_parse($conn, "SELECT COUNT(*) AS cnt FROM ENROLLMENTS WHERE student_id = :sid AND course_id = :cid");
        oci_bind_by_name($chk, ':sid', $sid);
        oci_bind_by_name($chk, ':cid', $courseId);
        oci_execute($chk);
        $chkRow = oci_fetch_assoc($chk);

        if ((int)($chkRow['CNT'] ?? 0) > 0) {
            $msg     = 'You are already registered for this course.';
            $msgType = 'warning';
        } else {
            // Get course semester
            $cstmt = oci_parse($conn, "SELECT semester, course_code FROM COURSES WHERE course_id = :cid");
            oci_bind_by_name($cstmt, ':cid', $courseId);
            oci_execute($cstmt);
            $cRow = oci_fetch_assoc($cstmt);

            if ($cRow) {
                $sem = $cRow['SEMESTER'] ?? '2024-S1';
                $code = $cRow['COURSE_CODE'];
                $transcriptRef = sprintf('TXN-%03d-%s-C%d', $sid, date('Y'), $courseId);

                $ins = oci_parse($conn, "INSERT INTO ENROLLMENTS (student_id, course_id, score, semester, transcript_ref, internal_note)
                                         VALUES (:sid, :cid, NULL, :sem, :tref, 'Self-enrolled via Student Portal')");
                oci_bind_by_name($ins, ':sid',  $sid);
                oci_bind_by_name($ins, ':cid',  $courseId);
                oci_bind_by_name($ins, ':sem',  $sem);
                oci_bind_by_name($ins, ':tref', $transcriptRef);

                if (@oci_execute($ins)) {
                    $msg     = "Successfully registered for course $code.";
                    $msgType = 'success';
                    logAction($userId, 'COURSE_ENROLL', "Enrolled in $code (ID: $courseId)");
                } else {
                    $e = oci_error($ins);
                    $msg     = 'Registration failed: ' . htmlspecialchars($e['message']);
                    $msgType = 'danger';
                }
            }
        }
    } elseif ($action === 'drop' && !empty($_POST['enrollment_id'])) {
        $eid = (int)$_POST['enrollment_id'];

        // Only allow dropping if score is null
        $chk = oci_parse($conn, "SELECT score, course_id FROM ENROLLMENTS WHERE enrollment_id = :eid AND student_id = :sid");
        oci_bind_by_name($chk, ':eid', $eid);
        oci_bind_by_name($chk, ':sid', $sid);
        oci_execute($chk);
        $enRow = oci_fetch_assoc($chk);

        if ($enRow && $enRow['SCORE'] === null) {
            $del = oci_parse($conn, "DELETE FROM ENROLLMENTS WHERE enrollment_id = :eid AND student_id = :sid");
            oci_bind_by_name($del, ':eid', $eid);
            oci_bind_by_name($del, ':sid', $sid);
            if (oci_execute($del)) {
                $msg     = 'Course registration dropped successfully.';
                $msgType = 'warning';
                logAction($userId, 'COURSE_DROP', "Dropped enrollment ID: $eid");
            } else {
                $msg     = 'Failed to drop course.';
                $msgType = 'danger';
            }
        } else {
            $msg     = 'Cannot drop a course that has already been graded.';
            $msgType = 'danger';
        }
    }
}

// Fetch all courses
$courses = [];
$qCourses = oci_parse($conn, "SELECT course_id, course_code, course_name, teacher_name, credits, semester FROM COURSES ORDER BY course_code");
oci_execute($qCourses);
while ($r = oci_fetch_assoc($qCourses)) {
    $courses[] = $r;
}

// Fetch enrolled course IDs and details for current student
$enrolledMap = [];
$totalEnrolledCredits = 0;
if ($student) {
    $sid = (int)$student['STUDENT_ID'];
    $qEnrolled = oci_parse($conn, "SELECT e.enrollment_id, e.course_id, e.score, c.credits
                                   FROM ENROLLMENTS e
                                   JOIN COURSES c ON e.course_id = c.course_id
                                   WHERE e.student_id = :sid");
    oci_bind_by_name($qEnrolled, ':sid', $sid);
    oci_execute($qEnrolled);
    while ($r = oci_fetch_assoc($qEnrolled)) {
        $enrolledMap[$r['COURSE_ID']] = $r;
        $totalEnrolledCredits += (int)$r['CREDITS'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Registration – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>📖 Course Catalog & Registration</h2>
    <p class="text-muted">Explore academic courses, check prerequisites, and manage your semester enrollments.</p>

    <?php if ($msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msgType) ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($student): ?>
    <div class="info-card" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <strong>Student:</strong> <?= htmlspecialchars($student['FULL_NAME']) ?> (<?= htmlspecialchars($student['MAJOR']) ?>)
        </div>
        <div>
            Registered Credits: <span class="badge badge-student"><?= $totalEnrolledCredits ?> / 24 Credits Max</span>
        </div>
    </div>
    <?php endif; ?>

    <div class="info-card">
        <h3>Available Academic Courses</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Course Name</th>
                    <th>Instructor</th>
                    <th>Credits</th>
                    <th>Semester</th>
                    <?php if ($role === 'student'): ?>
                        <th style="text-align:center;">Action / Status</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($courses as $c): ?>
                <?php
                $cid = (int)$c['COURSE_ID'];
                $isEnrolled = isset($enrolledMap[$cid]);
                $enrollment = $isEnrolled ? $enrolledMap[$cid] : null;
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($c['COURSE_CODE']) ?></strong></td>
                    <td><?= htmlspecialchars($c['COURSE_NAME']) ?></td>
                    <td><?= htmlspecialchars($c['TEACHER_NAME'] ?? 'TBA') ?></td>
                    <td><span class="badge-inline badge-teacher"><?= (int)$c['CREDITS'] ?> cr</span></td>
                    <td><?= htmlspecialchars($c['SEMESTER'] ?? 'N/A') ?></td>
                    <?php if ($role === 'student'): ?>
                    <td style="text-align:center;">
                        <?php if ($isEnrolled): ?>
                            <span class="badge badge-student" style="margin-right:6px;">Enrolled</span>
                            <?php if ($enrollment['SCORE'] === null): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to drop this course?');">
                                    <input type="hidden" name="action" value="drop">
                                    <input type="hidden" name="enrollment_id" value="<?= (int)$enrollment['ENROLLMENT_ID'] ?>">
                                    <button type="submit" class="btn btn-warning" style="padding:3px 8px;font-size:0.75rem;">Drop</button>
                                </form>
                            <?php else: ?>
                                <small class="text-muted">(Graded)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="enroll">
                                <input type="hidden" name="course_id" value="<?= $cid ?>">
                                <button type="submit" class="btn btn-primary" style="padding:4px 12px;font-size:0.8rem;">+ Enroll</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="hint-box">
        <details>
            <summary>ℹ️ Academic Registration Regulations</summary>
            <ul>
                <li>Maximum registration limit is 24 credits per semester.</li>
                <li>Courses can only be dropped before midterm grading is completed.</li>
                <li>For prerequisite overrides, contact the Academic Affairs Department.</li>
            </ul>
        </details>
    </div>
</div>
</body>
</html>
