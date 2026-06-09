<?php
/**
 * DBS401 - Group 02
 * secure_versions/store_secure.php
 *
 * SECURE VERSION of store.php (Vulnerability 2 Fix)
 *
 * FIXES APPLIED:
 *   1. Input validation: Ensure quantity is a positive integer.
 *   2. Server-side logic: Prevent negative credit manipulation.
 *
 * COMPARE WITH VULNERABLE VERSION:
 *   Vulnerable:  if ($credits >= $cost) { $newCredits = $credits - $cost; }
 *   Secure:      if ($qty > 0 && $credits >= $cost) { ... }
 */

require_once dirname(__DIR__) . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location:'.APP_BASE.'/login.php'); exit; }

$conn = getDbConnection();
$userId = $_SESSION['user_id'];
$msg = '';
$msgType = 'info';

// Fetch current credits
$stmt = oci_parse($conn, "SELECT credits FROM STUDENTS WHERE user_id = :uid");
oci_bind_by_name($stmt, ':uid', $userId);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$credits = $row['CREDITS'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy'])) {
    $qty = (int)$_POST['quantity'];
    $price = 100;
    $cost = $qty * $price;

    // --- SECURE: Input validation for quantity ---
    if ($qty <= 0) {
        $msg = "Quantity must be a positive number.";
        $msgType = 'danger';
    } elseif ($credits < $cost) {
        $msg = "Insufficient credits!";
        $msgType = 'danger';
    } else {
        $newCredits = $credits - $cost;
        $upd = oci_parse($conn, "UPDATE STUDENTS SET credits = :c WHERE user_id = :uid");
        oci_bind_by_name($upd, ':c', $newCredits);
        oci_bind_by_name($upd, ':uid', $userId);
        if (oci_execute($upd)) {
            $credits = $newCredits;
            $msg = "Purchase successful! Total cost: $cost credits.";
            $msgType = 'success';
            logAction($userId, 'STORE_PURCHASE_SECURE', "Bought item qty $qty");
        } else {
            $e = oci_error($upd);
            $msg = "Error processing purchase: " . $e['message'];
            $msgType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>[SECURE] Store – FPT Student Portal</title><link rel="stylesheet" href="<?= APP_BASE ?>/style.css"></head>
<body>
<?php include dirname(__DIR__) . '/inc_navbar.php'; ?>
<div class="container">
    <h2>📚 [SECURE] Course Material Store</h2>
    <div class="alert alert-info" style="border-left-color:#2d6a4f;background:#f0fff4;">
        ✅ This is the <strong>patched version</strong>. Negative quantity exploits are mitigated.
    </div>
    <div class="alert alert-info">Credits: <strong><?= $credits ?></strong></div>
    <?php if($msg): ?><div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <div class="info-card">
        <table class="data-table">
            <tr>
                <td><strong>Advanced Security Guide (PDF)</strong><br><small>Price: 100 credits</small></td>
                <td>
                    <form method="POST">
                        <input type="number" name="quantity" value="1" min="1" required style="width:60px">
                        <button type="submit" name="buy" class="btn btn-primary">Order</button>
                    </form>
                </td>
            </tr>
            <tr>
                <td><strong>Exam Leak 2024 (CLASSIFIED)</strong><br><small>Price: 999,999 credits</small></td>
                <td>
                    <?php if($credits >= 999999): ?>
                        <div class="badge-inline badge-admin">FLAG: DBS401{LOGIC_GURU_2024}</div>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled>Too Expensive</button>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    <div class="info-card" style="margin-top:24px;border-left:4px solid #2d6a4f;">
        <h3>🛡️ Security Fix Summary</h3>
        <table class="info-table">
            <tr><th>Issue</th><td>Business Logic Flaw: Allowing negative quantity to manipulate credits balance.</td></tr>
            <tr><th>Fix</th><td>Add server-side validation to ensure <code>quantity</code> is a positive integer (<code>$qty > 0</code>).</td></tr>
            <tr><th>Extra</th><td>Client-side <code>min="1"</code> attribute added to input field (for usability, not security).</td></tr>
        </table>
        <pre style="margin-top:12px;background:#1a1a2e;color:#e8e8e8;padding:14px;border-radius:8px;font-size:0.82rem;overflow-x:auto;">
<span style="color:#f4a261;">// VULNERABLE:</span>
if ($credits >= $cost) {
    $newCredits = $credits - $cost;
}

<span style="color:#52b788;">// SECURE:</span>
if (<span style="color:#52b788;">$qty > 0</span> && $credits >= $cost) {
    $newCredits = $credits - $cost;
} else if ($qty <= 0) {
    $msg = "Quantity must be a positive number.";
}
        </pre>
    </div>
</div>
</body>
</html>
