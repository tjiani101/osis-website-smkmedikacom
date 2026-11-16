<?php
// Konfigurasi Database
$host = "sql302.infinityfree.com";
$user = "if0_38709109";      // Default XAMPP user
$pass = "0O2uwnawRf";          // Default XAMPP password (kosong)
$db   = "if0_38709109_lombacontentramadhan";

// Membuat koneksi
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}
?>