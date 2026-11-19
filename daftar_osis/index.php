<?php
include 'db.php';

$message = '';
$is_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $nis = trim($_POST['nis'] ?? '');
    $telp = trim($_POST['telp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $visimisi = trim($_POST['visimisi'] ?? '');
    $cerita = trim($_POST['cerita'] ?? '');

    if (empty($nama) || empty($nis) || empty($telp) || empty($email) || empty($visimisi) || empty($cerita)) {
        $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Mohon isi semua data formulir dengan lengkap.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Format email tidak valid.</div>";
    } elseif (isset($_FILES['cv']) && $_FILES['cv']['error'] === 0) {
        $file = $_FILES['cv'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png'];
        $allowed_mimes = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
            'image/jpeg',
            'image/png'
        ];

        // Size check (max 6MB)
        if ($file['size'] > 6 * 1024 * 1024) {
            $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Ukuran berkas CV terlalu besar. Maksimal 6MB.</div>";
        } elseif (!in_array($ext, $allowed_exts, true)) {
            $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Format berkas tidak diizinkan. Gunakan PDF, DOCX, DOC, atau Gambar.</div>";
        } else {
            // Verify MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowed_mimes, true)) {
                $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Tipe berkas tidak valid.</div>";
            } else {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($nama));
                $cvName = 'cv_' . time() . '_' . $cleanName . '.' . $ext;
                $targetFile = $uploadDir . $cvName;
                $dbPath = 'uploads/' . $cvName;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $stmt = $conn->prepare("INSERT INTO pendaftaran_osis (nama, nis, telp, email, visimisi, cerita, cv_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $nama, $nis, $telp, $email, $visimisi, $cerita, $dbPath);

                    if ($stmt->execute()) {
                        $is_success = true;
                        $message = "<div class='p-5 mb-6 bg-emerald-500/20 border border-emerald-500/40 rounded-2xl text-emerald-200 text-center font-medium'>🎉 Formulir pendaftaran berhasil dikirim! Panitia seleksi akan memeriksa berkas Anda. Pantau pengumuman di forum/website.</div>";
                    } else {
                        $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Gagal menyimpan pendaftaran ke database.</div>";
                    }
                } else {
                    $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Gagal mengunggah berkas CV.</div>";
                }
            }
        }
    } else {
        $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Wajib mengunggah berkas CV Anda.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran OSIS - SMK Medikacom</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Animate On Scroll (AOS) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Liquid Theme Styles -->
    <link rel="stylesheet" href="../assets/css/liquid-theme.css">
</head>
<body class="selection:bg-blue-500/30 selection:text-white">

    <!-- Background Elements -->
    <div class="main-background"></div>
    <div class="main-overlay"></div>

    <!-- Navigation Header -->
    <header class="fixed top-0 left-0 right-0 z-50 pt-4 px-4 md:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="liquid-glass px-6 py-3.5 flex items-center justify-between">
                <a href="../index.html" class="flex items-center gap-3">
                    <img class="h-8 w-auto" src="../images/logo.png" alt="Logo" onerror="this.onerror=null;this.src='https://placehold.co/100x100/ffffff/000000?text=Logo';">
                    <span class="text-white font-bold text-sm tracking-wide">
                        OSIS & MPK <span class="text-white/60 font-normal">| Rekrutmen</span>
                    </span>
                </a>
                <a href="../index.html" class="liquid-btn-secondary px-4 py-2 text-xs font-bold gap-2">
                    <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    Beranda Utama
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 max-w-4xl pt-32 pb-20">

        <!-- Title Header -->
        <div class="text-center mb-10" data-aos="fade-down">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-white/20 bg-white/5 backdrop-blur-md text-xs font-semibold uppercase tracking-wider text-blue-300 mb-4">
                <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                Formulir Pendaftaran Calon Anggota
            </div>
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-white mb-3">
                Pendaftaran <span class="text-gradient">OSIS & MPK</span>
            </h1>
            <p class="text-white/70 max-w-xl mx-auto text-base">
                Jadilah bagian dari generasi perubahan. Kembangkan kepemimpinan, perbanyak koneksi, dan wujudkan ide-ide luar biasa.
            </p>
        </div>

        <div class="liquid-glass p-8 sm:p-12 relative overflow-hidden" data-aos="zoom-in" data-aos-delay="100">
            <!-- Glow background -->
            <div class="absolute -top-24 right-0 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <?= $message ?>

            <?php if (!$is_success): ?>
            <form method="POST" enctype="multipart/form-data" class="space-y-6">

                <!-- Grid Data Pribadi -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="nama" class="block text-white/80 text-sm font-semibold mb-2">Nama Lengkap</label>
                        <input 
                            type="text" 
                            name="nama" 
                            id="nama" 
                            placeholder="contoh: Muhammad Rizky" 
                            required 
                            class="liquid-input w-full px-5 py-3.5 text-sm"
                            value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                        >
                    </div>

                    <div>
                        <label for="nis" class="block text-white/80 text-sm font-semibold mb-2">NIS (Nomor Induk Siswa)</label>
                        <input 
                            type="text" 
                            name="nis" 
                            id="nis" 
                            placeholder="contoh: 242510101" 
                            required 
                            class="liquid-input w-full px-5 py-3.5 text-sm font-mono"
                            value="<?= htmlspecialchars($_POST['nis'] ?? '') ?>"
                        >
                    </div>

                    <div>
                        <label for="telp" class="block text-white/80 text-sm font-semibold mb-2">Nomor WhatsApp Aktif</label>
                        <input 
                            type="tel" 
                            name="telp" 
                            id="telp" 
                            placeholder="contoh: 081234567890" 
                            required 
                            class="liquid-input w-full px-5 py-3.5 text-sm font-mono"
                            value="<?= htmlspecialchars($_POST['telp'] ?? '') ?>"
                        >
                    </div>

                    <div>
                        <label for="email" class="block text-white/80 text-sm font-semibold mb-2">Alamat Email Aktif</label>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            placeholder="contoh: nama@gmail.com" 
                            required 
                            class="liquid-input w-full px-5 py-3.5 text-sm"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        >
                    </div>
                </div>

                <!-- Visi & Misi -->
                <div>
                    <label for="visimisi" class="block text-white/80 text-sm font-semibold mb-2">Visi & Misi Anda</label>
                    <textarea 
                        name="visimisi" 
                        id="visimisi" 
                        rows="3" 
                        placeholder="Tuliskan visi singkat dan misi apa yang ingin Anda capai di OSIS..." 
                        required 
                        class="liquid-input w-full px-5 py-3.5 text-sm"
                    ><?= htmlspecialchars($_POST['visimisi'] ?? '') ?></textarea>
                </div>

                <!-- Cerita Diri -->
                <div>
                    <label for="cerita" class="block text-white/80 text-sm font-semibold mb-2">Ceritakan Tentang Diri Anda & Pengalaman</label>
                    <textarea 
                        name="cerita" 
                        id="cerita" 
                        rows="4" 
                        placeholder="Ceritakan latar belakang, minat keahlian, atau pengalaman organisasi sebelumnya..." 
                        required 
                        class="liquid-input w-full px-5 py-3.5 text-sm"
                    ><?= htmlspecialchars($_POST['cerita'] ?? '') ?></textarea>
                </div>

                <!-- CV Upload Box -->
                <div>
                    <label for="cv" class="block text-white/80 text-sm font-semibold mb-2">Unggah Curriculum Vitae (CV) / Portofolio</label>
                    <div class="p-6 rounded-2xl bg-white/5 border border-dashed border-white/20 hover:border-blue-400/60 transition-colors text-center">
                        <svg class="w-10 h-10 mx-auto text-blue-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <input 
                            type="file" 
                            name="cv" 
                            id="cv" 
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" 
                            required 
                            class="block w-full text-sm text-white/70 file:mr-4 file:py-2.5 file:px-5 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer"
                        >
                        <p class="text-xs text-white/40 mt-2">Format yang didukung: PDF, DOCX, JPG, PNG (Maksimal 6MB)</p>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button 
                        type="submit" 
                        class="liquid-btn w-full py-4 text-base font-bold shadow-lg"
                    >
                        Kirim Pendaftaran Calon Anggota
                    </button>
                </div>

            </form>
            <?php else: ?>
                <div class="text-center py-6">
                    <a href="../index.html" class="liquid-btn px-8 py-3.5 text-sm font-bold">Kembali ke Beranda</a>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- Footer -->
    <footer class="pb-10 text-center text-white/50 text-xs">
        &copy; <?= date('Y') ?> OSIS & MPK SMK Medikacom Bandung.
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
