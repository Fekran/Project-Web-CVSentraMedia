<?php
// stok_opname.php
require_once __DIR__ . '/../includes/header.php';

// Allowed for Admin and Staff Gudang
check_role(['Admin', 'Staff Gudang']);

$search = trim($_GET['search'] ?? '');

// Fetch Products for selection
try {
    $prod_stmt = $pdo->query("SELECT id, kode_barang, nama_barang, satuan, stok FROM products ORDER BY nama_barang ASC");
    $products = $prod_stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}

// Fetch Stock Opname History
try {
    $history_query = "SELECT o.*, p.kode_barang, p.nama_barang, p.satuan, u.nama as nama_user 
                      FROM stock_opname o
                      JOIN products p ON o.product_id = p.id
                      LEFT JOIN users u ON o.created_by = u.id
                      WHERE 1=1";
    $params = [];
    if ($search !== '') {
        $history_query .= " AND (p.nama_barang LIKE :search OR p.kode_barang LIKE :search)";
        $params[':search'] = "%$search%";
    }
    $history_query .= " ORDER BY o.id DESC LIMIT 100";

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
        <h1 class="text-2xl font-bold text-white tracking-tight font-outfit">Stok Opname (Penyesuaian Stok)</h1>
        <p class="text-sm text-slate-400 font-normal">Cocokkan stok fisik di gudang dengan catatan sistem untuk penyesuaian</p>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Form Section -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl h-fit">
            <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-clipboard-check text-amber-500"></i> Form Stok Opname
            </h2>

            <form action="../actions/opname_action.php" method="POST" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">

                <!-- Date -->
                <div>
                    <label for="tanggal" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Tanggal Opname</label>
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
                                    data-stok="<?php echo $prod['stok']; ?>">
                                <?php echo e($prod['kode_barang']); ?> - <?php echo e($prod['nama_barang']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- System Stock Info & Unit -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-550 uppercase tracking-wider mb-1.5">Stok Sistem</label>
                        <input type="text" id="stok_sistem_tampil" disabled
                            class="block w-full px-3 py-2 bg-slate-950/40 border border-slate-850 rounded-xl text-indigo-400 text-sm font-mono font-bold select-none cursor-not-allowed">
                        <input type="hidden" name="stok_sistem" id="stok_sistem">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Satuan</label>
                        <input type="text" id="satuan_tampil" disabled
                            class="block w-full px-3 py-2 bg-slate-950/40 border border-slate-850 rounded-xl text-slate-500 text-sm select-none cursor-not-allowed">
                    </div>
                </div>

                <!-- Physical Stock Input -->
                <div>
                    <label for="stok_fisik" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Stok Fisik di Gudang *</label>
                    <input type="number" name="stok_fisik" id="stok_fisik" min="0" required placeholder="Masukkan jumlah fisik nyata" oninput="calculateDifference()"
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>

                <!-- Live Difference Box -->
                <div>
                    <label class="block text-xs font-semibold text-slate-450 uppercase tracking-wider mb-1.5">Selisih Penyesuaian</label>
                    <div class="flex items-center gap-3 px-3 py-3 bg-slate-950 border border-slate-850 rounded-xl">
                        <span id="selisih-val" class="font-mono text-lg font-bold text-slate-500">0</span>
                        <span id="selisih-label" class="text-xs text-slate-500">(Masukkan stok fisik)</span>
                    </div>
                </div>

                <!-- Notes / Keterangan -->
                <div>
                    <label for="keterangan" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">Alasan / Keterangan Penyesuaian</label>
                    <textarea name="keterangan" id="keterangan" rows="2" placeholder="Contoh: Barang rusak, salah catat transaksi, dll."
                        class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full py-2.5 px-4 bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white font-medium rounded-xl text-sm shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-[0.98] transition-all pt-2.5">
                    <i class="fas fa-check-double mr-2"></i> Simpan Penyesuaian
                </button>
            </form>
        </div>

        <!-- History Audit Section -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <!-- Search header -->
            <div class="p-6 border-b border-slate-800/80 flex flex-col sm:flex-row gap-4 items-center justify-between">
                <h3 class="text-base font-bold text-white self-start">Riwayat Stok Opname</h3>
                <form method="GET" action="" class="w-full sm:max-w-xs flex items-center relative">
                    <span class="absolute left-3 text-slate-500">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="search" value="<?php echo e($search); ?>"
                        placeholder="Cari berdasarkan nama barang..." 
                        class="w-full pl-10 pr-3 py-1.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                    <?php if ($search !== ''): ?>
                        <a href="stok_opname.php" class="absolute right-3 text-slate-500 hover:text-white text-xs">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- List Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-950/50 border-b border-slate-800/80 text-slate-400 font-medium uppercase tracking-wider">
                            <th class="px-5 py-3">Tanggal</th>
                            <th class="px-5 py-3">Barang</th>
                            <th class="px-5 py-3 text-right">Stok Sistem</th>
                            <th class="px-5 py-3 text-right">Stok Fisik</th>
                            <th class="px-5 py-3 text-right">Selisih</th>
                            <th class="px-5 py-3 text-center font-semibold">Status</th>
                            <th class="px-5 py-3">Keterangan</th>
                            <th class="px-5 py-3 text-center">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/40">
                        <?php if (count($history) > 0): ?>
                            <?php foreach ($history as $hist): ?>
                                <?php
                                $diff = $hist['selisih'];
                                if ($diff == 0) {
                                    $statusText = 'Sesuai';
                                    $statusClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
                                    $diffFormatted = '0';
                                } elseif ($diff > 0) {
                                    $statusText = 'Lebih';
                                    $statusClass = 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
                                    $diffFormatted = '+' . $diff;
                                } else {
                                    $statusText = 'Kurang';
                                    $statusClass = 'bg-rose-500/10 text-rose-500 border-rose-500/20';
                                    $diffFormatted = $diff;
                                }
                                ?>
                                <tr class="hover:bg-slate-800/20 transition-colors">
                                    <td class="px-5 py-3 text-slate-400"><?php echo e(date('d-m-Y', strtotime($hist['tanggal']))); ?></td>
                                    <td class="px-5 py-3">
                                        <div class="font-medium text-white"><?php echo e($hist['nama_barang']); ?></div>
                                        <div class="text-[10px] text-slate-500 font-mono"><?php echo e($hist['kode_barang']); ?></div>
                                    </td>
                                    <td class="px-5 py-3 text-right text-slate-400 font-mono font-medium"><?php echo e($hist['stok_sistem']); ?></td>
                                    <td class="px-5 py-3 text-right text-white font-mono font-bold"><?php echo e($hist['stok_fisik']); ?></td>
                                    <td class="px-5 py-3 text-right font-mono font-bold <?php echo $diff == 0 ? 'text-slate-400' : ($diff > 0 ? 'text-indigo-400' : 'text-rose-500'); ?>">
                                        <?php echo $diffFormatted; ?>
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold tracking-wide border uppercase <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-400 max-w-[150px] truncate" title="<?php echo e($hist['keterangan']); ?>"><?php echo e($hist['keterangan'] ?: '-'); ?></td>
                                    <td class="px-5 py-3 text-center text-slate-400">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-800 text-[10px]"><?php echo e($hist['nama_user'] ?: 'Sistem'); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-slate-500">
                                    <i class="fas fa-clipboard-list text-3xl mb-3 block"></i>
                                    Belum ada catatan riwayat stok opname.
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
    // Autofills stock values from selection data-attributes
    function autofillProductDetails() {
        const select = document.getElementById('product_id');
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption && selectedOption.value !== "") {
            const satuan = selectedOption.getAttribute('data-satuan');
            const stok = parseInt(selectedOption.getAttribute('data-stok'));
            
            document.getElementById('satuan_tampil').value = satuan;
            document.getElementById('stok_sistem_tampil').value = stok;
            document.getElementById('stok_sistem').value = stok;
            
            // Trigger difference calculation if physical stock is already inputted
            calculateDifference();
        } else {
            document.getElementById('satuan_tampil').value = '';
            document.getElementById('stok_sistem_tampil').value = '';
            document.getElementById('stok_sistem').value = '';
            document.getElementById('stok_fisik').value = '';
            resetDifferenceBox();
        }
    }

    // Reset difference box styling
    function resetDifferenceBox() {
        const valSpan = document.getElementById('selisih-val');
        const labelSpan = document.getElementById('selisih-label');
        valSpan.textContent = '0';
        valSpan.className = 'font-mono text-lg font-bold text-slate-500';
        labelSpan.textContent = '(Masukkan stok fisik)';
        labelSpan.className = 'text-xs text-slate-500';
    }

    // Live discrepancy calculation
    function calculateDifference() {
        const sistemVal = document.getElementById('stok_sistem').value;
        const fisikVal = document.getElementById('stok_fisik').value;
        
        if (sistemVal === "" || fisikVal === "") {
            resetDifferenceBox();
            return;
        }

        const sistem = parseInt(sistemVal);
        const fisik = parseInt(fisikVal);
        const diff = fisik - sistem;

        const valSpan = document.getElementById('selisih-val');
        const labelSpan = document.getElementById('selisih-label');

        valSpan.textContent = diff > 0 ? '+' + diff : diff;

        if (diff === 0) {
            valSpan.className = 'font-mono text-lg font-bold text-slate-400';
            labelSpan.textContent = '(Stok Sesuai)';
            labelSpan.className = 'text-xs text-emerald-400 font-semibold';
        } else if (diff > 0) {
            valSpan.className = 'font-mono text-lg font-bold text-indigo-400';
            labelSpan.textContent = '(Kelebihan Stok)';
            labelSpan.className = 'text-xs text-indigo-400 font-semibold';
        } else {
            valSpan.className = 'font-mono text-lg font-bold text-rose-500';
            labelSpan.textContent = '(Kekurangan Stok)';
            labelSpan.className = 'text-xs text-rose-500 font-semibold';
        }
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
