<?php
/**
 * DBS401 - Group 02
 * store.php – VULNERABILITY 2: Insecure Business Logic (Negative Quantity)
 *
 * Severity (DBS401 report): Hard
 * Flag difficulty          : Very Hard
 *
 * WHY VULNERABLE:
 *   - $qty is cast to int but never validated to be positive.
 *   - cost = qty * price → negative qty → negative cost
 *   - newCredits = credits - (negative cost) → credits INCREASE
 *   - Allows purchasing items worth 999,999 credits with no real funds.
 *
 * FLAG 2: DBS401{LOGIC_GURU_2024}
 *   Appears when student's credits >= 999999 and they buy "Exam Leak 2024"
 */
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location:'.APP_BASE.'/login.php'); exit; } 

$conn   = getDbConnection();
$userId = $_SESSION['user_id']; // Lấy user_id từ session (không phải từ input)
$msg     = '';
$msgType = 'info'; // FIX: initialise $msgType to avoid undefined variable

// Lấy credits hiện tại của sinh viên
$stmt = oci_parse($conn, "SELECT credits FROM STUDENTS WHERE user_id = " . $userId);
// oci_bind_by_name($stmt, ':uid', $userId);
oci_execute($stmt);
$row     = oci_fetch_assoc($stmt);
$credits = $row ? (int)$row['CREDITS'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy'])) {
    $itemId = (int)($_POST['item_id'] ?? 1);
    $qty    = (int)($_POST['quantity'] ?? 1);

    // Giá theo item
    $prices = [1 => 100, 2 => 999999];
    $price  = $prices[$itemId] ?? 100;

    // ===========================================================
    // VULNERABLE LOGIC — thiếu kiểm tra $qty > 0
    // ===========================================================
    $cost = $qty * $price;

    if ($credits >= $cost) {
        $newCredits = $credits - $cost;
        $upd = oci_parse($conn, "UPDATE STUDENTS SET credits = :c WHERE user_id = " . $userId);
        oci_bind_by_name($upd, ':c',   $newCredits);
        //oci_bind_by_name($upd, ':uid', $userId);
        if (oci_execute($upd)) {
            $credits = $newCredits;
            if ($qty < 0) {
                $msg     = "Order processed. Credits adjusted by " . number_format(abs($cost)) . ".";
                $msgType = 'warning';
            } else {
                $msg     = "Purchase successful! Cost: " . number_format($cost) . " credits.";
                $msgType = 'success';
            }
            logAction($userId, 'STORE_BUY', json_encode(['item_id' => $itemId, 'qty' => $qty, 'cost' => $cost]));
        } else {
            $e       = oci_error($upd);
            $msg     = "Transaction failed.";
            $msgType = 'danger';
        }
    } else {
        $msg     = "Insufficient credits. You need " . number_format($cost) . " but only have " . number_format($credits) . ".";
        $msgType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Store – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>📚 Course Material Store</h2>
    <div class="alert alert-info">
        Your Credits: <strong><?= number_format($credits) ?></strong>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msgType) ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="info-card">
        <table class="data-table">
            <thead>
                <tr><th>Item</th><th>Price</th><th>Order</th></tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <strong>Advanced Security Guide (PDF)</strong><br>
                    <small class="text-muted">Comprehensive guide on web application security</small>
                </td>
                <td>100 credits</td>
                <td>
                    <form method="POST" style="display:flex;gap:8px;align-items:center;">
                        <input type="hidden" name="item_id" value="1">
                        <input type="number" name="quantity" value="1"
                               style="width:70px;padding:5px;" placeholder="Qty">
                        <button type="submit" name="buy" class="btn btn-primary">Order</button>
                    </form>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Exam Leak 2024 (CLASSIFIED)</strong><br>
                    <small class="text-muted">🔒 Restricted item – requires high credit balance</small>
                </td>
                <td>999,999 credits</td>
                <td>
                    <?php if ($credits >= 999999): ?>
                        <div class="alert alert-success" style="margin:0;padding:8px 12px;">
                            <strong>🚩 FLAG:</strong> <code>DBS401{LOGIC_GURU_2024}</code>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="item_id" value="2">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" name="buy" class="btn btn-secondary" disabled>
                                Too Expensive (need <?= number_format(999999 - $credits) ?> more)
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="hint-box">
        <details>
            <summary>ℹ️ Store Information</summary>
            <ul>
                <li>Credits are deducted based on <code>quantity × price</code>.</li>
                <li>You can order multiple units of any item.</li>
                <li>Contact admin if you experience billing issues.</li>
            </ul>
        </details>
    </div>
</div>
</body>
</html>