<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/forum_helpers.php';

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

// Base SQL
$sql = "
    SELECT t.*, u.username, u.role, u.avatar, u.active_frame, u.reputation,
           p.badge_label as perk_label, p.color_class as perk_color, p.description as perk_desc,
           f.nama AS forum_name, f.slug AS forum_slug, f.icon AS forum_icon,
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
    SELECT f.*, (SELECT COUNT(*) FROM threads t WHERE t.forum_id = f.id) AS thread_count 
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

    <!-- Animate On Scroll (AOS) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

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
    </style>
</head>
<body class="selection:bg-blue-500/30 selection:text-white">

    <!-- Blurred Background Elements -->
    <div class="main-background"></div>
    <div class="main-overlay"></div>
    
    <!-- Compact Header & Navbar -->
    <header id="navbar" class="fixed top-0 left-0 right-0 z-50 pt-2 sm:pt-3 px-3 sm:px-6">
        <nav class="max-w-5xl mx-auto">
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
                        <a href="buat_post.php" class="liquid-btn px-3 py-1.5 text-[11px] sm:text-xs font-bold flex items-center gap-1.5" title="Buat Topik">
                            <i class="fa fa-pen text-[10px]"></i>
                            <span class="hidden sm:inline">Buat Topik</span>
                        </a>

                        <!-- User Profile Link (Compact & well-fitted frame) -->
                        <a href="profile.php" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 text-white transition-all" title="Profil Saya">
                            <?= render_avatar_with_frame($logged_user['username'], $logged_user['avatar'], $logged_user['active_frame'], 'xs') ?>
                            <span class="text-xs font-bold hidden md:inline max-w-[90px] truncate"><?= htmlspecialchars($logged_user['username']) ?></span>
                        </a>

                        <!-- Admin Panel Shortcut (Icon only for compactness) -->
                        <?php if ($is_admin): ?>
                            <a href="dev.php" class="p-1.5 rounded-full bg-purple-600/30 hover:bg-purple-600/50 border border-purple-500/40 text-purple-300 transition-all" title="Admin Panel">
                                <i class="fa fa-shield-halved text-xs"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Logout (Icon) -->
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
                        <a href="buat_post.php" class="liquid-btn py-2.5 font-bold mt-2">Buat Topik Baru</a>
                        <a href="logout.php" class="py-2 text-red-400 hover:text-red-300 font-semibold text-xs">Keluar</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content Container: Clean & Centered Feed (No clunky right sidebar) -->
    <main class="container mx-auto px-4 max-w-4xl pt-24 sm:pt-28 pb-24">

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
            <div id="announcement-banner" class="mb-6 p-4 rounded-2xl border backdrop-blur-xl flex items-start justify-between gap-4 <?= $ann_bg ?> shadow-lg shadow-black/20" data-aos="fade-down">
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

        <!-- Subreddit Bar (Category Pill Navigation) -->
        <div class="liquid-glass p-2.5 sm:p-3 mb-4 rounded-2xl border" data-aos="fade-up">
            <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar">
                <a href="index.php<?= $sort !== 'hot' ? '?sort=' . $sort : '' ?>" class="whitespace-nowrap shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition-all <?= !$forum_id ? 'bg-blue-600 text-white shadow-md' : 'bg-white/5 hover:bg-white/15 text-white/80 border border-white/10' ?>">
                    <i class="fa fa-globe mr-1 text-[11px]"></i> r/all
                </a>
                <?php foreach ($forum_list as $id => $finfo): 
                    $sub_slug = htmlspecialchars($finfo['slug'] ?: strtolower(str_replace(' ', '-', $finfo['nama'])));
                ?>
                    <a href="index.php?forum_id=<?= $id ?><?= $sort !== 'hot' ? '&sort=' . $sort : '' ?>" class="whitespace-nowrap shrink-0 px-3.5 py-1.5 rounded-full text-xs font-bold transition-all <?= $forum_id == $id ? 'bg-blue-600 text-white shadow-md' : 'bg-white/5 hover:bg-white/15 text-white/80 border border-white/10' ?> flex items-center gap-1.5">
                        <i class="fa <?= htmlspecialchars($finfo['icon'] ?? 'fa-comments') ?> text-[10px] opacity-75"></i>
                        <span>r/<?= $sub_slug ?></span>
                        <span class="text-[10px] px-1.5 rounded-full bg-white/10 font-mono"><?= $finfo['thread_count'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sub-forum Info Banner (If specific sub-forum selected) -->
        <?php if ($current_subforum): ?>
            <div class="mb-4 p-3.5 sm:p-4 rounded-2xl liquid-glass border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3" data-aos="fade-up">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-500/20 border border-blue-400/30 flex items-center justify-center text-blue-300 shrink-0 text-sm">
                        <i class="fa <?= htmlspecialchars($current_subforum['icon'] ?? 'fa-comments') ?>"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-extrabold text-white text-sm">r/<?= htmlspecialchars($current_subforum['slug']) ?></h3>
                            <span class="text-[10px] px-2 py-0.2 rounded-full bg-white/10 text-white/70"><?= $current_subforum['thread_count'] ?> postingan</span>
                        </div>
                        <p class="text-xs text-white/60 mt-0.5"><?= htmlspecialchars($current_subforum['description'] ?: 'Ruang diskusi dan aspirasi siswa.') ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto">
                    <a href="buat_post.php?forum_id=<?= $current_subforum['id'] ?>" class="liquid-btn py-1.5 px-4 text-xs font-bold w-full sm:w-auto text-center">
                        <i class="fa fa-plus text-[10px] mr-1"></i> Buat Topik
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Reddit Sorting Bar & Search (Compact) -->
        <div class="liquid-glass p-3 sm:p-3.5 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-2.5 border mb-5">
            
            <!-- Sorting Tabs: Hot, New, Top, Controversial -->
            <div class="flex items-center gap-1.5 w-full md:w-auto overflow-x-auto pb-1 no-scrollbar">
                <a href="index.php?sort=hot<?= $forum_id ? '&forum_id=' . $forum_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="whitespace-nowrap shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 <?= $sort === 'hot' ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-md' : 'text-white/60 hover:text-white bg-white/5' ?>">
                    <i class="fa fa-fire text-amber-300 text-[11px]"></i>
                    <span>Hot</span>
                </a>
                <a href="index.php?sort=new<?= $forum_id ? '&forum_id=' . $forum_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="whitespace-nowrap shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 <?= ($sort === 'new' || $sort === 'latest') ? 'bg-blue-600 text-white shadow-md' : 'text-white/60 hover:text-white bg-white/5' ?>">
                    <i class="fa fa-clock text-blue-300 text-[11px]"></i>
                    <span>New</span>
                </a>
                <a href="index.php?sort=top<?= $forum_id ? '&forum_id=' . $forum_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="whitespace-nowrap shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 <?= $sort === 'top' ? 'bg-purple-600 text-white shadow-md' : 'text-white/60 hover:text-white bg-white/5' ?>">
                    <i class="fa fa-trophy text-yellow-300 text-[11px]"></i>
                    <span>Top</span>
                </a>
                <a href="index.php?sort=controversial<?= $forum_id ? '&forum_id=' . $forum_id : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="whitespace-nowrap shrink-0 px-3 py-1.5 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 <?= $sort === 'controversial' ? 'bg-red-600 text-white shadow-md' : 'text-white/60 hover:text-white bg-white/5' ?>">
                    <i class="fa fa-bolt text-red-300 text-[11px]"></i>
                    <span>Controversial</span>
                </a>
            </div>

            <!-- Search Input Form -->
            <form method="GET" action="index.php" class="w-full md:w-64">
                <?php if ($forum_id): ?>
                    <input type="hidden" name="forum_id" value="<?= $forum_id ?>">
                <?php endif; ?>
                <?php if ($sort !== 'hot'): ?>
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
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

        <!-- Threads Feed (Spacious & Focused) -->
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
                ?>
                    <div class="liquid-glass liquid-glass-interactive p-4 sm:p-5 thread-card relative overflow-hidden rounded-2xl border" data-thread-id="<?= $row['id'] ?>">
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
                                
                                <!-- Meta Header: Avatar + Frame + r/slug + Author + Perks -->
                                <div class="flex flex-wrap items-center gap-2 mb-2 text-xs">
                                    
                                    <!-- Author Avatar with Frame -->
                                    <?= render_avatar_with_frame($row['username'], $row['avatar'], $row['active_frame'], 'xs') ?>

                                    <!-- Subreddit r/topik Pill -->
                                    <a href="index.php?forum_id=<?= $row['forum_id'] ?>" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wider bg-blue-500/20 hover:bg-blue-500/30 border border-blue-400/40 text-blue-300 transition-colors">
                                        r/<?= $sub_slug ?>
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
                                    <div class="mb-4 rounded-xl sm:rounded-2xl overflow-hidden max-h-[400px] border border-white/10 bg-black/40 flex items-center justify-center">
                                        <?php if ($row['media_type'] === 'video'): ?>
                                            <video src="post/<?= htmlspecialchars($row['gambar']) ?>" controls class="w-full max-h-[400px] object-contain rounded-xl sm:rounded-2xl"></video>
                                        <?php else: ?>
                                            <img src="post/<?= htmlspecialchars($row['gambar']) ?>" alt="Lampiran" class="w-full max-h-[400px] object-contain rounded-xl sm:rounded-2xl" onerror="this.style.display='none'">
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Thread Meta Footer -->
                                <div class="flex items-center gap-2 sm:gap-3 pt-3 border-t border-white/10 text-xs text-white/60">
                                    <a href="thread.php?id=<?= $row['id'] ?>" class="inline-flex items-center gap-1.5 hover:text-white transition-colors bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-full border border-white/10 text-[11px] sm:text-xs font-semibold">
                                        <i class="fa fa-comment text-blue-400"></i>
                                        <span><?= $row['comment_count'] ?> Komentar</span>
                                    </a>

                                    <!-- Quick Share -->
                                    <button onclick="shareThread(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['judul'])) ?>')" class="inline-flex items-center gap-1.5 hover:text-white transition-colors bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded-full border border-white/10 text-[11px] sm:text-xs" title="Salin Tautan">
                                        <i class="fa fa-share-nodes text-white/60"></i>
                                        <span class="hidden sm:inline">Bagikan</span>
                                    </button>

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
                <div class="liquid-glass p-12 text-center rounded-2xl border" data-aos="zoom-in">
                    <div class="w-16 h-16 rounded-full bg-white/5 border border-white/10 flex items-center justify-center mx-auto mb-4 text-white/40 text-2xl">
                        <i class="fa fa-comments"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Belum Ada Topik Diskusi</h3>
                    <p class="text-white/60 text-sm max-w-sm mx-auto mb-6">
                        Belum ada diskusi dalam kategori ini. Jadilah yang pertama memulai obrolan!
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

    </main>

    <!-- USER HOVER CARD (Floating Popover) -->
    <div id="user-hover-card" class="fixed z-50 hidden liquid-glass p-4 rounded-2xl border border-white/20 shadow-2xl w-72 pointer-events-auto backdrop-blur-2xl transition-opacity duration-200 opacity-0"></div>

    <!-- Script: AJAX Voting, Popover & Share -->
    <script>
        // Vote handling
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

        // Share Thread
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

        // Reddit-Style User Hover Popover
        const hoverCard = document.getElementById('user-hover-card');
        let hoverTimeout = null;
        let cardCache = {};

        document.querySelectorAll('.user-hover-trigger').forEach(el => {
            el.addEventListener('mouseenter', (e) => {
                const userId = el.getAttribute('data-user-id');
                if (!userId) return;

                clearTimeout(hoverTimeout);
                hoverTimeout = setTimeout(async () => {
                    const rect = el.getBoundingClientRect();
                    hoverCard.style.top = `${Math.min(window.innerHeight - 260, rect.bottom + 8)}px`;
                    hoverCard.style.left = `${Math.min(window.innerWidth - 310, Math.max(16, rect.left - 20))}px`;
                    hoverCard.classList.remove('hidden');

                    if (cardCache[userId]) {
                        renderHoverCard(cardCache[userId]);
                    } else {
                        hoverCard.innerHTML = '<div class="py-4 text-center text-xs text-white/50"><i class="fa fa-spinner fa-spin mr-1"></i> Memuat...</div>';
                        hoverCard.classList.remove('opacity-0');
                        try {
                            const res = await fetch(`get_user_card.php?id=${userId}`);
                            const data = await res.json();
                            if (data.success) {
                                cardCache[userId] = data.user;
                                renderHoverCard(data.user);
                            }
                        } catch (err) {
                            hoverCard.innerHTML = '<div class="text-xs text-red-300">Gagal memuat profil.</div>';
                        }
                    }
                }, 250);
            });

            el.addEventListener('mouseleave', () => {
                clearTimeout(hoverTimeout);
                hoverTimeout = setTimeout(() => {
                    if (!hoverCard.matches(':hover')) {
                        hoverCard.classList.add('opacity-0');
                        setTimeout(() => hoverCard.classList.add('hidden'), 150);
                    }
                }, 150);
            });
        });

        hoverCard.addEventListener('mouseleave', () => {
            hoverCard.classList.add('opacity-0');
            setTimeout(() => hoverCard.classList.add('hidden'), 150);
        });

        function renderHoverCard(user) {
            let perksHtml = '';
            if (user.showcase_perks && user.showcase_perks.length > 0) {
                perksHtml = `<div class="mt-2.5 pt-2.5 border-t border-white/10">
                    <span class="text-[9px] text-white/50 uppercase font-bold block mb-1">Etalase:</span>
                    <div class="flex flex-wrap gap-1">
                        ${user.showcase_perks.map(p => `<span class="text-[9px] px-2 py-0.2 rounded-full font-bold border ${p.color_class}">${p.badge_label}</span>`).join('')}
                    </div>
                </div>`;
            }

            let badgesHtml = '';
            if (user.badges && user.badges.length > 0) {
                badgesHtml = `<div class="mt-2 pt-2 border-t border-white/10 flex items-center gap-1.5">
                    ${user.badges.map(b => `<img src="assets/badges/${b.icon_url}" class="w-5 h-5 object-contain" title="${b.name}">`).join('')}
                </div>`;
            }

            hoverCard.innerHTML = `
                <div class="flex items-start gap-2.5">
                    <div class="relative inline-flex items-center justify-center w-10 h-10 shrink-0">
                        <div class="w-full h-full rounded-full overflow-hidden bg-gradient-to-tr from-blue-700 to-indigo-900 border border-white/20 flex items-center justify-center text-white font-extrabold shadow-md">
                            ${user.avatar && user.avatar !== 'default-avatar.jpg' ? `<img src="uploads/${user.avatar}" class="w-full h-full object-cover">` : `<span>${user.username.charAt(0).toUpperCase()}</span>`}
                        </div>
                        ${user.active_frame ? `<img src="assets/frames/${user.active_frame}" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[124%] h-[124%] max-w-none pointer-events-none z-10" style="object-fit: contain;">` : ''}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1 flex-wrap">
                            <span class="font-extrabold text-white text-xs truncate">${user.username}</span>
                            ${user.role && user.role !== 'murid' ? `<span class="text-[8px] px-1 py-0.2 rounded font-bold uppercase bg-purple-500/20 text-purple-300 border border-purple-500/30">${user.role}</span>` : ''}
                        </div>
                        ${user.active_perk ? `<span class="text-[9px] px-2 py-0.2 rounded-full font-bold border ${user.active_perk.color} inline-block mt-0.5">${user.active_perk.label}</span>` : ''}
                        <div class="text-[10px] text-amber-300 font-extrabold mt-0.5 flex items-center gap-1">
                            <i class="fa fa-star text-amber-400"></i> ${user.reputation} Rep
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 mt-2.5 pt-2.5 border-t border-white/10 text-center text-[10px]">
                    <div class="p-1 rounded-lg bg-white/5">
                        <span class="text-white/40 block text-[8px] uppercase">Followers</span>
                        <strong class="text-white">${user.followers_count}</strong>
                    </div>
                    <div class="p-1 rounded-lg bg-white/5">
                        <span class="text-white/40 block text-[8px] uppercase">Bergabung</span>
                        <strong class="text-white">${user.joined_date}</strong>
                    </div>
                </div>

                ${perksHtml}
                ${badgesHtml}

                <div class="mt-2.5 pt-2 border-t border-white/10 flex items-center gap-2">
                    <a href="profile.php?id=${user.id}" class="flex-1 text-center py-1 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-[11px] transition-all">
                        Lihat Profil
                    </a>
                </div>
            `;
            hoverCard.classList.remove('opacity-0');
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
