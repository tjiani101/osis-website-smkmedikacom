<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

// Hanya contributor yang boleh akses
if ($user['tier'] !== 'Contributor') {
    header("Location: index.php");
    exit;
}

// Ambil semua materi
$materials = mysqli_query($conn, "SELECT lm.*, u.username 
                                 FROM lightroom_materials lm 
                                 JOIN users u ON lm.user_id = u.id 
                                 ORDER BY lm.created_at DESC");

// Jika ada parameter id, ambil materi spesifik
$single_material = null;
if (isset($_GET['id'])) {
    $material_id = intval($_GET['id']);
    $result = mysqli_query($conn, "SELECT lm.*, u.username 
                                  FROM lightroom_materials lm 
                                  JOIN users u ON lm.user_id = u.id 
                                  WHERE lm.id = $material_id");
    if (mysqli_num_rows($result) > 0) {
        $single_material = mysqli_fetch_assoc($result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri OSIS - Lightroom Materials</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 240px;
            --bg-dark: #0f172a;
            --bg-darker: #0b1120;
            --accent: #3b82f6;
            --accent-gradient: linear-gradient(to right, #3b82f6, #22d3ee);
            --text-light: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-dark);
            color: var(--text-light);
            font-family: 'Mulish', sans-serif;
            display: flex;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: 50%;
            transform: translateX(-50%);
            width: 100vw;
            max-width: 800px;
            height: 400px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%);
            filter: blur(100px);
            z-index: -1;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: rgba(11, 17, 32, 0.8);
            padding: 1rem;
            position: fixed;
            height: 100%;
            transition: transform 0.3s;
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(59, 130, 246, 0.2);
            z-index: 100;
        }

        .sidebar-header {
            padding: 1rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-header h2 {
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-weight: 800;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .nav-link i {
            width: 24px;
            margin-right: 1rem;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .card {
            background: rgba(15, 23, 42, 0.5);
            padding: 1.5rem;
            border-radius: 12px;
            position: relative;
            border: 1px solid rgba(59, 130, 246, 0.2);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-gradient);
            border-radius: 12px 12px 0 0;
        }

        .card h3 {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .card p {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            background: var(--accent-gradient);
            padding: 0.75rem;
            border-radius: 8px;
            border: none;
            color: white;
            cursor: pointer;
        }

        .mobile-menu-toggle i {
            font-size: 1.2rem;
        }

        .main-content h1 {
            font-size: 2.2rem;
            margin-bottom: 1.5rem;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-weight: 800;
        }

        .container {
            max-width: 900px;
            margin: 3rem auto 0;
            background: rgba(15, 23, 42, 0.5);
            padding: 30px;
            border-radius: 12px;
            border: 1px solid rgba(59, 130, 246, 0.2);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .container h1 {
            font-size: 1.8rem;
            margin-bottom: 20px;
        }

        .container h2 {
            font-size: 1.4rem;
            margin-top: 25px;
            color: #3b82f6;
        }

        .container p, .container ul {
            font-size: 1rem;
            color: #cbd5e1;
            line-height: 1.8;
        }

        .container ul {
            padding-left: 20px;
        }

        .container ul li {
            margin-bottom: 10px;
        }

        .container strong {
            color: #38bdf8;
        }

        .container .highlight {
            color: #facc15;
        }

        /* Tambahan untuk lightroom */
        .materials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .material-card {
            background: rgba(15, 23, 42, 0.5);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(59, 130, 246, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .material-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .material-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .material-content {
            padding: 1.5rem;
        }

        .material-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .material-desc {
            color: var(--text-muted);
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .material-uploader {
            color: #38bdf8;
            font-size: 0.85rem;
        }

        .video-container {
            position: relative;
            width: 100%;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            margin: 1.5rem 0;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 8px;
        }

        .back-button {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
        }

        .back-button i {
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .mobile-menu-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
<button class="mobile-menu-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Galeri OSIS</h2>
    </div>
    <nav>
        <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i> Overview
        </a>
        <a href="galery.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'galery.php' ? 'active' : '' ?>">
            <i class="fas fa-images"></i> Galeri
        </a>
        <a href="albums.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'albums.php' ? 'active' : '' ?>">
            <i class="fas fa-book"></i> Album
        </a>
        <a href="lightroom.php" class="nav-link active">
            <i class="fas fa-graduation-cap"></i> Lightroom
        </a>
        <div class="sidebar-footer">
            <a href="../index.html" class="nav-link">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </nav>
</aside>

<main class="main-content">
    <?php if ($single_material): ?>
        <a href="lightroom.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar Materi
        </a>
        <h1><?= htmlspecialchars($single_material['title']) ?></h1>
        <div class="container">
            <div class="video-container">
                <iframe src="<?= htmlspecialchars($single_material['youtube_embed']) ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
            <div>
                <?= nl2br(htmlspecialchars($single_material['content'])) ?>
            </div>
            <p class="material-uploader">Uploaded by: <?= htmlspecialchars($single_material['username']) ?></p>
        </div>
    <?php else: ?>
        <h1>Materi Lightroom</h1>
        <p>Berikut adalah kumpulan materi Lightroom yang bisa Anda pelajari.</p>
        <div class="materials-grid">
            <?php while ($row = mysqli_fetch_assoc($materials)): ?>
                <a style="color: white; text-decoration: none;" href="lightroom.php?id=<?= $row['id'] ?>" class="material-card">
                    <img src="<?= htmlspecialchars($row['thumbnail']) ?>" alt="Thumbnail" class="material-thumbnail">
                    <div class="material-content">
                        <h3 class="material-title"><?= htmlspecialchars($row['title']) ?></h3>
                        <p class="material-desc"><?= htmlspecialchars($row['description']) ?></p>
                        <p class="material-uploader">Uploaded by: <?= htmlspecialchars($row['username']) ?></p>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</main>

<script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.querySelector('.mobile-menu-toggle');

        if (window.innerWidth <= 768 &&
            !sidebar.contains(e.target) &&
            !toggleBtn.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    });
</script>
</body>
</html>