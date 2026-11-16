<?php
// site/album.php
$album = isset($_GET['album']) ? basename($_GET['album']) : '';
$albumPath = __DIR__ . '/../upload/' . $album;

if ($album === '' || !is_dir($albumPath)) {
    die('<div class="text-center text-white/50 py-20">Album tidak ditemukan.</div>');
}

$photos = [];
$allowed = ['jpg', 'jpeg', 'png', 'webp'];
foreach (scandir($albumPath) as $file) {
    if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowed) && $file !== 'thumbnail.webp') {
        $photos[] = $file;
    }
}
?>

<section class="max-w-7xl mx-auto px-5 w-full mb-10 animate-card">
    <div class="liquid-glass rounded-[32px] p-6 sm:p-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-64 h-64 bg-blue-500/20 blur-3xl rounded-full -z-10"></div>
        <div>
            <h1 class="text-3xl sm:text-5xl font-black text-white mb-2"><?= htmlspecialchars($album) ?></h1>
            <p class="text-white/60 text-sm flex items-center gap-2"><i class="ph-fill ph-images"></i> Total <?= count($photos) ?> Foto High-Res</p>
        </div>
        <a href="?beranda" class="px-6 py-3 rounded-full bg-white/10 hover:bg-white/20 text-white font-semibold transition border border-white/10 flex items-center gap-2 backdrop-blur-md">
            <i class="ph ph-arrow-left"></i> Kembali
        </a>
    </div>
</section>

<section class="max-w-7xl mx-auto px-5 w-full columns-1 sm:columns-2 lg:columns-3 xl:columns-4 gap-6 space-y-6">
    <?php foreach ($photos as $photo): ?>
        <div class="bento-box animate-card liquid-glass rounded-[24px] overflow-hidden break-inside-avoid relative group">
            <!-- Panggil image.php untuk request thumbnail otomatis ringan -->
            <img src="site/image.php?album=<?= urlencode($album) ?>&photo=<?= urlencode($photo) ?>&type=thumb" 
                 alt="Foto" class="w-full h-auto object-cover opacity-90 group-hover:opacity-100 transition duration-500" loading="lazy">
            
            <!-- Overlay Actions -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-5">
                <div class="flex items-center justify-between">
                    <button class="btn-like w-10 h-10 rounded-full bg-white/10 hover:bg-white/30 backdrop-blur-md flex items-center justify-center text-white transition border border-white/10" 
                            data-album="<?= htmlspecialchars($album) ?>" data-photo="<?= htmlspecialchars($photo) ?>">
                        <i class="ph ph-heart text-lg"></i>
                    </button>
                    <div class="flex gap-2">
                        <!-- Preview nembak ke image.php type=preview -->
                        <button class="btn-preview w-10 h-10 rounded-full bg-white/10 hover:bg-blue-500/50 backdrop-blur-md flex items-center justify-center text-white transition border border-white/10" 
                                data-src="site/image.php?album=<?= urlencode($album) ?>&photo=<?= urlencode($photo) ?>&type=preview">
                            <i class="ph ph-eye text-lg"></i>
                        </button>
                        <!-- Download nembak langsung ke file original puluhan MB -->
                        <a href="upload/<?= rawurlencode($album) ?>/<?= rawurlencode($photo) ?>" download 
                           class="w-10 h-10 rounded-full bg-white/10 hover:bg-green-500/50 backdrop-blur-md flex items-center justify-center text-white transition border border-white/10">
                            <i class="ph ph-download-simple text-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</section>