<?php
// includes/functions.php

require_once __DIR__ . '/../config/database.php';

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function formatRupiah($number) {
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getMonthName($month) {
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
        4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
        10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $months[(int)$month] ?? '';
}

function getCategories($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY name");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getTransactions($pdo, $userId, $filters = []) {
    $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
            FROM transactions t 
            LEFT JOIN categories c ON t.category_id = c.id 
            WHERE t.user_id = ?";
    $params = [$userId];
    
    if (!empty($filters['start_date'])) {
        $sql .= " AND t.date >= ?";
        $params[] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $sql .= " AND t.date <= ?";
        $params[] = $filters['end_date'];
    }
    if (!empty($filters['category_id'])) {
        $sql .= " AND t.category_id = ?";
        $params[] = $filters['category_id'];
    }
    if (!empty($filters['type'])) {
        $sql .= " AND t.type = ?";
        $params[] = $filters['type'];
    }
    if (!empty($filters['search'])) {
        $sql .= " AND t.description ILIKE ?";
        $params[] = "%" . $filters['search'] . "%";
    }
    
    $sql .= " ORDER BY t.date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getDashboardData($pdo, $userId) {
    // Get total saldo
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN type = 'pemasukan' THEN amount ELSE 0 END), 0) as total_income,
            COALESCE(SUM(CASE WHEN type = 'pengeluaran' THEN amount ELSE 0 END), 0) as total_outcome
        FROM transactions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $totals = $stmt->fetch();
    
    $saldo = $totals['total_income'] - $totals['total_outcome'];
    
    // Get total transactions count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM transactions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalTransactions = $stmt->fetch()['total'];
    
    // Get monthly data for chart (PostgreSQL version)
    $stmt = $pdo->prepare("
        SELECT 
            TO_CHAR(date, 'YYYY-MM') as month,
            COALESCE(SUM(CASE WHEN type = 'pemasukan' THEN amount ELSE 0 END), 0) as income,
            COALESCE(SUM(CASE WHEN type = 'pengeluaran' THEN amount ELSE 0 END), 0) as outcome
        FROM transactions 
        WHERE user_id = ? 
        AND date >= (NOW() - INTERVAL '6 months')
        GROUP BY TO_CHAR(date, 'YYYY-MM')
        ORDER BY month ASC
    ");
    $stmt->execute([$userId]);
    $monthlyData = $stmt->fetchAll();
    
    // Get budgets
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
        AND b.month = EXTRACT(MONTH FROM CURRENT_DATE)
        AND b.year = EXTRACT(YEAR FROM CURRENT_DATE)
        ORDER BY b.amount DESC
    ");
    $stmt->execute([$userId, $userId]);
    $budgets = $stmt->fetchAll();
    
    foreach ($budgets as &$budget) {
        $budget['remaining'] = $budget['amount'] - $budget['spent'];
        $budget['percentage'] = $budget['amount'] > 0 ? min(100, round(($budget['spent'] / $budget['amount']) * 100)) : 0;
    }
    
    // Get recent transactions
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as category_name, c.color as category_color 
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
        ORDER BY t.date DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentTransactions = $stmt->fetchAll();
    
    // Get category data for donut chart
    $stmt = $pdo->prepare("
        SELECT 
            c.name as category_name,
            c.color as category_color,
            COALESCE(SUM(t.amount), 0) as total
        FROM categories c
        LEFT JOIN transactions t ON c.id = t.category_id AND t.type = 'pengeluaran' AND t.user_id = ?
        WHERE c.user_id = ? AND c.type = 'pengeluaran'
        GROUP BY c.id, c.name, c.color
        HAVING SUM(t.amount) > 0
    ");
    $stmt->execute([$userId, $userId]);
    $categoryData = $stmt->fetchAll();
    
    // Get remaining budget
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(b.amount), 0) as total_budget,
               COALESCE((
                   SELECT SUM(t.amount)
                   FROM transactions t
                   JOIN budgets b2 ON t.category_id = b2.category_id
                   WHERE t.user_id = ?
                   AND t.type = 'pengeluaran'
                   AND EXTRACT(MONTH FROM t.date) = b2.month 
                   AND EXTRACT(YEAR FROM t.date) = b2.year
                   AND b2.user_id = ?
                   AND b2.month = EXTRACT(MONTH FROM CURRENT_DATE)
                   AND b2.year = EXTRACT(YEAR FROM CURRENT_DATE)
               ), 0) as total_spent
        FROM budgets b
        WHERE b.user_id = ? 
        AND b.month = EXTRACT(MONTH FROM CURRENT_DATE)
        AND b.year = EXTRACT(YEAR FROM CURRENT_DATE)
    ");
    $stmt->execute([$userId, $userId, $userId]);
    $budgetTotals = $stmt->fetch();
    $remainingBudget = ($budgetTotals['total_budget'] ?? 0) - ($budgetTotals['total_spent'] ?? 0);
    
    return [
        'saldo' => $saldo,
        'totalIncome' => $totals['total_income'],
        'totalOutcome' => $totals['total_outcome'],
        'totalTransactions' => $totalTransactions,
        'remainingBudget' => max(0, $remainingBudget),
        'months' => array_column($monthlyData, 'month'),
        'incomeData' => array_column($monthlyData, 'income'),
        'outcomeData' => array_column($monthlyData, 'outcome'),
        'budgets' => $budgets,
        'recentTransactions' => $recentTransactions,
        'categoriesLabels' => array_column($categoryData, 'category_name'),
        'categoriesValues' => array_column($categoryData, 'total'),
        'categoriesColors' => array_column($categoryData, 'category_color')
    ];
}

// ============================================
// FUNGSI UNTUK SUPABASE AUTH (opsional)
// Bisa pakai JWT atau langsung ke tabel users
// ============================================

function getSupabaseClient() {
    // Jika mau pakai Supabase REST API
    // return new SupabaseClient(SUPABASE_URL, SUPABASE_ANON_KEY);
    return null;
}

// Fungsi untuk generate UUID (PostgreSQL native)
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}