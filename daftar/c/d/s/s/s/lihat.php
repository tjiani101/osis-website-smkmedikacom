<?php include 'db.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>Lihat Pendaftar Eskul</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h2>Data Pendaftar Eskul</h2>
  <form method="GET">
    <input type="text" name="search" placeholder="Cari Nama atau Eskul">
    <select name="filter">
      <option value="">Sortir by Eskul</option>
      <option value="Pramuka">Pramuka</option>
      <option value="Paskibra">Paskibra</option>
      <option value="PMR">PMR</option>
      <option value="Rohis">Rohis</option>
    </select>
    <button type="submit">Cari</button>
  </form>
  <table border="1" cellpadding="5" cellspacing="0">
    <tr><th>Nama</th><th>No Telp</th><th>Jurusan</th><th>Eskul</th></tr>
    <?php
      $search = $_GET['search'] ?? '';
      $filter = $_GET['filter'] ?? '';
      $sql = "SELECT * FROM pendaftaran_eskul WHERE (nama LIKE '%$search%' OR eskul LIKE '%$search%')";
      if ($filter != '') $sql .= " AND eskul = '$filter'";
      $result = $conn->query($sql);
      while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['nama']}</td><td>{$row['telp']}</td><td>{$row['jurusan']}</td><td>{$row['eskul']}</td></tr>";
      }
    ?>
  </table>
</body>
</html>
