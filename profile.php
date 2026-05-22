<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user_id'])) { header('Location:'.APP_BASE.'/login.php'); exit; }

$conn   = getDbConnection();
$userId = $_SESSION['user_id'];
$sql    = "SELECT u.user_id, u.username, u.role, u.status, u.created_at,
                  s.full_name, s.email, s.major, s.gpa, s.phone, s.address
           FROM USERS u LEFT JOIN STUDENTS s ON s.user_id = u.user_id
           WHERE u.user_id = :uid";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':uid', $userId);
oci_execute($stmt);
$profile = oci_fetch_assoc($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Profile – FPT Student Portal</title>
<link rel="stylesheet" href="<?= APP_BASE ?>/style.css"></head>
<body>
<?php include __DIR__ . '/inc_navbar.php'; ?>
<div class="container">
    <h2>👤 My Profile</h2>
    <div class="info-card">
        <table class="info-table">
            <tr><th>Username</th><td><?= htmlspecialchars($profile['USERNAME']) ?></td></tr>
            <tr><th>Role</th><td><span class="badge badge-<?= $profile['ROLE'] ?>"><?= htmlspecialchars($profile['ROLE']) ?></span></td></tr>
            <tr><th>Status</th><td><?= htmlspecialchars($profile['STATUS']) ?></td></tr>
            <tr><th>Full Name</th><td><?= htmlspecialchars($profile['FULL_NAME'] ?? 'N/A') ?></td></tr>
            <tr><th>Email</th><td><?= htmlspecialchars($profile['EMAIL'] ?? 'N/A') ?></td></tr>
            <tr><th>Major</th><td><?= htmlspecialchars($profile['MAJOR'] ?? 'N/A') ?></td></tr>
            <tr><th>GPA</th><td><?= htmlspecialchars($profile['GPA'] ?? 'N/A') ?></td></tr>
            <tr><th>Phone</th><td><?= htmlspecialchars($profile['PHONE'] ?? 'N/A') ?></td></tr>
            <tr><th>Address</th><td><?= htmlspecialchars($profile['ADDRESS'] ?? 'N/A') ?></td></tr>
            <tr><th>Member Since</th><td><?= htmlspecialchars($profile['CREATED_AT']) ?></td></tr>
        </table>
    </div>
</div>
</body>
</html>
