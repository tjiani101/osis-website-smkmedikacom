<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

// Cek role user
$allowed_roles = ['admin', 'dev', 'DEV'];
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?msg=" . urlencode("Silakan login sebagai admin terlebih dahulu."));
        exit;
    }
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 - Akses Ditolak | Admin Panel</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    </head>
    <body class="bg-[#080d1a] text-white flex items-center justify-center min-h-screen p-4 font-['Plus_Jakarta_Sans',sans-serif]">
        <div class="max-w-md w-full bg-white/[0.04] border border-white/10 rounded-3xl p-8 text-center backdrop-blur-xl shadow-2xl">
            <div class="w-16 h-16 bg-red-500/20 text-red-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                ⚠️
            </div>
            <h1 class="text-2xl font-extrabold mb-2">Akses Terbatas</h1>
            <p class="text-white/60 text-sm mb-6 leading-relaxed">
                Akun Anda (<strong class="text-white"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></strong>) memiliki role <span class="px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 font-semibold"><?= htmlspecialchars($_SESSION['role'] ?? 'Guest') ?></span>. Halaman Admin Panel hanya diperuntukkan bagi Administrator & Developer.
            </p>
            <div class="flex gap-3 justify-center">
                <a href="index.php" class="px-5 py-2.5 rounded-full bg-blue-600 hover:bg-blue-500 text-sm font-bold transition-all shadow-lg shadow-blue-600/30">Ke Forum</a>
                <a href="logout.php" class="px-5 py-2.5 rounded-full bg-white/10 hover:bg-white/20 text-sm font-bold transition-all">Ganti Akun</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Fetch current admin user
$admin_id = $_SESSION['user_id'];
$admin_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin_data = $admin_stmt->get_result()->fetch_assoc();

$page = $_GET['page'] ?? 'directory';

// Navigation items definition
$nav_items = [
    'overview' => [
        'title' => 'UTAMA',
        'items' => [
            'directory' => ['label' => 'Dashboard Overview', 'icon' => 'fa-gauge-high', 'badge' => null],
        ]
    ],
    'content' => [
        'title' => 'MANAJEMEN KONTEN',
        'items' => [
            'databases' => ['label' => 'Topik & Komentar', 'icon' => 'fa-comments', 'badge' => null],
            'galery' => ['label' => 'Galeri & Album', 'icon' => 'fa-photo-film', 'badge' => null],
            'files' => ['label' => 'Media & Storage', 'icon' => 'fa-folder-open', 'badge' => null],
            'announcements' => ['label' => 'Broadcast Banner', 'icon' => 'fa-bullhorn', 'badge' => 'New'],
        ]
    ],
    'membership' => [
        'title' => 'PENGGUNA & PENDAFTARAN',
        'items' => [
            'users' => ['label' => 'Kelola Pengguna', 'icon' => 'fa-users-gear', 'badge' => null],
            'eskul' => ['label' => 'Pendaftaran Eskul', 'icon' => 'fa-volleyball', 'badge' => null],
            'osis' => ['label' => 'Pendaftaran OSIS', 'icon' => 'fa-id-card-clip', 'badge' => null],
            'lomba_ramadhan' => ['label' => 'Event Ramadhan', 'icon' => 'fa-star', 'badge' => 'Event'],
        ]
    ],
    'system' => [
        'title' => 'SISTEM & AUDIT',
        'items' => [
            'log' => ['label' => 'Audit Log & Terminal', 'icon' => 'fa-terminal', 'badge' => null],
        ]
    ]
];

// Determine active label
$page_label = 'Dashboard Overview';
foreach ($nav_items as $group) {
    if (isset($group['items'][$page])) {
        $page_label = $group['items'][$page]['label'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_label) ?> | Admin OSIS Medikacom</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#080d1a',
                        },
                        brand: {
                            blue: '#3b82f6',
                            indigo: '#6366f1',
                            purple: '#a855f7',
                            cyan: '#06b6d4',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #080d1a;
            color: #e2e8f0;
        }
        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.6);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        /* Glass effect */
        .admin-glass {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .admin-glass-subtle {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .admin-glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.25rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .admin-glass-card:hover {
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 10px 30px -10px rgba(99, 102, 241, 0.15);
        }
        .text-gradient {
            background: linear-gradient(135deg, #60a5fa 0%, #a855f7 50%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row antialiased selection:bg-indigo-500/30 selection:text-white">

    <!-- Mobile Top Navigation Bar -->
    <header class="md:hidden flex items-center justify-between px-4 py-3.5 admin-glass sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <button id="mobile-toggle" class="p-2 rounded-xl bg-white/5 border border-white/10 text-white hover:bg-white/10">
                <i class="fa fa-bars text-lg"></i>
            </button>
            <div class="flex items-center gap-2">
                <img src="../../images/logo.png" alt="Logo" class="h-7 w-auto" onerror="this.onerror=null;this.src='https://placehold.co/100x100/ffffff/000000?text=OSIS';">
                <span class="font-bold text-sm text-white tracking-wide">ADMIN OSIS</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold">
                Online
            </span>
            <a href="logout.php" class="p-2 rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20" title="Logout">
                <i class="fa fa-power-off"></i>
            </a>
        </div>
    </header>

    <!-- Sidebar Backdrop for Mobile -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden md:hidden"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed md:sticky top-0 left-0 h-screen w-72 shrink-0 admin-glass flex flex-col z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out border-r border-white/10">
        
        <!-- Sidebar Brand -->
        <div class="p-5 border-b border-white/10 flex items-center justify-between">
            <a href="?page=directory" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 p-0.5 shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                    <div class="w-full h-full bg-[#0b1021] rounded-[14px] flex items-center justify-center">
                        <img src="../../images/logo.png" alt="Logo" class="h-6 w-auto drop-shadow" onerror="this.onerror=null;this.src='https://placehold.co/100x100/ffffff/000000?text=OSIS';">
                    </div>
                </div>
                <div>
                    <h1 class="text-sm font-extrabold text-white leading-tight tracking-wider uppercase">PANEL PENGURUS</h1>
                    <p class="text-[11px] text-blue-400 font-medium">OSIS SMK Medikacom</p>
                </div>
            </a>
            <button id="close-sidebar" class="md:hidden text-white/60 hover:text-white p-1">
                <i class="fa fa-times text-lg"></i>
            </button>
        </div>

        <!-- Sidebar Navigation -->
        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-6">
            <?php foreach ($nav_items as $group): ?>
                <div>
                    <p class="px-3 text-[10px] font-extrabold text-white/40 tracking-wider uppercase mb-2">
                        <?= $group['title'] ?>
                    </p>
                    <nav class="space-y-1">
                        <?php foreach ($group['items'] as $item_key => $item): 
                            $is_active = ($page === $item_key);
                        ?>
                            <a href="?page=<?= $item_key ?>" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all group <?= $is_active ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-indigo-600/25' : 'text-white/70 hover:text-white hover:bg-white/[0.06]' ?>">
                                <div class="flex items-center gap-3">
                                    <i class="fa <?= $item['icon'] ?> text-sm w-4 text-center <?= $is_active ? 'text-white' : 'text-white/50 group-hover:text-blue-400 transition-colors' ?>"></i>
                                    <span><?= $item['label'] ?></span>
                                </div>
                                <?php if ($item['badge']): ?>
                                    <span class="text-[9px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider <?= $is_active ? 'bg-white/20 text-white' : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' ?>">
                                        <?= $item['badge'] ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            <?php endforeach; ?>

            <!-- Public Site Quick Links -->
            <div>
                <p class="px-3 text-[10px] font-extrabold text-white/40 tracking-wider uppercase mb-2">
                    NAVIGASI SITUS
                </p>
                <div class="space-y-1">
                    <a href="index.php" target="_blank" class="flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-medium text-white/60 hover:text-white hover:bg-white/[0.04] transition-all">
                        <div class="flex items-center gap-3">
                            <i class="fa fa-comments text-xs text-white/40 w-4 text-center"></i>
                            <span>Buka Forum</span>
                        </div>
                        <i class="fa fa-arrow-up-right-from-square text-[10px] text-white/30"></i>
                    </a>
                    <a href="../../gallery/" target="_blank" class="flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-medium text-white/60 hover:text-white hover:bg-white/[0.04] transition-all">
                        <div class="flex items-center gap-3">
                            <i class="fa fa-images text-xs text-white/40 w-4 text-center"></i>
                            <span>Buka Galeri</span>
                        </div>
                        <i class="fa fa-arrow-up-right-from-square text-[10px] text-white/30"></i>
                    </a>
                    <a href="../../index.html" target="_blank" class="flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-medium text-white/60 hover:text-white hover:bg-white/[0.04] transition-all">
                        <div class="flex items-center gap-3">
                            <i class="fa fa-school text-xs text-white/40 w-4 text-center"></i>
                            <span>Beranda OSIS</span>
                        </div>
                        <i class="fa fa-arrow-up-right-from-square text-[10px] text-white/30"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Sidebar User Footer -->
        <div class="p-4 border-t border-white/10 bg-black/20">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 p-0.5 shrink-0">
                        <div class="w-full h-full rounded-full bg-[#0b1021] flex items-center justify-center overflow-hidden font-bold text-xs text-indigo-300">
                            <?= strtoupper(substr($admin_data['username'] ?? 'A', 0, 1)) ?>
                        </div>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($admin_data['username'] ?? 'Administrator') ?></p>
                        <span class="inline-flex items-center text-[10px] font-semibold text-emerald-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1 animate-pulse"></span>
                            <?= strtoupper($admin_data['role'] ?? 'ADMIN') ?>
                        </span>
                    </div>
                </div>
                <a href="logout.php" class="p-2 rounded-xl text-white/40 hover:text-red-400 hover:bg-red-500/10 transition-colors" title="Keluar / Logout">
                    <i class="fa fa-arrow-right-from-bracket text-sm"></i>
                </a>
            </div>
        </div>

    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 min-w-0 flex flex-col min-h-screen">
        
        <!-- Desktop Header Bar -->
        <header class="hidden md:flex items-center justify-between px-8 py-4 admin-glass sticky top-0 z-30 border-b border-white/10">
            <div class="flex items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-white/40 mb-0.5">
                        <a href="?page=directory" class="hover:text-white transition-colors">Admin Panel</a>
                        <span>/</span>
                        <span class="text-white/80 font-medium"><?= htmlspecialchars($page_label) ?></span>
                    </div>
                    <h2 class="text-xl font-bold text-white"><?= htmlspecialchars($page_label) ?></h2>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <!-- Server Clock Widget -->
                <div class="hidden lg:flex items-center gap-2 px-3.5 py-1.5 rounded-full admin-glass-subtle text-xs text-white/70">
                    <i class="fa-regular fa-clock text-blue-400"></i>
                    <span id="live-clock" class="font-mono-code font-semibold"><?= date('H:i:s') ?> WIB</span>
                </div>

                <!-- Fast Nav Pills -->
                <div class="flex items-center gap-2">
                    <a href="index.php" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white/5 hover:bg-white/10 text-white/80 border border-white/10 transition-all flex items-center gap-2">
                        <i class="fa fa-comments text-blue-400"></i>
                        <span>Forum</span>
                    </a>
                    <a href="../../gallery/" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white/5 hover:bg-white/10 text-white/80 border border-white/10 transition-all flex items-center gap-2">
                        <i class="fa fa-images text-purple-400"></i>
                        <span>Galeri</span>
                    </a>
                    <a href="logout.php" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-red-500/10 hover:bg-red-500/20 text-red-300 border border-red-500/20 transition-all flex items-center gap-2">
                        <i class="fa fa-power-off"></i>
                        <span>Keluar</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Dynamic Content Ingestion -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <?php
                $valid_pages = [
                    'directory' => 'pages/directory.php',
                    'files' => 'pages/files.php',
                    'databases' => 'pages/databases.php',
                    'users' => 'pages/users.php',
                    'eskul' => 'pages/eskul.php',
                    'osis' => 'pages/osis.php',
                    'galery' => 'pages/galery.php',
                    'announcements' => 'pages/announcements.php',
                    'log' => 'pages/log.php',
                    'lomba_ramadhan' => 'lomba/lomba_ramadhan.php',
                ];

                if (isset($valid_pages[$page]) && file_exists(__DIR__ . '/' . $valid_pages[$page])) {
                    include __DIR__ . '/' . $valid_pages[$page];
                } else {
                    ?>
                    <div class="admin-glass-card p-12 text-center max-w-lg mx-auto mt-12">
                        <div class="w-16 h-16 rounded-full bg-red-500/20 text-red-400 flex items-center justify-center mx-auto mb-4 text-2xl">
                            <i class="fa fa-triangle-exclamation"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-white mb-2">Halaman Tidak Ditemukan</h2>
                        <p class="text-white/60 text-sm mb-6">Modul "<?= htmlspecialchars($page) ?>" tidak tersedia atau sedang dalam perbaikan.</p>
                        <a href="?page=directory" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg transition-all">
                            <i class="fa fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    </div>
                    <?php
                }
                ?>
            </div>
        </main>

        <!-- Admin Footer -->
        <footer class="p-6 border-t border-white/5 text-center text-xs text-white/40">
            <p>&copy; <?= date('Y') ?> OSIS & MPK SMK Medikacom Bandung • Admin Panel v2.5.0</p>
        </footer>

    </div>

    <!-- Interactive Scripts -->
    <script>
        // Sidebar Toggle for Mobile
        const mobileToggle = document.getElementById('mobile-toggle');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const closeSidebar = document.getElementById('close-sidebar');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
        }
        function hideSidebar() {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
        }

        if (mobileToggle) mobileToggle.addEventListener('click', openSidebar);
        if (closeSidebar) closeSidebar.addEventListener('click', hideSidebar);
        if (backdrop) backdrop.addEventListener('click', hideSidebar);

        // Live Clock
        setInterval(() => {
            const now = new Date();
            const clockEl = document.getElementById('live-clock');
            if (clockEl) {
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                clockEl.textContent = `${hours}:${minutes}:${seconds} WIB`;
            }
        }, 1000);
    </script>
</body>
</html>
