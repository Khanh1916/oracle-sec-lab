<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location:'.APP_BASE.'/login.php'); exit; }

$conn = getDbConnection();
$userId = $_SESSION['user_id'];
$msg = '';

// Lấy credits hiện tại
$stmt = oci_parse($conn, "SELECT credits FROM STUDENTS WHERE user_id = :uid");
oci_bind_by_name($stmt, ':uid', $userId);
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$credits = $row['CREDITS'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy'])) {
    $qty = (int)$_POST['quantity'];
    $price = 100;
    $cost = $qty * $price;

    // LỖI LOGIC: Không kiểm tra $qty > 0
    if ($credits >= $cost) {
        $newCredits = $credits - $cost;
        $upd = oci_parse($conn, "UPDATE STUDENTS SET credits = :c WHERE user_id = :uid");
        oci_bind_by_name($upd, ':c', $newCredits);
        oci_bind_by_name($upd, ':uid', $userId);
        if (oci_execute($upd)) {
            $credits = $newCredits;
            $msg = ($qty < 0) ? "Logic Error: Credits adjusted by " . abs($cost) : "Purchase successful!";
            logAction($userId, 'STORE_BUY', "Qty: $qty, Cost: $cost");
        }
    } else { $msg = "Insufficient credits."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Store – FPT Student Portal</title><link rel="stylesheet" href="<?= APP_BASE ?>/style.css"></head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>📚 Course Material Store</h2>
    <div class="alert alert-info">Credits: <strong><?= $credits ?></strong></div>
    <?php if($msg): ?><div class="alert alert-warning"><?= $msg ?></div><?php endif; ?>
    <div class="info-card">
        <table class="data-table">
            <tr>
                <td><strong>Advanced Security Guide (PDF)</strong><br><small>Price: 100 credits</small></td>
                <td>
                    <form method="POST">
                        <input type="number" name="quantity" value="1" style="width:60px">
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
</div>
</body>
</html>