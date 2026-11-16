<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT COUNT(*) AS count FROM notifications 
                        WHERE user_id = $user_id AND is_read = 0");
$data = $result->fetch_assoc();

echo json_encode(['count' => $data['count']]);