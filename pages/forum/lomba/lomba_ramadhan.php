<?php
// Pastikan koneksi database tersedia
include 'lombadb.php'; 

$query = "SELECT * FROM lomba_content_creator ORDER BY waktu_submit DESC";
$result = $conn->query($query);
?>

<style>
    /* Styling Khusus Halaman Ini */
    .admin-header {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }
    
    /* Table Styling */
    .data-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        border-radius: 8px;
        overflow: hidden;
    }
    .data-table th, .data-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    .data-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #333;
    }
    .data-table tr:hover {
        background-color: #f1f5f9;
    }
    
    /* Buttons */
    .btn-detail {
        background-color: #3b82f6;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9em;
        transition: background 0.2s;
    }
    .btn-detail:hover {
        background-color: #2563eb;
    }

    /* MODAL POPUP STYLING */
    .modal-overlay {
        display: none; /* Hidden by default */
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.7);
        backdrop-filter: blur(4px);
    }

    .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 0;
        border-radius: 10px;
        width: 90%;
        max-width: 800px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        position: relative;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {transform: translateY(-50px); opacity: 0;}
        to {transform: translateY(0); opacity: 1;}
    }

    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
        border-radius: 10px 10px 0 0;
    }

    .modal-header h2 { margin: 0; font-size: 1.2rem; color: #333; }
    
    .close-modal {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.2s;
    }
    .close-modal:hover { color: #000; }

    .modal-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Grid layout untuk detail info */
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    .info-item {
        background: #f9fafb;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
    }
    .info-label { font-size: 0.8rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
    .info-value { font-size: 1rem; color: #111827; font-weight: 500; }

    /* Video Container Responsive */
    .video-container {
        position: relative;
        padding-bottom: 56.25%; /* 16:9 aspect ratio */
        height: 0;
        overflow: hidden;
        border-radius: 8px;
        background: #000;
        margin-top: 10px;
    }
    .video-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }
    
    .deskripsi-box {
        background: #fffbeb;
        border: 1px solid #fcd34d;
        color: #92400e;
        padding: 15px;
        border-radius: 6px;
        margin-top: 10px;
    }

</style>

<div class="admin-header">
    <h1><i class="fa fa-star" style="color: #fbbf24;"></i> Data Peserta Lomba Ramadhan</h1>
    <p>List peserta Content Creator Challenge. Klik tombol "Detail" untuk melihat video.</p>
</div>

<!-- DATA LIST TABLE -->
<div style="overflow-x:auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Nama Tim</th>
                <th width="15%">Kelas</th>
                <th width="25%">Judul Karya</th>
                <th width="15%">Ketua</th>
                <th width="10%">Tanggal</th>
                <th width="10%">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($result->num_rows > 0) {
                $no = 1;
                while($row = $result->fetch_assoc()) {
                    // Kita simpan semua data row ke dalam atribut data- HTML
                    // agar bisa diambil oleh JavaScript untuk Modal
                    $jsonData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
            ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td style="font-weight:bold; color:#2563eb;"><?php echo htmlspecialchars($row['nama_tim']); ?></td>
                    <td><span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; rounded:4px; font-size:0.85em; border-radius:4px;"><?php echo htmlspecialchars($row['kelas']); ?></span></td>
                    <td><?php echo htmlspecialchars($row['judul_karya']); ?></td>
                    <td><?php echo htmlspecialchars($row['ketua_tim']); ?></td>
                    <td style="font-size:0.85em; color:#666;"><?php echo date('d/m/Y', strtotime($row['waktu_submit'])); ?></td>
                    <td>
                        <button class="btn-detail" onclick="openModal('<?php echo $jsonData; ?>')">
                            <i class="fa fa-eye"></i> Detail
                        </button>
                    </td>
                </tr>
            <?php 
                }
            } else {
                echo "<tr><td colspan='7' style='text-align:center; padding:20px;'>Belum ada data peserta.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- MODAL POPUP (DATA FULL) -->
<div id="detailModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Detail Peserta</h2>
            <span class="close-modal" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            
            <!-- Informasi Utama -->
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nama Tim</div>
                    <div class="info-value" id="m_nama_tim">-</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Ketua Tim</div>
                    <div class="info-value" id="m_ketua_tim">-</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Kelas</div>
                    <div class="info-value" id="m_kelas">-</div>
                </div>
                <div class="info-item">
                    <div class="info-label">No WhatsApp</div>
                    <div class="info-value" id="m_no_wa">-</div>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Judul Karya</div>
                <div class="info-value" id="m_judul_karya">-</div>
            </div>

            <!-- Pesan / Deskripsi -->
            <div class="deskripsi-box">
                <strong>Pesan/Deskripsi:</strong><br>
                <span id="m_deskripsi" style="font-style: italic;">-</span>
            </div>

            <!-- Video Player -->
            <div>
                <div class="info-label" style="margin-bottom:5px;">Preview Video (YouTube):</div>
                <div class="video-container" id="videoWrapper">
                    <!-- Iframe akan digenerate oleh JS di sini -->
                    <p style="color:white; text-align:center; padding-top:20%;">Memuat Video...</p>
                </div>
                <div style="margin-top:5px; text-align:right;">
                    <a id="m_link_original" href="#" target="_blank" style="font-size:0.85rem; color:#3b82f6;">Buka link asli <i class="fa fa-external-link-alt"></i></a>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Fungsi Konversi Link YouTube ke Embed ID
    function getYoutubeId(url) {
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
        const match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : null;
    }

    // Fungsi Buka Modal
    function openModal(jsonString) {
        try {
            const data = JSON.parse(jsonString);
            
            // Isi Data Teks
            document.getElementById('m_nama_tim').innerText = data.nama_tim;
            document.getElementById('m_ketua_tim').innerText = data.ketua_tim;
            document.getElementById('m_kelas').innerText = data.kelas;
            document.getElementById('m_no_wa').innerText = data.no_wa; // Bisa ditambah link wa.me kalo mau
            document.getElementById('m_judul_karya').innerText = data.judul_karya;
            document.getElementById('m_deskripsi').innerText = data.deskripsi || "Tidak ada deskripsi.";
            document.getElementById('m_link_original').href = data.link_karya;

            // Logic Video Youtube
            const videoId = getYoutubeId(data.link_karya);
            const videoWrapper = document.getElementById('videoWrapper');

            if (videoId) {
                videoWrapper.innerHTML = `
                    <iframe 
                        src="https://www.youtube.com/embed/${videoId}?autoplay=1" 
                        title="YouTube video player" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>`;
            } else {
                videoWrapper.innerHTML = `
                    <div style="display:flex; justify-content:center; align-items:center; height:100%; color:#fff; flex-direction:column;">
                        <i class="fa fa-exclamation-triangle" style="font-size:3rem; margin-bottom:10px; color:#f59e0b;"></i>
                        <p>Format Link YouTube tidak dikenali.</p>
                        <p style="font-size:0.8rem; opacity:0.7;">${data.link_karya}</p>
                    </div>`;
            }

            // Tampilkan Modal
            document.getElementById('detailModal').style.display = "block";

        } catch (e) {
            console.error("Error parsing JSON data", e);
            alert("Terjadi kesalahan saat membuka data.");
        }
    }

    // Fungsi Tutup Modal
    function closeModal() {
        document.getElementById('detailModal').style.display = "none";
        // Stop video dengan menghapus iframe content saat ditutup
        document.getElementById('videoWrapper').innerHTML = ""; 
    }

    // Tutup jika klik di luar area modal content
    window.onclick = function(event) {
        const modal = document.getElementById('detailModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>