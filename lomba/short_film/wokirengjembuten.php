<?php
// Include koneksi database
include 'db.php';

$message = "";
$messageType = ""; // success atau error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dan sanitasi input untuk keamanan dasar
    $nama_tim = htmlspecialchars($_POST['nama_tim']);
    $ketua_tim = htmlspecialchars($_POST['ketua_tim']);
    $kelas = htmlspecialchars($_POST['kelas']);
    $no_wa = htmlspecialchars($_POST['no_wa']);
    $platform = htmlspecialchars($_POST['platform']);
    $link_karya = htmlspecialchars($_POST['link_karya']);
    $judul_karya = htmlspecialchars($_POST['judul_karya']);
    $deskripsi = htmlspecialchars($_POST['deskripsi']);

    // Validasi sederhana
    if (empty($nama_tim) || empty($link_karya) || empty($no_wa)) {
        $message = "Mohon lengkapi semua field yang wajib diisi!";
        $messageType = "error";
    } else {
        // Menggunakan Prepared Statement untuk mencegah SQL Injection
        $stmt = $conn->prepare("INSERT INTO lomba_content_creator (nama_tim, ketua_tim, kelas, no_wa, platform_karya, link_karya, judul_karya, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("ssssssss", $nama_tim, $ketua_tim, $kelas, $no_wa, $platform, $link_karya, $judul_karya, $deskripsi);

        if ($stmt->execute()) {
            $message = "Mantap! Karyamu berhasil dikirim. Kamu akan diarahkan dalam 3 detik...";
            $messageType = "success";
        } else {
            $message = "Waduh, gagal mengirim data: " . $stmt->error;
            $messageType = "error";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Lomba Ramadhan - OSIS SMK MEDIKACOM</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- SweetAlert2 (Untuk Popup Keren) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0a0a0a;
            color: white;
            overflow-x: hidden;
        }

        /* Background setup */
        .background-container {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100vh;
            z-index: -1;
            background-image: url('10001771336_11zon.webp'); 
            background-size: cover;
            background-position: center;
            filter: blur(8px) brightness(0.6);
            transform: scale(1.1);
        }

        /* Glassmorphism */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 1.5rem;
        }

        /* Input Styling */
        .glass-input {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }
        .glass-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
        }
        
        select.glass-input option {
            background-color: #1a1a1a;
            color: white;
        }

        .neon-glow-button {
            transition: all 0.3s ease;
            box-shadow: 0 0 5px rgba(59, 130, 246, 0), 0 0 10px rgba(59, 130, 246, 0);
        }
        .neon-glow-button:hover {
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.6), 0 0 25px rgba(37, 99, 235, 0.4);
            transform: translateY(-2px);
        }

        .text-gradient-ramadhan {
            background: linear-gradient(to right, #60a5fa, #fbbf24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Modal Styles */
        #rules-modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        #rules-modal.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        #rules-modal:not(.hidden) {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        .modal-content-scroll::-webkit-scrollbar {
            width: 8px;
        }
        .modal-content-scroll::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }
        .modal-content-scroll::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.5);
            border-radius: 4px;
        }
        .rule-list { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 0.5rem; color: rgba(255,255,255,0.8); }
        .rule-section { margin-bottom: 1.5rem; }
        .rule-title { font-weight: 600; color: #60a5fa; margin-bottom: 0.5rem; font-size: 1.1rem; }

    </style>
</head>
<body>

    <div class="background-container"></div>

    <nav class="fixed top-0 left-0 right-0 z-50 p-4">
        <div class="container mx-auto">
            <div class="glass-card px-6 py-3 flex justify-between items-center">
                <a href="index.html" class="flex items-center gap-3 hover:opacity-80 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    <span class="font-semibold text-sm md:text-base">Kembali ke Home</span>
                </a>
                <div class="text-xs md:text-sm text-blue-300 font-bold">EVENT RAMADHAN 1446H</div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 pt-24 pb-12 min-h-screen flex items-center justify-center">
        <div class="w-full max-w-4xl">
            
            <div class="text-center mb-10" data-aos="fade-down">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">
                    <span class="text-gradient-ramadhan">Content Creator Challenge</span>
                </h1>
                <p class="text-white/70 max-w-2xl mx-auto mb-6">
                    Tunjukkan kreativitas tim kamu di bulan suci ini! Upload karya terbaikmu dan menangkan hadiah menarik dari OSIS SMK Medikacom.
                </p>
                
                <!-- TOMBOL BACA KETENTUAN -->
                <button onclick="toggleModal()" class="inline-flex items-center gap-2 px-6 py-2 rounded-full border border-blue-500/30 bg-blue-500/10 hover:bg-blue-500/20 text-blue-300 transition-all hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <span>Baca Ketentuan & Juknis Lomba</span>
                </button>
            </div>

            <div class="glass-card p-6 md:p-10" data-aos="fade-up">
                <form action="" method="POST" class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-blue-300">Nama Tim / Individu</label>
                            <input type="text" name="nama_tim" required placeholder="Contoh: Tim Ngabuburit" 
                                class="w-full px-4 py-3 rounded-xl glass-input">
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-blue-300">Nama Ketua Tim</label>
                            <input type="text" name="ketua_tim" required placeholder="Nama Lengkap Ketua" 
                                class="w-full px-4 py-3 rounded-xl glass-input">
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-blue-300">Kelas</label>
                            <select name="kelas" required class="w-full px-4 py-3 rounded-xl glass-input cursor-pointer">
                                <option value="" disabled selected>Pilih Kelas</option>
                                <option value="X TKJ">X TKJ</option>
                                <option value="X RPL">X RPL</option>
                                <option value="X DKV">X DKV</option>
                                <option value="X TSM">X TSM</option>
                                <option value="X TKR">X TKR</option>
                                <option value="X FARMASI">X FARMASI</option>
                                <option value="XI TKJ">XI TKJ</option>
                                <option value="XI RPL">XI RPL</option>
                                <option value="XI DKV">XI DKV</option>
                                <option value="XI TSM">XI TSM</option>
                                <option value="XI TKR">XI TKR</option>
                                <option value="XI FARMASI">XI FARMASI</option>
                                <option value="XII TKJ">XII TKJ</option>
                                <option value="XII RPL">XII RPL</option>
                                <option value="XII DKV">XII DKV</option>
                                <option value="XII TSM">XII TSM</option>
                                <option value="XII TKR">XII TKR</option>
                                <option value="XII FARMASI">XII FARMASI</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-blue-300">Nomor WhatsApp (Aktif)</label>
                            <input type="number" name="no_wa" required placeholder="08xxxxxxxxxx" 
                                class="w-full px-4 py-3 rounded-xl glass-input">
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-blue-300">Platform Upload</label>
                            <select name="platform" required class="w-full px-4 py-3 rounded-xl glass-input cursor-pointer">
                                <option value="YouTube">YouTube</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-blue-300">Judul Karya</label>
                            <input type="text" name="judul_karya" required placeholder="Judul video kamu" 
                                class="w-full px-4 py-3 rounded-xl glass-input">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-blue-300">Link Video (Pastikan Akun Tidak Diprivate)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-3.5 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                            </span>
                            <input type="url" name="link_karya" required placeholder="https://..." 
                                class="w-full pl-12 pr-4 py-3 rounded-xl glass-input">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-blue-300">Deskripsi Singkat / Pesan</label>
                        <textarea name="deskripsi" rows="3" placeholder="Ceritakan sedikit tentang karyamu..." 
                            class="w-full px-4 py-3 rounded-xl glass-input resize-none"></textarea>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-4 rounded-xl neon-glow-button flex justify-center items-center gap-2 group">
                            <span>Kirim Karya</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-1 transition-transform"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        </button>
                    </div>

                    <p class="text-xs text-center text-white/40 mt-4">
                        Dengan mengirimkan form ini, kamu menyetujui bahwa karyamu adalah orisinal dan dapat dipublikasikan ulang oleh OSIS SMK Medikacom.
                    </p>
                </form>
            </div>
        </div>
    </main>

    <!-- MODAL POPUP KETENTUAN -->
    <div id="rules-modal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm hidden">
        <div class="glass-card w-full max-w-2xl max-h-[85vh] flex flex-col shadow-2xl animate__animated animate__zoomIn">
            
            <!-- Header Modal -->
            <div class="p-5 border-b border-white/10 flex justify-between items-center bg-blue-600/20 rounded-t-2xl">
                <h2 class="text-xl md:text-2xl font-bold text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Juknis Lomba Short Film
                </h2>
                <button onclick="toggleModal()" class="text-white/60 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>

            <!-- Content Modal (Scrollable) -->
            <div class="p-6 overflow-y-auto modal-content-scroll text-sm md:text-base leading-relaxed text-slate-200">
                
                <div class="rule-section">
                    <h3 class="rule-title">I. KETENTUAN PESERTA & TEMA</h3>
                    <ul class="rule-list">
                        <li><strong>Peserta:</strong> Perwakilan setiap kelas wajib mengirimkan 1 tim (Maksimal 5 orang per tim, termasuk kru dan aktor).</li>
                        <li><strong>Tema Utama:</strong> "Ramadhan" (Sub-tema: Religi, Inspiratif, Drama, atau Slice of Life).</li>
                        <li><strong>Etika Karya:</strong>
                            <ul class="list-[circle] pl-5 mt-1">
                                <li>Cerita harus mengandung nilai positif & pesan moral Ramadhan.</li>
                                <li>Karya wajib orisinil & belum pernah dipublikasikan.</li>
                                <li><strong>DILARANG KERAS</strong> memuat unsur SARA, kekerasan, atau konten negatif.</li>
                            </ul>
                        </li>
                    </ul>
                </div>

                <div class="rule-section">
                    <h3 class="rule-title">II. SPESIFIKASI TEKNIS FILM</h3>
                    <ul class="rule-list">
                        <li><strong>Durasi:</strong> 5-10 menit.</li>
                        <li><strong>Format Visual:</strong> Landscape (Rasio 16:9).</li>
                        <li><strong>Resolusi:</strong> Minimal 1080p (Full HD).</li>
                        <li><strong>Audio:</strong> Dialog harus jelas, noise minimal.</li>
                        <li><strong>Subtitle:</strong> Sangat dianjurkan jika ada dialog bahasa daerah/kurang jelas.</li>
                    </ul>
                </div>

                <div class="rule-section">
                    <h3 class="rule-title">III. ATURAN UPLOAD & PUBLIKASI</h3>
                    <ul class="rule-list">
                        <li><strong>Platform:</strong> YouTube.</li>
                        <li><strong>Visibilitas:</strong> Public atau Unlisted (Jangan Private).</li>
                        <li><strong>Format Judul:</strong> [Judul Film] - MedikaCom - [Kelas] - Lomba Short Film Ramadhan SMK.</li>
                        <li><strong>Deskripsi Video Wajib Memuat:</strong> Sinopsis, Nama Tim, Pembagian Peran, Perangkat Kamera, & Aplikasi Editing.</li>
                    </ul>
                </div>

                <div class="rule-section">
                    <h3 class="rule-title">IV. KETENTUAN CREDIT SCENE & PENGUMPULAN</h3>
                    <ul class="rule-list">
                        <li><strong>Credit Scene:</strong> Wajib ada di akhir video (Judul, Tim, Kelas, Nama Kru/Talent).</li>
                        <li><strong>Pengumpulan:</strong> Salin link YouTube dan submit di form ini sebelum batas waktu.</li>
                    </ul>
                </div>

                <div class="rule-section mb-0">
                    <h3 class="rule-title">V. KRITERIA PENILAIAN (TOTAL 100%)</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                        <div class="bg-white/5 p-3 rounded-lg border border-white/10">
                            <span class="text-yellow-400 font-bold">30%</span> Story & Alur Cerita
                        </div>
                        <div class="bg-white/5 p-3 rounded-lg border border-white/10">
                            <span class="text-yellow-400 font-bold">25%</span> Acting & Penghayatan
                        </div>
                        <div class="bg-white/5 p-3 rounded-lg border border-white/10">
                            <span class="text-yellow-400 font-bold">25%</span> Cinematography
                        </div>
                        <div class="bg-white/5 p-3 rounded-lg border border-white/10">
                            <span class="text-yellow-400 font-bold">15%</span> Editing & Audio
                        </div>
                        <div class="bg-white/5 p-3 rounded-lg border border-white/10">
                            <span class="text-yellow-400 font-bold">5%</span> Originalitas
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer Modal -->
            <div class="p-4 border-t border-white/10 bg-black/20 rounded-b-2xl flex justify-end">
                <button onclick="toggleModal()" class="px-6 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-medium transition-colors">
                    Saya Mengerti
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });

        // Logic Modal
        function toggleModal() {
            const modal = document.getElementById('rules-modal');
            const body = document.body;
            
            if (modal.classList.contains('hidden')) {
                modal.classList.remove('hidden');
                // Prevent background scrolling when modal is open
                body.style.overflow = 'hidden';
            } else {
                modal.classList.add('hidden');
                // Restore scrolling
                body.style.overflow = '';
            }
        }

        // Close modal when clicking outside
        document.getElementById('rules-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                toggleModal();
            }
        });

        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('rules-modal').classList.contains('hidden')) {
                toggleModal();
            }
        });

        <?php if ($message != ""): ?>
            <?php if ($messageType == "success"): ?>
                let timerInterval;
                Swal.fire({
                    title: 'Berhasil!',
                    html: '<?php echo $message; ?><br><br>Mengalihkan dalam <b></b> milidetik.',
                    icon: 'success',
                    background: '#1a1a1a',
                    color: '#fff',
                    timer: 3000,
                    timerProgressBar: true,
                    confirmButtonColor: '#3b82f6',
                    didOpen: () => {
                        Swal.showLoading();
                        const b = Swal.getHtmlContainer().querySelector('b');
                        timerInterval = setInterval(() => {
                            b.textContent = Swal.getTimerLeft();
                        }, 100)
                    },
                    willClose: () => {
                        clearInterval(timerInterval);
                    }
                }).then((result) => {
                    window.location.href = 'sukses.html';
                });
            <?php else: ?>
                Swal.fire({
                    title: 'Gagal!',
                    text: '<?php echo $message; ?>',
                    icon: 'error',
                    background: '#1a1a1a',
                    color: '#fff',
                    confirmButtonColor: '#ef4444'
                }).then(() => {
                    // Stay on page
                });
            <?php endif; ?>
        <?php endif; ?>
    </script>
</body>
</html>