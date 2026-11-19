<?php
$alert = null;

// Handle Gallery Actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. UPLOAD PHOTO
    if ($action === 'upload_photo') {
        $judul = trim($_POST['judul'] ?? '');
        $album_id = intval($_POST['album_id'] ?? 0);
        $event_id = intval($_POST['event_id'] ?? 0);
        $premium = isset($_POST['premium']) ? 1 : 0;
        $user_id = $_SESSION['user_id'];

        $upload_dir = __DIR__ . '/../../../gallery/galery/' . date('Y') . '/' . date('m') . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        function uploadImage($file, $folder) {
            if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'raw', 'cr2', 'nef', 'dng'])) return null;
            $safe = time() . '_' . uniqid() . '.' . $ext;
            $target = $folder . $safe;
            if (move_uploaded_file($file['tmp_name'], $target)) {
                return 'galery/' . date('Y') . '/' . date('m') . '/' . $safe;
            }
            return null;
        }

        $file_hd         = uploadImage($_FILES['file_hd'] ?? null, $upload_dir);
        $file_edited_hd  = uploadImage($_FILES['file_edited_hd'] ?? null, $upload_dir);
        $file_edited_fhd = uploadImage($_FILES['file_edited_fhd'] ?? null, $upload_dir);
        $file_raw        = uploadImage($_FILES['file_raw'] ?? null, $upload_dir);

        if ($file_hd) {
            $stmt = $conn->prepare("INSERT INTO photos (judul, file_hd, file_edited_hd, file_edited_fhd, file_raw, premium, event_id, uploaded_by, tanggal_upload, album_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
            $stmt->bind_param("sssssiiii", $judul, $file_hd, $file_edited_hd, $file_edited_fhd, $file_raw, $premium, $event_id, $user_id, $album_id);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'message' => "Foto '$judul' berhasil diunggah ke galeri!"];
            } else {
                $alert = ['type' => 'error', 'message' => "Gagal menyimpan foto ke database: " . $stmt->error];
            }
        } else {
            $alert = ['type' => 'error', 'message' => "File HD wajib diunggah dengan format gambar valid (JPG, PNG, WEBP)."];
        }
    }

    // 2. CREATE NEW ALBUM
    elseif ($action === 'create_album') {
        $name = trim($_POST['album_name'] ?? '');
        $desc = trim($_POST['album_desc'] ?? '');
        $thumb = trim($_POST['thumbnail_path'] ?? '');

        if (!empty($name)) {
            $stmt = $conn->prepare("INSERT INTO albums (name, description, thumbnail, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $name, $desc, $thumb);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'message' => "Album '$name' berhasil dibuat!"];
            } else {
                $alert = ['type' => 'error', 'message' => "Gagal membuat album: " . $stmt->error];
            }
        }
    }

    // 3. DELETE PHOTO
    elseif ($action === 'delete_photo') {
        $photo_id = intval($_POST['photo_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM photos WHERE id = ?");
        $stmt->bind_param("i", $photo_id);
        $stmt->execute();
        $alert = ['type' => 'success', 'message' => "Foto #$photo_id berhasil dihapus dari galeri."];
    }

    // 4. DELETE ALBUM
    elseif ($action === 'delete_album') {
        $del_album_id = intval($_POST['album_id'] ?? 0);
        // Set photo album_id to null
        $conn->query("UPDATE photos SET album_id = NULL WHERE album_id = $del_album_id");
        $conn->query("DELETE FROM albums WHERE id = $del_album_id");
        $alert = ['type' => 'success', 'message' => "Album berhasil dihapus."];
    }

    // 5. AUTO-SYNC DIRECTORIES
    elseif ($action === 'sync_gallery') {
        $base_gal = __DIR__ . '/../../../gallery';
        $dirs_to_scan = [
            1 => ['dir' => 'mplsday1', 'prefix' => 'mplsday1/', 'name' => 'MPLS 2025 - Hari Pertama', 'event' => 1],
            2 => ['dir' => 'mplsday2', 'prefix' => 'mplsday2/', 'name' => 'MPLS 2025 - Hari Kedua', 'event' => 1],
            3 => ['dir' => 'prampls2025', 'prefix' => 'prampls2025/', 'name' => 'Pra-MPLS 2025', 'event' => 1],
            4 => ['dir' => 'pelepasan-2025', 'prefix' => 'pelepasan-2025/', 'name' => 'Pelepasan Siswa 2025', 'event' => 2],
            5 => ['dir' => 'potbar', 'prefix' => 'potbar/', 'name' => 'Foto Bersama (Potbar)', 'event' => 3],
            6 => ['dir' => 'random', 'prefix' => 'random/', 'name' => 'Random Momen Sekolah', 'event' => 3],
        ];

        $synced_count = 0;
        foreach ($dirs_to_scan as $alb_id => $info) {
            $full = $base_gal . '/' . $info['dir'];
            if (is_dir($full)) {
                $files = scandir($full);
                $i = 1;
                foreach ($files as $f) {
                    if ($f === '.' || $f === '..' || is_dir($full . '/' . $f)) continue;
                    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $fpath = $info['prefix'] . $f;
                        // Check if already in db
                        $check = $conn->query("SELECT id FROM photos WHERE file_hd = '$fpath'");
                        if ($check->num_rows === 0) {
                            $judul = $info['name'] . " #" . $i;
                            $conn->query("INSERT INTO photos (judul, file_hd, premium, event_id, uploaded_by, tanggal_upload, album_id) VALUES ('$judul', '$fpath', 0, {$info['event']}, {$_SESSION['user_id']}, NOW(), $alb_id)");
                            $synced_count++;
                        }
                        $i++;
                    }
                }
            }
        }
        $alert = ['type' => 'success', 'message' => "Sinkronisasi selesai! $synced_count foto baru berhasil dimasukkan ke sistem."];
    }
}

// Fetch Albums & Events for dropdowns
$albums_res = $conn->query("SELECT a.*, (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id) as photo_count FROM albums a ORDER BY a.id ASC");
$albums = [];
if ($albums_res) {
    while ($row = $albums_res->fetch_assoc()) $albums[] = $row;
}

$events_res = $conn->query("SELECT * FROM events ORDER BY id ASC");
$events = [];
if ($events_res) {
    while ($row = $events_res->fetch_assoc()) $events[] = $row;
}

// Gallery Photos list
$gal_tab = $_GET['tab'] ?? 'upload';
$filter_album = intval($_GET['album_id'] ?? 0);
$gal_search = trim($_GET['search'] ?? '');

$photo_query = "SELECT p.*, a.name as album_name, e.nama_event FROM photos p LEFT JOIN albums a ON p.album_id = a.id LEFT JOIN events e ON p.event_id = e.id";
$conds = [];
if ($filter_album > 0) $conds[] = "p.album_id = $filter_album";
if ($gal_search !== '') {
    $s_safe = $conn->real_escape_string($gal_search);
    $conds[] = "p.judul LIKE '%$s_safe%'";
}
if (!empty($conds)) $photo_query .= " WHERE " . implode(" AND ", $conds);
$photo_query .= " ORDER BY p.tanggal_upload DESC LIMIT 40";
$photos_list = $conn->query($photo_query);
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

    <!-- Header & Tab Navigation -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-white">Manajemen Galeri & Album Dokumentasi</h1>
            <p class="text-white/50 text-xs">Unggah karya fotografi baru, kelola struktur album kegiatan, dan sinkronkan media lokal.</p>
        </div>

        <div class="flex items-center gap-1.5 p-1 rounded-2xl bg-white/5 border border-white/10">
            <a href="?page=galery&tab=upload" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $gal_tab === 'upload' ? 'bg-blue-600 text-white shadow-md' : 'text-white/70 hover:text-white' ?>">
                <i class="fa fa-cloud-arrow-up mr-1.5"></i> Upload Foto
            </a>
            <a href="?page=galery&tab=albums" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $gal_tab === 'albums' ? 'bg-blue-600 text-white shadow-md' : 'text-white/70 hover:text-white' ?>">
                <i class="fa fa-folder mr-1.5"></i> Kelola Album
            </a>
            <a href="?page=galery&tab=photos" class="px-4 py-2 rounded-xl text-xs font-bold transition-all <?= $gal_tab === 'photos' ? 'bg-blue-600 text-white shadow-md' : 'text-white/70 hover:text-white' ?>">
                <i class="fa fa-images mr-1.5"></i> Koleksi Foto
            </a>
        </div>
    </div>

    <!-- TAB 1: UPLOAD FOTO -->
    <?php if ($gal_tab === 'upload'): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Upload -->
            <div class="lg:col-span-2 admin-glass-card p-6 sm:p-8">
                <h3 class="text-base font-bold text-white mb-4 pb-3 border-b border-white/10 flex items-center gap-2">
                    <i class="fa fa-camera text-blue-400"></i> Unggah Dokumentasi Foto Baru
                </h3>

                <form method="POST" enctype="multipart/form-data" class="space-y-5">
                    <input type="hidden" name="action" value="upload_photo">

                    <div>
                        <label class="block text-xs font-semibold text-white/70 mb-1.5">Judul / Keterangan Foto *</label>
                        <input type="text" name="judul" required placeholder="Contoh: Apel Pembukaan MPLS 2025" class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none focus:border-blue-500">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-white/70 mb-1.5">Pilih Album Kegiatan</label>
                            <select name="album_id" class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none">
                                <option value="0" class="bg-[#0b1021]">Tanpa Album (Umum)</option>
                                <?php foreach ($albums as $alb): ?>
                                    <option value="<?= $alb['id'] ?>" class="bg-[#0b1021]"><?= htmlspecialchars($alb['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-white/70 mb-1.5">Pilih Kategori Event</label>
                            <select name="event_id" class="w-full px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none">
                                <option value="0" class="bg-[#0b1021]">Event Default</option>
                                <?php foreach ($events as $ev): ?>
                                    <option value="<?= $ev['id'] ?>" class="bg-[#0b1021]"><?= htmlspecialchars($ev['nama_event'] ?: $ev['event_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- File Upload Input with Live Preview -->
                    <div>
                        <label class="block text-xs font-semibold text-white/70 mb-1.5">File Foto Resolusi HD (Wajib) *</label>
                        <div class="border-2 border-dashed border-white/15 rounded-2xl p-6 text-center hover:border-blue-500/50 transition-colors bg-white/[0.02]">
                            <input type="file" name="file_hd" id="input-file-hd" required accept="image/*" class="hidden" onchange="previewUploadImage(this)">
                            <label for="input-file-hd" class="cursor-pointer flex flex-col items-center">
                                <i class="fa fa-cloud-arrow-up text-3xl text-blue-400 mb-2"></i>
                                <span class="text-xs font-bold text-white">Klik untuk memilih foto</span>
                                <span class="text-[10px] text-white/40 mt-1">Mendukung format JPG, PNG, WEBP (Max 10MB)</span>
                            </label>
                            <div id="image-preview-container" class="mt-4 hidden">
                                <img id="image-preview" src="" class="max-h-56 mx-auto rounded-xl object-contain border border-white/10">
                            </div>
                        </div>
                    </div>

                    <!-- Additional Resolutions (Optional) -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-white/50 mb-1">Edited HD (Opsional)</label>
                            <input type="file" name="file_edited_hd" class="w-full text-xs text-white/60 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-white/10 file:text-white file:text-[10px]">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-white/50 mb-1">Edited FHD (Opsional)</label>
                            <input type="file" name="file_edited_fhd" class="w-full text-xs text-white/60 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-white/10 file:text-white file:text-[10px]">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-white/50 mb-1">RAW Master (Opsional)</label>
                            <input type="file" name="file_raw" class="w-full text-xs text-white/60 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-white/10 file:text-white file:text-[10px]">
                        </div>
                    </div>

                    <!-- Premium Toggle -->
                    <div class="flex items-center gap-3 p-3.5 rounded-xl bg-purple-500/10 border border-purple-500/20">
                        <input type="checkbox" name="premium" id="premium-chk" class="w-4 h-4 rounded text-purple-600 bg-black/40 border-white/20">
                        <label for="premium-chk" class="text-xs text-purple-200 cursor-pointer">
                            <strong>Tandai sebagai Foto Premium</strong> (Hanya dapat diunduh oleh akun tier Contributor / Elite / Legendary)
                        </label>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center gap-2">
                            <i class="fa fa-cloud-arrow-up"></i>
                            <span>Publikasikan ke Galeri</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sync & Info Sidebar -->
            <div class="space-y-6">
                <!-- Sync Tool -->
                <div class="admin-glass-card p-6 bg-gradient-to-br from-indigo-950/40 to-slate-900/60">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-base">
                            <i class="fa fa-rotate"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white">Sinkronisasi Otomatis</h4>
                            <p class="text-[10px] text-white/50">Deteksi foto dari folder lokal</p>
                        </div>
                    </div>
                    <p class="text-xs text-white/70 leading-relaxed mb-4">
                        Pindai folder `gallery/` untuk memasukkan seluruh foto kegiatan MPLS, Pelepasan, dan Potbar yang belum terindeks ke database secara otomatis.
                    </p>
                    <form method="POST">
                        <input type="hidden" name="action" value="sync_gallery">
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg transition-all flex items-center justify-center gap-2">
                            <i class="fa fa-wand-magic-sparkles"></i>
                            <span>Jalankan Sinkronisasi Folder</span>
                        </button>
                    </form>
                </div>

                <!-- Quick Stats -->
                <div class="admin-glass-card p-6">
                    <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-3">Informasi Arsip</h4>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1 border-b border-white/5">
                            <span class="text-white/50">Total Foto</span>
                            <span class="font-bold text-white"><?= $conn->query("SELECT COUNT(*) as c FROM photos")->fetch_assoc()['c'] ?? 0 ?></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-white/5">
                            <span class="text-white/50">Total Album</span>
                            <span class="font-bold text-white"><?= count($albums) ?></span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-white/50">Akses Pengunjung</span>
                            <span class="text-emerald-400 font-semibold">Aktif Publik</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- TAB 2: MANAGE ALBUMS -->
    <?php elseif ($gal_tab === 'albums'): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Add Album Form -->
            <div class="admin-glass-card p-6">
                <h3 class="text-sm font-bold text-white mb-4 pb-3 border-b border-white/10 flex items-center gap-2">
                    <i class="fa fa-folder-plus text-purple-400"></i> Buat Album Baru
                </h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create_album">
                    <div>
                        <label class="block text-xs font-semibold text-white/70 mb-1">Nama Album *</label>
                        <input type="text" name="album_name" required placeholder="Contoh: Porseni Antar Kelas 2026" class="w-full px-3.5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-white/70 mb-1">Deskripsi Singkat</label>
                        <textarea name="album_desc" rows="3" placeholder="Jelaskan mengenai rangkaian kegiatan ini..." class="w-full px-3.5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none focus:border-blue-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-white/70 mb-1">Path Thumbnail Sampul (Opsional)</label>
                        <input type="text" name="thumbnail_path" placeholder="misal: thumb/1.jpg" class="w-full px-3.5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none focus:border-blue-500">
                    </div>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs transition-all">
                        Simpan Album
                    </button>
                </form>
            </div>

            <!-- Existing Albums List -->
            <div class="lg:col-span-2 admin-glass-card p-6">
                <h3 class="text-sm font-bold text-white mb-4 pb-3 border-b border-white/10">
                    Daftar Album Dokumentasi (<?= count($albums) ?>)
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($albums as $alb): ?>
                        <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/5 flex items-start justify-between gap-3 hover:border-purple-500/30 transition-all">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                                    <h4 class="font-bold text-white text-sm"><?= htmlspecialchars($alb['name']) ?></h4>
                                </div>
                                <p class="text-xs text-white/60 line-clamp-2 mb-2"><?= htmlspecialchars($alb['description'] ?: 'Tidak ada deskripsi.') ?></p>
                                <span class="text-[10px] px-2 py-0.5 rounded-md bg-purple-500/15 text-purple-300 font-semibold">
                                    📸 <?= $alb['photo_count'] ?> Foto
                                </span>
                            </div>
                            <div class="flex flex-col gap-1 shrink-0">
                                <a href="../../gallery/album.php?id=<?= $alb['id'] ?>" target="_blank" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 text-white/60 hover:text-white text-center" title="Buka Album">
                                    <i class="fa fa-arrow-up-right-from-square text-xs"></i>
                                </a>
                                <form method="POST" onsubmit="return confirm('Hapus album ini?')">
                                    <input type="hidden" name="action" value="delete_album">
                                    <input type="hidden" name="album_id" value="<?= $alb['id'] ?>">
                                    <button type="submit" class="p-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 text-xs" title="Hapus Album">
                                        <i class="fa fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    <!-- TAB 3: PHOTOS FEED -->
    <?php elseif ($gal_tab === 'photos'): ?>
        <!-- Filter Toolbar -->
        <div class="admin-glass-card p-4">
            <form method="GET" class="flex flex-col md:flex-row gap-3 items-center justify-between">
                <input type="hidden" name="page" value="galery">
                <input type="hidden" name="tab" value="photos">

                <div class="relative w-full md:w-80">
                    <i class="fa fa-search absolute left-3.5 top-3 text-white/40 text-xs"></i>
                    <input 
                        type="text" 
                        name="search" 
                        value="<?= htmlspecialchars($gal_search) ?>" 
                        placeholder="Cari judul foto..." 
                        class="w-full pl-9 pr-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/40 text-xs focus:outline-none focus:border-blue-500"
                    >
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto">
                    <select name="album_id" class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none">
                        <option value="0" class="bg-[#0b1021]">Semua Album</option>
                        <?php foreach ($albums as $alb): ?>
                            <option value="<?= $alb['id'] ?>" <?= $filter_album === (int)$alb['id'] ? 'selected' : '' ?> class="bg-[#0b1021]"><?= htmlspecialchars($alb['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition-all">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Photos Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php if ($photos_list && $photos_list->num_rows > 0): ?>
                <?php while ($p = $photos_list->fetch_assoc()): ?>
                    <div class="admin-glass-card rounded-2xl overflow-hidden group flex flex-col justify-between">
                        <div class="relative h-32 bg-black/40 overflow-hidden">
                            <img 
                                src="../../gallery/<?= htmlspecialchars($p['file_hd']) ?>" 
                                alt="<?= htmlspecialchars($p['judul']) ?>" 
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" 
                                onerror="this.onerror=null;this.src='https://placehold.co/300x200/0b1021/ffffff?text=Galeri';"
                            >
                            <?php if ($p['premium']): ?>
                                <span class="absolute top-2 right-2 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-purple-600 text-white">
                                    ⭐ Premium
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <p class="text-xs font-bold text-white truncate" title="<?= htmlspecialchars($p['judul']) ?>">
                                <?= htmlspecialchars($p['judul']) ?>
                            </p>
                            <span class="text-[10px] text-white/50 block truncate">
                                <?= htmlspecialchars($p['album_name'] ?: 'Umum') ?>
                            </span>
                            <div class="mt-2 pt-2 border-t border-white/5 flex items-center justify-between">
                                <a href="../../gallery/<?= htmlspecialchars($p['file_hd']) ?>" target="_blank" class="text-[10px] text-blue-400 hover:text-blue-300">
                                    Lihat HD
                                </a>
                                <form method="POST" onsubmit="return confirm('Hapus foto ini dari galeri?')">
                                    <input type="hidden" name="action" value="delete_photo">
                                    <input type="hidden" name="photo_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="text-red-400 hover:text-red-300 text-xs">
                                        <i class="fa fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full admin-glass-card p-12 text-center text-white/40 text-xs">
                    Tidak ada foto dalam album atau filter ini.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<script>
    function previewUploadImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('image-preview');
                const cont = document.getElementById('image-preview-container');
                img.src = e.target.result;
                cont.classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
