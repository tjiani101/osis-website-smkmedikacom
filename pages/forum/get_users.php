<?php
session_start();
require_once 'db.php';

$search = $_GET['search'] ?? '';

if (strlen($search) < 1) {
    echo json_encode([]);
    exit;
}

// Get current user ID to exclude from results
$currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : -1;

// Search for matching usernames
$searchTerm = $conn->real_escape_string($search);
$query = "SELECT id, username FROM users 
          WHERE username LIKE '$searchTerm%' 
          AND id != $currentUserId
          ORDER BY username ASC
          LIMIT 10";

$result = $conn->query($query);
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = [
        'id' => $row['id'],
        'username' => $row['username']
    ];
}

echo json_encode($users);