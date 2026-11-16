<?php include 'db.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>Daftar OSIS</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h2>Formulir Pendaftaran OSIS</h2>
  <form method="POST">
    <input type="text" name="nama" placeholder="Nama" required>
    <input type="text" name="telp" placeholder="No Telp" required>
    <textarea name="visi" placeholder="Visi dan Misi Masuk OSIS" required></textarea>
    <textarea name="tujuan" placeholder="Tujuan Masuk OSIS" required></textarea>
    <textarea name="cerita" placeholder="Ceritakan singkat tentang dirimu" required></textarea>
    <button type="submit" name="submit">Daftar</button>
  </form>
  <?php
    if (isset($_POST['submit'])) {
      $stmt = $conn->prepare("INSERT INTO pendaftaran_osis (nama, telp, visi, tujuan, cerita) VALUES (?, ?, ?, ?, ?)");
      $stmt->bind_param("sssss", $_POST['nama'], $_POST['telp'], $_POST['visi'], $_POST['tujuan'], $_POST['cerita']);
      $stmt->execute();
      echo "<p>Pendaftaran berhasil!</p>";
    }
  ?>
</body>
</html>