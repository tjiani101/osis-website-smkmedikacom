<?php
include 'db.php';
$message = '';

if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $nis = trim($_POST['nis']); // This stores email in the table
    $password = $_POST['password'];
    $repeat_password = $_POST['repeat_password'];

    // Check if passwords match
    if ($password !== $repeat_password) {
        $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Konfirmasi password tidak sesuai!</div>";
    } elseif (strlen($password) < 6) {
        $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Password minimal 6 karakter.</div>";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'murid';
        $status = 'active';
        $warning_nis = 1;

        // Cek apakah username sudah dipakai
        $cek = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Username sudah digunakan. Silakan pilih username lain.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, nis, password_hash, role, status, warning_nis) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $username, $nis, $password_hash, $role, $status, $warning_nis);
            if ($stmt->execute()) {
                $message = "<div class='p-4 mb-6 bg-emerald-500/20 border border-emerald-500/40 rounded-2xl text-emerald-200 text-center text-sm font-medium'>Akun berhasil dibuat! Silakan <a href='login.php' class='underline font-bold'>masuk di sini</a>.</div>";
            } else {
                $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Gagal mendaftar. Terjadi kesalahan pada sistem.</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - OSIS & MPK SMK Medikacom</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Liquid Theme Styles -->
    <link rel="stylesheet" href="assets/css/liquid-theme.css">
</head>
<body class="min-h-screen flex items-center justify-center p-4 py-12 relative selection:bg-blue-500/30 selection:text-white">

    <!-- Background Elements -->
    <div class="main-background"></div>
    <div class="main-overlay"></div>

    <!-- Register Container -->
    <div class="w-full max-w-md">
        
        <!-- Back link -->
        <div class="mb-4 text-center sm:text-left">
            <a href="index.html" class="inline-flex items-center gap-2 text-white/70 hover:text-white text-sm font-semibold transition-colors bg-white/5 hover:bg-white/10 px-4 py-2 rounded-full border border-white/10 backdrop-blur-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Beranda
            </a>
        </div>

        <div class="liquid-glass p-8 sm:p-10 relative overflow-hidden">
            <!-- Glow effect on top -->
            <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-24 bg-purple-500/20 rounded-full blur-2xl pointer-events-none"></div>

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-block p-3 bg-white/10 rounded-2xl mb-4 border border-white/20 shadow-lg">
                    <img class="h-10 w-auto" src="images/logo.png" alt="Logo OSIS MPK" onerror="this.onerror=null;this.src='https://placehold.co/100x100/ffffff/000000?text=Logo';">
                </div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight">Buat Akun Baru</h1>
                <p class="text-white/60 text-sm mt-1">Bergabung bersama komunitas siswa SMK Medikacom</p>
            </div>
            
            <?= $message ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-white/80 text-sm font-semibold mb-2">Username</label>
                    <input 
                        type="text" 
                        name="username" 
                        placeholder="Pilih nama panggilan / username unik" 
                        required
                        class="liquid-input w-full px-5 py-3.5 text-sm"
                    >
                </div>
                
                <div>
                    <label class="block text-white/80 text-sm font-semibold mb-2">Email Aktif</label>
                    <input 
                        type="email" 
                        name="nis" 
                        placeholder="contoh: nama@email.com" 
                        required
                        class="liquid-input w-full px-5 py-3.5 text-sm"
                    >
                </div>
                
                <div>
                    <label class="block text-white/80 text-sm font-semibold mb-2">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password"
                        placeholder="Minimal 6 karakter" 
                        required
                        class="liquid-input w-full px-5 py-3.5 text-sm"
                    >
                </div>
                
                <div>
                    <label class="block text-white/80 text-sm font-semibold mb-2">Ulangi Password</label>
                    <input 
                        type="password" 
                        name="repeat_password" 
                        id="repeatPassword"
                        placeholder="Ketik ulang password" 
                        required
                        class="liquid-input w-full px-5 py-3.5 text-sm"
                    >
                    
                    <div class="mt-3 flex items-center">
                        <label class="flex items-center text-white/60 hover:text-white text-xs cursor-pointer select-none">
                            <input 
                                type="checkbox" 
                                id="showPassword" 
                                class="mr-2 rounded accent-blue-600"
                            >
                            Tampilkan Password
                        </label>
                    </div>
                </div>
                
                <button 
                    type="submit" 
                    name="submit"
                    class="liquid-btn w-full py-4 text-base font-bold shadow-lg mt-4"
                >
                    Daftar Akun
                </button>
            </form>
            
            <div class="mt-8 pt-6 border-t border-white/10 text-center space-y-2 text-sm text-white/60">
                <p>
                    Sudah punya akun? 
                    <a href="login.php" class="text-blue-400 hover:text-blue-300 font-bold underline transition-colors">Masuk di sini</a>
                </p>
                <p class="text-xs text-white/40">
                    Dengan mendaftar, Anda menyetujui <a href="pages/forum/peraturan.html" class="underline hover:text-white/70">Aturan & Privasi Komunitas</a>
                </p>
            </div>
        </div>

    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const repeatPasswordInput = document.getElementById('repeatPassword');
        const showPasswordCheckbox = document.getElementById('showPassword');
        
        showPasswordCheckbox.addEventListener('change', function() {
            const type = this.checked ? 'text' : 'password';
            passwordInput.type = type;
            repeatPasswordInput.type = type;
        });
    </script>
</body>
</html>
