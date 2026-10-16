<?php
// opname_action.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Allowed for Admin and Staff Gudang
check_role(['Admin', 'Staff Gudang']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($csrf_token)) {
        set_flash_message('error', 'Token CSRF tidak valid.');
        header("Location: ../pages/stok_opname.php");
        exit;
    }

    $product_id = intval($_POST['product_id'] ?? 0);
    $stok_sistem = intval($_POST['stok_sistem'] ?? 0);
    $stok_fisik = intval($_POST['stok_fisik'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($product_id <= 0) {
        set_flash_message('error', 'Harap pilih produk yang valid.');
        header("Location: ../pages/stok_opname.php");
        exit;
    }

    // Calculate difference
    $selisih = $stok_fisik - $stok_sistem;

    try {
        $pdo->beginTransaction();

        // 1. Double check current system stock to avoid concurrency issues
        $stmt_check = $pdo->prepare("SELECT stok FROM products WHERE id = :id FOR UPDATE");
        $stmt_check->execute([':id' => $product_id]);
        $current_product = $stmt_check->fetch();
        if ($current_product) {
            $stok_sistem = intval($current_product['stok']);
            $selisih = $stok_fisik - $stok_sistem; // Recalculate based on locked value
        }

        // 2. Insert into stock_opname
        $stmt_insert = $pdo->prepare("INSERT INTO stock_opname (tanggal, product_id, stok_sistem, stok_fisik, selisih, keterangan, created_by) 
                                     VALUES (:tanggal, :product_id, :stok_sistem, :stok_fisik, :selisih, :keterangan, :created_by)");
        $stmt_insert->execute([
            ':tanggal' => $tanggal,
            ':product_id' => $product_id,
            ':stok_sistem' => $stok_sistem,
            ':stok_fisik' => $stok_fisik,
            ':selisih' => $selisih,
            ':keterangan' => $keterangan,
            ':created_by' => $user_id
        ]);

        // 3. Adjust product stock to match physical stock
        $stmt_update = $pdo->prepare("UPDATE products SET stok = :stok_fisik WHERE id = :id");
        $stmt_update->execute([
            ':stok_fisik' => $stok_fisik,
            ':id' => $product_id
        ]);

        $pdo->commit();
        set_flash_message('success', 'Transaksi Stok Opname berhasil disimpan! Stok disesuaikan menjadi: ' . $stok_fisik);
    } catch (PDOException $e) {
        $pdo->rollBack();
        set_flash_message('error', 'Gagal menyimpan stok opname: ' . $e->getMessage());
    }

    header("Location: ../pages/stok_opname.php");
    exit;
} else {
    header("Location: ../pages/stok_opname.php");
    exit;
}
?>
