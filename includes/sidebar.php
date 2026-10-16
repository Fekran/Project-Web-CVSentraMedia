<?php
// sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'Staff Gudang';

// Function to check and set active CSS class
function is_active($page_name, $current_page) {
    return $page_name === $current_page 
        ? 'bg-gradient-to-r from-indigo-500/20 to-blue-500/5 text-indigo-400 border-l-4 border-indigo-500 font-semibold shadow-md shadow-indigo-500/5' 
        : 'text-slate-400 hover:bg-slate-900/50 hover:text-white border-l-4 border-transparent';
}
?>
<aside id="sidebar-menu" class="hidden md:flex flex-col w-full md:w-64 bg-slate-900 border-r border-slate-900 shrink-0 z-40 transition-all duration-300 md:relative fixed inset-0 overflow-y-auto">
    <!-- Sidebar Header (Desktop Logo) -->
    <div class="px-6 py-6 border-b border-slate-950 flex items-center justify-between">
        <a href="<?php echo BASE_URL; ?>pages/dashboard.php" class="flex items-center gap-3">
            <img src="<?php echo BASE_URL; ?>assets/images/logo-sentra-media.jpg" alt="CV. Sentra Media" class="h-10 object-contain">
        </a>
        <button id="close-menu-btn" class="md:hidden p-2 text-slate-400 hover:text-white">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <!-- Navigation Menu Items -->
    <nav class="flex-1 px-4 py-6 space-y-1.5">
        <span class="block px-3 text-[10px] font-semibold text-slate-500 tracking-wider uppercase mb-2">Utama</span>
        
        <a href="<?php echo BASE_URL; ?>pages/dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all <?php echo is_active('dashboard.php', $current_page); ?>">
            <i class="fas fa-chart-pie w-5 text-center"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?php echo BASE_URL; ?>pages/produk.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all <?php echo is_active('produk.php', $current_page); ?>">
            <i class="fas fa-box w-5 text-center"></i>
            <span>Produk</span>
        </a>

        <span class="block px-3 text-[10px] font-semibold text-slate-500 tracking-wider uppercase pt-4 mb-2">Transaksi</span>

        <a href="<?php echo BASE_URL; ?>pages/barang_masuk.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all <?php echo is_active('barang_masuk.php', $current_page); ?>">
            <i class="fas fa-arrow-down-long w-5 text-center text-emerald-500"></i>
            <span>Barang Masuk</span>
        </a>

        <a href="<?php echo BASE_URL; ?>pages/barang_keluar.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all <?php echo is_active('barang_keluar.php', $current_page); ?>">
            <i class="fas fa-arrow-up-long w-5 text-center text-rose-500"></i>
            <span>Barang Keluar</span>
        </a>

        <a href="<?php echo BASE_URL; ?>pages/stok_opname.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all <?php echo is_active('stok_opname.php', $current_page); ?>">
            <i class="fas fa-clipboard-check w-5 text-center text-amber-500"></i>
            <span>Stok Opname</span>
        </a>

        <span class="block px-3 text-[10px] font-semibold text-slate-500 tracking-wider uppercase pt-4 mb-2">Master & Laporan</span>

        <a href="<?php echo BASE_URL; ?>pages/laporan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all <?php echo is_active('laporan.php', $current_page); ?>">
            <i class="fas fa-file-invoice w-5 text-center text-sky-500"></i>
            <span>Laporan</span>
        </a>

        <a href="<?php echo BASE_URL; ?>pages/supplier.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all <?php echo is_active('supplier.php', $current_page); ?>">
            <i class="fas fa-truck w-5 text-center text-indigo-400"></i>
            <span>Supplier</span>
        </a>

        <a href="<?php echo BASE_URL; ?>pages/kategori.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all <?php echo is_active('kategori.php', $current_page); ?>">
            <i class="fas fa-tags w-5 text-center text-teal-400"></i>
            <span>Kategori Barang</span>
        </a>

        <?php if ($role === 'Admin'): ?>
        <span class="block px-3 text-[10px] font-semibold text-slate-500 tracking-wider uppercase pt-4 mb-2">Sistem</span>

        <a href="<?php echo BASE_URL; ?>pages/pengguna.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all <?php echo is_active('pengguna.php', $current_page); ?>">
            <i class="fas fa-users w-5 text-center"></i>
            <span>Pengguna</span>
        </a>

        <a href="<?php echo BASE_URL; ?>pages/pengaturan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all <?php echo is_active('pengaturan.php', $current_page); ?>">
            <i class="fas fa-sliders w-5 text-center"></i>
            <span>Pengaturan</span>
        </a>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Footer (Profile Info & Logout) -->
    <div class="p-4 border-t border-slate-950 bg-slate-950/40">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-xs uppercase shrink-0">
                    <?php echo substr($_SESSION['nama'] ?? 'ST', 0, 2); ?>
                </div>
                <div class="min-w-0">
                    <span class="block text-xs font-semibold text-white truncate"><?php echo e($_SESSION['nama'] ?? 'Staff'); ?></span>
                    <span class="block text-[10px] text-slate-500 truncate"><?php echo e($_SESSION['role'] ?? 'Staff Gudang'); ?></span>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>logout.php" class="p-2 text-slate-500 hover:text-rose-400 hover:bg-slate-950 rounded-lg transition-all" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>
