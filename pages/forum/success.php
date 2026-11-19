<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil</title>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@200;1000&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 min-h-screen flex items-center justify-center">
<div class="text-center p-8 max-w-2xl">
    <div class="mb-6">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
    <h1 class="text-3xl md:text-4xl font-bold text-green-400 mb-4">Pendaftaran Berhasil!</h1>
    <p class="text-lg text-slate-300 mb-8">
        Kelas Anda telah terdaftar dalam PORSENI 2025. Terima kasih atas partisipasinya.
        <br>Silakan cek WhatsApp untuk informasi lebih lanjut.
    </p>
    <div class="flex justify-center gap-4">
        <a href="index.html" class="px-6 py-3 bg-slate-700 hover:bg-slate-600 rounded-lg font-semibold transition-colors">
            Kembali ke Beranda
        </a>
        <a href="profile.php" class="px-6 py-3 bg-blue-600 hover:bg-blue-500 rounded-lg font-semibold transition-colors">
            Lihat Profil
        </a>
    </div>
</div>
</body>
</html>