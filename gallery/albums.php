<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get user info for permissions
$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

// Get albums with their cover images
$albums_query = "
    SELECT a.*, 
           COALESCE(
               a.thumbnail,
               (SELECT p.file_hd FROM photos p WHERE p.album_id = a.id ORDER BY p.id DESC LIMIT 1)
           ) as cover_image,
           (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id) as photo_count
    FROM albums a
    ORDER BY a.created_at DESC
";
$albums = mysqli_query($conn, $albums_query);
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Album Foto - Galeri OSIS SMK MEDIKACOM</title>
    
    <!-- Meta OG Tags -->
    <meta property="og:title" content="Album Galeri OSIS SMK Medikacom" />
    <meta property="og:description" content="Koleksi Album Foto Kegiatan OSIS & MPK SMK Medikacom" />
    <meta property="og:image" content="images/logo.png" />
    <meta property="og:url" content="https://osis-medcom.web.id/galery/albums.php" />
    <meta property="og:type" content="website" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Animate On Scroll (AOS) Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0a0a0a;
            overflow-y: scroll; 
        }

        .background-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: -1;
            /* Use a relevant background image for the gallery */
            background-image: url('..//images/slider/IMG_6393.jpg'); 
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

        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 1.5rem;
        }

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

        .gradient-text {
            background: linear-gradient(to right, #3b82f6, #22d3ee);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .loader {
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-left-color: #3b82f6;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .album-card {
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .album-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .album-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(to right, #3b82f6, #22d3ee);
            z-index: 1;
        }
    </style>
</head>
<body class="text-white overflow-x-hidden">
    
    <!-- Loading Screen -->
    <div id="loading-screen" class="fixed inset-0 bg-black z-[100] flex items-center justify-center transition-opacity duration-500">
        <div class="loader"></div>
    </div>

    <!-- Background Elements -->
    <div class="background-container"></div>
    <div class="background-overlay"></div>

    <!-- Main Content -->
    <div id="main-content" class="opacity-0 transition-opacity duration-1000">
        <!-- Header & Navbar -->
        <header id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
            <nav class="container mx-auto px-4 md:px-6 py-3">
                <div class="flex items-center justify-between glass-card p-4 rounded-2xl">
                    <a href="../index.html" class="flex shrink-0 items-center">
                        <img class="h-9 w-auto mr-2" src="../images/logo.png" alt="Logo OSIS MPK"> 
                        <span class="text-slate-100 text-sm font-semibold leading-4">OSIS & MPK<br>SMK MEDIKACOM</span>
                    </a>
                    <!-- Desktop Menu -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="../index.html#home" class="neon-text-glow-hover transition-colors">Beranda</a>
                        <a href="index.php" class="neon-text-glow-hover transition-colors">Dashboard</a>
                        <a href="galery.php" class="neon-text-glow-hover transition-colors">Galeri</a>
                        <a href="albums.php" class="neon-text-glow-hover transition-colors">Album</a>
                    </div>
                    <div class="hidden md:flex items-center space-x-4">
                        <span class="text-blue-300"><?= $user['username'] ?></span>
                        <a href="../pages/forum/logout.php" class="bg-blue-600 text-white px-6 py-2 rounded-full font-semibold neon-glow-button">
                            Logout
                        </a>
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
                    <a href="../index.html#home" class="mobile-menu-link hover:text-blue-300 py-2">Beranda</a>
                    <a href="index.php" class="mobile-menu-link hover:text-blue-300 py-2">Dashboard</a>
                    <a href="galery.php" class="mobile-menu-link hover:text-blue-300 py-2">Galeri</a>
                    <a href="albums.php" class="mobile-menu-link hover:text-blue-300 py-2">Album</a>
                    <div class="pt-4 border-t border-white/10">
                        <span class="text-blue-300 block py-2"><?= $user['username'] ?></span>
                        <a href="../logout.php" class="mobile-menu-link bg-blue-600/50 rounded-lg py-2">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="container mx-auto px-4 md:px-6 pt-24 pb-12">
            <!-- Albums Section -->
            <section id="albums" class="py-8 md:py-12">
                <div class="text-center mb-8" data-aos="fade-up">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">
                        <span class="gradient-text">Album Foto</span> OSIS
                    </h1>
                    <p class="text-lg text-white/80 max-w-2xl mx-auto">
                        Koleksi album foto kegiatan OSIS & MPK SMK Medikacom
                    </p>
                </div>

                <?php if($user['role'] === 'admin'): ?>
                <div class="flex justify-end mb-6" data-aos="fade-left">
                    <a href="create_album.php" class="bg-blue-600 text-white px-6 py-3 rounded-full font-semibold neon-glow-button flex items-center gap-2">
                        <i class="fas fa-plus"></i> Buat Album Baru
                    </a>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if(mysqli_num_rows($albums) > 0): ?>
                        <?php while($album = mysqli_fetch_assoc($albums)): ?>
                        <a href="album.php?id=<?= $album['id'] ?>" 
                           class="glass-card album-card" 
                           data-aos="fade-up"
                           data-aos-delay="<?= ($index * 100) % 300 ?>">
                            <div class="relative">
                                <img src="<?= $album['cover_image'] ?? '../images/placeholder.jpg' ?>" 
                                     alt="<?= htmlspecialchars($album['name']) ?>"
                                     class="w-full h-52 object-cover rounded-t-2xl"
                                     onerror="this.src='../images/placeholder.jpg'">
                                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4">
                                    <span class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                                        <i class="fas fa-camera mr-1"></i> <?= $album['photo_count'] ?> Foto
                                    </span>
                                </div>
                            </div>
                            <div class="p-5">
                                <h3 class="font-bold text-xl mb-2"><?= htmlspecialchars($album['name']) ?></h3>
                                <p class="text-white/70 mb-4">
                                    <?= htmlspecialchars($album['description'] ?? 'Tidak ada deskripsi') ?>
                                </p>
                                <div class="flex justify-between items-center text-sm text-white/60">
                                    <span><?= date('d M Y', strtotime($album['created_at'])) ?></span>
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-eye"></i> Lihat Album
                                    </span>
                                </div>
                            </div>
                        </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <div class="glass-card p-12 text-center col-span-full" data-aos="fade-up">
                        <div class="w-24 h-24 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-book-open text-3xl text-blue-300"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Belum ada album</h3>
                        <p class="text-white/80 mb-6">Album yang dibuat akan muncul di sini</p>
                        <?php if($user['role'] === 'admin'): ?>
                        <a href="create_album.php" class="bg-blue-600 text-white px-6 py-3 rounded-full font-semibold neon-glow-button inline-flex items-center gap-2">
                            <i class="fas fa-plus"></i> Buat Album Pertama
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="mt-16 md:mt-20">
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
                                <li><a href="../index.html" class="hover:text-blue-300">Beranda</a></li>
                                <li><a href="../index.html#gallery" class="hover:text-blue-300">Galeri</a></li>
                                <li><a href="../index.html#faq" class="hover:text-blue-300">FAQ</a></li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Ikuti Kami</h3>
                            <div class="flex justify-center md:justify-start space-x-4">
                                <a href="#" class="text-white/80 hover:text-blue-300 text-xl">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="text-white/80 hover:text-blue-300 text-xl">
                                    <i class="fab fa-facebook"></i>
                                </a>
                                <a href="#" class="text-white/80 hover:text-blue-300 text-xl">
                                    <i class="fab fa-youtube"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t border-white/10 mt-8 pt-8 text-center text-white/50 text-sm">
                        &copy; <span id="year"></span> osis-medcom.web.id All Rights Reserved. Terms & Privacy Policy
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Page Load
            const loadingScreen = document.getElementById('loading-screen');
            const mainContent = document.getElementById('main-content');
            
            window.onload = () => {
                loadingScreen.classList.add('opacity-0');
                setTimeout(() => {
                    loadingScreen.style.display = 'none';
                    mainContent.classList.remove('opacity-0');
                    AOS.init({ duration: 800, once: true, offset: 50 });
                }, 500);
            };

            // Navbar Logic
            const navbar = document.getElementById('navbar');
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuLinks = document.querySelectorAll('.mobile-menu-link');

            window.addEventListener('scroll', () => {
                navbar.classList.toggle('py-2', window.scrollY > 50);
                navbar.classList.toggle('py-3', window.scrollY <= 50);
            });

            mobileMenuButton.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
            mobileMenuLinks.forEach(link => link.addEventListener('click', () => mobileMenu.classList.add('hidden')));

            // Footer Year
            document.getElementById('year').textContent = new Date().getFullYear();

            // Image protection
            document.addEventListener('contextmenu', event => {
                if (event.target.tagName === 'IMG') {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>