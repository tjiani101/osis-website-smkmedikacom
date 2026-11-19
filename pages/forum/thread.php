<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/forum_helpers.php';

// Logging function
function simpanLog($conn, $username, $action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $timestamp = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO logs (timestamp, username, action, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssss", $timestamp, $username, $action, $ip);
        $stmt->execute();
    }
}

$thread_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($thread_id <= 0) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$is_admin = false;
$logged_user = null;

if ($user_id) {
    $u_stmt = $conn->prepare("
        SELECT u.*, p.badge_label as perk_label, p.color_class as perk_color, p.description as perk_desc
        FROM users u 
        LEFT JOIN perks p ON u.active_perk_id = p.id
        WHERE u.id = ?
    ");
    $u_stmt->bind_param("i", $user_id);
    $u_stmt->execute();
    $logged_user = $u_stmt->get_result()->fetch_assoc();
    if ($logged_user) {
        $is_admin = in_array(strtolower($logged_user['role']), ['admin', 'dev']);
        award_milestones_if_eligible($conn, $user_id);
    }
}

// Handle Delete Thread POST (author or admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_thread') {
    if (!$user_id) die("Akses ditolak.");

    $chk = $conn->prepare("SELECT user_id, gambar FROM threads WHERE id = ?");
    $chk->bind_param("i", $thread_id);
    $chk->execute();
    $th_data = $chk->get_result()->fetch_assoc();

    if ($th_data && ($th_data['user_id'] == $user_id || $is_admin)) {
        if (!empty($th_data['gambar'])) {
            $f = __DIR__ . '/post/' . $th_data['gambar'];
            if (file_exists($f)) @unlink($f);
        }
        $conn->query("DELETE FROM comment_votes WHERE comment_id IN (SELECT id FROM comments WHERE thread_id = $thread_id)");
        $conn->query("DELETE FROM comments WHERE thread_id = $thread_id");
        $conn->query("DELETE FROM votes WHERE thread_id = $thread_id");
        $conn->query("DELETE FROM threads WHERE id = $thread_id");
        simpanLog($conn, $_SESSION['username'], "Deleted thread #$thread_id");
        header("Location: index.php?msg=" . urlencode("Topik berhasil dihapus."));
        exit;
    }
}

// Handle Delete Comment POST (author or admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_comment') {
    if (!$user_id) die("Akses ditolak.");
    $comment_id = intval($_POST['comment_id'] ?? 0);

    $chk = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
    $chk->bind_param("i", $comment_id);
    $chk->execute();
    $c_data = $chk->get_result()->fetch_assoc();

    if ($c_data && ($c_data['user_id'] == $user_id || $is_admin)) {
        $conn->query("DELETE FROM comment_votes WHERE comment_id = $comment_id");
        $del = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $del->bind_param("i", $comment_id);
        $del->execute();
        simpanLog($conn, $_SESSION['username'], "Deleted comment #$comment_id");
        header("Location: thread.php?id=$thread_id");
        exit;
    }
}

// Log thread view
if (isset($_SESSION['username'])) {
    simpanLog($conn, $_SESSION['username'], "Viewed thread #$thread_id");
}

// Get thread details
$stmt = $conn->prepare("
    SELECT t.*, u.username, u.role, u.avatar, u.active_frame, u.reputation,
           p.badge_label as perk_label, p.color_class as perk_color, p.description as perk_desc,
           f.id as subforum_id, f.nama as forum_name, f.slug as forum_slug, f.icon as forum_icon,
           COALESCE((SELECT SUM(vote_type) FROM votes v WHERE v.thread_id = t.id), 0) AS vote_count
    FROM threads t 
    JOIN users u ON t.user_id = u.id 
    LEFT JOIN perks p ON u.active_perk_id = p.id
    LEFT JOIN forum f ON t.forum_id = f.id
    WHERE t.id = ?
");
$stmt->bind_param("i", $thread_id);
$stmt->execute();
$thread_result = $stmt->get_result();

if ($thread_result->num_rows === 0) {
    header("Location: index.php");
    exit;
}
$thread = $thread_result->fetch_assoc();

// Check user's vote on this thread
$user_vote = 0;
if ($user_id) {
    $v_stmt = $conn->prepare("SELECT vote_type FROM votes WHERE thread_id = ? AND user_id = ?");
    $v_stmt->bind_param("ii", $thread_id, $user_id);
    $v_stmt->execute();
    $vr = $v_stmt->get_result()->fetch_assoc();
    if ($vr) $user_vote = (int)$vr['vote_type'];
}

// Fetch comments with author metadata
$komen = $conn->prepare("
    SELECT k.*, u.username, u.role, u.avatar, u.active_frame, u.reputation,
           p.badge_label as perk_label, p.color_class as perk_color, p.description as perk_desc
    FROM comments k 
    JOIN users u ON k.user_id = u.id 
    LEFT JOIN perks p ON u.active_perk_id = p.id
    WHERE k.thread_id = ? 
    ORDER BY k.created_at ASC
");
$komen->bind_param("i", $thread_id);
$komen->execute();
$komen_result = $komen->get_result();

$comments = [];
while ($row = $komen_result->fetch_assoc()) {
    $comments[] = $row;
}

// Fetch user votes on comments
$user_comment_votes = [];
if ($user_id) {
    $cv_res = $conn->query("
        SELECT cv.comment_id, cv.vote_type 
        FROM comment_votes cv 
        JOIN comments c ON cv.comment_id = c.id 
        WHERE c.thread_id = $thread_id AND cv.user_id = $user_id
    ");
    if ($cv_res) {
        while ($cvr = $cv_res->fetch_assoc()) {
            $user_comment_votes[$cvr['comment_id']] = (int)$cvr['vote_type'];
        }
    }
}

// Fetch total vote count for each comment
$comment_vote_counts = [];
$cvc_res = $conn->query("
    SELECT cv.comment_id, COALESCE(SUM(cv.vote_type), 0) AS total_votes
    FROM comment_votes cv
    JOIN comments c ON cv.comment_id = c.id
    WHERE c.thread_id = $thread_id
    GROUP BY cv.comment_id
");
if ($cvc_res) {
    while ($r = $cvc_res->fetch_assoc()) {
        $comment_vote_counts[$r['comment_id']] = (int)$r['total_votes'];
    }
}

// Build comment tree for threaded view
$commentTree = [];
foreach ($comments as $comment) {
    $parentId = $comment['parent_id'] ?? 'root';
    $commentTree[$parentId][] = $comment;
}

// Recursive Reddit Threaded Comments Display
function displayRedditComments($parentId, &$commentTree, &$user_comment_votes, &$comment_vote_counts, $level = 0, $current_user_id = null, $is_admin = false) {
    if (empty($commentTree[$parentId])) return;

    foreach ($commentTree[$parentId] as $comment) {
        $is_author_or_admin = ($current_user_id && ($comment['user_id'] == $current_user_id || $is_admin));
        $my_cvote = $user_comment_votes[$comment['id']] ?? 0;
        $total_cvotes = $comment_vote_counts[$comment['id']] ?? 0;
        $active_perk_info = !empty($comment['perk_label']) ? [
            'badge_label' => $comment['perk_label'],
            'color_class' => $comment['perk_color'],
            'description' => $comment['perk_desc']
        ] : null;
        ?>
        <div id="comment-<?= $comment['id'] ?>" class="comment-branch relative mt-3 sm:mt-4 group/branch <?= $level > 0 ? 'ml-2.5 sm:ml-5 pl-2.5 sm:pl-4 border-l-2 border-white/10 hover:border-blue-400/60 transition-colors' : '' ?>">
            
            <div class="liquid-glass p-3.5 sm:p-4 rounded-2xl bg-white/[0.03] border border-white/10">
                
                <!-- Comment Header -->
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Collapse Toggle Button [-] -->
                        <button type="button" onclick="toggleCommentCollapse(<?= $comment['id'] ?>)" class="text-white/40 hover:text-white font-mono text-xs font-bold px-1 rounded hover:bg-white/10 transition-colors" title="Ciutkan / Buka Utas">
                            <span class="collapse-icon">[-]</span>
                        </button>

                        <!-- Avatar with Frame -->
                        <?= render_avatar_with_frame($comment['username'], $comment['avatar'], $comment['active_frame'], 'xs') ?>

                        <!-- Username + Flair -->
                        <?= render_user_flair($comment['user_id'], $comment['username'], $comment['role'], $active_perk_info, $comment['reputation'] ?? 0) ?>

                        <span class="text-white/30">•</span>
                        <span class="text-[10px] sm:text-xs text-white/40">
                            <?= date('d M Y, H:i', strtotime($comment['created_at'])) ?>
                        </span>
                    </div>

                    <!-- Delete button -->
                    <?php if ($is_author_or_admin): ?>
                        <form method="POST" onsubmit="return confirm('Hapus komentar ini?')" class="inline-block">
                            <input type="hidden" name="action" value="delete_comment">
                            <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                            <button type="submit" class="text-red-400 hover:text-red-300 text-xs p-1" title="Hapus Komentar">
                                <i class="fa fa-trash-can text-[11px]"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Collapsed Placeholder (Hidden by default) -->
                <div class="comment-collapsed-msg hidden py-1 text-xs text-white/40 italic">
                    Utas komentar disembunyikan. Klik <strong class="text-blue-300 cursor-pointer" onclick="toggleCommentCollapse(<?= $comment['id'] ?>)">[+]</strong> untuk membuka kembali.
                </div>

                <!-- Comment Body & Actions (collapsible) -->
                <div class="comment-expandable">
                    <div class="text-white/80 text-xs sm:text-sm leading-relaxed mb-3 break-words">
                        <?= parse_reddit_markdown($comment['komentar']) ?>
                    </div>

                    <!-- Comment Actions Bar (Vote & Reply) -->
                    <div class="flex items-center justify-between pt-2 border-t border-white/5 text-xs text-white/60">
                        <!-- Upvote / Downvote on Comment -->
                        <div class="flex items-center gap-1 bg-white/5 px-2 py-0.5 rounded-full border border-white/10">
                            <button onclick="handleCommentVote(<?= $comment['id'] ?>, 1, this)" class="comment-vote-btn upvote hover:text-blue-400 transition-colors p-1 <?= $my_cvote === 1 ? 'text-blue-400' : 'text-white/50' ?>" title="Upvote Komentar">
                                <i class="fa fa-arrow-up text-[10px]"></i>
                            </button>
                            <span class="comment-vote-count font-mono text-[11px] font-extrabold text-white px-1">
                                <?= $total_cvotes ?>
                            </span>
                            <button onclick="handleCommentVote(<?= $comment['id'] ?>, -1, this)" class="comment-vote-btn downvote hover:text-red-400 transition-colors p-1 <?= $my_cvote === -1 ? 'text-red-400' : 'text-white/50' ?>" title="Downvote Komentar">
                                <i class="fa fa-arrow-down text-[10px]"></i>
                            </button>
                        </div>

                        <!-- Reply Button -->
                        <?php if ($current_user_id): ?>
                            <button onclick="openInlineReply(<?= $comment['id'] ?>, '<?= htmlspecialchars(addslashes($comment['username'])) ?>')" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/5 hover:bg-white/10 text-blue-300 border border-white/10 transition-colors">
                                <i class="fa fa-reply text-[10px]"></i>
                                <span>Balas</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Recursive render child comments -->
            <div class="comment-children">
                <?php displayRedditComments($comment['id'], $commentTree, $user_comment_votes, $comment_vote_counts, $level + 1, $current_user_id, $is_admin); ?>
            </div>

        </div>
        <?php
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($thread['judul']) ?> - Forum Medikacom</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Liquid Theme Styles -->
    <link rel="stylesheet" href="../../assets/css/liquid-theme.css">

    <style>
        .vote-pill {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 999px;
            backdrop-filter: blur(8px);
            padding: 4px;
        }
        .vote-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
            color: rgba(255, 255, 255, 0.6);
        }
        .vote-btn:hover {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            transform: scale(1.1);
        }
        .vote-btn.upvoted {
            color: #60a5fa !important;
            background: rgba(59, 130, 246, 0.25) !important;
        }
        .vote-btn.downvoted {
            color: #f87171 !important;
            background: rgba(239, 68, 68, 0.25) !important;
        }
        .comment-branch.collapsed > div > .comment-expandable,
        .comment-branch.collapsed > .comment-children {
            display: none !important;
        }
        .comment-branch.collapsed > div > .comment-collapsed-msg {
            display: block !important;
        }
    </style>
</head>
<body class="selection:bg-blue-500/30 selection:text-white">

    <!-- Background Elements -->
    <div class="main-background"></div>
    <div class="main-overlay"></div>

    <!-- Compact Header Navigation -->
    <header class="fixed top-0 left-0 right-0 z-50 pt-2 sm:pt-3 px-3 sm:px-6">
        <div class="max-w-5xl mx-auto">
            <div class="liquid-glass px-3.5 sm:px-5 py-2 flex items-center justify-between">
                <a href="index.php" class="flex items-center gap-2">
                    <img class="h-6 sm:h-7 w-auto" src="../../images/logo.png" alt="Logo" onerror="this.onerror=null;this.src='https://placehold.co/100x100/ffffff/000000?text=Logo';">
                    <span class="text-white font-extrabold text-xs sm:text-sm tracking-wide">
                        FORUM <span class="text-white/60 font-normal hidden sm:inline">| Detail Topik</span>
                    </span>
                </a>
                
                <div class="flex items-center gap-1.5 sm:gap-2.5">
                    <a href="shop.php" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-300 font-extrabold text-[11px] hover:bg-amber-500/25 transition-all">
                        <i class="fa fa-store text-amber-400 text-[10px]"></i>
                        <span>Toko</span>
                    </a>
                    <?php if ($is_admin): ?>
                        <a href="dev.php" class="p-1.5 rounded-full bg-purple-600/30 hover:bg-purple-600/50 border border-purple-500/40 text-purple-300 transition-all" title="Admin Panel">
                            <i class="fa fa-shield-halved text-xs"></i>
                        </a>
                    <?php endif; ?>
                    <a href="index.php" class="liquid-btn-secondary px-3 py-1 text-xs font-bold flex items-center gap-1">
                        <i class="fa fa-arrow-left text-[10px]"></i>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content (Centered & Spacious) -->
    <main class="container mx-auto px-4 max-w-4xl pt-24 sm:pt-28 pb-24 space-y-6">

        <!-- Main Thread Card -->
        <article class="liquid-glass p-4 sm:p-7 relative overflow-hidden rounded-2xl border" data-thread-id="<?= $thread['id'] ?>">
            
            <div class="flex items-start justify-between gap-3 sm:gap-4 mb-4">
                <!-- Author Info: Avatar + Frame + r/slug + Perk -->
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <?= render_avatar_with_frame($thread['username'], $thread['avatar'], $thread['active_frame'], 'sm') ?>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <!-- Subreddit Pill -->
                            <a href="index.php?forum_id=<?= $thread['forum_id'] ?>" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/20 hover:bg-blue-500/30 border border-blue-400/40 text-blue-300 transition-colors">
                                sf/<?= htmlspecialchars($thread['forum_slug'] ?: 'umum') ?>
                            </a>
                            <span class="text-white/40">•</span>
                            <span>oleh</span>
                            <?= render_user_flair($thread['user_id'], $thread['username'], $thread['role'], !empty($thread['perk_label']) ? ['badge_label' => $thread['perk_label'], 'color_class' => $thread['perk_color'], 'description' => $thread['perk_desc']] : null, $thread['reputation'] ?? 0) ?>
                        </div>
                        <p class="text-[11px] sm:text-xs text-white/50 mt-0.5"><?= date('d F Y, H:i', strtotime($thread['created_at'])) ?> WIB</p>
                    </div>
                </div>

                <!-- Thread Upvote/Downvote Pill -->
                <div class="vote-pill flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1 shrink-0">
                    <button class="vote-btn upvote <?= $user_vote === 1 ? 'upvoted' : '' ?>" title="Upvote" onclick="handleThreadVote(1)">
                        <i class="fa fa-arrow-up text-xs"></i>
                    </button>
                    <span class="vote-count font-extrabold text-xs sm:text-sm text-white font-mono">
                        <?= $thread['vote_count'] ?>
                    </span>
                    <button class="vote-btn downvote <?= $user_vote === -1 ? 'downvoted' : '' ?>" title="Downvote" onclick="handleThreadVote(-1)">
                        <i class="fa fa-arrow-down text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- Thread Title -->
            <h1 class="text-xl sm:text-2xl font-extrabold text-white mb-3 sm:mb-4 leading-snug break-words">
                <?= htmlspecialchars($thread['judul']) ?>
            </h1>

            <!-- Thread Body (Markdown parsed) -->
            <div class="text-white/80 text-sm sm:text-base leading-relaxed mb-6 break-words">
                <?= parse_reddit_markdown($thread['konten']) ?>
            </div>

            <!-- Media Attachment -->
            <?php if (!empty($thread['gambar'])): ?>
                <div class="mb-6 rounded-xl sm:rounded-2xl overflow-hidden max-h-[500px] border border-white/10 bg-black/40 flex items-center justify-center">
                    <?php if (isset($thread['media_type']) && $thread['media_type'] === 'video'): ?>
                        <video controls class="w-full max-h-[500px] object-contain rounded-xl sm:rounded-2xl">
                            <source src="post/<?= htmlspecialchars($thread['gambar']) ?>" type="video/mp4">
                        </video>
                    <?php else: ?>
                        <img src="post/<?= htmlspecialchars($thread['gambar']) ?>" alt="Lampiran" class="w-full max-h-[500px] object-contain rounded-xl sm:rounded-2xl">
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Thread Footer Actions -->
            <div class="pt-4 border-t border-white/10 flex items-center justify-between text-xs text-white/60">
                <span class="inline-flex items-center gap-1.5">
                    <i class="fa fa-comments text-blue-400"></i>
                    <?= count($comments) ?> Total Tanggapan
                </span>

                <div class="flex items-center gap-2">
                    <!-- Share Button -->
                    <button onclick="shareCurrentThread()" class="px-3 py-1 rounded-full bg-white/5 hover:bg-white/10 text-white/80 border border-white/10 flex items-center gap-1.5 transition-all text-xs">
                        <i class="fa fa-share-nodes text-[11px]"></i>
                        <span>Bagikan</span>
                    </button>

                    <!-- Delete Thread Button (Author or Admin) -->
                    <?php if ($user_id && ($thread['user_id'] == $user_id || $is_admin)): ?>
                        <form method="POST" onsubmit="return confirm('PERINGATAN: Topik ini dan semua komentarnya akan dihapus permanen. Lanjutkan?')" class="inline-block">
                            <input type="hidden" name="action" value="delete_thread">
                            <button type="submit" class="px-3 py-1 rounded-full bg-red-500/10 hover:bg-red-500/20 text-red-300 border border-red-500/20 flex items-center gap-1.5 transition-all text-xs">
                                <i class="fa fa-trash-can text-[11px]"></i>
                                <span>Hapus Topik</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </article>

        <!-- Komentar Input Box -->
        <section id="comment-box-section" class="liquid-glass p-4 sm:p-6 rounded-2xl border">
            <h3 class="text-base font-bold text-white mb-3 flex items-center gap-2">
                <span>💬</span> Berikan Tanggapan
            </h3>

            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" action="komentar_proses.php" class="space-y-3">
                    <div id="replying-to" class="hidden p-3 rounded-xl bg-blue-500/10 border border-blue-500/20 text-xs text-blue-300 flex items-center justify-between">
                        <span>Membalas komentar dari <strong id="reply-target">User</strong></span>
                        <button type="button" onclick="cancelReply()" class="text-white/60 hover:text-white font-bold text-xs">✕ Batalkan</button>
                    </div>

                    <textarea 
                        name="isi" 
                        id="commentTextarea" 
                        rows="3" 
                        placeholder="Tulis komentar... Gunakan **tebal**, *miring*, `kode`, atau > kutipan ala Reddit." 
                        required 
                        class="liquid-input w-full px-4 py-3 text-xs sm:text-sm rounded-xl"
                    ></textarea>

                    <input type="hidden" name="thread_id" value="<?= $thread_id ?>">
                    <input type="hidden" name="parent_id" id="parentIdInput" value="">

                    <div class="flex items-center justify-between text-[11px] text-white/40">
                        <span>Markdown didukung (**bold**, *italic*, `code`)</span>
                        <button type="submit" class="liquid-btn px-5 py-2 text-xs font-bold shadow-lg">
                            Kirim Tanggapan
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="p-6 rounded-xl bg-white/5 border border-white/10 text-center">
                    <p class="text-xs sm:text-sm text-white/70 mb-3">Masuk terlebih dahulu untuk ikut berdiskusi.</p>
                    <a href="login.php" class="liquid-btn px-6 py-2 text-xs font-bold">
                        Masuk ke Akun
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <!-- Daftar Komentar (Threaded / Nested) -->
        <section class="space-y-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-bold text-white">Diskusi Terkini (<?= count($comments) ?>)</h3>
            </div>

            <?php if (empty($comments)): ?>
                <div class="liquid-glass p-8 text-center text-white/50 text-xs sm:text-sm rounded-2xl border">
                    Belum ada komentar pada topik ini. Jadilah yang pertama memberikan tanggapan!
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php displayRedditComments('root', $commentTree, $user_comment_votes, $comment_vote_counts, 0, $user_id, $is_admin); ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- Floating User Hover Card Popover -->
    <div id="user-hover-card" class="fixed z-50 hidden liquid-glass p-4 rounded-2xl border border-white/20 shadow-2xl w-72 pointer-events-auto backdrop-blur-2xl transition-opacity duration-200 opacity-0"></div>

    <script>
        // Thread Voting AJAX
        async function handleThreadVote(voteType) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Silakan login terlebih dahulu untuk vote.');
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            const fd = new FormData();
            fd.append('thread_id', <?= $thread['id'] ?>);
            fd.append('vote_type', voteType);

            try {
                const res = await fetch('vote.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    const article = document.querySelector('article[data-thread-id]');
                    const countEl = article.querySelector('.vote-count');
                    const upBtn = article.querySelector('.vote-btn.upvote');
                    const downBtn = article.querySelector('.vote-btn.downvote');

                    countEl.textContent = data.vote_count;
                    upBtn.classList.remove('upvoted');
                    downBtn.classList.remove('downvoted');

                    if (data.user_vote === 1) upBtn.classList.add('upvoted');
                    if (data.user_vote === -1) downBtn.classList.add('downvoted');
                } else {
                    alert(data.error || 'Gagal memproses vote.');
                }
            } catch (e) {
                alert('Terjadi kesalahan jaringan.');
            }
        }

        // Comment Voting AJAX
        async function handleCommentVote(commentId, voteType, btnEl) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                alert('Silakan login terlebih dahulu untuk vote.');
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            const fd = new FormData();
            fd.append('comment_id', commentId);
            fd.append('vote_type', voteType);

            try {
                const res = await fetch('vote.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    const container = btnEl.closest('.flex');
                    const countEl = container.querySelector('.comment-vote-count');
                    const upBtn = container.querySelector('.upvote');
                    const downBtn = container.querySelector('.downvote');

                    countEl.textContent = data.vote_count;
                    upBtn.classList.remove('text-blue-400');
                    upBtn.classList.add('text-white/50');
                    downBtn.classList.remove('text-red-400');
                    downBtn.classList.add('text-white/50');

                    if (data.user_vote === 1) {
                        upBtn.classList.add('text-blue-400');
                        upBtn.classList.remove('text-white/50');
                    } else if (data.user_vote === -1) {
                        downBtn.classList.add('text-red-400');
                        downBtn.classList.remove('text-white/50');
                    }
                } else {
                    alert(data.error || 'Gagal vote komentar.');
                }
            } catch (e) {
                alert('Terjadi kesalahan jaringan.');
            }
        }

        // Reddit Collapse / Expand interaction
        function toggleCommentCollapse(commentId) {
            const branch = document.getElementById('comment-' + commentId);
            if (!branch) return;

            branch.classList.toggle('collapsed');
            const icon = branch.querySelector('.collapse-icon');
            if (icon) {
                icon.textContent = branch.classList.contains('collapsed') ? '[+]' : '[-]';
            }
        }

        // Reply to comment
        function openInlineReply(parentId, username) {
            const replyingTo = document.getElementById('replying-to');
            const replyTarget = document.getElementById('reply-target');
            const parentIdInput = document.getElementById('parentIdInput');
            const textarea = document.getElementById('commentTextarea');

            replyingTo.classList.remove('hidden');
            replyTarget.textContent = username;
            parentIdInput.value = parentId;

            document.getElementById('comment-box-section').scrollIntoView({ behavior: 'smooth' });
            textarea.focus();
        }

        function cancelReply() {
            document.getElementById('replying-to').classList.add('hidden');
            document.getElementById('parentIdInput').value = '';
        }

        function shareCurrentThread() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Tautan topik telah disalin ke clipboard!');
                });
            } else {
                prompt('Salin tautan topik berikut:', window.location.href);
            }
        }

        // Reddit-Style User Hover Popover
        const hoverCard = document.getElementById('user-hover-card');
        let hoverTimeout = null;
        let cardCache = {};

        document.querySelectorAll('.user-hover-trigger').forEach(el => {
            el.addEventListener('mouseenter', (e) => {
                const userId = el.getAttribute('data-user-id');
                if (!userId) return;

                clearTimeout(hoverTimeout);
                hoverTimeout = setTimeout(async () => {
                    const rect = el.getBoundingClientRect();
                    hoverCard.style.top = `${Math.min(window.innerHeight - 260, rect.bottom + 8)}px`;
                    hoverCard.style.left = `${Math.min(window.innerWidth - 310, Math.max(16, rect.left - 20))}px`;
                    hoverCard.classList.remove('hidden');

                    if (cardCache[userId]) {
                        renderHoverCard(cardCache[userId]);
                    } else {
                        hoverCard.innerHTML = '<div class="py-4 text-center text-xs text-white/50"><i class="fa fa-spinner fa-spin mr-1"></i> Memuat...</div>';
                        hoverCard.classList.remove('opacity-0');
                        try {
                            const res = await fetch(`get_user_card.php?id=${userId}`);
                            const data = await res.json();
                            if (data.success) {
                                cardCache[userId] = data.user;
                                renderHoverCard(data.user);
                            }
                        } catch (err) {
                            hoverCard.innerHTML = '<div class="text-xs text-red-300">Gagal memuat profil.</div>';
                        }
                    }
                }, 250);
            });

            el.addEventListener('mouseleave', () => {
                clearTimeout(hoverTimeout);
                hoverTimeout = setTimeout(() => {
                    if (!hoverCard.matches(':hover')) {
                        hoverCard.classList.add('opacity-0');
                        setTimeout(() => hoverCard.classList.add('hidden'), 150);
                    }
                }, 150);
            });
        });

        hoverCard.addEventListener('mouseleave', () => {
            hoverCard.classList.add('opacity-0');
            setTimeout(() => hoverCard.classList.add('hidden'), 150);
        });

        function renderHoverCard(user) {
            let perksHtml = '';
            if (user.showcase_perks && user.showcase_perks.length > 0) {
                perksHtml = `<div class="mt-2.5 pt-2.5 border-t border-white/10">
                    <span class="text-[9px] text-white/50 uppercase font-bold block mb-1">Etalase:</span>
                    <div class="flex flex-wrap gap-1">
                        ${user.showcase_perks.map(p => `<span class="text-[9px] px-2 py-0.2 rounded-full font-bold border ${p.color_class}">${p.badge_label}</span>`).join('')}
                    </div>
                </div>`;
            }

            let badgesHtml = '';
            if (user.badges && user.badges.length > 0) {
                badgesHtml = `<div class="mt-2 pt-2 border-t border-white/10 flex items-center gap-1.5">
                    ${user.badges.map(b => `<img src="assets/badges/${b.icon_url}" class="w-5 h-5 object-contain" title="${b.name}">`).join('')}
                </div>`;
            }

            hoverCard.innerHTML = `
                <div class="flex items-start gap-2.5">
                    <div class="relative inline-flex items-center justify-center w-10 h-10 shrink-0">
                        <div class="w-full h-full rounded-full overflow-hidden bg-gradient-to-tr from-blue-700 to-indigo-900 border border-white/20 flex items-center justify-center text-white font-extrabold shadow-md">
                            ${user.avatar && user.avatar !== 'default-avatar.jpg' ? `<img src="uploads/${user.avatar}" class="w-full h-full object-cover">` : `<span>${user.username.charAt(0).toUpperCase()}</span>`}
                        </div>
                        ${user.active_frame ? `<img src="assets/frames/${user.active_frame}" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[124%] h-[124%] max-w-none pointer-events-none z-10" style="object-fit: contain;">` : ''}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1 flex-wrap">
                            <span class="font-extrabold text-white text-xs truncate">${user.username}</span>
                            ${user.role && user.role !== 'murid' ? `<span class="text-[8px] px-1 py-0.2 rounded font-bold uppercase bg-purple-500/20 text-purple-300 border border-purple-500/30">${user.role}</span>` : ''}
                        </div>
                        ${user.active_perk ? `<span class="text-[9px] px-2 py-0.2 rounded-full font-bold border ${user.active_perk.color} inline-block mt-0.5">${user.active_perk.label}</span>` : ''}
                        <div class="text-[10px] text-amber-300 font-extrabold mt-0.5 flex items-center gap-1">
                            <i class="fa fa-star text-amber-400"></i> ${user.reputation} Rep
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 mt-2.5 pt-2.5 border-t border-white/10 text-center text-[10px]">
                    <div class="p-1 rounded-lg bg-white/5">
                        <span class="text-white/40 block text-[8px] uppercase">Followers</span>
                        <strong class="text-white">${user.followers_count}</strong>
                    </div>
                    <div class="p-1 rounded-lg bg-white/5">
                        <span class="text-white/40 block text-[8px] uppercase">Bergabung</span>
                        <strong class="text-white">${user.joined_date}</strong>
                    </div>
                </div>

                ${perksHtml}
                ${badgesHtml}

                <div class="mt-2.5 pt-2 border-t border-white/10 flex items-center gap-2">
                    <a href="profile.php?id=${user.id}" class="flex-1 text-center py-1 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-[11px] transition-all">
                        Lihat Profil
                    </a>
                </div>
            `;
            hoverCard.classList.remove('opacity-0');
        }
    </script>
</body>
</html>
