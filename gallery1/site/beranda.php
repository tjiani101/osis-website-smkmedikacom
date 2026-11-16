<?php
// site/beranda.php
$uploadDir = __DIR__ . '/../upload/';
$albums = [];

if (is_dir($uploadDir)) {
    foreach (scandir($uploadDir) as $folder) {
        if ($folder === '.' || $folder === '..') continue;
        if (is_dir($uploadDir . $folder) && file_exists($uploadDir . $folder . '/thumbnail.webp')) {
            // Hitung jumlah foto
            $files = scandir($uploadDir . $folder);
            $count = count(array_filter($files, fn($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp']))) - 1; // -1 for thumbnail
            
            $albums[] = [
                'name' => $folder,
                'count' => $count,
                'thumb' => 'upload/' . rawurlencode($folder) . '/thumbnail.webp'
            ];
        }
    }
}
?>

<section class="max-w-7xl mx-auto px-5 flex flex-col items-center text-center mt-4 mb-16 animate-card">
    <div class="inline-block px-5 py-2 rounded-full liquid-glass text-xs font-bold text-white/80 mb-6 uppercase tracking-widest shadow-lg">
        Arsip Dokumentasi
    </div>
    <h1 class="text-5xl md:text-7xl font-black tracking-tightest text-white mb-6 drop-shadow-2xl leading-[1.1]">
        Album <span class="text-transparent bg-clip-text bg-gradient-to-br from-purple-400 to-blue-400">Kegiatan.</span>
    </h1>
    <p class="text-lg text-white/70 max-w-2xl font-medium leading-relaxed">
        Jelajahi setiap momen berharga dari seluruh kegiatan dan program kerja OSIS SMK Medikacom.
    </p>
</section>

<section class="max-w-7xl mx-auto px-5 w-full">
    <?php if (empty($albums)): ?>
        <div class="liquid-glass rounded-3xl p-10 text-center text-white/50 animate-card">Belum ada album yang diunggah.</div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($albums as $album): ?>
                <a href="?album=<?= urlencode($album['name']) ?>" class="bento-box animate-card liquid-glass rounded-[32px] overflow-hidden group flex flex-col relative h-[300px]">
                    <div class="absolute inset-0 z-0 overflow-hidden">
                        <img src="<?= htmlspecialchars($album['thumb']) ?>" alt="Thumbnail" class="w-full h-full object-cover group-hover:scale-110 group-hover:rotate-1 transition-transform duration-700 opacity-60">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent"></div>
                    </div>
                    
                    <div class="relative z-10 mt-auto p-6 flex flex-col justify-end">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-3 py-1 rounded-full bg-blue-500/20 border border-blue-500/30 text-blue-300 text-[10px] font-bold uppercase flex items-center gap-1 backdrop-blur-md">
                                <i class="ph-fill ph-images"></i> <?= $album['count'] ?> Foto
                            </span>
                        </div>
                        <h2 class="text-2xl font-bold text-white leading-tight drop-shadow-md group-hover:text-blue-200 transition-colors">
                            <?= htmlspecialchars($album['name']) ?>
                        </h2>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>