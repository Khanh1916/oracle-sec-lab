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

$student = null;
$enrolledCourses = [];

if ($role === 'student') {
    $stmt = oci_parse($conn, "SELECT student_id, full_name, major FROM STUDENTS WHERE user_id = " . $userId);
    oci_execute($stmt);
    $student = oci_fetch_assoc($stmt);

    if ($student) {
        $sid = (int)$student['STUDENT_ID'];
        $q = oci_parse($conn, "SELECT c.course_id, c.course_code, c.course_name, c.teacher_name, c.credits, e.semester
                               FROM ENROLLMENTS e
                               JOIN COURSES c ON e.course_id = c.course_id
                               WHERE e.student_id = :sid
                               ORDER BY c.course_code");
        oci_bind_by_name($q, ':sid', $sid);
        oci_execute($q);
        while ($r = oci_fetch_assoc($q)) {
            $enrolledCourses[] = $r;
        }
    }
} else {
    // For teacher or admin: fetch relevant courses
    $q = oci_parse($conn, "SELECT course_id, course_code, course_name, teacher_name, credits, semester FROM COURSES ORDER BY course_code");
    oci_execute($q);
    while ($r = oci_fetch_assoc($q)) {
        $enrolledCourses[] = $r;
    }
}

// Fixed slot definitions common in FPT University
$slots = [
    1 => ['name' => 'Slot 1', 'time' => '07:30 – 09:50'],
    2 => ['name' => 'Slot 2', 'time' => '10:00 – 12:20'],
    3 => ['name' => 'Slot 3', 'time' => '12:50 – 15:10'],
    4 => ['name' => 'Slot 4', 'time' => '15:20 – 17:40'],
];

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Simulated deterministic classroom and schedule slot assignment based on course code
$rooms = ['BE-302', 'AL-204', 'DE-101', 'NV-205', 'LAB-401', 'CS-202'];

// Map courses to timetable slots deterministically
$scheduleGrid = [];
foreach ($enrolledCourses as $idx => $course) {
    $cid = (int)$course['COURSE_ID'];
    $room = $rooms[$cid % count($rooms)];

    // Assign 2 class sessions per week per course (e.g. Mon/Thu or Tue/Fri)
    $day1 = $days[($cid * 2) % count($days)];
    $day2 = $days[(($cid * 2) + 3) % count($days)];
    $slotNum = (($cid + 1) % 4) + 1;

    $scheduleGrid[$day1][$slotNum] = [
        'code'    => $course['COURSE_CODE'],
        'name'    => $course['COURSE_NAME'],
        'room'    => $room,
        'teacher' => $course['TEACHER_NAME'] ?? 'TBA',
    ];

    $scheduleGrid[$day2][$slotNum] = [
        'code'    => $course['COURSE_CODE'],
        'name'    => $course['COURSE_NAME'],
        'room'    => $room,
        'teacher' => $course['TEACHER_NAME'] ?? 'TBA',
    ];
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('sch.title') ?> – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
    <style>
        .timetable { width: 100%; border-collapse: collapse; margin-top: 15px; table-layout: fixed; }
        .timetable th, .timetable td { border: 1px solid var(--border); padding: 10px; text-align: center; vertical-align: top; }
        .timetable th { background: #f3f0ff; color: var(--secondary); font-size: 0.88rem; }
        .slot-cell { background: #faf9ff; width: 110px; font-weight: 600; font-size: 0.8rem; }
        .slot-time { color: var(--muted); font-size: 0.72rem; font-weight: normal; margin-top: 2px; }
        .class-box {
            background: #ede9fe;
            border-left: 3px solid var(--secondary);
            border-radius: 4px;
            padding: 8px 6px;
            text-align: left;
            font-size: 0.8rem;
            line-height: 1.3;
        }
        .class-code { font-weight: 700; color: var(--secondary); display: block; }
        .class-room { color: var(--primary); font-weight: 600; font-size: 0.75rem; margin-top: 2px; display: block; }
        .class-teacher { color: var(--muted); font-size: 0.72rem; margin-top: 2px; display: block; }
    </style>
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2><i class="ri-calendar-schedule-line"></i> <?= t('sch.heading') ?></h2>
    <p class="text-muted">
        <?= t('sem.label') ?> <?= t('sem.value') ?> &bull;
        <?= tf('sch.subtitle_fmt', '<strong>' . ($student ? htmlspecialchars($student['FULL_NAME']) : htmlspecialchars($username)) . '</strong>') ?>
    </p>

    <div class="info-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px; flex-wrap:wrap; gap:8px;">
            <div style="font-weight:600; color:var(--secondary);">
                <?= t('sch.active_sem') ?>: <code>2024-S1 (Summer)</code>
            </div>
            <div>
                <span class="badge badge-student"><?= tf('sch.enrolled_fmt', count($enrolledCourses)) ?></span>
            </div>
        </div>

        <table class="timetable">
            <thead>
                <tr>
                    <th style="width:120px;"><?= t('sch.time_slot') ?></th>
                    <?php foreach ($days as $day): ?>
                        <th><?= t('day.' . strtolower($day)) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slots as $sNum => $sInfo): ?>
                <tr>
                    <td class="slot-cell">
                        <?= $sInfo['name'] ?>
                        <div class="slot-time"><?= $sInfo['time'] ?></div>
                    </td>
                    <?php foreach ($days as $day): ?>
                    <td>
                        <?php if (isset($scheduleGrid[$day][$sNum])): ?>
                            <?php $cls = $scheduleGrid[$day][$sNum]; ?>
                            <div class="class-box">
                                <span class="class-code"><?= htmlspecialchars($cls['code']) ?></span>
                                <span style="color:#333;font-size:0.75rem;"><?= htmlspecialchars(substr($cls['name'], 0, 22)) ?>...</span>
                                <span class="class-room"><i class="ri-map-pin-line"></i> <?= htmlspecialchars($cls['room']) ?></span>
                                <span class="class-teacher"><i class="ri-user-line"></i> <?= htmlspecialchars($cls['teacher']) ?></span>
                            </div>
                        <?php else: ?>
                            <span style="color:#dee2e6;font-size:1.2rem;">&minus;</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="hint-box">
        <details>
            <summary>ℹ️ <?= t('sch.policy') ?></summary>
            <ul>
                <li><?= t('sch.pol1') ?></li>
                <li><?= t('sch.pol2') ?></li>
                <li><?= t('sch.pol3') ?></li>
            </ul>
        </details>
    </div>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
