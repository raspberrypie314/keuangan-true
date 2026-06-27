<?php
// includes/auth.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function login($username, $password) {
    $pdo = getDBConnection();
    
    // Cek di tabel users
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_email'] = $user['email'];
        return true;
    }
    return false;
}

function register($name, $username, $email, $password) {
    $pdo = getDBConnection();
    
    // Check if username or email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return false;
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Untuk PostgreSQL, ID auto increment (SERIAL)
    $stmt = $pdo->prepare("
        INSERT INTO users (name, username, email, password, created_at, updated_at) 
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ");
    return $stmt->execute([$name, $username, $email, $hashedPassword]);
}

function logout() {
    session_destroy();
    redirect('login.php');
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireGuest() {
    if (isLoggedIn()) {
        redirect('dashboard.php');
    }
}

// ============================================
// FUNGSI UNTUK SUPABASE AUTH (ALTERNATIF)
// Jika pakai auth bawaan Supabase
// ============================================

function loginWithSupabase($email, $password) {
    // Implementasi pakai Supabase Auth API
    // https://supabase.com/docs/reference/php/auth-signinwithpassword
    
    $supabaseUrl = 'https://your-project.supabase.co';
    $supabaseKey = 'your-anon-key';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $supabaseUrl . '/auth/v1/token?grant_type=password');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => $email,
        'password' => $password
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . $supabaseKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
    
    if (isset($data['access_token'])) {
        $_SESSION['supabase_token'] = $data['access_token'];
        $_SESSION['supabase_user'] = $data['user'];
        return true;
    }
    return false;
}