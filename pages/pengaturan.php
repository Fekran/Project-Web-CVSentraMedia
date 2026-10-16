<?php
// pengaturan.php
require_once __DIR__ . '/../includes/header.php';

// Restricted to Admin
check_role(['Admin']);

// Fetch current settings
try {
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1 LIMIT 1");
    $settings = $stmt->fetch();
} catch (PDOException $e) {
    $settings = null;
}
?>

<div class="space-y-6">
    <!-- Title Page -->
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight font-outfit">Pengaturan Sistem</h1>
        <p class="text-sm text-slate-400">Kelola profil toko, ganti kata sandi, dan backup/restore basis data</p>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Side: Shop Profile Settings -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Shop Profile Card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-store text-indigo-500"></i> Informasi Profil Toko
                </h2>
                
                <form action="../actions/pengaturan_action.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="update_toko">

                    <!-- Store Logo preview and upload -->
                    <div class="flex flex-col sm:flex-row items-center gap-4 py-2 border-b border-slate-800/60 mb-2">
                        <div class="shrink-0">
                            <?php if (!empty($settings['logo'])): ?>
                                <img src="<?php echo BASE_URL . 'uploads/' . e($settings['logo']); ?>" alt="Logo Toko" class="w-16 h-16 rounded-xl object-cover border border-slate-700 bg-slate-950">
                            <?php else: ?>
                                <div class="w-16 h-16 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center text-slate-650 text-2xl">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 w-full text-center sm:text-left space-y-1">
                            <label class="block text-xs font-semibold text-slate-450 uppercase tracking-wider">Logo Toko (Upload Logo Baru)</label>
                            <input type="file" name="logo" accept="image/*"
                                class="block w-full text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-indigo-400 hover:file:bg-slate-700 cursor-pointer">
                            <span class="text-[10px] text-slate-500 block">Mendukung format PNG, JPG, JPEG. Maks 2MB.</span>
                        </div>
                    </div>

                    <!-- Store Name -->
                    <div>
                        <label for="nama_toko" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Toko/Perusahaan *</label>
                        <input type="text" name="nama_toko" id="nama_toko" required value="<?php echo e($settings['nama_toko'] ?? ''); ?>"
                            class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Phone -->
                        <div>
                            <label for="telepon" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nomor Telepon Toko</label>
                            <input type="text" name="telepon" id="telepon" value="<?php echo e($settings['telepon'] ?? ''); ?>"
                                class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email Toko</label>
                            <input type="email" name="email" id="email" value="<?php echo e($settings['email'] ?? ''); ?>"
                                class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="alamat" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Alamat Lengkap Toko</label>
                        <textarea name="alamat" id="alamat" rows="3"
                            class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500"><?php echo e($settings['alamat'] ?? ''); ?></textarea>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                            class="px-5 py-2 bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white font-medium rounded-xl text-sm shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 active:scale-[0.98] transition-all">
                            <i class="fas fa-save mr-2"></i> Simpan Profil Toko
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Side: Security & Database Tools -->
        <div class="space-y-6">
            <!-- Change Password Card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-shield-halved text-indigo-500"></i> Ganti Kata Sandi
                </h2>

                <form action="../actions/pengaturan_action.php" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="ganti_password">

                    <div>
                        <label for="password_lama" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Kata Sandi Lama *</label>
                        <input type="password" name="password_lama" id="password_lama" required
                            class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="password_baru" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Kata Sandi Baru *</label>
                        <input type="password" name="password_baru" id="password_baru" required
                            class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="konfirmasi_password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Konfirmasi Sandi Baru *</label>
                        <input type="password" name="konfirmasi_password" id="konfirmasi_password" required
                            class="block w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    <button type="submit"
                        class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl text-sm transition-all active:scale-[0.98]">
                        <i class="fas fa-key mr-2"></i> Perbarui Kata Sandi
                    </button>
                </form>
            </div>

            <!-- Database Maintenance Card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-database text-indigo-500"></i> Pemeliharaan Database
                </h2>

                <div class="space-y-4">
                    <!-- Backup segment -->
                    <div class="p-4 bg-slate-950/50 border border-slate-850 rounded-xl space-y-2">
                        <span class="block text-xs font-bold text-white uppercase tracking-wider">Ekspor Database (Backup)</span>
                        <p class="text-[10px] text-slate-500">Unduh seluruh struktur data dan riwayat transaksi saat ini ke file SQL.</p>
                        <a href="../actions/db_backup_restore.php?action=backup" 
                            class="w-full py-2 px-4 bg-slate-800 hover:bg-slate-700 text-indigo-400 hover:text-white font-medium rounded-xl text-xs transition-all inline-flex items-center justify-center gap-2">
                            <i class="fas fa-download"></i> Backup Database (.sql)
                        </a>
                    </div>

                    <!-- Restore segment -->
                    <div class="p-4 bg-slate-950/50 border border-slate-850 rounded-xl space-y-2">
                        <span class="block text-xs font-bold text-white uppercase tracking-wider">Impor Database (Restore)</span>
                        <p class="text-[10px] text-rose-500/80 font-medium">
                            <i class="fas fa-exclamation-triangle"></i> PERINGATAN: Seluruh database saat ini akan diganti!
                        </p>
                        
                        <form action="../actions/db_backup_restore.php" method="POST" enctype="multipart/form-data" class="space-y-3">
                            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="action" value="restore">

                            <input type="file" name="backup_file" accept=".sql" required
                                class="block w-full text-[10px] text-slate-450 file:mr-3 file:py-1 file:px-2.5 file:rounded file:border-0 file:text-[10px] file:font-semibold file:bg-slate-800 file:text-indigo-400 hover:file:bg-slate-750 cursor-pointer">
                            
                            <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menimpa database saat ini? Seluruh data yang ada akan diganti.')"
                                class="w-full py-2 bg-rose-600/90 hover:bg-rose-700 text-white font-medium rounded-xl text-xs transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-upload"></i> Restore Database
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
