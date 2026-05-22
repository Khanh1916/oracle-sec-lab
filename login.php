<?php
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {
        $conn = getDbConnection();
        // Parameterized query - login itself is NOT vulnerable
        $sql  = "SELECT user_id, username, password_hash, role, status
                 FROM USERS WHERE username = :uname AND status = 'active'";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':uname', $username);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);

        if ($row && password_verify($password, $row['PASSWORD_HASH'])) {
            $_SESSION['user_id']  = (int)$row['USER_ID'];
            $_SESSION['username'] = $row['USERNAME'];
            $_SESSION['role']     = $row['ROLE'];
            logAction((int)$row['USER_ID'], 'LOGIN_SUCCESS',
                      json_encode(['ua' => $_SERVER['HTTP_USER_AGENT'] ?? '']));
            header('Location: ' . APP_BASE . '/dashboard.php');
            exit;
        } else {
            logAction(null, 'LOGIN_FAILED',
                      json_encode(['attempted_user' => htmlspecialchars($username)]));
            $error = 'Invalid username or password.';
        }
    }
}

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login – FPT Student Portal</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>/style.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <h1>🎓 FPT Student Portal</h1>
            <p>Database Security Lab – DBS401</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       placeholder="e.g. student1"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Login</button>
        </form>

        <div class="auth-hint">
            <small>
                Demo accounts:<br>
                <code>admin / Admin@DBS401!2024</code><br>
                <code>student1 / Student@123</code><br>
                <code>teacher1 / Teacher@123</code>
            </small>
        </div>
    </div>
</div>
</body>
</html>
