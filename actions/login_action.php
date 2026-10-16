<?php
// login_action.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($csrf_token)) {
        set_flash_message('error', 'Token CSRF tidak valid. Silakan coba lagi.');
        header("Location: ../login.php");
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        set_flash_message('error', 'Username dan password wajib diisi.');
        header("Location: ../login.php");
        exit;
    }

    try {
        // Find user in database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session id for security
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];

            set_flash_message('success', 'Selamat datang kembali, ' . $user['nama'] . '!');
            header("Location: ../pages/dashboard.php");
            exit;
        } else {
            set_flash_message('error', 'Username atau password salah.');
            header("Location: ../login.php");
            exit;
        }
    } catch (PDOException $e) {
        set_flash_message('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        header("Location: ../login.php");
        exit;
    }
} else {
    // If accessed directly, redirect to login page
    header("Location: ../login.php");
    exit;
}
?>
