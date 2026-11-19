<?php
$alert = null;

// Handle file deletion
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_file') {
        $file_rel = $_POST['file_path'] ?? '';
        // Security check: prevent directory traversal
        $real_base_post = realpath(__DIR__ . '/../post');
        $real_base_uploads = realpath(__DIR__ . '/../uploads');
        $target_full = realpath(__DIR__ . '/../' . $file_rel);

        if ($target_full && (strpos($target_full, $real_base_post) === 0 || strpos($target_full, $real_base_uploads) === 0)) {
            if (@unlink($target_full)) {
                $alert = ['type' => 'success', 'message' => "File '" . basename($file_rel) . "' berhasil dihapus."];
            } else {
                $alert = ['type' => 'error', 'message' => "Gagal menghapus file."];
            }
        } else {
            $alert = ['type' => 'error', 'message' => "Path file tidak valid atau berada di luar folder yang diizinkan."];
        }
    }
}

// Target folder filter
$folder = $_GET['folder'] ?? 'post';
$type_filter = $_GET['type'] ?? 'all';
$search_file = trim($_GET['search'] ?? '');

$folders_map = [
    'post' => ['label' => 'Forum Posts (post/)', 'path' => __DIR__ . '/../post', 'url_prefix' => 'post/'],
    'uploads' => ['label' => 'Avatar & Profil (uploads/)', 'path' => __DIR__ . '/../uploads', 'url_prefix' => 'uploads/'],
];

$active_folder_info = $folders_map[$folder] ?? $folders_map['post'];
$dir_path = $active_folder_info['path'];
$url_prefix = $active_folder_info['url_prefix'];

$files_data = [];
$total_folder_size = 0;

if (is_dir($dir_path)) {
    $scanned = scandir($dir_path);
    foreach ($scanned as $f) {
        if ($f === '.' || $f === '..' || $f === '.htaccess' || is_dir($dir_path . '/' . $f)) continue;

        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        $full_p = $dir_path . '/' . $f;
        $size = filesize($full_p);
        $total_folder_size += $size;
        $mtime = filemtime($full_p);

        // Filter by search
        if ($search_file !== '' && stripos($f, $search_file) === false) continue;

        // Filter by type
        $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        $is_video = in_array($ext, ['mp4', 'webm']);

        if ($type_filter === 'image' && !$is_image) continue;
        if ($type_filter === 'video' && !$is_video) continue;

        $files_data[] = [
            'name' => $f,
            'ext' => $ext,
            'size' => $size,
            'mtime' => $mtime,
            'is_image' => $is_image,
            'is_video' => $is_video,
            'rel_path' => $url_prefix . $f,
        ];
    }
}

// Sort by newest
usort($files_data, function($a, $b) {
    return $b['mtime'] - $a['mtime'];
});
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

    <!-- Header & Storage Summary -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-white">Media & File Storage</h1>
            <p class="text-white/50 text-xs">Jelajahi, pratinjau, dan bersihkan file lampiran post dan avatar pengguna.</p>
        </div>

        <div class="flex items-center gap-2 p-1 rounded-2xl bg-white/5 border border-white/10">
            <a href="?page=files&folder=post" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $folder === 'post' ? 'bg-blue-600 text-white shadow-md' : 'text-white/70 hover:text-white' ?>">
                <i class="fa fa-images mr-1"></i> Post Attachments
            </a>
            <a href="?page=files&folder=uploads" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $folder === 'uploads' ? 'bg-blue-600 text-white shadow-md' : 'text-white/70 hover:text-white' ?>">
                <i class="fa fa-user mr-1"></i> User Avatars
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="admin-glass-card p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-3 items-center justify-between">
            <input type="hidden" name="page" value="files">
            <input type="hidden" name="folder" value="<?= htmlspecialchars($folder) ?>">

            <div class="relative w-full md:w-80">
                <i class="fa fa-search absolute left-3.5 top-3 text-white/40 text-xs"></i>
                <input 
                    type="text" 
                    name="search" 
                    value="<?= htmlspecialchars($search_file) ?>" 
                    placeholder="Cari nama file..." 
                    class="w-full pl-9 pr-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/40 text-xs focus:outline-none focus:border-blue-500"
                >
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <select name="type" class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none">
                    <option value="all" <?= $type_filter === 'all' ? 'selected' : '' ?> class="bg-[#0b1021]">Semua Format</option>
                    <option value="image" <?= $type_filter === 'image' ? 'selected' : '' ?> class="bg-[#0b1021]">Gambar Saja</option>
                    <option value="video" <?= $type_filter === 'video' ? 'selected' : '' ?> class="bg-[#0b1021]">Video Saja</option>
                </select>

                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition-all">
                    Terapkan
                </button>

                <span class="text-xs text-white/50 border-l border-white/10 pl-3">
                    Total: <strong class="text-white"><?= count($files_data) ?> file</strong> (<?= round($total_folder_size / (1024 * 1024), 2) ?> MB)
                </span>
            </div>
        </form>
    </div>

    <!-- File Grid -->
    <?php if (!empty($files_data)): ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php foreach ($files_data as $file): ?>
                <div class="admin-glass-card rounded-2xl overflow-hidden group flex flex-col justify-between">
                    <!-- Thumbnail Container -->
                    <div class="relative h-32 bg-black/40 overflow-hidden flex items-center justify-center">
                        <?php if ($file['is_image']): ?>
                            <img 
                                src="<?= htmlspecialchars($file['rel_path']) ?>" 
                                alt="<?= htmlspecialchars($file['name']) ?>" 
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" 
                                onerror="this.onerror=null;this.src='https://placehold.co/300x200/0b1021/ffffff?text=Image';"
                                onclick="openFileModal('<?= htmlspecialchars($file['rel_path']) ?>', 'image', '<?= htmlspecialchars($file['name']) ?>')"
                            >
                        <?php elseif ($file['is_video']): ?>
                            <div class="flex flex-col items-center justify-center text-blue-400 cursor-pointer" onclick="openFileModal('<?= htmlspecialchars($file['rel_path']) ?>', 'video', '<?= htmlspecialchars($file['name']) ?>')">
                                <i class="fa fa-circle-play text-3xl"></i>
                                <span class="text-[10px] uppercase font-bold text-white/60 mt-1"><?= $file['ext'] ?></span>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center text-white/40">
                                <i class="fa fa-file text-3xl"></i>
                                <span class="text-[10px] uppercase font-bold text-white/60 mt-1"><?= $file['ext'] ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Format Pill -->
                        <span class="absolute top-2 left-2 text-[9px] px-1.5 py-0.5 rounded font-mono-code font-bold uppercase bg-black/60 text-white/80 backdrop-blur-md">
                            <?= $file['ext'] ?>
                        </span>
                    </div>

                    <!-- Meta Details -->
                    <div class="p-3">
                        <p class="text-xs font-bold text-white truncate" title="<?= htmlspecialchars($file['name']) ?>">
                            <?= htmlspecialchars($file['name']) ?>
                        </p>
                        <div class="flex items-center justify-between text-[10px] text-white/40 mt-1">
                            <span><?= round($file['size'] / 1024, 1) ?> KB</span>
                            <span><?= date('d/m/y', $file['mtime']) ?></span>
                        </div>

                        <!-- Actions -->
                        <div class="mt-2.5 pt-2 border-t border-white/5 flex items-center justify-between">
                            <a href="<?= htmlspecialchars($file['rel_path']) ?>" download class="text-[10px] text-blue-400 hover:text-blue-300 font-semibold flex items-center gap-1">
                                <i class="fa fa-download"></i> Unduh
                            </a>
                            <form method="POST" onsubmit="return confirm('Hapus file ini permanen?')">
                                <input type="hidden" name="action" value="delete_file">
                                <input type="hidden" name="file_path" value="<?= htmlspecialchars($file['rel_path']) ?>">
                                <button type="submit" class="text-red-400 hover:text-red-300 text-xs" title="Hapus File">
                                    <i class="fa fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="admin-glass-card p-12 text-center text-white/40 text-xs">
            <i class="fa fa-folder-open text-3xl mb-2 block"></i>
            Tidak ada file yang ditemukan dalam folder ini.
        </div>
    <?php endif; ?>

</div>

<!-- Preview Modal -->
<div id="file-modal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md hidden flex items-center justify-center p-4" onclick="closeFileModal()">
    <div class="max-w-3xl w-full admin-glass-card p-4 relative" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-white/10">
            <h4 id="modal-filename" class="text-xs font-bold text-white truncate"></h4>
            <button onclick="closeFileModal()" class="text-white/40 hover:text-white">&times;</button>
        </div>
        <div id="modal-content" class="flex items-center justify-center max-h-[70vh] overflow-hidden rounded-xl bg-black/40">
        </div>
    </div>
</div>

<script>
    function openFileModal(src, type, name) {
        document.getElementById('modal-filename').textContent = name;
        const container = document.getElementById('modal-content');
        if (type === 'image') {
            container.innerHTML = `<img src="${src}" class="max-w-full max-h-[70vh] object-contain">`;
        } else if (type === 'video') {
            container.innerHTML = `<video src="${src}" controls autoplay class="max-w-full max-h-[70vh]"></video>`;
        }
        document.getElementById('file-modal').classList.remove('hidden');
    }
    function closeFileModal() {
        document.getElementById('file-modal').classList.add('hidden');
        document.getElementById('modal-content').innerHTML = '';
    }
</script>
