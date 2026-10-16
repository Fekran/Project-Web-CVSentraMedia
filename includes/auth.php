<?php
// Session check and security helper

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF Token if not exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// XSS Protection Helper
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Validate CSRF Token
function validate_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Check if user is logged in
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
}

// Check role permission
function check_role($allowed_roles = []) {
    check_login();
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // Redirect to dashboard with error message
        set_flash_message('error', 'Anda tidak memiliki hak akses untuk halaman tersebut.');
        header("Location: " . BASE_URL . "pages/dashboard.php");
        exit;
    }
}

// Set Flash Message
function set_flash_message($type, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, // 'success' or 'error' or 'warning'
        'message' => $message
    ];
}

// Get and Display Flash Message
function display_flash_message() {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        
        $bgColor = $msg['type'] === 'success' ? 'bg-emerald-500' : ($msg['type'] === 'warning' ? 'bg-amber-500' : 'bg-rose-500');
        echo '<div id="flash-alert" class="fixed top-4 right-4 z-50 ' . $bgColor . ' text-white px-6 py-3 rounded-lg shadow-lg flex items-center justify-between gap-3 animate-fade-in-down transition-all duration-300">
                <div class="flex items-center gap-2">
                    <i class="fas ' . ($msg['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') . ' text-lg"></i>
                    <span class="font-medium">' . e($msg['message']) . '</span>
                </div>
                <button onclick="document.getElementById(\'flash-alert\').remove()" class="text-white hover:text-gray-200 transition-colors focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
              </div>';
    }
}

// Helper to define BASE_URL dynamically
if (!defined('BASE_URL')) {
    // Detect folder depth to properly reference files
    $script_path = $_SERVER['SCRIPT_NAME'];
    $depth = substr_count(str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($_SERVER['SCRIPT_FILENAME'])), DIRECTORY_SEPARATOR);
    
    // Fallback determination
    // Since we are running in subdirectories (like pages/ or actions/), let's determine the relative path or absolute path.
    // In Native PHP, using absolute path relative to host is best, e.g. /inventory-toko-bangunan/
    // Let's dynamically calculate the path:
    $req_uri = $_SERVER['REQUEST_URI'];
    // Find where the project folder is located
    if (strpos($req_uri, '/inventory-toko/') !== false || rtrim($req_uri, '/') === '/inventory-toko' || strpos($req_uri, '/inventory-toko?') !== false) {
        $base = '/inventory-toko/';
    } elseif (strpos($req_uri, '/inventory-toko-bangunan') !== false) {
        $base = '/inventory-toko-bangunan/';
    } else {
        // If hosted at root
        $base = '/';
    }
    define('BASE_URL', $base);
}
?>
