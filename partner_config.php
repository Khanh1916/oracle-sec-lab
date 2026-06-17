<?php
/**
 * partner_config.php – LAB ONLY
 * VULNERABILITY 3A: Broken Access Control / Insecure Partner Configuration
 *
 * Intended business idea:
 *   Partner integration team can suggest/update the manifest URL.
 *
 * Vulnerability:
 *   Any authenticated user can update CONFIG_STORE.update_url.
 *   There is no admin role check, no approval flow, no URL whitelist,
 *   and no signature verification.
 */

require_once __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php');
    exit;
}

$conn = getDbConnection();
$msg = '';
$msgType = 'info';
$currentUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manifestUrl = trim($_POST['manifest_url'] ?? '');

    if ($manifestUrl === '') {
        $msg = 'Manifest URL is required.';
        $msgType = 'danger';
    } elseif (strlen($manifestUrl) > 255) {
        $msg = 'Manifest URL is too long.';
        $msgType = 'danger';
    } elseif (!preg_match('#^https?://#i', $manifestUrl)) {
        $msg = 'Manifest URL must start with http:// or https://';
        $msgType = 'danger';
    } else {
        /*
         * VULNERABLE:
         * Missing authorization check.
         * Any authenticated user can modify update_url.
         *
         * This intentionally uses bind variables to keep the vulnerability focused
         * on Broken Access Control, not SQL Injection.
         */
        $sql = "UPDATE CONFIG_STORE
                SET config_value = :url,
                    updated_at = SYSDATE
                WHERE config_key = 'update_url'";

        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':url', $manifestUrl);

        if (oci_execute($stmt)) {
            $msg = 'Partner manifest URL updated successfully.';
            $msgType = 'success';

            logAction(
                (int)$_SESSION['user_id'],
                'PARTNER_CONFIG_UPDATE',
                json_encode([
                    'new_update_url' => $manifestUrl,
                    'note' => 'LAB_VULN_BROKEN_ACCESS_CONTROL'
                ], JSON_UNESCAPED_SLASHES)
            );
        } else {
            $e = oci_error($stmt);
            $msg = 'Update failed: ' . $e['message'];
            $msgType = 'danger';
        }
    }
}

/* Show current update_url for convenience in lab */
$q = oci_parse($conn, "SELECT config_value FROM CONFIG_STORE WHERE config_key = 'update_url'");
oci_execute($q);
$row = oci_fetch_assoc($q);
$currentUrl = $row['CONFIG_VALUE'] ?? DEFAULT_UPDATE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Partner Configuration – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>🤝 Partner Integration Configuration</h2>

    <p class="alert alert-info">
        Configure the partner update manifest URL.
        <br>
        <strong>Lab note:</strong> this page is intentionally hidden and misconfigured.
    </p>

    <?php if ($msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msgType) ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <div class="info-card">
        <h3>Current Partner Manifest URL</h3>
        <p><code><?= htmlspecialchars($currentUrl) ?></code></p>
    </div>

    <div class="info-card">
        <h3>Update Partner Manifest Source</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="manifest_url">Manifest URL</label>
                <input type="text"
                       id="manifest_url"
                       name="manifest_url"
                       placeholder="http://partner.example.local:8081/manifest.json"
                       value="<?= htmlspecialchars($_POST['manifest_url'] ?? '') ?>"
                       required>
            </div>

            <button type="submit" class="btn btn-primary">
                Save Partner URL
            </button>
        </form>
    </div>
</div>
</body>
</html>