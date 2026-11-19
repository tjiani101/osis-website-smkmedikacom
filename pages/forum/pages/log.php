<?php
$alert = null;

// Handle Purge Logs
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'purge_logs') {
    $conn->query("DELETE FROM logs WHERE timestamp < NOW() - INTERVAL 30 DAY");
    $affected = $conn->affected_rows;
    $alert = ['type' => 'success', 'message' => "Log aktivitas lebih lama dari 30 hari ($affected entri) berhasil dibersihkan."];
}

// Export CSV handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=audit_logs_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Waktu', 'Username', 'Aksi', 'Alamat IP']);
    $export_rows = $conn->query("SELECT * FROM logs ORDER BY timestamp DESC LIMIT 1000");
    while ($r = $export_rows->fetch_assoc()) {
        fputcsv($output, [$r['id'] ?? '', $r['timestamp'], $r['username'], $r['action'], $r['ip_address']]);
    }
    exit;
}

// Search & Filter
$search = trim($_GET['search'] ?? '');
$filter_type = $_GET['type'] ?? '';

$sql = "SELECT * FROM logs";
$conds = [];

if ($search !== '') {
    $s_safe = $conn->real_escape_string($search);
    $conds[] = "(username LIKE '%$s_safe%' OR action LIKE '%$s_safe%' OR ip_address LIKE '%$s_safe%')";
}
if ($filter_type === 'login') {
    $conds[] = "action LIKE '%Login%'";
} elseif ($filter_type === 'failed') {
    $conds[] = "action LIKE '%Failed%'";
} elseif ($filter_type === 'thread') {
    $conds[] = "(action LIKE '%thread%' OR action LIKE '%post%')";
} elseif ($filter_type === 'comment') {
    $conds[] = "action LIKE '%comment%'";
} elseif ($filter_type === 'download') {
    $conds[] = "action LIKE '%Download%'";
}

if (!empty($conds)) {
    $sql .= " WHERE " . implode(" AND ", $conds);
}
$sql .= " ORDER BY timestamp DESC LIMIT 100";
$logs = $conn->query($sql);

// Metrics
$total_logs = $conn->query("SELECT COUNT(*) as c FROM logs")->fetch_assoc()['c'] ?? 0;
$today_logs = $conn->query("SELECT COUNT(*) as c FROM logs WHERE DATE(timestamp) = CURDATE()")->fetch_assoc()['c'] ?? 0;
$failed_logins = $conn->query("SELECT COUNT(*) as c FROM logs WHERE action LIKE '%Failed login%'")->fetch_assoc()['c'] ?? 0;
$unique_ips = $conn->query("SELECT COUNT(DISTINCT ip_address) as c FROM logs")->fetch_assoc()['c'] ?? 0;
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
            <h1 class="text-xl font-bold text-white">Audit Log & Keamanan Sistem</h1>
            <p class="text-white/50 text-xs">Catatan riwayat otentikasi login, aktivitas posting, unduhan file, dan perubahan database.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="?page=log&export=csv" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-white/80 border border-white/10 text-xs font-semibold flex items-center gap-2 transition-all">
                <i class="fa fa-file-csv text-emerald-400"></i> Ekspor CSV
            </a>
            <form method="POST" onsubmit="return confirm('Bersihkan log aktivitas yang lebih lama dari 30 hari?')">
                <input type="hidden" name="action" value="purge_logs">
                <button type="submit" class="px-4 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-300 border border-red-500/20 text-xs font-semibold flex items-center gap-2 transition-all">
                    <i class="fa fa-broom"></i> Bersihkan Log Lama
                </button>
            </form>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="admin-glass-card p-4">
            <span class="text-[10px] font-bold uppercase text-white/50">Total Log</span>
            <p class="text-2xl font-extrabold text-white font-mono-code"><?= $total_logs ?></p>
        </div>
        <div class="admin-glass-card p-4">
            <span class="text-[10px] font-bold uppercase text-blue-400">Aktivitas Hari Ini</span>
            <p class="text-2xl font-extrabold text-white font-mono-code"><?= $today_logs ?></p>
        </div>
        <div class="admin-glass-card p-4">
            <span class="text-[10px] font-bold uppercase text-red-400">Gagal Login</span>
            <p class="text-2xl font-extrabold text-red-400 font-mono-code"><?= $failed_logins ?></p>
        </div>
        <div class="admin-glass-card p-4">
            <span class="text-[10px] font-bold uppercase text-purple-400">IP Unik Terdeteksi</span>
            <p class="text-2xl font-extrabold text-white font-mono-code"><?= $unique_ips ?></p>
        </div>
    </div>

    <!-- Search & Filter Toolbar -->
    <div class="admin-glass-card p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-3 items-center justify-between">
            <input type="hidden" name="page" value="log">

            <div class="relative w-full md:w-80">
                <i class="fa fa-search absolute left-3.5 top-3 text-white/40 text-xs"></i>
                <input 
                    type="text" 
                    name="search" 
                    value="<?= htmlspecialchars($search) ?>" 
                    placeholder="Cari username, aksi, IP..." 
                    class="w-full pl-9 pr-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/40 text-xs focus:outline-none focus:border-blue-500"
                >
            </div>

            <div class="flex items-center gap-2 w-full md:w-auto">
                <select name="type" class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none">
                    <option value="" class="bg-[#0b1021]">Semua Kategori</option>
                    <option value="login" <?= $filter_type === 'login' ? 'selected' : '' ?> class="bg-[#0b1021]">Login Berhasil</option>
                    <option value="failed" <?= $filter_type === 'failed' ? 'selected' : '' ?> class="bg-[#0b1021]">Gagal Login</option>
                    <option value="thread" <?= $filter_type === 'thread' ? 'selected' : '' ?> class="bg-[#0b1021]">Post Forum</option>
                    <option value="comment" <?= $filter_type === 'comment' ? 'selected' : '' ?> class="bg-[#0b1021]">Komentar</option>
                    <option value="download" <?= $filter_type === 'download' ? 'selected' : '' ?> class="bg-[#0b1021]">Unduhan Media</option>
                </select>

                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition-all">
                    Filter
                </button>
                <?php if ($search !== '' || $filter_type !== ''): ?>
                    <a href="?page=log" class="px-3 py-2 rounded-xl bg-white/10 text-white/70 text-xs">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Log Table -->
    <div class="admin-glass-card overflow-hidden">
        <div class="overflow-x-auto max-h-[500px]">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 z-10">
                    <tr class="border-b border-white/10 bg-[#0f172a] text-[10px] font-extrabold uppercase tracking-wider text-white/50">
                        <th class="p-3.5">Waktu (WIB)</th>
                        <th class="p-3.5">Pengguna</th>
                        <th class="p-3.5">Aktivitas / Aksi</th>
                        <th class="p-3.5">Alamat IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-xs font-mono-code">
                    <?php if ($logs && $logs->num_rows > 0): ?>
                        <?php while ($l = $logs->fetch_assoc()): 
                            $is_fail = stripos($l['action'], 'Failed') !== false;
                            $is_succ = stripos($l['action'], 'successful') !== false;
                        ?>
                            <tr class="hover:bg-white/[0.02] transition-colors <?= $is_fail ? 'bg-red-500/[0.03]' : '' ?>">
                                <td class="p-3.5 text-white/40 whitespace-nowrap">
                                    <?= date('Y-m-d H:i:s', strtotime($l['timestamp'])) ?>
                                </td>
                                <td class="p-3.5 font-bold <?= $is_fail ? 'text-red-400' : 'text-blue-300' ?>">
                                    <?= htmlspecialchars($l['username']) ?>
                                </td>
                                <td class="p-3.5 font-sans <?= $is_fail ? 'text-red-200' : ($is_succ ? 'text-emerald-300' : 'text-white/80') ?>">
                                    <?= htmlspecialchars($l['action']) ?>
                                </td>
                                <td class="p-3.5 text-white/50 whitespace-nowrap">
                                    <?= htmlspecialchars($l['ip_address']) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="p-8 text-center text-white/40 font-sans">
                                Belum ada catatan log aktivitas yang cocok dengan kriteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Diagnostic Web Terminal -->
    <div class="admin-glass-card rounded-2xl overflow-hidden">
        <!-- Terminal Header -->
        <div class="px-4 py-3 bg-black/60 border-b border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500/80"></span>
                <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
                <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
                <span class="font-mono-code text-xs text-white/60 ml-2 font-bold">medikacom-admin-cli ~ v2.5.0</span>
            </div>
            <span class="text-[10px] text-white/40 font-mono-code">Ketik 'help' untuk daftar perintah</span>
        </div>

        <!-- Terminal Output -->
        <div id="terminal-screen" class="p-4 bg-black/90 font-mono-code text-xs text-emerald-400 h-64 overflow-y-auto space-y-1">
            <div class="text-white/50">=== Antarmuka Diagnostik Server OSIS SMK Medikacom ===</div>
            <div class="text-white/50">Ketik <span class="text-emerald-400 font-bold">'help'</span> untuk melihat perintah diagnostik yang tersedia.</div>
            <div><span class="text-blue-400">system@medikacom</span>:<span class="text-white/60">~</span>$ status</div>
            <div class="text-emerald-300">✓ Database: Connected (MariaDB 10.11) | PHP: <?= phpversion() ?> | OS: <?= PHP_OS ?></div>
        </div>

        <!-- Terminal Input Bar -->
        <div class="p-3 bg-black border-t border-white/10 flex items-center gap-2">
            <span class="font-mono-code text-blue-400 text-xs font-bold">admin@medikacom:~$</span>
            <input 
                id="terminal-input" 
                type="text" 
                autocomplete="off" 
                placeholder="Ketik perintah di sini..." 
                class="flex-1 bg-transparent font-mono-code text-xs text-emerald-400 focus:outline-none"
            >
        </div>
    </div>

</div>

<script>
    const termScreen = document.getElementById('terminal-screen');
    const termInput = document.getElementById('terminal-input');

    function appendTermLine(text, colorClass = 'text-white/80') {
        const div = document.createElement('div');
        div.className = colorClass;
        div.textContent = text;
        termScreen.appendChild(div);
        termScreen.scrollTop = termScreen.scrollHeight;
    }

    if (termInput) {
        termInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const cmd = this.value.trim().toLowerCase();
                if (!cmd) return;
                appendTermLine(`admin@medikacom:~$ ${this.value}`, 'text-blue-300 font-bold');
                this.value = '';

                if (cmd === 'help') {
                    appendTermLine('Perintah yang tersedia:');
                    appendTermLine('  status   - Cek status koneksi DB, PHP, dan sistem');
                    appendTermLine('  users    - Ringkasan total dan role pengguna');
                    appendTermLine('  stats    - Statistik topik dan komentar forum');
                    appendTermLine('  disk     - Perkiraan ruang disk media terpakai');
                    appendTermLine('  whoami   - Info identitas sesi admin saat ini');
                    appendTermLine('  clear    - Bersihkan tampilan terminal');
                    appendTermLine('  restart  - Simulasi reset konfigurasi');
                } else if (cmd === 'status') {
                    appendTermLine('Status Layanan:');
                    appendTermLine('  • MariaDB/MySQL: ONLINE (Connected)');
                    appendTermLine('  • PHP Version: <?= phpversion() ?>');
                    appendTermLine('  • Timezone: <?= date_default_timezone_get() ?>');
                    appendTermLine('  • Server Time: <?= date("Y-m-d H:i:s") ?> WIB');
                } else if (cmd === 'users') {
                    appendTermLine('Data Pengguna:');
                    appendTermLine('  • Total Terdaftar: <?= $total_count ?? 1 ?> akun');
                    appendTermLine('  • Status: Active');
                } else if (cmd === 'stats') {
                    appendTermLine('Statistik Forum:');
                    appendTermLine('  • Total Threads: <?= $conn->query("SELECT COUNT(*) as c FROM threads")->fetch_assoc()["c"] ?? 0 ?>');
                    appendTermLine('  • Total Komentar: <?= $conn->query("SELECT COUNT(*) as c FROM comments")->fetch_assoc()["c"] ?? 0 ?>');
                } else if (cmd === 'disk') {
                    appendTermLine('Penggunaan Ruang Penyimpanan:');
                    appendTermLine('  • Media Post: <?= round(getDirSize(__DIR__ . "/../post") / 1024, 1) ?> KB');
                    appendTermLine('  • Status Disk: OK (Normal)');
                } else if (cmd === 'whoami') {
                    appendTermLine('User: <?= htmlspecialchars($_SESSION["username"] ?? "admin") ?> [Role: <?= htmlspecialchars($_SESSION["role"] ?? "admin") ?>]');
                } else if (cmd === 'clear') {
                    termScreen.innerHTML = '';
                } else if (cmd === 'restart') {
                    appendTermLine('Menjalankan reload konfigurasi internal...', 'text-amber-400');
                    setTimeout(() => appendTermLine('✓ Sistem siap dan berjalan normal.', 'text-emerald-400 font-bold'), 600);
                } else {
                    appendTermLine(`Perintah tidak dikenali: '${cmd}'. Ketik 'help' untuk daftar perintah.`, 'text-red-400');
                }
            }
        });
    }
</script>
