<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: ../logout.php");
    exit;
}

$is_admin = in_array(strtolower($user['role'] ?? ''), ['admin', 'dev']);

$search_album = trim($_GET['search'] ?? '');

// Get albums with their cover images and photo counts
$albums_query = "
    SELECT a.*, 
           COALESCE(
               a.thumbnail,
               (SELECT p.file_hd FROM photos p WHERE p.album_id = a.id ORDER BY p.id DESC LIMIT 1)
           ) as cover_image,
           (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id) as photo_count
    FROM albums a
";
if ($search_album !== '') {
    $s_safe = $conn->real_escape_string($search_album);
    $albums_query .= " WHERE a.name LIKE '%$s_safe%' OR a.description LIKE '%$s_safe%'";
}
$albums_query .= " ORDER BY a.created_at DESC, a.id ASC";
$albums = $conn->query($albums_query);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Album Foto - Galeri OSIS SMK Medikacom</title>
    
    <!-- Meta OG Tags -->
    <meta property="og:title" content="Album Galeri OSIS SMK Medikacom" />
    <meta property="og:description" content="Koleksi Album Foto Kegiatan OSIS & MPK SMK Medikacom" />
    <meta property="og:image" content="../images/logo.png" />
    <meta property="og:url" content="https://osis-medcom.web.id/gallery/albums.php" />
    <meta property="og:type" content="website" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/liquid-theme.css">
</head>
<body class="selection:bg-blue-500/30 selection:text-white">

    <div class="main-background"></div>
    <div class="main-overlay"></div>

    <!-- Header & Navbar -->
    <header id="navbar" class="fixed top-0 left-0 right-0 z-50 pt-4 px-4 md:px-8">
        <nav class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between liquid-glass px-6 py-3.5">
                <a href="../index.html" class="flex shrink-0 items-center group">
                    <div class="p-2 bg-white/10 rounded-xl mr-3 border border-white/20 group-hover:bg-white/20 transition-colors">
                        <img class="h-8 w-auto drop-shadow-md" src="../images/logo.png" alt="Logo OSIS MPK" onerror="this.onerror=null;this.src='https://placehold.co/100x100/ffffff/000000?text=Logo';">
                    </div>
                    <span class="text-white text-sm md:text-base font-bold leading-tight tracking-wide">
                        ALBUM DOKUMENTASI<br><span class="text-white/70 font-medium text-xs">SMK MEDIKACOM</span>
                    </span>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-1 lg:space-x-2">
                    <a href="../index.html" class="px-4 py-2 rounded-full text-white/80 hover:text-white hover:bg-white/10 transition-all font-medium text-sm">Beranda</a>
                    <a href="index.php" class="px-4 py-2 rounded-full text-white/80 hover:text-white hover:bg-white/10 transition-all font-medium text-sm">Dashboard</a>
                    <a href="galery.php" class="px-4 py-2 rounded-full text-white/80 hover:text-white hover:bg-white/10 transition-all font-medium text-sm">Foto Kegiatan</a>
                    <a href="albums.php" class="px-4 py-2 rounded-full text-white bg-white/15 border border-white/20 font-semibold text-sm">Album</a>
                    <a href="../pages/forum/" class="px-4 py-2 rounded-full text-white/80 hover:text-white hover:bg-white/10 transition-all font-medium text-sm">Forum</a>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <?php if ($is_admin): ?>
                        <a href="../pages/forum/dev.php" class="px-3.5 py-1.5 rounded-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold text-xs flex items-center gap-1.5 shadow-md">
                            <i class="fa fa-shield-halved text-[11px]"></i>
                            <span class="hidden sm:inline">Admin Panel</span>
                        </a>
                    <?php endif; ?>

                    <span class="hidden sm:inline-block text-xs font-semibold px-3 py-1 rounded-full bg-white/5 border border-white/10 text-blue-300">
                        👤 <?= htmlspecialchars($user['username'] ?? 'Member') ?>
                    </span>
                    <a href="../logout.php" class="liquid-btn-danger px-3 sm:px-4 py-2 text-xs font-bold" title="Keluar">
                        Keluar
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 md:px-6 max-w-7xl pt-32 pb-20">

        <!-- Title Section -->
        <div class="text-center mb-10" data-aos="fade-down">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-white/20 bg-white/5 backdrop-blur-md text-xs font-semibold uppercase tracking-wider text-purple-300 mb-3">
                <span class="w-2 h-2 rounded-full bg-purple-400 animate-pulse"></span>
                Katalog Dokumentasi Per Kegiatan
            </div>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white mb-3">
                Koleksi Album <span class="text-gradient">Dokumentasi</span>
            </h1>
            <p class="text-sm sm:text-base text-white/70 max-w-2xl mx-auto">
                Temukan album dokumentasi MPLS, peringatan hari besar, kegiatan ekstrakurikuler, dan momen bersejarah sekolah lainnya.
            </p>
        </div>

        <!-- Search Bar -->
        <div class="liquid-glass p-5 mb-10" data-aos="fade-up">
            <form method="GET" class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="relative flex-1 w-full">
                    <input 
                        type="text" 
                        name="search" 
                        value="<?= htmlspecialchars($search_album) ?>" 
                        placeholder="Cari album kegiatan (misal: MPLS, Pelepasan, Potbar)..." 
                        class="liquid-input w-full pl-10 pr-4 py-2.5 text-xs rounded-full"
                    >
                    <svg class="w-4 h-4 text-white/40 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <?php if ($search_album): ?>
                        <a href="albums.php" class="absolute right-3.5 top-2.5 text-white/40 hover:text-white text-xs">✕</a>
                    <?php endif; ?>
                </div>
                <button type="submit" class="liquid-btn px-6 py-2.5 text-xs font-bold w-full sm:w-auto">
                    Cari Album
                </button>
            </form>
        </div>

        <!-- Albums Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            <?php if ($albums && $albums->num_rows > 0): ?>
                <?php while ($album = $albums->fetch_assoc()): ?>
                    <a href="album.php?id=<?= $album['id'] ?>" class="liquid-glass liquid-glass-interactive rounded-3xl overflow-hidden group flex flex-col justify-between" data-aos="fade-up">
                        <div class="relative h-56 bg-black/40 overflow-hidden">
                            <img 
                                src="<?= htmlspecialchars($album['cover_image'] ?: 'thumb/1.jpg') ?>" 
                                alt="<?= htmlspecialchars($album['name']) ?>" 
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                onerror="this.src='https://placehold.co/600x400/0b1021/ffffff?text=Album';"
                            >
                            <span class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-bold bg-black/70 backdrop-blur-md text-white border border-white/20">
                                📸 <?= $album['photo_count'] ?> Foto
                            </span>
                        </div>
                        <div class="p-6">
                            <h3 class="font-bold text-white text-lg group-hover:text-blue-300 transition-colors mb-2 leading-snug">
                                <?= htmlspecialchars($album['name']) ?>
                            </h3>
                            <p class="text-xs text-white/70 line-clamp-2 leading-relaxed mb-6">
                                <?= htmlspecialchars($album['description'] ?: 'Kumpulan dokumentasi foto resmi kegiatan OSIS SMK Medikacom.') ?>
                            </p>
                            <div class="pt-4 border-t border-white/10 flex items-center justify-between text-xs font-bold text-blue-400">
                                <span>Buka Album Ini</span>
                                <i class="fa fa-arrow-right text-[11px] group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full liquid-glass p-12 text-center text-white/50 text-sm">
                    Tidak ada album yang sesuai dengan pencarian Anda.
                </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- Footer -->
    <footer>
        <div class="container mx-auto px-4 md:px-6 pb-10 max-w-7xl">
            <div class="liquid-glass p-8 rounded-[32px] flex flex-col md:flex-row items-center justify-between text-white/50 text-xs gap-4">
                <div>
                    &copy; <?= date('Y') ?> Galeri OSIS & MPK SMK Medikacom Bandung. All Rights Reserved.
                </div>
                <div class="flex items-center gap-3">
                    <a href="../index.html" class="hover:text-white transition-colors">Beranda</a>
                    <span>•</span>
                    <a href="galery.php" class="hover:text-white transition-colors">Galeri</a>
                    <span>•</span>
                    <a href="albums.php" class="hover:text-white transition-colors">Album</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            AOS.init({ duration: 800, once: true });
        });
    </script>
</body>
</html>
