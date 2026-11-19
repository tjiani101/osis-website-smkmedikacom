<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $nis = $_POST['nis'];
    $new_password = $_POST['new_password'];
    $current_password = $_POST['current_password'];

    // Validate current password
    if (!password_verify($current_password, $user['password_hash'])) {
        $error = "Password saat ini salah!";
    } else {
        // Check username availability
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check->bind_param("si", $username, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Handle avatar upload
            $avatar = $user['avatar'];
            if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png'];
                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);

                if (!in_array(strtolower($ext), $allowed)) {
                    $error = "Hanya file JPG/JPEG/PNG yang diperbolehkan!";
                } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
                    $error = "Ukuran file maksimal 2MB!";
                } else {
                    // Create absolute path for uploads
                    $upload_dir = __DIR__ . '/uploads/';
                    if (!is_dir($upload_dir)) {
                        if (!mkdir($upload_dir, 0755, true)) {
                            $error = "Gagal membuat direktori upload!";
                        }
                    }

                    // Generate unique filename
                    $filename = uniqid() . '.' . $ext;
                    $upload_path = $upload_dir . $filename;

                    // Debugging: Check temporary file
                    if (!is_uploaded_file($_FILES['avatar']['tmp_name'])) {
                        $error = "File tidak valid untuk diupload!";
                    } else {
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                            // Clean up old avatar
                            if (!empty($user['avatar']) && file_exists($user['avatar'])) {
                                unlink($user['avatar']);
                            }
                            $avatar = 'uploads/' . $filename; // Store relative path
                        } else {
                            // Detailed error information
                            $error = "Gagal mengupload avatar! Error: " . $_FILES['avatar']['error'];
                            $error .= " | Check permissions for: " . $upload_dir;
                            $error .= " | Server temp dir: " . ini_get('upload_tmp_dir');
                        }
                    }
                }
            }

            if (empty($error)) {
                // Update user data
                $password_hash = !empty($new_password) ?
                    password_hash($new_password, PASSWORD_DEFAULT) :
                    $user['password_hash'];

                $stmt = $conn->prepare("UPDATE users SET 
                    username = ?, 
                    nis = ?, 
                    password_hash = ?, 
                    avatar = ?
                    WHERE id = ?");

                $stmt->bind_param("ssssi",
                    $username,
                    $nis,
                    $password_hash,
                    $avatar,
                    $user_id);

                if ($stmt->execute()) {
                    $_SESSION['username'] = $username;
                    $message = "Profil berhasil diperbarui!";
                    // Refresh user data
                    $user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
                } else {
                    $error = "Gagal memperbarui profil!";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profil - Forum OSIS Medikacom</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Mulish', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #cbd5e1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .form-container {
            background: linear-gradient(135deg, #1e293b, #2d3748);
            border-radius: 16px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 500px;
            padding: 40px 30px;
            border: 1px solid #334155;
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        h1 {
            color: white;
            font-size: 1.8rem;
            margin-bottom: 30px;
            font-weight: 700;
            text-align: center;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            padding-bottom: 10px;
            border-bottom: 2px solid #334155;
        }

        .avatar-section {
            text-align: center;
            margin-bottom: 25px;
        }

        .avatar-preview {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 4px solid #0f172a;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            background: #1e293b;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #93c5fd;
            font-size: 1rem;
        }

        input {
            background: #1e293b;
            border: 2px solid #334155;
            color: #f8fafc;
            padding: 14px 20px;
            border-radius: 12px;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #60a5fa;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2);
        }

        button {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            margin-top: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        button:hover {
            background: linear-gradient(90deg, #60a5fa, #93c5fd);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }

        .message {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 1rem;
            border: 1px solid transparent;
            text-align: center;
        }

        .error {
            background: rgba(237, 66, 69, 0.2);
            color: #fecaca;
            border-color: #f87171;
        }

        .success {
            background: rgba(34, 197, 94, 0.2);
            color: #bbf7d0;
            border-color: #4ade80;
        }

        input[type="file"] {
            display: none;
        }

        .file-upload-button {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            display: inline-block;
            padding: 12px 25px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .file-upload-button:hover {
            background: linear-gradient(90deg, #60a5fa, #93c5fd);
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 1.6rem;
            }

            .avatar-preview {
                width: 120px;
                height: 120px;
            }

            input {
                padding: 12px 18px;
            }

            button {
                padding: 14px 25px;
            }
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 25px 15px;
            }

            h1 {
                font-size: 1.4rem;
            }

            .avatar-preview {
                width: 100px;
                height: 100px;
            }

            input {
                padding: 12px 15px;
                font-size: 0.95rem;
            }

            button {
                padding: 12px 20px;
                font-size: 1rem;
            }

            .file-upload-button {
                padding: 10px 20px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
<div class="form-container">
    <h1><i class="fas fa-user-edit"></i> Edit Profil</h1>

    <?php if ($error): ?>
        <div class="message error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="message success"><?= $message ?></div>
    <?php endif; ?>

    <div class="avatar-section">
        <img src="uploads/<?= $user['avatar'] ?? 'default-avatar.jpg' ?>"
             class="avatar-preview"
             id="avatarPreview"
             alt="Avatar Preview">
        <label class="file-upload-button">
            <input type="file"
                   name="avatar"
                   id="avatarInput"
                   accept="image/jpeg, image/png">
            <i class="fas fa-camera"></i> Ganti Avatar
        </label>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text"
                   name="username"
                   value="<?= htmlspecialchars($user['username']) ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="nis">NIS</label>
            <input type="text"
                   name="nis"
                   value="<?= htmlspecialchars($user['nis']) ?>"
                   required>
        </div>

        <div class="form-group">
            <label for="current_password">Password Saat Ini</label>
            <input type="password"
                   name="current_password"
                   required>
        </div>

        <div class="form-group">
            <label for="new_password">Password Baru (kosongkan jika tidak ingin mengubah)</label>
            <input type="password"
                   name="new_password">
        </div>

        <button type="submit">
            <i class="fas fa-save"></i> Simpan Perubahan
        </button>
    </form>
</div>

<script>
    // Avatar preview
    document.getElementById('avatarInput').addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatarPreview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
</script>
</body>
</html>