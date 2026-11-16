<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
$username = $user['username']; // Pastikan kolom 'username' ada di tabel users

$file_url = $_GET['file'] ?? '';
$file_type = $_GET['type'] ?? '';

// Validasi parameter
if (empty($file_url) || empty($file_type)) {
    die("Invalid request.");
}

// Dapatkan IP address pengguna
$ip_address = $_SERVER['REMOTE_ADDR'];

// Catat log ke database (gunakan prepared statement)
$action = "Download $file_type: " . basename($file_url);
$timestamp = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO logs (timestamp, username, action, ip_address) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $timestamp, $username, $action, $ip_address);
$stmt->execute();
$stmt->close();

// Redirect ke file yang sebenarnya
header("Location: $file_url");
exit;
?>