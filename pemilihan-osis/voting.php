<?php
session_start();
include 'db.php';

if (!isset($_SESSION['voter_id'])) {
  header("Location: index.php");
  exit();
}

$voter_id = $_SESSION['voter_id'];
$message = '';

if (isset($_POST['submit'])) {
  $osis = $_POST['osis'];
  $mpk = $_POST['mpk'];

  $stmt = $conn->prepare("INSERT INTO hasil_voting (voter_id, pilihan_osis, pilihan_mpk) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $voter_id, $osis, $mpk);
  $stmt->execute();

  $conn->query("UPDATE daftar_pemilih SET sudah_memilih = 1 WHERE voter_id = '$voter_id'");

  unset($_SESSION['voter_id']);
  header("Location: index.php");
  exit();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Voting OSIS & MPK</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .kandidat-group {
      display: flex;
      justify-content: space-around;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .kandidat-option {
      text-align: center;
      margin: 10px;
    }
    .kandidat-option img {
      width: 150px;
      height: 150px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 10px;
      border: 3px solid transparent;
      transition: border 0.3s;
    }
    .kandidat-option input[type="radio"]:checked + img {
      border: 3px solid #800000;
    }
    .kandidat-option input[type="radio"] {
      display: none;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Formulir Voting</h2>
    <form method="POST">
      <p><strong>Pilih Tim OSIS:</strong></p>
      <div class="kandidat-group">
        <label class="kandidat-option">
          <input type="radio" name="osis" value="Kandidat 1" required>
          <img src="images/o1.png" alt="Kandidat 1"><br>Kandidat 1
        </label>
        <label class="kandidat-option">
          <input type="radio" name="osis" value="Kandidat 2">
          <img src="images/o1.png" alt="Kandidat 2"><br>Kandidat 2
        </label>
        <label class="kandidat-option">
          <input type="radio" name="osis" value="Kandidat 3">
          <img src="images/o1.png" alt="Kandidat 3"><br>Kandidat 3
        </label>
      </div>

      <p><strong>Pilih Tim MPK:</strong></p>
      <div class="kandidat-group">
        <label class="kandidat-option">
          <input type="radio" name="mpk" value="Kandidat 1" required>
          <img src="images/m1.png" alt="Kandidat 1 MPK"><br>Kandidat 1
        </label>
        <label class="kandidat-option">
          <input type="radio" name="mpk" value="Kandidat 2">
          <img src="images/m1.png" alt="Kandidat 2 MPK"><br>Kandidat 2
        </label>
        <label class="kandidat-option">
          <input type="radio" name="mpk" value="Kandidat 3">
          <img src="images/m1.png" alt="Kandidat 3 MPK"><br>Kandidat 3
        </label>
      </div>

      <button type="submit" name="submit">Kirim</button>
    </form>
  </div>
</body>
</html>
