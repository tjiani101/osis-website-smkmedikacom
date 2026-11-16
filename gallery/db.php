<?php
// Blokir akses langsung
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    http_response_code(403);
    exit('Access denied.');
}

$host = 'localhost';
$db = 'osis_db';
$user = 'root';
$pass = 'root';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    error_log('Database error: ' . $conn->connect_error);
    die('Koneksi database gagal.');
}
