<?php
session_start();
include 'db.php';

function simpanLog($conn, $username, $action) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO logs (timestamp, username, action, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $timestamp, $username, $action, $ip);
    $stmt->execute();
}

$message = '';

if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            simpanLog($conn, $username, 'Login successful');

            $target = !empty($_REQUEST['redirect']) ? $_REQUEST['redirect'] : 'gallery/';
            header("Location: " . $target);
            exit();
        } else {
            $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Password salah! Silakan coba lagi.</div>";
            simpanLog($conn, $username, 'Failed login: Incorrect password');
        }
    } else {
        $message = "<div class='p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium'>Akun tidak ditemukan atau berstatus nonaktif.</div>";
        simpanLog($conn, $username, 'Failed login: Account not found or banned');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - OSIS & MPK SMK Medikacom</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Liquid Theme Styles -->
    <link rel="stylesheet" href="assets/css/liquid-theme.css">
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative selection:bg-blue-500/30 selection:text-white">

    <!-- Background Elements -->
    <div class="main-background"></div>
    <div class="main-overlay"></div>

    <!-- Login Container -->
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
            <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-24 bg-blue-500/20 rounded-full blur-2xl pointer-events-none"></div>

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-block p-3 bg-white/10 rounded-2xl mb-4 border border-white/20 shadow-lg">
                    <img class="h-10 w-auto" src="images/logo.png" alt="Logo OSIS MPK" onerror="this.onerror=null;this.src='https://placehold.co/100x100/ffffff/000000?text=Logo';">
                </div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight">Selamat Datang</h1>
                <p class="text-white/60 text-sm mt-1">Masuk ke Portal Komunitas & Galeri OSIS</p>
            </div>
            
            <?= $message ?>
            
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-white/80 text-sm font-semibold mb-2">Username</label>
                    <input 
                        type="text" 
                        name="username" 
                        placeholder="Masukkan username Anda" 
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
                        placeholder="Masukkan password Anda" 
                        required
                        class="liquid-input w-full px-5 py-3.5 text-sm"
                    >
                    
                    <div class="mt-3 flex items-center justify-between">
                        <label class="flex items-center text-white/60 hover:text-white text-xs cursor-pointer select-none">
                            <input 
                                type="checkbox" 
                                id="showPassword" 
                                class="mr-2 rounded accent-blue-600"
                            >
                            Tampilkan Password
                        </label>
                        <a href="resetpw.php" class="text-xs text-blue-400 hover:text-blue-300 font-semibold transition-colors">Lupa Password?</a>
                    </div>
                </div>
                
                <button 
                    type="submit" 
                    name="submit"
                    class="liquid-btn w-full py-4 text-base font-bold shadow-lg mt-2"
                >
                    Masuk Sekarang
                </button>
            </form>
            
            <div class="mt-8 pt-6 border-t border-white/10 text-center space-y-2 text-sm text-white/60">
                <p>
                    Belum punya akun? 
                    <a href="register.php" class="text-blue-400 hover:text-blue-300 font-bold underline transition-colors">Daftar sekarang</a>
                </p>
                <p class="text-xs text-white/40">
                    Dengan masuk, Anda menyetujui <a href="pages/forum/peraturan.html" class="underline hover:text-white/70">Aturan Komunitas</a>
                </p>
            </div>
        </div>

    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const showPasswordCheckbox = document.getElementById('showPassword');
        showPasswordCheckbox.addEventListener('change', function() {
            passwordInput.type = this.checked ? 'text' : 'password';
        });
    </script>
</body>
</html>
