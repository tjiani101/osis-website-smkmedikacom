<?php
session_start();
include 'db.php';

// Create notifications table if not exists
$conn->query("
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    actor_id INT DEFAULT NULL,
    type ENUM('like', 'comment', 'mention', 'follow') NOT NULL,
    source_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (actor_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Logging function
function simpanLog($conn, $username, $action) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO logs (timestamp, username, action, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $timestamp, $username, $action, $ip);
    $stmt->execute();
}

$thread_id = $_GET['id'] ?? null;
if (!$thread_id) die("Thread tidak ditemukan.");

// Log thread view
if(isset($_SESSION['username'])) {
    simpanLog($conn, $_SESSION['username'], "Viewed thread ID: $thread_id");
}

// Get notifications for logged-in user
$notifications = [];
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $notif_query = $conn->query("
        SELECT n.*, u.username AS actor_name 
        FROM notifications n
        LEFT JOIN users u ON n.actor_id = u.id
        WHERE n.user_id = $user_id
        ORDER BY n.created_at DESC
        LIMIT 5
    ");

    $notifications = $notif_query->fetch_all(MYSQLI_ASSOC);

    // Get unread count
    $unread_result = $conn->query("
        SELECT COUNT(*) AS count 
        FROM notifications 
        WHERE user_id = $user_id AND is_read = 0
    ");
    $unread_count = $unread_result->fetch_assoc()['count'];
}

// Ambil data thread
$stmt = $conn->prepare("SELECT t.*, u.username FROM threads t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->bind_param("i", $thread_id);
$stmt->execute();
$result = $stmt->get_result();
$thread = $result->fetch_assoc();
if (!$thread) die("Thread tidak tersedia.");

// Bangun struktur komentar
$komen = $conn->prepare("SELECT k.*, u.username, u.role FROM comments k JOIN users u ON k.user_id = u.id WHERE k.thread_id = ? ORDER BY k.created_at ASC");
$komen->bind_param("i", $thread_id);
$komen->execute();
$komen_result = $komen->get_result();

$comments = [];
while ($row = $komen_result->fetch_assoc()) $comments[] = $row;

$commentTree = [];
foreach ($comments as $comment) {
    $parentId = $comment['parent_id'] ?? 'root';
    $commentTree[$parentId][] = $comment;
}

function displayComments($parentId, &$commentTree, $level = 0) {
    if (empty($commentTree[$parentId])) return;

    foreach ($commentTree[$parentId] as $comment) {
        $margin = $level * 30;
        ?>
        <div class="comment" style="margin-left: <?= $margin ?>px;">
            <div class="comment-header">
                <div class="comment-author">
                    <a href="users.php?username=<?= htmlspecialchars($comment['username']) ?>">
                        <?= htmlspecialchars($comment['username']) ?>
                    </a>
                    <span class="comment-role"><?= strtoupper($comment['role']) ?></span>
                </div>
                <?php if (isset($_SESSION['user_id']) && is_null($comment['parent_id'])): ?>
                    <button class="reply-btn" data-parent-id="<?= $comment['id'] ?>"
                            style="margin-left:8px; padding:5px 10px; background:linear-gradient(to right, #3b82f6, #60a5fa); color:white; border:none; border-radius:20px; font-size:0.8em; cursor:pointer; transition: all 0.3s ease;">
                        Reply
                    </button>
                <?php endif; ?>
            </div>
            <div class="comment-content">
                <?= nl2br(htmlspecialchars($comment['komentar'])) ?>
            </div>
            <?php displayComments($comment['id'], $commentTree, $level + 1); ?>
        </div>
        <?php
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($thread['judul']) ?> - Forum Medikacom</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        a {
            text-decoration: none;
            color: #60a5fa;
            transition: all 0.3s ease;
        }

        a:hover {
            color: #93c5fd;
        }

        .gradient-text {
            background-image: linear-gradient(to right, #3b82f6, #60a5fa, #93c5fd);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .sidebar {
            flex: 0 0 280px;
            background-color: #1e293b;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #334155;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .sidebar h2 {
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: #60a5fa;
            border-bottom: 2px solid #334155;
            padding-bottom: 10px;
            font-weight: 700;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin-bottom: 20px;
        }

        .sidebar ul li {
            margin: 10px 0;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar ul li:hover {
            background-color: #334155;
        }

        .sidebar ul li a {
            color: #e2e8f0;
            font-weight: 600;
            font-size: 1rem;
            display: block;
        }

        .auth-links {
            font-size: 1rem;
            padding-top: 15px;
            border-top: 1px solid #334155;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .main-content {
            flex: 1;
            min-width: 0;
        }

        .thread {
            background: #1e293b;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid #334155;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .thread::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #3b82f6, #60a5fa);
        }

        .thread h2 {
            margin-top: 0;
            color: #f8fafc;
            font-size: 1.8rem;
            font-weight: 700;
            padding-bottom: 15px;
            border-bottom: 2px solid #334155;
            margin-bottom: 20px;
        }

        .thread p {
            font-size: 1.1rem;
            line-height: 1.7;
            color: #cbd5e1;
        }

        .thread-footer {
            font-size: 0.95rem;
            color: #93c5fd;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #334155;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .media-container {
            margin: 20px 0;
            max-width: 100%;
            border-radius: 8px;
            overflow: hidden;
        }

        .media-container img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            border: 1px solid #334155;
            display: block;
        }

        .media-container video {
            max-width: 100%;
            max-height: 500px;
            background: #000;
            border-radius: 8px;
            border: 1px solid #334155;
            display: block;
        }

        /* Comment Form Styles */
        .comment-form-container {
            position: relative;
        }

        textarea {
            width: 100%;
            padding: 15px;
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 8px;
            resize: vertical;
            min-height: 150px;
            font-family: 'Mulish', sans-serif;
            font-size: 1rem;
            color: #f8fafc;
            transition: all 0.3s ease;
        }

        textarea:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2);
        }

        .btn {
            background: linear-gradient(to right, #3b82f6, #60a5fa);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            cursor: pointer;
            margin-top: 15px;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Mulish', sans-serif;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn:hover {
            background: linear-gradient(to right, #60a5fa, #93c5fd);
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .comment {
            margin-bottom: 20px;
            padding: 20px;
            background: #1e293b;
            border-radius: 8px;
            border: 1px solid #334155;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .comment:hover {
            border-color: #60a5fa;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .comment-author {
            font-weight: 700;
            color: #f8fafc;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .comment-role {
            font-size: 0.8rem;
            background: linear-gradient(to right, #3b82f6, #60a5fa);
            color: #f8fafc;
            padding: 3px 12px;
            border-radius: 20px;
        }

        .comment-content {
            margin-top: 10px;
            padding-left: 5px;
            color: #cbd5e1;
            font-size: 1rem;
            line-height: 1.6;
        }

        .no-comments {
            color: #93c5fd;
            font-style: italic;
            text-align: center;
            padding: 30px;
            font-size: 1.1rem;
        }

        .login-prompt {
            text-align: center;
            padding: 20px;
            background: #1e293b;
            border-radius: 8px;
            border: 1px solid #334155;
            font-size: 1.1rem;
        }

        .login-prompt a {
            font-weight: 600;
        }

        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1000;
            font-size: 24px;
            background: #1e293b;
            color: #cbd5e1;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #334155;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Notification badge */
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }

        /* Notification dropdown */
        .notifications-dropdown {
            position: relative;
            display: inline-block;
        }

        .notifications-content {
            display: none;
            position: absolute;
            background-color: #1e293b;
            min-width: 300px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 8px;
            border: 1px solid #334155;
            padding: 10px;
            max-height: 400px;
            overflow-y: auto;
            right: 0;
            top: 100%;
            margin-top: 10px;
        }

        .notifications-content.active {
            display: block;
        }

        .notification-item {
            padding: 10px;
            border-bottom: 1px solid #334155;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-item:hover {
            background-color: #334155;
        }

        .notification-item.unread {
            background-color: rgba(59, 130, 246, 0.1);
            border-left: 3px solid #3b82f6;
        }

        .notification-message {
            font-size: 0.9rem;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 5px;
        }

        .notification-actions {
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #334155;
        }

        .mark-all-read {
            color: #93c5fd;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .mark-all-read:hover {
            text-decoration: underline;
        }

        /* Comment count styling */
        .comment-count {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            color: #93c5fd;
            margin-top: 5px;
        }

        /* Mention Autocomplete Styles */
        .autocomplete {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .autocomplete-items {
            position: absolute;
            border: 1px solid #334155;
            border-bottom: none;
            border-top: none;
            z-index: 99;
            top: 100%;
            left: 0;
            right: 0;
            background: #1e293b;
            max-height: 200px;
            overflow-y: auto;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .autocomplete-items div {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #334155;
            color: #cbd5e1;
            transition: all 0.2s;
        }

        .autocomplete-items div:hover {
            background-color: #334155;
            color: #fff;
        }

        .autocomplete-active {
            background-color: #3b82f6 !important;
            color: white !important;
        }

        @media (max-width: 767px) {
            .container {
                flex-direction: column;
                padding: 15px;
                gap: 15px;
            }

            .sidebar {
                width: 100%;
                position: static;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .thread {
                padding: 20px;
            }

            .thread h2 {
                font-size: 1.5rem;
            }

            .comment {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
<div class="mobile-menu-toggle">☰</div>
<div class="container">
    <aside class="sidebar">
        <h2>Menu</h2>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="index.php?popular=1">Popular</a></li>
            <li><a href="index.php">All</a></li>
            <li><a href="about.html">About</a></li>
        </ul>

        <!-- Notification Section -->
        <?php if (isset($_SESSION['username'])): ?>
            <h2>Notifikasi</h2>
            <div class="notifications-dropdown">
                <a href="#" class="notification-toggle" style="position: relative;">
                    Lihat Notifikasi
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?= $unread_count ?></span>
                    <?php endif; ?>
                </a>
                <div class="notifications-content">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notif): ?>
                            <div class="notification-item <?= $notif['is_read'] ? '' : 'unread' ?>" data-id="<?= $notif['id'] ?>">
                                <div class="notification-message">
                                    <?= $notif['message'] ?>
                                </div>
                                <div class="notification-time">
                                    <?= date('d M Y H:i', strtotime($notif['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="notification-actions">
                            <a href="notifications.php">Lihat Semua</a>
                            <span class="mark-all-read">Tandai Sudah Dibaca</span>
                        </div>
                    <?php else: ?>
                        <div class="notification-item">
                            <div class="notification-message">
                                Tidak ada notifikasi
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="auth-links">
            <?php if (isset($_SESSION['username'])): ?>
                <a href="buat_post.php">Create Post</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </aside>

    <div class="main-content">
        <div class="thread">
            <h2><?= htmlspecialchars($thread['judul']) ?></h2>
            <p><?= nl2br(htmlspecialchars($thread['konten'])) ?></p>

            <?php if (!empty($thread['gambar'])): ?>
                <div class="media-container">
                    <?php if (isset($thread['media_type']) && $thread['media_type'] === 'video'): ?>
                        <video controls>
                            <source src="post/<?= htmlspecialchars($thread['gambar']) ?>" type="video/mp4">
                            Browser Anda tidak mendukung pemutaran video.
                        </video>
                    <?php else: ?>
                        <img src="post/<?= htmlspecialchars($thread['gambar']) ?>" alt="Gambar post">
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="thread-footer">
                <a href="users.php?username=<?= htmlspecialchars($thread['username']) ?>">Dipost oleh: <?= htmlspecialchars($thread['username']) ?></a>
                <span><?= date('d M Y H:i', strtotime($thread['created_at'])) ?></span>
                <div class="comment-count">
                    <span>💬 <?= count($comments) ?> Komentar</span>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="thread">
                <h3>Tambah Komentar</h3>
                <div class="comment-form-container">
                    <form method="post" action="komentar_proses.php">
                        <div class="autocomplete">
                            <textarea name="isi" id="commentTextarea" placeholder="Tulis komentar Anda... Ketik '@' untuk mention user" required></textarea>
                        </div>
                        <input type="hidden" name="thread_id" value="<?= $thread_id ?>">
                        <input type="hidden" name="parent_id" id="parentIdInput" value="">
                        <button type="submit" class="btn">Kirim Komentar</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="thread login-prompt">
                <p><a href="login.php">Login</a> untuk menambahkan komentar.</p>
            </div>
        <?php endif; ?>

        <div class="thread">
            <h3>Komentar (<?= count($comments) ?>)</h3>
            <?php if (empty($comments)): ?>
                <p class="no-comments">Belum ada komentar. Jadilah yang pertama!</p>
            <?php else: ?>
                <?php displayComments('root', $commentTree); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.reply-btn').forEach(button => {
        button.addEventListener('click', function() {
            const parentId = this.dataset.parentId;
            document.getElementById('parentIdInput').value = parentId;
            document.getElementById('commentTextarea').focus();
            document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
        });
    });

    // Mobile menu toggle
    document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });

    // Notification toggle
    const notificationToggle = document.querySelector('.notification-toggle');
    if (notificationToggle) {
        notificationToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const content = this.nextElementSibling;
            content.classList.toggle('active');
        });
    }

    // Mark notification as read
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            const notifId = this.dataset.id;
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${notifId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.classList.remove('unread');
                        updateUnreadCount();
                    }
                });
        });
    });

    // Mark all as read
    const markAllRead = document.querySelector('.mark-all-read');
    if (markAllRead) {
        markAllRead.addEventListener('click', function() {
            fetch('mark_all_read.php', {
                method: 'POST'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll('.notification-item').forEach(item => {
                            item.classList.remove('unread');
                        });
                        updateUnreadCount();
                    }
                });
        });
    }

    // Update unread notification count
    function updateUnreadCount() {
        fetch('get_unread_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-badge');
                if (data.count > 0) {
                    if (!badge) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'notification-badge';
                        newBadge.textContent = data.count;
                        document.querySelector('.notification-toggle').appendChild(newBadge);
                    } else {
                        badge.textContent = data.count;
                    }
                } else if (badge) {
                    badge.remove();
                }
            });
    }

    // Mention Autocomplete Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const textarea = document.getElementById('commentTextarea');
        const autocompleteContainer = textarea.closest('.autocomplete');
        let currentFocus = -1;
        let lastAtPos = -1;

        textarea.addEventListener('input', function(e) {
            const val = this.value;
            const cursorPos = this.selectionStart;

            // Check if we're typing after an '@' symbol
            const textBeforeCursor = val.substring(0, cursorPos);
            const atPos = textBeforeCursor.lastIndexOf('@');

            // Close any existing autocomplete lists
            closeAllLists();

            if (atPos > -1 && (cursorPos - atPos > 1)) {
                const searchTerm = textBeforeCursor.substring(atPos + 1, cursorPos).toLowerCase();

                if (searchTerm.length > 0) {
                    lastAtPos = atPos;

                    // Fetch matching users from server
                    fetch(`get_users.php?search=${encodeURIComponent(searchTerm)}`)
                        .then(response => response.json())
                        .then(users => {
                            if (users.length > 0) {
                                const autocompleteList = document.createElement("div");
                                autocompleteList.setAttribute("id", "autocomplete-list");
                                autocompleteList.className = "autocomplete-items";

                                users.forEach(user => {
                                    const item = document.createElement("div");
                                    item.innerHTML = `<strong>${user.username}</strong>`;
                                    item.dataset.username = user.username;

                                    item.addEventListener("click", function() {
                                        insertUsername(this.dataset.username);
                                        closeAllLists();
                                    });

                                    autocompleteList.appendChild(item);
                                });

                                autocompleteContainer.appendChild(autocompleteList);
                            }
                        });
                }
            }
        });

        textarea.addEventListener('keydown', function(e) {
            let autocompleteList = document.getElementById("autocomplete-list");
            if (autocompleteList) {
                const items = autocompleteList.getElementsByTagName("div");

                if (e.key === "ArrowDown") {
                    e.preventDefault();
                    currentFocus = (currentFocus < items.length - 1) ? currentFocus + 1 : 0;
                    setActive(items);
                } else if (e.key === "ArrowUp") {
                    e.preventDefault();
                    currentFocus = (currentFocus > 0) ? currentFocus - 1 : items.length - 1;
                    setActive(items);
                } else if (e.key === "Enter") {
                    e.preventDefault();
                    if (currentFocus > -1 && items[currentFocus]) {
                        items[currentFocus].click();
                    }
                } else if (e.key === "Escape") {
                    closeAllLists();
                }
            }
        });

        function setActive(items) {
            if (!items || items.length === 0) return false;

            // Remove active class from all items
            for (let i = 0; i < items.length; i++) {
                items[i].classList.remove("autocomplete-active");
            }

            // Add active class to current item
            if (currentFocus >= items.length) currentFocus = 0;
            if (currentFocus < 0) currentFocus = items.length - 1;
            items[currentFocus].classList.add("autocomplete-active");

            // Scroll into view
            items[currentFocus].scrollIntoView({
                block: 'nearest',
                behavior: 'smooth'
            });
        }

        function insertUsername(username) {
            if (lastAtPos === -1) return;

            const val = textarea.value;
            const textBefore = val.substring(0, lastAtPos + 1);
            const textAfter = val.substring(textarea.selectionStart);

            textarea.value = textBefore + username + " " + textAfter;
            lastAtPos = -1;

            // Set cursor position after inserted username
            const newCursorPos = textBefore.length + username.length + 1;
            textarea.selectionStart = newCursorPos;
            textarea.selectionEnd = newCursorPos;
            textarea.focus();
        }

        function closeAllLists() {
            const lists = document.getElementsByClassName("autocomplete-items");
            for (let i = 0; i < lists.length; i++) {
                lists[i].parentNode.removeChild(lists[i]);
            }
            currentFocus = -1;
        }

        // Close autocomplete when clicking outside
        document.addEventListener('click', function(e) {
            if (!autocompleteContainer.contains(e.target)) {
                closeAllLists();
            }
        });
    });
</script>
</body>
</html>