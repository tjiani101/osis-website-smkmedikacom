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
    if($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek kode reset
        $reset_query = mysqli_query($conn, 
            "SELECT * FROM password_resets 
            WHERE BINARY code = '$code'
            AND expires_at > CONVERT_TZ(NOW(), 'SYSTEM', '+07:00')
            AND is_used = 0");
        
        if(mysqli_num_rows($reset_query) > 0) {
            $reset = mysqli_fetch_assoc($reset_query);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Perbaikan: Gunakan password_hash sesuai struktur database
            mysqli_query($conn, 
                "UPDATE users SET password_hash = '$hashed_password' 
                WHERE id = {$reset['user_id']}");
            
            mysqli_query($conn, 
                "UPDATE password_resets SET is_used = 1 
                WHERE id = {$reset['id']}");
            
            $success = "Password berhasil direset! Silahkan login";
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
    
    if(mysqli_num_rows($reset_query) === 0) {
        $error = "Kode reset tidak valid atau sudah kadaluarsa!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .bg-gradient-dark {
            background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        }
        .input-field {
            @apply w-full px-4 py-3 bg-gray-700 text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none transition duration-200;
        }
        .btn-primary {
            @apply w-full py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-200 transform hover:scale-105;
        }
    </style>
</head>
<body class="bg-gradient-dark min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8 bg-gray-800 rounded-xl shadow-2xl">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-white mb-2">Reset Password</h2>
            <p class="text-gray-400">Masukkan kode reset dan password baru</p>
        </div>

        <?php if($error): ?>
            <div class="bg-red-500 text-white p-3 rounded-lg"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="bg-green-500 text-white p-3 rounded-lg"><?= $success ?></div>
        <?php else: ?>
            <form class="mt-8 space-y-6" method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Kode Reset</label>
                        <input type="text" name="code" required 
                            value="<?= htmlspecialchars($_GET['code'] ?? '') ?>" 
                            class="input-field" <?= isset($_GET['code']) ? 'readonly' : '' ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Password Baru</label>
                        <input type="password" name="password" required class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Konfirmasi Password</label>
                        <input type="password" name="confirm_password" required class="input-field">
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    Reset Password
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]');
            const confirm = document.querySelector('input[name="confirm_password"]');
            
            if (password.value !== confirm.value) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok!');
            }
        });
    </script>
</body>
</html>