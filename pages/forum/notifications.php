<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all notifications
$stmt = $conn->prepare("
    SELECT n.*, u.username AS actor_name 
    FROM notifications n
    LEFT JOIN users u ON n.actor_id = u.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);

// Mark all as read
$conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - Forum OSIS Medikacom</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
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
            line-height: 1.5;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px 0;
        }

        h1 {
            font-size: 2rem;
            color: #f8fafc;
            background: linear-gradient(to right, #3b82f6, #60a5fa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            padding: 10px 0;
        }

        .notification-list {
            background: #1e293b;
            border-radius: 12px;
            border: 1px solid #334155;
            overflow: hidden;
        }

        .notification-item {
            padding: 20px;
            border-bottom: 1px solid #334155;
            transition: background-color 0.2s;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background-color: #334155;
        }

        .notification-message {
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .notification-time {
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .no-notifications {
            text-align: center;
            padding: 40px;
            font-size: 1.1rem;
            color: #93c5fd;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: linear-gradient(to right, #3b82f6, #60a5fa);
            color: white;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>Notifikasi</h1>
    </header>

    <div class="notification-list">
        <?php if (empty($notifications)): ?>
            <div class="no-notifications">
                Tidak ada notifikasi
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item">
                    <div class="notification-message">
                        <?= $notif['message'] ?>
                    </div>
                    <div class="notification-time">
                        <?= date('d M Y H:i', strtotime($notif['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="index.php" class="back-link">Kembali ke Forum</a>
    </div>
</div>
</body>
</html>