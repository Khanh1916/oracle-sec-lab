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
$totalCredits = 0;
$feePerCredit = 1150000; // 1,150,000 VND per credit

if ($role === 'student') {
    $stmt = oci_parse($conn, "SELECT student_id, full_name, email, major, credits FROM STUDENTS WHERE user_id = " . $userId);
    oci_execute($stmt);
    $student = oci_fetch_assoc($stmt);

    if ($student) {
        $sid = (int)$student['STUDENT_ID'];
        $q = oci_parse($conn, "SELECT c.course_id, c.course_code, c.course_name, c.credits, c.semester, e.transcript_ref
                               FROM ENROLLMENTS e
                               JOIN COURSES c ON e.course_id = c.course_id
                               WHERE e.student_id = :sid
                               ORDER BY c.course_code");
        oci_bind_by_name($q, ':sid', $sid);
        oci_execute($q);
        while ($r = oci_fetch_assoc($q)) {
            $enrolledCourses[] = $r;
            $totalCredits += (int)$r['CREDITS'];
        }
    }
} else {
    // For admin or teacher, fetch sample student
    $stmt = oci_parse($conn, "SELECT student_id, full_name, email, major, credits FROM STUDENTS WHERE ROWNUM = 1");
    oci_execute($stmt);
    $student = oci_fetch_assoc($stmt);
}

$totalTuition = $totalCredits * $feePerCredit;
$invoiceRef   = $student ? sprintf("INV-2024-S1-%04d", (int)$student['STUDENT_ID']) : 'INV-2024-S1-0001';
$paymentStatus = t('tu.status_paid');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('tu.title') ?> – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
    <style>
        .invoice-card { border-left: 4px solid var(--primary); }
        .summary-box { display: flex; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
        .summary-item { background: #faf9ff; border: 1px solid var(--border); border-radius: var(--radius); padding: 14px 18px; flex: 1; min-width: 180px; }
        .summary-label { font-size: 0.8rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .summary-val { font-size: 1.3rem; font-weight: 700; color: var(--secondary); margin-top: 4px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2><i class="ri-bank-card-line"></i> <?= t('tu.heading') ?></h2>
    <p class="text-muted"><?= t('sem.label') ?> <?= t('sem.value') ?> &bull; <?= t('tu.intro') ?></p>

    <div class="summary-box">
        <div class="summary-item">
            <div class="summary-label"><?= t('tu.invoice_ref') ?></div>
            <div class="summary-val" style="font-size:1.1rem;"><code><?= htmlspecialchars($invoiceRef) ?></code></div>
        </div>
        <div class="summary-item">
            <div class="summary-label"><?= t('tu.reg_credits') ?></div>
            <div class="summary-val"><?= $totalCredits ?> <?= t('tu.credits') ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-label"><?= t('tu.total_tuition') ?></div>
            <div class="summary-val"><?= number_format($totalTuition) ?> VND</div>
        </div>
        <div class="summary-item">
            <div class="summary-label"><?= t('tu.fin_status') ?></div>
            <div class="summary-val" style="color:var(--success);">
                <span class="badge badge-student" style="font-size:0.9rem; padding:4px 12px;"><i class="ri-checkbox-circle-line"></i> <?= $paymentStatus ?></span>
            </div>
        </div>
    </div>

    <?php if ($student): ?>
    <div class="info-card invoice-card">
        <h3><?= t('tu.breakdown') ?></h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= t('tu.h_code') ?></th>
                    <th><?= t('tu.h_title') ?></th>
                    <th><?= t('tu.credits') ?></th>
                    <th><?= t('tu.h_rate') ?></th>
                    <th><?= t('tu.h_subtotal') ?></th>
                    <th><?= t('tu.h_status') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($enrolledCourses) === 0): ?>
                    <tr><td colspan="6" class="no-results"><?= t('tu.none') ?></td></tr>
                <?php else: ?>
                    <?php foreach ($enrolledCourses as $c): ?>
                    <?php $sub = (int)$c['CREDITS'] * $feePerCredit; ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c['COURSE_CODE']) ?></strong></td>
                        <td><?= htmlspecialchars($c['COURSE_NAME']) ?></td>
                        <td><?= (int)$c['CREDITS'] ?> <?= t('db.cr') ?></td>
                        <td><?= number_format($feePerCredit) ?> VND</td>
                        <td><strong><?= number_format($sub) ?> VND</strong></td>
                        <td><span class="badge badge-student"><?= t('tu.settled') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8f9fa; font-weight:700;">
                    <td colspan="4" style="text-align:right;"><?= t('tu.total_due') ?></td>
                    <td colspan="2" style="color:var(--primary); font-size:1.05rem;"><?= number_format($totalTuition) ?> VND</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="info-card">
        <h3><i class="ri-bank-line"></i> <?= t('tu.payment_info') ?></h3>
        <table class="info-table">
            <tr><th><?= t('tu.bank') ?></th><td>TPBank (Tien Phong Commercial Joint Stock Bank)</td></tr>
            <tr><th><?= t('tu.acct') ?></th><td><code>FPTEDU<?= str_pad((string)$student['STUDENT_ID'], 6, '0', STR_PAD_LEFT) ?></code></td></tr>
            <tr><th><?= t('tu.holder') ?></th><td>TRUONG DAI HOC FPT - <?= htmlspecialchars(strtoupper($student['FULL_NAME'])) ?></td></tr>
            <tr><th><?= t('tu.desc') ?></th><td><code>HP SUMMER 2024 <?= htmlspecialchars($student['FULL_NAME']) ?></code></td></tr>
            <tr><th><?= t('tu.store_credits') ?></th><td><?= tf('tu.store_avail_fmt', '<strong>' . number_format((int)$student['CREDITS']) . ' ' . t('db.credits') . '</strong>', '<a href="' . APP_BASE . '/store.php">' . t('tu.course_store') . '</a>') ?></td></tr>
        </table>
    </div>
    <?php endif; ?>

    <div class="hint-box">
        <details>
            <summary><i class="ri-information-line"></i> <?= t('tu.regs') ?></summary>
            <ul>
                <li><?= t('tu.reg1') ?></li>
                <li><?= t('tu.reg2') ?></li>
                <li><?= t('tu.reg3') ?></li>
            </ul>
        </details>
    </div>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>
