<?php
include 'db.php';

// Auth check
$authorized = false;
if (isset($_POST['passcode'])) {
  $input = $_POST['passcode'];
  $cek = $conn->prepare("SELECT id FROM akses_passcode WHERE passcode = ? LIMIT 1");
  $cek->bind_param("s", $input);
  $cek->execute();
  $result = $cek->get_result();
  if ($result->num_rows > 0) {
    $authorized = true;
  } else {
    $error = 'Passcode salah.';
  }
}

if ($authorized):
  $result = $conn->query("SELECT * FROM pendaftaran_osis ORDER BY tanggal_daftar DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Pendaftar OSIS</title>
  <style>
    body {
      font-family: sans-serif;
      background: #f5f5f5;
      padding: 20px;
    }
    h2 {
      text-align: center;
      margin-bottom: 30px;
    }
    table {
      border-collapse: collapse;
      width: 100%;
      background: #fff;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    th, td {
      padding: 10px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    th {
      background: #800000;
      color: #fff;
    }
    tr:hover {
      background: #f1f1f1;
    }
    .cv-link {
      text-decoration: none;
      color: #0066cc;
    }
  </style>
</head>
<body>
  <h2>Data Pendaftar OSIS</h2>
  <table>
    <tr>
      <th>Nama</th>
      <th>NIS</th>
      <th>No Telp</th>
      <th>Visi Misi</th>
      <th>Cerita</th>
      <th>CV</th>
      <th>Tanggal Daftar</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?= htmlspecialchars($row['nama']) ?></td>
      <td><?= htmlspecialchars($row['nis']) ?></td>
      <td><?= htmlspecialchars($row['telp']) ?></td>
      <td><?= nl2br(htmlspecialchars($row['visimisi'])) ?></td>
      <td><?= nl2br(htmlspecialchars($row['cerita'])) ?></td>
      <td><a class="cv-link" href="<?= $row['cv_path'] ?>" target="_blank">Lihat CV</a></td>
      <td><?= $row['tanggal_daftar'] ?></td>
    </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>
<?php else: ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masukkan Passcode</title>
  <style>
    body {
      font-family: sans-serif;
      background: #f5f5f5;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .passcode-box {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      text-align: center;
    }
    input[type="password"] {
      padding: 10px;
      width: 100%;
      margin-top: 10px;
      margin-bottom: 20px;
    }
    button {
      padding: 10px 20px;
      background: #800000;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    .error {
      color: red;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div class="passcode-box">
    <h2>Masukkan Passcode</h2>
    <?php if (!empty($error)) echo '<p class="error">' . $error . '</p>'; ?>
    <form method="POST">
      <input type="password" name="passcode" placeholder="Passcode" required>
      <button type="submit">Masuk</button>
    </form>
  </div>
</body>
</html>
<?php endif; ?>
