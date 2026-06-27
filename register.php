<?php
// register.php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireGuest();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';
    
    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi!';
    } elseif ($password !== $passwordConfirmation) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid!';
    } elseif (register($name, $username, $email, $password)) {
        $success = 'Registrasi berhasil! Silakan login.';
    } else {
        $error = 'Username atau email sudah digunakan!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DompetKu - Register</title>
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
        <!-- Logo Header -->
        <div class="flex flex-col items-center mb-8">
            <div class="w-14 h-14 bg-primary rounded-2xl flex items-center justify-center text-white text-3xl font-bold shadow-lg shadow-indigo-100 mb-4">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Dompet<span class="text-primary">Ku</span></h2>
            <p class="text-sm text-slate-400 mt-1.5">Buat Akun Baru</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-5 p-4 bg-rose-50 border border-rose-200 text-rose-600 rounded-2xl text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-base"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="mb-5 p-4 bg-green-50 border border-green-200 text-green-600 rounded-2xl text-sm flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-base"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="space-y-5">
            <!-- Name Input -->
            <div>
                <label for="nameInput" class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Lengkap</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-regular fa-id-card"></i>
                    </div>
                    <input type="text" name="name" id="nameInput" required value="<?php echo $_POST['name'] ?? ''; ?>" placeholder="Masukkan nama lengkap" class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>
            </div>

            <!-- Username Input -->
            <div>
                <label for="usernameInput" class="block text-xs font-bold text-slate-500 uppercase mb-2">Username</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <input type="text" name="username" id="usernameInput" required value="<?php echo $_POST['username'] ?? ''; ?>" placeholder="Masukkan username" class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>
            </div>

            <!-- Email Input -->
            <div>
                <label for="emailInput" class="block text-xs font-bold text-slate-500 uppercase mb-2">Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-regular fa-envelope"></i>
                    </div>
                    <input type="email" name="email" id="emailInput" required value="<?php echo $_POST['email'] ?? ''; ?>" placeholder="Masukkan alamat email" class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>
            </div>

            <!-- Password Input -->
            <div>
                <label for="passwordInput" class="block text-xs font-bold text-slate-500 uppercase mb-2">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <input type="password" name="password" id="passwordInput" required placeholder="Buat password" class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>
            </div>

            <!-- Confirm Password Input -->
            <div>
                <label for="passwordConfirmInput" class="block text-xs font-bold text-slate-500 uppercase mb-2">Konfirmasi Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-shield"></i>
                    </div>
                    <input type="password" name="password_confirmation" id="passwordConfirmInput" required placeholder="Ulangi password" class="w-full border border-slate-200 rounded-xl pl-10 pr-4 py-3 text-sm focus:outline-none focus:border-primary">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" class="w-full bg-primary hover:bg-[#4f739c] text-white font-semibold py-3 rounded-xl shadow-lg shadow-[#5B84B6]/25 transition duration-150 flex items-center justify-center gap-2">
                    <span>Register</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <span class="text-xs text-slate-400">Sudah punya akun? </span>
            <a href="login.php" class="text-xs font-bold text-primary hover:underline">Masuk disini</a>
        </div>
    </div>
</body>
</html>