<?php
/**
 * Forum Reddit-Style Helper Functions
 * OSIS & MPK SMK Medikacom
 */

if (!function_exists('format_reputation')) {
    function format_reputation($rep) {
        $rep = (int)$rep;
        if ($rep >= 1000000) {
            return round($rep / 1000000, 1) . 'M';
        } elseif ($rep >= 1000) {
            return round($rep / 1000, 1) . 'k';
        }
        return (string)$rep;
    }
}

if (!function_exists('parse_reddit_markdown')) {
    function parse_reddit_markdown($text) {
        if (empty($text)) return '';
        // Escape HTML first to prevent XSS
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Bold: **text**
        $text = preg_replace('/\*\*(.*?)\*\*/s', '<strong class="font-bold text-white">$1</strong>', $text);
        
        // Italic: *text*
        $text = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/s', '<em class="italic text-white/90">$1</em>', $text);
        
        // Inline code: `code`
        $text = preg_replace('/`(.*?)`/s', '<code class="px-1.5 py-0.5 rounded bg-white/10 text-blue-300 font-mono text-[0.85em] border border-white/10">$1</code>', $text);
        
        // Quotes: &gt; quote
        $text = preg_replace('/^&gt;\s*(.*?)$/m', '<blockquote class="border-l-4 border-blue-400/60 pl-3 py-1 my-2 text-white/70 italic bg-white/[0.02] rounded-r">$1</blockquote>', $text);
        
        // Linebreaks
        $text = nl2br($text);

        return $text;
    }
}

if (!function_exists('render_avatar_with_frame')) {
    function render_avatar_with_frame($username, $avatar, $frame = null, $size = 'md', $extra_class = '') {
        $sizes = [
            'xs' => ['box' => 'w-6 h-6', 'text' => 'text-[9px]', 'border' => 'border'],
            'sm' => ['box' => 'w-8 h-8', 'text' => 'text-xs', 'border' => 'border'],
            'md' => ['box' => 'w-10 h-10', 'text' => 'text-sm', 'border' => 'border-2'],
            'lg' => ['box' => 'w-16 h-16', 'text' => 'text-xl', 'border' => 'border-2'],
            'xl' => ['box' => 'w-24 h-24 sm:w-28 sm:h-28', 'text' => 'text-3xl', 'border' => 'border-4'],
        ];

        $cfg = $sizes[$size] ?? $sizes['md'];
        $has_avatar = !empty($avatar) && $avatar !== 'default-avatar.jpg' && file_exists(__DIR__ . '/uploads/' . $avatar);
        $has_frame = !empty($frame) && file_exists(__DIR__ . '/assets/frames/' . $frame);

        $html = '<div class="relative inline-flex items-center justify-center shrink-0 ' . $cfg['box'] . ' ' . $extra_class . '">';
        
        // Avatar Inner Circle
        $html .= '<div class="w-full h-full rounded-full overflow-hidden bg-gradient-to-tr from-blue-700 to-indigo-900 ' . $cfg['border'] . ' border-white/20 flex items-center justify-center text-white font-extrabold shadow-sm">';
        if ($has_avatar) {
            $html .= '<img src="uploads/' . htmlspecialchars($avatar) . '" alt="' . htmlspecialchars($username) . '" class="w-full h-full object-cover select-none pointer-events-none">';
        } else {
            $html .= '<span class="' . $cfg['text'] . '">' . strtoupper(substr($username, 0, 1)) . '</span>';
        }
        $html .= '</div>';

        // Avatar Frame Overlay (Proportional centering)
        if ($has_frame) {
            $html .= '<img src="assets/frames/' . htmlspecialchars($frame) . '" alt="Frame" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[126%] h-[126%] max-w-none pointer-events-none select-none z-10" style="object-fit: contain;">';
        }

        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('render_user_flair')) {
    function render_user_flair($user_id, $username, $role = 'murid', $active_perk = null, $reputation = 0) {
        $html = '<span class="inline-flex items-center gap-1.5 flex-wrap">';
        
        // Username with hover trigger
        $html .= '<a href="profile.php?id=' . intval($user_id) . '" class="user-hover-trigger font-bold text-white hover:text-blue-300 transition-colors inline-flex items-center gap-1" data-user-id="' . intval($user_id) . '">';
        $html .= htmlspecialchars($username);
        $html .= '</a>';

        // Role badge if not standard murid
        if (!empty($role) && strtolower($role) !== 'murid') {
            $role_colors = [
                'admin' => 'bg-purple-500/20 text-purple-300 border-purple-500/40',
                'dev' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40',
                'osis' => 'bg-blue-500/20 text-blue-300 border-blue-500/40',
                'guru' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40'
            ];
            $r_class = $role_colors[strtolower($role)] ?? 'bg-white/10 text-white/80 border-white/20';
            $html .= '<span class="text-[9px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wider border ' . $r_class . '">';
            $html .= htmlspecialchars(strtoupper($role));
            $html .= '</span>';
        }

        // Active Perk Badge (1 can be shown next to name)
        if ($active_perk) {
            $perk_label = htmlspecialchars($active_perk['badge_label'] ?? $active_perk['name']);
            $perk_color = htmlspecialchars($active_perk['color_class'] ?? 'bg-white/10 text-white border-white/20');
            $html .= '<span class="text-[10px] px-2 py-0.5 rounded-full font-bold border ' . $perk_color . ' inline-flex items-center gap-1 shadow-sm" title="' . htmlspecialchars($active_perk['description'] ?? '') . '">';
            $html .= $perk_label;
            $html .= '</span>';
        }

        $html .= '</span>';
        return $html;
    }
}

if (!function_exists('award_milestones_if_eligible')) {
    function award_milestones_if_eligible($conn, $user_id) {
        $user_id = intval($user_id);
        if ($user_id <= 0) return;

        // 1. Pionir (first 50 registered users)
        if ($user_id <= 50) {
            $conn->query("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES ($user_id, 1)");
        }

        // 2. Influencer (50+ followers)
        $fc_res = $conn->query("SELECT followers_count FROM users WHERE id = $user_id");
        if ($fc_res && ($fc = $fc_res->fetch_assoc())) {
            if ($fc['followers_count'] >= 50) {
                $conn->query("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES ($user_id, 2)");
            }
        }

        // 3. Suara Vokal (10+ threads)
        $th_res = $conn->query("SELECT COUNT(*) AS total FROM threads WHERE user_id = $user_id");
        if ($th_res && ($th = $th_res->fetch_assoc())) {
            if ($th['total'] >= 10) {
                $conn->query("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES ($user_id, 3)");
            }
        }

        // 4. Sumpah Pemilih (20+ votes)
        $vt_res = $conn->query("SELECT COUNT(*) AS total FROM votes WHERE user_id = $user_id");
        if ($vt_res && ($vt = $vt_res->fetch_assoc())) {
            if ($vt['total'] >= 20) {
                $conn->query("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES ($user_id, 7)");
            }
        }
    }
}
