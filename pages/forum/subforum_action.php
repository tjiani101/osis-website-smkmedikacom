<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/forum_helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Silakan login terlebih dahulu.']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$action = $_POST['action'] ?? '';

// Fetch current user details
$u_stmt = $conn->prepare("SELECT id, username, role, reputation FROM users WHERE id = ?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$current_user = $u_stmt->get_result()->fetch_assoc();

if (!$current_user) {
    echo json_encode(['success' => false, 'error' => 'Data pengguna tidak valid.']);
    exit;
}

$is_admin = in_array(strtolower($current_user['role']), ['admin', 'dev']);

// ==========================================
// ACTION 1: BUAT SUB-FORUM BARU (Cost: 1200 Rep)
// ==========================================
if ($action === 'create_subforum') {
    $cost = 1200;
    if ($current_user['reputation'] < $cost && !$is_admin) {
        echo json_encode([
            'success' => false, 
            'error' => "Reputasi Anda tidak mencukupi. Butuh minimal {$cost} Reputasi (Reputasi Anda: {$current_user['reputation']})."
        ]);
        exit;
    }

    $nama = trim($_POST['nama'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $rules = trim($_POST['rules'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fa-comments');
    $banner_color = trim($_POST['banner_color'] ?? 'from-blue-600 to-indigo-600');

    if (empty($nama)) {
        echo json_encode(['success' => false, 'error' => 'Nama Sub-Forum wajib diisi.']);
        exit;
    }

    // Auto-generate slug if empty or sanitize
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $nama));
    } else {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', str_replace(['sf/', 'r/'], '', $slug)));
    }
    $slug = trim($slug, '-');

    if (empty($slug)) {
        echo json_encode(['success' => false, 'error' => 'Slug Sub-Forum tidak valid.']);
        exit;
    }

    // Check if slug already exists
    $chk_stmt = $conn->prepare("SELECT id FROM forum WHERE slug = ?");
    $chk_stmt->bind_param("s", $slug);
    $chk_stmt->execute();
    if ($chk_stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => "Sub-Forum sf/{$slug} sudah ada. Pilih nama atau slug lain."]);
        exit;
    }

    // Handle optional banner image upload
    $banner_image = null;
    if (isset($_FILES['banner_file']) && $_FILES['banner_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['banner_file'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (in_array($file['type'], $allowed) && $file['size'] <= 4 * 1024 * 1024) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'sf_banner_' . uniqid() . '.' . $ext;
            $dest = __DIR__ . '/uploads/' . $new_name;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $banner_image = $new_name;
            }
        }
    }

    // Deduct reputation (unless admin if desired, but user specified pay 1200)
    if (!$is_admin || $current_user['reputation'] >= $cost) {
        $conn->query("UPDATE users SET reputation = GREATEST(0, reputation - {$cost}) WHERE id = {$user_id}");
    }

    $moderators = $current_user['username'];
    $ins = $conn->prepare("
        INSERT INTO forum (nama, description, slug, rules, moderators, icon, banner_color, banner_image, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->bind_param("ssssssssi", $nama, $description, $slug, $rules, $moderators, $icon, $banner_color, $banner_image, $user_id);
    
    if ($ins->execute()) {
        $new_forum_id = $conn->insert_id;

        // Notification
        $notif_msg = "Selamat! Anda resmi menjadi pemilik Sub-Forum sf/{$slug}. Reputasi terpotong 1.200.";
        $n_stmt = $conn->prepare("INSERT INTO notifications (user_id, actor_id, type, message) VALUES (?, ?, 'mention', ?)");
        $n_stmt->bind_param("iis", $user_id, $user_id, $notif_msg);
        $n_stmt->execute();

        echo json_encode([
            'success' => true, 
            'message' => "Sub-Forum sf/{$slug} berhasil dibuat! Selamat mengelola komunitas Anda.",
            'forum_id' => $new_forum_id,
            'slug' => $slug
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Gagal membuat Sub-Forum. Silakan coba lagi.']);
        exit;
    }
}

// ==========================================
// ACTION 2: ARCHIVE THREAD (Wewenang Kreator / Admin)
// ==========================================
if ($action === 'archive_thread') {
    $thread_id = intval($_POST['thread_id'] ?? 0);
    if ($thread_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID topik tidak valid.']);
        exit;
    }

    // Check thread and forum creator
    $th_stmt = $conn->prepare("
        SELECT t.id, t.forum_id, t.judul, f.created_by as forum_creator, f.slug as forum_slug
        FROM threads t
        LEFT JOIN forum f ON t.forum_id = f.id
        WHERE t.id = ?
    ");
    $th_stmt->bind_param("i", $thread_id);
    $th_stmt->execute();
    $th = $th_stmt->get_result()->fetch_assoc();

    if (!$th) {
        echo json_encode(['success' => false, 'error' => 'Topik tidak ditemukan.']);
        exit;
    }

    $is_creator = ($th['forum_creator'] == $user_id);
    if (!$is_creator && !$is_admin) {
        echo json_encode(['success' => false, 'error' => 'Anda tidak memiliki wewenang untuk mengarsipkan topik di sub-forum ini.']);
        exit;
    }

    $arch_stmt = $conn->prepare("
        UPDATE threads 
        SET is_archived = 1, archived_at = NOW(), archived_by = ? 
        WHERE id = ?
    ");
    $arch_stmt->bind_param("ii", $user_id, $thread_id);
    if ($arch_stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Topik berhasil diarsipkan. Topik disembunyikan dari feed dan akan terhapus otomatis setelah 1 minggu (7 hari).'
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Gagal mengarsipkan topik.']);
        exit;
    }
}

// ==========================================
// ACTION 3: UNARCHIVE THREAD (Restore)
// ==========================================
if ($action === 'unarchive_thread') {
    $thread_id = intval($_POST['thread_id'] ?? 0);
    $th_stmt = $conn->prepare("
        SELECT t.id, f.created_by as forum_creator
        FROM threads t
        LEFT JOIN forum f ON t.forum_id = f.id
        WHERE t.id = ?
    ");
    $th_stmt->bind_param("i", $thread_id);
    $th_stmt->execute();
    $th = $th_stmt->get_result()->fetch_assoc();

    if (!$th) {
        echo json_encode(['success' => false, 'error' => 'Topik tidak ditemukan.']);
        exit;
    }

    $is_creator = ($th['forum_creator'] == $user_id);
    if (!$is_creator && !$is_admin) {
        echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
        exit;
    }

    $unarch_stmt = $conn->prepare("
        UPDATE threads 
        SET is_archived = 0, archived_at = NULL, archived_by = NULL 
        WHERE id = ?
    ");
    $unarch_stmt->bind_param("i", $thread_id);
    if ($unarch_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Topik berhasil dipulihkan dari arsip.']);
        exit;
    }
}

// ==========================================
// ACTION 4: REQUEST BAN AKUN (Kreator lapor ke Admin/Dev)
// ==========================================
if ($action === 'request_ban') {
    $forum_id = intval($_POST['forum_id'] ?? 0);
    $target_user_id = intval($_POST['target_user_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($forum_id <= 0 || $target_user_id <= 0 || empty($reason)) {
        echo json_encode(['success' => false, 'error' => 'Mohon lengkapi forum, pengguna target, dan alasan pengajuan ban.']);
        exit;
    }

    // Verify user is creator of this forum or admin
    $f_stmt = $conn->prepare("SELECT id, nama, slug, created_by FROM forum WHERE id = ?");
    $f_stmt->bind_param("i", $forum_id);
    $f_stmt->execute();
    $f_info = $f_stmt->get_result()->fetch_assoc();

    if (!$f_info) {
        echo json_encode(['success' => false, 'error' => 'Sub-Forum tidak ditemukan.']);
        exit;
    }

    if ($f_info['created_by'] != $user_id && !$is_admin) {
        echo json_encode(['success' => false, 'error' => 'Hanya pemilik Sub-Forum atau Admin yang dapat mengajukan ban pengguna.']);
        exit;
    }

    if ($target_user_id == $user_id) {
        echo json_encode(['success' => false, 'error' => 'Anda tidak dapat mengajukan ban untuk akun Anda sendiri.']);
        exit;
    }

    $ban_stmt = $conn->prepare("
        INSERT INTO subforum_ban_requests (forum_id, requested_by, target_user_id, reason, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $ban_stmt->bind_param("iiis", $forum_id, $user_id, $target_user_id, $reason);
    if ($ban_stmt->execute()) {
        // Send notif to admins
        $admin_res = $conn->query("SELECT id FROM users WHERE role IN ('admin', 'DEV')");
        if ($admin_res) {
            $msg = "Pengajuan Ban Baru: Pemilik sf/{$f_info['slug']} mengajukan ban untuk seorang pengguna. Mohon verifikasi.";
            while ($adm = $admin_res->fetch_assoc()) {
                $a_id = $adm['id'];
                $conn->query("INSERT INTO notifications (user_id, actor_id, type, message) VALUES ($a_id, $user_id, 'mention', '$msg')");
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Permintaan ban akun telah terkirim ke Admin & Dev untuk diverifikasi!'
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Gagal mengajukan permintaan ban.']);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Aksi tidak dikenal.']);
exit;
