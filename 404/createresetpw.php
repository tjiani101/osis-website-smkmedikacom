<?php
session_start();
include '../db.php';

// Pastikan hanya admin/DEV yang bisa akses
if (!in_array($_SESSION['role'], ['admin', 'DEV'])) {
    header("Location: ../login.php");
    exit;
}

// AKTIFKAN ERROR REPORTING UNTUK DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    
    // Cari user
    $user_query = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    $user = mysqli_fetch_assoc($user_query);
    
    if ($user) {
        // Generate code
        $code = bin2hex(random_bytes(16)); // 32 karakter hex
        $expires = date('Y-m-d H:i:s', strtotime('+48 hour'));
        
        // ========== BAGIAN YANG DIPERBAIKI ========== //
        // Gunakan prepared statement
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, code, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user['id'], $code, $expires);
        
        if ($stmt->execute()) {
            $success = "Reset code untuk $username: <strong>$code</strong> (Expires: $expires)";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
        // ========== END OF FIX ========== //
        
    } else {
        $error = "User tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reset Code</title>
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
            <h2 class="text-3xl font-bold text-white mb-2">Generate Reset Code</h2>
            <p class="text-gray-400">Masukkan username untuk membuat reset code</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="bg-red-500 text-white p-3 rounded-lg"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if(isset($success)): ?>
            <div class="bg-green-500 text-white p-3 rounded-lg"><?= $success ?></div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" method="POST">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                    <input type="text" name="username" required class="input-field">
                </div>
            </div>

            <button type="submit" class="btn-primary">
                Generate Reset Code
            </button>
        </form>
    </div>
</body>
</html>