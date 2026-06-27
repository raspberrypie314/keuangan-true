<?php
// laporan.php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDBConnection();
$user = getCurrentUser($pdo);

// Get filter parameters
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get totals
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'pemasukan' THEN amount ELSE 0 END), 0) as total_income,
        COALESCE(SUM(CASE WHEN type = 'pengeluaran' THEN amount ELSE 0 END), 0) as total_outcome
    FROM transactions 
    WHERE user_id = ? 
    AND EXTRACT(MONTH FROM date) = ? 
    AND EXTRACT(YEAR FROM date) = ?
");
$stmt->execute([$_SESSION['user_id'], $selectedMonth, $selectedYear]);
$totals = $stmt->fetch();

$totalIncome = $totals['total_income'];
$totalOutcome = $totals['total_outcome'];
$selisih = $totalIncome - $totalOutcome;

// Get summary per category
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.name,
        c.color,
        COALESCE(SUM(CASE WHEN t.type = 'pemasukan' THEN t.amount ELSE 0 END), 0) as pemasukan,
        COALESCE(SUM(CASE WHEN t.type = 'pengeluaran' THEN t.amount ELSE 0 END), 0) as pengeluaran
    FROM categories c
    LEFT JOIN transactions t ON c.id = t.category_id 
        AND t.user_id = ? 
        AND EXTRACT(MONTH FROM t.date) = ? 
        AND EXTRACT(YEAR FROM t.date) = ?
    WHERE c.user_id = ?
    GROUP BY c.id, c.name, c.color
    HAVING pemasukan > 0 OR pengeluaran > 0
    ORDER BY c.name
");
$stmt->execute([$_SESSION['user_id'], $selectedMonth, $selectedYear, $_SESSION['user_id']]);
$summary = $stmt->fetchAll();

foreach ($summary as &$row) {
    $row['selisih'] = $row['pemasukan'] - $row['pengeluaran'];
}

$pageTitle = 'Laporan Keuangan';
$currentPage = 'laporan.php';
include __DIR__ . '/includes/header.php';
?>

<div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 mb-8">
    <form method="GET" action="laporan.php" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase">Pilih Laporan</label>
            <select name="type" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-primary">
                <option value="bulanan">Ringkasan Laporan</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase">Bulan</label>
            <select name="month" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-primary">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $selectedMonth == $m ? 'selected' : ''; ?>>
                        <?php echo getMonthName($m); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase">Tahun</label>
            <select name="year" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-primary">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $selectedYear == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="w-full bg-primary hover:bg-[#4f739c] text-white font-semibold py-2.5 rounded-xl text-sm transition duration-150">Tampilkan Laporan</button>
        </div>
    </form>
</div>

<!-- Laporan Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Total Pemasukan -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-lg">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-400 block uppercase">Total Pemasukan</span>
                <h3 class="text-xl font-bold text-slate-800 mt-0.5">Rp <?php echo number_format($totalIncome, 0, ',', '.'); ?></h3>
            </div>
        </div>
    </div>

    <!-- Total Pengeluaran -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-lg">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-400 block uppercase">Total Pengeluaran</span>
                <h3 class="text-xl font-bold text-slate-800 mt-0.5">Rp <?php echo number_format($totalOutcome, 0, ',', '.'); ?></h3>
            </div>
        </div>
    </div>

    <!-- Selisih -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center text-lg">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-400 block uppercase">Selisih (Net)</span>
                <h3 class="text-xl font-bold <?php echo $selisih >= 0 ? 'text-primary' : 'text-rose-500'; ?> mt-0.5">
                    Rp <?php echo number_format($selisih, 0, ',', '.'); ?>
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- Laporan Table -->
<div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
    <div class="flex justify-between items-center mb-6">
        <h3 class="font-bold text-slate-800">Ringkasan Per Kategori</h3>
        <span class="text-xs text-slate-400 font-medium bg-slate-50 px-3 py-1.5 rounded-full">
            <?php echo getMonthName($selectedMonth) . ' ' . $selectedYear; ?>
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-slate-500 text-sm font-bold border-b border-slate-100">
                    <th class="pb-4 pl-4">Kategori</th>
                    <th class="pb-4">Pemasukan</th>
                    <th class="pb-4">Pengeluaran</th>
                    <th class="pb-4 text-right pr-4">Selisih</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (!empty($summary)): ?>
                    <?php foreach ($summary as $row): ?>
                    <tr>
                        <td class="py-4 pl-4 font-semibold text-slate-700 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full" style="background-color: <?php echo $row['color']; ?>"></span>
                            <?php echo $row['name']; ?>
                        </td>
                        <td class="py-4 text-emerald-500 font-semibold">
                            <?php echo $row['pemasukan'] > 0 ? 'Rp ' . number_format($row['pemasukan'], 0, ',', '.') : '-'; ?>
                        </td>
                        <td class="py-4 text-rose-500 font-semibold">
                            <?php echo $row['pengeluaran'] > 0 ? 'Rp ' . number_format($row['pengeluaran'], 0, ',', '.') : '-'; ?>
                        </td>
                        <td class="py-4 text-right pr-4 font-bold <?php echo $row['selisih'] >= 0 ? 'text-primary' : 'text-rose-500'; ?>">
                            Rp <?php echo number_format($row['selisih'], 0, ',', '.'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="py-10 text-center text-slate-400">Tidak ada transaksi terdaftar pada periode ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>