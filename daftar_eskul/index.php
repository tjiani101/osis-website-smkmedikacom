<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pendaftaran Ekstrakurikuler - SMK Medikacom</title>
  
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

  <!-- Blurred Background Elements -->
  <div class="main-background"></div>
  <div class="main-overlay"></div>

  <!-- Navigation Header -->
  <header class="fixed top-0 left-0 right-0 z-50 pt-4 px-4 md:px-8">
    <div class="max-w-4xl mx-auto">
      <div class="liquid-glass px-6 py-3.5 flex items-center justify-between">
        <a href="../index.html" class="flex items-center gap-3">
          <img class="h-8 w-auto" src="../images/logo.png" alt="Logo" onerror="this.onerror=null;this.src='https://placehold.co/100x100/ffffff/000000?text=Logo';">
          <span class="text-white font-bold text-sm tracking-wide">
            OSIS & MPK <span class="text-white/60 font-normal">| Ekstrakurikuler</span>
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
  <main class="container mx-auto px-4 max-w-3xl pt-36 pb-20">

    <div class="liquid-glass p-8 sm:p-12 text-center relative overflow-hidden" data-aos="zoom-in">
      <!-- Glow effect -->
      <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-80 h-80 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Status Badge -->
      <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-bold uppercase tracking-wider mb-6">
        <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
        Status Pendaftaran Periode Ini
      </div>

      <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-4">
        Pendaftaran Ekstrakurikuler <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 to-rose-400">Sedang Ditutup</span>
      </h1>
      <p class="text-white/70 max-w-xl mx-auto text-base leading-relaxed mb-8">
        Masa pendaftaran dan verifikasi berkas anggota ekstrakurikuler tahun ajaran ini telah berakhir atau belum dibuka kembali.
      </p>

      <!-- Alert Box -->
      <div class="p-6 rounded-2xl bg-white/5 border border-white/10 text-left mb-8 space-y-4">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
          <span class="text-blue-400">ℹ️</span> Informasi Untuk Siswa Baru:
        </h3>
        <ul class="space-y-3 text-sm text-white/80">
          <li class="flex items-start gap-3">
            <span class="text-blue-400 font-bold">•</span>
            <span>Pendaftaran biasanya dibuka serentak pada saat pelaksanaan <strong>Demo Ekstrakurikuler (MPLS)</strong> di awal tahun ajaran.</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="text-blue-400 font-bold">•</span>
            <span>Bagi siswa yang ingin pindah atau bergabung di luar jadwal online, silakan hubungi <strong>Koordinator Eskul</strong> atau Pembina masing-masing.</span>
          </li>
          <li class="flex items-start gap-3">
            <span class="text-blue-400 font-bold">•</span>
            <span>Informasi jadwal latihan mingguan tetap dapat dilihat melalui pengumuman resmi OSIS di forum sekolah.</span>
          </li>
        </ul>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
        <a href="../index.html" class="liquid-btn px-8 py-3.5 text-sm font-bold w-full sm:w-auto">
          Kembali ke Beranda
        </a>
        <a href="../pages/forum/" class="liquid-btn-secondary px-8 py-3.5 text-sm font-semibold w-full sm:w-auto">
          Tanya di Forum Diskusi
        </a>
      </div>

      <!-- Help Contact -->
      <div class="mt-8 pt-6 border-t border-white/10 text-xs text-white/50">
        Butuh bantuan darurat? Hubungi kami via email: <a href="mailto:mail@osis-medcom.web.id" class="text-blue-300 underline">mail@osis-medcom.web.id</a>
      </div>

    </div>

  </main>

  <!-- Footer -->
  <footer class="pb-10 text-center text-white/40 text-xs">
    &copy; <?= date('Y') ?> OSIS & MPK SMK Medikacom Bandung. All Rights Reserved.
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
