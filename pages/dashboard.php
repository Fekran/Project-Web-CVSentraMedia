<?php
// dashboard.php
require_once __DIR__ . '/../includes/header.php';

// Allowed for Admin and Staff Gudang
check_role(['Admin', 'Staff Gudang']);

// 1. Calculate Stats
try {
    // Total Products
    $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

    // Total Stock Quantity
    $total_stock = $pdo->query("SELECT SUM(stok) FROM products")->fetchColumn() ?: 0;

    // Total Goods In Sum
    $total_in = $pdo->query("SELECT SUM(jumlah) FROM stock_in")->fetchColumn() ?: 0;

    // Total Goods Out Sum
    $total_out = $pdo->query("SELECT SUM(jumlah) FROM stock_out")->fetchColumn() ?: 0;

    // Total Suppliers
    $total_suppliers = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();

    // Total Categories
    $total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

    // Low Stock (0 < stock <= stock_minimum)
    $low_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE stok <= stok_minimum AND stok > 0")->fetchColumn();

    // Out of Stock (stock <= 0)
    $out_of_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE stok <= 0")->fetchColumn();
} catch (PDOException $e) {
    die("Gagal memuat statistik dashboard: " . $e->getMessage());
}

// 2. Fetch last 6 months transaction metrics for Bar Chart
$months = [];
$incoming_data = [];
$outgoing_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month_year = date('Y-m', strtotime("-$i months"));
    $month_label = date('M Y', strtotime("-$i months"));
    $months[] = $month_label;

    try {
        $stmt_in = $pdo->prepare("SELECT SUM(jumlah) as total FROM stock_in WHERE DATE_FORMAT(tanggal, '%Y-%m') = :my");
        $stmt_in->execute([':my' => $month_year]);
        $incoming_data[] = intval($stmt_in->fetchColumn() ?: 0);

        $stmt_out = $pdo->prepare("SELECT SUM(jumlah) as total FROM stock_out WHERE DATE_FORMAT(tanggal, '%Y-%m') = :my");
        $stmt_out->execute([':my' => $month_year]);
        $outgoing_data[] = intval($stmt_out->fetchColumn() ?: 0);
    } catch (PDOException $e) {
        $incoming_data[] = 0;
        $outgoing_data[] = 0;
    }
}

// 3. Fetch Category stock distribution for Pie Chart
$cat_labels = [];
$cat_stocks = [];
try {
    $cat_dist = $pdo->query("
        SELECT COALESCE(c.nama_kategori, 'Lainnya') as kategori, SUM(p.stok) as total_stok 
        FROM products p 
        LEFT JOIN categories c ON p.kategori_id = c.id 
        GROUP BY p.kategori_id
        ORDER BY total_stok DESC
    ")->fetchAll();

    foreach ($cat_dist as $cd) {
        $cat_labels[] = $cd['kategori'];
        $cat_stocks[] = intval($cd['total_stok'] ?: 0);
    }
} catch (PDOException $e) {
    // empty arrays
}

// 4. Fetch 10 Latest Transactions (Union from IN, OUT, OPNAME)
try {
    $latest_tx_query = "
        (SELECT tanggal, nomor_transaksi, 'Masuk' as jenis_transaksi, p.nama_barang, jumlah, u.nama as nama_user, s.created_at 
         FROM stock_in s 
         JOIN products p ON s.product_id = p.id
         LEFT JOIN users u ON s.created_by = u.id)
        UNION ALL
        (SELECT tanggal, nomor_transaksi, 'Keluar' as jenis_transaksi, p.nama_barang, jumlah, u.nama as nama_user, s.created_at
         FROM stock_out s
         JOIN products p ON s.product_id = p.id
         LEFT JOIN users u ON s.created_by = u.id)
        UNION ALL
        (SELECT tanggal, 'OPNAME' as nomor_transaksi, 'Opname' as jenis_transaksi, p.nama_barang, selisih as jumlah, u.nama as nama_user, o.created_at
         FROM stock_opname o
         JOIN products p ON o.product_id = p.id
         LEFT JOIN users u ON o.created_by = u.id)
        ORDER BY created_at DESC LIMIT 10
    ";
    $latest_transactions = $pdo->query($latest_tx_query)->fetchAll();
} catch (PDOException $e) {
    $latest_transactions = [];
}
?>

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight font-outfit">Dashboard Overview</h1>
            <p class="text-sm text-slate-400">Ringkasan real-time pergerakan inventory dan status stok toko bangunan</p>
        </div>
        <div class="text-sm text-slate-400 font-medium">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Sistem Terkoneksi (Real-time)
            </span>
        </div>
    </div>

    <!-- 8 Statistik Cards Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Produk -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg flex items-center gap-4 hover:border-slate-700 transition-colors">
            <div class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-box"></i>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Produk</span>
                <span class="text-2xl font-extrabold text-white font-outfit"><?php echo $total_products; ?></span>
            </div>
        </div>

        <!-- 2. Total Stok -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg flex items-center gap-4 hover:border-slate-700 transition-colors">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Stok Fisik</span>
                <span class="text-2xl font-extrabold text-white font-outfit"><?php echo $total_stock; ?></span>
            </div>
        </div>

        <!-- 3. Barang Masuk -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg flex items-center gap-4 hover:border-slate-700 transition-colors">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-arrow-down-long"></i>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Barang Masuk</span>
                <span class="text-2xl font-extrabold text-white font-outfit"><?php echo $total_in; ?></span>
            </div>
        </div>

        <!-- 4. Barang Keluar -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg flex items-center gap-4 hover:border-slate-700 transition-colors">
            <div class="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-arrow-up-long"></i>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Barang Keluar</span>
                <span class="text-2xl font-extrabold text-white font-outfit"><?php echo $total_out; ?></span>
            </div>
        </div>

        <!-- 5. Total Supplier -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg flex items-center gap-4 hover:border-slate-700 transition-colors">
            <div class="w-12 h-12 rounded-xl bg-teal-500/10 text-teal-400 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-truck"></i>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Supplier</span>
                <span class="text-2xl font-extrabold text-white font-outfit"><?php echo $total_suppliers; ?></span>
            </div>
        </div>

        <!-- 6. Total Kategori -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg flex items-center gap-4 hover:border-slate-700 transition-colors">
            <div class="w-12 h-12 rounded-xl bg-sky-500/10 text-sky-400 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-tags"></i>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Total Kategori</span>
                <span class="text-2xl font-extrabold text-white font-outfit"><?php echo $total_categories; ?></span>
            </div>
        </div>

        <!-- 7. Stok Menipis -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg flex items-center gap-4 hover:border-slate-700 transition-colors">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Stok Menipis</span>
                <span class="text-2xl font-extrabold text-amber-400 font-outfit"><?php echo $low_stock; ?></span>
            </div>
        </div>

        <!-- 8. Stok Habis -->
        <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-lg flex items-center gap-4 hover:border-slate-700 transition-colors">
            <div class="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-circle-xmark"></i>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Stok Habis</span>
                <span class="text-2xl font-extrabold text-rose-500 font-outfit"><?php echo $out_of_stock; ?></span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 1. Bar Chart: Barang Masuk vs Keluar -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
            <div class="mb-4">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fas fa-chart-column text-indigo-500"></i> Trafik Barang Masuk & Keluar
                </h3>
                <p class="text-xs text-slate-500">Perbandingan volume barang masuk dan keluar selama 6 bulan terakhir</p>
            </div>
            <div class="relative w-full h-72">
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <!-- 2. Pie Chart: Distribusi Stok Kategori -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
            <div class="mb-4">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fas fa-chart-pie text-indigo-500"></i> Proporsi Stok Kategori
                </h3>
                <p class="text-xs text-slate-500">Persentase jumlah stok barang dikelompokkan per kategori</p>
            </div>
            <div class="relative w-full h-72 flex items-center justify-center">
                <?php if (count($cat_stocks) > 0): ?>
                    <canvas id="pieChart"></canvas>
                <?php else: ?>
                    <span class="text-slate-500 text-xs">Belum ada data distribusi kategori.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Last 10 Transactions Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="p-6 border-b border-slate-800/80">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <i class="fas fa-history text-indigo-400"></i> 10 Transaksi Terakhir
            </h3>
            <p class="text-xs text-slate-500 mt-1">Timeline aktivitas logistik terbaru di sistem</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-950/50 border-b border-slate-800/80 text-slate-400 font-medium uppercase tracking-wider">
                        <th class="px-6 py-3.5">Tanggal</th>
                        <th class="px-6 py-3.5">No. Transaksi</th>
                        <th class="px-6 py-3.5">Jenis</th>
                        <th class="px-6 py-3.5">Nama Barang</th>
                        <th class="px-6 py-3.5 text-right">Jumlah</th>
                        <th class="px-6 py-3.5 text-center">User</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40">
                    <?php if (count($latest_transactions) > 0): ?>
                        <?php foreach ($latest_transactions as $tx): ?>
                            <?php
                            $jenis = $tx['jenis_transaksi'];
                            if ($jenis === 'Masuk') {
                                $badgeClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
                                $qtyPrefix = '+';
                            } elseif ($jenis === 'Keluar') {
                                $badgeClass = 'bg-rose-500/10 text-rose-400 border-rose-500/20';
                                $qtyPrefix = '-';
                            } else {
                                $badgeClass = 'bg-amber-500/10 text-amber-400 border-amber-500/20';
                                $qtyPrefix = $tx['jumlah'] > 0 ? '+' : '';
                            }
                            ?>
                            <tr class="hover:bg-slate-800/20 transition-colors">
                                <td class="px-6 py-3.5 text-slate-400"><?php echo date('d-m-Y H:i', strtotime($tx['created_at'])); ?></td>
                                <td class="px-6 py-3.5 text-indigo-400 font-mono font-semibold"><?php echo e($tx['nomor_transaksi']); ?></td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold tracking-wide border uppercase <?php echo $badgeClass; ?>">
                                        <?php echo $jenis; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-white font-medium max-w-xs truncate"><?php echo e($tx['nama_barang']); ?></td>
                                <td class="px-6 py-3.5 text-right font-mono font-bold <?php echo $jenis === 'Masuk' ? 'text-emerald-400' : ($jenis === 'Keluar' ? 'text-rose-400' : 'text-amber-400'); ?>">
                                    <?php echo $qtyPrefix . $tx['jumlah']; ?>
                                </td>
                                <td class="px-6 py-3.5 text-center text-slate-400">
                                    <span class="px-2 py-0.5 rounded bg-slate-850 text-[10px]"><?php echo e($tx['nama_user'] ?: 'Sistem'); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500">
                                <i class="fas fa-clock text-3xl mb-3 block"></i>
                                Belum ada aktivitas transaksi yang tercatat.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Setup Bar Chart
    const ctxBar = document.getElementById('barChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: 'Barang Masuk',
                    data: <?php echo json_encode($incoming_data); ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.75)', // Emerald 500
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1.5,
                    borderRadius: 6
                },
                {
                    label: 'Barang Keluar',
                    data: <?php echo json_encode($outgoing_data); ?>,
                    backgroundColor: 'rgba(244, 63, 94, 0.75)', // Rose 500
                    borderColor: 'rgb(244, 63, 94)',
                    borderWidth: 1.5,
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#94a3b8', font: { family: 'Inter', size: 11 } }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { family: 'Inter', size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(51, 65, 85, 0.2)' },
                    ticks: { color: '#94a3b8', font: { family: 'Inter', size: 10 } }
                }
            }
        }
    });

    // 2. Setup Pie Chart
    <?php if (count($cat_stocks) > 0): ?>
    const ctxPie = document.getElementById('pieChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($cat_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($cat_stocks); ?>,
                backgroundColor: [
                    'rgba(99, 102, 241, 0.8)', // Indigo
                    'rgba(59, 130, 246, 0.8)', // Blue
                    'rgba(16, 185, 129, 0.8)', // Emerald
                    'rgba(245, 158, 11, 0.8)', // Amber
                    'rgba(20, 184, 166, 0.8)', // Teal
                    'rgba(239, 68, 68, 0.8)',  // Red
                    'rgba(168, 85, 247, 0.8)', // Purple
                    'rgba(148, 163, 184, 0.8)' // Slate
                ],
                borderWidth: 1.5,
                borderColor: '#0f172a'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#94a3b8', font: { family: 'Inter', size: 10 } }
                }
            },
            cutout: '60%'
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
