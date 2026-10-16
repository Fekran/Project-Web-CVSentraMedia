<?php
// laporan.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Allowed for Admin only? The request says: "Staff Gudang: Dashboard, Produk, Barang Masuk, Barang Keluar, Stok Opname. Tidak dapat mengakses pengaturan dan pengguna."
// It does not list Laporan for Staff Gudang. So check_role(['Admin']) is correct.
check_role(['Admin']);

// Retrieve Shop Settings for Report Header
try {
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1 LIMIT 1");
    $settings = $stmt->fetch() ?: [
        'nama_toko' => 'Toko Bangunan Utama',
        'alamat' => '',
        'telepon' => '',
        'email' => ''
    ];
} catch (PDOException $e) {
    $settings = ['nama_toko' => 'Toko Bangunan Utama', 'alamat' => '', 'telepon' => '', 'email' => ''];
}

// Filters
$report_type = $_GET['report_type'] ?? 'stok'; // 'stok', 'masuk', 'keluar', 'opname', 'menipis', 'nilai_persediaan'
$time_filter = $_GET['time_filter'] ?? 'semua'; // 'semua', 'hari_ini', 'minggu_ini', 'bulan_ini', 'tahun_ini', 'custom'
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build Query Date Condition
$date_cond = "";
$date_params = [];

if (in_array($report_type, ['masuk', 'keluar', 'opname'])) {
    $date_col = ($report_type === 'opname') ? 'tanggal' : 'tanggal';
    
    if ($time_filter === 'hari_ini') {
        $date_cond = " AND $date_col = CURDATE()";
    } elseif ($time_filter === 'minggu_ini') {
        $date_cond = " AND $date_col >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($time_filter === 'bulan_ini') {
        $date_cond = " AND DATE_FORMAT($date_col, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
    } elseif ($time_filter === 'tahun_ini') {
        $date_cond = " AND YEAR($date_col) = YEAR(CURDATE())";
    } elseif ($time_filter === 'custom' && !empty($start_date) && !empty($end_date)) {
        $date_cond = " AND $date_col BETWEEN :start_date AND :end_date";
        $date_params[':start_date'] = $start_date;
        $date_params[':end_date'] = $end_date;
    }
}

// Fetch Report Data
$report_data = [];
try {
    if ($report_type === 'stok') {
        $q = "SELECT p.*, c.nama_kategori FROM products p LEFT JOIN categories c ON p.kategori_id = c.id ORDER BY p.kode_barang ASC";
        $stmt = $pdo->prepare($q);
        $stmt->execute();
        $report_data = $stmt->fetchAll();
    } 
    
    elseif ($report_type === 'masuk') {
        $q = "SELECT s.*, p.kode_barang, p.nama_barang, p.satuan, sup.nama_supplier, u.nama as nama_user 
              FROM stock_in s
              JOIN products p ON s.product_id = p.id
              LEFT JOIN suppliers sup ON s.supplier_id = sup.id
              LEFT JOIN users u ON s.created_by = u.id
              WHERE 1=1 $date_cond 
              ORDER BY s.tanggal DESC, s.id DESC";
        $stmt = $pdo->prepare($q);
        $stmt->execute($date_params);
        $report_data = $stmt->fetchAll();
    } 
    
    elseif ($report_type === 'keluar') {
        $q = "SELECT s.*, p.kode_barang, p.nama_barang, p.satuan, u.nama as nama_user 
              FROM stock_out s
              JOIN products p ON s.product_id = p.id
              LEFT JOIN users u ON s.created_by = u.id
              WHERE 1=1 $date_cond 
              ORDER BY s.tanggal DESC, s.id DESC";
        $stmt = $pdo->prepare($q);
        $stmt->execute($date_params);
        $report_data = $stmt->fetchAll();
    } 
    
    elseif ($report_type === 'opname') {
        $q = "SELECT o.*, p.kode_barang, p.nama_barang, p.satuan, u.nama as nama_user 
              FROM stock_opname o
              JOIN products p ON o.product_id = p.id
              LEFT JOIN users u ON o.created_by = u.id
              WHERE 1=1 $date_cond 
              ORDER BY o.tanggal DESC, o.id DESC";
        $stmt = $pdo->prepare($q);
        $stmt->execute($date_params);
        $report_data = $stmt->fetchAll();
    } 
    
    elseif ($report_type === 'menipis') {
        $q = "SELECT p.*, c.nama_kategori 
              FROM products p 
              LEFT JOIN categories c ON p.kategori_id = c.id 
              WHERE p.stok <= p.stok_minimum
              ORDER BY p.stok ASC";
        $stmt = $pdo->prepare($q);
        $stmt->execute();
        $report_data = $stmt->fetchAll();
    } 
    
    elseif ($report_type === 'nilai_persediaan') {
        $q = "SELECT p.*, c.nama_kategori, (p.stok * p.harga_beli) as total_nilai 
              FROM products p 
              LEFT JOIN categories c ON p.kategori_id = c.id 
              ORDER BY total_nilai DESC";
        $stmt = $pdo->prepare($q);
        $stmt->execute();
        $report_data = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    die("Gagal memproses data laporan: " . $e->getMessage());
}

// ---------------- EXPORT EXCEL ----------------
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filename = "Laporan_" . ucfirst($report_type) . "_" . date('Ymd_His') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=$filename");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // Render Excel Table
    ?>
    <table border="1">
        <tr>
            <th colspan="<?php echo $report_type === 'nilai_persediaan' ? '7' : ($report_type === 'opname' ? '7' : '6'); ?>" style="text-align: center; font-weight: bold; font-size: 14px;">
                LAPORAN <?php echo strtoupper(str_replace('_', ' ', $report_type)); ?>
            </th>
        </tr>
        <tr>
            <th colspan="<?php echo $report_type === 'nilai_persediaan' ? '7' : ($report_type === 'opname' ? '7' : '6'); ?>" style="text-align: center; font-size: 12px;">
                <?php echo strtoupper($settings['nama_toko']); ?>
            </th>
        </tr>
        <tr>
            <th colspan="<?php echo $report_type === 'nilai_persediaan' ? '7' : ($report_type === 'opname' ? '7' : '6'); ?>" style="text-align: center; font-size: 10px;">
                Filter: <?php echo ucfirst($time_filter); ?> <?php echo ($time_filter==='custom') ? "($start_date s/d $end_date)" : ''; ?>
            </th>
        </tr>
        <tr><td colspan="<?php echo $report_type === 'nilai_persediaan' ? '7' : ($report_type === 'opname' ? '7' : '6'); ?>"></td></tr>
        
        <?php if ($report_type === 'stok' || $report_type === 'menipis'): ?>
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th>No.</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Satuan</th>
                    <th>Harga Beli (Rp)</th>
                    <th>Harga Jual (Rp)</th>
                    <th>Stok</th>
                    <th>Min. Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; foreach($report_data as $row): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $row['kode_barang']; ?></td>
                    <td><?php echo $row['nama_barang']; ?></td>
                    <td><?php echo $row['nama_kategori'] ?: 'Lainnya'; ?></td>
                    <td><?php echo $row['satuan']; ?></td>
                    <td><?php echo $row['harga_beli']; ?></td>
                    <td><?php echo $row['harga_jual']; ?></td>
                    <td><?php echo $row['stok']; ?></td>
                    <td><?php echo $row['stok_minimum']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        <?php elseif ($report_type === 'masuk'): ?>
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th>No.</th>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Barang</th>
                    <th>Supplier</th>
                    <th>Jumlah</th>
                    <th>Harga Beli (Rp)</th>
                    <th>Total (Rp)</th>
                    <th>Keterangan</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; foreach($report_data as $row): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $row['nomor_transaksi']; ?></td>
                    <td><?php echo $row['tanggal']; ?></td>
                    <td><?php echo $row['kode_barang'] . ' - ' . $row['nama_barang']; ?></td>
                    <td><?php echo $row['nama_supplier'] ?: 'Umum'; ?></td>
                    <td><?php echo $row['jumlah']; ?></td>
                    <td><?php echo $row['harga_beli']; ?></td>
                    <td><?php echo $row['jumlah'] * $row['harga_beli']; ?></td>
                    <td><?php echo $row['keterangan'] ?: '-'; ?></td>
                    <td><?php echo $row['nama_user'] ?: 'Sistem'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        <?php elseif ($report_type === 'keluar'): ?>
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th>No.</th>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Barang</th>
                    <th>Tujuan / Pelanggan</th>
                    <th>Jumlah Keluar</th>
                    <th>Keterangan</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; foreach($report_data as $row): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $row['nomor_transaksi']; ?></td>
                    <td><?php echo $row['tanggal']; ?></td>
                    <td><?php echo $row['kode_barang'] . ' - ' . $row['nama_barang']; ?></td>
                    <td><?php echo $row['tujuan']; ?></td>
                    <td><?php echo $row['jumlah']; ?></td>
                    <td><?php echo $row['keterangan'] ?: '-'; ?></td>
                    <td><?php echo $row['nama_user'] ?: 'Sistem'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        <?php elseif ($report_type === 'opname'): ?>
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th>No.</th>
                    <th>Tanggal</th>
                    <th>Barang</th>
                    <th>Stok Sistem</th>
                    <th>Stok Fisik</th>
                    <th>Selisih</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; foreach($report_data as $row): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $row['tanggal']; ?></td>
                    <td><?php echo $row['kode_barang'] . ' - ' . $row['nama_barang']; ?></td>
                    <td><?php echo $row['stok_sistem']; ?></td>
                    <td><?php echo $row['stok_fisik']; ?></td>
                    <td><?php echo $row['selisih']; ?></td>
                    <td><?php echo $row['selisih'] == 0 ? 'Sesuai' : ($row['selisih'] > 0 ? 'Lebih' : 'Kurang'); ?></td>
                    <td><?php echo $row['keterangan'] ?: '-'; ?></td>
                    <td><?php echo $row['nama_user'] ?: 'Sistem'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        <?php elseif ($report_type === 'nilai_persediaan'): ?>
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th>No.</th>
                    <th>Kode Barang</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Harga Beli (Rp)</th>
                    <th>Nilai Persediaan (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; $total_val=0; foreach($report_data as $row): $total_val += $row['total_nilai']; ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $row['kode_barang']; ?></td>
                    <td><?php echo $row['nama_barang']; ?></td>
                    <td><?php echo $row['nama_kategori'] ?: 'Lainnya'; ?></td>
                    <td><?php echo $row['stok']; ?></td>
                    <td><?php echo $row['harga_beli']; ?></td>
                    <td><?php echo $row['total_nilai']; ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="background-color: #f2f2f2; font-weight: bold;">
                    <td colspan="6" style="text-align: right;">Total Nilai Aset Inventory:</td>
                    <td><?php echo $total_val; ?></td>
                </tr>
            </tbody>
        <?php endif; ?>
    </table>
    <?php
    exit;
}

// Include regular Header
include_once __DIR__ . '/../includes/header.php';
?>

<!-- Custom CSS for Printing -->
<style>
    @media print {
        body {
            background: #ffffff !important;
            color: #000000 !important;
            font-size: 11px !important;
        }
        /* Hide everything except report-print-area */
        aside, header, footer, #filter-card, .btn-print-hide {
            display: none !important;
        }
        main {
            padding: 0 !important;
            margin: 0 !important;
        }
        #report-print-area {
            display: block !important;
            background: #ffffff !important;
            color: #000000 !important;
            padding: 20px !important;
            border: none !important;
        }
        table {
            border-collapse: collapse !important;
            width: 100% !important;
            color: #000000 !important;
        }
        th, td {
            border: 1px solid #000000 !important;
            padding: 6px 8px !important;
            color: #000000 !important;
        }
        th {
            background-color: #f3f4f6 !important;
        }
        .text-white { color: #000000 !important; }
        .text-slate-400 { color: #555555 !important; }
        .text-indigo-400 { color: #000000 !important; }
        .text-emerald-400 { color: #000000 !important; }
    }
</style>

<div class="space-y-6">
    <!-- Header Title -->
    <div class="flex items-center justify-between btn-print-hide">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight font-outfit">Laporan Inventory</h1>
            <p class="text-sm text-slate-400">Analisis dan ekspor data stok, barang masuk/keluar, opname, serta nilai aset</p>
        </div>
    </div>

    <!-- Filter Card (Form) -->
    <div id="filter-card" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl btn-print-hide">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- 1. Report Type -->
            <div>
                <label for="report_type" class="block text-xs font-semibold text-slate-450 uppercase tracking-wider mb-2">Jenis Laporan</label>
                <select name="report_type" id="report_type" onchange="toggleDateFilters()"
                    class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="stok" <?php echo $report_type === 'stok' ? 'selected' : ''; ?>>Laporan Stok Barang</option>
                    <option value="masuk" <?php echo $report_type === 'masuk' ? 'selected' : ''; ?>>Laporan Barang Masuk</option>
                    <option value="keluar" <?php echo $report_type === 'keluar' ? 'selected' : ''; ?>>Laporan Barang Keluar</option>
                    <option value="opname" <?php echo $report_type === 'opname' ? 'selected' : ''; ?>>Laporan Stok Opname</option>
                    <option value="menipis" <?php echo $report_type === 'menipis' ? 'selected' : ''; ?>>Laporan Stok Menipis</option>
                    <option value="nilai_persediaan" <?php echo $report_type === 'nilai_persediaan' ? 'selected' : ''; ?>>Laporan Nilai Persediaan</option>
                </select>
            </div>

            <!-- 2. Time Filter -->
            <div id="time-filter-wrapper">
                <label for="time_filter" class="block text-xs font-semibold text-slate-450 uppercase tracking-wider mb-2">Periode Waktu</label>
                <select name="time_filter" id="time_filter" onchange="toggleCustomDates()"
                    class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="semua" <?php echo $time_filter === 'semua' ? 'selected' : ''; ?>>Semua Waktu</option>
                    <option value="hari_ini" <?php echo $time_filter === 'hari_ini' ? 'selected' : ''; ?>>Harian (Hari Ini)</option>
                    <option value="minggu_ini" <?php echo $time_filter === 'minggu_ini' ? 'selected' : ''; ?>>Mingguan (7 Hari Terakhir)</option>
                    <option value="bulan_ini" <?php echo $time_filter === 'bulan_ini' ? 'selected' : ''; ?>>Bulanan (Bulan Ini)</option>
                    <option value="tahun_ini" <?php echo $time_filter === 'tahun_ini' ? 'selected' : ''; ?>>Tahunan (Tahun Ini)</option>
                    <option value="custom" <?php echo $time_filter === 'custom' ? 'selected' : ''; ?>>Rentang Tanggal</option>
                </select>
            </div>

            <!-- 3. Custom Date Range -->
            <div id="custom-dates" class="md:col-span-2 grid grid-cols-2 gap-4 <?php echo $time_filter === 'custom' ? '' : 'hidden'; ?>">
                <div>
                    <label for="start_date" class="block text-xs font-semibold text-slate-450 uppercase tracking-wider mb-2">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo e($start_date); ?>"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-semibold text-slate-450 uppercase tracking-wider mb-2">Tanggal Selesai</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo e($end_date); ?>"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
            </div>

            <!-- 4. Submit & Action Buttons -->
            <div class="md:col-span-4 flex flex-wrap gap-2 pt-2 border-t border-slate-850">
                <button type="submit" 
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl text-sm transition-all">
                    <i class="fas fa-filter mr-2"></i> Terapkan Filter
                </button>
                <button type="button" onclick="window.print()"
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-medium rounded-xl text-sm transition-all">
                    <i class="fas fa-print mr-2"></i> Cetak / Simpan PDF
                </button>
                <a href="?report_type=<?php echo $report_type; ?>&time_filter=<?php echo $time_filter; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export=excel"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl text-sm transition-all inline-flex items-center">
                    <i class="fas fa-file-excel mr-2"></i> Ekspor ke Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Print Report Area -->
    <div id="report-print-area" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <!-- Print Header (Hidden on screen, styled by CSS print rules) -->
        <div class="border-b-2 border-slate-800 pb-4 mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-white uppercase font-outfit"><?php echo e($settings['nama_toko']); ?></h2>
                <p class="text-xs text-slate-400 mt-1"><?php echo e($settings['alamat'] ?: 'Alamat Toko'); ?></p>
                <p class="text-xs text-slate-400">Telp: <?php echo e($settings['telepon'] ?: '-'); ?> | Email: <?php echo e($settings['email'] ?: '-'); ?></p>
            </div>
            <div class="text-right">
                <h1 class="text-lg font-bold text-indigo-400 tracking-wide">LAPORAN TRANSACTIONAL</h1>
                <p class="text-xs text-slate-500 uppercase mt-1">Jenis: <?php echo str_replace('_', ' ', $report_type); ?></p>
                <p class="text-[10px] text-slate-500">Dicetak: <?php echo date('d-m-Y H:i:s'); ?></p>
            </div>
        </div>

        <!-- Dynamic Tables based on report type -->
        <div class="overflow-x-auto">
            <?php if ($report_type === 'stok' || $report_type === 'menipis'): ?>
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-950/50 border-b border-slate-800 text-slate-400 font-medium uppercase">
                            <th class="px-4 py-3 w-12">No.</th>
                            <th class="px-4 py-3">Kode Barang</th>
                            <th class="px-4 py-3">Nama Barang</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Satuan</th>
                            <th class="px-4 py-3 text-right">Harga Beli</th>
                            <th class="px-4 py-3 text-right">Harga Jual</th>
                            <th class="px-4 py-3 text-center">Stok</th>
                            <th class="px-4 py-3 text-center">Stok Min.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        <?php $i=1; foreach ($report_data as $row): ?>
                        <tr class="hover:bg-slate-800/10">
                            <td class="px-4 py-2.5 text-slate-500"><?php echo $i++; ?></td>
                            <td class="px-4 py-2.5 font-mono text-indigo-400 font-semibold"><?php echo e($row['kode_barang']); ?></td>
                            <td class="px-4 py-2.5 font-medium text-white"><?php echo e($row['nama_barang']); ?></td>
                            <td class="px-4 py-2.5 text-slate-300"><?php echo e($row['nama_kategori'] ?: 'Lainnya'); ?></td>
                            <td class="px-4 py-2.5 text-slate-400"><?php echo e($row['satuan']); ?></td>
                            <td class="px-4 py-2.5 text-right font-mono">Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                            <td class="px-4 py-2.5 text-right font-mono text-emerald-400">Rp <?php echo number_format($row['harga_jual'], 0, ',', '.'); ?></td>
                            <td class="px-4 py-2.5 text-center font-bold text-white"><?php echo e($row['stok']); ?></td>
                            <td class="px-4 py-2.5 text-center text-slate-500 font-mono"><?php echo e($row['stok_minimum']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($report_data)): ?>
                        <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">Tidak ada data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif ($report_type === 'masuk'): ?>
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-950/50 border-b border-slate-800 text-slate-400 font-medium uppercase">
                            <th class="px-4 py-3 w-12">No.</th>
                            <th class="px-4 py-3">No. Transaksi</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Barang</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3 text-right">Jumlah</th>
                            <th class="px-4 py-3 text-right">Harga Beli</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        <?php $i=1; $tot_qty=0; $tot_val=0; foreach ($report_data as $row): 
                            $tot_qty += $row['jumlah'];
                            $total_price = $row['jumlah'] * $row['harga_beli'];
                            $tot_val += $total_price;
                        ?>
                        <tr class="hover:bg-slate-800/10">
                            <td class="px-4 py-2.5 text-slate-500"><?php echo $i++; ?></td>
                            <td class="px-4 py-2.5 font-mono text-indigo-400 font-semibold"><?php echo e($row['nomor_transaksi']); ?></td>
                            <td class="px-4 py-2.5 text-slate-400"><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                            <td class="px-4 py-2.5">
                                <span class="font-medium text-white"><?php echo e($row['nama_barang']); ?></span>
                                <span class="block text-[10px] text-slate-500 font-mono"><?php echo e($row['kode_barang']); ?></span>
                            </td>
                            <td class="px-4 py-2.5 text-slate-300"><?php echo e($row['nama_supplier'] ?: 'Umum'); ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-white">+<?php echo e($row['jumlah']); ?> <span class="text-[10px] text-slate-500 font-normal"><?php echo e($row['satuan']); ?></span></td>
                            <td class="px-4 py-2.5 text-right font-mono">Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                            <td class="px-4 py-2.5 text-right font-mono text-emerald-400">Rp <?php echo number_format($total_price, 0, ',', '.'); ?></td>
                            <td class="px-4 py-2.5 text-slate-400"><?php echo e($row['nama_user'] ?: 'Sistem'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(!empty($report_data)): ?>
                        <tr class="bg-slate-950/30 font-bold border-t border-slate-800 text-white">
                            <td colspan="5" class="px-4 py-3 text-right">TOTAL:</td>
                            <td class="px-4 py-3 text-right font-mono"><?php echo $tot_qty; ?></td>
                            <td></td>
                            <td class="px-4 py-3 text-right font-mono text-emerald-400">Rp <?php echo number_format($tot_val, 0, ',', '.'); ?></td>
                            <td></td>
                        </tr>
                        <?php else: ?>
                        <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">Tidak ada data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif ($report_type === 'keluar'): ?>
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-950/50 border-b border-slate-800 text-slate-400 font-medium uppercase">
                            <th class="px-4 py-3 w-12">No.</th>
                            <th class="px-4 py-3">No. Transaksi</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Barang</th>
                            <th class="px-4 py-3">Tujuan / Pelanggan</th>
                            <th class="px-4 py-3 text-right">Jumlah Keluar</th>
                            <th class="px-4 py-3">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        <?php $i=1; $tot_qty=0; foreach ($report_data as $row): 
                            $tot_qty += $row['jumlah'];
                        ?>
                        <tr class="hover:bg-slate-800/10">
                            <td class="px-4 py-2.5 text-slate-500"><?php echo $i++; ?></td>
                            <td class="px-4 py-2.5 font-mono text-indigo-400 font-semibold"><?php echo e($row['nomor_transaksi']); ?></td>
                            <td class="px-4 py-2.5 text-slate-400"><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                            <td class="px-4 py-2.5">
                                <span class="font-medium text-white"><?php echo e($row['nama_barang']); ?></span>
                                <span class="block text-[10px] text-slate-500 font-mono"><?php echo e($row['kode_barang']); ?></span>
                            </td>
                            <td class="px-4 py-2.5 text-slate-300"><?php echo e($row['tujuan']); ?></td>
                            <td class="px-4 py-2.5 text-right font-bold text-rose-400">-<?php echo e($row['jumlah']); ?> <span class="text-[10px] text-slate-500 font-normal"><?php echo e($row['satuan']); ?></span></td>
                            <td class="px-4 py-2.5 text-slate-400"><?php echo e($row['nama_user'] ?: 'Sistem'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(!empty($report_data)): ?>
                        <tr class="bg-slate-950/30 font-bold border-t border-slate-800 text-white">
                            <td colspan="5" class="px-4 py-3 text-right">TOTAL KELUAR:</td>
                            <td class="px-4 py-3 text-right font-mono text-rose-450">-<?php echo $tot_qty; ?></td>
                            <td></td>
                        </tr>
                        <?php else: ?>
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Tidak ada data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif ($report_type === 'opname'): ?>
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-950/50 border-b border-slate-800 text-slate-400 font-medium uppercase">
                            <th class="px-4 py-3 w-12">No.</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Barang</th>
                            <th class="px-4 py-3 text-right">Stok Sistem</th>
                            <th class="px-4 py-3 text-right">Stok Fisik</th>
                            <th class="px-4 py-3 text-right">Selisih</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3">Keterangan</th>
                            <th class="px-4 py-3">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        <?php $i=1; foreach ($report_data as $row): 
                            $diff = $row['selisih'];
                            $status = $diff == 0 ? 'Sesuai' : ($diff > 0 ? 'Lebih' : 'Kurang');
                            $diffClass = $diff == 0 ? 'text-slate-400' : ($diff > 0 ? 'text-indigo-400' : 'text-rose-500');
                        ?>
                        <tr class="hover:bg-slate-800/10">
                            <td class="px-4 py-2.5 text-slate-500"><?php echo $i++; ?></td>
                            <td class="px-4 py-2.5 text-slate-400"><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                            <td class="px-4 py-2.5">
                                <span class="font-medium text-white"><?php echo e($row['nama_barang']); ?></span>
                                <span class="block text-[10px] text-slate-500 font-mono"><?php echo e($row['kode_barang']); ?></span>
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono"><?php echo e($row['stok_sistem']); ?></td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-white"><?php echo e($row['stok_fisik']); ?></td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold <?php echo $diffClass; ?>"><?php echo $diff > 0 ? "+$diff" : $diff; ?></td>
                            <td class="px-4 py-2.5 text-center font-bold uppercase text-[10px]"><?php echo $status; ?></td>
                            <td class="px-4 py-2.5 text-slate-400 max-w-xs truncate"><?php echo e($row['keterangan'] ?: '-'); ?></td>
                            <td class="px-4 py-2.5 text-slate-400"><?php echo e($row['nama_user'] ?: 'Sistem'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($report_data)): ?>
                        <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">Tidak ada data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif ($report_type === 'nilai_persediaan'): ?>
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-950/50 border-b border-slate-800 text-slate-400 font-medium uppercase">
                            <th class="px-4 py-3 w-12">No.</th>
                            <th class="px-4 py-3">Kode Barang</th>
                            <th class="px-4 py-3">Nama Barang</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3 text-center">Stok Tersedia</th>
                            <th class="px-4 py-3 text-right">Harga Beli</th>
                            <th class="px-4 py-3 text-right">Nilai Persediaan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        <?php $i=1; $total_val=0; foreach ($report_data as $row): 
                            $total_val += $row['total_nilai'];
                        ?>
                        <tr class="hover:bg-slate-800/10">
                            <td class="px-4 py-2.5 text-slate-500"><?php echo $i++; ?></td>
                            <td class="px-4 py-2.5 font-mono text-indigo-400 font-semibold"><?php echo e($row['kode_barang']); ?></td>
                            <td class="px-4 py-2.5 font-medium text-white"><?php echo e($row['nama_barang']); ?></td>
                            <td class="px-4 py-2.5 text-slate-300"><?php echo e($row['nama_kategori'] ?: 'Lainnya'); ?></td>
                            <td class="px-4 py-2.5 text-center font-bold text-white font-mono"><?php echo e($row['stok']); ?> <span class="text-[10px] text-slate-500 font-normal"><?php echo e($row['satuan']); ?></span></td>
                            <td class="px-4 py-2.5 text-right font-mono">Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                            <td class="px-4 py-2.5 text-right font-mono text-emerald-400 font-bold">Rp <?php echo number_format($row['total_nilai'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(!empty($report_data)): ?>
                        <tr class="bg-slate-950/30 font-bold border-t border-slate-800 text-white text-sm">
                            <td colspan="6" class="px-4 py-3 text-right">TOTAL NILAI ASET INVENTARIS:</td>
                            <td class="px-4 py-3 text-right font-mono text-emerald-450">Rp <?php echo number_format($total_val, 0, ',', '.'); ?></td>
                        </tr>
                        <?php else: ?>
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Tidak ada data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function toggleDateFilters() {
        const type = document.getElementById('report_type').value;
        const timeWrapper = document.getElementById('time-filter-wrapper');
        const customDates = document.getElementById('custom-dates');
        
        // Date filters only apply to transaction reports (masuk, keluar, opname)
        if (type === 'masuk' || type === 'keluar' || type === 'opname') {
            timeWrapper.classList.remove('hidden');
            toggleCustomDates(); // update based on selected value
        } else {
            timeWrapper.classList.add('hidden');
            customDates.classList.add('hidden');
        }
    }

    function toggleCustomDates() {
        const timeFilter = document.getElementById('time_filter').value;
        const customDates = document.getElementById('custom-dates');
        const reportType = document.getElementById('report_type').value;

        if (timeFilter === 'custom' && (reportType === 'masuk' || reportType === 'keluar' || reportType === 'opname')) {
            customDates.classList.remove('hidden');
        } else {
            customDates.classList.add('hidden');
        }
    }

    // Call on load
    document.addEventListener('DOMContentLoaded', () => {
        toggleDateFilters();
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
