<?php
/**
 * DBS401 - Group 02
 * transcript.php  –  VULNERABILITY 2: IDOR + Broken Access Control
 * (NOTE: This was part of an old vulnerability scenario for Flag 2.
 *  The new VULN 2 is Business Logic in store.php.
 *  This file is now SECURE. The IDOR vulnerability has been patched with ownership checks.)
 *
 * DECOY:
 *   TXN-004-2024-S1 → internal_note chứa base64 FAKE
 *   TXN-040..060   → giả 403 để gây nản brute force đơn giản
 *   TXN-099-2024-S1 → hidden student, chứa flag part A + admin_ref_id
 */
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location:'.APP_BASE.'/login.php'); exit; }

$ref = trim($_GET['ref'] ?? '');
$transcriptData = null;
$errMsg = '';

if ($ref !== '') {
    if (!preg_match('/^TXN-\d{3}-\d{4}-S\d+$/', $ref)) {
        $errMsg = 'Invalid transcript reference format.';
    } elseif (preg_match('/^TXN-0([4-5]\d|60)-/', $ref)) {
        http_response_code(403);
        $errMsg = 'Access denied. This record is classified or restricted.';
    } else {
        $conn = getDbConnection();
        // VULNERABLE: bind variable diệt SQLi, NHƯNG không có ownership check
        $sql = "SELECT e.enrollment_id, e.student_id, e.transcript_ref,
                       e.semester, e.score, e.internal_note, e.admin_ref_id,
                       s.full_name, s.major, s.email, u.user_id,
                       c.course_name, c.course_code
                FROM ENROLLMENTS e
                JOIN STUDENTS s ON e.student_id = s.student_id
                JOIN COURSES  c ON e.course_id  = c.course_id
                JOIN USERS    u ON s.user_id    = u.user_id -- Join with USERS to check ownership
                WHERE e.transcript_ref = :ref";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':ref', $ref);
        if (!oci_execute($stmt)) {
            $errMsg = 'Error fetching transcript.';
        } else {
            $transcriptData = oci_fetch_assoc($stmt);
            if (!$transcriptData) {
                $errMsg = 'Transcript not found.';
            } elseif ($transcriptData['USER_ID'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
                // SECURE: Ownership check - only admin or owner can view
                http_response_code(403);
                $errMsg = 'Access denied. You do not have permission to view this transcript.';
                $transcriptData = null; // Clear data to prevent display
            } else {
                logAction($_SESSION['user_id'], 'TRANSCRIPT_VIEW',
                          json_encode(['ref' => $ref, 'sid' => $transcriptData['STUDENT_ID']]));
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transcript – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>📄 Transcript Viewer</h2>
    <form method="GET" action="">
        <div class="search-bar">
            <input type="text" name="ref" value="<?= htmlspecialchars($ref) ?>"
                   placeholder="e.g. TXN-001-2024-S1" class="search-input">
            <button type="submit" class="btn btn-primary">View Transcript</button>
        </div>
    </form>

    <?php if ($errMsg): ?>
        <div class="alert alert-<?= http_response_code()===403?'danger':'warning' ?>">
            <?= htmlspecialchars($errMsg) ?>
        </div>
    <?php endif; ?>

    <?php if ($transcriptData): ?>
    <div class="info-card transcript-card">
        <h3>Transcript Record</h3>
        <table class="info-table">
            <tr><th>Reference</th>   <td><code><?= htmlspecialchars($transcriptData['TRANSCRIPT_REF']) ?></code></td></tr>
            <tr><th>Student Name</th><td><?= htmlspecialchars($transcriptData['FULL_NAME']) ?></td></tr>
            <tr><th>Email</th>       <td><?= htmlspecialchars($transcriptData['EMAIL'] ?? 'N/A') ?></td></tr>
            <tr><th>Major</th>       <td><?= htmlspecialchars($transcriptData['MAJOR']) ?></td></tr>
            <tr><th>Course</th>      <td><?= htmlspecialchars($transcriptData['COURSE_NAME']) ?> (<?= htmlspecialchars($transcriptData['COURSE_CODE']) ?>)</td></tr>
            <tr><th>Semester</th>    <td><?= htmlspecialchars($transcriptData['SEMESTER']) ?></td></tr>
            <tr><th>Score</th>       <td><?= htmlspecialchars($transcriptData['SCORE'] ?? 'N/A') ?></td></tr>
            <tr>
                <th>System Note</th>
                <td class="system-note"><code><?= htmlspecialchars($transcriptData['INTERNAL_NOTE'] ?? 'None') ?></code></td>
            </tr>
            <?php if (!empty($transcriptData['ADMIN_REF_ID'])): ?>
            <tr>
                <th>Admin Log Ref</th>
                <td>
                    <a href="<?= APP_BASE ?>/audit.php?log_id=<?= (int)$transcriptData['ADMIN_REF_ID'] ?>">
                        LOG-<?= (int)$transcriptData['ADMIN_REF_ID'] ?>
                    </a>
                    <small class="text-muted">– Audit record for this enrollment</small>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <div class="transcript-footer">
            <small class="text-muted">
                Enrollment ID: <?= (int)$transcriptData['ENROLLMENT_ID'] ?>
                | Student ID: <?= (int)$transcriptData['STUDENT_ID'] ?>
            </small>
        </div>
    </div>
    <?php endif; ?>

    <div class="hint-box">
        <details>
            <summary>ℹ️ Transcript Reference Format</summary>
            <p>Format: <code>TXN-{ID:3digits}-{Year}-S{Semester}</code></p>
            <p>Example: <code>TXN-001-2024-S1</code></p>
        </details>
    </div>
</div>
</body>
</html>
