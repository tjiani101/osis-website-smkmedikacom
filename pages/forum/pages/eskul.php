<div class="card">
  <h2>Database (pendaftaran_eskul)</h2>
  <p>Menampilkan data dari tabel `pendaftaran_eskul`:</p>
  <table border="1" cellpadding="10" style="width:100%; background:#1e293b; color:white;">
    <tr><th>Nama</th><th>NIS</th><th>Email</th><th>no tlp</th><th>jurusan</th><th>Eskul</th></tr>
    <?php
    include 'db.php';
    $result = mysqli_query($conn, "SELECT * FROM `pendaftaran_eskul` LIMIT 50");
    while ($row = mysqli_fetch_assoc($result)) {
      echo "<tr><td>{$row['nama']}</td><td>{$row['nis']}</td><td>{$row['email']}</td><td>{$row['telp']}</td><td>{$row['jurusan']}</td><td>{$row['eskul']}</td></tr>";
    }
    ?>
  </table>
</div>
