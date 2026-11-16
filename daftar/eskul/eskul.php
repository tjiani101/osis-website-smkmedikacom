
<?php include 'eskul_db.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Formulir Pendaftaran Eskul</title>
  <link rel="stylesheet" href="eskul.css">
</head>
<body>
  <div class="container">
    <div class="text">Formulir Pendaftaran Eskul</div>
    <form method="POST">
       <div class="form-row">
          <div class="input-data">
             <input type="text" name="nama" required>
             <div class="underline"></div>
             <label for="">Nama Lengkap</label>
          </div>
          <div class="input-data">
             <input type="text" name="nis" required>
             <div class="underline"></div>
             <label for="">NIS</label>
          </div>
       </div>
       <div class="form-row">
          <div class="input-data">
            <select name="jurusan" required style="width: 100%; border: none; font-size: 17px; border-bottom: 2px solid rgba(0,0,0, 0.12);">
              <option value="" disabled selected>Pilih Jurusan</option>
              <option value="TKJ">TKJ</option>
              <option value="RPL">RPL</option>
              <option value="Farmasi">Farmasi</option>
              <option value="TPM">TPM</option>
              <option value="TSM">TSM</option>
              <option value="TKR">TKR</option>
              <option value="TBG">TBG</option>
              <option value="Akuntansi">Akuntansi</option>
            </select>
          </div>
          <div class="input-data">
            <select name="eskul" required style="width: 100%; border: none; font-size: 17px; border-bottom: 2px solid rgba(0,0,0, 0.12);">
              <option value="" disabled selected>Pilih Eskul</option>
              <option value="Paskibra">Paskibra</option>
              <option value="PMR">PMR</option>
              <option value="Pramuka">Pramuka</option>
              <option value="Futsal">Futsal</option>
              <option value="Basket">Basket</option>
              <option value="English Club">English Club</option>
            </select>
          </div>
       </div>
       <div class="form-row submit-btn">
          <div class="input-data">
             <div class="inner"></div>
             <input type="submit" name="submit" value="Submit">
          </div>
       </div>
    </form>
    <?php
    if (isset($_POST['submit'])) {
      $nama = $_POST['nama'];
      $nis = $_POST['nis'];
      $jurusan = $_POST['jurusan'];
      $eskul = $_POST['eskul'];
      $stmt = $conn->prepare("INSERT INTO pendaftaran_eskul (nama, nis, jurusan, eskul) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssss", $nama, $nis, $jurusan, $eskul);
      if ($stmt->execute()) {
        echo "<p style='color:green;'>Pendaftaran berhasil!</p>";
      } else {
        echo "<p style='color:red;'>Gagal mendaftar!</p>";
      }
    }
    ?>
  </div>
</body>
</html>
