<?php
session_start();
include 'db.php';
include 'badwords.php'; // tambahin filter kata kasar

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Validasi input
if (empty($_POST['judul']) || empty($_POST['konten']) || empty($_POST['forum_id'])) {
    die("Semua field wajib diisi");
}

$user_id = $_SESSION['user_id'];
$judul = $conn->real_escape_string(filter_bad_words($_POST['judul']));
$konten = $conn->real_escape_string(filter_bad_words($_POST['konten']));
$forum_id = intval($_POST['forum_id']);
$media_type = 'image'; // default
$filename = null;

// Handle media upload
if (isset($_FILES['media_upload']) && $_FILES['media_upload']['error'] === UPLOAD_ERR_OK) {
    $media_type = isset($_POST['media_type']) ? $_POST['media_type'] : 'image';
    $upload_dir = 'post/';
    $allowed_types = [];
    $max_size = 0;

    // Set allowed types and max size based on media type
    if ($media_type === 'image') {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
    } elseif ($media_type === 'video') {
        $allowed_types = ['video/mp4'];
        $max_size = 20 * 1024 * 1024; // 20MB
    }

    $file = $_FILES['media_upload'];
    
    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        die("Format file tidak didukung. Untuk ".$media_type." hanya format: ".implode(', ', $allowed_types));
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        die("Ukuran file terlalu besar. Maksimal: ".($max_size/1024/1024)."MB");
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid().'.'.$ext;
    $target_path = $upload_dir.$filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        die("Gagal mengupload file");
    }
}

// Insert into database
$query = "INSERT INTO threads (user_id, forum_id, judul, konten, gambar, media_type, created_at) 
          VALUES ($user_id, $forum_id, '$judul', '$konten', ".($filename ? "'$filename'" : "NULL").", '$media_type', NOW())";

if ($conn->query($query)) {
    header("Location: index.php?post_success=1");
} else {
    die("Error: ".$conn->error);
}
?>
