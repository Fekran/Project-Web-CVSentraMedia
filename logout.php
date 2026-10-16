<?php
// logout.php
require_once __DIR__ . '/includes/auth.php';

// Clear session variables
$_SESSION = [];

// Destroy session cookies if any
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start fresh session to set flash message
session_start();
set_flash_message('success', 'Anda telah berhasil keluar dari sistem.');
header("Location: login.php");
exit;
?>
