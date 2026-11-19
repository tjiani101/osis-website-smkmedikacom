<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

function simpanLog($conn, $username, $action) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO logs (timestamp, username, action, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $timestamp, $username, $action, $ip);
    $stmt->execute();
}

if (!isset($_SESSION['user_id'])) {
    simpanLog($conn, 'Guest', 'Unauthorized post creation attempt');
    header("Location: login.php");
    exit;
}

simpanLog($conn, $_SESSION['username'], 'Create post creation page');

$forum_result = $conn->query("SELECT * FROM forum ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Post Baru - Forum OSIS Medikacom</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Animate On Scroll (AOS) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Liquid Theme Styles -->
    <link rel="stylesheet" href="../../assets/css/liquid-theme.css">
</head>
<body class="selection:bg-blue-500/30 selection:text-white">

    <!-- Background Elements -->
    <div class="main-background"></div>
    <div class="main-overlay"></div>

    <!-- Header Navigation -->
    <header class="fixed top-0 left-0 right-0 z-50 pt-4 px-4 md:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="liquid-glass px-6 py-3.5 flex items-center justify-between">
                <a href="index.php" class="flex items-center gap-3">
                    <img class="h-8 w-auto" src="../../images/logo.png" alt="Logo" onerror="this.onerror=null;this.src='https://placehold.co/100x100/ffffff/000000?text=Logo';">
                    <span class="text-white font-bold text-sm tracking-wide">
                        Forum Diskusi <span class="text-white/60 font-normal">| Buat Topik</span>
                    </span>
                </a>
                <a href="index.php" class="liquid-btn-secondary px-4 py-2 text-xs font-bold gap-2">
                    <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    Batal & Kembali
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 max-w-3xl pt-32 pb-20">

        <div class="text-center mb-8" data-aos="fade-down">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-2">
                Mulai Diskusi <span class="text-gradient">Baru</span>
            </h1>
            <p class="text-white/70 text-sm">
                Bagikan ide, pertanyaan, aspirasi, atau karya kreatif Anda ke forum OSIS.
            </p>
        </div>

        <div class="liquid-glass p-8 sm:p-12 relative overflow-hidden" data-aos="zoom-in">
            <!-- Glow background -->
            <div class="absolute -top-20 right-0 w-72 h-72 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <form action="buat_post_proses.php" method="POST" enctype="multipart/form-data" class="space-y-6">

                <!-- Kategori Sub-forum Reddit -->
                <div>
                    <label class="block text-white/80 text-sm font-semibold mb-2">Pilih Komunitas (Sub-forum)</label>
                    <?php $preselected_id = isset($_GET['forum_id']) ? intval($_GET['forum_id']) : 0; ?>
                    <select name="forum_id" required class="liquid-input w-full px-5 py-3.5 text-sm appearance-none bg-black/40">
                        <?php while ($forum = $forum_result->fetch_assoc()): 
                            $slug = htmlspecialchars($forum['slug'] ?: strtolower(str_replace(' ', '-', $forum['nama'])));
                        ?>
                            <option value="<?= $forum['id'] ?>" <?= $preselected_id == $forum['id'] ? 'selected' : '' ?> class="bg-[#0b1021] text-white">
                                sf/<?= $slug ?> • <?= htmlspecialchars($forum['nama']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Judul -->
                <div>
                    <label class="block text-white/80 text-sm font-semibold mb-2">Judul Post</label>
                    <input 
                        type="text" 
                        name="judul" 
                        placeholder="Tulis judul yang menarik dan deskriptif..." 
                        required 
                        class="liquid-input w-full px-5 py-3.5 text-sm"
                    >
                </div>

                <!-- Konten -->
                <div>
                    <label class="block text-white/80 text-sm font-semibold mb-2">Konten / Isi Diskusi</label>
                    <textarea 
                        name="konten" 
                        rows="7" 
                        placeholder="Jelaskan topik diskusi Anda secara lengkap dan sopan..." 
                        required 
                        class="liquid-input w-full px-5 py-3.5 text-sm"
                    ></textarea>
                </div>

                <!-- Media Upload Option -->
                <div>
                    <label class="block text-white/80 text-sm font-semibold mb-2">Lampiran Media (Opsional)</label>
                    
                    <div class="flex items-center gap-4 mb-3">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-white/80 bg-white/5 hover:bg-white/10 px-4 py-2 rounded-xl border border-white/10 transition-colors">
                            <input type="radio" name="media_type" value="image" checked class="accent-blue-500">
                            <span>🖼️ Gambar (JPG, PNG, WebP)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-white/80 bg-white/5 hover:bg-white/10 px-4 py-2 rounded-xl border border-white/10 transition-colors">
                            <input type="radio" name="media_type" value="video" class="accent-blue-500">
                            <span>🎥 Video (MP4)</span>
                        </label>
                    </div>

                    <div class="p-5 rounded-2xl bg-white/5 border border-dashed border-white/20 text-center">
                        <input 
                            type="file" 
                            name="media_upload" 
                            id="media_upload" 
                            accept=".jpg,.jpeg,.png,.webp,.gif,.mp4" 
                            class="block w-full text-sm text-white/70 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer"
                        >
                        <p class="text-xs text-white/40 mt-2" id="file_info">
                            Ukuran maksimal gambar: 5MB | Video: 25MB
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="pt-4 flex items-center justify-end gap-3">
                    <a href="index.php" class="liquid-btn-secondary px-6 py-3.5 text-sm font-semibold">
                        Batal
                    </a>
                    <button type="submit" class="liquid-btn px-8 py-3.5 text-sm font-bold shadow-lg">
                        Terbitkan Post
                    </button>
                </div>

            </form>
        </div>

    </main>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            AOS.init({ duration: 800, once: true });

            document.getElementById('media_upload').addEventListener('change', function() {
                const info = document.getElementById('file_info');
                if (this.files.length > 0) {
                    const f = this.files[0];
                    info.innerHTML = `Terpilih: <strong>${f.name}</strong> (${(f.size/(1024*1024)).toFixed(2)} MB)`;
                } else {
                    info.innerHTML = 'Ukuran maksimal gambar: 5MB | Video: 25MB';
                }
            });
        });
    </script>
</body>
</html>
