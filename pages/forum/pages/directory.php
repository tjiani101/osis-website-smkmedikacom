<?php
// Calculate statistics
$stat_users_total = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0;
$stat_users_active = $conn->query("SELECT COUNT(*) as c FROM users WHERE status = 'active'")->fetch_assoc()['c'] ?? 0;
$stat_users_banned = $stat_users_total - $stat_users_active;

$stat_threads = $conn->query("SELECT COUNT(*) as c FROM threads")->fetch_assoc()['c'] ?? 0;
$stat_comments = $conn->query("SELECT COUNT(*) as c FROM comments")->fetch_assoc()['c'] ?? 0;

$stat_photos = $conn->query("SELECT COUNT(*) as c FROM photos")->fetch_assoc()['c'] ?? 0;
$stat_albums = $conn->query("SELECT COUNT(*) as c FROM albums")->fetch_assoc()['c'] ?? 0;

$stat_eskul = $conn->query("SELECT COUNT(*) as c FROM pendaftaran_eskul")->fetch_assoc()['c'] ?? 0;
$stat_osis = $conn->query("SELECT COUNT(*) as c FROM pendaftaran_osis")->fetch_assoc()['c'] ?? 0;

// Disk Usage calculation
function getDirSize($path) {
    $bytes = 0;
    if (is_dir($path)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
            $bytes += $file->getSize();
        }
    }
    return $bytes;
}
$post_size = getDirSize(__DIR__ . '/../post');
$gallery_size = getDirSize(__DIR__ . '/../../../gallery');
$total_bytes = $post_size + $gallery_size;
$storage_display = round($total_bytes / (1024 * 1024), 2) . ' MB';

// Recent threads
$recent_threads = $conn->query("
    SELECT t.*, u.username, f.nama as forum_name,
           (SELECT COUNT(*) FROM comments c WHERE c.thread_id = t.id) as comment_count
    FROM threads t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN forum f ON t.forum_id = f.id
    ORDER BY t.created_at DESC
    LIMIT 5
");

// Recent logs
$recent_logs = $conn->query("SELECT * FROM logs ORDER BY timestamp DESC LIMIT 6");

// Recent registrations
$recent_eskul = $conn->query("SELECT nama, eskul, tanggal_daftar, 'Eskul' as type FROM pendaftaran_eskul ORDER BY tanggal_daftar DESC LIMIT 3");
$recent_osis = $conn->query("SELECT nama, 'Calon Pengurus' as eskul, tanggal_daftar, 'OSIS' as type FROM pendaftaran_osis ORDER BY tanggal_daftar DESC LIMIT 3");
?>

<div class="space-y-8">

    <!-- Welcome Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-900/40 via-indigo-900/30 to-purple-900/40 border border-white/10 p-6 sm:p-8 backdrop-blur-xl">
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-bold uppercase tracking-wider mb-2">
                    <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                    Sistem Kontrol Terpadu
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white">
                    Selamat Datang, <span class="text-gradient"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>!
                </h1>
                <p class="text-white/60 text-xs sm:text-sm mt-1 max-w-xl leading-relaxed">
                    Kelola seluruh data forum diskusi, media dokumentasi galeri, pendaftaran kesiswaan, dan konfigurasi keamanan dari satu dashboard terpadu.
                </p>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <a href="buat_post.php" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center gap-2 transition-all">
                    <i class="fa fa-plus"></i>
                    <span>Buat Topik</span>
                </a>
                <a href="?page=galery" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs border border-white/10 flex items-center gap-2 transition-all">
                    <i class="fa fa-cloud-arrow-up text-purple-400"></i>
                    <span>Upload Foto</span>
                </a>
                <a href="?page=announcements" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs border border-white/10 flex items-center gap-2 transition-all">
                    <i class="fa fa-bullhorn text-amber-400"></i>
                    <span>Broadcast</span>
                </a>
            </div>
        </div>
        <!-- Decorative Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        
        <!-- Metric: Users -->
        <div class="admin-glass-card p-6 relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-white/50">Total Pengguna</span>
                <div class="w-10 h-10 rounded-xl bg-blue-500/15 text-blue-400 flex items-center justify-center text-base border border-blue-500/20">
                    <i class="fa fa-users"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white font-mono-code"><?= $stat_users_total ?></span>
                <span class="text-xs text-white/40">Akun Terdaftar</span>
            </div>
            <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-[11px]">
                <span class="text-emerald-400 flex items-center gap-1">
                    <i class="fa fa-circle-check"></i> <?= $stat_users_active ?> Aktif
                </span>
                <?php if ($stat_users_banned > 0): ?>
                    <span class="text-red-400 flex items-center gap-1">
                        <i class="fa fa-ban"></i> <?= $stat_users_banned ?> Dibanned
                    </span>
                <?php endif; ?>
                <a href="?page=users" class="text-blue-400 hover:text-blue-300 font-semibold ml-auto">Kelola &rarr;</a>
            </div>
        </div>

        <!-- Metric: Forum Threads & Comments -->
        <div class="admin-glass-card p-6 relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-white/50">Forum Diskusi</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/15 text-indigo-400 flex items-center justify-center text-base border border-indigo-500/20">
                    <i class="fa fa-comments"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white font-mono-code"><?= $stat_threads ?></span>
                <span class="text-xs text-white/40">Topik Diskusi</span>
            </div>
            <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-[11px]">
                <span class="text-indigo-300">
                    <i class="fa fa-comment-dots"></i> <?= $stat_comments ?> Total Komentar
                </span>
                <a href="?page=databases" class="text-indigo-400 hover:text-indigo-300 font-semibold">Moderasi &rarr;</a>
            </div>
        </div>

        <!-- Metric: Gallery Photos & Albums -->
        <div class="admin-glass-card p-6 relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-white/50">Arsip Galeri</span>
                <div class="w-10 h-10 rounded-xl bg-purple-500/15 text-purple-400 flex items-center justify-center text-base border border-purple-500/20">
                    <i class="fa fa-photo-film"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white font-mono-code"><?= $stat_photos ?></span>
                <span class="text-xs text-white/40">Foto Kegiatan</span>
            </div>
            <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-[11px]">
                <span class="text-purple-300">
                    <i class="fa fa-folder"></i> <?= $stat_albums ?> Album Event
                </span>
                <a href="?page=galery" class="text-purple-400 hover:text-purple-300 font-semibold">Kelola &rarr;</a>
            </div>
        </div>

        <!-- Metric: Registrations & Storage -->
        <div class="admin-glass-card p-6 relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-white/50">Pendaftar & Storage</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-400 flex items-center justify-center text-base border border-emerald-500/20">
                    <i class="fa fa-server"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white font-mono-code"><?= ($stat_eskul + $stat_osis) ?></span>
                <span class="text-xs text-white/40">Total Pendaftar</span>
            </div>
            <div class="mt-4 pt-3 border-t border-white/5 flex items-center justify-between text-[11px]">
                <span class="text-emerald-400">
                    <i class="fa fa-hard-drive"></i> <?= $storage_display ?> Digunakan
                </span>
                <a href="?page=files" class="text-emerald-400 hover:text-emerald-300 font-semibold">Files &rarr;</a>
            </div>
        </div>

    </div>

    <!-- Two-Column Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: 2 Spans -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Latest Threads Widget -->
            <div class="admin-glass-card p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/5">
                    <div class="flex items-center gap-2">
                        <i class="fa fa-fire text-amber-400 text-sm"></i>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">Topik Forum Terbaru</h2>
                    </div>
                    <a href="?page=databases" class="text-xs text-blue-400 hover:text-blue-300 font-semibold">Semua Diskusi &rarr;</a>
                </div>

                <div class="space-y-3">
                    <?php if ($recent_threads && $recent_threads->num_rows > 0): ?>
                        <?php while ($th = $recent_threads->fetch_assoc()): ?>
                            <div class="p-3.5 rounded-2xl bg-white/[0.02] hover:bg-white/[0.05] border border-white/5 transition-all flex items-center justify-between gap-4">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[10px] px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-300 border border-blue-500/20 font-bold uppercase">
                                            <?= htmlspecialchars($th['forum_name'] ?? 'Umum') ?>
                                        </span>
                                        <span class="text-xs text-white/40">•</span>
                                        <span class="text-xs text-white/60">Oleh <strong class="text-white/80"><?= htmlspecialchars($th['username'] ?? 'User') ?></strong></span>
                                        <span class="text-xs text-white/40">•</span>
                                        <span class="text-[11px] text-white/40"><?= date('d M Y, H:i', strtotime($th['created_at'])) ?></span>
                                    </div>
                                    <a href="thread.php?id=<?= $th['id'] ?>" target="_blank" class="text-sm font-bold text-white hover:text-blue-300 transition-colors line-clamp-1">
                                        <?= htmlspecialchars($th['judul']) ?>
                                    </a>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="text-xs px-2.5 py-1 rounded-full bg-white/5 border border-white/10 text-white/70 font-semibold flex items-center gap-1.5">
                                        <i class="fa fa-comment text-blue-400 text-[10px]"></i>
                                        <?= $th['comment_count'] ?>
                                    </span>
                                    <a href="thread.php?id=<?= $th['id'] ?>" target="_blank" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-white/60 hover:text-white transition-colors" title="Buka Topik">
                                        <i class="fa fa-arrow-up-right-from-square text-xs"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center py-8 text-xs text-white/40">Belum ada topik diskusi yang dibuat.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Registrations Summary Widget -->
            <div class="admin-glass-card p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/5">
                    <div class="flex items-center gap-2">
                        <i class="fa fa-id-card text-emerald-400 text-sm"></i>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">Pendaftar Terkini (Eskul & OSIS)</h2>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <a href="?page=eskul" class="text-emerald-400 hover:text-emerald-300 font-semibold">Data Eskul</a>
                        <span>•</span>
                        <a href="?page=osis" class="text-blue-400 hover:text-blue-300 font-semibold">Data OSIS</a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Eskul -->
                    <div class="space-y-2.5">
                        <p class="text-[11px] font-extrabold uppercase text-white/50 tracking-wider">Ekstrakurikuler Terbaru</p>
                        <?php if ($recent_eskul && $recent_eskul->num_rows > 0): ?>
                            <?php while ($r = $recent_eskul->fetch_assoc()): ?>
                                <div class="p-3 rounded-xl bg-white/[0.02] border border-white/5 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-white"><?= htmlspecialchars($r['nama']) ?></p>
                                        <span class="text-[10px] text-emerald-400 font-semibold"><?= htmlspecialchars($r['eskul']) ?></span>
                                    </div>
                                    <span class="text-[10px] text-white/40"><?= date('d M', strtotime($r['tanggal_daftar'])) ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-xs text-white/40 py-2">Belum ada pendaftaran eskul.</p>
                        <?php endif; ?>
                    </div>

                    <!-- OSIS -->
                    <div class="space-y-2.5">
                        <p class="text-[11px] font-extrabold uppercase text-white/50 tracking-wider">Calon OSIS Terbaru</p>
                        <?php if ($recent_osis && $recent_osis->num_rows > 0): ?>
                            <?php while ($r = $recent_osis->fetch_assoc()): ?>
                                <div class="p-3 rounded-xl bg-white/[0.02] border border-white/5 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-bold text-white"><?= htmlspecialchars($r['nama']) ?></p>
                                        <span class="text-[10px] text-blue-400 font-semibold">Calon Pengurus</span>
                                    </div>
                                    <span class="text-[10px] text-white/40"><?= date('d M', strtotime($r['tanggal_daftar'])) ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-xs text-white/40 py-2">Belum ada pendaftaran OSIS.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: 1 Span -->
        <div class="space-y-6">

            <!-- Recent System Activity / Audit Log -->
            <div class="admin-glass-card p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-white/5">
                    <div class="flex items-center gap-2">
                        <i class="fa fa-shield-halved text-blue-400 text-sm"></i>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">Audit Log Aktivitas</h2>
                    </div>
                    <a href="?page=log" class="text-xs text-blue-400 hover:text-blue-300 font-semibold">Semua Log &rarr;</a>
                </div>

                <div class="space-y-3">
                    <?php if ($recent_logs && $recent_logs->num_rows > 0): ?>
                        <?php while ($l = $recent_logs->fetch_assoc()): ?>
                            <div class="p-3 rounded-xl bg-white/[0.02] border border-white/5 text-xs">
                                <div class="flex items-center justify-between text-[10px] text-white/40 mb-1">
                                    <span class="font-bold text-indigo-300"><?= htmlspecialchars($l['username']) ?></span>
                                    <span><?= date('H:i • d M', strtotime($l['timestamp'])) ?></span>
                                </div>
                                <p class="text-white/80 line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($l['action']) ?>
                                </p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center py-6 text-xs text-white/40">Belum ada catatan log aktivitas.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Server & Software Information -->
            <div class="admin-glass-card p-6">
                <div class="flex items-center gap-2 mb-4 pb-3 border-b border-white/5">
                    <i class="fa fa-microchip text-purple-400 text-sm"></i>
                    <h2 class="text-sm font-bold text-white uppercase tracking-wider">Status Sistem & Server</h2>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between py-1.5 border-b border-white/5">
                        <span class="text-white/50">PHP Version</span>
                        <span class="font-mono-code font-bold text-white"><?= phpversion() ?></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-white/5">
                        <span class="text-white/50">Database Engine</span>
                        <span class="font-mono-code font-bold text-white"><?= htmlspecialchars(substr($conn->server_info, 0, 20)) ?></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-white/5">
                        <span class="text-white/50">Database Name</span>
                        <span class="font-mono-code font-bold text-emerald-400"><?= htmlspecialchars($db_name ?? 'anjay') ?></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5 border-b border-white/5">
                        <span class="text-white/50">Max Upload Size</span>
                        <span class="font-mono-code font-bold text-white"><?= ini_get('upload_max_filesize') ?></span>
                    </div>
                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-white/50">Server OS</span>
                        <span class="font-mono-code font-bold text-white"><?= PHP_OS ?></span>
                    </div>
                </div>
            </div>

            <!-- Developer & Version Credits -->
            <div class="admin-glass-card p-6 relative overflow-hidden bg-gradient-to-br from-indigo-950/40 to-slate-900/60">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center text-sm font-bold">
                        <i class="fa fa-code"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-white">Forum & Galeri Medikacom</h3>
                        <p class="text-[10px] text-white/50">Arsitektur & Pengembangan Tim Siswa</p>
                    </div>
                </div>
                
                <div class="space-y-1.5 text-xs text-white/70 py-2 border-y border-white/5 mb-3">
                    <p><i class="fa fa-user-check text-blue-400 text-[10px] w-4"></i> Created by: <strong class="text-white">Ghani (TKJ X-A)</strong></p>
                    <p><i class="fa fa-handshake-angle text-indigo-400 text-[10px] w-4"></i> Helper: <strong class="text-white">Radi (RPL X-A)</strong></p>
                </div>

                <div class="flex flex-wrap gap-2 text-[10px] font-mono-code">
                    <span class="px-2 py-0.5 rounded-md bg-white/5 border border-white/10 text-white/60">Home: v2.3.2</span>
                    <span class="px-2 py-0.5 rounded-md bg-white/5 border border-white/10 text-white/60">Forum: v1.9.0</span>
                    <span class="px-2 py-0.5 rounded-md bg-blue-500/20 border border-blue-500/30 text-blue-300 font-bold">Panel: v2.5.0 Pro</span>
                </div>
            </div>

        </div>

    </div>

</div>
