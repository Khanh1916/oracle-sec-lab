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
$paymentStatus = 'PAID';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tuition & Financial Status – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
    <style>
        .invoice-card { border-left: 4px solid var(--primary); }
        .summary-box { display: flex; justify-content: space-between; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
        .summary-item { background: #fafbfc; border: 1px solid var(--border); border-radius: var(--radius); padding: 14px 18px; flex: 1; min-width: 180px; }
        .summary-label { font-size: 0.8rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .summary-val { font-size: 1.3rem; font-weight: 700; color: var(--secondary); margin-top: 4px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>💳 Tuition & Financial Fee Status</h2>
    <p class="text-muted">Semester Summer 2024 &bull; Official tuition invoice and academic fee summary.</p>

    <div class="summary-box">
        <div class="summary-item">
            <div class="summary-label">Invoice Ref</div>
            <div class="summary-val" style="font-size:1.1rem;"><code><?= htmlspecialchars($invoiceRef) ?></code></div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Registered Credits</div>
            <div class="summary-val"><?= $totalCredits ?> Credits</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Total Semester Tuition</div>
            <div class="summary-val"><?= number_format($totalTuition) ?> VND</div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Financial Status</div>
            <div class="summary-val" style="color:var(--success);">
                <span class="badge badge-student" style="font-size:0.9rem; padding:4px 12px;">✅ <?= $paymentStatus ?></span>
            </div>
        </div>
    </div>

    <?php if ($student): ?>
    <div class="info-card invoice-card">
        <h3>Tuition Billing Breakdown</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Course Code</th>
                    <th>Course Title</th>
                    <th>Credits</th>
                    <th>Fee Rate / Credit</th>
                    <th>Subtotal (VND)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($enrolledCourses) === 0): ?>
                    <tr><td colspan="6" class="no-results">No courses enrolled for billing in current semester.</td></tr>
                <?php else: ?>
                    <?php foreach ($enrolledCourses as $c): ?>
                    <?php $sub = (int)$c['CREDITS'] * $feePerCredit; ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c['COURSE_CODE']) ?></strong></td>
                        <td><?= htmlspecialchars($c['COURSE_NAME']) ?></td>
                        <td><?= (int)$c['CREDITS'] ?> cr</td>
                        <td><?= number_format($feePerCredit) ?> VND</td>
                        <td><strong><?= number_format($sub) ?> VND</strong></td>
                        <td><span class="badge badge-student">SETTLED</span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8f9fa; font-weight:700;">
                    <td colspan="4" style="text-align:right;">Total Amount Due:</td>
                    <td colspan="2" style="color:var(--primary); font-size:1.05rem;"><?= number_format($totalTuition) ?> VND</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="info-card">
        <h3>🏦 Payment & Virtual Account Information</h3>
        <table class="info-table">
            <tr><th>Beneficiary Bank</th><td>TPBank (Tien Phong Commercial Joint Stock Bank)</td></tr>
            <tr><th>Beneficiary Account</th><td><code>FPTEDU<?= str_pad((string)$student['STUDENT_ID'], 6, '0', STR_PAD_LEFT) ?></code></td></tr>
            <tr><th>Account Holder</th><td>TRUONG DAI HOC FPT - <?= htmlspecialchars(strtoupper($student['FULL_NAME'])) ?></td></tr>
            <tr><th>Transfer Description</th><td><code>HP SUMMER 2024 <?= htmlspecialchars($student['FULL_NAME']) ?></code></td></tr>
            <tr><th>Material Store Credits</th><td><strong><?= number_format((int)$student['CREDITS']) ?> credits</strong> available in <a href="<?= APP_BASE ?>/store.php">Course Store</a></td></tr>
        </table>
    </div>
    <?php endif; ?>

    <div class="hint-box">
        <details>
            <summary>ℹ️ Tuition Payment Regulations</summary>
            <ul>
                <li>Tuition must be paid in full at least 7 days before the start of the semester.</li>
                <li>Late payment incurs a penalty fee according to University Financial Regulations.</li>
                <li>Electronic receipts (e-Invoices) will be sent to your student email within 48 hours of payment.</li>
            </ul>
        </details>
    </div>
</div>
</body>
</html>
