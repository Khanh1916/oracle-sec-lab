<?php
// logout.php
require_once __DIR__ . '/config.php';
if (!empty($_SESSION['user_id'])) {
    logAction($_SESSION['user_id'], 'LOGOUT', '');
}
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
header('Location: ' . APP_BASE . '/login.php');
exit;
