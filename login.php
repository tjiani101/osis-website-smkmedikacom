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
    $username = $_POST['username'];
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

            // Log successful login
            simpanLog($conn, $username, 'Login successful');

            header("Location: /gallery");
            exit();
        } else {
            $message = "<div class='p-3 mb-4 bg-red-500/20 border border-red-500/40 rounded-xl text-red-200 text-center'>Password salah!</div>";
            // Log failed password attempt
            simpanLog($conn, $username, 'Failed login: Incorrect password');
        }
    } else {
        $message = "<div class='p-3 mb-4 bg-red-500/20 border border-red-500/40 rounded-xl text-red-200 text-center'>Akun tidak ditemukan atau diban.</div>";
        // Log attempt for non-existent or banned account
        simpanLog($conn, $username, 'Failed login: Account not found or banned');
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Forum OSIS & MPK</title>
    
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
    
    <!-- Login Form -->
    <div class="glass-card p-8 relative gradient-border">
        <h1 class="text-3xl font-bold text-center mb-8 gradient-text">LOGIN</h1>
        
        <?= $message ?>
        
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-gray-300 mb-2">Username</label>
                <input 
                    type="text" 
                    name="username" 
                    placeholder="Masukkan username" 
                    required
                    class="w-full bg-white/5 border border-white/20 rounded-xl px-4 py-3 text-white focus:ring-2 focus:ring-blue-400 focus:outline-none"
                >
            </div>
            
            <div>
                <label class="block text-gray-300 mb-2">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    id="password"
                    placeholder="Masukkan password" 
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
            
            <button 
                type="submit" 
                name="submit"
                class="w-full bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold neon-glow-button"
            >
                Masuk
            </button>
        </form>
        
        <div class="mt-6 text-center space-y-3">
            <p class="text-gray-400">
                Belum punya akun? 
                <a href="register.php" class="text-blue-400 hover:text-blue-300 font-medium">Daftar sekarang</a>
            </p>
            <p class="text-gray-400">
                Belum baca aturan? 
                <a href="peraturan.html" class="text-blue-400 hover:text-blue-300 font-medium">Baca sekarang</a>
            </p>
        </div>
    </div>

    <script>
        // Password toggle functionality
        const passwordInput = document.getElementById('password');
        const showPasswordCheckbox = document.getElementById('showPassword');
        
        showPasswordCheckbox.addEventListener('change', function() {
            passwordInput.type = this.checked ? 'text' : 'password';
        });
    </script>
</body>
</html>