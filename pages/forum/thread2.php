<?php
session_start();
include 'db.php';

$thread_id = $_GET['id'] ?? null;
if (!$thread_id) die("Thread tidak ditemukan.");

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
        $margin = $level * 20;
        ?>
        <div class="comment" style="margin-left: <?= $margin ?>px;">
            <div class="comment-author">
                <a class="anjay" href="users.php?username=<?= htmlspecialchars($comment['username']) ?>">
                    <?= htmlspecialchars($comment['username']) ?>
                </a>
                <span class="comment-role"><?= strtoupper($comment['role']) ?></span>
                <?php if (isset($_SESSION['user_id']) && is_null($comment['parent_id'])): ?>
                <button class="reply-btn" data-parent-id="<?= $comment['id'] ?>" 
                        style="margin-left:8px; padding:2px 8px; background:#4a5568; color:white; border:none; border-radius:3px; font-size:0.8em; cursor:pointer;">
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
    <style>
    * {
        box-sizing: border-box;
    }
    body {
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #1a1e22;
        color: #c2c6cc;
        line-height: 1.5;
    }
    .container {
        max-width: 100%;
        padding: 15px;
        margin: 0 auto;
    }
    .thread {
        background: #2e3338;
        border-radius: 6px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #3d434a;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    h2, h3 {
        margin-top: 0;
        color: #e8ecef;
        border-bottom: 2px solid #3d434a;
        padding-bottom: 10px;
    }
    .thread-footer {
        font-size: 0.9em;
        color: #8b9dea;
        margin-top: 15px;
        padding-top: 10px;
        border-top: 1px solid #3d434a;
    }
    .media-container {
        margin: 15px 0;
        max-width: 100%;
    }
    .media-container img {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
        border: 1px solid #3d434a;
    }
    .media-container video {
        max-width: 100%;
        max-height: 400px;
        background: #000;
        border-radius: 4px;
        border: 1px solid #3d434a;
    }
    textarea {
        width: 100%;
        padding: 12px;
        background: #363b41;
        border: 1px solid #3d434a;
        border-radius: 4px;
        resize: vertical;
        min-height: 100px;
        font-family: inherit;
        color: #e8ecef;
    }
    .btn {
        background: #677bc4;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 10px;
        font-size: 16px;
        transition: all 0.2s ease;
    }
    .btn:hover {
        background: #5667a8;
        transform: translateY(-1px);
    }
    .comment {
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #3d434a;
    }
    .comment:last-child {
        border-bottom: none;
    }
    .comment-author {
        font-weight: bold;
        color: #e8ecef;
    }
    .comment-role {
        font-size: 0.8em;
        background: #677bc4;
        color: #e8ecef;
        padding: 2px 8px;
        border-radius: 12px;
        margin-left: 8px;
    }
    .comment-content {
        margin-top: 8px;
        padding-left: 10px;
        color: #c2c6cc;
    }
    .no-comments {
        color: #8b9dea;
        font-style: italic;
    }
    .login-prompt {
        text-align: center;
        padding: 15px;
        background: #2e3338;
        border-radius: 4px;
        border: 1px solid #3d434a;
    }
    .login-prompt a {
        color: #677bc4;
        font-weight: 500;
    }
    .anjay {
        color: white;
        text-decoration: none;
        transition: color 0.2s ease;
    }
    .reply-btn {
        transition: opacity 0.2s ease;
    }
    .reply-btn:hover {
        opacity: 0.9;
    }
    @media (min-width: 768px) {
        .container {
            max-width: 800px;
            padding: 20px;
        }
        .media-container {
            max-width: 80%;
            margin: 20px auto;
        }
    }
    </style>
</head>
<body>
<div class="container">
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
            <a class="anjay" href="users.php?username=<?= htmlspecialchars($thread['username']) ?>">Dipost oleh: <?= htmlspecialchars($thread['username']) ?></a>| 
            <?= date('d M Y H:i', strtotime($thread['created_at'])) ?>
        </div>
    </div>
        
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="thread">
            <h3>Tambah Komentar</h3>
            <form method="post" action="komentar_proses.php">
                <textarea name="isi" id="commentTextarea" placeholder="Tulis komentar Anda..." required></textarea>
                <input type="hidden" name="thread_id" value="<?= $thread_id ?>">
                <input type="hidden" name="parent_id" id="parentIdInput" value="">
                <button type="submit" class="btn">Kirim Komentar</button>
            </form>
            <a href="index.php"><button class="btn">Back to Home</button></a>
        </div>
    <?php else: ?>
        <div class="thread login-prompt">
            <p><a href="login.php">Login</a> untuk menambahkan komentar.</p>
        </div>
    <?php endif; ?>

    <div class="thread">
        <h3>Komentar</h3>
        <?php if (empty($comments)): ?>
            <p class="no-comments">Belum ada komentar. Jadilah yang pertama!</p>
        <?php else: ?>
            <?php displayComments('root', $commentTree); ?>
        <?php endif; ?>
    </div>
</div>

<script>
    // Handle reply button
    document.querySelectorAll('.reply-btn').forEach(button => {
        button.addEventListener('click', function() {
            const parentId = this.dataset.parentId;
            document.getElementById('parentIdInput').value = parentId;
            document.getElementById('commentTextarea').focus();
            document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
        });
    });
</script>
</body>
</html>