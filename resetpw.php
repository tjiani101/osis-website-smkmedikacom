<?php
session_start();
include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = mysqli_real_escape_string($conn, trim($_POST['code']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi password
    if ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        // Cek kode reset
        $reset_query = mysqli_query($conn, 
            "SELECT * FROM password_resets 
            WHERE BINARY code = '$code'
            AND expires_at > CONVERT_TZ(NOW(), 'SYSTEM', '+07:00')
            AND is_used = 0");
        
        if (mysqli_num_rows($reset_query) > 0) {
            $reset = mysqli_fetch_assoc($reset_query);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            mysqli_query($conn, 
                "UPDATE users SET password_hash = '$hashed_password' 
                WHERE id = {$reset['user_id']}");
            
            mysqli_query($conn, 
                "UPDATE password_resets SET is_used = 1 
                WHERE id = {$reset['id']}");
            
            $success = "Password berhasil direset! Silakan login dengan password baru.";
        } else {
            $error = "Kode reset tidak valid atau sudah kadaluarsa!";
        }
    }
}

// Handle GET request
if (isset($_GET['code'])) {
    $code = mysqli_real_escape_string($conn, trim($_GET['code']));
    $reset_query = mysqli_query($conn, 
        "SELECT * FROM password_resets 
        WHERE BINARY code = '$code' 
        AND expires_at > CONVERT_TZ(NOW(), 'SYSTEM', '+07:00') 
        AND is_used = 0");
    
    if (mysqli_num_rows($reset_query) === 0) {
        $error = "Kode reset tidak valid atau sudah kadaluarsa!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - OSIS & MPK SMK Medikacom</title>
    
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

    <!-- Container -->
    <div class="w-full max-w-md">
        
        <div class="mb-4 text-center sm:text-left">
            <a href="login.php" class="inline-flex items-center gap-2 text-white/70 hover:text-white text-sm font-semibold transition-colors bg-white/5 hover:bg-white/10 px-4 py-2 rounded-full border border-white/10 backdrop-blur-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Login
            </a>
        </div>

        <div class="liquid-glass p-8 sm:p-10 relative overflow-hidden">
            <div class="text-center mb-8">
                <div class="inline-block p-3 bg-white/10 rounded-2xl mb-4 border border-white/20 shadow-lg">
                    <img class="h-10 w-auto" src="images/logo.png" alt="Logo OSIS MPK" onerror="this.onerror=null;this.src='https://placehold.co/100x100/ffffff/000000?text=Logo';">
                </div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight">Atur Ulang Sandi</h1>
                <p class="text-white/60 text-sm mt-1">Masukkan kode verifikasi dan sandi baru Anda</p>
            </div>

            <?php if ($error): ?>
                <div class="p-4 mb-6 bg-red-500/20 border border-red-500/40 rounded-2xl text-red-200 text-center text-sm font-medium">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="p-4 mb-6 bg-emerald-500/20 border border-emerald-500/40 rounded-2xl text-emerald-200 text-center text-sm font-medium">
                    <?= htmlspecialchars($success) ?>
                    <div class="mt-4">
                        <a href="login.php" class="liquid-btn px-6 py-2.5 text-sm font-bold">Menuju Login</a>
                    </div>
                </div>
            <?php else: ?>
                <form class="space-y-4" method="POST">
                    <div>
                        <label class="block text-white/80 text-sm font-semibold mb-2">Kode Reset</label>
                        <input 
                            type="text" 
                            name="code" 
                            required 
                            value="<?= htmlspecialchars($_GET['code'] ?? '') ?>" 
                            class="liquid-input w-full px-5 py-3.5 text-sm font-mono tracking-wider" 
                            placeholder="Kode reset token"
                            <?= isset($_GET['code']) ? 'readonly' : '' ?>
                        >
                    </div>
                    <div>
                        <label class="block text-white/80 text-sm font-semibold mb-2">Password Baru</label>
                        <input 
                            type="password" 
                            name="password" 
                            required 
                            class="liquid-input w-full px-5 py-3.5 text-sm"
                            placeholder="Minimal 6 karakter"
                        >
                    </div>
                    <div>
                        <label class="block text-white/80 text-sm font-semibold mb-2">Konfirmasi Password</label>
                        <input 
                            type="password" 
                            name="confirm_password" 
                            required 
                            class="liquid-input w-full px-5 py-3.5 text-sm"
                            placeholder="Ketik ulang password baru"
                        >
                    </div>

                    <button type="submit" class="liquid-btn w-full py-4 text-base font-bold shadow-lg mt-4">
                        Perbarui Password
                    </button>
                </form>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
