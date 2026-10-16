<?php
// produk.php
require_once __DIR__ . '/../includes/header.php';

// Allowed for Admin and Staff Gudang
check_role(['Admin', 'Staff Gudang']);

// Filters
$search = trim($_GET['search'] ?? '');
$filter_status = trim($_GET['status'] ?? 'all'); // 'all', 'menipis', 'habis'

// Fetch Categories for forms
try {
    $cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY nama_kategori ASC");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Build query
try {
    $query = "SELECT p.*, c.nama_kategori 
              FROM products p 
              LEFT JOIN categories c ON p.kategori_id = c.id 
              WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $query .= " AND (p.kode_barang LIKE :search OR p.nama_barang LIKE :search OR c.nama_kategori LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if ($filter_status === 'menipis') {
        $query .= " AND p.stok <= p.stok_minimum AND p.stok > 0";
    } elseif ($filter_status === 'habis') {
        $query .= " AND p.stok <= 0";
    }

    $query .= " ORDER BY p.id DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data produk: " . $e->getMessage());
}
?>

<div class="space-y-6">
    <!-- Header Title & Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight font-outfit">Data Produk Barang</h1>
            <p class="text-sm text-slate-400">Kelola informasi barang, harga, lokasi rak, dan jumlah persediaan</p>
        </div>
        <div>
            <button onclick="openAddModal()" 
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white font-medium rounded-xl shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 transition-all focus:outline-none">
                <i class="fas fa-plus text-xs"></i> Tambah Produk Baru
            </button>
        </div>
    </div>

    <!-- Stats summary row -->
    <?php
    // Calculate numbers for this status overview
    $total_p = 0; $menipis_p = 0; $habis_p = 0;
    try {
        $stats_stmt = $pdo->query("SELECT stok, stok_minimum FROM products");
        while ($row = $stats_stmt->fetch()) {
            $total_p++;
            if ($row['stok'] <= 0) {
                $habis_p++;
            } elseif ($row['stok'] <= $row['stok_minimum']) {
                $menipis_p++;
            }
        }
    } catch (PDOException $e) {}
    ?>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="produk.php?status=all" class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex items-center justify-between hover:border-slate-700 transition-colors">
            <div>
                <span class="block text-xs font-semibold text-slate-500 uppercase">Semua Produk</span>
                <span class="text-2xl font-bold text-white"><?php echo $total_p; ?></span>
            </div>
            <div class="w-10 h-10 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center">
                <i class="fas fa-boxes-stacked"></i>
            </div>
        </a>
        <a href="produk.php?status=menipis" class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex items-center justify-between hover:border-slate-700 transition-colors">
            <div>
                <span class="block text-xs font-semibold text-slate-500 uppercase">Stok Menipis</span>
                <span class="text-2xl font-bold text-amber-400"><?php echo $menipis_p; ?></span>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
        </a>
        <a href="produk.php?status=habis" class="bg-slate-900 border border-slate-800 rounded-xl p-4 flex items-center justify-between hover:border-slate-700 transition-colors">
            <div>
                <span class="block text-xs font-semibold text-slate-500 uppercase">Stok Habis</span>
                <span class="text-2xl font-bold text-rose-500"><?php echo $habis_p; ?></span>
            </div>
            <div class="w-10 h-10 rounded-lg bg-rose-500/10 text-rose-500 flex items-center justify-center">
                <i class="fas fa-circle-xmark"></i>
            </div>
        </a>
    </div>

    <!-- Table Card Container -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <!-- Search & Filter Controls -->
        <div class="p-6 border-b border-slate-800/80 flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                <!-- Status Filter Buttons -->
                <a href="produk.php?status=all&search=<?php echo e($search); ?>" 
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold text-center transition-all <?php echo $filter_status === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'; ?>">
                    Semua
                </a>
                <a href="produk.php?status=menipis&search=<?php echo e($search); ?>" 
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold text-center transition-all <?php echo $filter_status === 'menipis' ? 'bg-amber-500 text-slate-950' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'; ?>">
                    Stok Menipis
                </a>
                <a href="produk.php?status=habis&search=<?php echo e($search); ?>" 
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold text-center transition-all <?php echo $filter_status === 'habis' ? 'bg-rose-600 text-white' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'; ?>">
                    Stok Habis
                </a>
            </div>

            <form method="GET" action="" class="w-full md:max-w-sm flex items-center relative">
                <input type="hidden" name="status" value="<?php echo e($filter_status); ?>">
                <span class="absolute left-3 text-slate-500">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="search" value="<?php echo e($search); ?>"
                    placeholder="Cari kode, nama, kategori..." 
                    class="w-full pl-10 pr-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                <?php if ($search !== ''): ?>
                    <a href="produk.php?status=<?php echo e($filter_status); ?>" class="absolute right-3 text-slate-500 hover:text-white text-xs">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-950/50 border-b border-slate-800/80 text-slate-400 font-medium">
                        <th class="px-6 py-4">Kode</th>
                        <th class="px-6 py-4">Nama Barang</th>
                        <th class="px-6 py-4">Kategori</th>
                        <th class="px-6 py-4">Satuan</th>
                        <th class="px-6 py-4 text-right">Harga Beli</th>
                        <th class="px-6 py-4 text-right">Harga Jual</th>
                        <th class="px-6 py-4 text-center">Stok</th>
                        <th class="px-6 py-4 text-center">Rak</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $prod): ?>
                            <?php
                            // Determine Stock Status
                            if ($prod['stok'] <= 0) {
                                $statusLabel = 'Habis';
                                $statusClass = 'bg-rose-500/10 text-rose-500 border-rose-500/20';
                            } elseif ($prod['stok'] <= $prod['stok_minimum']) {
                                $statusLabel = 'Menipis';
                                $statusClass = 'bg-amber-500/10 text-amber-400 border-amber-500/20';
                            } else {
                                $statusLabel = 'Aman';
                                $statusClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
                            }
                            ?>
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 text-indigo-400 font-mono font-semibold"><?php echo e($prod['kode_barang']); ?></td>
                                <td class="px-6 py-4 font-medium text-white max-w-xs truncate" title="<?php echo e($prod['nama_barang']); ?>"><?php echo e($prod['nama_barang']); ?></td>
                                <td class="px-6 py-4 text-slate-300"><?php echo e($prod['nama_kategori'] ?: 'Lainnya'); ?></td>
                                <td class="px-6 py-4 text-slate-400 text-xs"><?php echo e($prod['satuan']); ?></td>
                                <td class="px-6 py-4 text-right text-slate-300 font-mono text-xs">Rp <?php echo number_format($prod['harga_beli'], 0, ',', '.'); ?></td>
                                <td class="px-6 py-4 text-right text-emerald-400 font-mono text-xs font-semibold">Rp <?php echo number_format($prod['harga_jual'], 0, ',', '.'); ?></td>
                                <td class="px-6 py-4 text-center text-white font-semibold font-mono">
                                    <?php echo e($prod['stok']); ?>
                                    <span class="block text-[10px] text-slate-500 font-normal">Min: <?php echo $prod['stok_minimum']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-300 font-mono text-xs"><?php echo e($prod['lokasi_rak'] ?: '-'); ?></td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold tracking-wide border uppercase <?php echo $statusClass; ?>">
                                        <?php echo $statusLabel; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-1">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($prod)); ?>)"
                                        class="inline-flex items-center justify-center p-2 text-indigo-400 hover:text-white hover:bg-indigo-500/20 rounded-lg transition-all" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="openDeleteModal(<?php echo $prod['id']; ?>, '<?php echo e(addslashes($prod['nama_barang'])); ?>')"
                                        class="inline-flex items-center justify-center p-2 text-rose-400 hover:text-white hover:bg-rose-500/20 rounded-lg transition-all" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                                <i class="fas fa-box text-3xl mb-3 block"></i>
                                Tidak ada data produk ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- Add Product Modal -->
<div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-2xl rounded-2xl shadow-2xl p-6 relative overflow-hidden animate-fade-in-up">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-box-open text-indigo-500"></i> Tambah Produk Baru
        </h3>
        
        <form action="../actions/produk_action.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="add">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="add-nama" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Barang *</label>
                    <input type="text" name="nama_barang" id="add-nama" required
                        placeholder="Contoh: Semen Tiga Roda 40kg, Kabel Eterna NYM 2x1.5 50m"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="add-kategori" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Kategori</label>
                    <select name="kategori_id" id="add-kategori"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                        <option value="">-- Tanpa Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo e($cat['nama_kategori']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="add-satuan" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Satuan *</label>
                    <input type="text" name="satuan" id="add-satuan" required
                        placeholder="Contoh: Sak, Roll, Pcs, Meter, Batang"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="add-harga-beli" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Harga Beli (Rp)</label>
                    <input type="number" name="harga_beli" id="add-harga-beli" min="0" value="0"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="add-harga-jual" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Harga Jual (Rp)</label>
                    <input type="number" name="harga_jual" id="add-harga-jual" min="0" value="0"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="add-stok" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Stok Awal</label>
                    <input type="number" name="stok_awal" id="add-stok" min="0" value="0"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="add-stok-minimum" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Stok Minimum</label>
                    <input type="number" name="stok_minimum" id="add-stok-minimum" min="0" value="5"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="add-rak" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Lokasi Rak / Gudang</label>
                    <input type="text" name="lokasi_rak" id="add-rak"
                        placeholder="Contoh: Rak A-3, G-1"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div class="md:col-span-2">
                    <label for="add-deskripsi" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Deskripsi Produk</label>
                    <textarea name="deskripsi" id="add-deskripsi" rows="2"
                        placeholder="Masukkan keterangan detail barang..."
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeAddModal()"
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl text-sm transition-colors">
                    Batal
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white font-medium rounded-xl text-sm transition-colors">
                    Simpan Produk
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-2xl rounded-2xl shadow-2xl p-6 relative overflow-hidden animate-fade-in-up">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-edit text-indigo-500"></i> Edit Produk
        </h3>
        
        <form action="../actions/produk_action.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Kode Barang (Sistem)</label>
                    <input type="text" id="edit-kode" disabled
                        class="block w-full px-3 py-2 bg-slate-950/40 border border-slate-850 rounded-xl text-slate-500 text-sm font-mono font-semibold select-none cursor-not-allowed">
                </div>
                <div>
                    <label for="edit-nama" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Barang *</label>
                    <input type="text" name="nama_barang" id="edit-nama" required
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="edit-kategori" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Kategori</label>
                    <select name="kategori_id" id="edit-kategori"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                        <option value="">-- Tanpa Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo e($cat['nama_kategori']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="edit-satuan" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Satuan *</label>
                    <input type="text" name="satuan" id="edit-satuan" required
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="edit-harga-beli" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Harga Beli (Rp)</label>
                    <input type="number" name="harga_beli" id="edit-harga-beli" min="0"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="edit-harga-jual" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Harga Jual (Rp)</label>
                    <input type="number" name="harga_jual" id="edit-harga-jual" min="0"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="edit-stok" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Stok Sekarang</label>
                    <input type="number" name="stok" id="edit-stok" min="0"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="edit-stok-minimum" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Stok Minimum</label>
                    <input type="number" name="stok_minimum" id="edit-stok-minimum" min="0"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div>
                    <label for="edit-rak" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Lokasi Rak / Gudang</label>
                    <input type="text" name="lokasi_rak" id="edit-rak"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                </div>

                <div class="md:col-span-2">
                    <label for="edit-deskripsi" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Deskripsi Produk</label>
                    <textarea name="deskripsi" id="edit-deskripsi" rows="2"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditModal()"
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl text-sm transition-colors">
                    Batal
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl text-sm transition-colors">
                    Perbarui Produk
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-sm rounded-2xl shadow-2xl p-6 relative overflow-hidden animate-fade-in-up">
        <div class="text-center space-y-4">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-rose-500/15 text-rose-500 mb-2">
                <i class="fas fa-exclamation-triangle text-xl"></i>
            </div>
            <h3 class="text-lg font-bold text-white">Hapus Produk</h3>
            <p class="text-sm text-slate-400">
                Apakah Anda yakin ingin menghapus produk <span id="delete-name" class="text-white font-semibold"></span>? Seluruh riwayat stok produk ini juga akan terhapus.
            </p>
        </div>
        
        <form action="../actions/produk_action.php" method="POST" class="mt-6 flex justify-center gap-2">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete-id">
            
            <button type="button" onclick="closeDeleteModal()"
                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl text-sm transition-colors">
                Batal
            </button>
            <button type="submit"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-medium rounded-xl text-sm transition-colors">
                Ya, Hapus
            </button>
        </form>
    </div>
</div>

<script>
    // Modal script
    function openAddModal() {
        document.getElementById('add-modal').classList.remove('hidden');
    }
    function closeAddModal() {
        document.getElementById('add-modal').classList.add('hidden');
    }

    function openEditModal(prod) {
        document.getElementById('edit-id').value = prod.id;
        document.getElementById('edit-kode').value = prod.kode_barang;
        document.getElementById('edit-nama').value = prod.nama_barang;
        document.getElementById('edit-kategori').value = prod.kategori_id || '';
        document.getElementById('edit-satuan').value = prod.satuan;
        document.getElementById('edit-harga-beli').value = prod.harga_beli;
        document.getElementById('edit-harga-jual').value = prod.harga_jual;
        document.getElementById('edit-stok').value = prod.stok;
        document.getElementById('edit-stok-minimum').value = prod.stok_minimum;
        document.getElementById('edit-rak').value = prod.lokasi_rak || '';
        document.getElementById('edit-deskripsi').value = prod.deskripsi || '';
        
        document.getElementById('edit-modal').classList.remove('hidden');
    }
    function closeEditModal() {
        document.getElementById('edit-modal').classList.add('hidden');
    }

    function openDeleteModal(id, name) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-name').textContent = name;
        document.getElementById('delete-modal').classList.remove('hidden');
    }
    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.add('hidden');
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
