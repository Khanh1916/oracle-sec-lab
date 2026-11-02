<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location: ' . APP_BASE . '/login.php'); exit; }

$conn     = getDbConnection();
$userId   = (int)$_SESSION['user_id'];
$role     = $_SESSION['role'];
$username = $_SESSION['username'];

// Resolve the student record (students see only exams for courses they take).
$student = null;
if ($role === 'student') {
    $q = oci_parse($conn, "SELECT student_id, full_name FROM STUDENTS WHERE user_id = :b_uid");
    oci_bind_by_name($q, ':b_uid', $userId);
    oci_execute($q);
    $student = oci_fetch_assoc($q);
}

// Load the exam schedule.
$exams = [];
$examSql = "SELECT x.exam_id, c.course_code, c.course_name,
                   x.exam_type, x.exam_form, x.room, x.start_time, x.duration_min,
                   TO_CHAR(x.exam_date, 'YYYY-MM-DD') AS exam_day,
                   TO_CHAR(x.exam_date, 'Dy')          AS exam_dow,
                   CASE WHEN x.exam_date >= TRUNC(SYSDATE) THEN 1 ELSE 0 END AS is_upcoming
            FROM EXAMS x
            JOIN COURSES c ON c.course_id = x.course_id";

if ($role === 'student' && $student) {
    $examSql .= " WHERE x.course_id IN (
                      SELECT e.course_id FROM ENROLLMENTS e WHERE e.student_id = :b_sid)
                  ORDER BY x.exam_date, x.start_time";
    $stmt = oci_parse($conn, $examSql);
    oci_bind_by_name($stmt, ':b_sid', $student['STUDENT_ID']);
} else {
    $examSql .= " ORDER BY x.exam_date, x.start_time";
    $stmt = oci_parse($conn, $examSql);
}
oci_execute($stmt);
while ($r = oci_fetch_assoc($stmt)) { $exams[] = $r; }

// Summary figures.
$upcoming = array_values(array_filter($exams, fn($e) => (int)$e['IS_UPCOMING'] === 1));
$nextExam = $upcoming[0] ?? null;

// Deterministic seat label (cosmetic) from the student + exam ids.
function examSeat(?int $sid, int $examId): string {
    if (!$sid) return '—';
    return 'S' . str_pad((string)((($sid * 7 + $examId * 3) % 40) + 1), 2, '0', STR_PAD_LEFT);
}
$sid = $student ? (int)$student['STUDENT_ID'] : null;

// Translate a stored data value (exam type / form) if a mapping exists.
function tval(string $prefix, string $value): string {
    $k = $prefix . $value;
    $r = t($k);
    return $r === $k ? $value : $r;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('ex.title') ?> – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>📝 <?= t('ex.title') ?></h2>
    <p class="text-muted">
        <?= t('sem.label') ?> <?= t('sem.value') ?> &bull;
        <?php if ($role === 'student'): ?>
            <?= tf('ex.sub_student_fmt', '<strong>' . htmlspecialchars($student['FULL_NAME'] ?? $username) . '</strong>') ?>
        <?php else: ?>
            <?= t('ex.sub_staff') ?>
        <?php endif; ?>
    </p>

    <!-- Summary KPIs -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-icon">🗓️</span>
            <span class="kpi-value"><?= count($exams) ?></span>
            <span class="kpi-label"><?= t('ex.kpi_scheduled') ?></span>
            <span class="kpi-sub"><?= t('ex.kpi_scheduled_sub') ?></span>
        </div>
        <div class="kpi-card">
            <span class="kpi-icon">⏳</span>
            <span class="kpi-value"><?= count($upcoming) ?></span>
            <span class="kpi-label"><?= t('ex.kpi_upcoming') ?></span>
            <span class="kpi-sub"><?= t('ex.kpi_upcoming_sub') ?></span>
        </div>
        <div class="kpi-card">
            <span class="kpi-icon">⭐</span>
            <span class="kpi-value" style="font-size:1.1rem;">
                <?= $nextExam ? htmlspecialchars($nextExam['COURSE_CODE']) : '—' ?>
            </span>
            <span class="kpi-label"><?= t('ex.kpi_next') ?></span>
            <span class="kpi-sub"><?= $nextExam ? htmlspecialchars($nextExam['EXAM_DAY'] . ' · ' . $nextExam['START_TIME']) : t('ex.kpi_next_none') ?></span>
        </div>
    </div>

    <div class="info-card" style="padding:0; overflow:hidden;">
        <?php if (!$exams): ?>
            <p class="no-results" style="padding:24px;"><?= t('ex.none') ?></p>
        <?php else: ?>
        <table class="data-table exam-table">
            <thead>
                <tr>
                    <th><?= t('ex.col_date') ?></th>
                    <th><?= t('ex.col_time') ?></th>
                    <th><?= t('ex.col_subject') ?></th>
                    <th><?= t('ex.col_type') ?></th>
                    <th><?= t('ex.col_form') ?></th>
                    <th><?= t('ex.col_room') ?></th>
                    <?php if ($role === 'student'): ?><th><?= t('ex.col_seat') ?></th><?php endif; ?>
                    <th><?= t('ex.col_status') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exams as $e):
                    $isUp = (int)$e['IS_UPCOMING'] === 1;
                ?>
                <tr class="<?= $isUp ? 'exam-upcoming' : 'exam-done' ?>">
                    <td>
                        <strong><?= htmlspecialchars($e['EXAM_DAY']) ?></strong>
                        <div class="exam-dow"><?= htmlspecialchars(dowLabel($e['EXAM_DAY'])) ?></div>
                    </td>
                    <td>
                        <?= htmlspecialchars($e['START_TIME']) ?>
                        <div class="exam-dow"><?= (int)$e['DURATION_MIN'] ?> <?= t('ex.min') ?></div>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($e['COURSE_CODE']) ?></strong>
                        <div class="exam-sub"><?= htmlspecialchars($e['COURSE_NAME']) ?></div>
                    </td>
                    <td><?= htmlspecialchars(tval('ex.type.', $e['EXAM_TYPE'])) ?></td>
                    <td><span class="exam-form"><?= htmlspecialchars(tval('ex.form.', $e['EXAM_FORM'])) ?></span></td>
                    <td>📍 <?= htmlspecialchars($e['ROOM']) ?></td>
                    <?php if ($role === 'student'): ?>
                        <td><code><?= htmlspecialchars(examSeat($sid, (int)$e['EXAM_ID'])) ?></code></td>
                    <?php endif; ?>
                    <td>
                        <?php if ($isUp): ?>
                            <span class="badge exam-badge-up"><?= t('ex.status_upcoming') ?></span>
                        <?php else: ?>
                            <span class="badge exam-badge-done"><?= t('ex.status_completed') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="hint-box">
        <details>
            <summary>ℹ️ <?= t('ex.regs') ?></summary>
            <ul>
                <li><?= t('ex.reg1') ?></li>
                <li><?= t('ex.reg2') ?></li>
                <li><?= t('ex.reg3') ?></li>
                <li><?= t('ex.reg4') ?></li>
            </ul>
        </details>
    </div>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
