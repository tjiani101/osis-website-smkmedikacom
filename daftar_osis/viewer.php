<?php
$file = $_GET['file'] ?? '';

if (!$file || !file_exists($file)) {
  die("File tidak ditemukan.");
}

// Pastikan hanya file PDF yang bisa ditampilkan
if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'pdf') {
  die("File bukan PDF.");
}

// Tampilkan PDF langsung di browser
header("Content-Type: application/pdf");
readfile($file);
exit;
?>
