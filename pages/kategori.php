<?php
// kategori.php
require_once __DIR__ . '/../includes/header.php';

// Check role permission (Admin only)
check_role(['Admin']);

// Search term
$search = trim($_GET['search'] ?? '');

// Fetch Categories
try {
    if ($search !== '') {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE nama_kategori LIKE :search ORDER BY id DESC");
        $stmt->execute([':search' => "%$search%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
    }
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data kategori: " . $e->getMessage());
}
?>

<div class="space-y-6">
    <!-- Page Title & Add Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight font-outfit">Kategori Barang</h1>
            <p class="text-sm text-slate-400">Kelola kelompok atau pengelompokan produk toko bangunan</p>
        </div>
        <div>
            <button onclick="openAddModal()" 
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white font-medium rounded-xl shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 transition-all focus:outline-none">
                <i class="fas fa-plus text-xs"></i> Tambah Kategori
            </button>
        </div>
    </div>

    <!-- Table Card Container -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <!-- Search & Filter Controls -->
        <div class="p-6 border-b border-slate-800/80 flex flex-col md:flex-row gap-4 items-center justify-between">
            <form method="GET" action="" class="w-full md:max-w-sm flex items-center relative">
                <span class="absolute left-3 text-slate-500">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="search" value="<?php echo e($search); ?>"
                    placeholder="Cari kategori..." 
                    class="w-full pl-10 pr-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                <?php if ($search !== ''): ?>
                    <a href="kategori.php" class="absolute right-3 text-slate-500 hover:text-white text-xs">Clear</a>
                <?php endif; ?>
            </form>
            <div class="text-xs text-slate-500">
                Menampilkan <span class="text-slate-300 font-semibold"><?php echo count($categories); ?></span> data kategori.
            </div>
        </div>

        <!-- Data Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-950/50 border-b border-slate-800/80 text-slate-400 font-medium">
                        <th class="px-6 py-4 w-20">ID</th>
                        <th class="px-6 py-4">Nama Kategori</th>
                        <th class="px-6 py-4 text-right w-40">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    <?php if (count($categories) > 0): ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 text-slate-500 font-mono"><?php echo e($cat['id']); ?></td>
                                <td class="px-6 py-4 text-white font-medium"><?php echo e($cat['nama_kategori']); ?></td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button onclick="openEditModal(<?php echo $cat['id']; ?>, '<?php echo e(addslashes($cat['nama_kategori'])); ?>')"
                                        class="inline-flex items-center justify-center p-2 text-indigo-400 hover:text-white hover:bg-indigo-500/20 rounded-lg transition-all" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="openDeleteModal(<?php echo $cat['id']; ?>, '<?php echo e(addslashes($cat['nama_kategori'])); ?>')"
                                        class="inline-flex items-center justify-center p-2 text-rose-400 hover:text-white hover:bg-rose-500/20 rounded-lg transition-all" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-slate-500">
                                <i class="fas fa-folder-open text-3xl mb-3 block"></i>
                                Tidak ada data kategori ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- Add Category Modal -->
<div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-2xl shadow-2xl p-6 relative overflow-hidden animate-fade-in-up">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-folder-plus text-indigo-500"></i> Tambah Kategori
        </h3>
        
        <form action="../actions/kategori_action.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="add">
            
            <div>
                <label for="add-nama" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Kategori</label>
                <input type="text" name="nama_kategori" id="add-nama" required
                    placeholder="Contoh: Semen, Kabel, Pipa, dll."
                    class="block w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeAddModal()"
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl text-sm transition-colors">
                    Batal
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white font-medium rounded-xl text-sm transition-colors">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-2xl shadow-2xl p-6 relative overflow-hidden animate-fade-in-up">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-edit text-indigo-500"></i> Edit Kategori
        </h3>
        
        <form action="../actions/kategori_action.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            
            <div>
                <label for="edit-nama" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Kategori</label>
                <input type="text" name="nama_kategori" id="edit-nama" required
                    class="block w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditModal()"
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl text-sm transition-colors">
                    Batal
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl text-sm transition-colors">
                    Perbarui
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
            <h3 class="text-lg font-bold text-white">Konfirmasi Hapus</h3>
            <p class="text-sm text-slate-400">
                Apakah Anda yakin ingin menghapus kategori <span id="delete-name" class="text-white font-semibold"></span>? Tindakan ini tidak dapat dibatalkan.
            </p>
        </div>
        
        <form action="../actions/kategori_action.php" method="POST" class="mt-6 flex justify-center gap-2">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete-id">
            
            <button type="button" onclick="closeDeleteModal()"
                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl text-sm transition-colors">
                Batal
            </button>
            <button type="submit"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-medium rounded-xl text-sm transition-colors">
                Hapus Sekarang
            </button>
        </form>
    </div>
</div>

<script>
    // Modal management helper functions
    function openAddModal() {
        document.getElementById('add-modal').classList.remove('hidden');
    }
    function closeAddModal() {
        document.getElementById('add-modal').classList.add('hidden');
    }

    function openEditModal(id, name) {
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-nama').value = name;
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
