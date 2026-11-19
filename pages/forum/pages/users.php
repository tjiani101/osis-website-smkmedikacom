<?php
// Handle form actions
$alert = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ADD NEW USER
    if ($action === 'create_user') {
        $username = trim($_POST['username'] ?? '');
        $nis = intval($_POST['nis'] ?? 0);
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'murid';
        $tier = $_POST['tier'] ?? 'Visitor';

        if (empty($username) || empty($password)) {
            $alert = ['type' => 'error', 'message' => 'Username dan password wajib diisi.'];
        } else {
            // Check if username already exists
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->bind_param("s", $username);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $alert = ['type' => 'error', 'message' => 'Username sudah terdaftar, gunakan username lain.'];
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, nis, password_hash, role, status, joined_at, avatar, tier) VALUES (?, ?, ?, ?, 'active', NOW(), 'default-avatar.jpg', ?)");
                $stmt->bind_param("sisss", $username, $nis, $hash, $role, $tier);
                if ($stmt->execute()) {
                    $alert = ['type' => 'success', 'message' => "Pengguna '$username' berhasil ditambahkan!"];
                } else {
                    $alert = ['type' => 'error', 'message' => 'Gagal menambahkan pengguna: ' . $stmt->error];
                }
            }
        }
    }

    // 2. TOGGLE STATUS (BAN / UNBAN)
    elseif ($action === 'toggle_status') {
        $user_id = intval($_POST['user_id'] ?? 0);
        // Don't ban self
        if ($user_id === (int)$_SESSION['user_id']) {
            $alert = ['type' => 'error', 'message' => 'Anda tidak dapat memblokir akun Anda sendiri!'];
        } else {
            $stmt = $conn->prepare("UPDATE users SET status = IF(status='banned', 'active', 'banned') WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $alert = ['type' => 'success', 'message' => 'Status pengguna berhasil diperbarui.'];
        }
    }

    // 3. CHANGE ROLE
    elseif ($action === 'change_role') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $new_role = $_POST['role'] ?? 'murid';
        $allowed = ['murid', 'osis', 'guru', 'admin', 'dev'];

        if (in_array($new_role, $allowed)) {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $new_role, $user_id);
            $stmt->execute();
            $alert = ['type' => 'success', 'message' => "Role pengguna berhasil diubah menjadi '$new_role'."];
        }
    }

    // 4. RESET PASSWORD
    elseif ($action === 'reset_password') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $new_pass = trim($_POST['new_password'] ?? '');

        if (strlen($new_pass) < 6) {
            $alert = ['type' => 'error', 'message' => 'Password baru minimal 6 karakter.'];
        } else {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $hash, $user_id);
            $stmt->execute();
            $alert = ['type' => 'success', 'message' => 'Password pengguna berhasil direset!'];
        }
    }

    // 5. DELETE USER POSTS
    elseif ($action === 'delete_posts') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM threads WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $alert = ['type' => 'success', 'message' => 'Seluruh postingan pengguna berhasil dihapus.'];
    }

    // 6. DELETE USER ACCOUNT
    elseif ($action === 'delete_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id === (int)$_SESSION['user_id']) {
            $alert = ['type' => 'error', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri!'];
        } else {
            // Delete related child rows first
            $conn->query("DELETE FROM comments WHERE user_id = $user_id");
            $conn->query("DELETE FROM votes WHERE user_id = $user_id");
            $conn->query("DELETE FROM notifications WHERE user_id = $user_id OR actor_id = $user_id");
            $conn->query("DELETE FROM threads WHERE user_id = $user_id");
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $alert = ['type' => 'success', 'message' => 'Akun pengguna berhasil dihapus permanen.'];
        }
    }

    // 7. APPROVE SUB-FORUM BAN REQUEST
    elseif ($action === 'approve_ban_request') {
        $req_id = intval($_POST['request_id'] ?? 0);
        $target_id = intval($_POST['target_user_id'] ?? 0);
        $admin_uid = intval($_SESSION['user_id']);

        $conn->query("UPDATE users SET status = 'banned' WHERE id = $target_id");
        $conn->query("UPDATE subforum_ban_requests SET status = 'approved', reviewed_by = $admin_uid, reviewed_at = NOW() WHERE id = $req_id");
        $alert = ['type' => 'success', 'message' => 'Pengajuan ban disetujui. Akun pengguna berhasil diblokir.'];
    }

    // 8. REJECT SUB-FORUM BAN REQUEST
    elseif ($action === 'reject_ban_request') {
        $req_id = intval($_POST['request_id'] ?? 0);
        $admin_uid = intval($_SESSION['user_id']);

        $conn->query("UPDATE subforum_ban_requests SET status = 'rejected', reviewed_by = $admin_uid, reviewed_at = NOW() WHERE id = $req_id");
        $alert = ['type' => 'info', 'message' => 'Pengajuan ban ditolak.'];
    }
}

// Filters & Search
$search = trim($_GET['search'] ?? '');
$filter_role = $_GET['role'] ?? '';
$filter_status = $_GET['status'] ?? '';

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(username LIKE ? OR nis LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}
if ($filter_role !== '') {
    $where[] = "role = ?";
    $params[] = $filter_role;
    $types .= 's';
}
if ($filter_status !== '') {
    $where[] = "status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM threads t WHERE t.user_id = u.id) as thread_count,
        (SELECT COUNT(*) FROM comments c WHERE c.user_id = u.id) as comment_count
        FROM users u";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY u.joined_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users_list = $stmt->get_result();

// Counters
$cnt_total = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0;
$cnt_murid = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'murid'")->fetch_assoc()['c'] ?? 0;
$cnt_osis  = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'osis'")->fetch_assoc()['c'] ?? 0;
$cnt_guru  = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'guru'")->fetch_assoc()['c'] ?? 0;
$cnt_admin = $conn->query("SELECT COUNT(*) as c FROM users WHERE role IN ('admin', 'dev')")->fetch_assoc()['c'] ?? 0;
$cnt_ban   = $conn->query("SELECT COUNT(*) as c FROM users WHERE status = 'banned'")->fetch_assoc()['c'] ?? 0;

// Pending Sub-forum Ban Requests
$pending_ban_requests = $conn->query("
    SELECT sbr.*, f.nama as forum_name, f.slug as forum_slug, 
           u_req.username as requester_name, u_target.username as target_name, u_target.avatar as target_avatar
    FROM subforum_ban_requests sbr
    JOIN forum f ON sbr.forum_id = f.id
    JOIN users u_req ON sbr.requested_by = u_req.id
    JOIN users u_target ON sbr.target_user_id = u_target.id
    WHERE sbr.status = 'pending'
    ORDER BY sbr.created_at DESC
");
?>

<div class="space-y-6">

    <!-- Alert Notification -->
    <?php if ($alert): ?>
        <div class="p-4 rounded-2xl flex items-center justify-between <?= $alert['type'] === 'success' ? 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-200' : 'bg-red-500/15 border border-red-500/30 text-red-200' ?>">
            <div class="flex items-center gap-3">
                <i class="fa <?= $alert['type'] === 'success' ? 'fa-circle-check text-emerald-400' : 'fa-triangle-exclamation text-red-400' ?> text-lg"></i>
                <span class="text-xs font-semibold"><?= htmlspecialchars($alert['message']) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-white/40 hover:text-white text-xs">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-white">Kelola Data Pengguna</h1>
            <p class="text-white/50 text-xs">Atur hak akses akun siswa, guru, panitia OSIS, dan administrator.</p>
        </div>
        <button onclick="toggleModal('modal-add-user')" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/25 flex items-center gap-2 transition-all">
            <i class="fa fa-user-plus"></i>
            <span>Tambah Pengguna</span>
        </button>
    </div>

    <!-- PENDING SUB-FORUM BAN REQUESTS (VERIFIKASI ADMIN/DEV) -->
    <?php if ($pending_ban_requests && $pending_ban_requests->num_rows > 0): ?>
        <div class="p-4 sm:p-5 rounded-2xl bg-red-500/10 border border-red-500/30 backdrop-blur-xl">
            <div class="flex items-center gap-2 text-red-400 font-extrabold text-sm mb-3">
                <i class="fa fa-user-shield text-base"></i>
                <span>Verifikasi Pengajuan Ban dari Pemilik Sub-Forum (<?= $pending_ban_requests->num_rows ?> Antrean)</span>
            </div>
            <div class="space-y-3">
                <?php while ($req = $pending_ban_requests->fetch_assoc()): ?>
                    <div class="p-3.5 rounded-xl bg-white/5 border border-white/10 flex flex-col md:flex-row items-start md:items-center justify-between gap-3 text-xs">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span class="px-2 py-0.5 rounded bg-blue-500/20 text-blue-300 font-bold font-mono">sf/<?= htmlspecialchars($req['forum_slug']) ?></span>
                                <span class="text-white/50">Diajukan oleh:</span>
                                <strong class="text-white font-semibold"><?= htmlspecialchars($req['requester_name']) ?></strong>
                                <span class="text-white/40">•</span>
                                <span class="text-white/40 text-[11px]"><?= date('d M Y, H:i', strtotime($req['created_at'])) ?></span>
                            </div>
                            <p class="text-white/80">
                                Target yang dilaporkan: <strong class="text-red-400 font-bold"><?= htmlspecialchars($req['target_name']) ?></strong>
                            </p>
                            <p class="text-white/60 text-[11px] mt-1 bg-black/20 p-2 rounded-lg border border-white/5 italic">
                                "<?= nl2br(htmlspecialchars($req['reason'])) ?>"
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 w-full md:w-auto">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="approve_ban_request">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="target_user_id" value="<?= $req['target_user_id'] ?>">
                                <button type="submit" onclick="return confirm('Setujui ban untuk <?= htmlspecialchars(addslashes($req['target_name'])) ?>? Akun akan diblokir.')" class="px-3.5 py-1.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-md transition-all">
                                    Setujui Ban
                                </button>
                            </form>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="reject_ban_request">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs transition-all">
                                    Tolak
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Stats Badges -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <a href="?page=users" class="admin-glass-card p-4 text-center hover:border-blue-500/30 transition-all">
            <p class="text-[10px] font-bold text-white/50 uppercase">Semua</p>
            <p class="text-xl font-extrabold text-white font-mono-code"><?= $cnt_total ?></p>
        </a>
        <a href="?page=users&role=murid" class="admin-glass-card p-4 text-center hover:border-blue-500/30 transition-all">
            <p class="text-[10px] font-bold text-blue-400 uppercase">Murid</p>
            <p class="text-xl font-extrabold text-white font-mono-code"><?= $cnt_murid ?></p>
        </a>
        <a href="?page=users&role=osis" class="admin-glass-card p-4 text-center hover:border-blue-500/30 transition-all">
            <p class="text-[10px] font-bold text-indigo-400 uppercase">OSIS</p>
            <p class="text-xl font-extrabold text-white font-mono-code"><?= $cnt_osis ?></p>
        </a>
        <a href="?page=users&role=guru" class="admin-glass-card p-4 text-center hover:border-blue-500/30 transition-all">
            <p class="text-[10px] font-bold text-amber-400 uppercase">Guru</p>
            <p class="text-xl font-extrabold text-white font-mono-code"><?= $cnt_guru ?></p>
        </a>
        <a href="?page=users&role=admin" class="admin-glass-card p-4 text-center hover:border-blue-500/30 transition-all">
            <p class="text-[10px] font-bold text-purple-400 uppercase">Admin / Dev</p>
            <p class="text-xl font-extrabold text-white font-mono-code"><?= $cnt_admin ?></p>
        </a>
        <a href="?page=users&status=banned" class="admin-glass-card p-4 text-center hover:border-red-500/30 transition-all">
            <p class="text-[10px] font-bold text-red-400 uppercase">Banned</p>
            <p class="text-xl font-extrabold text-red-400 font-mono-code"><?= $cnt_ban ?></p>
        </a>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="admin-glass-card p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-3 items-center justify-between">
            <input type="hidden" name="page" value="users">
            
            <div class="relative w-full md:w-80">
                <i class="fa fa-search absolute left-3.5 top-3 text-white/40 text-xs"></i>
                <input 
                    type="text" 
                    name="search" 
                    value="<?= htmlspecialchars($search) ?>" 
                    placeholder="Cari nama pengguna atau NIS..." 
                    class="w-full pl-9 pr-4 py-2 rounded-xl bg-white/5 border border-white/10 text-white placeholder-white/40 text-xs focus:outline-none focus:border-blue-500/50"
                >
            </div>

            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                <select name="role" class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none">
                    <option value="" class="bg-[#0b1021]">Semua Role</option>
                    <option value="murid" <?= $filter_role === 'murid' ? 'selected' : '' ?> class="bg-[#0b1021]">Murid</option>
                    <option value="osis" <?= $filter_role === 'osis' ? 'selected' : '' ?> class="bg-[#0b1021]">OSIS</option>
                    <option value="guru" <?= $filter_role === 'guru' ? 'selected' : '' ?> class="bg-[#0b1021]">Guru</option>
                    <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?> class="bg-[#0b1021]">Admin</option>
                </select>

                <select name="status" class="px-3 py-2 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none">
                    <option value="" class="bg-[#0b1021]">Semua Status</option>
                    <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?> class="bg-[#0b1021]">Active</option>
                    <option value="banned" <?= $filter_status === 'banned' ? 'selected' : '' ?> class="bg-[#0b1021]">Banned</option>
                </select>

                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition-all">
                    Filter
                </button>
                <?php if ($search !== '' || $filter_role !== '' || $filter_status !== ''): ?>
                    <a href="?page=users" class="px-3 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-white/70 text-xs">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="admin-glass-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-white/10 bg-white/[0.02] text-[10px] font-extrabold uppercase tracking-wider text-white/50">
                        <th class="p-4">Pengguna</th>
                        <th class="p-4">NIS</th>
                        <th class="p-4">Role</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Tier Galeri</th>
                        <th class="p-4">Aktivitas</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-xs">
                    <?php if ($users_list && $users_list->num_rows > 0): ?>
                        <?php while ($u = $users_list->fetch_assoc()): ?>
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <!-- User & Avatar -->
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-blue-500/20 border border-blue-400/30 flex items-center justify-center font-bold text-xs text-blue-300 shrink-0 overflow-hidden">
                                            <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-white"><?= htmlspecialchars($u['username']) ?></p>
                                            <p class="text-[10px] text-white/40">Gabung: <?= date('d M Y', strtotime($u['joined_at'])) ?></p>
                                        </div>
                                    </div>
                                </td>

                                <!-- NIS -->
                                <td class="p-4 font-mono-code text-white/70">
                                    <?= htmlspecialchars($u['nis'] ?: '-') ?>
                                </td>

                                <!-- Role Badge -->
                                <td class="p-4">
                                    <?php
                                    $role_colors = [
                                        'admin' => 'bg-purple-500/15 text-purple-300 border-purple-500/30',
                                        'dev' => 'bg-pink-500/15 text-pink-300 border-pink-500/30',
                                        'guru' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
                                        'osis' => 'bg-blue-500/15 text-blue-300 border-blue-500/30',
                                        'murid' => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
                                    ];
                                    $col = $role_colors[$u['role']] ?? 'bg-white/10 text-white border-white/20';
                                    ?>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border <?= $col ?>">
                                        <?= strtoupper($u['role']) ?>
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="p-4">
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-red-500/15 text-red-300 border border-red-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> Banned
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Tier -->
                                <td class="p-4">
                                    <span class="text-[11px] font-medium text-white/70">
                                        <?= htmlspecialchars($u['tier'] ?? 'Visitor') ?>
                                    </span>
                                </td>

                                <!-- Activity -->
                                <td class="p-4 text-white/50 text-[11px]">
                                    <span><?= $u['thread_count'] ?> Topik</span> • 
                                    <span><?= $u['comment_count'] ?> Komen</span>
                                </td>

                                <!-- Actions -->
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Ban/Unban Form -->
                                        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status aktif/ban akun ini?')">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="p-2 rounded-xl text-xs <?= $u['status'] === 'banned' ? 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/20' ?>" title="<?= $u['status'] === 'banned' ? 'Aktifkan Akun' : 'Ban Akun' ?>">
                                                <i class="fa <?= $u['status'] === 'banned' ? 'fa-check' : 'fa-ban' ?>"></i>
                                            </button>
                                        </form>

                                        <!-- Quick Edit Role Modal Trigger -->
                                        <button onclick="openRoleModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>', '<?= $u['role'] ?>')" class="p-2 rounded-xl bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 border border-blue-500/20" title="Ganti Role">
                                            <i class="fa fa-user-tag"></i>
                                        </button>

                                        <!-- Reset Password Modal Trigger -->
                                        <button onclick="openResetPasswordModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')" class="p-2 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 text-purple-400 border border-purple-500/20" title="Reset Password">
                                            <i class="fa fa-key"></i>
                                        </button>

                                        <!-- Delete Account Form -->
                                        <form method="POST" onsubmit="return confirm('PERINGATAN: Akun ini dan seluruh riwayat postingannya akan dihapus permanen. Lanjutkan?')">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="p-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20" title="Hapus Permanen">
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
                                Tidak ada data pengguna yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Tambah User Baru -->
<div id="modal-add-user" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="admin-glass-card max-w-md w-full p-6 relative">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-white/10">
            <h3 class="font-bold text-white text-base">Tambah Pengguna Baru</h3>
            <button onclick="toggleModal('modal-add-user')" class="text-white/40 hover:text-white">&times;</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="create_user">
            <div>
                <label class="block text-xs font-semibold text-white/70 mb-1">Username / Email *</label>
                <input type="text" name="username" required placeholder="misal: ahmad.rizki" class="w-full px-3.5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-white/70 mb-1">NIS (Nomor Induk Siswa)</label>
                <input type="number" name="nis" placeholder="misal: 23241001" class="w-full px-3.5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-white/70 mb-1">Password Sementara *</label>
                <input type="password" name="password" required placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none focus:border-blue-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-white/70 mb-1">Role Akun</label>
                    <select name="role" class="w-full px-3.5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none">
                        <option value="murid" class="bg-[#0b1021]">Murid</option>
                        <option value="osis" class="bg-[#0b1021]">OSIS</option>
                        <option value="guru" class="bg-[#0b1021]">Guru</option>
                        <option value="admin" class="bg-[#0b1021]">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-white/70 mb-1">Tier Galeri</label>
                    <select name="tier" class="w-full px-3.5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none">
                        <option value="Visitor" class="bg-[#0b1021]">Visitor</option>
                        <option value="Member" class="bg-[#0b1021]">Member</option>
                        <option value="HD View" class="bg-[#0b1021]">HD View</option>
                        <option value="Contributor" class="bg-[#0b1021]">Contributor</option>
                        <option value="Elite" class="bg-[#0b1021]">Elite</option>
                        <option value="Legendary" class="bg-[#0b1021]">Legendary</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="toggleModal('modal-add-user')" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-white text-xs">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Ganti Role -->
<div id="modal-role" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="admin-glass-card max-w-sm w-full p-6 relative">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-white/10">
            <h3 class="font-bold text-white text-base">Ganti Role Pengguna</h3>
            <button onclick="toggleModal('modal-role')" class="text-white/40 hover:text-white">&times;</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="change_role">
            <input type="hidden" name="user_id" id="role-user-id">
            <p class="text-xs text-white/70">Ubah peran untuk: <strong id="role-username" class="text-white"></strong></p>
            <div>
                <select name="role" id="role-select" class="w-full px-3.5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none">
                    <option value="murid" class="bg-[#0b1021]">Murid</option>
                    <option value="osis" class="bg-[#0b1021]">OSIS</option>
                    <option value="guru" class="bg-[#0b1021]">Guru</option>
                    <option value="admin" class="bg-[#0b1021]">Admin</option>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="toggleModal('modal-role')" class="px-4 py-2 rounded-xl bg-white/10 text-white text-xs">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs">Perbarui Role</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reset Password -->
<div id="modal-reset-pw" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="admin-glass-card max-w-sm w-full p-6 relative">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-white/10">
            <h3 class="font-bold text-white text-base">Reset Password</h3>
            <button onclick="toggleModal('modal-reset-pw')" class="text-white/40 hover:text-white">&times;</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="reset-user-id">
            <p class="text-xs text-white/70">Atur password baru untuk: <strong id="reset-username" class="text-white"></strong></p>
            <div>
                <input type="password" name="new_password" required minlength="6" placeholder="Password baru (min. 6 karakter)" class="w-full px-3.5 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="toggleModal('modal-reset-pw')" class="px-4 py-2 rounded-xl bg-white/10 text-white text-xs">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs">Simpan Password Baru</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(id) {
        const m = document.getElementById(id);
        if (m) m.classList.toggle('hidden');
    }

    function openRoleModal(userId, username, currentRole) {
        document.getElementById('role-user-id').value = userId;
        document.getElementById('role-username').textContent = username;
        document.getElementById('role-select').value = currentRole;
        toggleModal('modal-role');
    }

    function openResetPasswordModal(userId, username) {
        document.getElementById('reset-user-id').value = userId;
        document.getElementById('reset-username').textContent = username;
        toggleModal('modal-reset-pw');
    }
</script>
