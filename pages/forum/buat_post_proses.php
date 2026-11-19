<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';
include 'badwords.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Validasi input
if (empty($_POST['judul']) || empty($_POST['konten']) || empty($_POST['forum_id'])) {
    die("Semua field wajib diisi");
}

$user_id = intval($_SESSION['user_id']);
$judul = filter_bad_words(trim($_POST['judul']));
$konten = filter_bad_words(trim($_POST['konten']));
$forum_id = intval($_POST['forum_id']);
$media_type = isset($_POST['media_type']) && in_array($_POST['media_type'], ['image', 'video']) ? $_POST['media_type'] : 'image';
$filename = null;

// Handle media upload
if (isset($_FILES['media_upload']) && $_FILES['media_upload']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/post/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file = $_FILES['media_upload'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $allowed_extensions = [];
    $allowed_mimes = [];
    $max_size = 0;

    if ($media_type === 'image') {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
    } elseif ($media_type === 'video') {
        $allowed_extensions = ['mp4', 'webm'];
        $allowed_mimes = ['video/mp4', 'video/webm'];
        $max_size = 25 * 1024 * 1024; // 25MB
    }

    // 1. Strict extension check
    if (!in_array($ext, $allowed_extensions, true)) {
        die("Ekstensi file tidak diizinkan. Hanya format: " . implode(', ', $allowed_extensions));
    }

    // 2. Server-side MIME verification using Fileinfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $real_mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($real_mime, $allowed_mimes, true)) {
        die("Tipe file tidak valid (" . htmlspecialchars($real_mime) . ").");
    }

    // 3. For images, verify it is truly a valid image
    if ($media_type === 'image') {
        $img_info = @getimagesize($file['tmp_name']);
        if ($img_info === false) {
            die("File gambar rusak atau tidak valid.");
        }
    }

    // 4. File size check
    if ($file['size'] > $max_size) {
        die("Ukuran file terlalu besar. Maksimal: " . ($max_size / (1024 * 1024)) . "MB");
    }

    // 5. Generate secure random filename
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $target_path = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        die("Gagal mengunggah file");
    }
}

// Insert into database using prepared statement
$stmt = $conn->prepare("INSERT INTO threads (user_id, forum_id, judul, konten, gambar, media_type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("iissss", $user_id, $forum_id, $judul, $konten, $filename, $media_type);

if ($stmt->execute()) {
    // Award +10 Reputation for creating discussion thread
    $conn->query("UPDATE users SET reputation = reputation + 10 WHERE id = $user_id");
    require_once __DIR__ . '/forum_helpers.php';
    award_milestones_if_eligible($conn, $user_id);

    header("Location: index.php?post_success=1");
    exit;
} else {
    die("Error: " . $stmt->error);
}
?>
