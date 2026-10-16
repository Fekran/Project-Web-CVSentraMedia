<?php
// barang_masuk_action.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Allowed for Admin and Staff Gudang
check_role(['Admin', 'Staff Gudang']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($csrf_token)) {
        set_flash_message('error', 'Token CSRF tidak valid.');
        header("Location: ../pages/barang_masuk.php");
        exit;
    }

    $product_id = intval($_POST['product_id'] ?? 0);
    $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
    $jumlah = intval($_POST['jumlah'] ?? 0);
    $harga_beli = floatval($_POST['harga_beli'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($product_id <= 0 || $jumlah <= 0 || $harga_beli <= 0) {
        set_flash_message('error', 'Harap isi data produk, jumlah, dan harga beli secara valid.');
        header("Location: ../pages/barang_masuk.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Generate nomor_transaksi: TRM-YYYYMMDD-0001
        $date_prefix = date('Ymd', strtotime($tanggal));
        $like_pattern = "TRM-" . $date_prefix . "-%";
        $stmt_count = $pdo->prepare("SELECT COUNT(*) as count FROM stock_in WHERE nomor_transaksi LIKE :pattern");
        $stmt_count->execute([':pattern' => $like_pattern]);
        $row_count = $stmt_count->fetch();
        $next_number = intval($row_count['count'] ?? 0) + 1;
        $nomor_transaksi = "TRM-" . $date_prefix . "-" . str_pad($next_number, 4, '0', STR_PAD_LEFT);

        // 2. Insert into stock_in
        $stmt_insert = $pdo->prepare("INSERT INTO stock_in (nomor_transaksi, tanggal, product_id, supplier_id, jumlah, harga_beli, keterangan, created_by) 
                                     VALUES (:nomor_transaksi, :tanggal, :product_id, :supplier_id, :jumlah, :harga_beli, :keterangan, :created_by)");
        $stmt_insert->execute([
            ':nomor_transaksi' => $nomor_transaksi,
            ':tanggal' => $tanggal,
            ':product_id' => $product_id,
            ':supplier_id' => $supplier_id,
            ':jumlah' => $jumlah,
            ':harga_beli' => $harga_beli,
            ':keterangan' => $keterangan,
            ':created_by' => $user_id
        ]);

        // 3. Update product stock and optionally purchase price (stok = stok + jumlah)
        $stmt_update = $pdo->prepare("UPDATE products SET stok = stok + :jumlah, harga_beli = :harga_beli WHERE id = :id");
        $stmt_update->execute([
            ':jumlah' => $jumlah,
            ':harga_beli' => $harga_beli,
            ':id' => $product_id
        ]);

        $pdo->commit();
        set_flash_message('success', 'Transaksi barang masuk berhasil disimpan dengan nomor: ' . $nomor_transaksi);
    } catch (PDOException $e) {
        $pdo->rollBack();
        set_flash_message('error', 'Gagal menyimpan transaksi: ' . $e->getMessage());
    }

    header("Location: ../pages/barang_masuk.php");
    exit;
} else {
    header("Location: ../pages/barang_masuk.php");
    exit;
}
?>
