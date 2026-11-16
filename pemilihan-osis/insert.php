<?php
include 'db.php';
$message = '';

if (isset($_POST['submit'])) {
  $file = $_FILES['idfile']['tmp_name'];
  $handle = fopen($file, "r");
  $imported = 0;
  while (($line = fgets($handle)) !== false) {
    $id = trim($line); // hapus spasi dan newline
    if (!empty($id)) {
      $stmt = $conn->prepare("INSERT IGNORE INTO daftar_pemilih (voter_id) VALUES (?)");
      $stmt->bind_param("s", $id);
      $stmt->execute();
      $imported++;
    }
  }
  fclose($handle);
  $message = "<p class='success'>Berhasil import $imported ID ke database.</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Import ID Voting</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="form-container">
    <h2>Import ID Siswa (Voting)</h2>
    <?= $message ?>
    <form method="POST" enctype="multipart/form-data">
      <input type="file" name="idfile" accept=".txt" required>
      <button type="submit" name="submit">Import</button>
    </form>
  </div>
</body>
</html>
