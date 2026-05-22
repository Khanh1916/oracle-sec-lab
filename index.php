<?php
require_once __DIR__ . '/config.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE . '/dashboard.php');
} else {
    header('Location: ' . APP_BASE . '/login.php');
}
exit;
