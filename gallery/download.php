<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$user_stmt = $conn->prepare("SELECT username, role, tier FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$username = $user['username'] ?? 'User';

$file_param = $_GET['file'] ?? '';
$file_type = $_GET['type'] ?? 'HD';

if (empty($file_param)) {
    die("Parameter file tidak valid.");
}

// Security: Prevent directory traversal
$clean_rel = ltrim(str_replace(['../', '..\\'], '', $file_param), '/');
$full_path = realpath(__DIR__ . '/' . $clean_rel);
$allowed_base = realpath(__DIR__);
$allowed_uploads = realpath(__DIR__ . '/../uploads');

if (!$full_path || (!str_starts_with($full_path, $allowed_base) && !str_starts_with($full_path, $allowed_uploads)) || !is_file($full_path)) {
    // If not found locally, redirect if it is a relative url
    if (file_exists(__DIR__ . '/' . $clean_rel)) {
        $full_path = __DIR__ . '/' . $clean_rel;
    } else {
        die("File tidak ditemukan atau path tidak diizinkan.");
    }
}

// Log download activity
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$action = "Download $file_type: " . basename($full_path);
$timestamp = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO logs (timestamp, username, action, ip_address) VALUES (?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("ssss", $timestamp, $username, $action, $ip_address);
    $stmt->execute();
    $stmt->close();
}

// Serve file download
$filename = basename($full_path);
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $full_path) ?: 'application/octet-stream';
finfo_close($finfo);

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($full_path));
readfile($full_path);
exit;
