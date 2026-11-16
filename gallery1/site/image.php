<?php
// site/image.php - On-The-Fly Compressor & Cacher
ini_set('memory_limit', '512M'); // RAM Extra buat buka gambar 20MB

$album = isset($_GET['album']) ? basename($_GET['album']) : '';
$photo = isset($_GET['photo']) ? basename($_GET['photo']) : '';
$type  = isset($_GET['type']) && $_GET['type'] === 'preview' ? 'preview' : 'thumb';

if (!$album || !$photo) { http_response_code(400); exit; }

$originalPath = __DIR__ . "/../upload/$album/$photo";
if (!file_exists($originalPath)) { http_response_code(404); exit; }

$cacheDir = __DIR__ . "/../cache/$album";
if (!is_dir($cacheDir)) { mkdir($cacheDir, 0755, true); }

$ext = strtolower(pathinfo($photo, PATHINFO_EXTENSION));
$cachePath = "$cacheDir/" . pathinfo($photo, PATHINFO_FILENAME) . "_$type.webp";

// Kalo cache udah ada, langsung tembak!
if (file_exists($cachePath)) {
    header('Content-Type: image/webp');
    header('Cache-Control: public, max-age=31536000');
    readfile($cachePath);
    exit;
}

// Bikin Cache Baru
$info = getimagesize($originalPath);
if (!$info) { http_response_code(500); exit; }
[$width, $height] = $info;

// Load Gambar Ori
switch ($ext) {
    case 'jpg': case 'jpeg': $src = imagecreatefromjpeg($originalPath); break;
    case 'png': $src = imagecreatefrompng($originalPath); break;
    case 'webp': $src = imagecreatefromwebp($originalPath); break;
    default: readfile($originalPath); exit; // Kalo tipe ga disupport, output ori aja
}

// Set Maksimal Lebar
$maxWidth = ($type === 'thumb') ? 600 : 1600;
$quality = ($type === 'thumb') ? 60 : 80;

if ($width > $maxWidth) {
    $newW = $maxWidth;
    $newH = (int)($height * ($maxWidth / $width));
} else {
    $newW = $width; $newH = $height;
}

// Bikin Canvas Baru (WebP)
$dst = imagecreatetruecolor($newW, $newH);
imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
imagewebp($dst, $cachePath, $quality); // Simpen ke Cache

imagedestroy($src); imagedestroy($dst);

// Output Hasilnya
header('Content-Type: image/webp');
header('Cache-Control: public, max-age=31536000');
readfile($cachePath);
exit;