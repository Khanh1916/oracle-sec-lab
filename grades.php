<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php');
    exit;
}

$conn     = getDbConnection();
$userId   = $_SESSION['user_id'];
$role     = $_SESSION['role'];
$username = $_SESSION['username'];
$msg      = '';
$msgType  = 'info';

// If student visits this page, redirect to transcript
if ($role === 'student') {
    header('Location: ' . APP_BASE . '/transcript.php');
    exit;
}

// Fetch all courses for the dropdown
$courses = [];
$cStmt = oci_parse($conn, "SELECT course_id, course_code, course_name, teacher_name, semester FROM COURSES ORDER BY course_code");
oci_execute($cStmt);
while ($r = oci_fetch_assoc($cStmt)) {
    $courses[] = $r;
}

$selectedCourseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : ($courses[0]['COURSE_ID'] ?? 0);

// Handle Grade Update (Teacher / Admin submitting score)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
    $scores = $_POST['scores'] ?? [];
    $updatedCount = 0;

    foreach ($scores as $enrollmentId => $scoreVal) {
        $eid = (int)$enrollmentId;
        $scoreVal = trim($scoreVal);

        if ($scoreVal === '') {
            $upd = oci_parse($conn, "UPDATE ENROLLMENTS SET score = NULL WHERE enrollment_id = :eid");
            oci_bind_by_name($upd, ':eid', $eid);
            if (oci_execute($upd)) $updatedCount++;
        } else {
            $numScore = (float)$scoreVal;
            if ($numScore >= 0.0 && $numScore <= 10.0) {
                $upd = oci_parse($conn, "UPDATE ENROLLMENTS SET score = :sc WHERE enrollment_id = :eid");
                oci_bind_by_name($upd, ':sc',  $numScore);
                oci_bind_by_name($upd, ':eid', $eid);
                if (oci_execute($upd)) $updatedCount++;
            }
        }
    }

    if ($updatedCount > 0) {
        $msg     = tf('gr.msg_ok_fmt', $updatedCount);
        $msgType = 'success';
        logAction($userId, 'TEACHER_GRADE_UPDATE', "Updated grades for course ID $selectedCourseId");
    }
}

// Fetch students enrolled in the selected course
$enrolledStudents = [];
if ($selectedCourseId > 0) {
    $sql = "SELECT e.enrollment_id, e.score, e.transcript_ref, e.semester,
                   s.student_id, s.full_name, s.email, s.major, u.username
            FROM ENROLLMENTS e
            JOIN STUDENTS s ON e.student_id = s.student_id
            JOIN USERS    u ON s.user_id    = u.user_id
            WHERE e.course_id = :cid
            ORDER BY s.student_id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':cid', $selectedCourseId);
    oci_execute($stmt);
    while ($r = oci_fetch_assoc($stmt)) {
        $enrolledStudents[] = $r;
    }
}

// Current course info
$currentCourse = null;
foreach ($courses as $c) {
    if ((int)$c['COURSE_ID'] === $selectedCourseId) {
        $currentCourse = $c;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('gr.title') ?> – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2><i class="ri-file-list-3-line"></i> <?= t('gr.heading') ?></h2>
    <p class="text-muted"><?= t('gr.intro') ?></p>

    <?php if ($msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msgType) ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <div class="info-card">
        <form method="GET" action="" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <label for="course_select" style="font-weight:600; color:var(--secondary);"><?= t('gr.select_course') ?></label>
            <select id="course_select" name="course_id" style="padding:8px 12px; border-radius:var(--radius); border:1px solid var(--border); font-size:0.95rem; min-width:320px;">
                <?php foreach ($courses as $c): ?>
                    <option value="<?= (int)$c['COURSE_ID'] ?>" <?= (int)$c['COURSE_ID'] === $selectedCourseId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['COURSE_CODE']) ?> – <?= htmlspecialchars($c['COURSE_NAME']) ?> (<?= htmlspecialchars($c['TEACHER_NAME'] ?? 'TBA') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary" style="padding:7px 16px;"><?= t('gr.load') ?></button>
        </form>
    </div>

    <?php if ($currentCourse): ?>
    <div class="info-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap;">
            <div>
                <h3><?= tf('gr.roster_fmt', htmlspecialchars($currentCourse['COURSE_CODE']), htmlspecialchars($currentCourse['COURSE_NAME'])) ?></h3>
                <small class="text-muted"><?= tf('gr.sem_instr_fmt', htmlspecialchars($currentCourse['SEMESTER'] ?? '2024-S1'), htmlspecialchars($currentCourse['TEACHER_NAME'] ?? 'TBA')) ?></small>
            </div>
            <div>
                <span class="badge badge-student"><?= tf('gr.students_fmt', count($enrolledStudents)) ?></span>
            </div>
        </div>

        <?php if (count($enrolledStudents) === 0): ?>
            <p class="no-results"><?= t('gr.none') ?></p>
        <?php else: ?>
            <form method="POST" action="">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?= t('db.f_sid') ?></th>
                            <th><?= t('db.f_fullname') ?></th>
                            <th><?= t('db.f_major') ?></th>
                            <th><?= t('gr.h_username') ?></th>
                            <th><?= t('gr.h_tref') ?></th>
                            <th style="width:130px; text-align:center;"><?= t('gr.h_score') ?></th>
                            <th style="text-align:center;"><?= t('gr.h_status') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($enrolledStudents as $s): ?>
                        <?php
                        $score = $s['SCORE'] !== null ? number_format((float)$s['SCORE'], 1) : '';
                        $numScore = $s['SCORE'] !== null ? (float)$s['SCORE'] : null;
                        $statusBadge = '<span class="badge-inline" style="background:#e9ecef;color:#6c757d;">' . t('gr.pending') . '</span>';
                        if ($numScore !== null) {
                            if ($numScore >= 5.0) {
                                $statusBadge = '<span class="badge badge-student">' . t('gr.passed') . '</span>';
                            } else {
                                $statusBadge = '<span class="badge badge-admin">' . t('gr.failed') . '</span>';
                            }
                        }
                        ?>
                        <tr>
                            <td><strong>SID-<?= str_pad((string)$s['STUDENT_ID'], 3, '0', STR_PAD_LEFT) ?></strong></td>
                            <td><?= htmlspecialchars($s['FULL_NAME']) ?></td>
                            <td><?= htmlspecialchars($s['MAJOR']) ?></td>
                            <td><code><?= htmlspecialchars($s['USERNAME']) ?></code></td>
                            <td><small><?= htmlspecialchars($s['TRANSCRIPT_REF']) ?></small></td>
                            <td style="text-align:center;">
                                <input type="number" step="0.1" min="0" max="10"
                                       name="scores[<?= (int)$s['ENROLLMENT_ID'] ?>]"
                                       value="<?= htmlspecialchars($score) ?>"
                                       placeholder="N/A"
                                       style="width:85px; padding:5px 8px; text-align:center; border:1px solid var(--border); border-radius:4px; font-weight:bold;">
                            </td>
                            <td style="text-align:center;"><?= $statusBadge ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div style="margin-top:20px; display:flex; justify-content:flex-end;">
                    <button type="submit" name="save_grades" class="btn btn-primary" style="padding:10px 24px;">
                        <i class="ri-save-line"></i> <?= t('gr.save') ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="hint-box">
        <details>
            <summary><i class="ri-information-line"></i> <?= t('gr.guidelines') ?></summary>
            <ul>
                <li><?= t('gr.g1') ?></li>
                <li><?= t('gr.g2') ?></li>
                <li><?= t('gr.g3') ?></li>
            </ul>
        </details>
    </div>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
