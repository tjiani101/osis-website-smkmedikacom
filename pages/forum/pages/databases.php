<div class="card">
  <h2>Database (Read Only)</h2>
  <p>Menampilkan data dari tabel `posts`:</p>
  <table border="1" cellpadding="10" style="width:100%; background:#1e293b; color:white;">
    <tr><th>User ID</th><th>Judul</th><th>Isi konten</th></tr>
    <?php
    include 'db.php';
    $result = mysqli_query($conn, "SELECT user_id, judul, konten FROM threads LIMIT 10");
    while ($row = mysqli_fetch_assoc($result)) {
      echo "<tr><td>{$row['user_id']}</td><td>{$row['judul']}</td><td>{$row['konten']}</td></tr>";
    }
    ?>
  </table>
  <p>Menampilkan data dari tabel `Comments`:</p>
  <table border="1" cellpadding="10" style="width:100%; background:#1e293b; color:white;">
    <tr><th>User ID</th><th>Judul</th><th>Isi konten</th></tr>
    <?php
    include 'db.php';
    $result = mysqli_query($conn, "SELECT thread_id, user_id, komentar FROM comments LIMIT 10");
    while ($row = mysqli_fetch_assoc($result)) {
      echo "<tr><td>{$row['thread_id']}</td><td>{$row['user_id']}</td><td>{$row['komentar']}</td></tr>";
    }
    ?>
  </table>
</div>
