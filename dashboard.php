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
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('nav.dashboard') ?> – FPT Student Portal</title>
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
        <h2><?= tf('db.welcome_fmt', htmlspecialchars($studentRow['FULL_NAME'] ?? $username)) ?>
            <span class="badge badge-<?= $role ?>"><?= strtoupper($role) ?></span>
        </h2>
        <span class="text-muted" style="font-size:0.85rem;"><?= t('sem.label') ?>: <strong><?= t('sem.value') ?></strong></span>
    </div>

    <!-- At-a-glance KPIs -->
    <?php if ($studentRow): ?>
    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-icon">🎯</span>
            <span class="kpi-value"><?= number_format((float)$studentRow['GPA'], 2) ?></span>
            <span class="kpi-label"><?= t('db.gpa') ?></span>
            <span class="kpi-sub"><?= t('db.gpa_sub') ?></span>
        </div>
        <div class="kpi-card">
            <span class="kpi-icon">📚</span>
            <span class="kpi-value"><?= count($enrollments) ?></span>
            <span class="kpi-label"><?= t('db.enrolled') ?></span>
            <span class="kpi-sub"><?= t('db.enrolled_sub') ?></span>
        </div>
        <div class="kpi-card">
            <span class="kpi-icon">🎓</span>
            <span class="kpi-value"><?= (int)$totalCredits ?></span>
            <span class="kpi-label"><?= t('db.credit_load') ?></span>
            <span class="kpi-sub"><?= t('db.credit_load_sub') ?></span>
        </div>
        <div class="kpi-card">
            <span class="kpi-icon">💳</span>
            <span class="kpi-value"><?= number_format((int)$studentRow['CREDITS']) ?></span>
            <span class="kpi-label"><?= t('db.material') ?></span>
            <span class="kpi-sub"><?= t('db.material_sub') ?></span>
        </div>
    </div>
    <?php elseif ($role === 'teacher'): $tCredits = 0; foreach ($teacherCourses as $tc) { $tCredits += (int)$tc['CREDITS']; } ?>
    <div class="kpi-grid">
        <div class="kpi-card">
            <span class="kpi-icon">📖</span>
            <span class="kpi-value"><?= count($teacherCourses) ?></span>
            <span class="kpi-label"><?= t('db.assigned') ?></span>
            <span class="kpi-sub"><?= t('db.assigned_sub') ?></span>
        </div>
        <div class="kpi-card">
            <span class="kpi-icon">🎓</span>
            <span class="kpi-value"><?= (int)$tCredits ?></span>
            <span class="kpi-label"><?= t('db.teaching') ?></span>
            <span class="kpi-sub"><?= t('db.teaching_sub') ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Academic Services Grid -->
    <div class="portal-grid">
        <?php if ($role === 'student'): ?>
            <a href="<?= APP_BASE ?>/courses.php" class="portal-btn">
                <span class="icon">📖</span>
                <span class="label"><?= t('db.svc_catalog') ?></span>
                <span class="sub"><?= t('db.svc_catalog_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/schedule.php" class="portal-btn">
                <span class="icon">📅</span>
                <span class="label"><?= t('db.svc_timetable') ?></span>
                <span class="sub"><?= t('db.svc_timetable_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/exams.php" class="portal-btn">
                <span class="icon">📝</span>
                <span class="label"><?= t('db.svc_exams') ?></span>
                <span class="sub"><?= t('db.svc_exams_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/attendance.php" class="portal-btn">
                <span class="icon">✅</span>
                <span class="label"><?= t('db.svc_attendance') ?></span>
                <span class="sub"><?= t('db.svc_attendance_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/transcript.php" class="portal-btn">
                <span class="icon">📄</span>
                <span class="label"><?= t('db.svc_transcript') ?></span>
                <span class="sub"><?= t('db.svc_transcript_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/tuition.php" class="portal-btn">
                <span class="icon">💳</span>
                <span class="label"><?= t('db.svc_tuition') ?></span>
                <span class="sub"><?= t('db.svc_tuition_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/store.php" class="portal-btn">
                <span class="icon">📚</span>
                <span class="label"><?= t('db.svc_store') ?></span>
                <span class="sub"><?= t('db.svc_store_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/search.php" class="portal-btn">
                <span class="icon">🔍</span>
                <span class="label"><?= t('db.svc_search') ?></span>
                <span class="sub"><?= t('db.svc_search_sub') ?></span>
            </a>
        <?php elseif ($role === 'teacher'): ?>
            <a href="<?= APP_BASE ?>/grades.php" class="portal-btn">
                <span class="icon">📝</span>
                <span class="label"><?= t('db.svc_grading') ?></span>
                <span class="sub"><?= t('db.svc_grading_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/courses.php" class="portal-btn">
                <span class="icon">📖</span>
                <span class="label"><?= t('db.svc_catalog') ?></span>
                <span class="sub"><?= t('db.svc_catalog_t_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/schedule.php" class="portal-btn">
                <span class="icon">📅</span>
                <span class="label"><?= t('db.svc_tsched') ?></span>
                <span class="sub"><?= t('db.svc_tsched_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/exams.php" class="portal-btn">
                <span class="icon">📝</span>
                <span class="label"><?= t('db.svc_exams') ?></span>
                <span class="sub"><?= t('db.svc_exams_t_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/attendance.php" class="portal-btn">
                <span class="icon">✅</span>
                <span class="label"><?= t('db.svc_attendance') ?></span>
                <span class="sub"><?= t('db.svc_attendance_t_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/search.php" class="portal-btn">
                <span class="icon">🔍</span>
                <span class="label"><?= t('db.svc_sdir') ?></span>
                <span class="sub"><?= t('db.svc_sdir_sub') ?></span>
            </a>
        <?php elseif ($role === 'admin'): ?>
            <a href="<?= APP_BASE ?>/admin.php" class="portal-btn">
                <span class="icon">⚙️</span>
                <span class="label"><?= t('db.svc_admin') ?></span>
                <span class="sub"><?= t('db.svc_admin_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/audit.php" class="portal-btn">
                <span class="icon">📋</span>
                <span class="label"><?= t('db.svc_audit') ?></span>
                <span class="sub"><?= t('db.svc_audit_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/courses.php" class="portal-btn">
                <span class="icon">📖</span>
                <span class="label"><?= t('db.svc_curric') ?></span>
                <span class="sub"><?= t('db.svc_curric_sub') ?></span>
            </a>
            <a href="<?= APP_BASE ?>/search.php" class="portal-btn">
                <span class="icon">🔍</span>
                <span class="label"><?= t('db.svc_ssearch') ?></span>
                <span class="sub"><?= t('db.svc_ssearch_sub') ?></span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Student Information Overview -->
    <?php if ($studentRow): ?>
    <div class="info-card">
        <h3>📋 <?= t('db.profile_title') ?></h3>
        <table class="info-table">
            <tr><th><?= t('db.f_fullname') ?></th><td><strong><?= htmlspecialchars($studentRow['FULL_NAME']) ?></strong></td></tr>
            <tr><th><?= t('db.f_sid') ?></th><td><code>SID-<?= str_pad((string)$studentRow['STUDENT_ID'], 3, '0', STR_PAD_LEFT) ?></code></td></tr>
            <tr><th><?= t('db.f_email') ?></th><td><?= htmlspecialchars($studentRow['EMAIL']) ?></td></tr>
            <tr><th><?= t('db.f_major') ?></th><td><?= htmlspecialchars($studentRow['MAJOR']) ?></td></tr>
            <tr><th><?= t('db.gpa') ?></th><td><span class="badge badge-student"><?= number_format((float)$studentRow['GPA'], 2) ?> / 4.00</span></td></tr>
            <tr><th><?= t('db.f_material') ?></th><td><strong><?= number_format((int)$studentRow['CREDITS']) ?> <?= t('db.credits') ?></strong> (<a href="<?= APP_BASE ?>/store.php"><?= t('db.visit_store') ?></a>)</td></tr>
        </table>
    </div>

    <div class="info-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3>📚 <?= t('db.enroll_title') ?></h3>
            <a href="<?= APP_BASE ?>/courses.php" class="btn btn-primary" style="padding:4px 12px; font-size:0.8rem;"><?= t('db.add_drop') ?></a>
        </div>
        <?php if ($enrollments): ?>
        <table class="data-table">
            <thead><tr>
                <th><?= t('db.c_code') ?></th><th><?= t('db.c_name') ?></th><th><?= t('db.c_credits') ?></th><th><?= t('db.c_instructor') ?></th><th><?= t('db.c_semester') ?></th><th><?= t('db.c_score') ?></th><th><?= t('db.c_transcript') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($enrollments as $e): ?>
            <tr>
                <td><strong><?= htmlspecialchars($e['COURSE_CODE']) ?></strong></td>
                <td><?= htmlspecialchars($e['COURSE_NAME']) ?></td>
                <td><?= (int)$e['CREDITS'] ?> <?= t('db.cr') ?></td>
                <td><small><?= htmlspecialchars($e['TEACHER_NAME'] ?? 'TBA') ?></small></td>
                <td><?= htmlspecialchars($e['SEMESTER']) ?></td>
                <td>
                    <?php if ($e['SCORE'] !== null): ?>
                        <strong><?= number_format((float)$e['SCORE'], 1) ?></strong>
                    <?php else: ?>
                        <span class="text-muted"><?= t('db.in_progress') ?></span>
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
        <p class="no-results"><?= t('db.no_enroll') ?> <a href="<?= APP_BASE ?>/courses.php"><?= t('db.register_now') ?> &rarr;</a></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Teacher Courses Overview -->
    <?php if ($role === 'teacher'): ?>
    <div class="info-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3>👨‍🏫 <?= t('db.teacher_courses') ?></h3>
            <a href="<?= APP_BASE ?>/grades.php" class="btn btn-primary" style="padding:4px 12px; font-size:0.8rem;"><?= t('db.go_grading') ?> &rarr;</a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= t('db.c_code') ?></th><th><?= t('db.c_title') ?></th><th><?= t('db.c_credits') ?></th><th><?= t('db.c_semester') ?></th><th><?= t('db.c_lead') ?></th><th><?= t('db.c_action') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teacherCourses as $tc): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($tc['COURSE_CODE']) ?></strong></td>
                    <td><?= htmlspecialchars($tc['COURSE_NAME']) ?></td>
                    <td><?= (int)$tc['CREDITS'] ?> <?= t('db.cr') ?></td>
                    <td><?= htmlspecialchars($tc['SEMESTER'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($tc['TEACHER_NAME'] ?? 'TBA') ?></td>
                    <td>
                        <a href="<?= APP_BASE ?>/grades.php?course_id=<?= (int)$tc['COURSE_ID'] ?>" class="btn btn-secondary" style="padding:3px 10px; font-size:0.75rem;">
                            <?= t('db.grade_roster') ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Academic Performance chart (student) -->
    <?php if ($studentRow && $enrollments): ?>
    <div class="info-card">
        <h3>📊 <?= t('db.chart_title') ?></h3>
        <div class="score-chart">
            <?php foreach ($enrollments as $e):
                $sc  = $e['SCORE'];
                $has = $sc !== null && $sc !== '';
                $val = $has ? (float)$sc : 0.0;
                $pct = $has ? max(5.0, min(100.0, $val * 10)) : 6.0;
                $cls = !$has ? 'pending' : ($val >= 8 ? 'good' : ($val >= 6.5 ? 'ok' : ($val >= 5 ? 'warn' : 'bad')));
            ?>
            <div class="sc-col" title="<?= htmlspecialchars($e['COURSE_NAME']) ?>">
                <div class="sc-val"><?= $has ? number_format($val, 1) : '—' ?></div>
                <div class="sc-track"><div class="sc-bar <?= $cls ?>" style="height: <?= $pct ?>%"></div></div>
                <div class="sc-label"><?= htmlspecialchars($e['COURSE_CODE']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="sc-legend">
            <span><i class="dot good"></i> ≥ 8.0</span>
            <span><i class="dot ok"></i> 6.5–7.9</span>
            <span><i class="dot warn"></i> 5.0–6.4</span>
            <span><i class="dot bad"></i> &lt; 5.0</span>
            <span><i class="dot pending"></i> <?= t('db.chart_inprog') ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Campus Bulletin / Notice Board (from the Notification Center) -->
    <div class="info-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3 style="margin:0;">📢 <?= t('db.news_title') ?></h3>
            <a href="<?= APP_BASE ?>/notifications.php" class="btn btn-secondary" style="padding:4px 12px; font-size:0.8rem;"><?= t('db.view_all') ?> &rarr;</a>
        </div>
        <?php $dashNotifs = getUserNotifications($conn, (int)$userId, $role, 4); ?>
        <?php if (!$dashNotifs): ?>
            <p class="no-results"><?= t('db.no_news') ?></p>
        <?php else: foreach ($dashNotifs as $dn):
            [$dnLabel, $dnClass] = notifCategoryMeta($dn['CATEGORY']); ?>
        <div class="news-item">
            <span class="news-tag <?= $dnClass ?>"><?= $dnLabel ?></span>
            <strong><?= htmlspecialchars($dn['TITLE']) ?></strong>
            <?php if (!(int)$dn['IS_READ']): ?><span class="notif-badge-new"><?= t('db.new') ?></span><?php endif; ?>
            <p style="font-size:0.85rem; color:var(--muted); margin-top:4px;">
                <?= htmlspecialchars($dn['BODY']) ?>
            </p>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
