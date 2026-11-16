<div class="card">
  <h2>Manajemen Pengguna</h2>
  <table border="1" cellpadding="10" style="width:100%; background:#1e293b; color:white;">
    <tr><th>Nama</th><th>NIS</th><th>Role</th><th>Status</th><th>Tier</th><th>followers</th><th>following</th><th>Aksi</th></tr>
    <?php
    include 'db.php';

    // Proses form
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $id = $_POST['user_id'];

      if (isset($_POST['toggle_status'])) {
        mysqli_query($conn, "UPDATE users SET status = IF(status='banned','active','banned') WHERE id=$id");
        header("Refresh:0");
      }

      if (isset($_POST['delete_posts'])) {
        mysqli_query($conn, "DELETE FROM posts WHERE user_id=$id");
        header("Refresh:0");
      }

      if (isset($_POST['change_role'])) {
        $newRole = $_POST['role'];
        $cek = mysqli_query($conn, "SELECT role FROM users WHERE id=$id");
        $get = mysqli_fetch_assoc($cek);
        if ($get['role'] != 'dev') {
          mysqli_query($conn, "UPDATE users SET role='$newRole' WHERE id=$id");
          header("Refresh:0");
        }
      }
    }

    $result = mysqli_query($conn, "SELECT * FROM users");
    while ($row = mysqli_fetch_assoc($result)) {
      echo "<tr>
        <td>{$row['username']}</td>
        <td>{$row['nis']}</td>
        <td>{$row['role']}</td>
        <td>{$row['status']}</td>
        <td>{$row['tier']}</td>
        <td>{$row['followers_count']}</td>
        <td>{$row['following_count']}</td>
        <td>";

      echo "<form method='post' style='display:inline-block; margin-right:5px;'>
              <input type='hidden' name='user_id' value='{$row['id']}'>
              <button class='btn " . ($row['status'] == 'banned' ? '' : 'red') . "' name='toggle_status'>" . ($row['status'] == 'banned' ? 'Unban' : 'Ban') . "</button>
            </form>";

      echo "<form method='post' style='display:inline-block; margin-right:5px;' onsubmit='return confirm(\"Yakin hapus semua post user ini?\")'>
              <input type='hidden' name='user_id' value='{$row['id']}'>
              <button class='btn red' name='delete_posts'>Hapus Post</button>
            </form>";

      if ($row['role'] != 'dev') {
        echo "<form method='post' style='display:inline-block;'>
                <input type='hidden' name='user_id' value='{$row['id']}'>
                <select name='role'>";
        $roles = ['murid', 'osis', 'guru', 'admin'];
        foreach ($roles as $r) {
          $selected = $row['role'] == $r ? 'selected' : '';
          echo "<option value='$r' $selected>$r</option>";
        }
        echo "</select>
              <button class='btn' name='change_role'>Ganti</button>
              </form>";
      }

      echo "</td></tr>";
    }
    ?>
  </table>
</div>
