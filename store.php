<?php

require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location:'.APP_BASE.'/login.php'); exit; } 

$conn   = getDbConnection();
$userId = $_SESSION['user_id']; 
$msg     = '';
$msgType = 'info'; 

$stmt = oci_parse($conn, "SELECT credits FROM STUDENTS WHERE user_id = " . $userId);
// oci_bind_by_name($stmt, ':uid', $userId);
oci_execute($stmt);
$row     = oci_fetch_assoc($stmt);
$credits = $row ? (int)$row['CREDITS'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy'])) {
    $itemId = (int)($_POST['item_id'] ?? 1);
    $qty    = (int)($_POST['quantity'] ?? 1);

    
    $prices = [1 => 100, 2 => 999999];
    $price  = $prices[$itemId] ?? 100;

    $cost = $qty * $price;

    if ($credits >= $cost) {
        $newCredits = $credits - $cost;
        $upd = oci_parse($conn, "UPDATE STUDENTS SET credits = :c WHERE user_id = " . $userId);
        oci_bind_by_name($upd, ':c',   $newCredits);
        //oci_bind_by_name($upd, ':uid', $userId);
        if (oci_execute($upd)) {
            $credits = $newCredits;
            if ($qty < 0) {
                $msg     = tf('st.msg_adjusted_fmt', number_format(abs($cost)));
                $msgType = 'warning';
            } else {
                $msg     = tf('st.msg_ok_fmt', number_format($cost));
                $msgType = 'success';
            }
            logAction($userId, 'STORE_BUY', json_encode(['item_id' => $itemId, 'qty' => $qty, 'cost' => $cost]));
        } else {
            $e       = oci_error($upd);
            $msg     = t('st.msg_fail');
            $msgType = 'danger';
        }
    } else {
        $msg     = tf('st.msg_insufficient_fmt', number_format($cost), number_format($credits));
        $msgType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($GLOBALS['PORTAL_LANG']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="<?= APP_BASE ?>/favicon.svg">
    <title><?= t('st.title') ?> – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>📚 <?= t('st.heading') ?></h2>
    <div class="alert alert-info">
        <?= t('st.your_credits') ?>: <strong><?= number_format($credits) ?></strong>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msgType) ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="info-card">
        <table class="data-table">
            <thead>
                <tr><th><?= t('st.h_item') ?></th><th><?= t('st.h_price') ?></th><th><?= t('st.h_order') ?></th></tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <strong><?= t('st.item1') ?></strong><br>
                    <small class="text-muted"><?= t('st.item1_desc') ?></small>
                </td>
                <td>100 <?= t('db.credits') ?></td>
                <td>
                    <form method="POST" style="display:flex;gap:8px;align-items:center;">
                        <input type="hidden" name="item_id" value="1">
                        <input type="number" name="quantity" value="1"
                               style="width:70px;padding:5px;" placeholder="Qty">
                        <button type="submit" name="buy" class="btn btn-primary"><?= t('st.order') ?></button>
                    </form>
                </td>
            </tr>
            <tr>
                <td>
                    <strong><?= t('st.item2') ?></strong><br>
                    <small class="text-muted"><?= t('st.item2_desc') ?></small>
                </td>
                <td>999,999 <?= t('db.credits') ?></td>
                <td>
                    <?php if ($credits >= 999999): ?>
                        <div class="alert alert-success" style="margin:0;padding:8px 12px;">
                            <strong>🚩 FLAG:</strong> <code><?= htmlspecialchars(getenv('FLAG2') ?: 'DBS401{LOGIC_GURU_2024}') ?></code>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="item_id" value="2">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" name="buy" class="btn btn-secondary" disabled>
                                <?= tf('st.too_expensive_fmt', number_format(999999 - $credits)) ?>
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
            <summary>ℹ️ <?= t('st.info') ?></summary>
            <ul>
                <li><?= t('st.info1') ?></li>
                <li><?= t('st.info2') ?></li>
                <li><?= t('st.info3') ?></li>
            </ul>
        </details>
    </div>
</div>
<?php include __DIR__ . '/inc_footer.php'; ?>
</body>
</html>