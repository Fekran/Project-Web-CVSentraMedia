<?php
// supplier_action.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Restricted to Admin role
check_role(['Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($csrf_token)) {
        set_flash_message('error', 'Token CSRF tidak valid.');
        header("Location: ../pages/supplier.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama_supplier = trim($_POST['nama_supplier'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $telepon = trim($_POST['telepon'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $kontak_person = trim($_POST['kontak_person'] ?? '');

        if (empty($nama_supplier)) {
            set_flash_message('error', 'Nama supplier tidak boleh kosong.');
            header("Location: ../pages/supplier.php");
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO suppliers (nama_supplier, alamat, telepon, email, kontak_person) VALUES (:nama_supplier, :alamat, :telepon, :email, :kontak_person)");
            $stmt->execute([
                ':nama_supplier' => $nama_supplier,
                ':alamat' => $alamat,
                ':telepon' => $telepon,
                ':email' => $email,
                ':kontak_person' => $kontak_person
            ]);
            set_flash_message('success', 'Supplier baru berhasil ditambahkan!');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menambahkan supplier: ' . $e->getMessage());
        }
    } 
    
    elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $nama_supplier = trim($_POST['nama_supplier'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $telepon = trim($_POST['telepon'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $kontak_person = trim($_POST['kontak_person'] ?? '');

        if ($id <= 0 || empty($nama_supplier)) {
            set_flash_message('error', 'Data input tidak valid.');
            header("Location: ../pages/supplier.php");
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE suppliers SET nama_supplier = :nama_supplier, alamat = :alamat, telepon = :telepon, email = :email, kontak_person = :kontak_person WHERE id = :id");
            $stmt->execute([
                ':nama_supplier' => $nama_supplier,
                ':alamat' => $alamat,
                ':telepon' => $telepon,
                ':email' => $email,
                ':kontak_person' => $kontak_person,
                ':id' => $id
            ]);
            set_flash_message('success', 'Data supplier berhasil diperbarui!');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal memperbarui data supplier: ' . $e->getMessage());
        }
    } 
    
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            set_flash_message('error', 'ID tidak valid.');
            header("Location: ../pages/supplier.php");
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = :id");
            $stmt->execute([':id' => $id]);
            set_flash_message('success', 'Supplier berhasil dihapus!');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menghapus supplier. Supplier ini mungkin digunakan dalam riwayat transaksi barang masuk.');
        }
    }

    header("Location: ../pages/supplier.php");
    exit;
} else {
    header("Location: ../pages/supplier.php");
    exit;
}
?>
