<?php
include 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama = $_POST['nama'];
  $nis = $_POST['nis'];
  $telp = $_POST['telp'];
  $visimisi = $_POST['visimisi'];
  $cerita = $_POST['cerita'];
  $email = $_POST['email'];

  // Cek file CV
  if (isset($_FILES['cv']) && $_FILES['cv']['error'] === 0) {
    $ext = pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION);
    $cvName = 'cv_' . time() . '_' . preg_replace('/\s+/', '_', strtolower($nama)) . '.' . $ext;
    $uploadPath = 'uploads/' . $cvName;

    if (move_uploaded_file($_FILES['cv']['tmp_name'], $uploadPath)) {
      $stmt = $conn->prepare("INSERT INTO pendaftaran_osis (nama, nis, telp, email, visimisi, cerita, cv_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssssss", $nama, $nis, $telp, $email, $visimisi, $cerita, $uploadPath);
      

      if ($stmt->execute()) {
        $message = "<p style='color: green;'>Pendaftaran berhasil dikirim!</p>";
      } else {
        $message = "<p style='color: red;'>Gagal menyimpan ke database.</p>";
      }
    } else {
      $message = "<p style='color: red;'>Gagal mengunggah file CV.</p>";
    }
  } else {
    $message = "<p style='color: red;'>File CV tidak valid atau tidak ditemukan.</p>";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Pendaftaran OSIS</title>
  <style>
    <?php include 'style.css'; ?> /* Add your CSS here or link externally */
    /* Additional styles for textarea and file input */
    textarea {
      width: 100%;
      border: none;
      border-bottom: 2px solid #4a556e;
      padding: 15px 0;
      font-size: 16px;
      background: transparent;
      color: #ebf0ff;
      transition: 0.3s;
      resize: vertical;
    }

    textarea:focus {
      outline: none;
      border-bottom-color: #7289da;
    }

    .input-data input[type="file"] {
      padding: 15px 0;
      color: #7c8ca5;
      width: 100%;
    }

    .input-data input[type="file"]:focus ~ label,
    .input-data input[type="file"]:valid ~ label {
      transform: translateY(-25px);
      font-size: 14px;
      color: #7289da;
    }

    .cv{
      padding: bottom 100px ;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1 class="text">Pendaftaran OSIS 20</h1>
    <p class="text"> <a href="" require style="font-size: 16px; color: ffffff;"> tutorial isi</a> </p>
    <?= $message ?>
    
    <form method="POST" enctype="multipart/form-data">
      <div class="form-row">
        <div class="input-data">
          <input type="text" name="nama" id="nama" required>
          <label for="nama">Nama</label>
        </div>
      </div>

      <div class="form-row">
        <div class="input-data">
          <input type="text" name="nis" id="nis" required>
          <label for="nis">NIS</label>
        </div>
      </div>

      <div class="form-row">
        <div class="input-data">
          <input type="text" name="telp" id="telp" required>
          <label for="telp">Nomor Telepon</label>
        </div>
      </div>

      <div class="form-row">
        <div class="input-data">
          <input type="email" name="email" id="email" required>
          <label for="email">Email aktif</label>
        </div>
      </div>

      <div class="form-row">
        <div class="input-data">
          <textarea name="visimisi" id="visimisi" required></textarea>
          <label for="visimisi">Visi dan Misi</label>
        </div>
      </div>

      <div class="form-row">
        <div class="input-data">
          <textarea name="cerita" id="cerita" required></textarea>
          <label for="cerita">Ceritakan Tentang Dirimu</label>
        </div>
      </div>

      <div class="form-row">
        <div class="input-data">
          <input type="file" name="cv" id="cv" accept=".pdf,.doc,.docx" required style="padding-bottom:40px;">
          <br>
          <label for="cv">Upload cv</label>
        </div>
      </div>

      <div class="form-row submit-btn">
        <input type="submit" value="Kirim Pendaftaran">
      </div>
    </form>
    <a href="../" class="home-btn">Home</a>
  </div>

</body>
</html>