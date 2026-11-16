<?php
// site/like.php
// Handles POST request to toggle/register a like
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$album  = isset($_POST['album']) ? trim($_POST['album']) : '';
$photo  = isset($_POST['photo']) ? trim($_POST['photo']) : '';

// Basic validation (only alphanumeric, hyphens, underscores, dots for photo)
if (!preg_match('/^[a-zA-Z0-9_\- ]+$/', $album) || !preg_match('/^[a-zA-Z0-9_\-\. ]+$/', $photo)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Database connection
require_once __DIR__ . '/../db.php';

// Get user IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Manage cookie (long‑lasting unique hash)
$cookieName = 'gallery_uid';
$cookieHash = $_COOKIE[$cookieName] ?? null;
if (!$cookieHash) {
    $cookieHash = bin2hex(random_bytes(16)); // 32-char hex
    setcookie($cookieName, $cookieHash, [
        'expires'  => time() + 31536000, // 1 year
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    $_COOKIE[$cookieName] = $cookieHash; // make available in current request
}

// Check if already liked (IP + cookie)
$check = $pdo->prepare("SELECT id FROM likes WHERE album_name = ? AND photo_name = ? AND ip_address = ? AND cookie_hash = ?");
$check->execute([$album, $photo, $ip, $cookieHash]);
if ($check->fetch()) {
    // Already liked
    $total = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE album_name = ? AND photo_name = ?");
    $total->execute([$album, $photo]);
    echo json_encode([
        'success' => true,
        'liked'   => false,
        'total'   => (int)$total->fetchColumn(),
        'message' => 'Anda sudah menyukai foto ini.'
    ]);
    exit;
}

// Insert new like
try {
    $insert = $pdo->prepare("INSERT INTO likes (album_name, photo_name, ip_address, cookie_hash) VALUES (?, ?, ?, ?)");
    $insert->execute([$album, $photo, $ip, $cookieHash]);

    $total = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE album_name = ? AND photo_name = ?");
    $total->execute([$album, $photo]);
    echo json_encode([
        'success' => true,
        'liked'   => true,
        'total'   => (int)$total->fetchColumn(),
        'message' => 'Like berhasil!'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}