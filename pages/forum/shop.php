<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/forum_helpers.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=" . urlencode("Silakan masuk untuk mengakses Toko Reputasi."));
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Fetch user data
$u_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$user = $u_stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: logout.php");
    exit;
}

// Award any pending milestone badges
award_milestones_if_eligible($conn, $user_id);

// Check if user has VIP perk (perk_id 15) for extra showcase slots
$has_vip = false;
$vip_chk = $conn->query("SELECT id FROM user_perks WHERE user_id = $user_id AND perk_id = 15");
if ($vip_chk && $vip_chk->num_rows > 0) $has_vip = true;
$max_showcase = $has_vip ? 7 : 5;

// Fetch all Avatar Frames
$frames_res = $conn->query("SELECT * FROM avatar_frames ORDER BY price_rep ASC");
$all_frames = [];
if ($frames_res) {
    while ($f = $frames_res->fetch_assoc()) $all_frames[] = $f;
}

// Fetch user owned frames
$owned_frames = [];
$of_res = $conn->query("SELECT frame_id FROM user_frames WHERE user_id = $user_id");
if ($of_res) {
    while ($row = $of_res->fetch_assoc()) $owned_frames[] = (int)$row['frame_id'];
}

// Fetch all Badges
$badges_res = $conn->query("SELECT * FROM badges ORDER BY type DESC, price_rep ASC");
$all_badges = [];
if ($badges_res) {
    while ($b = $badges_res->fetch_assoc()) $all_badges[] = $b;
}

// Fetch user owned badges
$owned_badges = [];
$ob_res = $conn->query("SELECT badge_id FROM user_badges WHERE user_id = $user_id");
if ($ob_res) {
    while ($row = $ob_res->fetch_assoc()) $owned_badges[] = (int)$row['badge_id'];
}

// Fetch all Perks
$perks_res = $conn->query("SELECT * FROM perks ORDER BY price_rep ASC");
$all_perks = [];
if ($perks_res) {
    while ($p = $perks_res->fetch_assoc()) $all_perks[] = $p;
}

// Fetch user owned perks with showcase status
$owned_perks = [];
$showcase_count = 0;
$op_res = $conn->query("SELECT perk_id, is_showcase FROM user_perks WHERE user_id = $user_id");
if ($op_res) {
    while ($row = $op_res->fetch_assoc()) {
        $owned_perks[(int)$row['perk_id']] = (int)$row['is_showcase'];
        if ($row['is_showcase'] == 1) $showcase_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Reputasi & Lemari - Forum Medikacom</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/liquid-theme.css">

    <style>
        .rarity-common { border-color: rgba(148, 163, 184, 0.4); }
        .rarity-rare { border-color: rgba(56, 189, 248, 0.5); background: rgba(56, 189, 248, 0.05); }
        .rarity-epic { border-color: rgba(192, 132, 252, 0.5); background: rgba(192, 132, 252, 0.05); }
        .rarity-legendary { border-color: rgba(250, 204, 21, 0.6); background: rgba(250, 204, 21, 0.08); box-shadow: 0 0 20px rgba(234, 179, 8, 0.15); }
    </style>
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
                        FORUM <span class="text-white/60 font-normal hidden sm:inline">| Toko Reputasi</span>
                    </span>
                </a>
                
                <div class="flex items-center gap-2">
                    <!-- User Rep Pill -->
                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-300 font-extrabold text-xs shadow-sm">
                        <i class="fa fa-star text-amber-400 text-[10px]"></i>
                        <span id="user-rep-display"><?= number_format($user['reputation']) ?></span>
                        <span class="text-[9px] uppercase font-semibold text-amber-200/70">Rep</span>
                    </div>

                    <a href="profile.php" class="liquid-btn-secondary px-3 py-1 text-xs font-semibold gap-1 flex items-center">
                        <i class="fa fa-user text-[10px]"></i>
                        <span class="hidden sm:inline">Profil</span>
                    </a>
                    <a href="index.php" class="liquid-btn px-3 py-1 text-xs font-bold gap-1 flex items-center">
                        <i class="fa fa-arrow-left text-[10px]"></i>
                        <span class="hidden sm:inline">Kembali</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 max-w-6xl pt-28 sm:pt-32 pb-24">

        <!-- Toast Notification Container -->
        <div id="toast-msg" class="fixed top-20 right-4 z-50 transform transition-all duration-300 translate-x-full opacity-0 pointer-events-none p-4 rounded-2xl liquid-glass border font-semibold text-xs shadow-2xl flex items-center gap-2.5 max-w-sm"></div>

        <!-- Hero Section -->
        <div class="text-center mb-8">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-2">
                🛍️ Toko Reputasi & <span class="text-gradient">Lemari Kustomisasi</span>
            </h1>
            <p class="text-xs sm:text-sm text-white/70 max-w-xl mx-auto">
                Tukarkan poin reputasi yang kamu kumpulkan dari upvote siswa lain untuk membuka bingkai avatar eksklusif, gelar perks unik, dan lencana prestisius!
            </p>
        </div>

        <!-- Nav Tabs (Shop vs Wardrobe) -->
        <div class="liquid-glass p-2 mb-8 flex items-center justify-center gap-2 max-w-md mx-auto rounded-2xl">
            <button id="tab-btn-shop" onclick="switchTab('shop')" class="flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition-all bg-blue-600 text-white shadow-lg flex items-center justify-center gap-2">
                <i class="fa fa-store"></i>
                <span>Toko Item</span>
            </button>
            <button id="tab-btn-wardrobe" onclick="switchTab('wardrobe')" class="flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition-all text-white/70 hover:text-white flex items-center justify-center gap-2">
                <i class="fa fa-shirt"></i>
                <span>Lemari Saya</span>
            </button>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 1: TOKO REPUTASI -->
        <!-- ======================================================== -->
        <div id="tab-content-shop" class="space-y-12">
            
            <!-- SECTION 1: AVATAR FRAMES -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                            <span>🖼️ Bingkai Avatar Eksklusif</span>
                        </h2>
                        <p class="text-xs text-white/60">Tampil menonjol di beranda feed dan kolom komentar setiap postingan.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <?php foreach ($all_frames as $frame): 
                        $is_owned = in_array((int)$frame['id'], $owned_frames);
                    ?>
                        <div class="liquid-glass p-5 rounded-2xl border text-center flex flex-col justify-between rarity-<?= $frame['rarity'] ?> transition-transform hover:-translate-y-1">
                            <div>
                                <!-- Live Preview Box -->
                                <div class="my-3 flex justify-center">
                                    <?= render_avatar_with_frame($user['username'], $user['avatar'], $frame['image_url'], 'lg') ?>
                                </div>
                                <h3 class="font-bold text-white text-sm mb-1"><?= htmlspecialchars($frame['name']) ?></h3>
                                <span class="text-[9px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider mb-2 inline-block <?= $frame['rarity'] === 'legendary' ? 'bg-amber-400/20 text-amber-300' : ($frame['rarity'] === 'epic' ? 'bg-purple-500/20 text-purple-300' : 'bg-sky-500/20 text-sky-300') ?>">
                                    <?= strtoupper($frame['rarity']) ?>
                                </span>
                                <p class="text-[11px] text-white/60 mb-4 line-clamp-2"><?= htmlspecialchars($frame['description']) ?></p>
                            </div>

                            <div>
                                <?php if ($is_owned): ?>
                                    <button disabled class="w-full py-2 rounded-xl bg-white/10 text-white/50 text-xs font-semibold cursor-not-allowed">
                                        <i class="fa fa-check mr-1 text-emerald-400"></i> Dimiliki
                                    </button>
                                <?php else: ?>
                                    <button onclick="buyItem('buy_frame', <?= $frame['id'] ?>, <?= $frame['price_rep'] ?>)" class="w-full py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5">
                                        <i class="fa fa-star text-amber-300 text-[10px]"></i>
                                        <span>Beli (<?= $frame['price_rep'] ?> Rep)</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- SECTION 2: PERKS EKSKLUSIF (20 PERKS) -->
            <div>
                <div class="mb-4">
                    <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                        <span>⚡ Gelar & Perks Forum</span>
                    </h2>
                    <p class="text-xs text-white/60">Gelar istimewa yang bisa kamu pasang di samping nama dan dipajang di etalase profil.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach ($all_perks as $perk): 
                        $is_owned = isset($owned_perks[(int)$perk['id']]);
                    ?>
                        <div class="liquid-glass p-4 rounded-2xl border flex flex-col justify-between rarity-<?= $perk['rarity'] ?> transition-transform hover:-translate-y-1">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] px-2.5 py-0.5 rounded-full font-bold border <?= $perk['color_class'] ?>">
                                        <?= htmlspecialchars($perk['badge_label']) ?>
                                    </span>
                                    <span class="text-[9px] uppercase font-bold text-white/40"><?= $perk['rarity'] ?></span>
                                </div>
                                <h3 class="font-bold text-white text-sm mb-1"><?= htmlspecialchars($perk['name']) ?></h3>
                                <p class="text-[11px] text-white/70 mb-2"><?= htmlspecialchars($perk['description']) ?></p>
                                <div class="p-2 rounded-xl bg-white/5 border border-white/5 text-[10px] text-blue-200/90 mb-4">
                                    <i class="fa fa-gift text-blue-400 mr-1"></i> <?= htmlspecialchars($perk['benefit']) ?>
                                </div>
                            </div>

                            <div>
                                <?php if ($is_owned): ?>
                                    <button disabled class="w-full py-2 rounded-xl bg-white/10 text-white/50 text-xs font-semibold cursor-not-allowed">
                                        <i class="fa fa-check mr-1 text-emerald-400"></i> Dimiliki
                                    </button>
                                <?php else: ?>
                                    <button onclick="buyItem('buy_perk', <?= $perk['id'] ?>, <?= $perk['price_rep'] ?>)" class="w-full py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition-all shadow-md flex items-center justify-center gap-1.5">
                                        <i class="fa fa-star text-amber-300 text-[10px]"></i>
                                        <span>Beli (<?= $perk['price_rep'] ?> Rep)</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- SECTION 3: BADGES BERBAYAR -->
            <div>
                <div class="mb-4">
                    <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                        <span>🎖️ Lencana Kolektor (Badges Berbayar)</span>
                    </h2>
                    <p class="text-xs text-white/60">Simbol prestise dedikasi tinggi bagi pendukung aktif forum komunitas.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <?php foreach ($all_badges as $badge): 
                        if ($badge['type'] !== 'purchasable') continue;
                        $is_owned = in_array((int)$badge['id'], $owned_badges);
                    ?>
                        <div class="liquid-glass p-5 rounded-2xl border flex items-center gap-4">
                            <div class="w-14 h-14 shrink-0 rounded-2xl p-1 bg-white/5 border border-white/10 flex items-center justify-center">
                                <img src="assets/badges/<?= htmlspecialchars($badge['icon_url']) ?>" alt="Badge" class="w-12 h-12 object-contain">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-white text-sm"><?= htmlspecialchars($badge['name']) ?></h3>
                                <p class="text-[11px] text-white/60 mb-2 line-clamp-2"><?= htmlspecialchars($badge['description']) ?></p>
                                
                                <?php if ($is_owned): ?>
                                    <span class="inline-block text-[10px] text-emerald-400 font-bold">
                                        <i class="fa fa-check-circle mr-1"></i> Sudah Dikoleksi
                                    </span>
                                <?php else: ?>
                                    <button onclick="buyItem('buy_badge', <?= $badge['id'] ?>, <?= $badge['price_rep'] ?>)" class="py-1.5 px-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition-all flex items-center gap-1">
                                        <i class="fa fa-star text-amber-300 text-[9px]"></i>
                                        <span>Beli (<?= $badge['price_rep'] ?> Rep)</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- ======================================================== -->
        <!-- TAB 2: LEMARI KUSTOMISASI (WARDROBE / INVENTORY) -->
        <!-- ======================================================== -->
        <div id="tab-content-wardrobe" class="hidden space-y-10">

            <!-- 1. Ganti Frame Avatar Aktif -->
            <div class="liquid-glass p-6 sm:p-8 rounded-2xl border">
                <div class="flex flex-col sm:flex-row items-center sm:items-start justify-between gap-6 mb-6">
                    <div>
                        <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                            <span>🖼️ Pasang Bingkai Avatar</span>
                        </h2>
                        <p class="text-xs text-white/60">Pilih frame yang kamu miliki untuk ditampilkan di foto profil forum.</p>
                    </div>
                    <!-- Current Avatar Preview -->
                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/5 border border-white/10">
                        <span class="text-xs text-white/60">Tampilan Saat Ini:</span>
                        <?= render_avatar_with_frame($user['username'], $user['avatar'], $user['active_frame'], 'md') ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                    <!-- Option None / Tanpa Frame -->
                    <button onclick="equipFrame('none')" class="p-3 rounded-xl border <?= empty($user['active_frame']) ? 'border-blue-400 bg-blue-500/20' : 'border-white/10 bg-white/5 hover:bg-white/10' ?> flex flex-col items-center text-center">
                        <div class="w-10 h-10 rounded-full border border-dashed border-white/40 flex items-center justify-center text-white/40 text-xs mb-2">
                            ✕
                        </div>
                        <span class="text-xs font-bold text-white">Tanpa Frame</span>
                    </button>

                    <?php foreach ($all_frames as $frame): 
                        if (!in_array((int)$frame['id'], $owned_frames)) continue;
                        $is_active = ($user['active_frame'] === $frame['image_url']);
                    ?>
                        <button onclick="equipFrame('<?= htmlspecialchars($frame['image_url']) ?>')" class="p-3 rounded-xl border <?= $is_active ? 'border-blue-400 bg-blue-500/20 ring-2 ring-blue-400/50' : 'border-white/10 bg-white/5 hover:bg-white/10' ?> flex flex-col items-center text-center transition-all">
                            <div class="my-1">
                                <?= render_avatar_with_frame($user['username'], $user['avatar'], $frame['image_url'], 'sm') ?>
                            </div>
                            <span class="text-xs font-bold text-white truncate max-w-full"><?= htmlspecialchars($frame['name']) ?></span>
                            <?php if ($is_active): ?>
                                <span class="text-[9px] text-blue-300 font-bold mt-1">Aktif</span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 2. Pilih 1 Perk Aktif di Samping Nama -->
            <div class="liquid-glass p-6 sm:p-8 rounded-2xl border">
                <div class="mb-4">
                    <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                        <span>🏷️ Pilih 1 Perk Utama di Samping Nama</span>
                    </h2>
                    <p class="text-xs text-white/60">Gelar ini akan terpampang persis di sebelah nama kamu pada feed beranda dan komentar.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <!-- Option None -->
                    <button onclick="setActivePerk(0)" class="p-3 rounded-xl border <?= empty($user['active_perk_id']) ? 'border-blue-400 bg-blue-500/20' : 'border-white/10 bg-white/5 hover:bg-white/10' ?> text-left">
                        <span class="text-xs font-bold text-white/70 block">Tanpa Gelar Perk</span>
                        <span class="text-[10px] text-white/40">Hanya menampilkan nama & role</span>
                    </button>

                    <?php foreach ($all_perks as $perk): 
                        if (!isset($owned_perks[(int)$perk['id']])) continue;
                        $is_active = ($user['active_perk_id'] == $perk['id']);
                    ?>
                        <button onclick="setActivePerk(<?= $perk['id'] ?>)" class="p-3 rounded-xl border <?= $is_active ? 'border-blue-400 bg-blue-500/20 ring-2 ring-blue-400/50' : 'border-white/10 bg-white/5 hover:bg-white/10' ?> text-left transition-all">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold border <?= $perk['color_class'] ?>">
                                    <?= htmlspecialchars($perk['badge_label']) ?>
                                </span>
                                <?php if ($is_active): ?>
                                    <span class="text-[9px] text-blue-300 font-bold">Dipakai</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-xs font-bold text-white block truncate"><?= htmlspecialchars($perk['name']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 3. Kelola Etalase Profil (Pameran 5 Perks) -->
            <div class="liquid-glass p-6 sm:p-8 rounded-2xl border">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                            <span>🏛️ Etalase Profil (Pamerkan <?= $max_showcase ?> Perks)</span>
                        </h2>
                        <p class="text-xs text-white/60">Pilih hingga <?= $max_showcase ?> perks yang akan dipajang saat orang lain melihat profil atau popover kartu kamu.</p>
                    </div>
                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-white/10 text-white">
                        <span id="showcase-counter"><?= $showcase_count ?></span> / <?= $max_showcase ?> Terpilih
                    </span>
                </div>

                <?php if (empty($owned_perks)): ?>
                    <p class="text-xs text-white/40 italic py-4">Kamu belum memiliki perks. Silakan buka tab Toko Item untuk membelinya!</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach ($all_perks as $perk): 
                            if (!isset($owned_perks[(int)$perk['id']])) continue;
                            $in_showcase = ($owned_perks[(int)$perk['id']] == 1);
                        ?>
                            <div class="p-3.5 rounded-xl border <?= $in_showcase ? 'border-emerald-500/40 bg-emerald-500/10' : 'border-white/10 bg-white/5' ?> flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold border <?= $perk['color_class'] ?> inline-block mb-1">
                                        <?= htmlspecialchars($perk['badge_label']) ?>
                                    </span>
                                    <div class="text-xs font-bold text-white truncate"><?= htmlspecialchars($perk['name']) ?></div>
                                </div>
                                <button onclick="toggleShowcasePerk(<?= $perk['id'] ?>, <?= $in_showcase ? 0 : 1 ?>)" class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $in_showcase ? 'bg-red-500/20 text-red-300 hover:bg-red-500/30' : 'bg-emerald-600 text-white hover:bg-emerald-500' ?>">
                                    <?= $in_showcase ? 'Copot' : 'Pajang' ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 4. Koleksi Badges / Lencana Saya -->
            <div class="liquid-glass p-6 sm:p-8 rounded-2xl border">
                <div class="mb-4">
                    <h2 class="text-xl font-extrabold text-white flex items-center gap-2">
                        <span>🎖️ Koleksi Lencana Saya</span>
                    </h2>
                    <p class="text-xs text-white/60">Semua lencana pencapaian (milestone) dan lencana toko yang telah kamu raih.</p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                    <?php foreach ($all_badges as $badge): 
                        $is_owned = in_array((int)$badge['id'], $owned_badges);
                    ?>
                        <div class="p-4 rounded-xl border text-center flex flex-col items-center justify-center <?= $is_owned ? 'border-blue-400/30 bg-blue-500/10' : 'border-white/5 bg-white/[0.02] opacity-40 grayscale' ?>">
                            <img src="assets/badges/<?= htmlspecialchars($badge['icon_url']) ?>" alt="Badge" class="w-12 h-12 object-contain mb-2">
                            <h4 class="text-xs font-bold text-white mb-0.5 truncate max-w-full"><?= htmlspecialchars($badge['name']) ?></h4>
                            <span class="text-[9px] <?= $is_owned ? 'text-emerald-400 font-semibold' : 'text-white/40' ?>">
                                <?= $is_owned ? 'Telah Diraih' : 'Terkunci' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

    </main>

    <script>
        function showToast(msg, isSuccess = true) {
            const toast = document.getElementById('toast-msg');
            toast.textContent = (isSuccess ? '✅ ' : '⚠️ ') + msg;
            toast.className = `fixed top-20 right-4 z-50 transform transition-all duration-300 translate-x-0 opacity-100 p-4 rounded-2xl border font-semibold text-xs shadow-2xl flex items-center gap-2.5 max-w-sm ${isSuccess ? 'bg-emerald-950/90 border-emerald-500/50 text-emerald-200' : 'bg-red-950/90 border-red-500/50 text-red-200'}`;
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
            }, 3000);
        }

        function switchTab(tab) {
            const shopContent = document.getElementById('tab-content-shop');
            const wardrobeContent = document.getElementById('tab-content-wardrobe');
            const shopBtn = document.getElementById('tab-btn-shop');
            const wardrobeBtn = document.getElementById('tab-btn-wardrobe');

            if (tab === 'shop') {
                shopContent.classList.remove('hidden');
                wardrobeContent.classList.add('hidden');
                shopBtn.className = "flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition-all bg-blue-600 text-white shadow-lg flex items-center justify-center gap-2";
                wardrobeBtn.className = "flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition-all text-white/70 hover:text-white flex items-center justify-center gap-2";
            } else {
                shopContent.classList.add('hidden');
                wardrobeContent.classList.remove('hidden');
                wardrobeBtn.className = "flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition-all bg-blue-600 text-white shadow-lg flex items-center justify-center gap-2";
                shopBtn.className = "flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition-all text-white/70 hover:text-white flex items-center justify-center gap-2";
            }
        }

        async function buyItem(action, id, price) {
            if (!confirm(`Konfirmasi pembelian seharga ${price} Rep?`)) return;

            const fd = new FormData();
            fd.append('action', action);
            if (action === 'buy_frame') fd.append('frame_id', id);
            if (action === 'buy_badge') fd.append('badge_id', id);
            if (action === 'buy_perk') fd.append('perk_id', id);

            try {
                const res = await fetch('shop_action.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, true);
                    if (data.new_rep !== undefined) {
                        document.getElementById('user-rep-display').textContent = Number(data.new_rep).toLocaleString();
                    }
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(data.error || 'Gagal memproses pembelian', false);
                }
            } catch (e) {
                showToast('Terjadi kesalahan jaringan.', false);
            }
        }

        async function equipFrame(frameFile) {
            const fd = new FormData();
            fd.append('action', 'equip_frame');
            fd.append('frame_file', frameFile);

            try {
                const res = await fetch('shop_action.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, true);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error, false);
                }
            } catch (e) {
                showToast('Terjadi kesalahan jaringan.', false);
            }
        }

        async function setActivePerk(perkId) {
            const fd = new FormData();
            fd.append('action', 'set_active_perk');
            fd.append('perk_id', perkId);

            try {
                const res = await fetch('shop_action.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, true);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error, false);
                }
            } catch (e) {
                showToast('Terjadi kesalahan jaringan.', false);
            }
        }

        async function toggleShowcasePerk(perkId, status) {
            const fd = new FormData();
            fd.append('action', 'toggle_showcase_perk');
            fd.append('perk_id', perkId);
            fd.append('status', status);

            try {
                const res = await fetch('shop_action.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, true);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error, false);
                }
            } catch (e) {
                showToast('Terjadi kesalahan jaringan.', false);
            }
        }
    </script>
</body>
</html>
