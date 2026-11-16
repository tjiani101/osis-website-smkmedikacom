<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'db.php';
include 'badwords.php';

function simpanLog($conn, $username, $action) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO logs (timestamp, username, action, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $timestamp, $username, $action, $ip);
    $stmt->execute();
}

if (!isset($_SESSION['user_id'])) {
    simpanLog($conn, 'Guest', 'Unauthorized comment attempt');
    die("Akses ditolak.");
}

$isi = $_POST['isi'] ?? '';
$thread_id = $_POST['thread_id'] ?? '';
$parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

if (!$isi || !$thread_id) {
    simpanLog($conn, $_SESSION['username'], "Invalid comment input - missing fields");
    header("Location: thread.php?id=".$thread_id);
    exit;
}

// Get thread details for notifications
$thread_query = $conn->prepare("SELECT * FROM threads WHERE id = ?");
$thread_query->bind_param("i", $thread_id);
$thread_query->execute();
$thread_result = $thread_query->get_result();
$thread = $thread_result->fetch_assoc();

// Filter bad words
$isi = filter_bad_words($isi);

// Insert comment
$stmt = $conn->prepare("INSERT INTO comments (thread_id, user_id, komentar, parent_id) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iisi", $thread_id, $_SESSION['user_id'], $isi, $parent_id);

if ($stmt->execute()) {
    $comment_id = $stmt->insert_id;
    $action_type = $parent_id ? "Replied to comment $parent_id" : "Posted comment";
    simpanLog($conn, $_SESSION['username'], "$action_type (ID: $comment_id) in thread $thread_id");

    // Create notification for thread owner if not the commenter
    if ($thread && $thread['user_id'] != $_SESSION['user_id']) {
        $message = "{$_SESSION['username']} mengomentari postingan Anda: \"{$thread['judul']}\"";
        $conn->query("INSERT INTO notifications (user_id, actor_id, type, source_id, message) 
                      VALUES ({$thread['user_id']}, {$_SESSION['user_id']}, 'comment', $thread_id, '$message')");
    }

    // Process mentions
    preg_match_all('/@(\w+)/', $isi, $matches);
    foreach ($matches[1] as $username) {
        $userQuery = $conn->query("SELECT id FROM users WHERE username = '$username'");
        if ($userQuery->num_rows > 0) {
            $user = $userQuery->fetch_assoc();
            // Don't notify if mentioned user is the commenter
            if ($user['id'] != $_SESSION['user_id']) {
                $message = "{$_SESSION['username']} menyebut Anda dalam komentar: \"" . substr($isi, 0, 50) . "...\"";
                $conn->query("INSERT INTO notifications (user_id, actor_id, type, source_id, message) 
                              VALUES ({$user['id']}, {$_SESSION['user_id']}, 'mention', $thread_id, '$message')");
            }
        }
    }
} else {
    simpanLog($conn, $_SESSION['username'], "Comment failed: ".$stmt->error);
}

header("Location: thread.php?id=".$thread_id);
exit;
?>