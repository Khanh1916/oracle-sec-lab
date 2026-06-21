<?php
/**
 * DBS401 - Group 02
 * secure_versions/partner_config_secure.php
 *
 * SECURE VERSION of partner_config.php (Vulnerability 3A Fix)
 *
 * FIXES APPLIED:
 *   1. Authorization: only admin can change the partner manifest URL.
 *   2. Allowlist: only approved partner URLs are accepted.
 *   3. Server-side validation: URL must be a valid http(s) URL and match allowlist exactly.
 */

require_once dirname(__DIR__) . '/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo '<h2>403 Forbidden</h2>';
    exit;
}

$conn = getDbConnection();
$msg = '';
$msgType = 'info';

$allowedManifestUrls = [
    'http://127.0.0.1:8081/manifest.json',
    'https://cdn.fpt-partner.net/v3/manifest.json',
    'https://api.fpt-partner-cloud.net/v3/updates/manifest.json',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manifestUrl = trim($_POST['manifest_url'] ?? '');

    if ($manifestUrl === '') {
        $msg = 'Manifest URL is required.';
        $msgType = 'danger';
    } elseif (!filter_var($manifestUrl, FILTER_VALIDATE_URL)) {
        $msg = 'Invalid manifest URL format.';
        $msgType = 'danger';
    } elseif (!in_array($manifestUrl, $allowedManifestUrls, true)) {
        $msg = 'Blocked: manifest URL is not in the approved partner allowlist.';
        $msgType = 'danger';
        logAction((int)$_SESSION['user_id'], 'PARTNER_CONFIG_BLOCKED', $manifestUrl);
    } else {
        $sql = "UPDATE CONFIG_STORE
                SET config_value = :url,
                    updated_at = SYSDATE
                WHERE config_key = 'update_url'";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':url', $manifestUrl);

        if (oci_execute($stmt)) {
            $msg = 'Partner manifest URL updated successfully.';
            $msgType = 'success';
            logAction((int)$_SESSION['user_id'], 'PARTNER_CONFIG_UPDATE_SECURE', $manifestUrl);
        } else {
            $e = oci_error($stmt);
            $msg = 'Update failed: ' . $e['message'];
            $msgType = 'danger';
        }
    }
}

$q = oci_parse($conn, "SELECT config_value FROM CONFIG_STORE WHERE config_key = 'update_url'");
oci_execute($q);
$row = oci_fetch_assoc($q);
$currentUrl = $row['CONFIG_VALUE'] ?? DEFAULT_UPDATE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>[SECURE] Partner Configuration – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include dirname(__DIR__) . '/inc_navbar.php'; ?>
<div class="container">
    <h2>🤝 [SECURE] Partner Integration Configuration</h2>
    <div class="alert alert-info" style="border-left-color:#2d6a4f;background:#f0fff4;">
        ✅ This version requires admin role and only accepts approved partner URLs.
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msgType) ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="info-card">
        <h3>Current Partner Manifest URL</h3>
        <p><code><?= htmlspecialchars($currentUrl) ?></code></p>
    </div>

    <div class="info-card">
        <h3>Approved Partner Manifest Source</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="manifest_url">Manifest URL</label>
                <select id="manifest_url" name="manifest_url" required>
                    <?php foreach ($allowedManifestUrls as $url): ?>
                        <option value="<?= htmlspecialchars($url) ?>" <?= $url === $currentUrl ? 'selected' : '' ?>>
                            <?= htmlspecialchars($url) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save Partner URL</button>
        </form>
    </div>
</div>
</body>
</html>
