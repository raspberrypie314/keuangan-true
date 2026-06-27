<?php
// transaksi.php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDBConnection();
$user = getCurrentUser($pdo);

// Handle POST requests (Create & Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $amount = str_replace('.', '', $_POST['amount'] ?? '0');
    $date = $_POST['date'] ?? date('Y-m-d H:i:s');
    $description = $_POST['description'] ?? '';
    
    if (isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
        // Update
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET type = ?, category_id = ?, amount = ?, date = ?, description = ? 
            WHERE id = ? AND user_id = ?
        ");
        $result = $stmt->execute([$type, $category_id, $amount, $date, $description, $id, $_SESSION['user_id']]);
        $_SESSION['success'] = 'Transaksi berhasil diupdate!';
    } else {
        // Create
        $stmt = $pdo->prepare("
            INSERT INTO transactions (user_id, type, category_id, amount, date, description, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $result = $stmt->execute([$_SESSION['user_id'], $type, $category_id, $amount, $date, $description]);
        $_SESSION['success'] = 'Transaksi berhasil ditambahkan!';
    }
    redirect('transaksi.php');
}

// Handle DELETE
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $_SESSION['success'] = 'Transaksi berhasil dihapus!';
    redirect('transaksi.php');
}

// Get filter parameters
$filters = [
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'type' => $_GET['type'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$transactions = getTransactions($pdo, $_SESSION['user_id'], $filters);
$categories = getCategories($pdo, $_SESSION['user_id']);

$pageTitle = 'Data Transaksi';
$currentPage = 'transaksi.php';
include __DIR__ . '/includes/header.php';
?>

<div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 mb-8">
    <div class="flex flex-col lg:flex-row gap-4 items-end justify-between">
        <form method="GET" action="transaksi.php" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end flex-1">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase">Tanggal Mulai</label>
                <input type="date" name="start_date" value="<?php echo $filters['start_date']; ?>" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase">Tanggal Selesai</label>
                <input type="date" name="end_date" value="<?php echo $filters['end_date']; ?>" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-primary">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase">Kategori</label>
                <select name="category_id" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-primary">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $filters['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase">Tipe</label>
                <select name="type" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-primary">
                    <option value="">Semua Tipe</option>
                    <option value="pemasukan" <?php echo $filters['type'] == 'pemasukan' ? 'selected' : ''; ?>>Pemasukan</option>
                    <option value="pengeluaran" <?php echo $filters['type'] == 'pengeluaran' ? 'selected' : ''; ?>>Pengeluaran</option>
                </select>
            </div>
            <div class="flex gap-2">
                <input type="text" name="search" placeholder="Cari Transaksi..." value="<?php echo $filters['search']; ?>" class="border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-primary w-full">
                <button type="submit" class="bg-primary hover:bg-[#4f739c] text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition duration-150"><i class="fa-solid fa-search"></i></button>
            </div>
        </form>
        <button onclick="toggleModal(true)" class="bg-primary hover:bg-[#4f739c] text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition duration-150 flex items-center gap-1.5 shadow-sm shadow-indigo-100/25">
            <i class="fa-solid fa-plus"></i> Tambah Transaksi
        </button>
    </div>
</div>

<!-- Transactions Table Card -->
<div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 text-sm font-bold border-b border-slate-100">
                    <th class="pb-4">Tanggal</th>
                    <th class="pb-4">Deskripsi</th>
                    <th class="pb-4">Kategori</th>
                    <th class="pb-4">Tipe</th>
                    <th class="pb-4">Jumlah</th>
                    <th class="pb-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (!empty($transactions)): ?>
                    <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td class="py-4 text-slate-500"><?php echo date('j F Y, H.i', strtotime($tx['date'])); ?></td>
                        <td class="py-4 font-semibold text-slate-700"><?php echo $tx['description']; ?></td>
                        <td class="py-4">
                            <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full text-white" style="background-color: <?php echo $tx['category_color'] ?? '#94a3b8'; ?>">
                                <?php echo $tx['category_name'] ?? 'Tanpa Kategori'; ?>
                            </span>
                        </td>
                        <td class="py-4">
                            <span class="<?php echo $tx['type'] == 'pemasukan' ? 'text-emerald-500' : 'text-rose-500'; ?> font-bold">
                                <?php echo $tx['type'] == 'pemasukan' ? 'Pemasukan' : 'Pengeluaran'; ?>
                            </span>
                        </td>
                        <td class="py-4 font-bold <?php echo $tx['type'] == 'pemasukan' ? 'text-emerald-500' : 'text-rose-500'; ?>">
                            <?php echo $tx['type'] == 'pemasukan' ? '+' : '-'; ?>Rp <?php echo number_format($tx['amount'], 0, ',', '.'); ?>
                        </td>
                        <td class="py-4 text-center flex items-center justify-center gap-2">
                            <button onclick="openEditModal(<?php echo $tx['id']; ?>, <?php echo $tx['category_id'] ?? 'null'; ?>, '<?php echo $tx['type']; ?>', <?php echo $tx['amount']; ?>, '<?php echo date('Y-m-d\TH:i', strtotime($tx['date'])); ?>', '<?php echo addslashes($tx['description']); ?>')" class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-lg hover:bg-slate-50 flex items-center justify-center transition duration-150">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <a href="transaksi.php?delete=<?php echo $tx['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini?')" class="text-rose-500 hover:text-rose-700 w-8 h-8 rounded-lg hover:bg-rose-50 flex items-center justify-center transition duration-150">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-400">Belum ada transaksi terdaftar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Transaksi -->
<div id="txModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-lg rounded-3xl p-8 shadow-2xl border border-slate-100 m-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-slate-800">Tambah Transaksi Baru</h3>
            <button onclick="toggleModal(false)" class="text-slate-400 hover:text-slate-600 text-xl"><i class="fa-solid fa-times"></i></button>
        </div>
        <form action="transaksi.php" method="POST">
            <div class="space-y-5">
                <!-- Tipe -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Tipe Transaksi</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="border border-slate-200 rounded-xl p-3.5 flex items-center justify-center gap-2 cursor-pointer hover:bg-slate-50 transition duration-150">
                            <input type="radio" name="type" value="pemasukan" required class="text-primary focus:ring-primary">
                            <span class="text-sm font-medium text-slate-700">Pemasukan</span>
                        </label>
                        <label class="border border-slate-200 rounded-xl p-3.5 flex items-center justify-center gap-2 cursor-pointer hover:bg-slate-50 transition duration-150">
                            <input type="radio" name="type" value="pengeluaran" required checked class="text-primary focus:ring-primary">
                            <span class="text-sm font-medium text-slate-700">Pengeluaran</span>
                        </label>
                    </div>
                </div>

                <!-- Kategori -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Kategori</label>
                    <select name="category_id" required class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?> (<?php echo ucfirst($cat['type']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Jumlah -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Jumlah (Rp)</label>
                    <input type="text" name="amount" required placeholder="Masukkan nominal" class="number-format-input w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>

                <!-- Tanggal -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Tanggal</label>
                    <input type="datetime-local" name="date" required value="<?php echo date('Y-m-d\TH:i'); ?>" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>

                <!-- Deskripsi -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Deskripsi</label>
                    <input type="text" name="description" required placeholder="Contoh: Beli Makan Siang" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>

                <!-- Button -->
                <div class="pt-4 flex gap-4">
                    <button type="button" onclick="toggleModal(false)" class="flex-1 border border-slate-200 text-slate-500 font-semibold py-3 rounded-xl hover:bg-slate-50 transition duration-150">Batal</button>
                    <button type="submit" class="flex-1 bg-primary hover:bg-[#4f739c] text-white font-semibold py-3 rounded-xl shadow-md shadow-[#5B84B6]/20 transition duration-150">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Transaksi -->
<div id="editTxModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-lg rounded-3xl p-8 shadow-2xl border border-slate-100 m-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-slate-800">Ubah Transaksi</h3>
            <button onclick="toggleEditModal(false)" class="text-slate-400 hover:text-slate-600 text-xl"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="editTxForm" action="transaksi.php" method="POST">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="id" id="edit_id">
            <div class="space-y-5">
                <!-- Tipe -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Tipe Transaksi</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="border border-slate-200 rounded-xl p-3.5 flex items-center justify-center gap-2 cursor-pointer hover:bg-slate-50 transition duration-150">
                            <input type="radio" name="type" id="edit_type_pemasukan" value="pemasukan" required class="text-primary focus:ring-primary">
                            <span class="text-sm font-medium text-slate-700">Pemasukan</span>
                        </label>
                        <label class="border border-slate-200 rounded-xl p-3.5 flex items-center justify-center gap-2 cursor-pointer hover:bg-slate-50 transition duration-150">
                            <input type="radio" name="type" id="edit_type_pengeluaran" value="pengeluaran" required class="text-primary focus:ring-primary">
                            <span class="text-sm font-medium text-slate-700">Pengeluaran</span>
                        </label>
                    </div>
                </div>

                <!-- Kategori -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Kategori</label>
                    <select name="category_id" id="edit_category_id" required class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?> (<?php echo ucfirst($cat['type']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Jumlah -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Jumlah (Rp)</label>
                    <input type="text" name="amount" id="edit_amount" required placeholder="Masukkan nominal" class="number-format-input w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>

                <!-- Tanggal -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Tanggal</label>
                    <input type="datetime-local" name="date" id="edit_date" required class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>

                <!-- Deskripsi -->
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Deskripsi</label>
                    <input type="text" name="description" id="edit_description" required placeholder="Contoh: Beli Makan Siang" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>

                <!-- Button -->
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
    const modal = document.getElementById('txModal');
    if (show) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}

function toggleEditModal(show) {
    const modal = document.getElementById('editTxModal');
    if (show) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}

function openEditModal(id, categoryId, type, amount, date, description) {
    document.getElementById('edit_id').value = id;
    
    if (type === 'pemasukan') {
        document.getElementById('edit_type_pemasukan').checked = true;
    } else {
        document.getElementById('edit_type_pengeluaran').checked = true;
    }
    
    document.getElementById('edit_category_id').value = categoryId;
    
    let formattedAmount = amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    document.getElementById('edit_amount').value = formattedAmount;
    
    document.getElementById('edit_date').value = date;
    document.getElementById('edit_description').value = description;
    
    toggleEditModal(true);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>