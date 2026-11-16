<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['DEV','admin','osis'])) {
  die("Akses ditolak.");
}

$message = '';
if (isset($_POST['submit'])) {
  $nama = $_POST['nama'];
  $desc = $_POST['desc'];

  $stmt = $conn->prepare("INSERT INTO forum (nama, description) VALUES (?, ?)");
  $stmt->bind_param("ss", $nama, $desc);
  if ($stmt->execute()) {
    $message = "<p class='success'>Forum berhasil ditambahkan!</p>";
  } else {
    $message = "<p class='error'>Gagal menambahkan forum.</p>";
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Buat Forum Baru</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
  <h2>Buat Forum Baru</h2>
  <?= $message ?>
  <form method="post">
    <input type="text" name="nama" placeholder="Nama Forum" required>
    <textarea name="desc" placeholder="Deskripsi Forum (opsional)"></textarea>
    <button type="submit" name="submit">Buat</button>
  </form>
</div>
</body>
</html>
