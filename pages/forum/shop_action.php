<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/forum_helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Silakan masuk terlebih dahulu.']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$action = $_POST['action'] ?? '';

// Fetch current user
$u_stmt = $conn->prepare("SELECT id, username, reputation, active_frame, active_perk_id FROM users WHERE id = ?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$user = $u_stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Pengguna tidak ditemukan.']);
    exit;
}

// -------------------------------------------------------------
// 1. BUY FRAME
// -------------------------------------------------------------
if ($action === 'buy_frame') {
    $frame_id = intval($_POST['frame_id'] ?? 0);
    $f_stmt = $conn->prepare("SELECT * FROM avatar_frames WHERE id = ?");
    $f_stmt->bind_param("i", $frame_id);
    $f_stmt->execute();
    $frame = $f_stmt->get_result()->fetch_assoc();

    if (!$frame) {
        echo json_encode(['success' => false, 'error' => 'Frame tidak valid.']);
        exit;
    }

    // Check if already owned
    $own = $conn->query("SELECT id FROM user_frames WHERE user_id = $user_id AND frame_id = $frame_id");
    if ($own && $own->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Kamu sudah memiliki frame ini!']);
        exit;
    }

    if ($user['reputation'] < $frame['price_rep']) {
        echo json_encode(['success' => false, 'error' => 'Reputation kamu tidak mencukupi (Butuh ' . $frame['price_rep'] . ' Rep).']);
        exit;
    }

    // Deduct & insert
    $conn->query("UPDATE users SET reputation = reputation - {$frame['price_rep']} WHERE id = $user_id");
    $conn->query("INSERT INTO user_frames (user_id, frame_id) VALUES ($user_id, $frame_id)");

    // Auto-equip if first frame
    if (empty($user['active_frame'])) {
        $img = $conn->real_escape_string($frame['image_url']);
        $conn->query("UPDATE users SET active_frame = '$img' WHERE id = $user_id");
    }

    // Award Collector badge milestone if eligible
    $conn->query("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES ($user_id, 8)");

    echo json_encode([
        'success' => true,
        'message' => 'Berhasil membeli frame ' . $frame['name'] . '!',
        'new_rep' => $user['reputation'] - $frame['price_rep']
    ]);
    exit;
}

// -------------------------------------------------------------
// 2. BUY BADGE
// -------------------------------------------------------------
if ($action === 'buy_badge') {
    $badge_id = intval($_POST['badge_id'] ?? 0);
    $b_stmt = $conn->prepare("SELECT * FROM badges WHERE id = ? AND type = 'purchasable'");
    $b_stmt->bind_param("i", $badge_id);
    $b_stmt->execute();
    $badge = $b_stmt->get_result()->fetch_assoc();

    if (!$badge) {
        echo json_encode(['success' => false, 'error' => 'Lencana tidak tersedia untuk dibeli.']);
        exit;
    }

    $own = $conn->query("SELECT id FROM user_badges WHERE user_id = $user_id AND badge_id = $badge_id");
    if ($own && $own->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Kamu sudah memiliki lencana ini!']);
        exit;
    }

    if ($user['reputation'] < $badge['price_rep']) {
        echo json_encode(['success' => false, 'error' => 'Reputation kamu tidak mencukupi (Butuh ' . $badge['price_rep'] . ' Rep).']);
        exit;
    }

    $conn->query("UPDATE users SET reputation = reputation - {$badge['price_rep']} WHERE id = $user_id");
    $conn->query("INSERT INTO user_badges (user_id, badge_id) VALUES ($user_id, $badge_id)");

    echo json_encode([
        'success' => true,
        'message' => 'Lencana ' . $badge['name'] . ' berhasil didapatkan!',
        'new_rep' => $user['reputation'] - $badge['price_rep']
    ]);
    exit;
}

// -------------------------------------------------------------
// 3. BUY PERK
// -------------------------------------------------------------
if ($action === 'buy_perk') {
    $perk_id = intval($_POST['perk_id'] ?? 0);
    $p_stmt = $conn->prepare("SELECT * FROM perks WHERE id = ?");
    $p_stmt->bind_param("i", $perk_id);
    $p_stmt->execute();
    $perk = $p_stmt->get_result()->fetch_assoc();

    if (!$perk) {
        echo json_encode(['success' => false, 'error' => 'Perk tidak ditemukan.']);
        exit;
    }

    $own = $conn->query("SELECT id FROM user_perks WHERE user_id = $user_id AND perk_id = $perk_id");
    if ($own && $own->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Kamu sudah memiliki Perk ini!']);
        exit;
    }

    if ($user['reputation'] < $perk['price_rep']) {
        echo json_encode(['success' => false, 'error' => 'Reputation kamu tidak mencukupi (Butuh ' . $perk['price_rep'] . ' Rep).']);
        exit;
    }

    $conn->query("UPDATE users SET reputation = reputation - {$perk['price_rep']} WHERE id = $user_id");
    $conn->query("INSERT INTO user_perks (user_id, perk_id, is_showcase) VALUES ($user_id, $perk_id, 0)");

    // Auto set as active if no active perk yet
    if (empty($user['active_perk_id'])) {
        $conn->query("UPDATE users SET active_perk_id = $perk_id WHERE id = $user_id");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Perk ' . $perk['name'] . ' berhasil dibuka!',
        'new_rep' => $user['reputation'] - $perk['price_rep']
    ]);
    exit;
}

// -------------------------------------------------------------
// 4. EQUIP / UNEQUIP FRAME
// -------------------------------------------------------------
if ($action === 'equip_frame') {
    $frame_file = trim($_POST['frame_file'] ?? '');

    if ($frame_file === '' || $frame_file === 'none') {
        $conn->query("UPDATE users SET active_frame = NULL WHERE id = $user_id");
        echo json_encode(['success' => true, 'message' => 'Frame avatar dilepas.']);
        exit;
    }

    // Verify ownership
    $chk = $conn->prepare("
        SELECT f.image_url 
        FROM avatar_frames f
        JOIN user_frames uf ON f.id = uf.frame_id
        WHERE uf.user_id = ? AND f.image_url = ?
    ");
    $chk->bind_param("is", $user_id, $frame_file);
    $chk->execute();
    $found = $chk->get_result()->fetch_assoc();

    if (!$found) {
        echo json_encode(['success' => false, 'error' => 'Kamu belum memiliki frame ini.']);
        exit;
    }

    $escaped = $conn->real_escape_string($found['image_url']);
    $conn->query("UPDATE users SET active_frame = '$escaped' WHERE id = $user_id");

    echo json_encode(['success' => true, 'message' => 'Frame avatar berhasil dipasang!']);
    exit;
}

// -------------------------------------------------------------
// 5. SET ACTIVE PERK (Displayed next to name)
// -------------------------------------------------------------
if ($action === 'set_active_perk') {
    $perk_id = intval($_POST['perk_id'] ?? 0);

    if ($perk_id === 0) {
        $conn->query("UPDATE users SET active_perk_id = NULL WHERE id = $user_id");
        echo json_encode(['success' => true, 'message' => 'Gelar perk di samping nama dinonaktifkan.']);
        exit;
    }

    $own = $conn->query("SELECT id FROM user_perks WHERE user_id = $user_id AND perk_id = $perk_id");
    if (!$own || $own->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Kamu belum memiliki perk ini.']);
        exit;
    }

    $conn->query("UPDATE users SET active_perk_id = $perk_id WHERE id = $user_id");
    echo json_encode(['success' => true, 'message' => 'Perk aktif berhasil dipasang di samping nama!']);
    exit;
}

// -------------------------------------------------------------
// 6. TOGGLE SHOWCASE PERK (Max 5 or 7 for VIP)
// -------------------------------------------------------------
if ($action === 'toggle_showcase_perk') {
    $perk_id = intval($_POST['perk_id'] ?? 0);
    $status = intval($_POST['status'] ?? 0); // 1 or 0

    $own = $conn->query("SELECT id FROM user_perks WHERE user_id = $user_id AND perk_id = $perk_id");
    if (!$own || $own->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Perk tidak ditemukan di inventaris kamu.']);
        exit;
    }

    if ($status === 1) {
        // Check current showcase count
        // Check if user owns VIP perk (perk_id 15)
        $vip_check = $conn->query("SELECT id FROM user_perks WHERE user_id = $user_id AND perk_id = 15");
        $max_allowed = ($vip_check && $vip_check->num_rows > 0) ? 7 : 5;

        $cur_count_res = $conn->query("SELECT COUNT(*) as cnt FROM user_perks WHERE user_id = $user_id AND is_showcase = 1");
        $cur_count = (int)($cur_count_res->fetch_assoc()['cnt'] ?? 0);

        if ($cur_count >= $max_allowed) {
            echo json_encode(['success' => false, 'error' => "Batas maksimal showcase tercapai ($max_allowed perks). Silakan copot salah satu terlebih dahulu."]);
            exit;
        }

        $conn->query("UPDATE user_perks SET is_showcase = 1 WHERE user_id = $user_id AND perk_id = $perk_id");
        echo json_encode(['success' => true, 'message' => 'Perk ditambahkan ke etalase profil!']);
        exit;
    } else {
        $conn->query("UPDATE user_perks SET is_showcase = 0 WHERE user_id = $user_id AND perk_id = $perk_id");
        echo json_encode(['success' => true, 'message' => 'Perk dilepas dari etalase profil.']);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Aksi tidak dikenali.']);
