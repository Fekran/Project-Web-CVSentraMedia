<?php
// pengguna.php
require_once __DIR__ . '/../includes/header.php';

// Restricted to Admin
check_role(['Admin']);

$search = trim($_GET['search'] ?? '');

// Fetch Users list
try {
    if ($search !== '') {
        $stmt = $pdo->prepare("SELECT id, nama, username, role, created_at FROM users WHERE nama LIKE :search OR username LIKE :search ORDER BY id DESC");
        $stmt->execute([':search' => "%$search%"]);
    } else {
        $stmt = $pdo->query("SELECT id, nama, username, role, created_at FROM users ORDER BY id DESC");
    }
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data pengguna: " . $e->getMessage());
}
?>

<div class="space-y-6">
    <!-- Title Page & Add Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight font-outfit">Manajemen Pengguna</h1>
            <p class="text-sm text-slate-400">Atur hak akses staf gudang dan administrator sistem</p>
        </div>
        <div>
            <button onclick="openAddModal()" 
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white font-medium rounded-xl shadow-lg shadow-indigo-500/10 hover:shadow-indigo-500/20 transition-all focus:outline-none">
                <i class="fas fa-user-plus text-xs"></i> Tambah Pengguna
            </button>
        </div>
    </div>

    <!-- Data Table Card Container -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <!-- Controls -->
        <div class="p-6 border-b border-slate-800/80 flex flex-col sm:flex-row gap-4 items-center justify-between">
            <form method="GET" action="" class="w-full sm:max-w-sm flex items-center relative">
                <span class="absolute left-3 text-slate-500">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="search" value="<?php echo e($search); ?>"
                    placeholder="Cari nama atau username..." 
                    class="w-full pl-10 pr-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm placeholder-slate-650 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all">
                <?php if ($search !== ''): ?>
                    <a href="pengguna.php" class="absolute right-3 text-slate-500 hover:text-white text-xs">Clear</a>
                <?php endif; ?>
            </form>
            <div class="text-xs text-slate-500">
                Total pengguna terdaftar: <span class="text-slate-300 font-semibold"><?php echo count($users); ?></span> orang
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-950/50 border-b border-slate-800/80 text-slate-400 font-medium">
                        <th class="px-6 py-4">Nama Lengkap</th>
                        <th class="px-6 py-4">Username</th>
                        <th class="px-6 py-4">Hak Akses / Role</th>
                        <th class="px-6 py-4">Tanggal Dibuat</th>
                        <th class="px-6 py-4 text-right w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $usr): ?>
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-6 py-4 text-white font-medium">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold">
                                            <?php echo substr($usr['nama'], 0, 1); ?>
                                        </div>
                                        <span>
                                            <?php echo e($usr['nama']); ?>
                                            <?php if ($usr['id'] === $_SESSION['user_id']): ?>
                                                <span class="ml-1 text-[9px] bg-slate-800 text-slate-400 border border-slate-700 px-1.5 py-0.5 rounded font-normal">Anda</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-300 font-mono text-xs"><?php echo e($usr['username']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium border <?php echo $usr['role'] === 'Admin' ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' : 'bg-slate-500/10 text-slate-400 border-slate-500/20'; ?>">
                                        <i class="fas <?php echo $usr['role'] === 'Admin' ? 'fa-user-shield' : 'fa-user-gear'; ?> text-[10px]"></i>
                                        <?php echo e($usr['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-400 text-xs"><?php echo e(date('d-m-Y H:i', strtotime($usr['created_at']))); ?></td>
                                <td class="px-6 py-4 text-right space-x-1">
                                    <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($usr)); ?>)"
                                        class="inline-flex items-center justify-center p-2 text-indigo-400 hover:text-white hover:bg-indigo-500/20 rounded-lg transition-all" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="openDeleteModal(<?php echo $usr['id']; ?>, '<?php echo e(addslashes($usr['nama'])); ?>')"
                                        <?php echo $usr['id'] === $_SESSION['user_id'] ? 'disabled class="opacity-30 cursor-not-allowed inline-flex items-center justify-center p-2 text-slate-500"' : 'class="inline-flex items-center justify-center p-2 text-rose-400 hover:text-white hover:bg-rose-500/20 rounded-lg transition-all"'; ?> 
                                        title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">
                                <i class="fas fa-users-slash text-3xl mb-3 block"></i>
                                Tidak ada data pengguna ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- Add User Modal -->
<div id="add-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-2xl shadow-2xl p-6 relative overflow-hidden animate-fade-in-up">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-user-plus text-indigo-500"></i> Tambah Pengguna Baru
        </h3>
        
        <form action="../actions/user_action.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="add">
            
            <div>
                <label for="add-nama" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Lengkap *</label>
                <input type="text" name="nama" id="add-nama" required placeholder="Contoh: Muhammad Yusuf"
                    class="block w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
            </div>

            <div>
                <label for="add-username" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Username *</label>
                <input type="text" name="username" id="add-username" required placeholder="Masukkan username masuk"
                    class="block w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
            </div>

            <div>
                <label for="add-password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Password *</label>
                <input type="password" name="password" id="add-password" required placeholder="Masukkan kata sandi baru"
                    class="block w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
            </div>

            <div>
                <label for="add-role" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Hak Akses / Role</label>
                <select name="role" id="add-role"
                    class="block w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                    <option value="Staff Gudang">Staff Gudang</option>
                    <option value="Admin">Admin (Akses Penuh)</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeAddModal()"
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl text-sm transition-colors">
                    Batal
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white font-medium rounded-xl text-sm transition-colors">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="edit-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-2xl shadow-2xl p-6 relative overflow-hidden animate-fade-in-up">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-user-edit text-indigo-500"></i> Edit Data Pengguna
        </h3>
        
        <form action="../actions/user_action.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            
            <div>
                <label for="edit-nama" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Nama Lengkap *</label>
                <input type="text" name="nama" id="edit-nama" required
                    class="block w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
            </div>

            <div>
                <label for="edit-username" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Username *</label>
                <input type="text" name="username" id="edit-username" required
                    class="block w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
            </div>

            <div>
                <label for="edit-password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Ganti Password (Opsional)</label>
                <span class="text-[10px] text-slate-500 block mb-2">Kosongkan jika tidak ingin mengubah kata sandi</span>
                <input type="password" name="password" id="edit-password" placeholder="Masukkan password baru"
                    class="block w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
            </div>

            <div>
                <label for="edit-role" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Hak Akses / Role</label>
                <select name="role" id="edit-role"
                    class="block w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 text-sm">
                    <option value="Staff Gudang">Staff Gudang</option>
                    <option value="Admin">Admin (Akses Penuh)</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditModal()"
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white rounded-xl text-sm transition-colors">
                    Batal
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-indigo-650 hover:bg-indigo-700 text-white font-medium rounded-xl text-sm transition-colors">
                    Perbarui Pengguna
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
            <h3 class="text-lg font-bold text-white">Hapus Pengguna</h3>
            <p class="text-sm text-slate-400">
                Apakah Anda yakin ingin menghapus akun <span id="delete-name" class="text-white font-semibold"></span>? Pengguna ini tidak akan dapat mengakses sistem lagi.
            </p>
        </div>
        
        <form action="../actions/user_action.php" method="POST" class="mt-6 flex justify-center gap-2">
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
    function openAddModal() {
        document.getElementById('add-modal').classList.remove('hidden');
    }
    function closeAddModal() {
        document.getElementById('add-modal').classList.add('hidden');
    }

    function openEditModal(usr) {
        document.getElementById('edit-id').value = usr.id;
        document.getElementById('edit-nama').value = usr.nama;
        document.getElementById('edit-username').value = usr.username;
        document.getElementById('edit-role').value = usr.role;
        document.getElementById('edit-password').value = ''; // clear password field
        
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
