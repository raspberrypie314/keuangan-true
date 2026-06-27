<?php
// pengaturan.php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pdo = getDBConnection();
$user = getCurrentUser($pdo);

// Handle update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    
    $errors = [];
    if (empty($name)) $errors[] = 'Nama wajib diisi!';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid!';
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone_number = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $email, $phone_number, $_SESSION['user_id']]);
        $_SESSION['user_name'] = $name;
        $_SESSION['success'] = 'Profil berhasil diperbarui!';
        redirect('pengaturan.php');
    }
}

// Handle change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $new_password_confirmation = $_POST['new_password_confirmation'] ?? '';
    
    $errors = [];
    if (empty($old_password)) $errors[] = 'Password lama wajib diisi!';
    if (strlen($new_password) < 6) $errors[] = 'Password baru minimal 6 karakter!';
    if ($new_password !== $new_password_confirmation) $errors[] = 'Konfirmasi password tidak cocok!';
    
    if (empty($errors)) {
        // Verify old password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $currentUser = $stmt->fetch();
        
        if (password_verify($old_password, $currentUser['password'])) {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
            $_SESSION['success'] = 'Password berhasil diubah!';
            redirect('pengaturan.php');
        } else {
            $errors[] = 'Password lama salah!';
        }
    }
}

$pageTitle = 'Pengaturan Akun';
$currentPage = 'pengaturan.php';
include __DIR__ . '/includes/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Left Column: Informasi Akun -->
    <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100 flex flex-col justify-between">
        <div>
            <h3 class="font-bold text-slate-800 text-base mb-6 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-user-gear text-[#5B84B6]"></i> Informasi Akun
            </h3>

            <?php if (!empty($errors)): ?>
                <div class="mb-5 p-4 bg-red-50 border border-red-200 text-red-600 rounded-xl text-xs">
                    <ul class="list-disc pl-4 space-y-1">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo $err; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="pengaturan.php" method="POST">
                <input type="hidden" name="update_profile" value="1">
                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Lengkap</label>
                        <input type="text" name="name" value="<?php echo $user['name']; ?>" required class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-[#5B84B6]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Email</label>
                        <input type="email" name="email" value="<?php echo $user['email']; ?>" required class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-[#5B84B6]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">No. Handphone</label>
                        <input type="text" name="phone_number" value="<?php echo $user['phone_number'] ?? '0812-3456-7890'; ?>" class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-[#5B84B6]">
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="bg-[#5B84B6] hover:bg-[#4f739c] text-white font-semibold px-6 py-3 rounded-xl text-sm transition duration-150 shadow-sm shadow-[#5B84B6]/20">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Keamanan, Preferensi & Notifikasi -->
    <div class="space-y-8">
        <!-- Keamanan (Ubah Password) -->
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
            <h3 class="font-bold text-slate-800 text-base mb-6 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-[#5B84B6]"></i> Keamanan
            </h3>
            <form action="pengaturan.php" method="POST">
                <input type="hidden" name="change_password" value="1">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-2">Password Lama</label>
                        <input type="password" name="old_password" required placeholder="Password Lama" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#5B84B6]">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-2">Password Baru</label>
                        <input type="password" name="new_password" required placeholder="Password Baru" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#5B84B6]">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-2">Konfirmasi Password Baru</label>
                        <input type="password" name="new_password_confirmation" required placeholder="Konfirmasi Password Baru" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#5B84B6]">
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="bg-[#5B84B6] hover:bg-[#4f739c] text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition duration-150 shadow-sm shadow-[#5B84B6]/20">
                            Ubah Password
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Preferensi -->
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
            <h3 class="font-bold text-slate-800 text-base mb-6 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-palette text-[#5B84B6]"></i> Preferensi
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-2">Tema</label>
                    <select id="themeSelect" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#5B84B6]">
                        <option value="light">Light</option>
                        <option value="dark">Dark</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-2">Bahasa</label>
                    <select id="langSelect" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#5B84B6]">
                        <option value="id">Bahasa Indonesia</option>
                        <option value="en">English</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Notifikasi -->
        <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
            <h3 class="font-bold text-slate-800 text-base mb-6 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-bell text-[#5B84B6]"></i> Notifikasi
            </h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold text-slate-700 text-sm">Notifikasi Transaksi</h4>
                        <p class="text-xs text-slate-400">Terima notifikasi instan saat menambahkan transaksi</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#5B84B6]"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold text-slate-700 text-sm">Notifikasi Budget</h4>
                        <p class="text-xs text-slate-400">Peringatan saat pengeluaran melampaui 80% budget bulanan</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#5B84B6]"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-semibold text-slate-700 text-sm">Email Notifikasi</h4>
                        <p class="text-xs text-slate-400">Terima laporan ringkasan bulanan via email</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#5B84B6]"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const themeSelect = document.getElementById('themeSelect');
    const currentTheme = localStorage.getItem('theme') || 'light';
    themeSelect.value = currentTheme;

    themeSelect.addEventListener('change', function () {
        const selectedTheme = themeSelect.value;
        localStorage.setItem('theme', selectedTheme);
        if (selectedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>