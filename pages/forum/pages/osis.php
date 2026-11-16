<div class="card">
  <h2>Database (pendaftaran_osis)</h2>
  <p>Menampilkan data dari tabel `pendaftaran_osis`:</p>
  <table border="1" cellpadding="10" style="width:100%; background:#1e293b; color:white;">
    <tr><th>Nama</th><th>NIS</th><th>No Tlp</th><th>Email</th><th> CV download </th></tr>
    <?php
    include 'db.php';
    $result = mysqli_query($conn, "SELECT * FROM `pendaftaran_osis`  LIMIT 30");
    while ($row = mysqli_fetch_assoc($result)) {
      echo "<tr><td>{$row['nama']}</td><td>{$row['nis']}</td><td>{$row['telp']}</td><td>{$row['email']}</td>
      <td><a class='cv-link' href=https://osis-medcom.web.id/daftar_osis/{$row['cv_path']} target='_blank'>Lihat CV</a></td>

      </tr>";
    }
    ?>
  </table>
<br>
<br>
<br>
    <p>Visi Misi Dan Cerita singkat tentang Pendaftar</p>
  
  <table border="1" cellpadding="10" style="width:100%; background:#1e293b; color:white;">
    <tr><th>Nama</th><th> Visi Misi </th></tr>
    <?php
    include 'db.php';
    $result = mysqli_query($conn, "SELECT * FROM `pendaftaran_osis`  LIMIT 30");
    while ($row = mysqli_fetch_assoc($result)) {
      echo "<tr><td>{$row['nama']}</td><td>{$row['visimisi']}</td></tr>";
    }
    ?>
  </table>

  <br>

  <table border="1" cellpadding="10" style="width:100%; background:#1e293b; color:white;">
    <tr><th>Nama</th><th> cerita </th></tr>
    <?php
    include 'db.php';
    $result = mysqli_query($conn, "SELECT * FROM `pendaftaran_osis`  LIMIT 30");
    while ($row = mysqli_fetch_assoc($result)) {
      echo "<tr><td>{$row['nama']}</td><td>{$row['cerita']}</td></tr>";
    }
    ?>
  </table>
</div>
