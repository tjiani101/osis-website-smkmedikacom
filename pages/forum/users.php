<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ambil parameter username
$username = isset($_GET['username']) ? mysqli_real_escape_string($conn, $_GET['username']) : null;

// Fungsi aktivitas
function getActivityLevel($comments) {
    if($comments == 0) return ["🕸️ Silent Lurker", "#57F287"];
    if($comments <= 10) return ["🌱 Greenhorn", "#fee75c"];
    if($comments <= 50) return ["🔥 Active User", "#eb459e"];
    if($comments <= 100) return ["🚀 Power User", "#ed4245"];
    if($comments <= 500) return ["💎 Elite Member", "#5865F2"];
    if($comments <= 1000) return ["👑 Legend", "#ffffff"];
    return ["🤖 Touch Grass", "#ff0000"];
}

// Aksi follow/unfollow
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_follow'])) {
    $follower = $_SESSION['user_id'];
    $following = intval($_POST['followed_id']);

    // Cek apakah sudah follow
    $check = mysqli_query($conn, "SELECT * FROM follows WHERE follower_id=$follower AND following_id=$following");

    if (mysqli_num_rows($check) > 0) {
        // Unfollow
        mysqli_query($conn, "DELETE FROM follows WHERE follower_id=$follower AND following_id=$following");
        mysqli_query($conn, "UPDATE users SET followers_count = followers_count - 1 WHERE id = $following");
        mysqli_query($conn, "UPDATE users SET following_count = following_count - 1 WHERE id = $follower");
    } else {
        // Follow
        mysqli_query($conn, "INSERT INTO follows (follower_id, following_id) VALUES ($follower, $following)");
        mysqli_query($conn, "UPDATE users SET followers_count = followers_count + 1 WHERE id = $following");
        mysqli_query($conn, "UPDATE users SET following_count = following_count + 1 WHERE id = $follower");
    }

    header("Location: users.php?username=" . urlencode($username));
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>User Directory - Galeri OSIS</title>
    <style>
        @keyframes slide-left {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }

        .animate-slide-left-infinite {
            animation: slide-left 30s reverse linear infinite;
        }

        .animate-slide-right-infinite {
            animation: slide-left 30s linear infinite;
        }
    </style>
</head>
<body class="bg-slate-950 font-mulish text-white">

<div class="absolute -top-50 left-1/2 -translate-x-1/2 w-[100vw] lg:w-[800px] h-[400px] rounded-full bg-blue-500 opacity-20 blur-[150px]"></div>

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <?php if($username): ?>
        <?php
        $user_query = "SELECT u.*, 
        COUNT(DISTINCT p.id) as post_count,
        COUNT(DISTINCT c.id) as comment_count
      FROM users u
      LEFT JOIN threads p ON u.id = p.user_id
      LEFT JOIN comments c ON u.id = c.user_id
      WHERE u.username = '$username'
      GROUP BY u.id";

        $user_result = mysqli_query($conn, $user_query);
        $user = mysqli_fetch_assoc($user_result);

        if($user):
            list($activity, $color) = getActivityLevel($user['comment_count']);
            $is_following = false;
            if ($user['id'] != $_SESSION['user_id']) {
                $check_follow = mysqli_query($conn, "SELECT * FROM follows 
          WHERE follower_id = {$_SESSION['user_id']} 
          AND following_id = {$user['id']}");
                $is_following = mysqli_num_rows($check_follow) > 0;
            }

            function getFollowerBadge($followers) {
                if ($followers >= 500) {
                    return '<img src="badge/3.svg" alt="Diamond Badge" class="w-8 h-8">';
                } elseif ($followers >= 250) {
                    return '<img src="badge/2.svg" alt="Gold Badge" class="w-8 h-8">';
                } elseif ($followers >= 100) {
                    return '<img src="badge/1.svg" alt="Silver Badge" class="w-8 h-8">';
                }
                return '';
            }
            ?>

            <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-2xl p-6 mb-8 shadow-xl">
                <div class="flex flex-col md:flex-row items-center gap-6">
                    <div class="w-32 h-32 rounded-full overflow-hidden border-2 border-blue-500/30">
                        <img src="uploads/<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" class="w-full h-full object-cover">
                    </div>
                    <div class="text-center md:text-left">
                        <h1 class="text-2xl font-bold flex items-center justify-center md:justify-start gap-2">
                            <?= htmlspecialchars($user['username']) ?>
                            <?= getFollowerBadge($user['followers_count']) ?>
                        </h1>
                        <p class="text-slate-400">Tier: <?= $user['tier'] ?> Member</p>
                        <p class="mb-2" style="color: <?= $color ?>"><?= $activity ?></p>
                        <p class="text-slate-300"><?= htmlspecialchars($user['status']) ?></p>

                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" class="mt-4">
                                <input type="hidden" name="followed_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="toggle_follow" class="px-4 py-2 bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                                    <?= $is_following ? '🚫 Unfollow' : '❤️ Follow' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-slate-800 rounded-2xl p-6 text-center shadow-lg">
                    <div class="text-slate-400">Posts</div>
                    <div class="text-2xl font-bold"><?= $user['post_count'] ?></div>
                </div>
                <div class="bg-slate-800 rounded-2xl p-6 text-center shadow-lg">
                    <div class="text-slate-400">Comments</div>
                    <div class="text-2xl font-bold"><?= $user['comment_count'] ?></div>
                </div>
                <div class="bg-slate-800 rounded-2xl p-6 text-center shadow-lg">
                    <div class="text-slate-400">Followers</div>
                    <div class="text-2xl font-bold"><?= $user['followers_count'] ?></div>
                </div>
                <div class="bg-slate-800 rounded-2xl p-6 text-center shadow-lg">
                    <div class="text-slate-400">Following</div>
                    <div class="text-2xl font-bold"><?= $user['following_count'] ?></div>
                </div>
            </div>

            <div class="text-center mb-8">
                <a href="index.php" class="inline-block px-4 py-2 bg-blue-600 rounded-lg hover:bg-blue-700 transition">🏠 Home</a>
            </div>

        <?php else: ?>
            <div class="bg-red-900 text-red-200 p-4 rounded-xl">User not found</div>
        <?php endif; ?>

    <?php else: ?>
        <h1 class="text-3xl font-bold mb-8 text-center">Member Directory</h1>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $users_query = "SELECT u.*, 
          COUNT(DISTINCT p.id) as post_count,
          COUNT(DISTINCT c.id) as comment_count
        FROM users u
        LEFT JOIN posts p ON u.id = p.user_id
        LEFT JOIN comments c ON u.id = c.user_id
        GROUP BY u.id
        ORDER BY u.joined_at DESC
        LIMIT 24";

            $users_result = mysqli_query($conn, $users_query);

            while($user = mysqli_fetch_assoc($users_result)):
                list($activity, $color) = getActivityLevel($user['comment_count']);
                ?>
                <a href="users.php?username=<?= $user['username'] ?>" class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl overflow-hidden shadow-xl transition transform hover:-translate-y-1 hover:shadow-2xl">
                    <div class="p-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-16 h-16 rounded-full bg-slate-700 flex items-center justify-center text-xl font-bold" style="background: <?= $color ?>">
                                <?= strtoupper(substr($user['username'], 0, 2)) ?>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold"><?= htmlspecialchars($user['username']) ?></h3>
                                <p class="text-slate-400"><?= $user['tier'] ?> Tier</p>
                            </div>
                        </div>
                        <p class="mb-4" style="color: <?= $color ?>"><?= $activity ?></p>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-slate-700/50 rounded-lg p-3 text-center">
                                <div class="text-slate-400 text-sm">Followers</div>
                                <div class="font-bold"><?= $user['followers_count'] ?></div>
                            </div>
                            <div class="bg-slate-700/50 rounded-lg p-3 text-center">
                                <div class="text-slate-400 text-sm">Posts</div>
                                <div class="font-bold"><?= $user['post_count'] ?></div>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>