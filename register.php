<?php
include 'db.php';
$message = '';

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $nis = $_POST['nis']; // This will store email but keep the field name as nis
    $password = $_POST['password'];
    $repeat_password = $_POST['repeat_password'];

    // Check if passwords match
    if ($password !== $repeat_password) {
        $message = "<div class='p-3 mb-4 bg-red-500/20 border border-red-500/40 rounded-xl text-red-200 text-center'>Password tidak sesuai!</div>";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'murid'; // default
        $status = 'active';
        $warning_nis = 1; // selalu warning

        // cek apakah username udah dipakai
        $cek = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $message = "<div class='p-3 mb-4 bg-red-500/20 border border-red-500/40 rounded-xl text-red-200 text-center'>Username sudah digunakan.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, nis, password_hash, role, status, warning_nis) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $username, $nis, $password_hash, $role, $status, $warning_nis);
            if ($stmt->execute()) {
                $message = "<div class='p-3 mb-4 bg-green-500/20 border border-green-500/40 rounded-xl text-green-200 text-center'>Akun berhasil dibuat! Silakan login.</div>";
            } else {
                $message = "<div class='p-3 mb-4 bg-red-500/20 border border-red-500/40 rounded-xl text-red-200 text-center'>Gagal mendaftar.</div>";
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
    <title>Register - Forum OSIS & MPK</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0a0a0a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow-x: hidden;
        }
        
        .background-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            z-index: -1;
            background-image: url('images/slider/IMG_6393.jpg');
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
            max-width: 450px;
            width: 100%;
        }
        
        .gradient-border::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, #3b82f6, #60a5fa);
            border-radius: 1.5rem 1.5rem 0 0;
        }
        
        .gradient-text {
            background: linear-gradient(to right, #3b82f6, #60a5fa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .neon-glow-button {
            transition: all 0.3s ease;
            box-shadow: 0 0 5px rgba(59, 130, 246, 0), 0 0 10px rgba(59, 130, 246, 0);
        }
        
        .neon-glow-button:hover {
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.6), 0 0 25px rgba(37, 99, 235, 0.4);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Background Elements -->
    <div class="background-container"></div>
    <div class="background-overlay"></div>
    
    <!-- Register Form -->
    <div class="glass-card p-8 relative gradient-border">
        <h1 class="text-3xl font-bold text-center mb-6 gradient-text">REGISTER</h1>
        
        <?= $message ?>
        
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-gray-300 mb-2">Username</label>
                <input 
                    type="text" 
                    name="username" 
                    placeholder="Buat username unik" 
                    required
                    class="w-full bg-white/5 border border-white/20 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-400 focus:outline-none"
                >
            </div>
            
            <div>
                <label class="block text-gray-300 mb-2">Email Aktif</label>
                <input 
                    type="email" 
                    name="nis" 
                    placeholder="Masukkan email aktif" 
                    required
                    class="w-full bg-white/5 border border-white/20 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-400 focus:outline-none"
                >
                
                <!--
                <div class="mt-2 p-3 bg-yellow-500/10 border border-yellow-500/30 rounded-lg text-yellow-300 text-sm">
                    ⚠️ Jika email Anda tidak terdeteksi dalam database SMK Medikacom,
                    akun Anda tetap bisa dibuat, namun berpotensi diban. Pastikan Anda sudah membaca peraturan 
                    <a href="peraturan.html" class="font-medium underline">di sini</a>
                </div>-->
            </div>
            
            <div>
                <label class="block text-gray-300 mb-2">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password"
                    placeholder="Buat password" 
                    required
                    class="w-full bg-white/5 border border-white/20 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-400 focus:outline-none"
                >
                
                <div class="mt-2 flex items-center">
                    <input 
                        type="checkbox" 
                        id="showPassword" 
                        class="mr-2 accent-blue-500"
                    >
                    <label for="showPassword" class="text-gray-400 text-sm">Tampilkan Password</label>
                </div>
            </div>
            
            <div>
                <label class="block text-gray-300 mb-2">Ulangi Password</label>
                <input 
                    type="password" 
                    name="repeat_password" 
                    id="repeatPassword"
                    placeholder="Ulangi password" 
                    required
                    class="w-full bg-white/5 border border-white/20 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-400 focus:outline-none"
                >
                
                <div class="mt-2 flex items-center">
                    <input 
                        type="checkbox" 
                        id="showRepeatPassword" 
                        class="mr-2 accent-blue-500"
                    >
                    <label for="showRepeatPassword" class="text-gray-400 text-sm">Tampilkan Password</label>
                </div>
            </div>
            
            <button 
                type="submit" 
                name="submit"
                class="w-full bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold neon-glow-button"
            >
                Daftar
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <p class="text-gray-400">
                Sudah punya akun? 
                <a href="login.php" class="text-blue-400 hover:text-blue-300 font-medium">Login di sini</a>
            </p>
        </div>
    </div>

    <script>
        // Password toggle functionality
        const passwordInput = document.getElementById('password');
        const repeatPasswordInput = document.getElementById('repeatPassword');
        const showPasswordCheckbox = document.getElementById('showPassword');
        const showRepeatPasswordCheckbox = document.getElementById('showRepeatPassword');
        
        showPasswordCheckbox.addEventListener('change', function() {
            passwordInput.type = this.checked ? 'text' : 'password';
        });
        
        showRepeatPasswordCheckbox.addEventListener('change', function() {
            repeatPasswordInput.type = this.checked ? 'text' : 'password';
        });
    </script>
</body>
</html>