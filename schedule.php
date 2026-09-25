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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Timetable – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
    <style>
        .timetable { width: 100%; border-collapse: collapse; margin-top: 15px; table-layout: fixed; }
        .timetable th, .timetable td { border: 1px solid var(--border); padding: 10px; text-align: center; vertical-align: top; }
        .timetable th { background: #eef2f7; color: var(--secondary); font-size: 0.88rem; }
        .slot-cell { background: #fafbfc; width: 110px; font-weight: 600; font-size: 0.8rem; }
        .slot-time { color: var(--muted); font-size: 0.72rem; font-weight: normal; margin-top: 2px; }
        .class-box {
            background: #e3f2fd;
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
    <h2>📅 Weekly Class Timetable</h2>
    <p class="text-muted">
        Semester Summer 2024 &bull; Weekly academic schedule for
        <strong><?= $student ? htmlspecialchars($student['FULL_NAME']) : htmlspecialchars($username) ?></strong>
    </p>

    <div class="info-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px; flex-wrap:wrap; gap:8px;">
            <div style="font-weight:600; color:var(--secondary);">
                Active Semester: <code>2024-S1 (Summer)</code>
            </div>
            <div>
                <span class="badge badge-student"><?= count($enrolledCourses) ?> Enrolled Subjects</span>
            </div>
        </div>

        <table class="timetable">
            <thead>
                <tr>
                    <th style="width:120px;">Time / Slot</th>
                    <?php foreach ($days as $day): ?>
                        <th><?= $day ?></th>
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
                                <span class="class-room">📍 <?= htmlspecialchars($cls['room']) ?></span>
                                <span class="class-teacher">👤 <?= htmlspecialchars($cls['teacher']) ?></span>
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
            <summary>ℹ️ Attendance & Timetable Policy</summary>
            <ul>
                <li>Students must maintain an attendance record of at least 80% to be eligible for final examinations.</li>
                <li>Rooms prefixed with <code>LAB</code> are located in Building Delta.</li>
                <li>Report scheduling conflicts to the Office of Academic Affairs within the first 2 weeks.</li>
            </ul>
        </details>
    </div>
</div>
</body>
</html>
