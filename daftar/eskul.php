<?php include 'db.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>Daftar Eskul</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h2>Formulir Pendaftaran Ekstrakurikuler</h2>
  <form method="POST">
    <input type="text" name="nama" placeholder="Nama" required>
    <input type="text" name="telp" placeholder="No Telp" required>
    
    <select name="jurusan" required>
      <option value="">-- Pilih Jurusan --</option>
      <option value="TKJ">TKJ</option>
      <option value="RPL">RPL</option>
      <option value="Farmasi">Farmasi</option>
      <option value="TPM">TPM</option>
      <option value="TSM">TSM</option>
      <option value="TKR">TKR</option>
      <option value="TBG">TBG</option>
      <option value="Akuntansi">Akuntansi</option>
    </select>

    <select name="eskul" required>
      <option value="">-- Pilih Ekstrakurikuler --</option>
      <option value="Paskibra">Paskibra</option>
      <option value="Pramuka">Pramuka</option>
      <option value="PMR">PMR</option>
      <option value="Futsal">Futsal</option>
      <option value="Basket">Basket</option>
      <option value="Voli">Voli</option>
      <option value="IT Club">IT Club</option>
      <option value="Karya Ilmiah">Karya Ilmiah</option>
      <option value="English Club">English Club</option>
    </select>

    <button type="submit" name="submit">Daftar</button>
  </form>

  <?php
    if (isset($_POST['submit'])) {
      $stmt = $conn->prepare("INSERT INTO pendaftaran_eskul (nama, telp, jurusan, eskul) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssss", $_POST['nama'], $_POST['telp'], $_POST['jurusan'], $_POST['eskul']);
      $stmt->execute();
      echo "<p>Pendaftaran berhasil!</p>";
    }
  ?>
</body>
</html>
