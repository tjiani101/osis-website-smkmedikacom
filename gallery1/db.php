<?php
// db.php
// Secure PDO connection - edit credentials to match your database
try {
    $pdo = new PDO(
        'mysql:host=sql302.infinityfree.com;dbname=if0_38709109_gallery;charset=utf8mb4',
        'if0_38709109',
        '0O2uwnawRf',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Log the real error, show a generic message to the user
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Service temporarily unavailable.');
}