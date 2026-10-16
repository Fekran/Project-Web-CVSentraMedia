<?php
// produk_action.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Allowed for both Admin and Staff Gudang
check_role(['Admin', 'Staff Gudang']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($csrf_token)) {
        set_flash_message('error', 'Token CSRF tidak valid.');
        header("Location: ../pages/produk.php");
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama_barang = trim($_POST['nama_barang'] ?? '');
        $kategori_id = !empty($_POST['kategori_id']) ? intval($_POST['kategori_id']) : null;
        $satuan = trim($_POST['satuan'] ?? '');
        $harga_beli = floatval($_POST['harga_beli'] ?? 0);
        $harga_jual = floatval($_POST['harga_jual'] ?? 0);
        $stok = intval($_POST['stok_awal'] ?? 0);
        $stok_minimum = intval($_POST['stok_minimum'] ?? 0);
        $lokasi_rak = trim($_POST['lokasi_rak'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if (empty($nama_barang) || empty($satuan)) {
            set_flash_message('error', 'Nama barang dan satuan wajib diisi.');
            header("Location: ../pages/produk.php");
            exit;
        }

        try {
            // Auto generate kode_barang: BRG-0001
            $stmtCode = $pdo->query("SELECT MAX(id) as max_id FROM products");
            $maxRow = $stmtCode->fetch();
            $nextId = intval($maxRow['max_id'] ?? 0) + 1;
            $kode_barang = "BRG-" . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            // Insert into products
            $stmt = $pdo->prepare("INSERT INTO products (kode_barang, nama_barang, kategori_id, satuan, harga_beli, harga_jual, stok, stok_minimum, lokasi_rak, deskripsi) 
                                   VALUES (:kode_barang, :nama_barang, :kategori_id, :satuan, :harga_beli, :harga_jual, :stok, :stok_minimum, :lokasi_rak, :deskripsi)");
            $stmt->execute([
                ':kode_barang' => $kode_barang,
                ':nama_barang' => $nama_barang,
                ':kategori_id' => $kategori_id,
                ':satuan' => $satuan,
                ':harga_beli' => $harga_beli,
                ':harga_jual' => $harga_jual,
                ':stok' => $stok,
                ':stok_minimum' => $stok_minimum,
                ':lokasi_rak' => $lokasi_rak,
                ':deskripsi' => $deskripsi
            ]);
            
            set_flash_message('success', 'Produk baru berhasil ditambahkan dengan kode: ' . $kode_barang);
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menambahkan produk: ' . $e->getMessage());
        }
    } 
    
    elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $nama_barang = trim($_POST['nama_barang'] ?? '');
        $kategori_id = !empty($_POST['kategori_id']) ? intval($_POST['kategori_id']) : null;
        $satuan = trim($_POST['satuan'] ?? '');
        $harga_beli = floatval($_POST['harga_beli'] ?? 0);
        $harga_jual = floatval($_POST['harga_jual'] ?? 0);
        $stok_minimum = intval($_POST['stok_minimum'] ?? 0);
        $lokasi_rak = trim($_POST['lokasi_rak'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        // Note: Stok is managed through transactions (Barang Masuk/Keluar/Opname) but can be updated here too.
        $stok = intval($_POST['stok'] ?? 0);

        if ($id <= 0 || empty($nama_barang) || empty($satuan)) {
            set_flash_message('error', 'Data input tidak valid.');
            header("Location: ../pages/produk.php");
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE products SET 
                                    nama_barang = :nama_barang, 
                                    kategori_id = :kategori_id, 
                                    satuan = :satuan, 
                                    harga_beli = :harga_beli, 
                                    harga_jual = :harga_jual, 
                                    stok = :stok,
                                    stok_minimum = :stok_minimum, 
                                    lokasi_rak = :lokasi_rak, 
                                    deskripsi = :deskripsi 
                                   WHERE id = :id");
            $stmt->execute([
                ':nama_barang' => $nama_barang,
                ':kategori_id' => $kategori_id,
                ':satuan' => $satuan,
                ':harga_beli' => $harga_beli,
                ':harga_jual' => $harga_jual,
                ':stok' => $stok,
                ':stok_minimum' => $stok_minimum,
                ':lokasi_rak' => $lokasi_rak,
                ':deskripsi' => $deskripsi,
                ':id' => $id
            ]);
            
            set_flash_message('success', 'Produk berhasil diperbarui!');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal memperbarui produk: ' . $e->getMessage());
        }
    } 
    
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            set_flash_message('error', 'ID tidak valid.');
            header("Location: ../pages/produk.php");
            exit;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
            $stmt->execute([':id' => $id]);
            set_flash_message('success', 'Produk berhasil dihapus!');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menghapus produk. Produk ini mungkin memiliki riwayat transaksi.');
        }
    }

    header("Location: ../pages/produk.php");
    exit;
} else {
    header("Location: ../pages/produk.php");
    exit;
}
?>
