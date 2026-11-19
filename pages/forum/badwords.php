<?php
function filter_bad_words($text) {
    $bad_words = [
        'anjing', 'anjir', 'goblok', 'tolol', 'bego', 'idiot', 'kampungan',
        'bangsat', 'brengsek', 'tai', 'sialan',
        'kontol', 'memek', 'titit', 'pelacur', 'lonte', 'jembut', 'ngentot',
        'coli', 'tete', 'tetek', 'payudara', 'bugil', 'bokep', 'porno', 'bispak', 'kimak',
        'kafir', 'cina', 'arab', 'hitam', 'jawa tolol', 'nonis', 'nasrani goblok',
        'bakar sekolah', 'hajar', 'tusuk', 'bunuh', 'ledakin', 'serang', 'tawuran', 'bantai',
        'sabu', 'ganja', 'inex', 'narkoba', 'ngelem', 'pil koplo', 'obat batuk', 'ngefly',
        'pinjaman cepat', 'slot gacor', 'judi online', 'kasino', 'klik link ini',
        'gratis saldo'
    ];

    foreach ($bad_words as $word) {
        $replacement = str_repeat('*', strlen($word));
        $text = preg_replace('/\b' . preg_quote($word, '/') . '\b/i', $replacement, $text);
    }

    return $text;
}
?>
