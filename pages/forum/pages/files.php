<style>

.file-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    justify-content: center;
}

.file-item {
    background-color: #1f2a3a;
    padding: 10px;
    border-radius: 8px;
    text-align: center;
    max-width: 340px;
}

.thumb {
    max-width: 100%;
    max-height: 500px;
    border-radius: 5px;
}


</style>
<div class="card">
    <h2>File Postingan (Gambar/Video)</h2>
    <div class="file-grid">
        <?php
        $dir = 'post/';
        $files = array_diff(scandir($dir), array('.', '..'));

        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $path = $dir . $file;
            if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif'])) {
                echo "<div class='file-item'>
                        <img src='$path' alt='$file' class='thumb'>
                        <p>$file</p>
                      </div>";
            } elseif (in_array(strtolower($ext), ['mp4', 'webm'])) {
                echo "<div class='file-item'>
                        <video src='$path' controls class='thumb'></video>
                        <p>$file</p>
                      </div>";
            }
        }
        ?>
    </div>
</div>
