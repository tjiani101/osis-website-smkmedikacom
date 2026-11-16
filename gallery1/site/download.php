<?php
// site/download.php
// Forces download of the original high-res image
$album = isset($_GET['album']) ? basename($_GET['album']) : '';
$photo = isset($_GET['photo']) ? basename($_GET['photo']) : '';

if ($album === '' || $photo === '') {
    http_response_code(404);
    exit;
}

$filePath = __DIR__ . '/../upload/' . $album . '/' . $photo;
if (!file_exists($filePath) || !is_file($filePath)) {
    http_response_code(404);
    exit;
}

// Prevent path traversal
$realBase = realpath(__DIR__ . '/../upload/');
$realFile = realpath($filePath);
if ($realFile === false || strpos($realFile, $realBase) !== 0) {
    http_response_code(403);
    exit;
}

// Get file info
$mime = mime_content_type($realFile) ?: 'application/octet-stream';
$size = filesize($realFile);

// Clean output buffer
if (ob_get_level()) ob_end_clean();

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($realFile) . '"');
header('Content-Length: ' . $size);
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

readfile($realFile);
exit;