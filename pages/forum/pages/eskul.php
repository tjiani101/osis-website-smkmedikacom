<?php
$alert = null;

// Handle Delete
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM pendaftaran_eskul WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $alert = ['type' => 'success', 'message' => "Data pendaftaran #$del_id berhasil dihapus."];
    }
}

// Filters & Search
$search = trim($_GET['search'] ?? '');
$filter_eskul = $_GET['eskul'] ?? '';
$filter_jurusan = $_GET['jurusan'] ?? '';

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(nama LIKE ? OR email LIKE ? OR telp LIKE ?)";
    $sp = "%$search%";
    $params[] = $sp;
    $params[] = $sp;
    $params[] = $sp;
    $types .= 'sss';
}
if ($filter_eskul !== '') {
    $where[] = "eskul = ?";
    $params[] = $filter_eskul;
    $types .= 's';
}
if ($filter_jurusan !== '') {
    $where[] = "jurusan = ?";
    $params[] = $filter_jurusan;
    $types .= 's';
}

$query = "SELECT * FROM pendaftaran_eskul";
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}
$query .= " ORDER BY tanggal_daftar DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$list = $stmt->get_result();

// Distinct for filters
$eskul_options = $conn->query("SELECT DISTINCT eskul FROM pendaftaran_eskul WHERE eskul IS NOT NULL AND eskul != ''");
$jurusan_options = $conn->query("SELECT DISTINCT jurusan FROM pendaftaran_eskul WHERE jurusan IS NOT NULL AND jurusan != ''");

$total_count = $conn->query("SELECT COUNT(*) as c FROM pendaftaran_eskul")->fetch_assoc()['c'] ?? 0;
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
            <h1 class="text-xl font-bold text-white">Pendaftaran Ekstrakurikuler</h1>
            <p class="text-white/50 text-xs">Pantau minat dan bakat siswa yang mendaftar ke berbagai cabang eskul SMK Medikacom.</p>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-white/80 border border-white/10 text-xs font-semibold flex items-center gap-2 transition-all">
                <i class="fa fa-print"></i> Cetak Dokumen
            </button>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="admin-glass-card p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-3 items-center justify-between">
            <input type="hidden" name="page" value="eskul">

            <div class="relative w-full md:w-80">
                <i class="fa fa-search absolute left-3.5 top-3 text-white/40 text-xs"></i>
                <input 
                    type="text" 
                    name="search" 
                    value="<?= htmlspecialchars($search) ?>" 
                    placeholder="Cari nama, email, no HP..." 
                    class="w-full pl-9 pr-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/40 text-xs focus:outline-none focus:border-blue-500"
                >
            </div>

            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                <select name="eskul" class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none">
                    <option value="" class="bg-[#0b1021]">Semua Eskul</option>
                    <?php if ($eskul_options) while ($e = $eskul_options->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($e['eskul']) ?>" <?= $filter_eskul === $e['eskul'] ? 'selected' : '' ?> class="bg-[#0b1021]"><?= htmlspecialchars($e['eskul']) ?></option>
                    <?php endwhile; ?>
                </select>

                <select name="jurusan" class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none">
                    <option value="" class="bg-[#0b1021]">Semua Jurusan</option>
                    <?php if ($jurusan_options) while ($j = $jurusan_options->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($j['jurusan']) ?>" <?= $filter_jurusan === $j['jurusan'] ? 'selected' : '' ?> class="bg-[#0b1021]"><?= htmlspecialchars($j['jurusan']) ?></option>
                    <?php endwhile; ?>
                </select>

                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition-all">
                    Filter
                </button>
                <?php if ($search !== '' || $filter_eskul !== '' || $filter_jurusan !== ''): ?>
                    <a href="?page=eskul" class="px-3 py-2 rounded-xl bg-white/10 text-white/70 text-xs">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="admin-glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/10 bg-white/[0.02] text-[10px] font-extrabold uppercase tracking-wider text-white/50">
                        <th class="p-4">No</th>
                        <th class="p-4">Nama Lengkap</th>
                        <th class="p-4">Kontak (Email / Telp)</th>
                        <th class="p-4">Jurusan</th>
                        <th class="p-4">Pilihan Eskul</th>
                        <th class="p-4">Tanggal Daftar</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-xs">
                    <?php if ($list && $list->num_rows > 0): 
                        $no = 1;
                        while ($row = $list->fetch_assoc()):
                    ?>
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="p-4 font-mono-code text-white/40"><?= $no++ ?></td>
                            <td class="p-4 font-bold text-white"><?= htmlspecialchars($row['nama']) ?></td>
                            <td class="p-4">
                                <p class="text-white/80"><?= htmlspecialchars($row['email'] ?: '-') ?></p>
                                <p class="text-[11px] text-white/50"><?= htmlspecialchars($row['telp'] ?: '-') ?></p>
                            </td>
                            <td class="p-4 text-white/70"><?= htmlspecialchars($row['jurusan']) ?></td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-500/15 text-blue-300 border border-blue-500/30">
                                    ⚽ <?= htmlspecialchars($row['eskul']) ?>
                                </span>
                            </td>
                            <td class="p-4 text-white/40 text-[11px]">
                                <?= date('d M Y, H:i', strtotime($row['tanggal_daftar'])) ?>
                            </td>
                            <td class="p-4 text-right">
                                <form method="POST" onsubmit="return confirm('Hapus data pendaftaran ini?')">
                                    <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="p-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20" title="Hapus">
                                        <i class="fa fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-white/40">
                                Belum ada pendaftaran ekstrakurikuler yang sesuai filter.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
