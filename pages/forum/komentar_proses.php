<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/badwords.php';

function simpanLog($conn, $username, $action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $timestamp = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO logs (timestamp, username, action, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $timestamp, $username, $action, $ip);
        $stmt->execute();
    }
}

if (!isset($_SESSION['user_id'])) {
    simpanLog($conn, 'Guest', 'Unauthorized comment attempt');
    header("Location: login.php");
    exit;
}

$isi = trim($_POST['isi'] ?? '');
$thread_id = isset($_POST['thread_id']) ? intval($_POST['thread_id']) : 0;
$parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
$user_id = intval($_SESSION['user_id']);

if (empty($isi) || $thread_id <= 0) {
    header("Location: thread.php?id=" . $thread_id);
    exit;
}

// Get thread details
$t_stmt = $conn->prepare("SELECT * FROM threads WHERE id = ?");
$t_stmt->bind_param("i", $thread_id);
$t_stmt->execute();
$thread = $t_stmt->get_result()->fetch_assoc();

if (!$thread) {
    die("Thread tidak ditemukan.");
}

// Filter bad words
if (function_exists('filter_bad_words')) {
    $isi = filter_bad_words($isi);
}

// Insert comment using prepared statement
$c_stmt = $conn->prepare("INSERT INTO comments (thread_id, user_id, komentar, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())");
$c_stmt->bind_param("iisi", $thread_id, $user_id, $isi, $parent_id);

if ($c_stmt->execute()) {
    $comment_id = $c_stmt->insert_id;

    // Award +3 Reputation for posting a comment
    $conn->query("UPDATE users SET reputation = reputation + 3 WHERE id = $user_id");
    require_once __DIR__ . '/forum_helpers.php';
    award_milestones_if_eligible($conn, $user_id);

    $action_type = $parent_id ? "Replied to comment #$parent_id" : "Posted comment";
    simpanLog($conn, $_SESSION['username'], "$action_type (ID: $comment_id) in thread #$thread_id");

    // Notification for thread author
    if ($thread['user_id'] != $user_id) {
        $notif_msg = "{$_SESSION['username']} mengomentari postingan Anda: \"{$thread['judul']}\"";
        $n_stmt = $conn->prepare("INSERT INTO notifications (user_id, actor_id, type, source_id, message, is_read, created_at) VALUES (?, ?, 'comment', ?, ?, 0, NOW())");
        if ($n_stmt) {
            $n_stmt->bind_param("iiis", $thread['user_id'], $user_id, $thread_id, $notif_msg);
            $n_stmt->execute();
        }
    }

    // Process mentions (@username)
    preg_match_all('/@([a-zA-Z0-9_.\-]+)/', $isi, $matches);
    if (!empty($matches[1])) {
        $mentioned_usernames = array_unique($matches[1]);
        foreach ($mentioned_usernames as $m_user) {
            $u_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $u_stmt->bind_param("s", $m_user);
            $u_stmt->execute();
            $m_res = $u_stmt->get_result();
            if ($m_res->num_rows > 0) {
                $m_row = $m_res->fetch_assoc();
                if ($m_row['id'] != $user_id) {
                    $mention_msg = "{$_SESSION['username']} menyebut Anda dalam komentar: \"" . substr($isi, 0, 50) . "...\"";
                    $mn_stmt = $conn->prepare("INSERT INTO notifications (user_id, actor_id, type, source_id, message, is_read, created_at) VALUES (?, ?, 'mention', ?, ?, 0, NOW())");
                    if ($mn_stmt) {
                        $mn_stmt->bind_param("iiis", $m_row['id'], $user_id, $thread_id, $mention_msg);
                        $mn_stmt->execute();
                    }
                }
            }
        }
    }
}

header("Location: thread.php?id=" . $thread_id . "#comment-" . ($comment_id ?? ''));
exit;
