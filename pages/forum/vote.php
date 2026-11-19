<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/forum_helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Silakan masuk terlebih dahulu untuk melakukan vote.']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$vote_type = isset($_POST['vote_type']) ? intval($_POST['vote_type']) : 0;
$thread_id = isset($_POST['thread_id']) ? intval($_POST['thread_id']) : 0;
$comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;

if (!in_array($vote_type, [-1, 1])) {
    echo json_encode(['success' => false, 'error' => 'Tipe vote tidak valid.']);
    exit;
}

// ==========================================
// 1. THREAD VOTE
// ==========================================
if ($thread_id > 0) {
    // Get thread author
    $t_stmt = $conn->prepare("SELECT user_id FROM threads WHERE id = ?");
    $t_stmt->bind_param("i", $thread_id);
    $t_stmt->execute();
    $th_info = $t_stmt->get_result()->fetch_assoc();
    if (!$th_info) {
        echo json_encode(['success' => false, 'error' => 'Topik tidak ditemukan.']);
        exit;
    }
    $author_id = intval($th_info['user_id']);

    $check = $conn->prepare("SELECT id, vote_type FROM votes WHERE thread_id = ? AND user_id = ?");
    $check->bind_param("ii", $thread_id, $user_id);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    $current_user_vote = 0;
    $rep_delta = 0;

    if ($existing) {
        $old_type = (int)$existing['vote_type'];
        if ($old_type === $vote_type) {
            // Cancel vote
            $del = $conn->prepare("DELETE FROM votes WHERE id = ?");
            $del->bind_param("i", $existing['id']);
            $del->execute();
            $current_user_vote = 0;
            // Revert author rep
            $rep_delta = ($old_type === 1) ? -5 : 2;
        } else {
            // Switch vote direction
            $upd = $conn->prepare("UPDATE votes SET vote_type = ? WHERE id = ?");
            $upd->bind_param("ii", $vote_type, $existing['id']);
            $upd->execute();
            $current_user_vote = $vote_type;
            // Switch author rep (+5 from -2 = +7, or -5 from +2 = -7)
            $rep_delta = ($vote_type === 1) ? 7 : -7;
        }
    } else {
        // Insert new vote
        $ins = $conn->prepare("INSERT INTO votes (thread_id, user_id, vote_type) VALUES (?, ?, ?)");
        $ins->bind_param("iii", $thread_id, $user_id, $vote_type);
        $ins->execute();
        $current_user_vote = $vote_type;
        $rep_delta = ($vote_type === 1) ? 5 : -2;
    }

    // Update thread author's reputation (if not voting on own thread)
    if ($rep_delta !== 0 && $author_id !== $user_id) {
        $conn->query("UPDATE users SET reputation = GREATEST(0, reputation + ($rep_delta)) WHERE id = $author_id");
    }

    // Check voter milestone
    award_milestones_if_eligible($conn, $user_id);

    // Get updated total vote count
    $cnt_stmt = $conn->prepare("SELECT COALESCE(SUM(vote_type), 0) AS total FROM votes WHERE thread_id = ?");
    $cnt_stmt->bind_param("i", $thread_id);
    $cnt_stmt->execute();
    $count = (int)($cnt_stmt->get_result()->fetch_assoc()['total'] ?? 0);

    echo json_encode([
        'success' => true,
        'item_type' => 'thread',
        'vote_count' => $count,
        'user_vote' => $current_user_vote
    ]);
    exit;
}

// ==========================================
// 2. COMMENT VOTE
// ==========================================
if ($comment_id > 0) {
    // Get comment author
    $c_stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
    $c_stmt->bind_param("i", $comment_id);
    $c_stmt->execute();
    $c_info = $c_stmt->get_result()->fetch_assoc();
    if (!$c_info) {
        echo json_encode(['success' => false, 'error' => 'Komentar tidak ditemukan.']);
        exit;
    }
    $author_id = intval($c_info['user_id']);

    $check = $conn->prepare("SELECT id, vote_type FROM comment_votes WHERE comment_id = ? AND user_id = ?");
    $check->bind_param("ii", $comment_id, $user_id);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    $current_user_vote = 0;
    $rep_delta = 0;

    if ($existing) {
        $old_type = (int)$existing['vote_type'];
        if ($old_type === $vote_type) {
            // Cancel vote
            $del = $conn->prepare("DELETE FROM comment_votes WHERE id = ?");
            $del->bind_param("i", $existing['id']);
            $del->execute();
            $current_user_vote = 0;
            $rep_delta = ($old_type === 1) ? -2 : 1;
        } else {
            // Switch direction
            $upd = $conn->prepare("UPDATE comment_votes SET vote_type = ? WHERE id = ?");
            $upd->bind_param("ii", $vote_type, $existing['id']);
            $upd->execute();
            $current_user_vote = $vote_type;
            $rep_delta = ($vote_type === 1) ? 3 : -3;
        }
    } else {
        // Insert new comment vote
        $ins = $conn->prepare("INSERT INTO comment_votes (comment_id, user_id, vote_type) VALUES (?, ?, ?)");
        $ins->bind_param("iii", $comment_id, $user_id, $vote_type);
        $ins->execute();
        $current_user_vote = $vote_type;
        $rep_delta = ($vote_type === 1) ? 2 : -1;
    }

    if ($rep_delta !== 0 && $author_id !== $user_id) {
        $conn->query("UPDATE users SET reputation = GREATEST(0, reputation + ($rep_delta)) WHERE id = $author_id");
    }

    // Get updated total comment vote count
    $cnt_stmt = $conn->prepare("SELECT COALESCE(SUM(vote_type), 0) AS total FROM comment_votes WHERE comment_id = ?");
    $cnt_stmt->bind_param("i", $comment_id);
    $cnt_stmt->execute();
    $count = (int)($cnt_stmt->get_result()->fetch_assoc()['total'] ?? 0);

    echo json_encode([
        'success' => true,
        'item_type' => 'comment',
        'vote_count' => $count,
        'user_vote' => $current_user_vote
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Parameter tidak lengkap.']);
