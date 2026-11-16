<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Login required']));
}

$thread_id = (int)$_POST['thread_id'];
$vote_type = (int)$_POST['vote_type'];
$user_id = (int)$_SESSION['user_id'];

// Check existing vote
$check = $conn->prepare("SELECT * FROM votes WHERE thread_id = ? AND user_id = ?");
$check->bind_param("ii", $thread_id, $user_id);
$check->execute();
$existing = $check->get_result()->fetch_assoc();

if($existing) {
    // Update existing vote
    $stmt = $conn->prepare("UPDATE votes SET vote_type = ? WHERE id = ?");
    $stmt->bind_param("ii", $vote_type, $existing['id']);
} else {
    // Insert new vote
    $stmt = $conn->prepare("INSERT INTO votes (thread_id, user_id, vote_type) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $thread_id, $user_id, $vote_type);
}

$stmt->execute();

// Get updated vote count
$count = $conn->query("SELECT SUM(vote_type) AS total FROM votes WHERE thread_id = $thread_id")->fetch_assoc()['total'] ?? 0;

echo json_encode(['success' => true, 'vote_count' => $count]);
?>