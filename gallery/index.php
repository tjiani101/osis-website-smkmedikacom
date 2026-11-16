<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

// Data fetching remains unchanged
$photos = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM photos"))['total'] ?? 'NULL';
$events = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM events"))['total'] ?? 'NULL';
$premium = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM photos WHERE premium = 1"))['total'] ?? 'NULL';
$requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM custom_requests"))['total'] ?? 'NULL';

function folderSize($dir){
    $size = 0;
    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : folderSize($each);
    }
    return $size;
}
$storageUsed = folderSize('galery');
$storageDisplay = round($storageUsed / 1024 / 1024, 2) . ' MB';

$downloads = "NULL";
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri OSIS - SMK MEDIKACOM</title>
    
    <!-- Meta OG Tags -->
    <meta property="og:title" content="Galeri OSIS SMK Medikacom" />
    <meta property="og:description" content="Dashboard Galeri Resmi OSIS & MPK SMK Medikacom" />
    <meta property="og:image" content="images/logo.png" />
    <meta property="og:url" content="https://osis-medcom.web.id/galery/" />
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

        .stat-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        .stat-card:hover {
            border-color: rgba(59, 130, 246, 0.3);
            transform: translateY(-5px);
        }

        .gradient-text {
            background: linear-gradient(to right, #3b82f6, #22d3ee);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .dot-flashing {
            position: relative;
            width: 8px;
            height: 8px;
            border-radius: 5px;
            color: #93c5fd;
            animation: dotFlashing 1s infinite linear alternate;
            animation-delay: .5s;
        }

        .dot-flashing::before, .dot-flashing::after {
            content: '';
            display: inline-block;
            position: absolute;
            top: 0;
        }

        .dot-flashing::before {
            left: -12px;
            width: 8px;
            height: 8px;
            border-radius: 5px;
            background-color: #93c5fd;
            animation: dotFlashing 1s infinite alternate;
            animation-delay: 0s;
        }

        .dot-flashing::after {
            left: 12px;
            width: 8px;
            height: 8px;
            border-radius: 5px;
            background-color: #93c5fd;
            animation: dotFlashing 1s infinite alternate;
            animation-delay: 1s;
        }

        @keyframes dotFlashing {
            0% { background-color: #93c5fd; }
            50%, 100% { background-color: rgba(147, 197, 253, 0.3); }
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

        .sidebar {
            background: rgba(11, 17, 32, 0.8);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(59, 130, 246, 0.2);
        }

        .mobile-menu-toggle {
            background: linear-gradient(to right, #3b82f6, #22d3ee);
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
                        <a href="#dashboard" class="neon-text-glow-hover transition-colors">Dashboard</a>
                        <a href="galery.php" class="neon-text-glow-hover transition-colors">Galeri</a>
                        <a href="albums.php" class="neon-text-glow-hover transition-colors">Album</a>
                        <a href="#terms" class="neon-text-glow-hover transition-colors">Syarat</a>
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
                    <a href="#dashboard" class="mobile-menu-link hover:text-blue-300 py-2">Dashboard</a>
                    <a href="galery.php" class="mobile-menu-link hover:text-blue-300 py-2">Galeri</a>
                    <a href="albums.php" class="mobile-menu-link hover:text-blue-300 py-2">Album</a>
                    <a href="#terms" class="mobile-menu-link hover:text-blue-300 py-2">Syarat</a>
                    <div class="pt-4 border-t border-white/10">
                        <span class="text-blue-300 block py-2"><?= $user['username'] ?></span>
                        <a href="../logout.php" class="mobile-menu-link bg-blue-600/50 rounded-lg py-2">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="container mx-auto px-4 md:px-6 pt-24 pb-12">
            <!-- Dashboard Section -->
            <section id="dashboard" class="py-8 md:py-12">
                <div class="text-center mb-12" data-aos="fade-up">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">
                        Dashboard <span class="gradient-text">Galeri OSIS</span>
                    </h1>
                    <p class="text-lg text-white/80 max-w-2xl mx-auto">
                        Statistik lengkap dan manajemen galeri foto kegiatan OSIS & MPK
                    </p>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-16">
                    <div class="glass-card stat-card p-6 text-center" data-aos="fade-up" data-aos-delay="100">
                        <h3 class="text-lg font-semibold text-blue-300 mb-2">Total Foto</h3>
                        <p class="text-3xl font-bold"><?= $photos ?? 'NULL' ?></p>
                    </div>
                    
                    <div class="glass-card stat-card p-6 text-center" data-aos="fade-up" data-aos-delay="150">
                        <h3 class="text-lg font-semibold text-blue-300 mb-2">Foto Premium</h3>
                        <p class="text-3xl font-bold"><?= $premium ?? 'NULL' ?></p>
                    </div>
                    
                    <div class="glass-card stat-card p-6 text-center" data-aos="fade-up" data-aos-delay="200">
                        <h3 class="text-lg font-semibold text-blue-300 mb-2">Tier Kamu</h3>
                        <p class="text-3xl font-bold"><?= $user['tier'] ?? 'HD View' ?></p>
                    </div>
                    
                    <div class="glass-card stat-card p-6 text-center" data-aos="fade-up" data-aos-delay="250">
                        <h3 class="text-lg font-semibold text-blue-300 mb-2">Storage</h3>
                        <p class="text-3xl font-bold"><?= $storageDisplay ?></p>
                    </div>
                    
                    <div class="glass-card stat-card p-6 text-center" data-aos="fade-up" data-aos-delay="300">
                        <h3 class="text-lg font-semibold text-blue-300 mb-2">Download</h3>
                        <p class="text-3xl font-bold"><?= $downloads ?></p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                    <a href="galery.php" class="glass-card p-8 text-center group" data-aos="fade-up">
                        <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-blue-500/30 transition-colors">
                            <i class="fas fa-images text-2xl text-blue-300"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Jelajahi Galeri</h3>
                        <p class="text-white/80">Temukan semua foto kegiatan OSIS & MPK terkini</p>
                    </a>
                    
                    <a href="albums.php" class="glass-card p-8 text-center group" data-aos="fade-up" data-aos-delay="100">
                        <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-blue-500/30 transition-colors">
                            <i class="fas fa-book-open text-2xl text-blue-300"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Kelola Album</h3>
                        <p class="text-white/80">cari album foto terbaru</p>
                    </a>
                    
                    <a href="#terms" class="glass-card p-8 text-center group" data-aos="fade-up" data-aos-delay="200">
                        <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-blue-500/30 transition-colors">
                            <i class="fas fa-file-alt text-2xl text-blue-300"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Syarat Penggunaan</h3>
                        <p class="text-white/80">Ketentuan penggunaan layanan galeri OSIS</p>
                    </a>
                </div>
            </section>

            <!-- Terms Section -->
            <section id="terms" class="py-8 md:py-12">
                <div class="glass-card p-8 md:p-10" data-aos="fade-up">
                    <h2 class="text-3xl font-bold mb-6 gradient-text">Syarat & Ketentuan Galeri OSIS</h2>
                    <p class="text-white/80 mb-8"><strong>Terakhir diperbarui:</strong> July 2025</p>

                    <div class="space-y-6">
                        <div>
                            <h3 class="text-xl font-semibold text-blue-300 mb-3">1. Penerimaan Ketentuan</h3>
                            <p class="text-white/80">
                                Dengan mengakses galeri ini, Anda secara otomatis menerima dan setuju untuk terikat oleh semua syarat dan ketentuan yang berlaku. Jika Anda tidak setuju, Anda dipersilakan untuk tidak menggunakan layanan ini.
                            </p>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-semibold text-blue-300 mb-3">2. Tujuan dan Lingkup Layanan</h3>
                            <p class="text-white/80">
                                Galeri ini bertujuan untuk menjadi pusat dokumentasi resmi kegiatan OSIS dan acara sekolah SMK Medikacom. Konten disajikan dalam bentuk foto dan video untuk dapat diakses oleh komunitas sekolah.
                            </p>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-semibold text-blue-300 mb-3">3. Hak Akses Publik</h3>
                            <p class="text-white/80">
                                Seluruh konten yang tersedia di galeri ini bersifat publik dan dapat diakses secara gratis. Tidak ada biaya, donasi, atau sistem keanggotaan berbayar yang diberlakukan.
                            </p>
                        <!--                            <ul class="list-disc pl-6 mt-3 space-y-2 text-white/80">
                                <li>Nominal harus sesuai harga tier yang ditentukan</li>
                                <li>Kelebihan nominal dianggap sebagai dukungan</li>
                                <li>Kekurangan nominal tidak akan diproses</li>
                                <li>Semua pembayaran adalah donasi sukarela</li>
                            </ul> -->
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-semibold text-blue-300 mb-3">4. Kepemilikan Hak Cipta</h3>
                            <p class="text-white/80">
                                Semua materi (foto, video, dan teks) yang ditampilkan dalam galeri ini sepenuhnya merupakan milik dan hak cipta dari OSIS dan SMK Medikacom, kecuali dinyatakan lain.
                            </p>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-semibold text-blue-300 mb-3">5. Larangan Penggunaan Komersial</h3>
                            <p class="text-white/80">
                                Pengguna dilarang keras menggunakan konten apa pun dari galeri ini untuk tujuan komersial, termasuk namun tidak terbatas pada penjualan, pencetakan untuk dijual, atau penggunaan dalam iklan tanpa izin tertulis dari pihak OSIS.
                            </p>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-semibold text-blue-300 mb-3">6. Larangan Modifikasi dan Plagiarisme</h3>
                            <p class="text-white/80">
                                Pengguna dilarang mengubah, memotong (crop), menambahkan filter, menghilangkan watermark (jika ada), atau memodifikasi konten asli dengan cara apa pun. Mengklaim konten sebagai milik pribadi adalah tindakan plagiarisme yang tidak dibenarkan.
                            </p>
                        </div>

                        <div>
                            <h3 class="text-xl font-semibold text-blue-300 mb-3">7. Etika dan Tanggung Jawab Pengguna</h3>
                            <p class="text-white/80">
                                Pengguna wajib menggunakan konten galeri secara etis. Dilarang menggunakan foto atau video untuk tujuan perundungan (bullying), pelecehan, penyebaran kebencian, atau aktivitas lain yang melanggar hukum dan norma sosial.
                            </p>
                        </div>

                        <div>
                            <h3 class="text-xl font-semibold text-blue-300 mb-3">8. Tindakan Perlindungan Konten</h3>
                            <p class="text-white/80">
                                Galeri ini mungkin dilengkapi dengan sistem proteksi teknis (seperti anti klik-kanan atau anti drag-and-drop) untuk mencegah pengunduhan ilegal. Setiap upaya untuk melewati sistem keamanan ini adalah pelanggaran.
                            </p>
                        </div>

                        <div>
                            <h3 class="text-xl font-semibold text-blue-300 mb-3">9. Batasan Tanggung Jawab</h3>
                            <p class="text-white/80">
                                OSIS SMK Medikacom berusaha menyajikan konten yang akurat, namun tidak menjamin ketersediaan layanan tanpa gangguan atau kesalahan teknis. OSIS tidak bertanggung jawab atas kerugian yang mungkin timbul akibat penggunaan galeri ini.
                            </p>
                        </div>

                        <div>
                            <h3 class="text-xl font-semibold text-blue-300 mb-3">10. Perubahan Ketentuan</h3>
                            <p class="text-white/80">
                                OSIS SMK Medikacom berhak untuk meninjau, mengubah, atau memperbarui syarat dan ketentuan ini sewaktu-waktu tanpa pemberitahuan sebelumnya. Perubahan akan berlaku efektif segera setelah dipublikasikan.
                            </p>
                        </div>

                        
                        
                        <div class="pt-4 border-t border-white/10">
                            <p class="text-white/60 italic">
                                Dengan menggunakan layanan ini, Anda dianggap telah menyetujui semua syarat & ketentuan di atas.
                            </p>
                        </div>
                    </div>
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
        });
    </script>
</body>
</html>