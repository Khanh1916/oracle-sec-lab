<?php
/**
 * DBS401 - Group 02
 * secure_versions/transcript_secure.php
 *
 * SECURE VERSION of legacy transcript.php IDOR scenario (not current Vuln 2)
 *
 * ROOT CAUSE OF IDOR:
 *   - No check that s.user_id = logged-in user's user_id.
 *   - transcript_ref was predictable (TXN-{id:03d}-{year}-S{sem}).
 *   - Any authenticated user could access any transcript.
 */

require_once dirname(__DIR__) . '/config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php'); exit;
}

$ref            = $_GET['ref'] ?? '';
$transcriptData = null;
$errMsg         = '';
$currentUserId  = $_SESSION['user_id'];

if ($ref !== '') {
    if (!preg_match('/^TXN-\d{3}-\d{4}-S\d+$/', $ref)) {
        $errMsg = 'Invalid transcript reference format.';
    } else {
        $conn = getDbConnection();

        // ── SECURE: Ownership enforced via JOIN on user_id ──────
        $sql = "SELECT e.enrollment_id,
                       e.transcript_ref,
                       e.semester,
                       e.score,
                       s.full_name,
                       s.major,
                       s.email,
                       c.course_name,
                       c.course_code
                FROM ENROLLMENTS e
                JOIN STUDENTS s ON e.student_id = s.student_id
                JOIN COURSES  c ON e.course_id  = c.course_id
                JOIN USERS    u ON s.user_id     = u.user_id
                WHERE e.transcript_ref = :ref
                  AND u.user_id = :uid";   // ← OWNERSHIP CHECK (IDOR fix)

        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':ref', $ref);
        oci_bind_by_name($stmt, ':uid', $currentUserId);
        oci_execute($stmt);
        $transcriptData = oci_fetch_assoc($stmt);

        if (!$transcriptData) {
            // Generic 404 — don't reveal whether record exists for another user
            http_response_code(404);
            $errMsg = 'Transcript not found or access denied.';
        } else {
            logAction($currentUserId, 'TRANSCRIPT_VIEW_SECURE',
                      json_encode(['ref' => $ref]));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>[SECURE] Transcript – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include dirname(__DIR__) . '/inc_navbar.php'; ?>
<div class="container">
    <h2>📄 [SECURE] Transcript Viewer</h2>
    <div class="alert alert-info" style="border-left-color:#2d6a4f;background:#f0fff4;">
        ✅ This is the <strong>patched version</strong>. IDOR is mitigated via session-based ownership check.
    </div>

    <form method="GET" action="">
        <div class="search-bar">
            <input type="text" name="ref" value="<?= htmlspecialchars($ref) ?>"
                   placeholder="e.g. TXN-001-2024-S1" class="search-input">
            <button type="submit" class="btn btn-primary">View Transcript</button>
        </div>
    </form>

    <?php if ($errMsg): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($errMsg) ?></div>
    <?php endif; ?>

    <?php if ($transcriptData): ?>
    <div class="info-card transcript-card">
        <h3>Transcript Record</h3>
        <table class="info-table">
            <tr><th>Reference</th>   <td><code><?= htmlspecialchars($transcriptData['TRANSCRIPT_REF']) ?></code></td></tr>
            <tr><th>Student Name</th><td><?= htmlspecialchars($transcriptData['FULL_NAME']) ?></td></tr>
            <tr><th>Email</th>       <td><?= htmlspecialchars($transcriptData['EMAIL']) ?></td></tr>
            <tr><th>Major</th>       <td><?= htmlspecialchars($transcriptData['MAJOR']) ?></td></tr>
            <tr><th>Course</th>      <td><?= htmlspecialchars($transcriptData['COURSE_NAME']) ?> (<?= htmlspecialchars($transcriptData['COURSE_CODE']) ?>)</td></tr>
            <tr><th>Semester</th>    <td><?= htmlspecialchars($transcriptData['SEMESTER']) ?></td></tr>
            <tr><th>Score</th>       <td><?= htmlspecialchars($transcriptData['SCORE'] ?? 'N/A') ?></td></tr>
            <!-- internal_note and admin_ref_id intentionally hidden in secure version -->
        </table>
    </div>
    <?php endif; ?>

    <div class="info-card" style="margin-top:24px;border-left:4px solid #2d6a4f;">
        <h3>🛡️ Security Fix Summary</h3>
        <table class="info-table">
            <tr><th>Issue</th><td>IDOR: no ownership check on transcript_ref; predictable token pattern</td></tr>
            <tr><th>Fix 1</th><td>Add <code>AND u.user_id = :uid</code> (session user) to the query</td></tr>
            <tr><th>Fix 2</th><td>Use bind variable for <code>transcript_ref</code> parameter</td></tr>
            <tr><th>Fix 3</th><td>Remove <code>internal_note</code> and <code>admin_ref_id</code> from response</td></tr>
            <tr><th>Fix 4</th><td>Use non-predictable transcript tokens (UUID-based or signed JWT)</td></tr>
        </table>
        <pre style="margin-top:12px;background:#1a1a2e;color:#e8e8e8;padding:14px;border-radius:8px;font-size:0.82rem;overflow-x:auto;">
<span style="color:#f4a261;">// VULNERABLE (no ownership check):</span>
WHERE e.transcript_ref = '$ref'

<span style="color:#52b788;">// SECURE (ownership enforced):</span>
WHERE e.transcript_ref = :ref
  AND u.user_id = :uid   <span style="color:#aaa;">// session user must own the transcript</span>
        </pre>
    </div>
</div>
</body>
</html>
