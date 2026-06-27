<?php
// config/database.php - PAKE SUPABASE API!

define('SUPABASE_URL', 'https://qiupifqmjrpvldbqhoww.supabase.co');
define('SUPABASE_KEY', 'EYJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InFpdXBpZnFtanJwdmxkYnFob3d3Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3Mjg4OTM2MDAsImV4cCI6MjA0NDQ3MDAwMH0.xxxxxxxxxxxxxxxxxxxx');

function supabaseRequest($endpoint, $method = 'GET', $data = null, $token = null) {
    $url = SUPABASE_URL . $endpoint;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Content-Type: application/json'
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['data' => json_decode($response, true), 'code' => $httpCode];
}

function getDBConnection() {
    // BALIKIN ARRAY BIAR GA ERROR DI FUNGSI LAIN
    return ['url' => SUPABASE_URL, 'key' => SUPABASE_KEY];
}
