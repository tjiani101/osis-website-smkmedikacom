<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/forum_helpers.php';

$logged_user_id = $_SESSION['user_id'] ?? null;
$view_user_id = isset($_GET['id']) ? intval($_GET['id']) : ($logged_user_id ? intval($logged_user_id) : 0);

if ($view_user_id <= 0) {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT u.*, p.badge_label as perk_label, p.color_class as perk_color, p.description as perk_desc
    FROM users u
    LEFT JOIN perks p ON u.active_perk_id = p.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $view_user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: index.php");
    exit;
}

$is_self = ($logged_user_id && $logged_user_id == $user['id']);
$is_admin = in_array(strtolower($user['role']), ['admin', 'dev']);

// Check follow status
$is_following = false;
if ($logged_user_id && !$is_self) {
    $f_chk = $conn->query("SELECT id FROM follows WHERE follower_id = $logged_user_id AND following_id = {$user['id']}");
    if ($f_chk && $f_chk->num_rows > 0) $is_following = true;
}

// Fetch user showcase perks (is_showcase = 1)
$showcase_res = $conn->query("
    SELECT p.* 
    FROM user_perks up
    JOIN perks p ON up.perk_id = p.id
    WHERE up.user_id = {$user['id']} AND up.is_showcase = 1
    ORDER BY up.unlocked_at DESC
");
$showcase_perks = [];
if ($showcase_res) {
    while ($sp = $showcase_res->fetch_assoc()) $showcase_perks[] = $sp;
}

// Fetch user badges
$badges_res = $conn->query("
    SELECT b.* 
    FROM user_badges ub
    JOIN badges b ON ub.badge_id = b.id
    WHERE ub.user_id = {$user['id']}
    ORDER BY ub.unlocked_at DESC
");
$user_badges = [];
if ($badges_res) {
    while ($b = $badges_res->fetch_assoc()) $user_badges[] = $b;
}

// User threads
$my_threads = $conn->query("
    SELECT t.*, f.nama as forum_name, f.slug as forum_slug,
           (SELECT COUNT(*) FROM comments c WHERE c.thread_id = t.id) as comment_count,
           COALESCE((SELECT SUM(vote_type) FROM votes v WHERE v.thread_id = t.id), 0) as vote_count
    FROM threads t
    LEFT JOIN forum f ON t.forum_id = f.id
    WHERE t.user_id = {$user['id']}
    ORDER BY t.created_at DESC
");

// User comments count
$c_count_res = $conn->query("SELECT COUNT(*) as cnt FROM comments WHERE user_id = {$user['id']}");
$comments_count = (int)($c_count_res->fetch_assoc()['cnt'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil <?= htmlspecialchars($user['username']) ?> - Forum Medikacom</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/liquid-theme.css">
</head>
<body class="selection:bg-blue-500/30 selection:text-white">

    <div class="main-background"></div>
    <div class="main-overlay"></div>

    <!-- Header Navbar (Compact) -->
    <header class="fixed top-0 left-0 right-0 z-50 pt-2 sm:pt-3 px-3 sm:px-6">
        <div class="max-w-5xl mx-auto">
            <div class="liquid-glass px-3.5 sm:px-5 py-2 flex items-center justify-between">
                <a href="index.php" class="flex items-center gap-2">
                    <img class="h-6 sm:h-7 w-auto" src="../../images/logo.png" alt="Logo" onerror="this.onerror=null;this.src='https://placehold.co/100x100/ffffff/000000?text=Logo';">
                    <span class="text-white font-extrabold text-xs sm:text-sm tracking-wide">
                        FORUM <span class="text-white/60 font-normal hidden sm:inline">| Profil Pengguna</span>
                    </span>
                </a>
                <div class="flex items-center gap-1.5 sm:gap-2.5">
                    <a href="shop.php" class="px-3 py-1 rounded-full bg-amber-500/20 hover:bg-amber-500/30 border border-amber-500/40 text-amber-300 font-bold text-xs flex items-center gap-1.5 transition-all shadow-sm">
                        <i class="fa fa-store text-amber-400 text-[10px]"></i>
                        <span>Toko & Lemari</span>
                    </a>
                    <a href="index.php" class="liquid-btn-secondary px-3 py-1 text-xs font-bold gap-1 flex items-center">
                        <i class="fa fa-arrow-left text-[10px]"></i>
                        <span>Ke Forum</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 max-w-4xl pt-32 pb-24">

        <!-- Profile Card -->
        <div class="liquid-glass p-8 sm:p-10 mb-8 relative overflow-hidden">
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6 text-center sm:text-left">
                
                <!-- Avatar with Frame -->
                <div class="shrink-0 flex items-center justify-center">
                    <?= render_avatar_with_frame($user['username'], $user['avatar'], $user['active_frame'], 'xl') ?>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-2">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-white truncate"><?= htmlspecialchars($user['username']) ?></h1>
                        
                        <!-- Role Badge -->
                        <?php if (!empty($user['role']) && $user['role'] !== 'murid'): ?>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-500/20 text-purple-300 border border-purple-500/40">
                                <?= strtoupper($user['role']) ?>
                            </span>
                        <?php endif; ?>

                        <!-- Active Perk Title Badge -->
                        <?php if ($user['perk_label']): ?>
                            <span class="px-3 py-0.5 rounded-full text-xs font-bold border <?= $user['perk_color'] ?> shadow-md inline-flex items-center gap-1" title="<?= htmlspecialchars($user['perk_desc']) ?>">
                                <?= htmlspecialchars($user['perk_label']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="text-xs text-white/60 mb-4">
                        Bergabung sejak <strong class="text-white/80"><?= date('d F Y', strtotime($user['joined_at'])) ?></strong>
                    </p>

                    <!-- Stats Row -->
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 text-xs mb-4">
                        <!-- Reputation Points -->
                        <div class="px-3.5 py-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-300 font-extrabold flex items-center gap-1.5 shadow-sm">
                            <i class="fa fa-star text-amber-400"></i>
                            <span><?= number_format($user['reputation']) ?> Reputation</span>
                        </div>
                        <div class="px-3.5 py-1.5 rounded-xl bg-white/5 border border-white/10 text-white/80">
                            <span class="text-white/50">Followers:</span> <strong><?= $user['followers_count'] ?></strong>
                        </div>
                        <div class="px-3.5 py-1.5 rounded-xl bg-white/5 border border-white/10 text-white/80">
                            <span class="text-white/50">Topik:</span> <strong><?= $my_threads->num_rows ?></strong>
                        </div>
                        <div class="px-3.5 py-1.5 rounded-xl bg-white/5 border border-white/10 text-white/80">
                            <span class="text-white/50">Komentar:</span> <strong><?= $comments_count ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="shrink-0 flex flex-col gap-2">
                    <?php if ($is_self): ?>
                        <a href="shop.php" class="liquid-btn px-4 py-2 text-xs font-bold gap-2">
                            <i class="fa fa-shirt"></i> Kustomisasi Lemari
                        </a>
                        <a href="edit_profile.php" class="liquid-btn-secondary px-4 py-2 text-xs font-semibold gap-2">
                            <i class="fa fa-user-pen"></i> Edit Profil
                        </a>
                    <?php else: ?>
                        <!-- Follow Form -->
                        <form method="POST" action="users.php?id=<?= $user['id'] ?>">
                            <button type="submit" name="action" value="<?= $is_following ? 'unfollow' : 'follow' ?>" class="liquid-btn px-5 py-2 text-xs font-bold gap-2">
                                <i class="fa <?= $is_following ? 'fa-user-check' : 'fa-user-plus' ?>"></i>
                                <span><?= $is_following ? 'Mengikuti' : 'Ikuti Pengguna' ?></span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- Showcase Perks (5 Perks Pameran) -->
        <div class="liquid-glass p-6 sm:p-8 rounded-2xl border mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa fa-award text-amber-400"></i> Etalase Perks (Pameran)
                </h3>
                <?php if ($is_self): ?>
                    <a href="shop.php" class="text-xs text-blue-400 hover:text-blue-300 font-semibold">
                        Kelola di Lemari &rarr;
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($showcase_perks)): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                    <?php foreach ($showcase_perks as $sp): ?>
                        <div class="p-3.5 rounded-xl border <?= $sp['color_class'] ?> flex flex-col items-center text-center">
                            <span class="text-xs font-extrabold mb-1"><?= htmlspecialchars($sp['badge_label']) ?></span>
                            <span class="text-[10px] text-white/80 font-medium truncate max-w-full"><?= htmlspecialchars($sp['name']) ?></span>
                            <span class="text-[9px] text-white/50 mt-1 line-clamp-1"><?= htmlspecialchars($sp['benefit']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-xs text-white/50 italic py-2">
                    <?= $is_self ? 'Kamu belum memajang perks di etalase profil. Buka Lemari untuk memilih hingga 5 perks!' : 'Pengguna ini belum memajang perks di etalase.' ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Badges Collection (Lencana) -->
        <div class="liquid-glass p-6 sm:p-8 rounded-2xl border mb-8">
            <h3 class="text-base font-bold text-white flex items-center gap-2 mb-4">
                <i class="fa fa-medal text-purple-400"></i> Lencana & Pencapaian (<?= count($user_badges) ?>)
            </h3>
            
            <?php if (!empty($user_badges)): ?>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                    <?php foreach ($user_badges as $ub): ?>
                        <div class="p-3 rounded-xl border border-white/10 bg-white/5 text-center flex flex-col items-center">
                            <img src="assets/badges/<?= htmlspecialchars($ub['icon_url']) ?>" alt="Badge" class="w-12 h-12 object-contain mb-2">
                            <span class="text-xs font-bold text-white truncate max-w-full"><?= htmlspecialchars($ub['name']) ?></span>
                            <span class="text-[9px] text-white/40 mt-0.5 line-clamp-1"><?= htmlspecialchars($ub['description']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-xs text-white/50 italic py-2">Belum ada lencana yang terbuka.</p>
            <?php endif; ?>
        </div>

        <!-- User Posts Section -->
        <div class="space-y-4">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                <i class="fa fa-comments text-blue-400"></i> Topik Diskusi (<?= $my_threads->num_rows ?>)
            </h3>

            <?php if ($my_threads && $my_threads->num_rows > 0): ?>
                <div class="space-y-3">
                    <?php while ($th = $my_threads->fetch_assoc()): ?>
                        <div class="liquid-glass p-4 sm:p-5 rounded-2xl flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 text-[10px] text-white/50 mb-1">
                                    <span class="font-bold text-blue-300">sf/<?= htmlspecialchars($th['forum_slug'] ?? 'umum') ?></span>
                                    <span>•</span>
                                    <span><?= date('d M Y, H:i', strtotime($th['created_at'])) ?></span>
                                </div>
                                <a href="thread.php?id=<?= $th['id'] ?>" class="font-bold text-white text-sm sm:text-base hover:text-blue-300 transition-colors line-clamp-1">
                                    <?= htmlspecialchars($th['judul']) ?>
                                </a>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-xs px-2.5 py-1 rounded-full bg-white/5 text-white/70 font-semibold">
                                    💬 <?= $th['comment_count'] ?>
                                </span>
                                <span class="text-xs px-2.5 py-1 rounded-full bg-white/5 text-white/70 font-semibold">
                                    ⭐ <?= $th['vote_count'] ?>
                                </span>
                                <a href="thread.php?id=<?= $th['id'] ?>" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-white/60 hover:text-white">
                                    <i class="fa fa-arrow-up-right-from-square text-xs"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="liquid-glass p-8 text-center text-white/50 text-xs rounded-2xl">
                    Belum membuat topik diskusi.
                </div>
            <?php endif; ?>
        </div>

    </main>

</body>
</html>
