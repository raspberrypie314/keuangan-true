<?php
// kategori.php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDBConnection();
$user = getCurrentUser($pdo);

// Handle POST (Create & Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? 'pengeluaran';
    $color = $_POST['color'] ?? '#5B84B6';
    
    if (isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
        // Update
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, type = ?, color = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$name, $type, $color, $id, $_SESSION['user_id']]);
        $_SESSION['success'] = 'Kategori berhasil diupdate!';
    } else {
        // Create
        $stmt = $pdo->prepare("INSERT INTO categories (user_id, name, type, color, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$_SESSION['user_id'], $name, $type, $color]);
        $_SESSION['success'] = 'Kategori berhasil ditambahkan!';
    }
    redirect('kategori.php');
}

// Handle DELETE
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $_SESSION['success'] = 'Kategori berhasil dihapus!';
    redirect('kategori.php');
}

// Get categories with transaction count
$stmt = $pdo->prepare("
    SELECT c.*, COUNT(t.id) as transactions_count
    FROM categories c
    LEFT JOIN transactions t ON c.id = t.category_id AND t.user_id = ?
    WHERE c.user_id = ?
    GROUP BY c.id
    ORDER BY c.name
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$categories = $stmt->fetchAll();

$pageTitle = 'Kategori';
$currentPage = 'kategori.php';
include __DIR__ . '/includes/header.php';
?>

<div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 mb-8">
    <div class="flex justify-between items-center">
        <h3 class="font-bold text-slate-800 text-base">Kelola Kategori Keuangan</h3>
        <button onclick="toggleModal(true)" class="bg-primary hover:bg-[#4f739c] text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition duration-150 flex items-center gap-1.5 shadow-sm shadow-[#5B84B6]/25">
            <i class="fa-solid fa-plus"></i> Tambah Kategori
        </button>
    </div>
</div>

<!-- Categories Table -->
<div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 text-sm font-bold border-b border-slate-100">
                    <th class="pb-4 pl-4">Kategori</th>
                    <th class="pb-4">Tipe</th>
                    <th class="pb-4 text-center">Jumlah Transaksi</th>
                    <th class="pb-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td class="py-4 pl-4 font-semibold text-slate-700 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full" style="background-color: <?php echo $cat['color']; ?>"></span>
                            <?php echo $cat['name']; ?>
                        </td>
                        <td class="py-4">
                            <?php if ($cat['type'] == 'pemasukan'): ?>
                            <span class="text-emerald-500 font-semibold">Pemasukan</span>
                            <?php else: ?>
                            <span class="text-rose-500 font-semibold">Pengeluaran</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-4 text-center text-slate-600 font-medium">
                            <?php echo $cat['transactions_count']; ?>
                        </td>
                        <td class="py-4 text-center flex items-center justify-center gap-2">
                            <button onclick="openEditModal(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>', '<?php echo $cat['type']; ?>', '<?php echo $cat['color']; ?>')" class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-lg hover:bg-slate-50 flex items-center justify-center transition duration-150">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <a href="kategori.php?delete=<?php echo $cat['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini? Semua transaksi terkait akan terpengaruh.')" class="text-rose-500 hover:text-rose-700 w-8 h-8 rounded-lg hover:bg-rose-50 flex items-center justify-center transition duration-150">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="py-10 text-center text-slate-400">Belum ada kategori terdaftar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div id="catModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-lg rounded-3xl p-8 shadow-2xl border border-slate-100 m-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-slate-800">Tambah Kategori Baru</h3>
            <button onclick="toggleModal(false)" class="text-slate-400 hover:text-slate-600 text-xl"><i class="fa-solid fa-times"></i></button>
        </div>
        <form action="kategori.php" method="POST">
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Nama Kategori</label>
                    <input type="text" name="name" required placeholder="Contoh: Makanan, Transportasi" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Tipe Transaksi</label>
                    <select name="type" required class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                        <option value="pengeluaran">Pengeluaran</option>
                        <option value="pemasukan">Pemasukan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Warna Kategori</label>
                    <div class="flex gap-3 items-center">
                        <input type="color" name="color" value="#5B84B6" required class="w-12 h-12 rounded-xl border border-slate-200 cursor-pointer p-1">
                        <span class="text-xs text-slate-400">Pilih warna untuk membedakan kategori ini</span>
                    </div>
                </div>
                <div class="pt-4 flex gap-4">
                    <button type="button" onclick="toggleModal(false)" class="flex-1 border border-slate-200 text-slate-500 font-semibold py-3 rounded-xl hover:bg-slate-50 transition duration-150">Batal</button>
                    <button type="submit" class="flex-1 bg-primary hover:bg-[#4f739c] text-white font-semibold py-3 rounded-xl shadow-md shadow-[#5B84B6]/20 transition duration-150">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div id="editCatModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-lg rounded-3xl p-8 shadow-2xl border border-slate-100 m-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-slate-800">Ubah Kategori</h3>
            <button onclick="toggleEditModal(false)" class="text-slate-400 hover:text-slate-600 text-xl"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="editCatForm" action="kategori.php" method="POST">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="id" id="edit_id">
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Nama Kategori</label>
                    <input type="text" name="name" id="edit_name" required placeholder="Contoh: Makanan, Transportasi" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Tipe Transaksi</label>
                    <select name="type" id="edit_type" required class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                        <option value="pengeluaran">Pengeluaran</option>
                        <option value="pemasukan">Pemasukan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Warna Kategori</label>
                    <div class="flex gap-3 items-center">
                        <input type="color" name="color" id="edit_color" required class="w-12 h-12 rounded-xl border border-slate-200 cursor-pointer p-1">
                        <span class="text-xs text-slate-400">Pilih warna untuk membedakan kategori ini</span>
                    </div>
                </div>
                <div class="pt-4 flex gap-4">
                    <button type="button" onclick="toggleEditModal(false)" class="flex-1 border border-slate-200 text-slate-500 font-semibold py-3 rounded-xl hover:bg-slate-50 transition duration-150">Batal</button>
                    <button type="submit" class="flex-1 bg-primary hover:bg-[#4f739c] text-white font-semibold py-3 rounded-xl shadow-md shadow-[#5B84B6]/20 transition duration-150">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(show) {
    const modal = document.getElementById('catModal');
    if (show) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}

function toggleEditModal(show) {
    const modal = document.getElementById('editCatModal');
    if (show) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}

function openEditModal(id, name, type, color) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_color').value = color;
    toggleEditModal(true);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>