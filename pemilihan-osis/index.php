<?php
include 'db.php';
session_start();
$message = '';

if (isset($_POST['submit'])) {
  $voter_id = $_POST['voter_id'];

  $cek = $conn->prepare("SELECT * FROM daftar_pemilih WHERE voter_id = ?");
  $cek->bind_param("s", $voter_id);
  $cek->execute();
  $result = $cek->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if ($row['sudah_memilih'] == 1) {
      $message = "<p class='error'>Pilihan anda sudah kami terima.</p>";
    } else {
      $_SESSION['voter_id'] = $voter_id;
      header("Location: voting.php");
      exit();
    }
  } else {
    $message = "<p class='error'>ID tidak valid.</p>";
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Login Voting</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="form-container">
    <h2>Masukkan ID Anda</h2>
    <?= $message ?>
    <form method="POST">
      <input type="text" name="voter_id" placeholder="ID Siswa" required>
      <button type="submit" name="submit">Masuk</button>
    </form>
  </div>
</body>
</html>
