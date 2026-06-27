<?php
// budgeting.php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDBConnection();
$user = getCurrentUser($pdo);

// Handle POST (Create & Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id'] ?? 0;
    $amount = str_replace('.', '', $_POST['amount'] ?? '0');
    $month = date('n');
    $year = date('Y');
    
    if (isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
        // Update
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("UPDATE budgets SET category_id = ?, amount = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$category_id, $amount, $id, $_SESSION['user_id']]);
        $_SESSION['success'] = 'Budget berhasil diupdate!';
    } else {
        // Check if budget already exists for this category this month
        $stmt = $pdo->prepare("SELECT id FROM budgets WHERE user_id = ? AND category_id = ? AND month = ? AND year = ?");
        $stmt->execute([$_SESSION['user_id'], $category_id, $month, $year]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Budget untuk kategori ini sudah ada bulan ini!';
        } else {
            // Create
            $stmt = $pdo->prepare("INSERT INTO budgets (user_id, category_id, amount, month, year, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$_SESSION['user_id'], $category_id, $amount, $month, $year]);
            $_SESSION['success'] = 'Budget berhasil ditambahkan!';
        }
    }
    redirect('budgeting.php');
}

// Handle DELETE
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    $_SESSION['success'] = 'Budget berhasil dihapus!';
    redirect('budgeting.php');
}

// Get budgets for current month
$month = date('n');
$year = date('Y');

$stmt = $pdo->prepare("
    SELECT b.*, c.name as category_name, c.color as category_color,
        COALESCE((
            SELECT SUM(amount) 
            FROM transactions 
            WHERE category_id = b.category_id 
            AND type = 'pengeluaran'
            AND EXTRACT(MONTH FROM date) = b.month 
            AND EXTRACT(YEAR FROM date) = b.year
            AND user_id = ?
        ), 0) as spent
    FROM budgets b
    JOIN categories c ON b.category_id = c.id
    WHERE b.user_id = ? 
    AND b.month = ?
    AND b.year = ?
    ORDER BY b.amount DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $month, $year]);
$budgets = $stmt->fetchAll();

// Calculate totals
$totalBudget = 0;
$totalSpent = 0;
foreach ($budgets as &$budget) {
    $budget['remaining'] = $budget['amount'] - $budget['spent'];
    $budget['percentage'] = $budget['amount'] > 0 ? min(100, round(($budget['spent'] / $budget['amount']) * 100)) : 0;
    $totalBudget += $budget['amount'];
    $totalSpent += $budget['spent'];
}
$totalRemaining = $totalBudget - $totalSpent;
$totalPercentage = $totalBudget > 0 ? min(100, round(($totalSpent / $totalBudget) * 100)) : 0;

// Get all categories for dropdown
$categories = getCategories($pdo, $_SESSION['user_id']);

$pageTitle = 'Budget Bulanan';
$currentPage = 'budgeting.php';
include __DIR__ . '/includes/header.php';
?>

<div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 mb-8 flex justify-between items-center">
    <div class="flex items-center gap-3">
        <h3 class="font-bold text-slate-800">Anggaran Kategori Pengeluaran</h3>
    </div>
    <div class="flex gap-2">
        <span class="bg-slate-50 border border-slate-200 text-slate-700 font-semibold px-4 py-2.5 rounded-xl text-sm flex items-center">
            <?php echo date('F Y'); ?>
        </span>
        <button onclick="toggleModal(true)" class="bg-primary hover:bg-[#4f739c] text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition duration-150 flex items-center gap-1.5 shadow-sm shadow-indigo-100/25">
            <i class="fa-solid fa-plus"></i> Atur Anggaran
        </button>
    </div>
</div>

<!-- Budgets Table Card -->
<div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 text-sm font-bold border-b border-slate-100">
                    <th class="pb-4 pl-4">Kategori</th>
                    <th class="pb-4">Budget</th>
                    <th class="pb-4">Terpakai</th>
                    <th class="pb-4">Sisa</th>
                    <th class="pb-4 w-40">Persentase</th>
                    <th class="pb-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (!empty($budgets)): ?>
                    <?php foreach ($budgets as $budget): ?>
                    <tr>
                        <td class="py-4 pl-4 font-semibold text-slate-700 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full" style="background-color: <?php echo $budget['category_color']; ?>"></span>
                            <?php echo $budget['category_name']; ?>
                        </td>
                        <td class="py-4 text-slate-700">Rp <?php echo number_format($budget['amount'], 0, ',', '.'); ?></td>
                        <td class="py-4 text-rose-500 font-semibold">Rp <?php echo number_format($budget['spent'], 0, ',', '.'); ?></td>
                        <td class="py-4 text-slate-600 font-medium">Rp <?php echo number_format($budget['remaining'], 0, ',', '.'); ?></td>
                        <td class="py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-24 bg-slate-100 rounded-full h-2">
                                    <div class="rounded-full h-2 transition-all duration-300" style="width: <?php echo $budget['percentage']; ?>%; background-color: <?php echo $budget['category_color']; ?>"></div>
                                </div>
                                <span class="text-xs text-slate-400 font-medium"><?php echo $budget['percentage']; ?>%</span>
                            </div>
                        </td>
                        <td class="py-4 text-center flex items-center justify-center gap-2">
                            <button onclick="openEditModal(<?php echo $budget['id']; ?>, <?php echo $budget['category_id']; ?>, <?php echo $budget['amount']; ?>)" class="text-slate-400 hover:text-slate-600 w-8 h-8 rounded-lg hover:bg-slate-50 flex items-center justify-center transition duration-150">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <a href="budgeting.php?delete=<?php echo $budget['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus budget ini?')" class="text-rose-500 hover:text-rose-700 w-8 h-8 rounded-lg hover:bg-rose-50 flex items-center justify-center transition duration-150">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-400">Belum ada anggaran bulanan aktif.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($budgets)): ?>
            <tfoot>
                <tr class="font-bold border-t border-slate-200 bg-slate-50/50">
                    <td class="py-4 pl-4 text-slate-800">Total</td>
                    <td class="py-4 text-slate-800">Rp <?php echo number_format($totalBudget, 0, ',', '.'); ?></td>
                    <td class="py-4 text-rose-500">Rp <?php echo number_format($totalSpent, 0, ',', '.'); ?></td>
                    <td class="py-4 text-primary">Rp <?php echo number_format($totalRemaining, 0, ',', '.'); ?></td>
                    <td class="py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-24 bg-slate-200 rounded-full h-2">
                                <div class="rounded-full h-2 bg-emerald-500" style="width: <?php echo $totalPercentage; ?>%"></div>
                            </div>
                            <span class="text-xs text-slate-600"><?php echo $totalPercentage; ?>%</span>
                        </div>
                    </td>
                    <td class="py-4"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- Modal Atur Anggaran -->
<div id="budgetModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-lg rounded-3xl p-8 shadow-2xl border border-slate-100 m-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-slate-800">Atur Anggaran Bulanan</h3>
            <button onclick="toggleModal(false)" class="text-slate-400 hover:text-slate-600 text-xl"><i class="fa-solid fa-times"></i></button>
        </div>
        <form action="budgeting.php" method="POST">
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Pilih Kategori</label>
                    <select name="category_id" required class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Batas Anggaran (Rp)</label>
                    <input type="text" name="amount" required placeholder="Masukkan jumlah budget" class="number-format-input w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>
                <div class="pt-4 flex gap-4">
                    <button type="button" onclick="toggleModal(false)" class="flex-1 border border-slate-200 text-slate-500 font-semibold py-3 rounded-xl hover:bg-slate-50 transition duration-150">Batal</button>
                    <button type="submit" class="flex-1 bg-primary hover:bg-[#4f739c] text-white font-semibold py-3 rounded-xl shadow-md shadow-[#5B84B6]/20 transition duration-150 font-medium">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Anggaran -->
<div id="editBudgetModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-lg rounded-3xl p-8 shadow-2xl border border-slate-100 m-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-slate-800">Ubah Anggaran Bulanan</h3>
            <button onclick="toggleEditModal(false)" class="text-slate-400 hover:text-slate-600 text-xl"><i class="fa-solid fa-times"></i></button>
        </div>
        <form id="editBudgetForm" action="budgeting.php" method="POST">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="id" id="edit_id">
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Pilih Kategori</label>
                    <select name="category_id" id="edit_category_id" required class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2">Batas Anggaran (Rp)</label>
                    <input type="text" name="amount" id="edit_amount" required placeholder="Masukkan jumlah budget" class="number-format-input w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>
                <div class="pt-4 flex gap-4">
                    <button type="button" onclick="toggleEditModal(false)" class="flex-1 border border-slate-200 text-slate-500 font-semibold py-3 rounded-xl hover:bg-slate-50 transition duration-150">Batal</button>
                    <button type="submit" class="flex-1 bg-primary hover:bg-[#4f739c] text-white font-semibold py-3 rounded-xl shadow-md shadow-[#5B84B6]/20 transition duration-150 font-medium">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(show) {
    const modal = document.getElementById('budgetModal');
    if (show) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}

function toggleEditModal(show) {
    const modal = document.getElementById('editBudgetModal');
    if (show) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}

function openEditModal(id, categoryId, amount) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_category_id').value = categoryId;
    
    let formattedAmount = amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    document.getElementById('edit_amount').value = formattedAmount;
    
    toggleEditModal(true);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>