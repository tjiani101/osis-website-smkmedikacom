<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get album data
if(!isset($_GET['id']) || !$album_id = filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die("Invalid album ID");
}

$album = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM albums WHERE id = $album_id"
));

if(!$album) {
    die("Album not found");
}

// Get user permissions
$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
$tier = $user['tier'] ?? 'HD View';
$tierArray = explode(', ', $tier);

// Get photos with permissions
$query = "SELECT p.*, e.nama_event 
          FROM photos p
          LEFT JOIN events e ON p.event_id = e.id
          WHERE p.album_id = $album_id AND ";

if (in_array('HD View', $tierArray) || in_array('Member', $tierArray) || in_array('Visitor', $tierArray)) {
    $query .= "p.premium = 0";
} elseif (array_intersect($tierArray, ['Contributor', 'Elite', 'Legendary'])) {
    $query .= "p.premium IN (0,1)";
} else {
    $query .= "1=1";
}

$photos = mysqli_query($conn, $query);

// Handle upload (admin only)
if ($user['role'] === 'admin' && isset($_POST['upload'])) {
    $uploadDir = '../uploads/albums/' . $album_id . '/';
    if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

    foreach($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
        $ext = pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            if(move_uploaded_file($tmp_name, $targetPath)) {
                $stmt = $conn->prepare("INSERT INTO photos 
                    (judul, file_hd, album_id, uploaded_by, premium)
                    VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiii",
                    $_POST['titles'][$key],
                    $targetPath,
                    $album_id,
                    $user_id,
                    $_POST['premium'][$key] ?? 0
                );
                $stmt->execute();
            }
        }
    }
    header("Refresh:0");
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $album['name'] ?> - Album Galeri OSIS</title>
    
    <!-- Meta OG Tags -->
    <meta property="og:title" content="<?= $album['name'] ?> - OSIS SMK Medikacom" />
    <meta property="og:description" content="Album Foto Kegiatan OSIS & MPK SMK Medikacom" />
    <meta property="og:image" content="images/logo.png" />
    <meta property="og:url" content="https://osis-medcom.web.id/galery/album.php?id=<?= $album_id ?>" />
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

        .photo-card {
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .photo-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .photo-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(to right, #3b82f6, #22d3ee);
            z-index: 1;
        }

        .premium-badge {
            background: linear-gradient(to right, #f59e0b, #fbbf24);
            color: #1c1917;
            font-weight: bold;
        }

        .file-options {
            display: none;
            position: absolute;
            bottom: 100%;
            left: 0;
            width: 100%;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(59, 130, 246, 0.5);
            border-radius: 8px;
            z-index: 10;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.2s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .file-option {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.2s;
        }

        .file-option:hover {
            background: rgba(59, 130, 246, 0.2);
            color: white;
        }

        .file-option i {
            width: 16px;
            text-align: center;
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
            <!-- Album Header -->
            <div class="glass-card p-8 mb-8" data-aos="fade-up">
                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <div class="w-full md:w-1/3">
                        <img src="<?= $album['thumbnail'] ?? '../images/placeholder.jpg' ?>" 
                             alt="<?= $album['name'] ?>" 
                             class="w-full h-64 object-cover rounded-2xl"
                             onerror="this.src='../images/placeholder.jpg'">
                    </div>
                    <div class="flex-1">
                        <h1 class="text-3xl sm:text-4xl font-bold mb-4 gradient-text"><?= $album['name'] ?></h1>
                        <p class="text-lg text-white/80 mb-6"><?= $album['description'] ?></p>
                        <div class="flex items-center gap-4 text-white/60">
                            <span>
                                <i class="fas fa-calendar-alt mr-2"></i> 
                                <?= date('d M Y', strtotime($album['created_at'])) ?>
                            </span>
                            <span>
                                <i class="fas fa-images mr-2"></i> 
                                <?= mysqli_num_rows($photos) ?> Foto
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if($user['role'] === 'admin'): ?>
            <div class="glass-card p-6 mb-8" data-aos="fade-up">
                <h3 class="text-xl font-bold mb-4 text-blue-300">Upload Foto Baru</h3>
                <form method="post" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-white/80 mb-2">Pilih Foto</label>
                        <input type="file" name="photos[]" multiple accept="image/*" required 
                               class="w-full bg-white/10 border border-white/20 rounded-2xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-400 focus:outline-none transition-all">
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="checkbox" id="premium" name="premium[]" value="1" class="w-5 h-5">
                        <label for="premium" class="text-white/80">Tandai sebagai Konten Premium</label>
                    </div>
                    <button type="submit" name="upload" class="bg-blue-600 text-white px-6 py-3 rounded-full font-semibold neon-glow-button flex items-center gap-2">
                        <i class="fas fa-upload"></i> Upload Foto
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Photo Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                <?php if(mysqli_num_rows($photos) > 0): ?>
                    <?php while($photo = mysqli_fetch_assoc($photos)): ?>
                    <div class="glass-card photo-card" data-aos="fade-up">
                        <!-- Image with premium badge -->
                        <div class="relative">
                            <img src="<?= $photo['file_hd'] ?>" alt="<?= $photo['judul'] ?>" 
                                 class="w-full h-64 object-cover transition-transform duration-300 hover:scale-105"
                                 oncontextmenu="return false" draggable="false">
                            <?php if($photo['premium']): ?>
                                <span class="premium-badge absolute top-3 right-3 px-3 py-1 rounded-full text-sm">PREMIUM</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Photo Info -->
                        <div class="p-5">
                            <h3 class="font-bold text-lg mb-1"><?= $photo['judul'] ?></h3>
                            <div class="flex justify-between text-sm text-white/60 mb-4">
                                <span><?= date('d M Y', strtotime($photo['tanggal_upload'])) ?></span>
                                <span><?= $photo['nama_event'] ? $photo['nama_event'] : 'Umum' ?></span>
                            </div>
                            
                            <!-- Download Options -->
                            <?php
                            $hasPremiumAccess = array_intersect($tierArray, ['Contributor', 'Elite', 'Legendary']);
                            $isEliteOrLegendary = array_intersect($tierArray, ['Elite', 'Legendary']);
                            ?>
                            
                            <?php if(!$photo['premium'] || $hasPremiumAccess): ?>
                                <div class="download-wrapper relative">
                                    <?php if(!$photo['premium'] || in_array('HD View', $tierArray)): ?>
                                        <a href="download.php?file=<?= urlencode($photo['file_hd']) ?>&type=HD Original" 
                                           class="w-full bg-blue-600 text-white px-4 py-3 rounded-full font-semibold neon-glow-button flex items-center justify-center gap-2" 
                                           download>
                                            <i class="fas fa-download"></i> Download HD
                                        </a>
                                    <?php else: ?>
                                        <button class="w-full bg-blue-600 text-white px-4 py-3 rounded-full font-semibold neon-glow-button flex items-center justify-center gap-2"
                                                onclick="showFileOptions(event, <?= $photo['id'] ?>)">
                                            <i class="fas fa-download"></i> Download Options
                                        </button>
                                        <div class="file-options" id="options-<?= $photo['id'] ?>">
                                            <?php if($isEliteOrLegendary && $photo['file_raw']): ?>
                                                <a href="download.php?file=<?= $photo['file_raw'] ?>&type=RAW" class="file-option px-4 py-3" download>
                                                    <i class="fas fa-file-code"></i> RAW
                                                </a>
                                            <?php endif; ?>
                                            <?php if($photo['file_edited_fhd']): ?>
                                                <a href="download.php?file=<?= $photo['file_edited_fhd'] ?>&type=FHD Edited" class="file-option px-4 py-3" download>
                                                    <i class="fas fa-image"></i> FHD Edited
                                                </a>
                                            <?php endif; ?>
                                            <a href="download.php?file=<?= $photo['file_hd'] ?>&type=Original" class="file-option px-4 py-3" download>
                                                <i class="fas fa-file-image"></i> Original
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <button class="w-full bg-gray-600 text-white/80 px-4 py-3 rounded-full font-semibold flex items-center justify-center gap-2" disabled>
                                    <i class="fas fa-lock"></i> Premium Content
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="glass-card p-12 text-center col-span-full" data-aos="fade-up">
                        <div class="w-24 h-24 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-images text-3xl text-blue-300"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Belum ada foto di album ini</h3>
                        <p class="text-white/80 mb-6">Foto yang diupload akan muncul di sini</p>
                        <?php if($user['role'] === 'admin'): ?>
                        <a href="#" class="bg-blue-600 text-white px-6 py-3 rounded-full font-semibold neon-glow-button inline-flex items-center gap-2">
                            <i class="fas fa-plus"></i> Upload Foto Pertama
                        </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
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
            // Page Load - FIXED: Use window.addEventListener instead of window.onload
            const loadingScreen = document.getElementById('loading-screen');
            const mainContent = document.getElementById('main-content');
            
            // FIX: This is the critical fix for the loading screen loop
            window.addEventListener('load', () => {
                loadingScreen.classList.add('opacity-0');
                setTimeout(() => {
                    loadingScreen.style.display = 'none';
                    mainContent.classList.remove('opacity-0');
                    AOS.init({ duration: 800, once: true, offset: 50 });
                }, 500);
            });

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

            // Gallery Functionality
            document.addEventListener('contextmenu', event => {
                if (event.target.tagName === 'IMG') {
                    event.preventDefault();
                }
            });

            function showFileOptions(e, photoId) {
                e.stopPropagation();

                // Hide all other options
                document.querySelectorAll('.file-options').forEach(options => {
                    if (options.id !== `options-${photoId}`) {
                        options.style.display = 'none';
                    }
                });

                const options = document.getElementById(`options-${photoId}`);
                const isShowing = options.style.display === 'block';
                options.style.display = isShowing ? 'none' : 'block';

                // Close options when clicking elsewhere
                document.addEventListener('click', function closeOptions(event) {
                    if (!options.contains(event.target)) {
                        options.style.display = 'none';
                        document.removeEventListener('click', closeOptions);
                    }
                });
            }

            // Footer Year
            document.getElementById('year').textContent = new Date().getFullYear();
        });
    </script>
</body>
</html>