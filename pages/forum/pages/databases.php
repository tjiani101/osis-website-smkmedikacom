<?php
$alert = null;

// Handle Delete Actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // Delete Thread
    if ($action === 'delete_thread') {
        $thread_id = intval($_POST['thread_id'] ?? 0);
        // Get media file to remove
        $th = $conn->query("SELECT gambar FROM threads WHERE id = $thread_id")->fetch_assoc();
        if ($th && !empty($th['gambar'])) {
            $file_path = __DIR__ . '/../post/' . $th['gambar'];
            if (file_exists($file_path)) @unlink($file_path);
        }
        // Delete comments & votes
        $conn->query("DELETE FROM comments WHERE thread_id = $thread_id");
        $conn->query("DELETE FROM votes WHERE thread_id = $thread_id");
        $conn->query("DELETE FROM notifications WHERE source_id = $thread_id");
        $conn->query("DELETE FROM threads WHERE id = $thread_id");
        $alert = ['type' => 'success', 'message' => "Topik diskusi #$thread_id beserta seluruh komentar berhasil dihapus."];
    }

    // Delete Comment
    elseif ($action === 'delete_comment') {
        $comment_id = intval($_POST['comment_id'] ?? 0);
        $conn->query("DELETE FROM comments WHERE id = $comment_id");
        $alert = ['type' => 'success', 'message' => "Komentar #$comment_id berhasil dihapus."];
    }

    // Optimize Table
    elseif ($action === 'optimize_table') {
        $table_name = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['table_name'] ?? '');
        if (!empty($table_name)) {
            $conn->query("OPTIMIZE TABLE `$table_name`");
            $alert = ['type' => 'success', 'message' => "Tabel '$table_name' berhasil dioptimasi."];
        }
    }
}

// Active Tab
$tab = $_GET['tab'] ?? 'threads';

// 1. Threads Data
$search_thread = trim($_GET['search_thread'] ?? '');
$thread_sql = "
    SELECT t.*, u.username, f.nama as forum_name,
           (SELECT COUNT(*) FROM comments c WHERE c.thread_id = t.id) as comment_count,
           COALESCE((SELECT SUM(vote_type) FROM votes v WHERE v.thread_id = t.id), 0) as vote_count
    FROM threads t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN forum f ON t.forum_id = f.id
";
if ($search_thread !== '') {
    $search_safe = $conn->real_escape_string($search_thread);
    $thread_sql .= " WHERE t.judul LIKE '%$search_safe%' OR u.username LIKE '%$search_safe%'";
}
$thread_sql .= " ORDER BY t.created_at DESC LIMIT 50";
$threads_list = $conn->query($thread_sql);

// 2. Comments Data
$search_comment = trim($_GET['search_comment'] ?? '');
$comment_sql = "
    SELECT c.*, u.username, t.judul as thread_title
    FROM comments c
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN threads t ON c.thread_id = t.id
";
if ($search_comment !== '') {
    $search_safe = $conn->real_escape_string($search_comment);
    $comment_sql .= " WHERE c.komentar LIKE '%$search_safe%' OR u.username LIKE '%$search_safe%'";
}
$comment_sql .= " ORDER BY c.created_at DESC LIMIT 50";
$comments_list = $conn->query($comment_sql);

// 3. Database Tables Inspector
$tables_info = [];
$t_res = $conn->query("
    SELECT table_name, table_rows, data_length, index_length, engine
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
    ORDER BY data_length DESC
");
if ($t_res) {
    while ($row = $t_res->fetch_assoc()) {
        $tables_info[] = $row;
    }
}
?>

<div class="space-y-6">

    <!-- Alert -->
    <?php if ($alert): ?>
        <div class="p-4 rounded-2xl flex items-center justify-between <?= $alert['type'] === 'success' ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-200' : 'bg-red-500/15 border border-red-500/30 text-red-200' ?>">
            <div class="flex items-center gap-3">
                <i class="fa <?= $alert['type'] === 'success' ? 'fa-circle-check text-emerald-400' : 'fa-triangle-exclamation text-red-400' ?> text-lg"></i>
                <span class="text-xs font-semibold"><?= htmlspecialchars($alert['message']) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-white/40 hover:text-white text-xs">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Header & Navigation Tabs -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-white">Manajemen Forum & Database</h1>
            <p class="text-white/50 text-xs">Kelola konten topik diskusi, moderasi komentar siswa, dan pantau status tabel database.</p>
        </div>
        
        <!-- Tabs -->
        <div class="flex items-center gap-1.5 p-1 rounded-2xl bg-white/5 border border-white/10">
            <a href="?page=databases&tab=threads" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $tab === 'threads' ? 'bg-blue-600 text-white shadow-md' : 'text-white/70 hover:text-white hover:bg-white/5' ?>">
                <i class="fa fa-comments mr-1.5"></i> Topik (<?= $threads_list ? $threads_list->num_rows : 0 ?>)
            </a>
            <a href="?page=databases&tab=comments" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $tab === 'comments' ? 'bg-blue-600 text-white shadow-md' : 'text-white/70 hover:text-white hover:bg-white/5' ?>">
                <i class="fa fa-comment-dots mr-1.5"></i> Komentar (<?= $comments_list ? $comments_list->num_rows : 0 ?>)
            </a>
            <a href="?page=databases&tab=schema" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $tab === 'schema' ? 'bg-blue-600 text-white shadow-md' : 'text-white/70 hover:text-white hover:bg-white/5' ?>">
                <i class="fa fa-database mr-1.5"></i> Tabel Database (<?= count($tables_info) ?>)
            </a>
        </div>
    </div>

    <!-- TAB 1: THREADS -->
    <?php if ($tab === 'threads'): ?>
        <div class="admin-glass-card p-4">
            <form method="GET" class="flex items-center gap-3">
                <input type="hidden" name="page" value="databases">
                <input type="hidden" name="tab" value="threads">
                <div class="relative flex-1">
                    <i class="fa fa-search absolute left-3.5 top-3 text-white/40 text-xs"></i>
                    <input 
                        type="text" 
                        name="search_thread" 
                        value="<?= htmlspecialchars($search_thread) ?>" 
                        placeholder="Cari judul diskusi atau pembuat topik..." 
                        class="w-full pl-9 pr-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/40 text-xs focus:outline-none focus:border-blue-500"
                    >
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition-all">
                    Cari
                </button>
                <?php if ($search_thread !== ''): ?>
                    <a href="?page=databases&tab=threads" class="px-3 py-2 rounded-xl bg-white/10 text-white/70 text-xs">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/10 bg-white/[0.02] text-[10px] font-extrabold uppercase tracking-wider text-white/50">
                            <th class="p-4">ID</th>
                            <th class="p-4">Judul Topik</th>
                            <th class="p-4">Kategori</th>
                            <th class="p-4">Penulis</th>
                            <th class="p-4">Statistik</th>
                            <th class="p-4">Waktu Dibuat</th>
                            <th class="p-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-xs">
                        <?php if ($threads_list && $threads_list->num_rows > 0): ?>
                            <?php while ($th = $threads_list->fetch_assoc()): ?>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="p-4 font-mono-code text-white/40">#<?= $th['id'] ?></td>
                                    <td class="p-4">
                                        <a href="thread.php?id=<?= $th['id'] ?>" target="_blank" class="font-bold text-white hover:text-blue-300 transition-colors line-clamp-1">
                                            <?= htmlspecialchars($th['judul']) ?>
                                        </a>
                                        <p class="text-[11px] text-white/50 line-clamp-1 mt-0.5">
                                            <?= htmlspecialchars(substr($th['konten'], 0, 100)) ?>...
                                        </p>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-500/10 text-blue-300 border border-blue-500/20">
                                            <?= htmlspecialchars($th['forum_name'] ?? 'Umum') ?>
                                        </span>
                                    </td>
                                    <td class="p-4 font-semibold text-white/80">
                                        <?= htmlspecialchars($th['username'] ?? 'User') ?>
                                    </td>
                                    <td class="p-4 text-white/60 text-[11px]">
                                        <span class="inline-flex items-center gap-1 mr-2"><i class="fa fa-thumbs-up text-blue-400"></i> <?= $th['vote_count'] ?></span>
                                        <span class="inline-flex items-center gap-1"><i class="fa fa-comment text-indigo-400"></i> <?= $th['comment_count'] ?></span>
                                    </td>
                                    <td class="p-4 text-white/40 text-[11px]">
                                        <?= date('d M Y, H:i', strtotime($th['created_at'])) ?>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="thread.php?id=<?= $th['id'] ?>" target="_blank" class="p-2 rounded-xl bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 border border-blue-500/20" title="Buka Thread">
                                                <i class="fa fa-arrow-up-right-from-square"></i>
                                            </a>
                                            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus topik ini dan semua komentarnya?')">
                                                <input type="hidden" name="action" value="delete_thread">
                                                <input type="hidden" name="thread_id" value="<?= $th['id'] ?>">
                                                <button type="submit" class="p-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20" title="Hapus Topik">
                                                    <i class="fa fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-white/40">
                                    Tidak ada topik diskusi yang ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- TAB 2: COMMENTS -->
    <?php elseif ($tab === 'comments'): ?>
        <div class="admin-glass-card p-4">
            <form method="GET" class="flex items-center gap-3">
                <input type="hidden" name="page" value="databases">
                <input type="hidden" name="tab" value="comments">
                <div class="relative flex-1">
                    <i class="fa fa-search absolute left-3.5 top-3 text-white/40 text-xs"></i>
                    <input 
                        type="text" 
                        name="search_comment" 
                        value="<?= htmlspecialchars($search_comment) ?>" 
                        placeholder="Cari isi komentar atau nama penulis..." 
                        class="w-full pl-9 pr-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/40 text-xs focus:outline-none focus:border-blue-500"
                    >
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition-all">
                    Cari
                </button>
                <?php if ($search_comment !== ''): ?>
                    <a href="?page=databases&tab=comments" class="px-3 py-2 rounded-xl bg-white/10 text-white/70 text-xs">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/10 bg-white/[0.02] text-[10px] font-extrabold uppercase tracking-wider text-white/50">
                            <th class="p-4">ID</th>
                            <th class="p-4">Penulis</th>
                            <th class="p-4">Topik Terkait</th>
                            <th class="p-4">Isi Komentar</th>
                            <th class="p-4">Waktu</th>
                            <th class="p-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-xs">
                        <?php if ($comments_list && $comments_list->num_rows > 0): ?>
                            <?php while ($c = $comments_list->fetch_assoc()): ?>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="p-4 font-mono-code text-white/40">#<?= $c['id'] ?></td>
                                    <td class="p-4 font-bold text-white"><?= htmlspecialchars($c['username'] ?? 'User') ?></td>
                                    <td class="p-4">
                                        <a href="thread.php?id=<?= $c['thread_id'] ?>" target="_blank" class="text-blue-400 hover:underline line-clamp-1">
                                            <?= htmlspecialchars($c['thread_title'] ?? "Thread #{$c['thread_id']}") ?>
                                        </a>
                                    </td>
                                    <td class="p-4 text-white/80 max-w-md">
                                        <p class="line-clamp-2"><?= htmlspecialchars($c['komentar']) ?></p>
                                    </td>
                                    <td class="p-4 text-white/40 text-[11px] whitespace-nowrap">
                                        <?= date('d M Y, H:i', strtotime($c['created_at'])) ?>
                                    </td>
                                    <td class="p-4 text-right">
                                        <form method="POST" onsubmit="return confirm('Hapus komentar ini?')">
                                            <input type="hidden" name="action" value="delete_comment">
                                            <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="p-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20" title="Hapus Komentar">
                                                <i class="fa fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-white/40">
                                    Tidak ada data komentar yang ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <!-- TAB 3: DATABASE SCHEMA INSPECTOR -->
    <?php elseif ($tab === 'schema'): ?>
        <div class="admin-glass-card overflow-hidden">
            <div class="p-4 border-b border-white/10 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-white text-sm">Status Tabel Database (<?= count($tables_info) ?> Tabel)</h3>
                    <p class="text-white/40 text-xs">Pemeriksaan struktur penyimpanan internal MySQL/MariaDB.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/10 bg-white/[0.02] text-[10px] font-extrabold uppercase tracking-wider text-white/50">
                            <th class="p-4">Nama Tabel</th>
                            <th class="p-4">Engine</th>
                            <th class="p-4">Total Baris (Approx)</th>
                            <th class="p-4">Ukuran Data</th>
                            <th class="p-4">Ukuran Index</th>
                            <th class="p-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-xs font-mono-code">
                        <?php foreach ($tables_info as $tbl): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="p-4 font-bold text-white font-sans flex items-center gap-2">
                                    <i class="fa fa-table text-indigo-400 text-xs"></i>
                                    <span><?= htmlspecialchars($tbl['table_name']) ?></span>
                                </td>
                                <td class="p-4 text-white/60"><?= htmlspecialchars($tbl['engine']) ?></td>
                                <td class="p-4 text-emerald-400 font-bold"><?= number_format($tbl['table_rows']) ?></td>
                                <td class="p-4 text-white/80"><?= round($tbl['data_length'] / 1024, 2) ?> KB</td>
                                <td class="p-4 text-white/60"><?= round($tbl['index_length'] / 1024, 2) ?> KB</td>
                                <td class="p-4 text-right font-sans">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="action" value="optimize_table">
                                        <input type="hidden" name="table_name" value="<?= htmlspecialchars($tbl['table_name']) ?>">
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 border border-indigo-500/20 text-xs font-semibold">
                                            <i class="fa fa-wrench mr-1"></i> Optimasi
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
