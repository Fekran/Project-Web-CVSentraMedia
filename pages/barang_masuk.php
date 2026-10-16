<?php
// barang_masuk.php
require_once __DIR__ . '/../includes/header.php';

// Allowed for Admin and Staff Gudang
check_role(['Admin', 'Staff Gudang']);

$search = trim($_GET['search'] ?? '');

// Fetch Products for selection
try {
    $prod_stmt = $pdo->query("SELECT id, kode_barang, nama_barang, satuan, harga_beli FROM products ORDER BY nama_barang ASC");
    $products = $prod_stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}

// Fetch Suppliers for selection
try {
    $sup_stmt = $pdo->query("SELECT id, nama_supplier FROM suppliers ORDER BY nama_supplier ASC");
    $suppliers = $sup_stmt->fetchAll();
} catch (PDOException $e) {
    $suppliers = [];
}

// Fetch Stock In History
try {
    $history_query = "SELECT s.*, p.kode_barang, p.nama_barang, p.satuan, sup.nama_supplier, u.nama as nama_user 
                      FROM stock_in s
                      JOIN products p ON s.product_id = p.id
                      LEFT JOIN suppliers sup ON s.supplier_id = sup.id
                      LEFT JOIN users u ON s.created_by = u.id
                      WHERE 1=1";
    $params = [];
    if ($search !== '') {
        $history_query .= " AND (s.nomor_transaksi LIKE :search OR p.nama_barang LIKE :search OR sup.nama_supplier LIKE :search)";
        $params[':search'] = "%$search%";
    }
    $history_query .= " ORDER BY s.id DESC LIMIT 100"; // show last 100 entries

    $hist_stmt = $pdo->prepare($history_query);
    $hist_stmt->execute($params);
    $history = $hist_stmt->fetchAll();
} catch (PDOException $e) {
    $history = [];
}
?>

<div class="space-y-6">
    <!-- Title Section -->
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight font-outfit">Barang Masuk (Stok Masuk)</h1>
        <p class="text-sm text-slate-400 font-normal">Catat penerimaan barang dari supplier untuk menambah persediaan</p>
    </div>

    <!-- Main Grid Layout: Form and History -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Form Section -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl h-fit">
            <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-file-invoice text-emerald-500"></i> Form Barang Masuk
            </h2>

            <form action="../actions/barang_masuk_action.php" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">

                <!-- Date -->
                <div>
                    <label for="tanggal" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Tanggal Penerimaan</label>
                    <input type="date" name="tanggal" id="tanggal" required value="<?php echo date('Y-m-d'); ?>"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                <!-- Product Selector -->
                <div>
                    <label for="product_id" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Pilih Produk *</label>
                    <select name="product_id" id="product_id" required onchange="autofillProductDetails()"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">-- Pilih Barang --</option>
                        <?php foreach ($products as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>" 
                                    data-satuan="<?php echo e($prod['satuan']); ?>" 
                                    data-harga="<?php echo $prod['harga_beli']; ?>">
                                <?php echo e($prod['kode_barang']); ?> - <?php echo e($prod['nama_barang']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Supplier Selector -->
                <div>
                    <label for="supplier_id" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Pilih Supplier</label>
                    <select name="supplier_id" id="supplier_id"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">-- Tanpa Supplier (Umum) --</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?php echo $sup['id']; ?>"><?php echo e($sup['nama_supplier']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Quantity & Unit -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="jumlah" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Jumlah Masuk *</label>
                        <input type="number" name="jumlah" id="jumlah" min="1" required placeholder="Jumlah"
                            class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="satuan_tampil" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Satuan</label>
                        <input type="text" id="satuan_tampil" disabled
                            class="block w-full px-3 py-2 bg-slate-950/40 border border-slate-850 rounded-xl text-slate-500 text-sm select-none cursor-not-allowed">
                    </div>
                </div>

                <!-- Purchase Price -->
                <div>
                    <label for="harga_beli" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Harga Beli per Satuan (Rp) *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-slate-500 text-sm">Rp</span>
                        <input type="number" name="harga_beli" id="harga_beli" min="0" required placeholder="Harga beli"
                            class="block w-full pl-9 pr-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <span class="text-[10px] text-slate-500 block mt-1">Mengupdate harga beli default produk di database.</span>
                </div>

                <!-- Keterangan -->
                <div>
                    <label for="keterangan" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Keterangan / Catatan</label>
                    <textarea name="keterangan" id="keterangan" rows="2" placeholder="Catatan pengiriman..."
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full py-2.5 px-4 bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white font-medium rounded-xl text-sm shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-[0.98] transition-all pt-2.5">
                    <i class="fas fa-save mr-2"></i> Simpan Transaksi
                </button>
            </form>
        </div>

        <!-- History Section -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <!-- Search header -->
            <div class="p-6 border-b border-slate-800/80 flex flex-col sm:flex-row gap-4 items-center justify-between">
                <h3 class="text-base font-bold text-white self-start">Riwayat Barang Masuk</h3>
                <form method="GET" action="" class="w-full sm:max-w-xs flex items-center relative">
                    <span class="absolute left-3 text-slate-500">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="search" value="<?php echo e($search); ?>"
                        placeholder="Cari transaksi, produk, supplier..." 
                        class="w-full pl-10 pr-3 py-1.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                    <?php if ($search !== ''): ?>
                        <a href="barang_masuk.php" class="absolute right-3 text-slate-500 hover:text-white text-xs">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- List Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-950/50 border-b border-slate-800/80 text-slate-400 font-medium uppercase tracking-wider">
                            <th class="px-5 py-3">No. Transaksi</th>
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Barang</th>
                            <th class="px-5 py-3">Supplier</th>
                            <th class="px-5 py-3 text-right">Jumlah</th>
                            <th class="px-5 py-3 text-right">Harga Beli</th>
                            <th class="px-5 py-3 text-center">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        <?php if (count($history) > 0): ?>
                            <?php foreach ($history as $hist): ?>
                                <tr class="hover:bg-slate-800/20 transition-colors">
                                    <td class="px-5 py-3 text-indigo-400 font-mono font-semibold"><?php echo e($hist['nomor_transaksi']); ?></td>
                                    <td class="px-5 py-3 text-slate-400"><?php echo e(date('d-m-Y', strtotime($hist['tanggal']))); ?></td>
                                    <td class="px-5 py-3">
                                        <div class="font-medium text-white"><?php echo e($hist['nama_barang']); ?></div>
                                        <div class="text-[10px] text-slate-500 font-mono"><?php echo e($hist['kode_barang']); ?></div>
                                    </td>
                                    <td class="px-5 py-3 text-slate-300"><?php echo e($hist['nama_supplier'] ?: 'Umum (Tanpa Pemasok)'); ?></td>
                                    <td class="px-5 py-3 text-right text-white font-bold font-mono">
                                        +<?php echo e($hist['jumlah']); ?> <span class="text-[10px] text-slate-500 font-normal"><?php echo e($hist['satuan']); ?></span>
                                    </td>
                                    <td class="px-5 py-3 text-right text-slate-400 font-mono">
                                        Rp <?php echo number_format($hist['harga_beli'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="px-5 py-3 text-center text-slate-400">
                                        <span class="px-2 py-0.5 rounded bg-slate-800 text-[10px]"><?php echo e($hist['nama_user'] ?: 'Sistem'); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    <i class="fas fa-receipt text-3xl mb-3 block"></i>
                                    Belum ada catatan riwayat barang masuk.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    // Autofills product details based on option data-attributes
    function autofillProductDetails() {
        const select = document.getElementById('product_id');
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption && selectedOption.value !== "") {
            const satuan = selectedOption.getAttribute('data-satuan');
            const harga = selectedOption.getAttribute('data-harga');
            
            document.getElementById('satuan_tampil').value = satuan;
            document.getElementById('harga_beli').value = harga;
        } else {
            document.getElementById('satuan_tampil').value = '';
            document.getElementById('harga_beli').value = '';
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
