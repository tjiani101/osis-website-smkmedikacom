<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/forum_helpers.php';

// Auto-cleanup archived threads older than 7 days (Permanently delete)
$conn->query("DELETE FROM threads WHERE is_archived = 1 AND archived_at < NOW() - INTERVAL 7 DAY");

// Create notifications table if not exists
$conn->query("
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    actor_id INT DEFAULT NULL,
    type ENUM('like', 'comment', 'mention', 'follow') NOT NULL,
    source_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (actor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// User session info
$logged_user = null;
$is_admin = false;
$user_votes = [];

if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $u_stmt = $conn->prepare("
        SELECT u.id, u.username, u.nis, u.role, u.avatar, u.reputation, u.active_frame, u.active_perk_id,
               p.badge_label as perk_label, p.color_class as perk_color, p.description as perk_desc
        FROM users u 
        LEFT JOIN perks p ON u.active_perk_id = p.id
        WHERE u.id = ?
    ");
    $u_stmt->bind_param("i", $uid);
    $u_stmt->execute();
    $logged_user = $u_stmt->get_result()->fetch_assoc();

    if ($logged_user) {
        $is_admin = in_array(strtolower($logged_user['role']), ['admin', 'dev']);
        award_milestones_if_eligible($conn, $uid);
    }

    // Fetch user thread votes
    $v_res = $conn->query("SELECT thread_id, vote_type FROM votes WHERE user_id = $uid");
    if ($v_res) {
        while ($vr = $v_res->fetch_assoc()) {
            $user_votes[$vr['thread_id']] = (int)$vr['vote_type'];
        }
    }
}

// Get filters from URL
$forum_id = isset($_GET['forum_id']) ? intval($_GET['forum_id']) : null;
$sort = $_GET['sort'] ?? 'hot'; // default to Reddit Hot
$search = isset($_GET['search']) ? trim($_GET['search']) : null;
$view_archived = isset($_GET['view']) && $_GET['view'] === 'archived';

// Base SQL for Threads
$sql = "
    SELECT t.*, u.username, u.role, u.avatar, u.active_frame, u.reputation,
           p.badge_label as perk_label, p.color_class as perk_color, p.description as perk_desc,
           f.nama AS forum_name, f.slug AS forum_slug, f.icon AS forum_icon, f.created_by AS forum_creator, f.banner_image AS forum_banner_image,
           COALESCE(SUM(v.vote_type), 0) AS vote_count,
           (SELECT COUNT(*) FROM comments c WHERE c.thread_id = t.id) AS comment_count,
           (SELECT COUNT(*) FROM votes v_up WHERE v_up.thread_id = t.id AND v_up.vote_type = 1) AS up_votes,
           (SELECT COUNT(*) FROM votes v_down WHERE v_down.thread_id = t.id AND v_down.vote_type = -1) AS down_votes
    FROM threads t 
    JOIN users u ON t.user_id = u.id 
    LEFT JOIN perks p ON u.active_perk_id = p.id
    LEFT JOIN forum f ON t.forum_id = f.id
    LEFT JOIN votes v ON t.id = v.thread_id 
";

// Add WHERE conditions
$conditions = [];
if ($forum_id) {
    $conditions[] = "t.forum_id = $forum_id";
}
if ($search) {
    $searchSafe = $conn->real_escape_string($search);
    $conditions[] = "(t.judul LIKE '%$searchSafe%' OR t.konten LIKE '%$searchSafe%')";
}

// Archive filter (show archived only if explicitly requested)
if ($view_archived) {
    $conditions[] = "t.is_archived = 1";
} else {
    $conditions[] = "(t.is_archived = 0 OR t.is_archived IS NULL)";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Grouping and Reddit Sorting
$sql .= " GROUP BY t.id ";
if ($sort === 'top') {
    $sql .= "ORDER BY vote_count DESC, t.created_at DESC";
} elseif ($sort === 'new' || $sort === 'latest') {
    $sql .= "ORDER BY t.created_at DESC";
} elseif ($sort === 'controversial') {
    $sql .= "ORDER BY (up_votes * down_votes) DESC, comment_count DESC, t.created_at DESC";
} else {
    // Reddit Hot Algorithm
    $sql .= "ORDER BY (COALESCE(SUM(v.vote_type), 0) * 2 + (SELECT COUNT(*) FROM comments c WHERE c.thread_id = t.id) * 3) / POW((TIMESTAMPDIFF(HOUR, t.created_at, NOW()) + 2), 1.2) DESC, t.created_at DESC";
}

$threads = $conn->query($sql);

// Dynamic sub-forums from database
$forums_db = $conn->query("
    SELECT f.*, 
           (SELECT COUNT(*) FROM threads t WHERE t.forum_id = f.id AND (t.is_archived = 0 OR t.is_archived IS NULL)) AS thread_count,
           (SELECT COUNT(*) FROM threads t WHERE t.forum_id = f.id AND t.is_archived = 1) AS archived_count
    FROM forum f 
    ORDER BY f.id ASC
");
$forum_list = [];
$current_subforum = null;
if ($forums_db) {
    while ($f = $forums_db->fetch_assoc()) {
        $forum_list[$f['id']] = $f;
        if ($forum_id && $f['id'] == $forum_id) {
            $current_subforum = $f;
        }
    }
}

// Active broadcast announcement
$announcement_res = $conn->query("SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1");
$active_announcement = ($announcement_res && $announcement_res->num_rows > 0) ? $announcement_res->fetch_assoc() : null;

// Leaderboard: Top Reputasi
$top_rep = [];
$rep_q = $conn->query("SELECT id, username, role, avatar, active_frame, reputation FROM users ORDER BY reputation DESC LIMIT 5");
if ($rep_q) { while ($r = $rep_q->fetch_assoc()) $top_rep[] = $r; }

// Leaderboard: Top Followers
$top_followers = [];
$fol_q = $conn->query("SELECT id, username, role, avatar, active_frame, followers_count FROM users ORDER BY followers_count DESC LIMIT 5");
if ($fol_q) { while ($r = $fol_q->fetch_assoc()) $top_followers[] = $r; }

// Leaderboard: Top Upvoted
$top_upvotes = [];
$upv_q = $conn->query("
    SELECT u.id, u.username, u.role, u.avatar, u.active_frame, COUNT(v.id) as total_upvotes
    FROM users u
    JOIN threads t ON t.user_id = u.id
    JOIN votes v ON v.thread_id = t.id AND v.vote_type = 1
    GROUP BY u.id
    ORDER BY total_upvotes DESC
    LIMIT 5
");
if ($upv_q) { while ($r = $upv_q->fetch_assoc()) $top_upvotes[] = $r; }

// Community Stats for Sidebar
$total_members = 0;
$m_res = $conn->query("SELECT COUNT(*) as c FROM users");
if ($m_res) $total_members = (int)$m_res->fetch_assoc()['c'];

$total_threads_count = 0;
$th_res = $conn->query("SELECT COUNT(*) as c FROM threads WHERE is_archived = 0 OR is_archived IS NULL");
if ($th_res) $total_threads_count = (int)$th_res->fetch_assoc()['c'];

// Get notifications for logged-in user
$notifications = [];
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $notif_query = $conn->query("
        SELECT n.*, u.username AS actor_name 
        FROM notifications n
        LEFT JOIN users u ON n.actor_id = u.id
        WHERE n.user_id = $user_id
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    if ($notif_query) {
        $notifications = $notif_query->fetch_all(MYSQLI_ASSOC);
    }
    $unread_result = $conn->query("
        SELECT COUNT(*) AS count 
        FROM notifications 
        WHERE user_id = $user_id AND is_read = 0
    ");
    if ($unread_result) {
        $unread_count = $unread_result->fetch_assoc()['count'];
    }
}

// Check if user is creator of currently selected subforum
$is_subforum_creator = false;
if ($current_subforum && isset($_SESSION['user_id'])) {
    $is_subforum_creator = ($current_subforum['created_by'] == $_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Komunitas - OSIS & MPK SMK Medikacom</title>
    
    <!-- Meta OG Tags -->
    <meta property="og:title" content="Forum Komunitas OSIS & MPK Medikacom" />
    <meta property="og:description" content="Tempat diskusi, tanya jawab, dan ide siswa SMK Medikacom ala Reddit" />
    <meta property="og:image" content="../../images/logo.png" />
    <meta property="og:type" content="website" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Liquid Theme Styles -->
    <link rel="stylesheet" href="../../assets/css/liquid-theme.css">

    <style>
        .vote-pill {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 999px;
            backdrop-filter: blur(8px);
            padding: 4px;
        }
        .vote-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
            color: rgba(255, 255, 255, 0.6);
        }
        .vote-btn:hover {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            transform: scale(1.1);
        }
        .vote-btn.upvoted {
            color: #60a5fa !important;
            background: rgba(59, 130, 246, 0.25) !important;
        }
        .vote-btn.downvoted {
            color: #f87171 !important;
            background: rgba(239, 68, 68, 0.25) !important;
        }
        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #ef4444;
            color: white;
            font-size: 0.6rem;
            font-weight: 800;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #05050f;
        }
        /* Custom scrollbar */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Discord Popover animations */
        #user-hover-card {
            transition: opacity 0.18s cubic-bezier(0.16, 1, 0.3, 1), transform 0.18s cubic-bezier(0.16, 1, 0.3, 1);
            transform: scale(0.96) translateY(-4px);
        }
        #user-hover-card.active {
            opacity: 1 !important;
            transform: scale(1) translateY(0) !important;
            pointer-events: auto !important;
        }
    </style>
</head>
<body class="selection:bg-blue-500/30 selection:text-white">

    <!-- Blurred Background Elements -->
    <div class="main-background"></div>
    <div class="main-overlay"></div>
    
    <!-- Compact Header & Navbar -->
    <header id="navbar" class="fixed top-0 left-0 right-0 z-50 pt-2 sm:pt-3 px-3 sm:px-6">
        <nav class="w-full max-w-[1720px] mx-auto">
            <div class="flex items-center justify-between liquid-glass px-3.5 sm:px-5 py-2">
                
                <!-- Logo & Brand -->
                <a href="../../index.html" class="flex shrink-0 items-center group gap-2">
                    <img class="h-6 sm:h-7 w-auto drop-shadow-md" src="../../images/logo.png" alt="Logo OSIS MPK" onerror="this.onerror=null;this.src='https://placehold.co/100x100/ffffff/000000?text=Logo';">
                    <span class="text-white text-xs sm:text-sm font-extrabold tracking-wide">
                        FORUM <span class="text-blue-400 font-semibold text-[10px] sm:text-xs">MEDIKACOM</span>
                    </span>
                </a>

                <!-- Desktop Compact Nav Links -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="index.php" class="px-3 py-1 rounded-full text-white bg-white/15 border border-white/20 font-bold text-xs">Forum</a>
                    <a href="shop.php" class="px-3 py-1 rounded-full text-amber-300 hover:text-amber-200 hover:bg-white/10 font-bold text-xs flex items-center gap-1">
                        <i class="fa fa-store text-[10px] text-amber-400"></i>
                        <span>Toko</span>
                    </a>
                    <a href="../../gallery/" class="px-3 py-1 rounded-full text-white/70 hover:text-white hover:bg-white/10 font-medium text-xs">Galeri</a>
                    <a href="../../index.html" class="px-3 py-1 rounded-full text-white/70 hover:text-white hover:bg-white/10 font-medium text-xs">Beranda</a>
                </div>

                <!-- User Area (Compact) -->
                <div class="flex items-center gap-1.5 sm:gap-2.5">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        
                        <!-- Rep Pill in Header -->
                        <a href="shop.php" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-300 font-extrabold text-[11px] hover:bg-amber-500/25 transition-all" title="Reputasi Saya">
                            <i class="fa fa-star text-amber-400 text-[10px]"></i>
                            <span><?= number_format($logged_user['reputation'] ?? 0) ?></span>
                        </a>

                        <!-- Notification Button -->
                        <div class="relative">
                            <button id="notification-toggle" class="relative p-1.5 sm:p-2 rounded-full bg-white/5 border border-white/15 text-white/80 hover:text-white hover:bg-white/10 transition-all" title="Notifikasi">
                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?= $unread_count ?></span>
                                <?php endif; ?>
                            </button>

                            <!-- Dropdown -->
                            <div id="notification-dropdown" class="hidden absolute right-0 mt-3 w-72 sm:w-80 liquid-glass p-4 rounded-2xl shadow-2xl z-50 border border-white/20">
                                <div class="flex items-center justify-between pb-3 mb-2 border-b border-white/10">
                                    <h3 class="font-bold text-sm text-white">Notifikasi</h3>
                                    <button class="mark-all-read text-xs text-blue-400 hover:text-blue-300 font-semibold">Tandai Dibaca</button>
                                </div>
                                <div class="max-h-72 overflow-y-auto space-y-2">
                                    <?php if (!empty($notifications)): ?>
                                        <?php foreach ($notifications as $notif): ?>
                                            <div class="notification-item p-3 rounded-xl border border-white/5 hover:bg-white/10 cursor-pointer <?= $notif['is_read'] ? 'bg-white/5 text-white/70' : 'bg-blue-500/15 text-white font-medium' ?>" data-id="<?= $notif['id'] ?>">
                                                <div class="text-xs"><?= htmlspecialchars($notif['message']) ?></div>
                                                <div class="text-[10px] text-white/40 mt-1"><?= date('d M Y H:i', strtotime($notif['created_at'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-center py-4 text-xs text-white/50">Belum ada notifikasi baru.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Buat Post Button (Compact) -->
                        <a href="buat_post.php<?= $forum_id ? '?forum_id=' . $forum_id : '' ?>" class="liquid-btn px-3 py-1.5 text-[11px] sm:text-xs font-bold flex items-center gap-1.5" title="Buat Topik">
                            <i class="fa fa-pen text-[10px]"></i>
                            <span class="hidden sm:inline">Buat Topik</span>
                        </a>

                        <!-- User Profile Link -->
                        <a href="profile.php" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 text-white transition-all user-hover-trigger" data-user-id="<?= $logged_user['id'] ?>" title="Profil Saya">
                            <?= render_avatar_with_frame($logged_user['username'], $logged_user['avatar'], $logged_user['active_frame'], 'xs') ?>
                            <span class="text-xs font-bold hidden md:inline max-w-[90px] truncate"><?= htmlspecialchars($logged_user['username']) ?></span>
                        </a>

                        <!-- Admin Panel Shortcut -->
                        <?php if ($is_admin): ?>
                            <a href="dev.php" class="p-1.5 rounded-full bg-purple-600/30 hover:bg-purple-600/50 border border-purple-500/40 text-purple-300 transition-all" title="Admin Panel">
                                <i class="fa fa-shield-halved text-xs"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Logout -->
                        <a href="logout.php" class="p-1.5 rounded-full bg-white/5 hover:bg-red-500/20 text-white/60 hover:text-red-300 border border-white/10 transition-all" title="Keluar">
                            <i class="fa fa-arrow-right-from-bracket text-xs"></i>
                        </a>

                    <?php else: ?>
                        <a href="login.php" class="liquid-btn-secondary px-3 py-1 text-xs font-semibold">
                            Masuk
                        </a>
                        <a href="register.php" class="liquid-btn px-3.5 py-1 text-xs font-bold">
                            Daftar
                        </a>
                    <?php endif; ?>

                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-btn" class="md:hidden p-1.5 rounded-xl bg-white/10 border border-white/20 text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden mt-2">
                <div class="liquid-glass p-4 flex flex-col space-y-2 text-center text-sm">
                    <a href="../../index.html" class="py-2 text-white/80 hover:text-white rounded-xl">Beranda</a>
                    <a href="index.php" class="py-2 text-white bg-white/15 rounded-xl font-bold">Forum Diskusi</a>
                    <a href="shop.php" class="py-2 text-amber-300 bg-amber-500/10 rounded-xl font-bold">🛍️ Toko Reputasi</a>
                    <a href="../../gallery/" class="py-2 text-white/80 hover:text-white rounded-xl">Galeri</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($is_admin): ?>
                            <a href="dev.php" class="py-2 text-purple-300 bg-purple-500/20 rounded-xl font-bold">🛡️ Admin Panel</a>
                        <?php endif; ?>
                        <a href="profile.php" class="py-2 text-blue-300 bg-blue-500/10 rounded-xl font-bold">Profil Akun Saya</a>
                        <button onclick="openCreateSubforumModal()" class="py-2 text-indigo-300 bg-indigo-500/15 rounded-xl font-bold flex items-center justify-center gap-1.5">
                            <i class="fa fa-folder-plus text-xs"></i> Buat Sub-Forum (1.200 ⭐)
                        </button>
                        <a href="buat_post.php" class="liquid-btn py-2.5 font-bold mt-2">Buat Topik Baru</a>
                        <a href="logout.php" class="py-2 text-red-400 hover:text-red-300 font-semibold text-xs">Keluar</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content Container: 3 Columns Layout (Reddit Wide Style) -->
    <main class="w-full max-w-[1780px] mx-auto px-3 sm:px-5 lg:px-6 xl:px-8 pt-24 sm:pt-28 pb-24">

        <!-- Active Broadcast Announcement (if any) -->
        <?php if ($active_announcement): 
            $ann_colors = [
                'info' => 'bg-blue-500/15 border-blue-500/30 text-blue-200',
                'warning' => 'bg-amber-500/15 border-amber-500/30 text-amber-200',
                'success' => 'bg-emerald-500/15 border-emerald-500/30 text-emerald-200',
                'danger' => 'bg-red-500/15 border-red-500/30 text-red-200',
            ];
            $ann_bg = $ann_colors[$active_announcement['type']] ?? $ann_colors['info'];
        ?>
            <div id="announcement-banner" class="mb-5 p-4 rounded-2xl border backdrop-blur-xl flex items-start justify-between gap-4 <?= $ann_bg ?> shadow-lg shadow-black/20">
                <div class="flex items-start gap-3">
                    <div class="p-2 rounded-xl bg-white/10 text-base shrink-0 mt-0.5">
                        <i class="fa fa-bullhorn animate-bounce"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-sm text-white"><?= htmlspecialchars($active_announcement['title']) ?></h4>
                        <p class="text-xs mt-0.5 leading-relaxed opacity-90"><?= nl2br(htmlspecialchars($active_announcement['content'])) ?></p>
                    </div>
                </div>
                <button onclick="document.getElementById('announcement-banner').style.display='none'" class="text-white/60 hover:text-white p-1 text-xs shrink-0">
                    ✕
                </button>
            </div>
        <?php endif; ?>

        <!-- 3-Column Wide Layout (Pushed to sides, maximum post width) -->
        <div class="flex flex-col lg:flex-row gap-5 lg:gap-6 items-start justify-between">
            
            <!-- ============================================== -->
            <!-- LEFT SIDEBAR: SUB-FORUMS DIRECTORY             -->
            <!-- ============================================== -->
            <aside class="hidden lg:block w-64 xl:w-72 shrink-0 sticky top-24 space-y-4">
                
                <!-- Sub-Forum Directory Card -->
                <div class="liquid-glass p-4 rounded-2xl border border-white/10 shadow-xl">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-white/10">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center text-xs">
                                <i class="fa fa-layer-group"></i>
                            </div>
                            <h3 class="font-extrabold text-xs uppercase tracking-wider text-white">Sub-Forum</h3>
                        </div>
                        <span class="text-[10px] text-white/50 font-bold"><?= count($forum_list) ?> Komunitas</span>
                    </div>

                    <!-- Button: Buat Sub-Forum (Cost 1200 Rep) -->
                    <div class="mb-3.5">
                        <button onclick="openCreateSubforumModal()" class="w-full py-2 px-3 rounded-xl bg-gradient-to-r from-blue-600/80 to-indigo-600/80 hover:from-blue-500 hover:to-indigo-500 border border-blue-400/30 text-white font-bold text-xs flex items-center justify-between group shadow-lg shadow-blue-900/20 transition-all hover:scale-[1.02]">
                            <span class="flex items-center gap-1.5">
                                <i class="fa fa-folder-plus text-blue-200 group-hover:rotate-6 transition-transform"></i>
                                <span>Buat Sub-Forum</span>
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-400/20 border border-amber-400/40 text-amber-300 font-extrabold flex items-center gap-1">
                                <i class="fa fa-star text-[9px] text-amber-400"></i> 1.200
                            </span>
                        </button>
                    </div>

                    <!-- Sub-Forum Nav List -->
                    <div class="space-y-1 max-h-[460px] overflow-y-auto pr-1 no-scrollbar">
                        <!-- All Feeds -->
                        <a href="index.php<?= $sort !== 'hot' ? '?sort=' . $sort : '' ?>" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= !$forum_id ? 'bg-blue-600 text-white shadow-md' : 'text-white/70 hover:text-white hover:bg-white/10' ?>">
                            <span class="flex items-center gap-2">
                                <i class="fa fa-globe text-[11px] <?= !$forum_id ? 'text-white' : 'text-blue-400' ?>"></i>
                                <span>sf/all (Semua)</span>
                            </span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-white/10 font-mono"><?= $total_threads_count ?></span>
                        </a>

                        <?php foreach ($forum_list as $id => $finfo): 
                            $sub_slug = htmlspecialchars($finfo['slug'] ?: strtolower(str_replace(' ', '-', $finfo['nama'])));
                            $is_active_sf = ($forum_id == $id);
                            $is_sf_owner = (isset($_SESSION['user_id']) && $finfo['created_by'] == $_SESSION['user_id']);
                        ?>
                            <a href="index.php?forum_id=<?= $id ?><?= $sort !== 'hot' ? '&sort=' . $sort : '' ?>" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-all <?= $is_active_sf ? 'bg-blue-600 text-white font-bold shadow-md' : 'text-white/70 hover:text-white hover:bg-white/10' ?>">
                                <span class="flex items-center gap-2 truncate pr-2">
                                    <i class="fa <?= htmlspecialchars($finfo['icon'] ?? 'fa-comments') ?> text-[11px] <?= $is_active_sf ? 'text-white' : 'text-blue-400/80' ?>"></i>
                                    <span class="truncate">sf/<?= $sub_slug ?></span>
                                    <?php if ($is_sf_owner): ?>
                                        <span class="text-[9px] text-amber-300" title="Anda pemilik sub-forum ini">👑</span>
                                    <?php endif; ?>
                                </span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-white/10 font-mono shrink-0"><?= $finfo['thread_count'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sub-forum Creator Authority & Rules Box -->
                <div class="liquid-glass p-3.5 rounded-2xl border border-white/10 text-xs">
                    <div class="flex items-center gap-2 text-amber-300 font-bold mb-1.5">
                        <i class="fa fa-shield-halved text-xs"></i>
                        <span>Wewenang Pemilik Sub-Forum</span>
                    </div>
                    <ul class="text-[11px] text-white/60 space-y-1.5 list-disc list-inside leading-relaxed">
                        <li><strong class="text-white/80">Arsip Postingan:</strong> Postingan disembunyikan dan terhapus otomatis setelah 7 hari.</li>
                        <li><strong class="text-white/80">Request Ban:</strong> Ajukan pemblokiran siswa bermasalah ke Admin/Dev.</li>
                        <li><strong class="text-white/80">Biaya Komunitas:</strong> Butuh 1.200 Reputasi untuk membuka sub-forum baru.</li>
                    </ul>
                </div>

            </aside>

            <!-- ============================================== -->
            <!-- CENTER: MAIN THREAD FEED (Expanded Width)      -->
            <!-- ============================================== -->
            <section class="flex-1 min-w-0 space-y-4">

                <!-- Mobile / Tablet Sub-Forum Pill Navigation -->
                <div class="lg:hidden liquid-glass p-2.5 rounded-2xl border flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
                    <button onclick="openCreateSubforumModal()" class="whitespace-nowrap shrink-0 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-400/40 flex items-center gap-1">
                        <i class="fa fa-plus text-[10px]"></i> Buat (1.200 ⭐)
                    </button>
                    <a href="index.php<?= $sort !== 'hot' ? '?sort=' . $sort : '' ?>" class="whitespace-nowrap shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition-all <?= !$forum_id ? 'bg-blue-600 text-white' : 'bg-white/5 text-white/80' ?>">
                        sf/all
                    </a>
                    <?php foreach ($forum_list as $id => $finfo): 
                        $sub_slug = htmlspecialchars($finfo['slug'] ?: strtolower(str_replace(' ', '-', $finfo['nama'])));
                    ?>
                        <a href="index.php?forum_id=<?= $id ?><?= $sort !== 'hot' ? '&sort=' . $sort : '' ?>" class="whitespace-nowrap shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition-all <?= $forum_id == $id ? 'bg-blue-600 text-white' : 'bg-white/5 text-white/80' ?>">
                            sf/<?= $sub_slug ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Sub-forum Info Banner (If specific sub-forum selected) -->
                <?php if ($current_subforum): 
                    $sf_creator_id = $current_subforum['created_by'];
                    $can_manage_sf = ($is_subforum_creator || $is_admin);
                ?>
                    <div class="p-4 sm:p-5 rounded-2xl liquid-glass border border-white/15 relative overflow-hidden">
                        <!-- Banner image overlay if exists -->
                        <?php if (!empty($current_subforum['banner_image']) && file_exists(__DIR__ . '/uploads/' . $current_subforum['banner_image'])): ?>
                            <div class="absolute inset-0 bg-cover bg-center opacity-20 pointer-events-none" style="background-image: url('uploads/<?= htmlspecialchars($current_subforum['banner_image']) ?>')"></div>
                        <?php endif; ?>

                        <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr <?= htmlspecialchars($current_subforum['banner_color'] ?? 'from-blue-600 to-indigo-600') ?> flex items-center justify-center text-white shrink-0 text-base shadow-lg shadow-black/30 border border-white/20">
                                    <i class="fa <?= htmlspecialchars($current_subforum['icon'] ?? 'fa-comments') ?>"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-black text-white text-base">sf/<?= htmlspecialchars($current_subforum['slug']) ?></h3>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-white/10 text-white/80 font-bold"><?= $current_subforum['thread_count'] ?> postingan</span>
                                        <?php if ($is_subforum_creator): ?>
                                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/40 font-bold">
                                                👑 Anda Pemilik
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-white/70 mt-0.5"><?= htmlspecialchars($current_subforum['description'] ?: 'Ruang diskusi dan aspirasi siswa Medikacom.') ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto">
                                <a href="buat_post.php?forum_id=<?= $current_subforum['id'] ?>" class="liquid-btn py-1.5 px-4 text-xs font-bold w-full sm:w-auto text-center flex items-center justify-center gap-1.5">
                                    <i class="fa fa-plus text-[10px]"></i>
                                    <span>Buat Topik</span>
                                </a>
                            </div>
                        </div>

                        <!-- Creator Authority Toolbar (Archive Toggle & Ban Request) -->
                        <?php if ($can_manage_sf): ?>
                            <div class="relative z-10 mt-3 pt-3 border-t border-white/10 flex items-center justify-between flex-wrap gap-2 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-white/50 uppercase font-bold">Wewenang:</span>
                                    <a href="index.php?forum_id=<?= $current_subforum['id'] ?><?= $view_archived ? '' : '&view=archived' ?>" class="px-2.5 py-1 rounded-lg text-xs font-bold transition-colors <?= $view_archived ? 'bg-amber-500/20 text-amber-300 border border-amber-400/40' : 'bg-white/5 hover:bg-white/10 text-white/70' ?>">
                                        <i class="fa fa-box-archive mr-1"></i>
                                        <?= $view_archived ? 'Kembali ke Post Aktif' : 'Lihat Arsip (' . $current_subforum['archived_count'] . ')' ?>
                                    </a>
                                </div>
                                <button onclick="openRequestBanModal(<?= $current_subforum['id'] ?>, 'sf/<?= htmlspecialchars($current_subforum['slug']) ?>')" class="px-2.5 py-1 rounded-lg bg-red-500/15 hover:bg-red-500/25 border border-red-500/30 text-red-300 text-xs font-bold flex items-center gap-1">
                                    <i class="fa fa-user-slash text-[10px]"></i>
                                    <span>Request Ban Akun</span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Reddit Sorting Bar & Search -->
                <div class="liquid-glass p-3 sm:p-3.5 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-2.5 border">
                    
                    <!-- Sorting Tabs: Hot, New, Top, Controversial -->
                    <div class="flex items-center gap-1.5 w-full md:w-auto overflow-x-auto pb-1 no-scrollbar">
                        <a href="index.php?sort=hot<?= $forum_id ? '&forum_id=' . $forum_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $view_archived ? '&view=archived' : '' ?>" class="whitespace-nowrap shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 <?= $sort === 'hot' ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-md' : 'text-white/60 hover:text-white bg-white/5' ?>">
                            <i class="fa fa-fire text-amber-300 text-[11px]"></i>
                            <span>Hot</span>
                        </a>
                        <a href="index.php?sort=new<?= $forum_id ? '&forum_id=' . $forum_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $view_archived ? '&view=archived' : '' ?>" class="whitespace-nowrap shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 <?= ($sort === 'new' || $sort === 'latest') ? 'bg-blue-600 text-white shadow-md' : 'text-white/60 hover:text-white bg-white/5' ?>">
                            <i class="fa fa-clock text-blue-300 text-[11px]"></i>
                            <span>New</span>
                        </a>
                        <a href="index.php?sort=top<?= $forum_id ? '&forum_id=' . $forum_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $view_archived ? '&view=archived' : '' ?>" class="whitespace-nowrap shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 <?= $sort === 'top' ? 'bg-purple-600 text-white shadow-md' : 'text-white/60 hover:text-white bg-white/5' ?>">
                            <i class="fa fa-trophy text-yellow-300 text-[11px]"></i>
                            <span>Top</span>
                        </a>
                        <a href="index.php?sort=controversial<?= $forum_id ? '&forum_id=' . $forum_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $view_archived ? '&view=archived' : '' ?>" class="whitespace-nowrap shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 <?= $sort === 'controversial' ? 'bg-red-600 text-white shadow-md' : 'text-white/60 hover:text-white bg-white/5' ?>">
                            <i class="fa fa-bolt text-red-300 text-[11px]"></i>
                            <span>Controversial</span>
                        </a>
                    </div>

                    <!-- Search Input Form -->
                    <form method="GET" action="index.php" class="w-full md:w-56">
                        <?php if ($forum_id): ?>
                            <input type="hidden" name="forum_id" value="<?= $forum_id ?>">
                        <?php endif; ?>
                        <?php if ($sort !== 'hot'): ?>
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                        <?php endif; ?>
                        <?php if ($view_archived): ?>
                            <input type="hidden" name="view" value="archived">
                        <?php endif; ?>
                        <div class="relative">
                            <input 
                                type="text" 
                                name="search" 
                                placeholder="Cari topik diskusi..." 
                                value="<?= htmlspecialchars($search ?? '') ?>" 
                                class="liquid-input w-full pl-8 pr-6 py-1.5 text-xs rounded-full"
                            >
                            <svg class="w-3.5 h-3.5 text-white/40 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <?php if ($search): ?>
                                <a href="index.php<?= $forum_id ? '?forum_id=' . $forum_id : '' ?>" class="absolute right-2.5 top-1.5 text-white/40 hover:text-white text-xs">✕</a>
                            <?php endif; ?>
                        </div>
                    </form>

                </div>

                <!-- Archived Mode Notice -->
                <?php if ($view_archived): ?>
                    <div class="p-3 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-200 text-xs flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <i class="fa fa-box-archive text-amber-400"></i>
                            <span>Menampilkan postingan yang diarsipkan. Otomatis terhapus setelah 1 minggu sejak tanggal arsip.</span>
                        </div>
                        <a href="index.php<?= $forum_id ? '?forum_id=' . $forum_id : '' ?>" class="underline text-amber-300 font-bold shrink-0">Tutup Arsip</a>
                    </div>
                <?php endif; ?>

                <!-- Threads Feed -->
                <div class="space-y-4">
                    <?php if ($threads && $threads->num_rows > 0): ?>
                        <?php while($row = $threads->fetch_assoc()): 
                            $curr_vote = $user_votes[$row['id']] ?? 0;
                            $sub_slug = htmlspecialchars($row['forum_slug'] ?: strtolower(str_replace(' ', '-', $row['forum_name'] ?? 'umum')));
                            $active_perk_info = !empty($row['perk_label']) ? [
                                'badge_label' => $row['perk_label'],
                                'color_class' => $row['perk_color'],
                                'description' => $row['perk_desc']
                            ] : null;

                            // Thread moderation privileges
                            $can_archive_this = (
                                isset($_SESSION['user_id']) && 
                                ($row['forum_creator'] == $_SESSION['user_id'] || $is_admin)
                            );
                        ?>
                            <div class="liquid-glass liquid-glass-interactive p-4 sm:p-5 thread-card relative overflow-hidden rounded-2xl border border-white/10" data-thread-id="<?= $row['id'] ?>">
                                <div class="flex items-start gap-3 sm:gap-4">
                                    
                                    <!-- Upvote / Downvote Pill -->
                                    <div class="vote-pill flex flex-col items-center shrink-0">
                                        <button class="vote-btn upvote <?= $curr_vote === 1 ? 'upvoted' : '' ?>" title="Upvote" onclick="handleVote(<?= $row['id'] ?>, 1, this)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                        <span class="vote-count font-extrabold text-xs sm:text-sm text-white my-0.5 font-mono">
                                            <?= $row['vote_count'] ?>
                                        </span>
                                        <button class="vote-btn downvote <?= $curr_vote === -1 ? 'downvoted' : '' ?>" title="Downvote" onclick="handleVote(<?= $row['id'] ?>, -1, this)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </div>

                                    <!-- Thread Body -->
                                    <div class="flex-1 min-w-0">
                                        
                                        <!-- Meta Header: Avatar + Frame + sf/slug + Author + Perks -->
                                        <div class="flex flex-wrap items-center gap-2 mb-2 text-xs">
                                            
                                            <!-- Author Avatar with Frame (Hover & Click trigger) -->
                                            <div class="user-hover-trigger cursor-pointer" data-user-id="<?= $row['user_id'] ?>">
                                                <?= render_avatar_with_frame($row['username'], $row['avatar'], $row['active_frame'], 'xs') ?>
                                            </div>

                                            <!-- Sub-forum sf/topik Pill -->
                                            <a href="index.php?forum_id=<?= $row['forum_id'] ?>" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wider bg-blue-500/20 hover:bg-blue-500/30 border border-blue-400/40 text-blue-300 transition-colors">
                                                sf/<?= $sub_slug ?>
                                            </a>

                                            <span class="text-white/40">•</span>

                                            <!-- Author Username + Role + Active Perk Flair -->
                                            <span class="text-white/70 flex items-center gap-1.5 flex-wrap">
                                                <span>oleh</span>
                                                <?= render_user_flair($row['user_id'], $row['username'], $row['role'], $active_perk_info, $row['reputation'] ?? 0) ?>
                                            </span>

                                            <span class="text-white/40">•</span>
                                            <span class="text-white/40 text-[11px]">
                                                <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                                            </span>
                                        </div>

                                        <!-- Archived Notification Pill on Card -->
                                        <?php if (!empty($row['is_archived'])): 
                                            $arch_date = new DateTime($row['archived_at']);
                                            $now_date = new DateTime();
                                            $days_passed = $arch_date->diff($now_date)->days;
                                            $days_left = max(0, 7 - $days_passed);
                                        ?>
                                            <div class="mb-2 px-3 py-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-300 text-xs flex items-center justify-between">
                                                <span><i class="fa fa-box-archive mr-1"></i> Diarsipkan • Dihapus otomatis dalam <strong><?= $days_left ?> hari</strong></span>
                                                <?php if ($can_archive_this): ?>
                                                    <button onclick="unarchiveThread(<?= $row['id'] ?>)" class="underline text-white font-bold hover:text-amber-200">Pulihkan</button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Thread Title -->
                                        <h2 class="text-base sm:text-lg font-bold text-white mb-2 leading-snug break-words">
                                            <a href="thread.php?id=<?= $row['id'] ?>" class="hover:text-blue-300 transition-colors">
                                                <?= htmlspecialchars($row['judul']) ?>
                                            </a>
                                        </h2>

                                        <!-- Thread Content Excerpt -->
                                        <p class="text-xs sm:text-sm text-white/70 line-clamp-3 leading-relaxed mb-4 break-words">
                                            <?= htmlspecialchars($row['konten']) ?>
                                        </p>

                                        <!-- Media Attachment -->
                                        <?php if (!empty($row['gambar'])): ?>
                                            <div class="mb-4 rounded-xl sm:rounded-2xl overflow-hidden max-h-[520px] border border-white/10 bg-black/40 flex items-center justify-center">
                                                <?php if ($row['media_type'] === 'video'): ?>
                                                    <video src="post/<?= htmlspecialchars($row['gambar']) ?>" controls class="w-full max-h-[520px] object-contain rounded-xl sm:rounded-2xl"></video>
                                                <?php else: ?>
                                                    <img src="post/<?= htmlspecialchars($row['gambar']) ?>" alt="Lampiran" class="w-full max-h-[520px] object-contain rounded-xl sm:rounded-2xl" onerror="this.style.display='none'">
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Thread Meta Footer -->
                                        <div class="flex items-center gap-2 sm:gap-3 pt-3 border-t border-white/10 text-xs text-white/60 flex-wrap">
                                            <a href="thread.php?id=<?= $row['id'] ?>" class="inline-flex items-center gap-1.5 hover:text-white transition-colors bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-full border border-white/10 text-[11px] sm:text-xs font-semibold">
                                                <i class="fa fa-comment text-blue-400"></i>
                                                <span><?= $row['comment_count'] ?> Komentar</span>
                                            </a>

                                            <!-- Quick Share -->
                                            <button onclick="shareThread(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['judul'])) ?>')" class="inline-flex items-center gap-1.5 hover:text-white transition-colors bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-full border border-white/10 text-[11px] sm:text-xs" title="Salin Tautan">
                                                <i class="fa fa-share-nodes text-white/60"></i>
                                                <span class="hidden sm:inline">Bagikan</span>
                                            </button>

                                            <!-- Sub-forum Creator Moderation Button -->
                                            <?php if ($can_archive_this && empty($row['is_archived'])): ?>
                                                <button onclick="archiveThread(<?= $row['id'] ?>)" class="inline-flex items-center gap-1 hover:text-amber-300 transition-colors bg-amber-500/10 hover:bg-amber-500/20 text-amber-300/80 px-2.5 py-1.5 rounded-full border border-amber-500/20 text-[11px]" title="Arsipkan postingan ini (dihapus setelah 7 hari)">
                                                    <i class="fa fa-box-archive text-[10px]"></i>
                                                    <span>Arsipkan</span>
                                                </button>
                                            <?php endif; ?>

                                            <a href="thread.php?id=<?= $row['id'] ?>" class="ml-auto text-blue-400 hover:text-blue-300 font-bold inline-flex items-center gap-1 text-[11px] sm:text-xs">
                                                <span>Baca Diskusi</span>
                                                <i class="fa fa-arrow-right text-[9px]"></i>
                                            </a>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="liquid-glass p-12 text-center rounded-2xl border">
                            <div class="w-16 h-16 rounded-full bg-white/5 border border-white/10 flex items-center justify-center mx-auto mb-4 text-white/40 text-2xl">
                                <i class="fa fa-comments"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Belum Ada Topik Diskusi</h3>
                            <p class="text-white/60 text-sm max-w-sm mx-auto mb-6">
                                <?= $view_archived ? 'Tidak ada postingan yang diarsipkan dalam kategori ini.' : 'Belum ada diskusi dalam kategori ini. Jadilah yang pertama memulai obrolan!' ?>
                            </p>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="buat_post.php<?= $forum_id ? '?forum_id=' . $forum_id : '' ?>" class="liquid-btn px-6 py-3 text-xs font-bold">
                                    Mulai Topik Pertama
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="liquid-btn px-6 py-3 text-xs font-bold">
                                    Masuk Untuk Buat Topik
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </section>

            <!-- ============================================== -->
            <!-- RIGHT SIDEBAR: LEADERBOARD & COMMUNITY         -->
            <!-- ============================================== -->
            <aside class="hidden lg:block w-72 xl:w-80 shrink-0 sticky top-24 space-y-4">
                
                <!-- LEADERBOARD WIDGET -->
                <div class="liquid-glass p-4 rounded-2xl border border-white/10 shadow-xl">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-white/10">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center text-xs">
                                <i class="fa fa-trophy"></i>
                            </div>
                            <h3 class="font-extrabold text-xs uppercase tracking-wider text-white">Leaderboard</h3>
                        </div>
                        <span class="text-[9px] px-2 py-0.5 rounded-full bg-white/5 border border-white/10 text-white/50 font-bold">Top 5</span>
                    </div>

                    <!-- Filter Tabs: Reputasi, Followers, Upvoted -->
                    <div class="flex items-center p-1 rounded-xl bg-white/5 border border-white/10 mb-3 gap-1">
                        <button onclick="switchLeaderboard('rep')" id="lb-tab-rep" class="lb-tab flex-1 py-1 text-[11px] font-bold rounded-lg transition-all bg-amber-500 text-white shadow-sm">
                            ⭐ Rep
                        </button>
                        <button onclick="switchLeaderboard('followers')" id="lb-tab-followers" class="lb-tab flex-1 py-1 text-[11px] font-bold rounded-lg transition-all text-white/60 hover:text-white">
                            👥 Follow
                        </button>
                        <button onclick="switchLeaderboard('upvotes')" id="lb-tab-upvotes" class="lb-tab flex-1 py-1 text-[11px] font-bold rounded-lg transition-all text-white/60 hover:text-white">
                            ⬆️ Upvote
                        </button>
                    </div>

                    <!-- PANEL 1: TOP REPUTASI -->
                    <div id="lb-panel-rep" class="lb-panel space-y-2">
                        <?php if (!empty($top_rep)): ?>
                            <?php foreach ($top_rep as $idx => $user): 
                                $rank = $idx + 1;
                                $rank_decor = [
                                    1 => 'bg-amber-400/20 text-amber-300 border-amber-400/40 font-extrabold',
                                    2 => 'bg-slate-300/20 text-slate-200 border-slate-300/40 font-bold',
                                    3 => 'bg-amber-700/20 text-amber-400 border-amber-700/40 font-bold',
                                ];
                                $badge_cls = $rank_decor[$rank] ?? 'bg-white/5 text-white/50 border-white/10';
                            ?>
                                <div class="flex items-center justify-between p-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/5 transition-all group">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] border <?= $badge_cls ?> shrink-0">
                                            <?= $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : $rank)) ?>
                                        </span>
                                        <div class="user-hover-trigger cursor-pointer flex items-center gap-2 min-w-0" data-user-id="<?= $user['id'] ?>">
                                            <?= render_avatar_with_frame($user['username'], $user['avatar'], $user['active_frame'], 'xs') ?>
                                            <span class="text-xs font-bold text-white truncate max-w-[100px] group-hover:text-blue-300 transition-colors">
                                                <?= htmlspecialchars($user['username']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-xs font-extrabold text-amber-300 font-mono shrink-0">
                                        ⭐ <?= number_format($user['reputation']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center py-4 text-xs text-white/40">Belum ada data.</p>
                        <?php endif; ?>
                    </div>

                    <!-- PANEL 2: TOP FOLLOWERS -->
                    <div id="lb-panel-followers" class="lb-panel space-y-2 hidden">
                        <?php if (!empty($top_followers)): ?>
                            <?php foreach ($top_followers as $idx => $user): 
                                $rank = $idx + 1;
                            ?>
                                <div class="flex items-center justify-between p-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/5 transition-all group">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] border <?= $rank <= 3 ? 'bg-blue-500/20 text-blue-300 border-blue-400/40' : 'bg-white/5 text-white/50 border-white/10' ?> shrink-0">
                                            <?= $rank ?>
                                        </span>
                                        <div class="user-hover-trigger cursor-pointer flex items-center gap-2 min-w-0" data-user-id="<?= $user['id'] ?>">
                                            <?= render_avatar_with_frame($user['username'], $user['avatar'], $user['active_frame'], 'xs') ?>
                                            <span class="text-xs font-bold text-white truncate max-w-[100px] group-hover:text-blue-300 transition-colors">
                                                <?= htmlspecialchars($user['username']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-blue-300 font-mono shrink-0">
                                        👥 <?= number_format($user['followers_count']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center py-4 text-xs text-white/40">Belum ada data.</p>
                        <?php endif; ?>
                    </div>

                    <!-- PANEL 3: TOP UPVOTED -->
                    <div id="lb-panel-upvotes" class="lb-panel space-y-2 hidden">
                        <?php if (!empty($top_upvotes)): ?>
                            <?php foreach ($top_upvotes as $idx => $user): 
                                $rank = $idx + 1;
                            ?>
                                <div class="flex items-center justify-between p-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/5 transition-all group">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] border <?= $rank <= 3 ? 'bg-indigo-500/20 text-indigo-300 border-indigo-400/40' : 'bg-white/5 text-white/50 border-white/10' ?> shrink-0">
                                            <?= $rank ?>
                                        </span>
                                        <div class="user-hover-trigger cursor-pointer flex items-center gap-2 min-w-0" data-user-id="<?= $user['id'] ?>">
                                            <?= render_avatar_with_frame($user['username'], $user['avatar'], $user['active_frame'], 'xs') ?>
                                            <span class="text-xs font-bold text-white truncate max-w-[100px] group-hover:text-blue-300 transition-colors">
                                                <?= htmlspecialchars($user['username']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-emerald-300 font-mono shrink-0">
                                        ⬆️ <?= number_format($user['total_upvotes']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center py-4 text-xs text-white/40">Belum ada data.</p>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- COMMUNITY RULES & STATS -->
                <div class="liquid-glass p-4 rounded-2xl border border-white/10 text-xs shadow-xl">
                    <h4 class="font-extrabold text-white text-xs uppercase tracking-wider mb-2 flex items-center gap-2">
                        <i class="fa fa-circle-info text-blue-400"></i>
                        <span>Tentang Medikacom</span>
                    </h4>
                    <p class="text-[11px] text-white/60 leading-relaxed mb-3">
                        Wadah diskusi, aspirasi, dan mading digital siswa SMK Medikacom. Dikelola oleh OSIS & MPK.
                    </p>

                    <div class="grid grid-cols-2 gap-2 text-center text-[10px] mb-3">
                        <div class="p-2 rounded-xl bg-white/5 border border-white/10">
                            <span class="text-white/40 block text-[9px] uppercase">Anggota</span>
                            <strong class="text-white text-xs font-bold"><?= number_format($total_members) ?></strong>
                        </div>
                        <div class="p-2 rounded-xl bg-white/5 border border-white/10">
                            <span class="text-white/40 block text-[9px] uppercase">Sub-Forum</span>
                            <strong class="text-white text-xs font-bold"><?= count($forum_list) ?></strong>
                        </div>
                    </div>

                    <div class="pt-2.5 border-t border-white/10">
                        <span class="text-[10px] font-bold text-white/70 block mb-1">Tata Tertib Singkat:</span>
                        <ul class="text-[11px] text-white/50 space-y-1 list-decimal list-inside leading-relaxed">
                            <li>Jaga sopan santun & anti cyberbullying.</li>
                            <li>Gunakan tag sub-forum yang relevan.</li>
                            <li>Postingan arsip auto-hapus setelah 7 hari.</li>
                        </ul>
                    </div>
                </div>

            </aside>

        </div>

    </main>

    <!-- ============================================================== -->
    <!-- DISCORD-STYLE USER HOVER & CLICK CARD (POPOVER)                 -->
    <!-- ============================================================== -->
    <div id="user-hover-card" class="fixed z-50 hidden bg-[#111215]/95 backdrop-blur-2xl p-0 rounded-3xl border border-white/15 shadow-2xl shadow-black/80 w-80 pointer-events-auto opacity-0 overflow-hidden text-white"></div>

    <!-- ============================================================== -->
    <!-- MODAL: BUAT SUB-FORUM (Cost 1200 Rep)                          -->
    <!-- ============================================================== -->
    <div id="create-subforum-modal" class="fixed inset-0 z-50 hidden bg-black/75 backdrop-blur-md flex items-center justify-center p-4">
        <div class="liquid-glass w-full max-w-md p-5 sm:p-6 rounded-3xl border border-white/20 shadow-2xl relative">
            <button onclick="closeCreateSubforumModal()" class="absolute top-4 right-4 text-white/60 hover:text-white w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-sm">✕</button>
            
            <div class="flex items-center gap-2.5 mb-4">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white text-base shadow-lg">
                    <i class="fa fa-folder-plus"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-white">Buat Sub-Forum Baru</h3>
                    <p class="text-xs text-white/60">Biaya pembuatan: <span class="text-amber-300 font-extrabold">⭐ 1.200 Reputasi</span></p>
                </div>
            </div>

            <!-- User Balance Status Alert -->
            <?php 
                $user_rep = $logged_user['reputation'] ?? 0;
                $can_afford = ($user_rep >= 1200 || $is_admin);
            ?>
            <div class="mb-4 p-3 rounded-2xl border text-xs flex items-center justify-between <?= $can_afford ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-200' : 'bg-red-500/15 border-red-500/30 text-red-200' ?>">
                <span>Reputasi Anda: <strong>⭐ <?= number_format($user_rep) ?></strong></span>
                <?php if ($can_afford): ?>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300">Cukup</span>
                <?php else: ?>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-500/20 text-red-300">Kurang <?= 1200 - $user_rep ?></span>
                <?php endif; ?>
            </div>

            <form id="form-create-subforum" onsubmit="handleCreateSubforum(event)" class="space-y-3.5 text-xs">
                <div>
                    <label class="block text-white/80 font-bold mb-1">Nama Sub-Forum</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-blue-400 font-bold">sf/</span>
                        <input type="text" name="nama" id="sf-nama-input" placeholder="contoh: programming, mading, futsal" required class="liquid-input w-full pl-9 pr-3 py-2 text-xs rounded-xl" oninput="updateSlugPreview(this.value)">
                    </div>
                    <span id="sf-slug-preview" class="text-[10px] text-white/50 mt-1 block">Tautan nanti: sf/</span>
                </div>

                <div>
                    <label class="block text-white/80 font-bold mb-1">Deskripsi Singkat</label>
                    <textarea name="description" placeholder="Ceritakan komunitas ini membahas topik apa..." rows="2" class="liquid-input w-full px-3 py-2 text-xs rounded-xl"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-white/80 font-bold mb-1">Icon FontAwesome</label>
                        <select name="icon" class="liquid-input w-full px-2.5 py-2 text-xs rounded-xl bg-[#090d1f] text-white">
                            <option value="fa-comments">💬 Diskusi (fa-comments)</option>
                            <option value="fa-gamepad">🎮 Gaming (fa-gamepad)</option>
                            <option value="fa-code">💻 Coding (fa-code)</option>
                            <option value="fa-book-open">📚 Akademik (fa-book-open)</option>
                            <option value="fa-futbol">⚽ Olahraga (fa-futbol)</option>
                            <option value="fa-palette">🎨 Seni (fa-palette)</option>
                            <option value="fa-camera">📸 Fotografi (fa-camera)</option>
                            <option value="fa-music">🎵 Musik (fa-music)</option>
                            <option value="fa-mug-hot">☕ Santai (fa-mug-hot)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-white/80 font-bold mb-1">Warna Banner</label>
                        <select name="banner_color" class="liquid-input w-full px-2.5 py-2 text-xs rounded-xl bg-[#090d1f] text-white">
                            <option value="from-blue-600 to-indigo-600">Biru Medika</option>
                            <option value="from-purple-600 to-pink-600">Ungu Neon</option>
                            <option value="from-emerald-600 to-teal-600">Hijau Zamrud</option>
                            <option value="from-amber-600 to-orange-600">Emas Senja</option>
                            <option value="from-rose-600 to-red-600">Merah Membara</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-white/80 font-bold mb-1">Gambar Banner Kustom (Opsional)</label>
                    <input type="file" name="banner_file" accept="image/jpeg,image/png,image/webp" class="text-xs text-white/70 file:mr-2 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-white/10 file:text-white hover:file:bg-white/20">
                </div>

                <div class="pt-2">
                    <?php if ($can_afford): ?>
                        <button type="submit" id="btn-submit-subforum" class="liquid-btn w-full py-2.5 text-xs font-bold flex items-center justify-center gap-1.5 shadow-lg shadow-blue-900/40">
                            <i class="fa fa-check"></i>
                            <span>Buat Sub-Forum & Bayar 1.200 ⭐</span>
                        </button>
                    <?php else: ?>
                        <button type="button" disabled class="w-full py-2.5 rounded-xl bg-white/5 border border-white/10 text-white/40 font-bold text-xs cursor-not-allowed">
                            Reputasi Tidak Mencukupi (Butuh 1.200 ⭐)
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: REQUEST BAN AKUN (Verifikasi Admin/Dev)                   -->
    <!-- ============================================================== -->
    <div id="request-ban-modal" class="fixed inset-0 z-50 hidden bg-black/75 backdrop-blur-md flex items-center justify-center p-4">
        <div class="liquid-glass w-full max-w-sm p-5 rounded-3xl border border-white/20 shadow-2xl relative">
            <button onclick="closeRequestBanModal()" class="absolute top-4 right-4 text-white/60 hover:text-white w-8 h-8 rounded-full bg-white/5 flex items-center justify-center text-sm">✕</button>
            
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 rounded-xl bg-red-500/20 text-red-400 flex items-center justify-center text-sm">
                    <i class="fa fa-user-slash"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Ajukan Ban Siswa</h3>
                    <p id="ban-modal-forum-label" class="text-[11px] text-white/50">Sub-Forum: sf/...</p>
                </div>
            </div>

            <p class="text-[11px] text-white/60 mb-3 leading-relaxed">
                Pengajuan ban akan masuk ke daftar verifikasi Admin & Dev OSIS. Berikan alasan dan bukti yang objektif.
            </p>

            <form id="form-request-ban" onsubmit="handleRequestBan(event)" class="space-y-3 text-xs">
                <input type="hidden" name="forum_id" id="ban-forum-id-val">
                
                <div>
                    <label class="block text-white/80 font-bold mb-1">Target Pengguna</label>
                    <select name="target_user_id" id="ban-target-user-select" required class="liquid-input w-full px-3 py-2 text-xs rounded-xl bg-[#090d1f] text-white">
                        <option value="">Pilih Siswa...</option>
                        <?php 
                        $all_u = $conn->query("SELECT id, username FROM users WHERE status = 'active' ORDER BY username ASC");
                        if ($all_u) {
                            while ($u = $all_u->fetch_assoc()) {
                                if (isset($_SESSION['user_id']) && $u['id'] == $_SESSION['user_id']) continue;
                                echo '<option value="' . $u['id'] . '">' . htmlspecialchars($u['username']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label class="block text-white/80 font-bold mb-1">Alasan Pelanggaran</label>
                    <textarea name="reason" rows="3" placeholder="Sebutkan pelanggaran (cth: spam, toxic, SARA, provokasi)..." required class="liquid-input w-full px-3 py-2 text-xs rounded-xl"></textarea>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-lg shadow-red-900/30 transition-all">
                    Kirim Pengajuan ke Admin/Dev
                </button>
            </form>
        </div>
    </div>

    <!-- Script: AJAX Voting, Discord Popover, Leaderboard & Sub-Forum -->
    <script>
        // ==========================================
        // 1. LEADERBOARD TAB SWITCHER
        // ==========================================
        function switchLeaderboard(type) {
            document.querySelectorAll('.lb-panel').forEach(p => p.classList.add('hidden'));
            document.querySelectorAll('.lb-tab').forEach(t => {
                t.classList.remove('bg-amber-500', 'text-white', 'shadow-sm');
                t.classList.add('text-white/60');
            });

            const panel = document.getElementById(`lb-panel-${type}`);
            const tab = document.getElementById(`lb-tab-${type}`);
            if (panel && tab) {
                panel.classList.remove('hidden');
                tab.classList.add('bg-amber-500', 'text-white', 'shadow-sm');
                tab.classList.remove('text-white/60');
            }
        }

        // ==========================================
        // 2. DISCORD-STYLE HOVER & CLICK POPOVER
        // ==========================================
        const hoverCard = document.getElementById('user-hover-card');
        let hoverTimeout = null;
        let cardCache = {};
        let activeTrigger = null;

        function positionHoverCard(el) {
            const rect = el.getBoundingClientRect();
            const cardWidth = 320;
            const cardHeight = 360;

            let top = rect.bottom + 10;
            let left = rect.left - 20;

            // Flip above if reaching bottom of viewport
            if (top + cardHeight > window.innerHeight) {
                top = Math.max(12, rect.top - cardHeight - 10);
            }

            // Bounded inside viewport horizontally
            if (left + cardWidth > window.innerWidth - 16) {
                left = window.innerWidth - cardWidth - 16;
            }
            if (left < 16) left = 16;

            hoverCard.style.top = `${top}px`;
            hoverCard.style.left = `${left}px`;
        }

        async function showUserCard(el, userId) {
            activeTrigger = el;
            positionHoverCard(el);
            hoverCard.classList.remove('hidden');

            if (cardCache[userId]) {
                renderDiscordCard(cardCache[userId]);
                requestAnimationFrame(() => hoverCard.classList.add('active'));
            } else {
                hoverCard.innerHTML = `
                    <div class="h-20 bg-gradient-to-r from-blue-700/60 to-indigo-800/60 animate-pulse"></div>
                    <div class="p-4 pt-10 text-center text-xs text-white/50">
                        <i class="fa fa-spinner fa-spin mr-1"></i> Memuat profil...
                    </div>
                `;
                requestAnimationFrame(() => hoverCard.classList.add('active'));
                try {
                    const res = await fetch(`get_user_card.php?id=${userId}`);
                    const data = await res.json();
                    if (data.success) {
                        cardCache[userId] = data.user;
                        renderDiscordCard(data.user);
                    } else {
                        hoverCard.innerHTML = `<div class="p-4 text-xs text-red-300 text-center">${data.error || 'Profil tidak ditemukan.'}</div>`;
                    }
                } catch (err) {
                    hoverCard.innerHTML = '<div class="p-4 text-xs text-red-300 text-center">Gagal memuat profil.</div>';
                }
            }
        }

        function hideHoverCard() {
            clearTimeout(hoverTimeout);
            hoverCard.classList.remove('active');
            setTimeout(() => {
                if (!hoverCard.classList.contains('active')) {
                    hoverCard.classList.add('hidden');
                }
            }, 180);
            activeTrigger = null;
        }

        // Render Discord-Style Profile Card (Deep dark solid style matching Discord Nitro)
        function renderDiscordCard(user) {
            // Theme banner gradient based on role
            let bannerGradients = {
                'admin': 'from-[#2b0c47] via-[#591b8a] to-[#210938]',
                'dev': 'from-[#191040] via-[#3d1c82] to-[#1c1240]',
                'osis': 'from-[#0b2246] via-[#1d4ed8] to-[#171c42]',
                'guru': 'from-[#064234] via-[#047857] to-[#042a22]',
                'murid': 'from-[#1a1b2e] via-[#2d2f4d] to-[#141524]'
            };
            let bgGrad = bannerGradients[user.role?.toLowerCase()] || bannerGradients['murid'];

            // Avatar markup
            let avatarImg = (user.avatar && user.avatar !== 'default-avatar.jpg')
                ? `<img src="uploads/${user.avatar}" class="w-full h-full object-cover">`
                : `<span class="text-2xl font-black text-white">${user.username.charAt(0).toUpperCase()}</span>`;

            // Frame overlay
            let frameImg = user.active_frame
                ? `<img src="assets/frames/${user.active_frame}" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[128%] h-[128%] max-w-none pointer-events-none z-10 select-none" style="object-fit: contain;">`
                : '';

            // Role Badge (in top right of banner)
            let roleBadge = '';
            if (user.role && user.role !== 'murid') {
                roleBadge = `<span class="text-[10px] px-3 py-1 rounded-full font-extrabold uppercase tracking-wider bg-black/40 text-blue-300 border border-blue-400/40 backdrop-blur-md shadow-sm">${user.role}</span>`;
            }

            // Active Perk
            let perkBadge = '';
            if (user.active_perk) {
                perkBadge = `<span class="text-[11px] px-2.5 py-0.5 rounded-full font-bold border ${user.active_perk.color} inline-flex items-center gap-1 shadow-sm">${user.active_perk.label}</span>`;
            }

            // Badges list (Circle icons like in image 2)
            let badgesHtml = '';
            if (user.badges && user.badges.length > 0) {
                badgesHtml = `
                    <div class="pt-3 border-t border-white/10 flex items-center gap-2 flex-wrap mb-3.5">
                        ${user.badges.map(b => `
                            <div class="w-8 h-8 rounded-full bg-white/5 border border-white/10 flex items-center justify-center p-1.5 shadow-sm hover:scale-110 transition-transform cursor-pointer" title="${b.name}">
                                <img src="assets/badges/${b.icon_url}" class="w-full h-full object-contain">
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            hoverCard.innerHTML = `
                <!-- Discord Banner Header -->
                <div class="h-24 bg-gradient-to-r ${bgGrad} relative p-3 flex justify-end items-start">
                    <div class="absolute inset-0 bg-black/15"></div>
                    <div class="relative z-10">
                        ${roleBadge}
                    </div>
                </div>

                <!-- Lower Card Container (Deep dark / a bit transparent) -->
                <div class="bg-[#121316] px-4 pb-4 pt-0 relative">
                    
                    <!-- Avatar with Thick Border overlapping Banner -->
                    <div class="relative -mt-12 mb-2 inline-block">
                        <div class="w-20 h-20 rounded-full overflow-hidden bg-gradient-to-tr from-blue-700 to-indigo-900 border-[5px] border-[#121316] flex items-center justify-center text-white shadow-2xl">
                            ${avatarImg}
                        </div>
                        ${frameImg}
                    </div>

                    <!-- User Name & Handle -->
                    <div class="mb-3">
                        <h4 class="font-black text-white text-lg leading-tight tracking-tight">${user.username}</h4>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <span class="text-xs text-white/50 font-mono">@${user.username.toLowerCase()}</span>
                            ${perkBadge}
                        </div>
                    </div>

                    <!-- Stats Box (Dark Discord Style) -->
                    <div class="grid grid-cols-3 gap-2 p-3 rounded-2xl bg-[#1a1b20] border border-white/5 text-center mb-3.5 shadow-inner">
                        <div>
                            <span class="text-[9px] text-white/40 uppercase block font-extrabold tracking-wider mb-0.5">Reputasi</span>
                            <span class="text-xs font-black text-amber-300 flex items-center justify-center gap-1">⭐ ${user.reputation_formatted}</span>
                        </div>
                        <div>
                            <span class="text-[9px] text-white/40 uppercase block font-extrabold tracking-wider mb-0.5">Followers</span>
                            <span class="text-xs font-black text-white">${user.followers_count}</span>
                        </div>
                        <div>
                            <span class="text-[9px] text-white/40 uppercase block font-extrabold tracking-wider mb-0.5">Upvotes</span>
                            <span class="text-xs font-black text-blue-300 flex items-center justify-center gap-0.5">
                                <span class="px-1 py-0.2 rounded bg-blue-500/20 text-[10px] font-bold">⬆️</span> ${user.total_upvotes || 0}
                            </span>
                        </div>
                    </div>

                    ${badgesHtml}

                    <!-- CTA: Kunjungi Profil -->
                    <div>
                        <a href="profile.php?id=${user.id}" class="w-full py-2.5 rounded-2xl bg-[#5865F2] hover:bg-[#4752C4] text-white font-extrabold text-xs text-center transition-all shadow-lg shadow-indigo-900/40 flex items-center justify-center gap-2">
                            <i class="fa fa-user text-xs"></i>
                            <span>Kunjungi Profil</span>
                        </a>
                    </div>
                </div>
            `;
        }

        // Attach Trigger Events (Hover + Click)
        function initUserHoverTriggers() {
            document.querySelectorAll('.user-hover-trigger').forEach(el => {
                const userId = el.getAttribute('data-user-id');
                if (!userId) return;

                // 1. Mouse Enter (Desktop Hover)
                el.addEventListener('mouseenter', () => {
                    clearTimeout(hoverTimeout);
                    hoverTimeout = setTimeout(() => {
                        showUserCard(el, userId);
                    }, 150);
                });

                // 2. Mouse Leave (Desktop Exit)
                el.addEventListener('mouseleave', () => {
                    clearTimeout(hoverTimeout);
                    hoverTimeout = setTimeout(() => {
                        if (!hoverCard.matches(':hover')) {
                            hideHoverCard();
                        }
                    }, 160);
                });

                // 3. Click / Tap (Mobile & Desktop Click Friendly)
                el.addEventListener('click', (e) => {
                    // If target clicked is an <a> tag navigating to profile, let them see card first if on mobile
                    if (window.innerWidth < 1024) {
                        e.preventDefault();
                    }
                    clearTimeout(hoverTimeout);
                    if (hoverCard.classList.contains('active') && activeTrigger === el) {
                        hideHoverCard();
                    } else {
                        showUserCard(el, userId);
                    }
                });
            });
        }

        // Card Hover Retention
        hoverCard.addEventListener('mouseenter', () => {
            clearTimeout(hoverTimeout);
        });
        hoverCard.addEventListener('mouseleave', () => {
            hideHoverCard();
        });

        // AUTO-CLOSE ON SCROLL (Solusi anti-nyangkut)
        window.addEventListener('scroll', () => {
            if (hoverCard.classList.contains('active')) {
                hideHoverCard();
            }
        }, { passive: true });

        // CLOSE ON OUTSIDE CLICK
        document.addEventListener('click', (e) => {
            if (!hoverCard.contains(e.target) && !e.target.closest('.user-hover-trigger')) {
                hideHoverCard();
            }
        });

        // CLOSE ON ESCAPE
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') hideHoverCard();
        });

        // Initialize triggers on page load
        initUserHoverTriggers();

        // ==========================================
        // 3. SUB-FORUM CREATION (1200 REP)
        // ==========================================
        function openCreateSubforumModal() {
            document.getElementById('create-subforum-modal').classList.remove('hidden');
        }
        function closeCreateSubforumModal() {
            document.getElementById('create-subforum-modal').classList.add('hidden');
        }
        function updateSlugPreview(val) {
            const clean = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
            document.getElementById('sf-slug-preview').textContent = `Tautan nanti: sf/${clean || 'nama-komunitas'}`;
        }

        async function handleCreateSubforum(e) {
            e.preventDefault();
            const form = document.getElementById('form-create-subforum');
            const submitBtn = document.getElementById('btn-submit-subforum');
            const fd = new FormData(form);
            fd.append('action', 'create_subforum');

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Memproses...';
            }

            try {
                const res = await fetch('subforum_action.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    window.location.href = `index.php?forum_id=${data.forum_id}`;
                } else {
                    alert(data.error || 'Gagal membuat Sub-Forum.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa fa-check mr-1"></i> Buat Sub-Forum & Bayar 1.200 ⭐';
                    }
                }
            } catch (err) {
                alert('Terjadi kesalahan koneksi.');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa fa-check mr-1"></i> Buat Sub-Forum & Bayar 1.200 ⭐';
                }
            }
        }

        // ==========================================
        // 4. ARCHIVE THREAD (7 Days Auto-Delete)
        // ==========================================
        async function archiveThread(threadId) {
            if (!confirm('Arsipkan postingan ini? Postingan akan disembunyikan dari feed dan dihapus permanen otomatis setelah 1 minggu (7 hari).')) {
                return;
            }

            const fd = new FormData();
            fd.append('action', 'archive_thread');
            fd.append('thread_id', threadId);

            try {
                const res = await fetch('subforum_action.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.error || 'Gagal mengarsipkan.');
                }
            } catch (e) {
                alert('Gagal menghubungi server.');
            }
        }

        async function unarchiveThread(threadId) {
            const fd = new FormData();
            fd.append('action', 'unarchive_thread');
            fd.append('thread_id', threadId);

            try {
                const res = await fetch('subforum_action.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.error || 'Gagal memulihkan topik.');
                }
            } catch (e) {
                alert('Gagal menghubungi server.');
            }
        }

        // ==========================================
        // 5. REQUEST BAN AKUN (Admin/Dev Verification)
        // ==========================================
        function openRequestBanModal(forumId, forumSlug) {
            document.getElementById('ban-forum-id-val').value = forumId;
            document.getElementById('ban-modal-forum-label').textContent = `Sub-Forum: ${forumSlug}`;
            document.getElementById('request-ban-modal').classList.remove('hidden');
        }
        function closeRequestBanModal() {
            document.getElementById('request-ban-modal').classList.add('hidden');
        }

        async function handleRequestBan(e) {
            e.preventDefault();
            const form = document.getElementById('form-request-ban');
            const fd = new FormData(form);
            fd.append('action', 'request_ban');

            try {
                const res = await fetch('subforum_action.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    closeRequestBanModal();
                } else {
                    alert(data.error || 'Gagal mengajukan ban.');
                }
            } catch (err) {
                alert('Kesalahan jaringan.');
            }
        }

        // ==========================================
        // 6. VOTING & SHARING
        // ==========================================
        async function handleVote(threadId, voteType, btnEl) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Silakan login terlebih dahulu untuk melakukan vote.');
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            const fd = new FormData();
            fd.append('thread_id', threadId);
            fd.append('vote_type', voteType);

            try {
                const res = await fetch('vote.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    const card = btnEl.closest('.thread-card');
                    const countEl = card.querySelector('.vote-count');
                    const upBtn = card.querySelector('.vote-btn.upvote');
                    const downBtn = card.querySelector('.vote-btn.downvote');

                    countEl.textContent = data.vote_count;
                    upBtn.classList.remove('upvoted');
                    downBtn.classList.remove('downvoted');

                    if (data.user_vote === 1) upBtn.classList.add('upvoted');
                    if (data.user_vote === -1) downBtn.classList.add('downvoted');
                } else {
                    alert(data.error || 'Gagal memproses vote.');
                }
            } catch (e) {
                alert('Terjadi kesalahan koneksi.');
            }
        }

        function shareThread(id, title) {
            const url = window.location.origin + window.location.pathname.replace('index.php', '') + 'thread.php?id=' + id;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Tautan topik telah disalin ke clipboard!');
                });
            } else {
                prompt('Salin tautan topik berikut:', url);
            }
        }

        // Notification toggle
        const notifBtn = document.getElementById('notification-toggle');
        const notifDropdown = document.getElementById('notification-dropdown');
        if (notifBtn && notifDropdown) {
            notifBtn.addEventListener('click', () => notifDropdown.classList.toggle('hidden'));
        }

        // Mobile menu toggle
        const mobileBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileBtn && mobileMenu) {
            mobileBtn.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
        }
    </script>
</body>
</html>
