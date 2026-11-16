<?php
session_start();
require 'db.php';

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
                    $filename = uniqid() . '.' . $ext;
                    $upload_path = 'uploads/' . $filename;
                    
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                        // Delete old avatar if exists
                        if ($user['avatar'] && file_exists($user['avatar'])) {
                            unlink($user['avatar']);
                        }
                        $avatar = $upload_path;
                    } else {
                        $error = "Gagal mengupload avatar!";
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
    <title>Edit Profil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }

        h1 {
            color: #1a1a1b;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.75rem;
        }

        .avatar-section {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 3px solid #eee;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        input, button {
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            width: 100%;
        }

        input:focus {
            outline: none;
            border-color: #0079d3;
            box-shadow: 0 0 0 2px rgba(0,121,211,0.2);
        }

        button {
            background: #0079d3;
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #0069c0;
        }

        .message {
            padding: 0.8rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .error {
            background: #ffebee;
            color: #ff4444;
        }

        .success {
            background: #e8f5e9;
            color: #00c851;
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 1.5rem;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            .avatar-preview {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Edit Profil</h1>
        
        <?php if ($error): ?>
            <div class="message error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="message success"><?= $message ?></div>
        <?php endif; ?>

        <div class="avatar-section">
            <img src="<?= $user['avatar'] ?? 'default-avatar.jpg' ?>" 
                 class="avatar-preview" 
                 id="avatarPreview"
                 alt="Avatar Preview">
            <input type="file" 
                   name="avatar" 
                   id="avatarInput" 
                   accept="image/jpeg, image/png"
                   style="display: none;">
            <button type="button" 
                    onclick="document.getElementById('avatarInput').click()"
                    style="width: auto; margin: 0 auto;">
                Ganti Avatar
            </button>
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

            <button type="submit">Simpan Perubahan</button>
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