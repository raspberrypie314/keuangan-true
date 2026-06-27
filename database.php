<?php
// config/database.php

define('SUPABASE_URL', 'https://qiupifqmjrpvldbqhoww.supabase.co');

// ⚠️ PASTE ANON PUBLIC YANG BENAR!
define('SUPABASE_KEY', 'sb_publishable_Z5VjqWxTfhxcI3peybaXNQ_QCjOV3Zy');

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
    return ['url' => SUPABASE_URL, 'key' => SUPABASE_KEY];
}
