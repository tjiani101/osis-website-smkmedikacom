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

$album_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($album_id <= 0) {
    header("Location: albums.php");
    exit;
}

// Fetch album details
$a_stmt = $conn->prepare("SELECT * FROM albums WHERE id = ?");
$a_stmt->bind_param("i", $album_id);
$a_stmt->execute();
$album = $a_stmt->get_result()->fetch_assoc();

if (!$album) {
    header("Location: albums.php");
    exit;
}

// User Tier permissions
$tier = $user['tier'] ?? 'HD View';
$tierArray = explode(', ', $tier);
$hasPremiumAccess = array_intersect($tierArray, ['Contributor', 'Elite', 'Legendary', 'admin']);

// Fetch photos in album
$query = "
    SELECT p.*, e.nama_event 
    FROM photos p
    LEFT JOIN events e ON p.event_id = e.id
    WHERE p.album_id = ?
";
if (!$hasPremiumAccess && $user['role'] !== 'admin') {
    $query .= " AND p.premium = 0";
}
$query .= " ORDER BY p.tanggal_upload DESC, p.id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $album_id);
$stmt->execute();
$photos = $stmt->get_result();

$photo_items_json = [];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($album['name']) ?> - Album Galeri OSIS</title>
    
    <!-- Meta OG Tags -->
    <meta property="og:title" content="<?= htmlspecialchars($album['name']) ?> - OSIS SMK Medikacom" />
    <meta property="og:description" content="Album Foto Kegiatan OSIS & MPK SMK Medikacom" />
    <meta property="og:image" content="../images/logo.png" />
    <meta property="og:url" content="https://osis-medcom.web.id/gallery/album.php?id=<?= $album_id ?>" />
    <meta property="og:type" content="website" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/liquid-theme.css">

    <style>
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
                        ALBUM KEGIATAN<br><span class="text-white/70 font-medium text-xs">SMK MEDIKACOM</span>
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
                    <a href="albums.php" class="liquid-btn-secondary px-3 sm:px-4 py-2 text-xs font-bold gap-1.5">
                        <i class="fa fa-arrow-left text-[11px]"></i>
                        <span>Semua Album</span>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 md:px-6 max-w-7xl pt-32 pb-20">

        <!-- Album Header Banner -->
        <div class="liquid-glass p-8 sm:p-10 mb-10 rounded-3xl relative overflow-hidden" data-aos="fade-down">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
                <div>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-bold uppercase tracking-wider mb-2">
                        <i class="fa fa-folder-open text-[11px]"></i>
                        <span>Album Kegiatan Resmi</span>
                    </div>
                    <h1 class="text-2xl sm:text-4xl font-extrabold text-white mb-2 leading-tight">
                        <?= htmlspecialchars($album['name']) ?>
                    </h1>
                    <p class="text-xs sm:text-sm text-white/70 max-w-2xl leading-relaxed">
                        <?= htmlspecialchars($album['description'] ?: 'Koleksi dokumentasi foto resmi kegiatan OSIS SMK Medikacom Bandung.') ?>
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="px-4 py-2 rounded-full bg-white/10 border border-white/15 text-white font-bold text-xs">
                        📸 <?= $photos->num_rows ?> Total Foto
                    </span>
                    <?php if ($is_admin): ?>
                        <a href="../pages/forum/dev.php?page=galery" class="px-4 py-2 rounded-full bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center gap-1.5">
                            <i class="fa fa-cloud-arrow-up"></i> Tambah Foto
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Photos Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php if ($photos && $photos->num_rows > 0): 
                $idx = 0;
                while($photo = $photos->fetch_assoc()): 
                    $photo_items_json[] = [
                        'id' => $photo['id'],
                        'title' => $photo['judul'],
                        'src' => $photo['file_hd'],
                        'album' => $album['name'],
                        'date' => date('d M Y', strtotime($photo['tanggal_upload'])),
                        'premium' => (bool)$photo['premium']
                    ];
            ?>
                <div class="liquid-glass liquid-glass-interactive rounded-2xl overflow-hidden group flex flex-col justify-between" data-aos="fade-up">
                    <div class="relative h-56 bg-black/40 overflow-hidden cursor-pointer" onclick="openLightbox(<?= $idx ?>)">
                        <img 
                            src="<?= htmlspecialchars($photo['file_hd']) ?>" 
                            alt="<?= htmlspecialchars($photo['judul']) ?>" 
                            loading="lazy"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            onerror="this.src='https://placehold.co/600x400/0b1021/ffffff?text=Foto';"
                        >
                        <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="p-3 rounded-full bg-white/20 backdrop-blur-md text-white text-base">
                                <i class="fa fa-expand"></i>
                            </span>
                        </div>
                        <?php if ($photo['premium']): ?>
                            <span class="absolute top-2.5 right-2.5 px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase bg-purple-600 text-white">
                                ⭐ HD
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="p-4 flex flex-col flex-grow justify-between">
                        <div class="mb-3">
                            <p class="text-xs font-bold text-white line-clamp-1 mb-1" title="<?= htmlspecialchars($photo['judul']) ?>">
                                <?= htmlspecialchars($photo['judul']) ?>
                            </p>
                            <span class="text-[10px] text-white/50 block">
                                📅 <?= date('d M Y', strtotime($photo['tanggal_upload'])) ?>
                            </span>
                        </div>

                        <a href="download.php?file=<?= urlencode($photo['file_hd']) ?>&type=HD" download class="liquid-btn w-full py-2 text-xs font-bold gap-1.5 flex items-center justify-center">
                            <i class="fa fa-download text-[10px]"></i>
                            <span>Unduh Foto</span>
                        </a>
                    </div>
                </div>
                <?php 
                    $idx++;
                endwhile; ?>
            <?php else: ?>
                <div class="col-span-full liquid-glass p-12 text-center text-white/50 text-sm">
                    Belum ada foto yang diunggah ke album ini.
                </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- Lightbox Modal -->
    <div id="lightbox-modal" class="fixed inset-0 z-50 bg-black/95 backdrop-blur-2xl hidden flex flex-col items-center justify-between p-4 sm:p-6" onclick="closeLightbox()">
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

        <div class="relative flex-1 w-full flex items-center justify-center my-4 overflow-hidden" onclick="event.stopPropagation()">
            <button onclick="lightboxPrev()" class="lightbox-nav-btn absolute left-2 sm:left-4 z-20 w-11 h-11 rounded-full flex items-center justify-center text-lg">
                <i class="fa fa-chevron-left"></i>
            </button>
            <img id="lightbox-img" src="" alt="Photo" class="max-w-full max-h-[75vh] object-contain rounded-2xl shadow-2xl">
            <button onclick="lightboxNext()" class="lightbox-nav-btn absolute right-2 sm:right-4 z-20 w-11 h-11 rounded-full flex items-center justify-center text-lg">
                <i class="fa fa-chevron-right"></i>
            </button>
        </div>

        <div class="text-center text-xs text-white/40 pb-2" onclick="event.stopPropagation()">
            <span id="lightbox-counter">1 / 1</span> • Gunakan tombol keyboard ← → untuk navigasi
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
        const galleryItems = <?= json_encode($photo_items_json) ?>;
        let currentLightboxIndex = 0;

        document.addEventListener('DOMContentLoaded', () => {
            AOS.init({ duration: 800, once: true });
        });

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
            if (currentLightboxIndex > 0) currentLightboxIndex--;
            else currentLightboxIndex = galleryItems.length - 1;
            renderLightboxItem();
        }

        function lightboxNext() {
            if (currentLightboxIndex < galleryItems.length - 1) currentLightboxIndex++;
            else currentLightboxIndex = 0;
            renderLightboxItem();
        }

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
