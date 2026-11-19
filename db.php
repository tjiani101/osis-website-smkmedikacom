<?php
/**
 * Konfigurasi Database Terpusat (Centralized Database Connection)
 * Website OSIS & MPK - SMK Medikacom
 * 
 * Semua file dan modul dalam website ini mengarah ke file ini.
 */

// 1. KEAMANAN: Cegah file ini diakses langsung dari browser (Direct URL Access)
if (
    (count(get_included_files()) <= 1 && php_sapi_name() !== 'cli') ||
    (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) ||
    (isset($_SERVER['PHP_SELF']) && basename(__FILE__) === basename($_SERVER['PHP_SELF']))
) {
    http_response_code(403);
    header('HTTP/1.1 403 Forbidden');
    exit('<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head><title>403 Forbidden</title></head>
<body><h1>Forbidden</h1><p>Access Denied: Direct access to this file is prohibited.</p></body></html>');
}

// 2. KREDENSIAL DATABASE
// Prioritas: Environment Variable -> Konfigurasi Lokal (Default)
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'admin';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'admin123';
$db_name = getenv('DB_NAME') ?: 'osisdb';

// Nonaktifkan default mysqli exception reporting agar kita bisa handle error & fallback sendiri
mysqli_report(MYSQLI_REPORT_OFF);

// 3. INISIALISASI KONEKSI MYSQLI
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);

// Fallback 1: Jika database 'osisdb' belum ada, coba cek database 'if0_38709109_osisdb' (nama asli di SQL dump)
if ($conn->connect_errno && $conn->connect_errno === 1049) {
    $alt_db = ($db_name === 'osisdb') ? 'if0_38709109_osisdb' : 'osisdb';
    $conn = @new mysqli($db_host, $db_user, $db_pass, $alt_db);
    if (!$conn->connect_errno) {
        $db_name = $alt_db;
    }
}

// Fallback 2: Jika user 'admin' ditolak (1045), coba user default 'root' tanpa password
if ($conn->connect_errno && $conn->connect_errno === 1045) {
    $conn = @new mysqli($db_host, 'root', '', $db_name);
    if ($conn->connect_errno && $conn->connect_errno === 1049) {
        $alt_db = ($db_name === 'osisdb') ? 'if0_38709109_osisdb' : 'osisdb';
        $conn = @new mysqli($db_host, 'root', '', $alt_db);
        if (!$conn->connect_errno) {
            $db_name = $alt_db;
        }
    }
    if (!$conn->connect_errno) {
        $db_user = 'root';
        $db_pass = '';
    }
}

// 4. PENANGANAN ERROR KONEKSI
if ($conn->connect_errno) {
    error_log("Database connection error [{$conn->connect_errno}]: {$conn->connect_error}");
    http_response_code(500);

    // Tampilkan pesan panduan jika diakses secara lokal
    $is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost']) || php_sapi_name() === 'cli';
    if ($is_local) {
        die("<div style='font-family: system-ui, sans-serif; padding: 24px; background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; border-radius: 12px; max-width: 650px; margin: 40px auto; line-height: 1.6;'>"
            . "<h2 style='margin-top: 0; color: #b91c1c;'>⚠️ Koneksi Database Gagal</h2>"
            . "<p>Aplikasi tidak dapat terhubung ke MySQL / MariaDB lokal.</p>"
            . "<ul>"
            . "<li><b>Host:</b> {$db_host}</li>"
            . "<li><b>User:</b> {$db_user}</li>"
            . "<li><b>Database:</b> {$db_name}</li>"
            . "<li><b>Error:</b> " . htmlspecialchars($conn->connect_error) . " (Kode: {$conn->connect_errno})</li>"
            . "</ul>"
            . "<p><b>Solusi:</b> Pastikan service MariaDB/MySQL sudah jalan dan database sudah dibuat / di-import.</p>"
            . "</div>");
    } else {
        die("Layanan database sedang tidak tersedia. Silakan hubungi administrator.");
    }
}

// Set karakter encoding ke utf8mb4
$conn->set_charset("utf8mb4");

// 5. VARIABLE ALIASES (Kompatibilitas dengan file-file lama)
$host = $db_host;
$user = $db_user;
$pass = $db_pass;
$db   = $db_name;

// 6. INISIALISASI PDO (Untuk modul galeri atau modul lain yang menggunakan PDO)
try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (Exception $e) {
    // Jika PDO gagal, log error tanpa mematikan mysqli
    error_log("PDO initialization notice: " . $e->getMessage());
    $pdo = null;
}
