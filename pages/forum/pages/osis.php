<?php
$alert = null;

// Handle Delete
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM pendaftaran_osis WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $alert = ['type' => 'success', 'message' => "Data calon pengurus #$del_id berhasil dihapus."];
    }
}

// Search
$search = trim($_GET['search'] ?? '');
$query = "SELECT * FROM pendaftaran_osis";
if ($search !== '') {
    $s_safe = $conn->real_escape_string($search);
    $query .= " WHERE nama LIKE '%$s_safe%' OR nis LIKE '%$s_safe%' OR email LIKE '%$s_safe%'";
}
$query .= " ORDER BY tanggal_daftar DESC";
$candidates = $conn->query($query);
$total_c = $conn->query("SELECT COUNT(*) as c FROM pendaftaran_osis")->fetch_assoc()['c'] ?? 0;
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
            <h1 class="text-xl font-bold text-white">Seleksi & Pendaftaran Calon OSIS</h1>
            <p class="text-white/50 text-xs">Evaluasi berkas calon pengurus OSIS & MPK SMK Medikacom Bandung periode mendatang.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3.5 py-1.5 rounded-full bg-blue-500/15 border border-blue-500/30 text-blue-300 text-xs font-bold">
                🎯 <?= $total_c ?> Calon Terdaftar
            </span>
        </div>
    </div>

    <!-- Search Toolbar -->
    <div class="admin-glass-card p-4">
        <form method="GET" class="flex items-center gap-3">
            <input type="hidden" name="page" value="osis">
            <div class="relative flex-1">
                <i class="fa fa-search absolute left-3.5 top-3 text-white/40 text-xs"></i>
                <input 
                    type="text" 
                    name="search" 
                    value="<?= htmlspecialchars($search) ?>" 
                    placeholder="Cari nama calon pengurus, NIS, atau email..." 
                    class="w-full pl-9 pr-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/40 text-xs focus:outline-none focus:border-blue-500"
                >
            </div>
            <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition-all">
                Cari
            </button>
            <?php if ($search !== ''): ?>
                <a href="?page=osis" class="px-3 py-2 rounded-xl bg-white/10 text-white/70 text-xs">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Candidates Table -->
    <div class="admin-glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/10 bg-white/[0.02] text-[10px] font-extrabold uppercase tracking-wider text-white/50">
                        <th class="p-4">Calon Pengurus</th>
                        <th class="p-4">NIS</th>
                        <th class="p-4">Kontak</th>
                        <th class="p-4">Visi & Misi</th>
                        <th class="p-4">Motivasi & Cerita</th>
                        <th class="p-4">Curriculum Vitae</th>
                        <th class="p-4">Waktu Daftar</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-xs">
                    <?php if ($candidates && $candidates->num_rows > 0): ?>
                        <?php while ($c = $candidates->fetch_assoc()): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <!-- Name & Avatar -->
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 flex items-center justify-center font-bold text-xs shrink-0">
                                            <?= strtoupper(substr($c['nama'], 0, 1)) ?>
                                        </div>
                                        <span class="font-bold text-white"><?= htmlspecialchars($c['nama']) ?></span>
                                    </div>
                                </td>

                                <!-- NIS -->
                                <td class="p-4 font-mono-code text-white/70">
                                    <?= htmlspecialchars($c['nis'] ?: '-') ?>
                                </td>

                                <!-- Kontak -->
                                <td class="p-4">
                                    <p class="text-white/80"><?= htmlspecialchars($c['email'] ?: '-') ?></p>
                                    <p class="text-[11px] text-white/50"><?= htmlspecialchars($c['telp'] ?: '-') ?></p>
                                </td>

                                <!-- Visi Misi Button -->
                                <td class="p-4">
                                    <button onclick="openVisiModal('<?= htmlspecialchars(addslashes($c['nama'])) ?>', `<?= htmlspecialchars(addslashes($c['visimisi'])) ?>`)" class="px-3 py-1.5 rounded-xl bg-blue-500/10 hover:bg-blue-500/20 text-blue-300 border border-blue-500/20 text-[11px] font-semibold flex items-center gap-1.5">
                                        <i class="fa fa-eye"></i> Baca Visi Misi
                                    </button>
                                </td>

                                <!-- Cerita Button -->
                                <td class="p-4">
                                    <button onclick="openCeritaModal('<?= htmlspecialchars(addslashes($c['nama'])) ?>', `<?= htmlspecialchars(addslashes($c['cerita'])) ?>`)" class="px-3 py-1.5 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 border border-purple-500/20 text-[11px] font-semibold flex items-center gap-1.5">
                                        <i class="fa fa-book-open"></i> Cerita Calon
                                    </button>
                                </td>

                                <!-- CV Link -->
                                <td class="p-4">
                                    <?php if (!empty($c['cv_path'])): ?>
                                        <a href="../../daftar_osis/<?= htmlspecialchars($c['cv_path']) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/20 text-[11px] font-semibold">
                                            <i class="fa fa-file-pdf"></i> Unduh CV
                                        </a>
                                    <?php else: ?>
                                        <span class="text-white/40 text-[11px]">- Belum ada -</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Date -->
                                <td class="p-4 text-white/40 text-[11px] whitespace-nowrap">
                                    <?= date('d M Y, H:i', strtotime($c['tanggal_daftar'])) ?>
                                </td>

                                <!-- Action -->
                                <td class="p-4 text-right">
                                    <form method="POST" onsubmit="return confirm('Hapus calon ini dari pendaftaran?')">
                                        <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="p-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20" title="Hapus">
                                            <i class="fa fa-trash-can"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="p-8 text-center text-white/40">
                                Belum ada berkas pendaftaran calon OSIS.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Visi & Misi -->
<div id="modal-visimisi" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4" onclick="closeVisiModal()">
    <div class="admin-glass-card max-w-lg w-full p-6 relative" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-white/10">
            <h3 class="font-bold text-white text-base">Visi & Misi: <span id="visimisi-nama" class="text-blue-400"></span></h3>
            <button onclick="closeVisiModal()" class="text-white/40 hover:text-white">&times;</button>
        </div>
        <div class="p-4 rounded-xl bg-black/30 border border-white/5 text-white/80 text-xs leading-relaxed whitespace-pre-line max-h-96 overflow-y-auto" id="visimisi-content">
        </div>
    </div>
</div>

<!-- Modal: Cerita & Motivasi -->
<div id="modal-cerita" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4" onclick="closeCeritaModal()">
    <div class="admin-glass-card max-w-lg w-full p-6 relative" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-white/10">
            <h3 class="font-bold text-white text-base">Motivasi & Cerita: <span id="cerita-nama" class="text-purple-400"></span></h3>
            <button onclick="closeCeritaModal()" class="text-white/40 hover:text-white">&times;</button>
        </div>
        <div class="p-4 rounded-xl bg-black/30 border border-white/5 text-white/80 text-xs leading-relaxed whitespace-pre-line max-h-96 overflow-y-auto" id="cerita-content">
        </div>
    </div>
</div>

<script>
    function openVisiModal(nama, content) {
        document.getElementById('visimisi-nama').textContent = nama;
        document.getElementById('visimisi-content').textContent = content || 'Tidak ada visi misi tercantum.';
        document.getElementById('modal-visimisi').classList.remove('hidden');
    }
    function closeVisiModal() {
        document.getElementById('modal-visimisi').classList.add('hidden');
    }

    function openCeritaModal(nama, content) {
        document.getElementById('cerita-nama').textContent = nama;
        document.getElementById('cerita-content').textContent = content || 'Tidak ada cerita motivasi tercantum.';
        document.getElementById('modal-cerita').classList.remove('hidden');
    }
    function closeCeritaModal() {
        document.getElementById('modal-cerita').classList.add('hidden');
    }
</script>
