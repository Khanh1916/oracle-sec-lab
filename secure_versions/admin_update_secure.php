<?php
/**
 * DBS401 - Group 02
 * secure_versions/admin_update_secure.php
 *
 * SECURE VERSION of admin.php's update check (Vulnerability 3 Fix)
 *
 * FIXES APPLIED:
 *   1. Whitelisting: Only allow update URLs from a predefined list.
 *   2. Digital Signature Verification (conceptual): Ensure manifest integrity.
 *   3. Defense in depth: even if partner_config/update_url is abused, admin update only trusts approved sources.
 *
 * COMPARE WITH VULNERABLE VERSION:
 *   Vulnerable:  $url = $conf['CONFIG_VALUE'] ?? DEFAULT_UPDATE_URL; $jsonData = @file_get_contents($url);
 *   Secure:      $url is validated against a whitelist before fetching.
 */

require_once dirname(__DIR__) . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location:'.APP_BASE.'/login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { http_response_code(403); echo '<h2>403 Forbidden</h2>'; exit; }

$conn    = getDbConnection();
$msg     = '';
$msgType = 'info';

// --- SECURE: Supply Chain Update Check ---
if (isset($_GET['check_updates'])) {
    // Fetch update_url from CONFIG_STORE (still from DB, but will be validated)
    $q = oci_parse($conn, "SELECT config_value FROM CONFIG_STORE WHERE config_key = 'update_url'");
    oci_execute($q);
    $conf = oci_fetch_assoc($q);
    $configuredUrl = $conf['CONFIG_VALUE'] ?? DEFAULT_UPDATE_URL;

    // --- SECURE: Whitelist for update URLs ---
    $allowedUpdateUrls = [
        'http://127.0.0.1:8081/manifest.json', // Local partner server
        'https://cdn.fpt-partner.net/v3/manifest.json', // Official CDN
        'https://api.fpt-partner-cloud.net/v3/updates/manifest.json' // Official API
    ];

    if (!in_array($configuredUrl, $allowedUpdateUrls)) {
        $msg = "🚫 Security Alert: Configured update URL is not from an authorized source: " . htmlspecialchars($configuredUrl);
        $msgType = "danger";
        logAction($_SESSION['user_id'], 'SUPPLY_CHAIN_ALERT', "Unauthorized update URL: $configuredUrl");
    } else {
        $jsonData = @file_get_contents($configuredUrl);
        if ($jsonData) {
            $manifest = json_decode($jsonData, true);
            if (isset($manifest['version'])) {
                // --- SECURE: Digital Signature Verification (conceptual) ---
                // In a real system, you would verify a digital signature of the manifest here
                // e.g., if (!verify_digital_signature($jsonData, $manifest['signature'])) { ... }
                // For this lab, we'll just check version.

                if (version_compare($manifest['version'], APP_VERSION, '>')) {
                    $msg = "🎉 Update Available! New version: " . htmlspecialchars($manifest['version']);
                    $msgType = "success";
                    // In a real system, you'd trigger the update process here.
                } else {
                    $msg = "System is up to date (Current: " . APP_VERSION . ", Partner: " . $manifest['version'] . ")";
                    $msgType = "info";
                }
            } else {
                $msg = "Invalid manifest format from provider."; $msgType = "danger";
            }
        } else {
            $msg = "Could not connect to update server at: " . htmlspecialchars($configuredUrl); $msgType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>[SECURE] Admin Panel – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<?php include dirname(__DIR__) . '/inc_navbar.php'; ?>
<div class="container">
    <h2>⚙️ [SECURE] Admin Panel</h2>
    <div class="alert alert-info" style="border-left-color:#2d6a4f;background:#f0fff4;">
        ✅ This is the <strong>patched version</strong>. Supply Chain attacks via update URL manipulation are mitigated.
    </div>
    <p class="alert alert-info">This panel is restricted to admin users only.</p>

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="info-card" style="border-left: 5px solid #2d6a4f;">
        <h3>🌐 System Update & Supply Chain</h3>
        <p>Current App Version: <strong><?= APP_VERSION ?></strong></p>
        <p>Configured Update URL: <code><?= htmlspecialchars($configuredUrl ?? DEFAULT_UPDATE_URL) ?></code></p>
        <a href="?check_updates=1" class="btn btn-primary">Fetch Partner Manifest (Secure)</a>
        <div class="info-card" style="margin-top:24px;border-left:4px solid #2d6a4f;">
            <h3>🛡️ Security Fix Summary</h3>
            <table class="info-table">
                <tr><th>Issue</th><td>Supply Chain Poisoning: Trusting <code>update_url</code> from DB without validation.</td></tr>
                <tr><th>Fix 1</th><td>Implement a server-side whitelist for all allowed update URLs.</td></tr>
                <tr><th>Fix 2</th><td>(Conceptual) Implement digital signature verification for the manifest file.</td></tr>
                <tr><th>Fix 3</th><td>Fix <code>partner_config.php</code> so low-privileged users cannot modify <code>update_url</code>; validate again here before fetching.</td></tr>
            </table>
            <pre style="margin-top:12px;background:#1a1a2e;color:#e8e8e8;padding:14px;border-radius:8px;font-size:0.82rem;overflow-x:auto;">
<span style="color:#f4a261;">// VULNERABLE:</span>
$url = $conf['CONFIG_VALUE'] ?? DEFAULT_UPDATE_URL;
$jsonData = @file_get_contents($url);

<span style="color:#52b788;">// SECURE:</span>
$allowedUpdateUrls = ['http://127.0.0.1:8081/manifest.json', 'https://cdn.fpt-partner.net/v3/manifest.json'];
if (!in_array($configuredUrl, $allowedUpdateUrls)) {
    // Block unauthorized URL
} else {
    // Fetch and verify digital signature
}
            </pre>
        </div>
    </div>
</div>
</body>
</html>
