<?php
session_start();
require_once 'db.php';

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

// Get filters from URL
$forum_id = isset($_GET['forum_id']) ? intval($_GET['forum_id']) : null;
$popular = isset($_GET['popular']) ? true : false;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

// Base SQL
$sql = "
    SELECT t.*, u.username, 
           COALESCE(SUM(v.vote_type), 0) AS vote_count,
           (SELECT COUNT(*) FROM comments c WHERE c.thread_id = t.id) AS comment_count
    FROM threads t 
    JOIN users u ON t.user_id = u.id 
    LEFT JOIN votes v ON t.id = v.thread_id 
";

// Add WHERE conditions
$conditions = [];
if ($forum_id) {
    $conditions[] = "t.forum_id = $forum_id";
}
if ($search) {
    $searchSafe = $conn->real_escape_string($search);
    $conditions[] = "t.judul LIKE '%$searchSafe%'";
}
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Grouping and sorting
$sql .= " GROUP BY t.id ";
$sql .= $popular ? "ORDER BY vote_count DESC" : "ORDER BY t.created_at DESC";

$threads = $conn->query($sql);

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

    $notifications = $notif_query->fetch_all(MYSQLI_ASSOC);

    // Get unread count
    $unread_result = $conn->query("
        SELECT COUNT(*) AS count 
        FROM notifications 
        WHERE user_id = $user_id AND is_read = 0
    ");
    $unread_count = $unread_result->fetch_assoc()['count'];
}

$forum_names = [
    1 => 'Umum',
    2 => 'Sekolah',
    3 => 'Curhat',
    4 => 'Pembelajaran'
];
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum OSIS & MPK - SMK MEDIKACOM</title>
    
    <!-- Meta OG Tags -->
    <meta property="og:title" content="Forum OSIS & MPK SMK Medikacom" />
    <meta property="og:description" content="Forum diskusi resmi OSIS & MPK SMK Medikacom" />
    <meta property="og:image" content="images/logo.png" />
    <meta property="og:url" content="https://osis-medcom.web.id/forum" />
    <meta property="og:type" content="website" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Animate On Scroll (AOS) Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        .background-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: -1;
            /* Use a relevant background image for the gallery */
            background-image: url('IMG.jpg'); 
            background-size: cover;
            background-position: center;
            filter: blur(8px);
            transform: scale(1.1);
        }
        .background-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: -1;
            background-color: rgba(10, 10, 20, 0.7);
        }
        /* Glassmorphism Effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 1.5rem;
        }
        
        .thread-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .thread-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #3b82f6, #60a5fa);
        }
        
        .thread-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            border-color: #60a5fa;
        }
        
        /* Neon Glow Effects */
        .neon-glow-button {
            transition: all 0.3s ease;
            box-shadow: 0 0 5px rgba(59, 130, 246, 0), 0 0 10px rgba(59, 130, 246, 0);
        }
        
        .neon-glow-button:hover {
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.6), 0 0 25px rgba(37, 99, 235, 0.4);
            transform: translateY(-2px);
        }
        
        .neon-text-glow-hover:hover {
            text-shadow: 0 0 8px rgba(96, 165, 250, 0.7);
        }
        
        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        /* Vote Buttons */
        .vote-btn {
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .upvote:hover {
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
            transform: scale(1.1);
        }
        
        .downvote:hover {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            transform: scale(1.1);
        }
        
        /* Animation for thread hover */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .thread-card {
            animation: fadeIn 0.5s ease forwards;
        }
        
        /* Background Elements */
        .background-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: -1;
            background-image: url('IMG.jpg');
            background-size: cover;
            background-position: center;
            filter: blur(8px);
            transform: scale(1.1);
        }
        
        .background-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: -1;
            background-color: rgba(10, 10, 20, 0.7);
        }
    </style>
</head>
<body class="text-white overflow-x-hidden bg-black min-h-screen">
    <!-- Background Elements -->
    <div class="background-container"></div>
    <div class="background-overlay"></div>
    
    <!-- Header & Navbar -->
    <header id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
        <nav class="container mx-auto px-4 md:px-6 py-3">
            <div class="flex items-center justify-between glass-card p-4 rounded-2xl">
                <a href="/" class="flex shrink-0 items-center">
                    <img class="h-9 w-auto mr-2" src="images/logo.png" alt="Logo OSIS MPK"> 
                    <span class="text-slate-100 text-sm font-semibold leading-4">OSIS & MPK<br>SMK MEDIKACOM</span>
                </a>
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/" class="neon-text-glow-hover transition-colors">Beranda</a>
                    <a href="#ai-assistant" class="neon-text-glow-hover transition-colors">Asisten AI</a>
                    <a href="event.php" class="neon-text-glow-hover transition-colors">Event</a>
                    <a href="gallery.php" class="neon-text-glow-hover transition-colors">Gallery</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="relative group">
                            <button class="neon-text-glow-hover flex items-center gap-1">
                                Forum
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div class="absolute hidden group-hover:block mt-2 w-48 rounded-lg shadow-lg glass-card z-50">
                                <div class="py-1">
                                    <a href="index.php?forum_id=1" class="block px-4 py-2 text-sm hover:bg-blue-600/20">Umum</a>
                                    <a href="index.php?forum_id=2" class="block px-4 py-2 text-sm hover:bg-blue-600/20">Sekolah</a>
                                    <a href="index.php?forum_id=3" class="block px-4 py-2 text-sm hover:bg-blue-600/20">Curhat</a>
                                    <a href="index.php?forum_id=4" class="block px-4 py-2 text-sm hover:bg-blue-600/20">Pembelajaran</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Notification -->
                        <div class="relative">
                            <button id="notification-toggle" class="relative p-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?= $unread_count ?></span>
                                <?php endif; ?>
                            </button>
                            <!-- Notification Dropdown -->
                            <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 glass-card rounded-xl shadow-lg z-50">
                                <div class="p-4 border-b border-white/10">
                                    <h3 class="font-bold text-lg">Notifikasi</h3>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    <?php if (!empty($notifications)): ?>
                                        <?php foreach ($notifications as $notif): ?>
                                            <div class="notification-item p-4 border-b border-white/10 hover:bg-blue-600/10 cursor-pointer <?= $notif['is_read'] ? '' : 'bg-blue-500/10' ?>" data-id="<?= $notif['id'] ?>">
                                                <div class="text-sm"><?= $notif['message'] ?></div>
                                                <div class="text-xs text-gray-400 mt-1">
                                                    <?= date('d M Y H:i', strtotime($notif['created_at'])) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-4 text-center text-gray-400">
                                            Tidak ada notifikasi
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3 border-t border-white/10 flex justify-between">
                                    <a href="notifications.php" class="text-blue-400 text-sm hover:underline">Lihat Semua</a>
                                    <button class="mark-all-read text-blue-400 text-sm hover:underline">Tandai Sudah Dibaca</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Dropdown -->
                        <div class="relative group">
                            <button class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center">
                                    <?= substr($_SESSION['username'], 0, 1) ?>
                                </div>
                                <span class="hidden md:inline"><?= $_SESSION['username'] ?></span>
                            </button>
                            <div class="absolute hidden group-hover:block right-0 mt-2 w-48 rounded-lg shadow-lg glass-card z-50">
                                <div class="py-1">
                                    <a href="profile.php" class="block px-4 py-2 text-sm hover:bg-blue-600/20">Profile</a>
                                    <a href="buat_post.php" class="block px-4 py-2 text-sm hover:bg-blue-600/20">Buat Post</a>
                                    <a href="logout.php" class="block px-4 py-2 text-sm hover:bg-blue-600/20">Logout</a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="text-blue-400 hover:text-blue-300">Login</a>
                        <a href="register.php" class="bg-blue-600 text-white px-4 py-2 rounded-full font-semibold neon-glow-button">
                            Register
                        </a>
                    <?php endif; ?>
                </div>
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-button" class="md:hidden p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </button>
            </div>
        </nav>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden container mx-auto px-4 md:px-6 pb-4">
            <div class="glass-card p-4 rounded-2xl flex flex-col space-y-4 text-center">
                <a href="/" class="mobile-menu-link hover:text-blue-300 py-2">Beranda</a>
                <a href="#ai-assistant" class="mobile-menu-link hover:text-blue-300 py-2">Asisten AI</a>
                <a href="event.php" class="mobile-menu-link hover:text-blue-300 py-2">Event</a>
                <a href="gallery.php" class="mobile-menu-link hover:text-blue-300 py-2">Gallery</a>
                <div class="border-t border-white/10 pt-4">
                    <h4 class="font-semibold mb-2">Forum Kategori</h4>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="index.php?forum_id=1" class="py-2 bg-blue-600/20 rounded-lg">Umum</a>
                        <a href="index.php?forum_id=2" class="py-2 bg-blue-600/20 rounded-lg">Sekolah</a>
                        <a href="index.php?forum_id=3" class="py-2 bg-blue-600/20 rounded-lg">Curhat</a>
                        <a href="index.php?forum_id=4" class="py-2 bg-blue-600/20 rounded-lg">Pembelajaran</a>
                    </div>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="buat_post.php" class="mobile-menu-link bg-blue-600/50 rounded-lg py-2">Buat Post</a>
                <?php else: ?>
                    <div class="flex gap-2">
                        <a href="login.php" class="mobile-menu-link bg-blue-600/50 rounded-lg py-2 flex-1">Login</a>
                        <a href="register.php" class="mobile-menu-link bg-blue-600 rounded-lg py-2 flex-1">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 md:px-6 pt-24 pb-16">
        <!-- Hero Section -->
        <section class="py-8 md:py-12 text-center" data-aos="fade-up">
            <h1 class="text-4xl sm:text-5xl font-bold mb-4 bg-gradient-to-r from-blue-500 via-cyan-400 to-blue-500 bg-clip-text text-transparent">
                Forum OSIS & MPK
            </h1>
            <p class="text-white/70 max-w-2xl mx-auto">
                Diskusi, berbagi ide, dan berkolaborasi untuk membuat SMK Medikacom lebih baik
            </p>
        </section>

        <!-- Forum Content -->
        <section class="glass-card p-6 rounded-2xl mb-8">
            <!-- Category Filter -->
            <div class="flex flex-wrap gap-3 mb-6">
                <a href="index.php" class="px-4 py-2 rounded-full <?= !$forum_id ? 'bg-blue-600 text-white' : 'bg-white/10 hover:bg-white/20' ?>">
                    Semua
                </a>
                <?php foreach ($forum_names as $id => $name): ?>
                    <a href="index.php?forum_id=<?= $id ?>" class="px-4 py-2 rounded-full <?= $forum_id == $id ? 'bg-blue-600 text-white' : 'bg-white/10 hover:bg-white/20' ?>">
                        <?= $name ?>
                    </a>
                <?php endforeach; ?>
                <a href="index.php?popular=1" class="px-4 py-2 rounded-full <?= $popular ? 'bg-blue-600 text-white' : 'bg-white/10 hover:bg-white/20' ?> ml-auto">
                    Terpopuler
                </a>
            </div>

            <!-- Search Bar -->
            <form method="GET" action="index.php" class="mb-8">
                <?php if ($forum_id): ?>
                    <input type="hidden" name="forum_id" value="<?= $forum_id ?>">
                <?php endif; ?>
                <div class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Cari topik..." 
                        value="<?= htmlspecialchars($search ?? '') ?>" 
                        class="w-full bg-white/5 border border-white/20 rounded-full px-6 py-3 text-white focus:ring-2 focus:ring-blue-400 focus:outline-none pl-12"
                    >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 absolute left-4 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <button type="submit" class="absolute right-2 top-1.5 bg-blue-600 text-white px-4 py-1.5 rounded-full font-medium text-sm">
                        Cari
                    </button>
                </div>
            </form>

            <!-- Thread List -->
            <div class="space-y-6">
                <?php if ($threads->num_rows > 0): ?>
                    <?php while($row = $threads->fetch_assoc()): ?>
                        <div class="thread-card glass-card p-6" data-aos="fade-up" data-thread-id="<?= $row['id'] ?>">
                            <div class="flex gap-4">
                                <!-- Voting -->
                                <div class="flex flex-col items-center">
                                    <button class="vote-btn upvote">↑</button>
                                    <span class="vote-count font-bold text-lg my-1"><?= $row['vote_count'] ?></span>
                                    <button class="vote-btn downvote">↓</button>
                                </div>
                                
                                <!-- Content -->
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="text-xs px-2 py-1 bg-blue-600/30 rounded-full">
                                            <?= $forum_names[$row['forum_id']] ?? 'Umum' ?>
                                        </span>
                                        <span class="text-sm text-gray-400">
                                            <?= date('d M Y H:i', strtotime($row['created_at'])) ?>
                                        </span>
                                    </div>
                                    
                                    <h3 class="text-xl font-bold mb-3">
                                        <a href="thread.php?id=<?= $row['id'] ?>" class="hover:text-blue-300">
                                            <?= htmlspecialchars($row['judul']) ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="text-gray-300 mb-4 line-clamp-2">
                                        <?= htmlspecialchars($row['konten']) ?>
                                    </div>
                                    
                                    <!-- Media -->
                                    <?php if (!empty($row['gambar'])): ?>
                                        <div class="mb-4">
                                            <?php if (isset($row['media_type']) && $row['media_type'] == 'video'): ?>
                                                <video controls class="max-h-80 rounded-xl">
                                                    <source src="post/<?= htmlspecialchars($row['gambar']) ?>" type="video/mp4">
                                                </video>
                                            <?php else: ?>
                                                <img 
                                                    src="post/<?= htmlspecialchars($row['gambar']) ?>" 
                                                    alt="Post image" 
                                                    class="max-h-80 rounded-xl object-cover"
                                                >
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Footer -->
                                    <div class="flex flex-wrap items-center gap-4 pt-3 border-t border-white/10">
                                        <a href="users.php?username=<?= htmlspecialchars($row['username']) ?>" class="flex items-center gap-2 hover:text-blue-300">
                                            <div class="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center text-xs">
                                                <?= substr($row['username'], 0, 1) ?>
                                            </div>
                                            <?= htmlspecialchars($row['username']) ?>
                                        </a>
                                        
                                        <div class="flex items-center gap-1 text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            <?= $row['comment_count'] ?> Komentar
                                        </div>
                                        
                                        <a href="thread.php?id=<?= $row['id'] ?>" class="ml-auto text-blue-400 hover:text-blue-300 text-sm">
                                            Baca selengkapnya →
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-12">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-xl font-medium mt-4">Tidak ada diskusi ditemukan</h3>
                        <p class="text-gray-400 mt-2">Jadilah yang pertama memulai diskusi di forum ini</p>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="buat_post.php" class="mt-4 inline-block bg-blue-600 text-white px-6 py-2 rounded-full font-medium">
                                Buat Diskusi Baru
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="mt-8">
        <div class="container mx-auto px-4 md:px-6 py-8">
            <div class="glass-card p-8 rounded-2xl">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:text-left">
                    <div>
                        <h3 class="text-xl font-bold mb-4 text-blue-300">OSIS & MPK MEDIKACOM</h3>
                        <p class="text-white/60">Jl. Rancabolang – Soekarno Hatta, No. 10B, Bandung – Jawa Barat</p>
                        <p class="text-white/60 mt-2">mail@osis-medcom.web.id</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Tautan Penting</h3>
                        <ul class="space-y-2 text-white/80">
                            <li><a href="#home" class="hover:text-blue-300">Beranda</a></li>
                            <li><a href="#struktur" class="hover:text-blue-300">Struktur</a></li>
                            <li><a href="#gallery" class="hover:text-blue-300">Galeri</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Ikuti Kami</h3>
                        <div class="flex justify-center md:justify-start space-x-4">
                            <a href="#" class="text-white/80 hover:text-blue-300">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919A48.904 48.904 0 0 1 12 2.163m0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.162 6.162 6.162 6.162-2.759 6.162-6.162-2.759-6.162-6.162-6.162zM12 16c-2.209 0-4-1.791-4-4s1.791-4 4-4 4 1.791 4 4-1.791 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44s-1.44-.645-1.44-1.44.646-1.44 1.44-1.44 1.44.645 1.44 1.44z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="border-t border-white/10 mt-8 pt-8 text-center text-white/50 text-sm">
                    &copy; <?= date('Y') ?> osis-medcom.web.id All Rights Reserved. Terms & Privacy Policy
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize AOS
            AOS.init({ duration: 800, once: true, offset: 50 });
            
            // Mobile menu toggle
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            
            mobileMenuButton.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
            
            // Notification toggle
            const notificationToggle = document.getElementById('notification-toggle');
            const notificationDropdown = document.getElementById('notification-dropdown');
            
            if (notificationToggle) {
                notificationToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    notificationDropdown.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', (e) => {
                    if (!notificationDropdown.contains(e.target) {
                        notificationDropdown.classList.add('hidden');
                    }
                });
            }
            
            // Voting system
            document.querySelectorAll('.upvote, .downvote').forEach(button => {
                button.addEventListener('click', async function(e) {
                    e.stopPropagation();
                    const thread = this.closest('.thread-card');
                    const threadId = thread.dataset.threadId;
                    const isUpvote = this.classList.contains('upvote');
                    
                    try {
                        const response = await fetch('vote.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `thread_id=${threadId}&vote_type=${isUpvote ? 1 : -1}`
                        });
                        
                        const result = await response.json();
                        
                        if(result.success) {
                            thread.querySelector('.vote-count').textContent = result.vote_count;
                        } else {
                            alert(result.error);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                });
            });
            
            // Mark notification as read
            document.querySelectorAll('.notification-item').forEach(item => {
                item.addEventListener('click', function() {
                    const notifId = this.dataset.id;
                    fetch('mark_notification_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${notifId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.remove('bg-blue-500/10');
                            updateUnreadCount();
                        }
                    });
                });
            });
            
            // Mark all as read
            const markAllRead = document.querySelector('.mark-all-read');
            if (markAllRead) {
                markAllRead.addEventListener('click', function() {
                    fetch('mark_all_read.php', {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.querySelectorAll('.notification-item').forEach(item => {
                                item.classList.remove('bg-blue-500/10');
                            });
                            updateUnreadCount();
                        }
                    });
                });
            }
            
            // Update unread notification count
            function updateUnreadCount() {
                fetch('get_unread_count.php')
                    .then(response => response.json())
                    .then(data => {
                        const badge = document.querySelector('.notification-badge');
                        if (data.count > 0) {
                            if (!badge) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'notification-badge';
                                newBadge.textContent = data.count;
                                document.getElementById('notification-toggle').appendChild(newBadge);
                            } else {
                                badge.textContent = data.count;
                            }
                        } else if (badge) {
                            badge.remove();
                        }
                    });
            }
        });
    </script>
</body>
</html>