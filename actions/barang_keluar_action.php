<?php
// barang_keluar_action.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Allowed for Admin and Staff Gudang
check_role(['Admin', 'Staff Gudang']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($csrf_token)) {
        set_flash_message('error', 'Token CSRF tidak valid.');
        header("Location: ../pages/barang_keluar.php");
        exit;
    }

    $product_id = intval($_POST['product_id'] ?? 0);
    $jumlah = intval($_POST['jumlah'] ?? 0);
    $tujuan = trim($_POST['tujuan'] ?? '');
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($product_id <= 0 || $jumlah <= 0 || empty($tujuan)) {
        set_flash_message('error', 'Harap isi data produk, jumlah keluar, dan tujuan secara valid.');
        header("Location: ../pages/barang_keluar.php");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Validate Stock Availability
        $stmt_stock = $pdo->prepare("SELECT stok, nama_barang FROM products WHERE id = :id FOR UPDATE");
        $stmt_stock->execute([':id' => $product_id]);
        $product = $stmt_stock->fetch();

        if (!$product) {
            throw new Exception("Produk tidak ditemukan.");
        }

        if ($product['stok'] < $jumlah) {
            set_flash_message('error', 'Stok barang tidak mencukupi.');
            $pdo->rollBack();
            header("Location: ../pages/barang_keluar.php");
            exit;
        }

        // 2. Generate nomor_transaksi: TRK-YYYYMMDD-0001
        $date_prefix = date('Ymd', strtotime($tanggal));
        $like_pattern = "TRK-" . $date_prefix . "-%";
        $stmt_count = $pdo->prepare("SELECT COUNT(*) as count FROM stock_out WHERE nomor_transaksi LIKE :pattern");
        $stmt_count->execute([':pattern' => $like_pattern]);
        $row_count = $stmt_count->fetch();
        $next_number = intval($row_count['count'] ?? 0) + 1;
        $nomor_transaksi = "TRK-" . $date_prefix . "-" . str_pad($next_number, 4, '0', STR_PAD_LEFT);

        // 3. Insert into stock_out
        $stmt_insert = $pdo->prepare("INSERT INTO stock_out (nomor_transaksi, tanggal, product_id, jumlah, tujuan, keterangan, created_by) 
                                     VALUES (:nomor_transaksi, :tanggal, :product_id, :jumlah, :tujuan, :keterangan, :created_by)");
        $stmt_insert->execute([
            ':nomor_transaksi' => $nomor_transaksi,
            ':tanggal' => $tanggal,
            ':product_id' => $product_id,
            ':jumlah' => $jumlah,
            ':tujuan' => $tujuan,
            ':keterangan' => $keterangan,
            ':created_by' => $user_id
        ]);

        // 4. Update product stock (stok = stok - jumlah)
        $stmt_update = $pdo->prepare("UPDATE products SET stok = stok - :jumlah WHERE id = :id");
        $stmt_update->execute([
            ':jumlah' => $jumlah,
            ':id' => $product_id
        ]);

        $pdo->commit();
        set_flash_message('success', 'Transaksi barang keluar berhasil disimpan dengan nomor: ' . $nomor_transaksi);
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash_message('error', 'Gagal menyimpan transaksi: ' . $e->getMessage());
    }

    header("Location: ../pages/barang_keluar.php");
    exit;
} else {
    header("Location: ../pages/barang_keluar.php");
    exit;
}
?>
