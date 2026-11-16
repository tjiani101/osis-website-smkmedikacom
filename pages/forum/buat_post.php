<?php
session_start();
include 'db.php';

// Logging function
function simpanLog($conn, $username, $action) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $timestamp = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO logs (timestamp, username, action, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $timestamp, $username, $action, $ip);
    $stmt->execute();
}

if (!isset($_SESSION['user_id'])) {
    // Log unauthorized access attempt
    simpanLog($conn, 'Guest', 'Unauthorized post creation attempt');
    header("Location: login.php");
    exit;
}

// Log post creation page access
simpanLog($conn, $_SESSION['username'], 'Create post creation page');

$forum_result = $conn->query("SELECT * FROM forum ORDER BY nama ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Buat Post - Forum Medikacom</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Mulish', sans-serif;
            background-color: #0f172a; /* slate-950 */
            color: #ebf0ff;
            line-height: 1.5;
        }
        .container {
            width: 100%;
            padding: 15px;
            margin: 0 auto;
            max-width: 800px;
        }
        .thread {
            background: #0f172a;
            padding: 25px;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .thread::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(to right, #0f172a, #3b82f6, #0f172a);
            z-index: -1;
        }
        h2 {
            margin-top: 0;
            font-size: 1.8rem;
            background: linear-gradient(to right, #3b82f6, #22d3ee);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 25px;
            font-weight: 800;
        }
        label {
            display: block;
            margin: 15px 0 8px;
            font-weight: 600;
            font-size: 1rem;
            color: #b8c1e0;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 14px;
            border: 1px solid #334155;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            background-color: #1e293b;
            color: #ebf0ff;
            transition: all 0.3s;
        }
        input[type="text"]:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        textarea {
            min-height: 180px;
            resize: vertical;
        }
        .btn-container {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        .btn {
            background: linear-gradient(to right, #2563eb, #0ea5e9);
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
            flex: 1;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.4);
        }
        .btn-cancel {
            background: linear-gradient(to right, #475569, #64748b);
        }
        .btn-cancel:hover {
            background: linear-gradient(to right, #334155, #475569);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(71, 85, 105, 0.4);
        }
        .media-type {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        .media-type label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            cursor: pointer;
            font-weight: normal;
            padding: 8px 15px;
            background: #1e293b;
            border-radius: 6px;
        }
        .file-info {
            margin-top: 8px;
            font-size: 0.9rem;
            color: #94a3b8;
        }
        input[type="file"] {
            width: 100%;
            margin-bottom: 10px;
            color: #94a3b8;
            padding: 10px;
            background: #1e293b;
            border-radius: 8px;
            border: 1px dashed #3b82f6;
        }
        input[type="radio"] {
            accent-color: #3b82f6;
            width: 18px;
            height: 18px;
        }
        .gradient-border {
            position: relative;
            padding: 2px;
            border-radius: 12px;
            background: linear-gradient(145deg, #2563eb, #0ea5e9);
            margin-bottom: 30px;
        }
        .gradient-border::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #0f172a;
            border-radius: 10px;
            z-index: -1;
        }
        .content-box {
            position: relative;
            z-index: 2;
            background: #0f172a;
            border-radius: 10px;
            padding: 25px;
        }

        /* Animasi background */
        .bg-animation {
            position: absolute;
            top: -50%;
            left: 50%;
            transform: translateX(-50%);
            width: 100vw;
            max-width: 800px;
            height: 400px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%);
            filter: blur(100px);
            z-index: -2;
        }

        @media (min-width: 768px) {
            .container {
                padding: 30px;
            }
            .thread {
                padding: 35px;
            }
            .btn-container {
                justify-content: flex-end;
            }
            .btn {
                flex: 0 0 auto;
                min-width: 140px;
            }
        }
    </style>
</head>
<body>
<div class="bg-animation"></div>
<div class="container">
    <div class="gradient-border">
        <div class="content-box">
            <div class="thread">
                <h2>Buat Post Baru</h2>
                <form action="buat_post_proses.php" method="post" enctype="multipart/form-data">
                    <label>Judul:</label>
                    <input type="text" name="judul" required>

                    <label>Konten:</label>
                    <textarea name="konten" required></textarea>

                    <label>Forum:</label>
                    <select name="forum_id" required>
                        <?php while ($forum = $forum_result->fetch_assoc()): ?>
                            <option value="<?= $forum['id'] ?>"><?= htmlspecialchars($forum['nama']) ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Media Upload (optional):</label>
                    <div class="media-type">
                        <label>
                            <input type="radio" name="media_type" value="image" checked> Gambar
                        </label>
                        <label>
                            <input type="radio" name="media_type" value="video"> Video
                        </label>
                    </div>
                    <input type="file" name="media_upload" id="media_upload">
                    <div class="file-info" id="file_info">
                        Format: JPG, PNG (max 2MB) atau MP4 (max 20MB)
                    </div>

                    <div class="btn-container">
                        <a href="index.php" class="btn btn-cancel">Batal</a>
                        <button type="submit" class="btn">Posting</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('media_upload').addEventListener('change', function(e) {
        const fileInfo = document.getElementById('file_info');
        if (this.files.length > 0) {
            const file = this.files[0];
            fileInfo.innerHTML = `File: ${file.name} (${(file.size/1024/1024).toFixed(2)} MB)`;
        } else {
            fileInfo.innerHTML = 'Format: JPG, PNG (max 2MB) atau MP4 (max 20MB)';
        }
    });
</script>
</body>
</html>