<?php
$host = "sql302.infinityfree.com";     // atau 127.0.0.1
$user = "if0_38709109";          // username MySQL kamu
$pass = "jkwKkUXwyzZM";              // password MySQL (kosongin kalau di localhost/XAMPP)
$db   = "if0_38709109_osisdb";       // ganti sesuai nama database kamu

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
