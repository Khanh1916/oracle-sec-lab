<?php
// logout.php
require_once __DIR__ . '/config.php';
if (!empty($_SESSION['user_id'])) {
    logAction($_SESSION['user_id'], 'LOGOUT', '');
}
session_destroy();
header('Location: ' . APP_BASE . '/login.php');
exit;
