<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

function simpanLog($conn, $username, $action) {
  $ip = $_SERVER['REMOTE_ADDR'];
  $timestamp = date('Y-m-d H:i:s');
  $username = mysqli_real_escape_string($conn, $username);
  $action = mysqli_real_escape_string($conn, $action);
  mysqli_query($conn, "INSERT INTO logs (timestamp, username, action, ip_address) VALUES ('$timestamp', '$username', '$action', '$ip')");
}

if (isset($_SESSION['username'])) {
  simpanLog($conn, $_SESSION['username'], 'Logout');
}

session_destroy();
header("Location: login.php");
exit;
?>
