<?php
// dashboard.php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDBConnection();
$user = getCurrentUser($pdo);

// Get dashboard data
$data = getDashboardData($pdo, $_SESSION['user_id']);

$pageTitle = 'Dashboard';
$currentPage = 'dashboard.php';
include __DIR__ . '/includes/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <!-- Total Saldo -->
    <div class="bg-primary rounded-3xl p-6 text-white border border-primary/50 flex flex-col justify-between h-40">
        <div class="flex justify-between items-start">
            <span class="text-sm font-semibold text-white/80">Total Saldo</span>
            <div class="w-8 h-8 rounded-lg bg-white/20 text-white flex items-center justify-center">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-white"><?php echo formatRupiah($data['saldo']); ?></h3>
            <span class="text-xs text-white/90 font-medium mt-1 block flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block animate-pulse"></span> Terhubung ke Kas
            </span>
        </div>
    </div>

    <!-- Total Pemasukan -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between h-40">
        <div class="flex justify-between items-start">
            <span class="text-sm font-medium text-slate-500">Total Pemasukan</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-slate-800"><?php echo formatRupiah($data['totalIncome']); ?></h3>
            <span class="text-xs text-emerald-500 font-semibold mt-1 block">
                <i class="fa-solid fa-caret-up"></i> Pemasukan terdaftar
            </span>
        </div>
    </div>

    <!-- Total Pengeluaran -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between h-40">
        <div class="flex justify-between items-start">
            <span class="text-sm font-medium text-slate-500">Total Pengeluaran</span>
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-slate-800"><?php echo formatRupiah($data['totalOutcome']); ?></h3>
            <span class="text-xs text-rose-500 font-semibold mt-1 block">
                <i class="fa-solid fa-caret-down"></i> Pengeluaran terdaftar
            </span>
        </div>
    </div>

    <!-- Total Transaksi -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between h-40">
        <div class="flex justify-between items-start">
            <span class="text-sm font-medium text-slate-500">Total Transaksi</span>
            <div class="w-8 h-8 rounded-lg bg-slate-50 text-slate-500 flex items-center justify-center">
                <i class="fa-solid fa-exchange-alt"></i>
            </div>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-slate-800"><?php echo $data['totalTransactions']; ?></h3>
            <span class="text-xs text-slate-400 mt-1 block">Transaksi terdaftar</span>
        </div>
    </div>

    <!-- Sisa Budget Bulan Ini -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 flex flex-col justify-between h-40">
        <div class="flex justify-between items-start">
            <span class="text-sm font-medium text-slate-500">Sisa Budget Bulan Ini</span>
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-500 flex items-center justify-center">
                <i class="fa-solid fa-piggy-bank"></i>
            </div>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-slate-800"><?php echo formatRupiah($data['remainingBudget']); ?></h3>
            <span class="text-xs text-purple-500 font-semibold mt-1 block">Batas aman belanja</span>
        </div>
    </div>
</div>

<!-- Main Section: Charts & Budget List -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Bar Chart Pemasukan & Pengeluaran -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 lg:col-span-2">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-bold text-slate-800">Grafik Data Pemasukan & Pengeluaran Per Bulan</h3>
            <span class="text-xs text-slate-400 font-medium bg-slate-50 px-3 py-1.5 rounded-full">Tahun <?php echo date('Y'); ?></span>
        </div>
        <div class="h-64">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    <!-- Budget Bulanan Progress -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-bold text-slate-800">Budget Bulanan</h3>
            <a href="budgeting.php" class="text-xs text-primary font-semibold hover:underline">Lihat Semua Budget</a>
        </div>
        <div class="space-y-5">
            <?php if (!empty($data['budgets'])): ?>
                <?php foreach ($data['budgets'] as $budget): ?>
                <div>
                    <div class="flex justify-between text-sm mb-1.5">
                        <span class="font-medium text-slate-600 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" style="background-color: <?php echo $budget['category_color']; ?>"></span>
                            <?php echo $budget['category_name']; ?>
                        </span>
                        <span class="text-xs text-slate-400">
                            <?php echo $budget['percentage']; ?>% (Rp <?php echo number_format($budget['spent'], 0, ',', '.'); ?> / Rp <?php echo number_format($budget['amount'], 0, ',', '.'); ?>)
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="rounded-full h-2 transition-all duration-500" style="width: <?php echo $budget['percentage']; ?>%; background-color: <?php echo $budget['category_color']; ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-10 text-slate-400">
                    <i class="fa-solid fa-sliders text-3xl mb-3"></i>
                    <p class="text-xs">Belum ada anggaran bulanan yang dibuat.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bottom Section: Transactions, Donut Chart & Calendar -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Recent Transactions Table -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 lg:col-span-2">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-bold text-slate-800">Transaksi Terbaru</h3>
            <button onclick="toggleModal(true)" class="text-xs bg-primary hover:bg-[#4f739c] text-white font-semibold px-4 py-2 rounded-xl transition duration-150 flex items-center gap-1.5 shadow-sm shadow-[#5B84B6]/20">
                <i class="fa-solid fa-plus"></i> Tambah Transaksi
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-400 text-xs font-semibold uppercase border-b border-slate-50">
                        <th class="pb-3">Tanggal</th>
                        <th class="pb-3">Deskripsi</th>
                        <th class="pb-3">Kategori</th>
                        <th class="pb-3">Tipe</th>
                        <th class="pb-3 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-sm">
                    <?php if (!empty($data['recentTransactions'])): ?>
                        <?php foreach ($data['recentTransactions'] as $tx): ?>
                        <tr>
                            <td class="py-3.5 text-slate-500"><?php echo date('d F Y, H:i', strtotime($tx['date'])); ?></td>
                            <td class="py-3.5 font-medium text-slate-700"><?php echo $tx['description']; ?></td>
                            <td class="py-3.5">
                                <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full text-white" style="background-color: <?php echo $tx['category_color'] ?? '#94a3b8'; ?>">
                                    <?php echo $tx['category_name'] ?? 'Tanpa Kategori'; ?>
                                </span>
                            </td>
                            <td class="py-3.5">
                                <?php if ($tx['type'] == 'pemasukan'): ?>
                                <span class="text-emerald-500 font-semibold flex items-center gap-1"><i class="fa-solid fa-arrow-trend-up text-xs"></i> Pemasukan</span>
                                <?php else: ?>
                                <span class="text-rose-500 font-semibold flex items-center gap-1"><i class="fa-solid fa-arrow-trend-down text-xs"></i> Pengeluaran</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 text-right font-bold <?php echo $tx['type'] == 'pemasukan' ? 'text-emerald-500' : 'text-rose-500'; ?>">
                                <?php echo $tx['type'] == 'pemasukan' ? '+' : '-'; ?> Rp <?php echo number_format($tx['amount'], 0, ',', '.'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-10 text-center text-slate-400">Belum ada transaksi terdaftar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Category Expense Donut & Calendar -->
    <div class="space-y-8">
        <!-- Donut Chart -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
            <h3 class="font-bold text-slate-800 mb-6">Pengeluaran Per Kategori</h3>
            <div class="h-48 relative">
                <?php if (!empty($data['categoriesLabels'])): ?>
                <canvas id="categoryChart"></canvas>
                <?php else: ?>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 text-xs">
                    <i class="fa-solid fa-chart-pie text-3xl mb-2"></i>
                    Belum ada data pengeluaran per kategori.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Calendar Widget -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
            <div class="flex justify-between items-center bg-primary text-white p-3 rounded-2xl mb-4 shadow-sm">
                <span class="font-bold text-sm"><i class="fa-regular fa-calendar-days mr-1.5"></i> Kalender</span>
                <span class="text-xs font-semibold"><?php echo date('F Y'); ?></span>
            </div>
            <div class="grid grid-cols-7 gap-2 text-center text-xs">
                <!-- Day Headers -->
                <span class="font-semibold text-rose-500">Min</span>
                <span class="font-semibold text-slate-400">Sen</span>
                <span class="font-semibold text-slate-400">Sel</span>
                <span class="font-semibold text-slate-400">Rab</span>
                <span class="font-semibold text-slate-400">Kam</span>
                <span class="font-semibold text-slate-400">Jum</span>
                <span class="font-semibold text-slate-400">Sab</span>

                <?php
                $today = date('j');
                $startOfMonth = date('w', strtotime(date('Y-m-01')));
                $daysInMonth = date('t');
                ?>
                
                <!-- Empty cells for padding -->
                <?php for ($i = 0; $i < $startOfMonth; $i++): ?>
                <span></span>
                <?php endfor; ?>

                <!-- Month Days -->
                <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                <span class="py-1.5 rounded-lg flex items-center justify-center font-medium <?php echo $day == $today ? 'bg-primary text-white font-bold shadow-md shadow-indigo-100/30' : 'text-slate-600 hover:bg-slate-50 cursor-pointer'; ?>">
                    <?php echo $day; ?>
                </span>
                <?php endfor; ?>
            </div>
        </div>
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
                        <?php
                        $categories = getCategories($pdo, $_SESSION['user_id']);
                        foreach ($categories as $cat):
                        ?>
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

<script>
function toggleModal(show) {
    const modal = document.getElementById('txModal');
    if (show) {
        modal.classList.remove('hidden');
    } else {
        modal.classList.add('hidden');
    }
}

// Chart 1: Pemasukan & Pengeluaran Per Bulan
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($data['months']); ?>,
        datasets: [
            {
                label: 'Pemasukan',
                data: <?php echo json_encode($data['incomeData']); ?>,
                backgroundColor: 'rgba(91, 132, 182, 0.35)',
                borderColor: '#5B84B6',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false
            },
            {
                label: 'Pengeluaran',
                data: <?php echo json_encode($data['outcomeData']); ?>,
                backgroundColor: 'rgba(244, 63, 94, 0.35)',
                borderColor: 'rgb(244, 63, 94)',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    font: { size: 11, family: "'Inter', sans-serif" }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false }
            },
            y: {
                grid: { color: '#f3f4f6' },
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});

// Chart 2: Pengeluaran Per Kategori (Donut Chart)
<?php if (!empty($data['categoriesLabels'])): ?>
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($data['categoriesLabels']); ?>,
        datasets: [{
            data: <?php echo json_encode($data['categoriesValues']); ?>,
            backgroundColor: <?php echo json_encode($data['categoriesColors']); ?>,
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 10,
                    font: { size: 10, family: "'Inter', sans-serif" }
                }
            }
        },
        cutout: '65%'
    }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>