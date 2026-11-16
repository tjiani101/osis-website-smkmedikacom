<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $judul = $_POST['judul'];
  $premium = isset($_POST['premium']) ? 1 : 0;
  $user_id = $_SESSION['user_id'];
  $upload_dir = '../galery/galery' . '/' . date('Y') . '/' . date('m') . '/';

  if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
  }

  function upload($file, $folder) {
    if (!isset($file) || $file['error'] !== 0) return null;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safe = time() . '_' . uniqid() . '.' . $ext;
    $target = $folder . $safe;
    if (move_uploaded_file($file['tmp_name'], $target)) {
      return 'galery/' . date('Y') . '/' . date('m') . '/' . $safe;
    }
    return null;
  }

  $file_hd         = upload($_FILES['file_hd'], $upload_dir);
  $file_edited_hd  = upload($_FILES['file_edited_hd'], $upload_dir);
  $file_edited_fhd = upload($_FILES['file_edited_fhd'], $upload_dir);
  $file_raw        = upload($_FILES['file_raw'], $upload_dir);

  if ($file_hd) {
    $stmt = $conn->prepare("INSERT INTO photos (judul, file_hd, file_edited_hd, file_edited_fhd, file_raw, premium, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", $judul, $file_hd, $file_edited_hd, $file_edited_fhd, $file_raw, $premium, $user_id);
    $stmt->execute();
    echo '<div class="card"><p class="success">Upload berhasil!</p></div>';
  } else {
    echo '<div class="card"><p class="error">HD file wajib diupload.</p></div>';
  }
}
?>

<div class="card">
  <h2>Upload Foto Galeri (Admin)</h2>
  <form method="POST" enctype="multipart/form-data">
    <label>Judul Foto:</label>
    <input type="text" name="judul" required><br>

    <label>File HD (wajib):</label>
    <input type="file" name="file_hd" required><br>

    <label>File Edited HD (optional):</label>
    <input type="file" name="file_edited_hd"><br>

    <label>File Edited FHD (optional):</label>
    <input type="file" name="file_edited_fhd"><br>

    <label>File RAW (optional):</label>
    <input type="file" name="file_raw"><br>

    <label><input type="checkbox" name="premium"> Tandai sebagai Premium</label><br><br>

    <button class="btn" type="submit">Upload</button>
  </form>
</div>
