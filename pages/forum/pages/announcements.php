<?php
$alert = null;

// Ensure table exists
$conn->query("CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle POST actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create Announcement
    if ($action === 'create_announcement') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = $_POST['type'] ?? 'info';

        if (!empty($title) && !empty($content)) {
            $stmt = $conn->prepare("INSERT INTO announcements (title, content, type, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->bind_param("sss", $title, $content, $type);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'message' => "Pengumuman '$title' berhasil dipublikasikan!"];
            } else {
                $alert = ['type' => 'error', 'message' => "Gagal menyimpan pengumuman: " . $stmt->error];
            }
        } else {
            $alert = ['type' => 'error', 'message' => "Judul dan isi pengumuman wajib diisi."];
        }
    }

    // Toggle Active Status
    elseif ($action === 'toggle_active') {
        $ann_id = intval($_POST['ann_id'] ?? 0);
        $conn->query("UPDATE announcements SET is_active = IF(is_active=1, 0, 1) WHERE id = $ann_id");
        $alert = ['type' => 'success', 'message' => "Status penayangan pengumuman berhasil diubah."];
    }

    // Delete Announcement
    elseif ($action === 'delete_announcement') {
        $ann_id = intval($_POST['ann_id'] ?? 0);
        $conn->query("DELETE FROM announcements WHERE id = $ann_id");
        $alert = ['type' => 'success', 'message' => "Pengumuman berhasil dihapus."];
    }
}

// Fetch all announcements
$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
?>

<div class="space-y-6">

    <!-- Alert -->
    <?php if ($alert): ?>
        <div class="p-4 rounded-2xl flex items-center justify-between <?= $alert['type'] === 'success' ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-200' : 'bg-red-500/15 border border-red-500/30 text-red-200' ?>">
            <div class="flex items-center gap-3">
                <i class="fa fa-circle-check text-emerald-400 text-lg"></i>
                <span class="text-xs font-semibold"><?= htmlspecialchars($alert['message']) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-white/40 hover:text-white text-xs">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-white">Broadcast Banner & Pengumuman</h1>
            <p class="text-white/50 text-xs">Tayangkan pengumuman penting di bagian atas forum dan galeri untuk seluruh pengunjung.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Create Form -->
        <div class="admin-glass-card p-6">
            <h3 class="text-sm font-bold text-white mb-4 pb-3 border-b border-white/10 flex items-center gap-2">
                <i class="fa fa-bullhorn text-amber-400"></i> Buat Pengumuman Baru
            </h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create_announcement">

                <div>
                    <label class="block text-xs font-semibold text-white/70 mb-1">Judul Pengumuman *</label>
                    <input type="text" name="title" required placeholder="Contoh: Pendaftaran OSIS Resmi Dibuka!" class="w-full px-3.5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-white/70 mb-1">Tipe Pesan Banner</label>
                    <select name="type" class="w-full px-3.5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none">
                        <option value="info" class="bg-[#0b1021]">🔵 Informasi (Biru)</option>
                        <option value="warning" class="bg-[#0b1021]">🟡 Peringatan / Penting (Kuning)</option>
                        <option value="success" class="bg-[#0b1021]">🟢 Keberhasilan / Event (Hijau)</option>
                        <option value="danger" class="bg-[#0b1021]">🔴 Urgent / Perhatian (Merah)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-white/70 mb-1">Isi Pesan *</label>
                    <textarea name="content" rows="4" required placeholder="Tuliskan detail pengumuman yang akan ditampilkan kepada siswa..." class="w-full px-3.5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none focus:border-blue-500"></textarea>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-bold text-xs shadow-lg shadow-orange-600/20 transition-all flex items-center justify-center gap-2">
                    <i class="fa fa-paper-plane"></i>
                    <span>Terbitkan Pengumuman</span>
                </button>
            </form>
        </div>

        <!-- Announcements List -->
        <div class="lg:col-span-2 space-y-4">
            <h3 class="text-sm font-bold text-white mb-2">Riwayat Pengumuman</h3>
            
            <?php if ($announcements && $announcements->num_rows > 0): ?>
                <?php while ($ann = $announcements->fetch_assoc()): 
                    $type_classes = [
                        'info' => 'border-blue-500/30 bg-blue-500/10 text-blue-300',
                        'warning' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
                        'success' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
                        'danger' => 'border-red-500/30 bg-red-500/10 text-red-300',
                    ];
                    $badge_class = $type_classes[$ann['type']] ?? $type_classes['info'];
                ?>
                    <div class="admin-glass-card p-5 flex flex-col justify-between gap-4 <?= $ann['is_active'] ? 'border-l-4 border-l-blue-500' : 'opacity-60' ?>">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border <?= $badge_class ?>">
                                        <?= strtoupper($ann['type']) ?>
                                    </span>
                                    <?php if ($ann['is_active']): ?>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 font-bold flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Tayang
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-white/5 text-white/40 font-bold">
                                            Nonaktif
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-[11px] text-white/40"><?= date('d M Y, H:i', strtotime($ann['created_at'])) ?></span>
                            </div>

                            <h4 class="text-sm font-bold text-white mb-1"><?= htmlspecialchars($ann['title']) ?></h4>
                            <p class="text-xs text-white/70 leading-relaxed"><?= nl2br(htmlspecialchars($ann['content'])) ?></p>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-white/5">
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="ann_id" value="<?= $ann['id'] ?>">
                                <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-semibold <?= $ann['is_active'] ? 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/20' : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20' ?>">
                                    <i class="fa <?= $ann['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?> mr-1"></i>
                                    <?= $ann['is_active'] ? 'Sembunyikan' : 'Aktifkan' ?>
                                </button>
                            </form>

                            <form method="POST" onsubmit="return confirm('Hapus pengumuman ini?')">
                                <input type="hidden" name="action" value="delete_announcement">
                                <input type="hidden" name="ann_id" value="<?= $ann['id'] ?>">
                                <button type="submit" class="p-1.5 px-3 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 text-xs">
                                    <i class="fa fa-trash-can mr-1"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="admin-glass-card p-12 text-center text-white/40 text-xs">
                    Belum ada pengumuman yang diterbitkan.
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>
