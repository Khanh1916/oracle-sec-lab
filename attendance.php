<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location: ' . APP_BASE . '/login.php'); exit; }

$conn     = getDbConnection();
$userId   = (int)$_SESSION['user_id'];
$role     = $_SESSION['role'];
$username = $_SESSION['username'];

$ATT_THRESHOLD = 80; // Minimum attendance (%) required to sit the final exam.

// Status display metadata (label key + css class).
function attStatusMeta(string $status): array {
    switch (strtolower($status)) {
        case 'present': return [t('att.st_present'), 'att-present'];
        case 'late':    return [t('att.st_late'),    'att-late'];
        case 'absent':  return [t('att.st_absent'),  'att-absent'];
        default:        return [ucfirst($status),    'att-present'];
    }
}

// Colour a percentage against the eligibility threshold.
function attClass(int $pct, int $threshold): string {
    if ($pct >= 90) return 'good';
    if ($pct >= $threshold) return 'ok';
    if ($pct >= $threshold - 10) return 'warn';
    return 'bad';
}

$student        = null;
$courseSummary  = [];   // per-course rollup (student view)
$sessions       = [];   // detailed log (student view)
$classSummary   = [];   // per-course rollup (teacher / admin view)
$overall        = ['total' => 0, 'attended' => 0];

if ($role === 'student') {
    $q = oci_parse($conn, "SELECT student_id, full_name FROM STUDENTS WHERE user_id = :b_uid");
    oci_bind_by_name($q, ':b_uid', $userId);
    oci_execute($q);
    $student = oci_fetch_assoc($q);

    if ($student) {
        $sql = "SELECT a.attendance_id, c.course_code, c.course_name, a.slot, a.status,
                       TO_CHAR(a.session_date, 'YYYY-MM-DD') AS session_day,
                       TO_CHAR(a.session_date, 'Dy')          AS session_dow
                FROM ATTENDANCE a
                JOIN COURSES c ON c.course_id = a.course_id
                WHERE a.student_id = :b_sid
                ORDER BY c.course_code, a.session_date";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':b_sid', $student['STUDENT_ID']);
        oci_execute($stmt);
        while ($r = oci_fetch_assoc($stmt)) {
            $sessions[] = $r;
            $code = $r['COURSE_CODE'];
            if (!isset($courseSummary[$code])) {
                $courseSummary[$code] = [
                    'code' => $code, 'name' => $r['COURSE_NAME'],
                    'present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0,
                ];
            }
            $st = strtolower($r['STATUS']);
            if (isset($courseSummary[$code][$st])) { $courseSummary[$code][$st]++; }
            $courseSummary[$code]['total']++;
            $overall['total']++;
            if ($st === 'present' || $st === 'late') { $overall['attended']++; }
        }
    }
} else {
    // Teacher / admin: aggregate attendance per course.
    $sql = "SELECT c.course_code, c.course_name,
                   COUNT(*)                        AS total_marks,
                   COUNT(DISTINCT a.student_id)    AS students,
                   SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) AS attended
            FROM ATTENDANCE a
            JOIN COURSES c ON c.course_id = a.course_id
            GROUP BY c.course_code, c.course_name
            ORDER BY c.course_code";
    $stmt = oci_parse($conn, $sql);
    oci_execute($stmt);
    while ($r = oci_fetch_assoc($stmt)) { $classSummary[] = $r; }
}

$overallPct = $overall['total'] > 0 ? (int)round(100 * $overall['attended'] / $overall['total']) : null;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('att.title') ?> – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2><i class="ri-checkbox-circle-line"></i> <?= t('att.title') ?></h2>
    <p class="text-muted">
        <?= t('sem.label') ?> <?= t('sem.value') ?> &bull;
        <?php if ($role === 'student'): ?>
            <?= tf('att.sub_student_fmt', '<strong>' . htmlspecialchars($student['FULL_NAME'] ?? $username) . '</strong>') ?>
        <?php else: ?>
            <?= t('att.sub_staff') ?>
        <?php endif; ?>
    </p>

    <?php if ($role === 'student'): ?>
        <?php if ($overallPct === null): ?>
            <div class="info-card"><p class="no-results"><?= t('att.none') ?></p></div>
        <?php else: ?>
            <!-- Overall standing -->
            <?php $oClass = attClass($overallPct, $ATT_THRESHOLD); ?>
            <div class="info-card">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div>
                        <h3 style="margin:0;"><?= t('att.overall') ?></h3>
                        <p class="text-muted" style="margin:4px 0 0;">
                            <?= tf('att.overall_sub_fmt', (int)$overall['attended'], (int)$overall['total'], $ATT_THRESHOLD) ?>
                        </p>
                    </div>
                    <div class="att-big att-<?= $oClass ?>"><?= $overallPct ?>%</div>
                </div>
                <div class="att-track" style="margin-top:14px;">
                    <div class="att-fill <?= $oClass ?>" style="width:<?= $overallPct ?>%"></div>
                </div>
                <?php if ($overallPct < $ATT_THRESHOLD): ?>
                    <div class="alert alert-danger" style="margin-top:14px; margin-bottom:0;">
                        <?= tf('att.warning_fmt', $ATT_THRESHOLD) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Per-course breakdown -->
            <h3><?= t('att.by_course') ?></h3>
            <div class="att-grid">
                <?php foreach ($courseSummary as $cs):
                    $attended = $cs['present'] + $cs['late'];
                    $pct = $cs['total'] > 0 ? (int)round(100 * $attended / $cs['total']) : 0;
                    $cls = attClass($pct, $ATT_THRESHOLD);
                ?>
                <div class="info-card att-card">
                    <div class="att-card-head">
                        <div>
                            <strong><?= htmlspecialchars($cs['code']) ?></strong>
                            <div class="exam-sub"><?= htmlspecialchars($cs['name']) ?></div>
                        </div>
                        <div class="att-pct att-<?= $cls ?>"><?= $pct ?>%</div>
                    </div>
                    <div class="att-track"><div class="att-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
                    <div class="att-legend">
                        <span class="att-present">● <?= (int)$cs['present'] ?> <?= t('att.present') ?></span>
                        <span class="att-late">● <?= (int)$cs['late'] ?> <?= t('att.late') ?></span>
                        <span class="att-absent">● <?= (int)$cs['absent'] ?> <?= t('att.absent') ?></span>
                        <span class="text-muted"><?= (int)$cs['total'] ?> <?= t('att.sessions') ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Session log -->
            <div class="info-card" style="padding:0; overflow:hidden; margin-top:4px;">
                <div style="padding:16px 20px; border-bottom:1px solid var(--row-border); font-weight:700; color:var(--secondary);">
                    <?= t('att.session_log') ?>
                </div>
                <table class="data-table">
                    <thead>
                        <tr><th><?= t('att.col_date') ?></th><th><?= t('att.col_day') ?></th><th><?= t('att.col_subject') ?></th><th><?= t('att.col_slot') ?></th><th><?= t('att.col_status') ?></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $s):
                            [$label, $cls] = attStatusMeta($s['STATUS']); ?>
                        <tr>
                            <td><?= htmlspecialchars($s['SESSION_DAY']) ?></td>
                            <td><?= htmlspecialchars(dowLabel($s['SESSION_DAY'])) ?></td>
                            <td><strong><?= htmlspecialchars($s['COURSE_CODE']) ?></strong></td>
                            <td><?= htmlspecialchars($s['SLOT']) ?></td>
                            <td><span class="att-pill <?= $cls ?>"><?= $label ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Teacher / admin aggregate view -->
        <div class="info-card" style="padding:0; overflow:hidden;">
            <?php if (!$classSummary): ?>
                <p class="no-results" style="padding:24px;"><?= t('att.none_staff') ?></p>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th><?= t('att.staff_code') ?></th><th><?= t('att.staff_course') ?></th><th><?= t('att.staff_students') ?></th><th><?= t('att.staff_marks') ?></th><th><?= t('att.staff_rate') ?></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($classSummary as $c):
                        $total = (int)$c['TOTAL_MARKS'];
                        $pct = $total > 0 ? (int)round(100 * (int)$c['ATTENDED'] / $total) : 0;
                        $cls = attClass($pct, $ATT_THRESHOLD);
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c['COURSE_CODE']) ?></strong></td>
                        <td><?= htmlspecialchars($c['COURSE_NAME']) ?></td>
                        <td><?= (int)$c['STUDENTS'] ?></td>
                        <td><?= $total ?></td>
                        <td style="min-width:160px;">
                            <div class="att-track" style="margin-bottom:4px;"><div class="att-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
                            <span class="att-<?= $cls ?>" style="font-weight:700; font-size:0.82rem;"><?= $pct ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="hint-box">
        <details>
            <summary><i class="ri-information-line"></i> <?= t('att.policy') ?></summary>
            <ul>
                <li><?= tf('att.pol1_fmt', $ATT_THRESHOLD) ?></li>
                <li><?= t('att.pol2') ?></li>
                <li><?= t('att.pol3') ?></li>
            </ul>
        </details>
    </div>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
