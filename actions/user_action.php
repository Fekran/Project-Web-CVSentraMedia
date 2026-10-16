<?php
// user_action.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Restricted to Admin
check_role(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($csrf_token)) {
        set_flash_message('error', 'Token CSRF tidak valid.');
        header("Location: ../pages/pengguna.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'Staff Gudang';

        if (empty($nama) || empty($username) || empty($password)) {
            set_flash_message('error', 'Semua field wajib diisi.');
            header("Location: ../pages/pengguna.php");
            exit;
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (nama, username, password, role) VALUES (:nama, :username, :password, :role)");
            $stmt->execute([
                ':nama' => $nama,
                ':username' => $username,
                ':password' => $hashed_password,
                ':role' => $role
            ]);
            set_flash_message('success', 'Pengguna baru berhasil ditambahkan!');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate key
                set_flash_message('error', 'Username tersebut sudah terdaftar.');
            } else {
                set_flash_message('error', 'Gagal menambahkan pengguna: ' . $e->getMessage());
            }
        }
    } 
    
    elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $role = $_POST['role'] ?? 'Staff Gudang';
        $password = $_POST['password'] ?? ''; // Optional password update

        if ($id <= 0 || empty($nama) || empty($username)) {
            set_flash_message('error', 'Data input tidak valid.');
            header("Location: ../pages/pengguna.php");
            exit;
        }

        try {
            if (!empty($password)) {
                // Update with password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET nama = :nama, username = :username, role = :role, password = :password WHERE id = :id");
                $stmt->execute([
                    ':nama' => $nama,
                    ':username' => $username,
                    ':role' => $role,
                    ':password' => $hashed_password,
                    ':id' => $id
                ]);
            } else {
                // Update without password
                $stmt = $pdo->prepare("UPDATE users SET nama = :nama, username = :username, role = :role WHERE id = :id");
                $stmt->execute([
                    ':nama' => $nama,
                    ':username' => $username,
                    ':role' => $role,
                    ':id' => $id
                ]);
            }

            // Update session if editing self
            if ($id === $_SESSION['user_id']) {
                $_SESSION['nama'] = $nama;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
            }

            set_flash_message('success', 'Data pengguna berhasil diperbarui!');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                set_flash_message('error', 'Username tersebut sudah terdaftar.');
            } else {
                set_flash_message('error', 'Gagal memperbarui data pengguna: ' . $e->getMessage());
            }
        }
    } 
    
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            set_flash_message('error', 'ID tidak valid.');
            header("Location: ../pages/pengguna.php");
            exit;
        }

        // Prevent self-deletion
        if ($id === $_SESSION['user_id']) {
            set_flash_message('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            header("Location: ../pages/pengguna.php");
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            set_flash_message('success', 'Pengguna berhasil dihapus!');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menghapus pengguna. Pengguna ini mungkin memiliki riwayat transaksi.');
        }
    }

    header("Location: ../pages/pengguna.php");
    exit;
} else {
    header("Location: ../pages/pengguna.php");
    exit;
}
?>
