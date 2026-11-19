<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$type = $_POST['type'] ?? '';
$source_id = $_POST['source_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Get notification details based on type
switch ($type) {
    case 'like':
        // Get thread owner
        $thread_query = $conn->prepare("SELECT user_id, judul FROM threads WHERE id = ?");
        $thread_query->bind_param("i", $source_id);
        $thread_query->execute();
        $thread = $thread_query->get_result()->fetch_assoc();

        if ($thread && $thread['user_id'] != $user_id) {
            $target_user = $thread['user_id'];
            $message = "{$_SESSION['username']} menyukai postingan Anda: {$thread['judul']}";
            $conn->query("INSERT INTO notifications (user_id, actor_id, type, source_id, message) 
                          VALUES ($target_user, $user_id, 'like', $source_id, '$message')");
        }
        break;

    case 'comment':
        // Get thread owner
        $thread_query = $conn->prepare("SELECT user_id, judul FROM threads WHERE id = ?");
        $thread_query->bind_param("i", $source_id);
        $thread_query->execute();
        $thread = $thread_query->get_result()->fetch_assoc();

        if ($thread && $thread['user_id'] != $user_id) {
            $target_user = $thread['user_id'];
            $message = "{$_SESSION['username']} mengomentari postingan Anda: {$thread['judul']}";
            $conn->query("INSERT INTO notifications (user_id, actor_id, type, source_id, message) 
                          VALUES ($target_user, $user_id, 'comment', $source_id, '$message')");
        }
        break;

    case 'mention':
        // Implement mention detection in post creation/editing
        break;

    case 'follow':
        // Implement when adding follow system
        break;
}

echo json_encode(['success' => true]);