<?php
// login.php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireGuest();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (login($username, $password)) {
        redirect('dashboard.php');
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DompetKu - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #dbeafe 100%);
        }
        .bg-primary { background-color: #5B84B6; }
        .text-primary { color: #5B84B6; }
        .border-primary { border-color: #5B84B6; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md bg-white rounded-3xl p-8 shadow-2xl border border-slate-100/50">
        <div class="flex flex-col items-center mb-8">
            <div class="w-14 h-14 bg-primary rounded-2xl flex items-center justify-center text-white text-3xl font-bold shadow-lg shadow-indigo-100 mb-4">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Dompet<span class="text-primary">Ku</span></h2>
            <p class="text-sm text-slate-400 mt-1.5">Aplikasi Pengelolaan Keuangan</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-600 rounded-2xl text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-base"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-5">
            <div>
                <label for="usernameInput" class="block text-xs font-bold text-slate-500 uppercase mb-2">Username / Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <input type="text" name="username" id="usernameInput" required value="<?php echo $_POST['username'] ?? ''; ?>" placeholder="Masukkan username atau email" class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>
            </div>

            <div>
                <label for="passwordInput" class="block text-xs font-bold text-slate-500 uppercase mb-2">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <input type="password" name="password" id="passwordInput" required placeholder="Masukkan password" class="w-full border border-slate-200 rounded-xl pl-10 pr-10 py-3 text-sm focus:outline-none focus:border-primary">
                    <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600">
                        <i id="passwordToggleIcon" class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full bg-primary hover:bg-[#4f739c] text-white font-semibold py-3 rounded-xl shadow-lg shadow-[#5B84B6]/25 transition duration-150 flex items-center justify-center gap-2">
                    <span>Masuk</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </form>
        
        <div class="mt-6 text-center">
            <span class="text-xs text-slate-400">Belum punya akun? </span>
            <a href="register.php" class="text-xs font-bold text-primary hover:underline">Daftar disini</a>
        </div>
        
        <div class="mt-4 text-center">
            <span class="text-[10px] text-slate-300">Terhubung ke Supabase Cloud</span>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('passwordInput');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>