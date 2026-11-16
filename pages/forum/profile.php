<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil data user
$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM users WHERE id = $user_id"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Forum OSIS Medikacom</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Mulish', sans-serif;
            background-color: #0f172a;
            color: #cbd5e1;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-card {
            background: linear-gradient(135deg, #1e293b, #2d3748);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid #334155;
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            z-index: 0;
        }

        .profile-header {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding-top: 30px;
        }

        .avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 4px solid #0f172a;
            background: #1e293b;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-info h1 {
            margin: 0;
            font-size: 2.2rem;
            color: white;
            margin-bottom: 5px;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .user-info .nis {
            color: #93c5fd;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .btn-primary {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #60a5fa, #93c5fd);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-danger {
            background: linear-gradient(90deg, #ef4444, #f87171);
            color: white;
        }

        .btn-danger:hover {
            background: linear-gradient(90deg, #f87171, #fca5a5);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            padding: 25px 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #334155;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            border-color: #60a5fa;
        }

        .stat-card h3 {
            color: #93c5fd;
            margin: 0 0 10px;
            font-size: 1rem;
            font-weight: 500;
        }

        .stat-card p {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .avatar {
                width: 120px;
                height: 120px;
            }

            .user-info h1 {
                font-size: 1.8rem;
            }

            .action-buttons {
                gap: 10px;
            }

            .btn {
                padding: 10px 20px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .profile-card {
                padding: 20px 15px;
            }

            .avatar {
                width: 100px;
                height: 100px;
            }

            .user-info h1 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="profile-card">
        <div class="profile-header">
            <div class="avatar">
                <img src="uploads/<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
            </div>

            <div class="user-info">
                <h1><?= htmlspecialchars($user['username']) ?></h1>
                <p class="nis">NIS: <?= htmlspecialchars($user['nis']) ?></p>
            </div>

            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Home
                </a>
                <a href="edit_profile.php" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Tier</h3>
                <p><?= htmlspecialchars($user['tier']) ?></p>
            </div>
            <div class="stat-card">
                <h3>Status</h3>
                <p><?= htmlspecialchars($user['status']) ?></p>
            </div>
            <div class="stat-card">
                <h3>Bergabung</h3>
                <p><?= date('d M Y', strtotime($user['joined_at'])) ?></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>