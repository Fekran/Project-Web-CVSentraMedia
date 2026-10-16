<?php
// Entry Point Dispatcher
require_once __DIR__ . '/includes/auth.php';

if (isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
} else {
    header("Location: login.php");
}
exit;
?>
