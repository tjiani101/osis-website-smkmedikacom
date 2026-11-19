<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/forum_helpers.php';

header('Content-Type: application/json');

$target_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($target_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID pengguna tidak valid.']);
    exit;
}

$u_stmt = $conn->prepare("
    SELECT u.id, u.username, u.role, u.avatar, u.reputation, u.active_frame, u.active_perk_id,
           u.followers_count, u.following_count, u.joined_at, u.tier,
           p.badge_label as perk_label, p.color_class as perk_color, p.description as perk_desc
    FROM users u
    LEFT JOIN perks p ON u.active_perk_id = p.id
    WHERE u.id = ?
");
$u_stmt->bind_param("i", $target_id);
$u_stmt->execute();
$target_user = $u_stmt->get_result()->fetch_assoc();

if (!$target_user) {
    echo json_encode(['success' => false, 'error' => 'Pengguna tidak ditemukan.']);
    exit;
}

// Check following status
$is_following = false;
$logged_user_id = $_SESSION['user_id'] ?? null;
if ($logged_user_id && $logged_user_id != $target_id) {
    $chk_f = $conn->query("SELECT id FROM follows WHERE follower_id = $logged_user_id AND following_id = $target_id");
    if ($chk_f && $chk_f->num_rows > 0) {
        $is_following = true;
    }
}

// Fetch showcase perks (is_showcase = 1)
$showcase_res = $conn->query("
    SELECT p.name, p.badge_label, p.color_class, p.icon, p.description
    FROM user_perks up
    JOIN perks p ON up.perk_id = p.id
    WHERE up.user_id = $target_id AND up.is_showcase = 1
    ORDER BY up.unlocked_at DESC
    LIMIT 7
");
$showcase_perks = [];
if ($showcase_res) {
    while ($sp = $showcase_res->fetch_assoc()) {
        $showcase_perks[] = $sp;
    }
}

// Fetch user badges
$badges_res = $conn->query("
    SELECT b.name, b.icon_url, b.description
    FROM user_badges ub
    JOIN badges b ON ub.badge_id = b.id
    WHERE ub.user_id = $target_id
    ORDER BY ub.unlocked_at DESC
    LIMIT 5
");
$badges = [];
if ($badges_res) {
    while ($b = $badges_res->fetch_assoc()) {
        $badges[] = $b;
    }
}

// Fetch total upvotes
$up_res = $conn->query("
    SELECT COUNT(v.id) as total_upvotes 
    FROM threads t 
    JOIN votes v ON v.thread_id = t.id AND v.vote_type = 1 
    WHERE t.user_id = $target_id
");
$total_upvotes = ($up_res && $u_row = $up_res->fetch_assoc()) ? (int)$u_row['total_upvotes'] : 0;

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $target_id,
        'username' => $target_user['username'],
        'role' => $target_user['role'],
        'avatar' => $target_user['avatar'],
        'active_frame' => $target_user['active_frame'],
        'reputation' => (int)$target_user['reputation'],
        'reputation_formatted' => format_reputation($target_user['reputation']),
        'followers_count' => (int)$target_user['followers_count'],
        'following_count' => (int)$target_user['following_count'],
        'total_upvotes' => $total_upvotes,
        'joined_date' => date('d M Y', strtotime($target_user['joined_at'])),
        'tier' => $target_user['tier'] ?? 'Visitor',
        'active_perk' => $target_user['perk_label'] ? [
            'label' => $target_user['perk_label'],
            'color' => $target_user['perk_color'],
            'desc' => $target_user['perk_desc']
        ] : null,
        'showcase_perks' => $showcase_perks,
        'badges' => $badges,
        'is_following' => $is_following,
        'is_self' => ($logged_user_id == $target_id)
    ]
]);
exit;
