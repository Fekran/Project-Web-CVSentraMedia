<?php
// pengaturan_action.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Restricted to Admin
check_role(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($csrf_token)) {
        set_flash_message('error', 'Token CSRF tidak valid.');
        header("Location: ../pages/pengaturan.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. UPDATE SHOP PROFILE & LOGO
    if ($action === 'update_toko') {
        $nama_toko = trim($_POST['nama_toko'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $telepon = trim($_POST['telepon'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($nama_toko)) {
            set_flash_message('error', 'Nama toko wajib diisi.');
            header("Location: ../pages/pengaturan.php");
            exit;
        }

        try {
            // Check logo upload
            $logo_name = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['logo']['tmp_name'];
                $file_name = $_FILES['logo']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_exts = ['png', 'jpg', 'jpeg', 'gif'];
                
                if (!in_array($file_ext, $allowed_exts)) {
                    throw new Exception("Format logo tidak didukung. Harap gunakan png, jpg, jpeg, atau gif.");
                }

                // Check directory
                $upload_dir = __DIR__ . '/../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $new_logo_name = "logo_" . time() . "." . $file_ext;
                $dest_path = $upload_dir . $new_logo_name;

                if (move_uploaded_file($file_tmp, $dest_path)) {
                    $logo_name = $new_logo_name;
                    
                    // Delete old logo if exist
                    $old_logo_stmt = $pdo->query("SELECT logo FROM settings WHERE id = 1 LIMIT 1");
                    $old_logo = $old_logo_stmt->fetchColumn();
                    if ($old_logo && file_exists($upload_dir . $old_logo)) {
                        unlink($upload_dir . $old_logo);
                    }
                } else {
                    throw new Exception("Gagal menyimpan file logo.");
                }
            }

            // Update Database settings table
            if ($logo_name) {
                $stmt = $pdo->prepare("UPDATE settings SET nama_toko = :nama_toko, alamat = :alamat, telepon = :telepon, email = :email, logo = :logo WHERE id = 1");
                $stmt->execute([
                    ':nama_toko' => $nama_toko,
                    ':alamat' => $alamat,
                    ':telepon' => $telepon,
                    ':email' => $email,
                    ':logo' => $logo_name
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE settings SET nama_toko = :nama_toko, alamat = :alamat, telepon = :telepon, email = :email WHERE id = 1");
                $stmt->execute([
                    ':nama_toko' => $nama_toko,
                    ':alamat' => $alamat,
                    ':telepon' => $telepon,
                    ':email' => $email
                ]);
            }

            set_flash_message('success', 'Pengaturan toko berhasil disimpan!');
        } catch (Exception $e) {
            set_flash_message('error', $e->getMessage());
        }
    } 
    
    // 2. CHANGE PASSWORD
    elseif ($action === 'ganti_password') {
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
        $user_id = $_SESSION['user_id'];

        if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
            set_flash_message('error', 'Semua input kata sandi wajib diisi.');
            header("Location: ../pages/pengaturan.php");
            exit;
        }

        if ($password_baru !== $konfirmasi_password) {
            set_flash_message('error', 'Konfirmasi kata sandi baru tidak sesuai.');
            header("Location: ../pages/pengaturan.php");
            exit;
        }

        try {
            // Get current password hash
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $user_id]);
            $current_hash = $stmt->fetchColumn();

            if ($current_hash && password_verify($password_lama, $current_hash)) {
                // Update password
                $new_hash = password_hash($password_baru, PASSWORD_BCRYPT);
                $stmt_up = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt_up->execute([
                    ':password' => $new_hash,
                    ':id' => $user_id
                ]);
                set_flash_message('success', 'Kata sandi Anda berhasil diubah!');
            } else {
                set_flash_message('error', 'Kata sandi lama salah.');
            }
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal merubah password: ' . $e->getMessage());
        }
    }

    header("Location: ../pages/pengaturan.php");
    exit;
} else {
    header("Location: ../pages/pengaturan.php");
    exit;
}
?>
