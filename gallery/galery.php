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

// Get photos based on user tier
$tier = $user['tier'] ?? 'HD View';
$tierArray = explode(', ', $tier);
$hasPremiumAccess = array_intersect($tierArray, ['Contributor', 'Elite', 'Legendary', 'admin']);
$isEliteOrLegendary = array_intersect($tierArray, ['Elite', 'Legendary', 'admin']);

// Filters & Search
$search = trim($_GET['search'] ?? '');
$album_filter = intval($_GET['album_id'] ?? 0);
$type_filter = $_GET['type'] ?? 'all';

$where = [];
$params = [];
$types = '';

if ($album_filter > 0) {
    $where[] = "p.album_id = ?";
    $params[] = $album_filter;
    $types .= 'i';
}
if ($search !== '') {
    $where[] = "p.judul LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}
if ($type_filter === 'premium') {
    $where[] = "p.premium = 1";
} elseif ($type_filter === 'standard') {
    $where[] = "p.premium = 0";
}

// User Tier permissions
if (!$hasPremiumAccess && $user['role'] !== 'admin') {
    $where[] = "p.premium = 0";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count for pagination
$count_sql = "SELECT COUNT(*) as total FROM photos p $where_clause";
$c_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $c_stmt->bind_param($types, ...$params);
}
$c_stmt->execute();
$total_photos = $c_stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Pagination
$per_page = 24;
$total_pages = max(1, ceil($total_photos / $per_page));
$page = max(1, min($total_pages, intval($_GET['page'] ?? 1)));
$offset = ($page - 1) * $per_page;

// Fetch photos
$query = "
    SELECT 
        p.id,
        p.judul,
        p.preview,
        p.file_hd,
        p.file_edited_hd,
        p.file_edited_fhd,
        p.file_raw,
        p.premium,
        p.tanggal_upload,
        p.album_id,
        a.name AS album_name,
        e.nama_event 
    FROM photos p
    LEFT JOIN albums a ON p.album_id = a.id
    LEFT JOIN events e ON p.event_id = e.id
    $where_clause
    ORDER BY p.tanggal_upload DESC, p.id DESC
    LIMIT ? OFFSET ?
";

$fetch_params = $params;
$fetch_types = $types . 'ii';
$fetch_params[] = $per_page;
$fetch_params[] = $offset;

$stmt = $conn->prepare($query);
$stmt->bind_param($fetch_types, ...$fetch_params);
$stmt->execute();
$photos = $stmt->get_result();

// Fetch albums list for filter dropdown
$albums_res = $conn->query("SELECT id, name, (SELECT COUNT(*) FROM photos p WHERE p.album_id = albums.id) as cnt FROM albums ORDER BY id ASC");
$albums = [];
if ($albums_res) {
    while ($r = $albums_res->fetch_assoc()) $albums[] = $r;
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Foto Kegiatan - Galeri OSIS SMK Medikacom</title>
    
    <!-- Meta OG Tags -->
    <meta property="og:title" content="Koleksi Foto OSIS SMK Medikacom" />
    <meta property="og:description" content="Arsip foto resolusi tinggi kegiatan OSIS & MPK Medikacom" />
    <meta property="og:image" content="../images/logo.png" />
    <meta property="og:url" content="https://osis-medcom.web.id/gallery/galery.php" />
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
    <link rel="stylesheet" href="../assets/css/liquid-theme.css">

    <style>
        .file-options-menu {
            display: none;
            position: absolute;
            bottom: 100%;
            left: 0;
            right: 0;
            margin-bottom: 8px;
            background: #090d1f;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(16px);
            z-index: 40;
            overflow: hidden;
        }
        .lightbox-nav-btn {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            color: white;
            transition: all 0.2s;
        }
        .lightbox-nav-btn:hover {
            background: rgba(59, 130, 246, 0.8);
            transform: scale(1.1);
        }
    </style>
</head>
<body class="selection:bg-blue-500/30 selection:text-white">

    <!-- Background Elements -->
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
                        GALERI DOKUMENTASI<br><span class="text-white/70 font-medium text-xs">SMK MEDIKACOM</span>
                    </span>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-1 lg:space-x-2">
                    <a href="../index.html" class="px-4 py-2 rounded-full text-white/80 hover:text-white hover:bg-white/10 transition-all font-medium text-sm">Beranda</a>
                    <a href="index.php" class="px-4 py-2 rounded-full text-white/80 hover:text-white hover:bg-white/10 transition-all font-medium text-sm">Dashboard</a>
                    <a href="galery.php" class="px-4 py-2 rounded-full text-white bg-white/15 border border-white/20 font-semibold text-sm">Foto Kegiatan</a>
                    <a href="albums.php" class="px-4 py-2 rounded-full text-white/80 hover:text-white hover:bg-white/10 transition-all font-medium text-sm">Album</a>
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
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-white/20 bg-white/5 backdrop-blur-md text-xs font-semibold uppercase tracking-wider text-blue-300 mb-3">
                <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                Koleksi Dokumentasi Terkini
            </div>
            <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight text-white mb-3">
                Feed Foto <span class="text-gradient">Kegiatan Sekolah</span>
            </h1>
            <p class="text-sm sm:text-base text-white/70 max-w-2xl mx-auto">
                Arsip dokumentasi resolusi tinggi dari berbagai kegiatan resmi, MPLS, pelepasan, dan event OSIS SMK Medikacom.
            </p>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="liquid-glass p-5 mb-8" data-aos="fade-up">
            <form method="GET" class="flex flex-col md:flex-row items-center justify-between gap-4">
                
                <!-- Album Pills -->
                <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <a href="galery.php" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all <?= !$album_filter ? 'bg-blue-600 text-white shadow-md' : 'bg-white/5 hover:bg-white/15 text-white/70 border border-white/10' ?>">
                        Semua Foto (<?= $total_photos ?>)
                    </a>
                    <?php foreach ($albums as $alb): ?>
                        <a href="galery.php?album_id=<?= $alb['id'] ?>" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all <?= $album_filter === (int)$alb['id'] ? 'bg-blue-600 text-white shadow-md' : 'bg-white/5 hover:bg-white/15 text-white/70 border border-white/10' ?>">
                            <?= htmlspecialchars($alb['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Search Input -->
                <div class="relative w-full md:w-72">
                    <input 
                        type="text" 
                        name="search" 
                        value="<?= htmlspecialchars($search) ?>" 
                        placeholder="Cari foto..." 
                        class="liquid-input w-full pl-9 pr-4 py-2 text-xs rounded-full"
                    >
                    <svg class="w-4 h-4 text-white/40 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <?php if ($search || $album_filter): ?>
                        <a href="galery.php" class="absolute right-3 top-2 text-white/40 hover:text-white text-xs">✕</a>
                    <?php endif; ?>
                </div>

            </form>
        </div>

        <!-- Photo Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php 
            $photo_items_json = [];
            if ($photos && $photos->num_rows > 0): 
                $idx = 0;
                while($photo = $photos->fetch_assoc()): 
                    $photo_items_json[] = [
                        'id' => $photo['id'],
                        'title' => $photo['judul'],
                        'src' => $photo['file_hd'],
                        'album' => $photo['album_name'] ?? 'Umum',
                        'date' => date('d M Y', strtotime($photo['tanggal_upload'])),
                        'premium' => (bool)$photo['premium']
                    ];
            ?>
                <div class="liquid-glass liquid-glass-interactive rounded-[28px] overflow-hidden flex flex-col justify-between group" data-aos="fade-up">
                    
                    <!-- Image Card with Click to Lightbox -->
                    <div class="relative overflow-hidden bg-black/40 h-56 cursor-pointer" onclick="openLightbox(<?= $idx ?>)">
                        <img 
                            src="<?= htmlspecialchars($photo['file_hd']) ?>" 
                            alt="<?= htmlspecialchars($photo['judul']) ?>" 
                            loading="lazy"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            oncontextmenu="return false;" 
                            draggable="false"
                            onerror="this.src='https://placehold.co/600x400/0b1021/ffffff?text=Foto+Kegiatan';"
                        >
                        <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="p-3 rounded-full bg-white/20 backdrop-blur-md text-white text-base">
                                <i class="fa fa-expand"></i>
                            </span>
                        </div>
                        <?php if(!empty($photo['premium'])): ?>
                            <span class="absolute top-3 right-3 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-600/90 text-white shadow-lg backdrop-blur-md border border-white/30">
                                ⭐ Premium
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Photo Details -->
                    <div class="p-5 flex flex-col flex-grow justify-between">
                        <div>
                            <h3 class="font-bold text-white text-base leading-snug line-clamp-1 mb-1" title="<?= htmlspecialchars($photo['judul']) ?>">
                                <?= htmlspecialchars($photo['judul']) ?>
                            </h3>
                            <div class="flex items-center justify-between text-xs text-white/50 mb-4">
                                <span>📅 <?= date('d M Y', strtotime($photo['tanggal_upload'])) ?></span>
                                <span class="px-2 py-0.5 rounded-full bg-white/5 border border-white/10 text-white/70 text-[10px] truncate max-w-[120px]">
                                    <?= htmlspecialchars($photo['album_name'] ?: ($photo['nama_event'] ?: 'Umum')) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Download Action -->
                        <?php if(!$photo['premium'] || $hasPremiumAccess): ?>
                            <div class="relative">
                                <?php if(!$photo['premium'] || in_array('HD View', $tierArray)): ?>
                                    <a href="download.php?file=<?= urlencode($photo['file_hd']) ?>&type=HD Original" 
                                       class="liquid-btn w-full py-2.5 text-xs font-bold gap-2" 
                                       download>
                                        <i class="fa fa-download text-xs"></i>
                                        <span>Unduh Foto HD</span>
                                    </a>
                                <?php else: ?>
                                    <button class="liquid-btn w-full py-2.5 text-xs font-bold gap-2" onclick="toggleDownloadMenu(event, <?= $photo['id'] ?>)">
                                        <i class="fa fa-download text-xs"></i>
                                        <span>Pilih Resolusi</span>
                                    </button>
                                    
                                    <div class="file-options-menu p-2" id="options-<?= $photo['id'] ?>">
                                        <?php if($isEliteOrLegendary && !empty($photo['file_raw'])): ?>
                                            <a href="download.php?file=<?= urlencode($photo['file_raw']) ?>&type=RAW" class="block px-3 py-2 text-xs rounded-xl hover:bg-white/10 text-white font-medium" download>
                                                📁 RAW Master File
                                            </a>
                                        <?php endif; ?>
                                        <?php if(!empty($photo['file_edited_fhd'])): ?>
                                            <a href="download.php?file=<?= urlencode($photo['file_edited_fhd']) ?>&type=FHD Edited" class="block px-3 py-2 text-xs rounded-xl hover:bg-white/10 text-white font-medium" download>
                                                ✨ Full HD (Edited)
                                            </a>
                                        <?php endif; ?>
                                        <a href="download.php?file=<?= urlencode($photo['file_hd']) ?>&type=Original" class="block px-3 py-2 text-xs rounded-xl hover:bg-white/10 text-white font-medium" download>
                                            📷 HD Original
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <button class="w-full py-2.5 rounded-full text-xs font-bold bg-white/5 border border-white/10 text-white/40 cursor-not-allowed flex items-center justify-center gap-2" disabled>
                                🔒 Khusus Tier Contributor
                            </button>
                        <?php endif; ?>

                    </div>
                </div>
                <?php 
                    $idx++;
                endwhile; ?>
            <?php else: ?>
                <div class="col-span-full liquid-glass p-12 text-center text-white/60">
                    <i class="fa fa-images text-3xl mb-3 block text-white/30"></i>
                    Belum ada foto dalam kategori atau filter ini.
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="flex items-center justify-center gap-2 mt-12">
                <?php if ($page > 1): ?>
                    <a href="galery.php?page=<?= $page - 1 ?><?= $album_filter ? '&album_id=' . $album_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="p-2.5 px-4 rounded-xl liquid-glass text-xs font-bold text-white hover:bg-white/10 transition-all">
                        ← Sebelumnya
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="galery.php?page=<?= $i ?><?= $album_filter ? '&album_id=' . $album_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="w-10 h-10 rounded-xl flex items-center justify-center text-xs font-bold transition-all <?= $page === $i ? 'bg-blue-600 text-white shadow-lg' : 'liquid-glass text-white/70 hover:text-white' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="galery.php?page=<?= $page + 1 ?><?= $album_filter ? '&album_id=' . $album_filter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="p-2.5 px-4 rounded-xl liquid-glass text-xs font-bold text-white hover:bg-white/10 transition-all">
                        Selanjutnya →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>

    <!-- Interactive Lightbox Modal -->
    <div id="lightbox-modal" class="fixed inset-0 z-50 bg-black/95 backdrop-blur-2xl hidden flex flex-col items-center justify-between p-4 sm:p-6" onclick="closeLightbox()">
        <!-- Lightbox Topbar -->
        <div class="w-full max-w-6xl flex items-center justify-between text-white pb-3 border-b border-white/10" onclick="event.stopPropagation()">
            <div>
                <h3 id="lightbox-title" class="text-sm sm:text-base font-bold text-white truncate max-w-md"></h3>
                <p id="lightbox-meta" class="text-xs text-white/50"></p>
            </div>
            <div class="flex items-center gap-3">
                <a id="lightbox-dl-btn" href="#" download class="px-4 py-2 rounded-full bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center gap-1.5 transition-all">
                    <i class="fa fa-download text-xs"></i>
                    <span>Unduh</span>
                </a>
                <button onclick="closeLightbox()" class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all text-sm">
                    ✕
                </button>
            </div>
        </div>

        <!-- Lightbox Main Stage -->
        <div class="relative flex-1 w-full flex items-center justify-center my-4 overflow-hidden" onclick="event.stopPropagation()">
            <!-- Prev Button -->
            <button onclick="lightboxPrev()" class="lightbox-nav-btn absolute left-2 sm:left-4 z-20 w-11 h-11 rounded-full flex items-center justify-center text-lg">
                <i class="fa fa-chevron-left"></i>
            </button>

            <!-- Image View -->
            <img id="lightbox-img" src="" alt="Photo" class="max-w-full max-h-[75vh] object-contain rounded-2xl shadow-2xl transition-all duration-300">

            <!-- Next Button -->
            <button onclick="lightboxNext()" class="lightbox-nav-btn absolute right-2 sm:right-4 z-20 w-11 h-11 rounded-full flex items-center justify-center text-lg">
                <i class="fa fa-chevron-right"></i>
            </button>
        </div>

        <!-- Lightbox Counter Footer -->
        <div class="text-center text-xs text-white/40 pb-2" onclick="event.stopPropagation()">
            <span id="lightbox-counter">1 / 1</span> • Gunakan tombol panah keyboard ← → untuk navigasi
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container mx-auto px-4 md:px-6 pb-10 max-w-7xl">
            <div class="liquid-glass p-8 rounded-[32px] flex flex-col md:flex-row items-center justify-between text-white/50 text-xs gap-4">
                <div>
                    &copy; <?= date('Y') ?> Galeri OSIS & MPK SMK Medikacom Bandung. All Rights Reserved.
                </div>
                <div class="flex items-center gap-3">
                    <a href="../index.html" class="hover:text-white transition-colors">Beranda Utama</a>
                    <span>•</span>
                    <a href="index.php" class="hover:text-white transition-colors">Dashboard Galeri</a>
                    <span>•</span>
                    <a href="albums.php" class="hover:text-white transition-colors">Koleksi Album</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        const galleryItems = <?= json_encode($photo_items_json) ?>;
        let currentLightboxIndex = 0;

        document.addEventListener('DOMContentLoaded', () => {
            AOS.init({ duration: 800, once: true });
        });

        function toggleDownloadMenu(e, id) {
            e.stopPropagation();
            document.querySelectorAll('.file-options-menu').forEach(menu => {
                if (menu.id !== `options-${id}`) menu.style.display = 'none';
            });
            const target = document.getElementById(`options-${id}`);
            if (target) {
                target.style.display = target.style.display === 'block' ? 'none' : 'block';
            }
        }

        document.addEventListener('click', () => {
            document.querySelectorAll('.file-options-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        });

        // Lightbox Functions
        function openLightbox(index) {
            if (!galleryItems[index]) return;
            currentLightboxIndex = index;
            renderLightboxItem();
            document.getElementById('lightbox-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            document.getElementById('lightbox-modal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function renderLightboxItem() {
            const item = galleryItems[currentLightboxIndex];
            if (!item) return;

            document.getElementById('lightbox-img').src = item.src;
            document.getElementById('lightbox-title').textContent = item.title;
            document.getElementById('lightbox-meta').textContent = `${item.album} • ${item.date}`;
            document.getElementById('lightbox-dl-btn').href = `download.php?file=${encodeURIComponent(item.src)}&type=HD`;
            document.getElementById('lightbox-counter').textContent = `${currentLightboxIndex + 1} dari ${galleryItems.length}`;
        }

        function lightboxPrev() {
            if (currentLightboxIndex > 0) {
                currentLightboxIndex--;
            } else {
                currentLightboxIndex = galleryItems.length - 1;
            }
            renderLightboxItem();
        }

        function lightboxNext() {
            if (currentLightboxIndex < galleryItems.length - 1) {
                currentLightboxIndex++;
            } else {
                currentLightboxIndex = 0;
            }
            renderLightboxItem();
        }

        // Keyboard navigation for lightbox
        document.addEventListener('keydown', (e) => {
            const modal = document.getElementById('lightbox-modal');
            if (!modal.classList.contains('hidden')) {
                if (e.key === 'ArrowLeft') lightboxPrev();
                else if (e.key === 'ArrowRight') lightboxNext();
                else if (e.key === 'Escape') closeLightbox();
            }
        });
    </script>
</body>
</html>
