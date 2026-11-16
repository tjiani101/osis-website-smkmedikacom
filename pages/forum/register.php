<?php
include 'db.php';
$message = '';

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $nis = $_POST['nis'];
    $password = $_POST['password'];
    $repeat_password = $_POST['repeat_password'];

    // Check if passwords match
    if ($password !== $repeat_password) {
        $message = "<div class='error'>Password tidak sesuai!</div>";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'murid'; // default
        $status = 'active';
        $warning_nis = 1; // selalu warning

        // cek apakah username udah dipakai
        $cek = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $message = "<div class='error'>Username sudah digunakan.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, nis, password_hash, role, status, warning_nis) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $username, $nis, $password_hash, $role, $status, $warning_nis);
            if ($stmt->execute()) {
                $message = "<div class='success'>Akun berhasil dibuat! Silakan login.</div>";
            } else {
                $message = "<div class='error'>Gagal mendaftar.</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Mulish', sans-serif;
            background-color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: 50%;
            transform: translateX(-50%);
            width: 100vw;
            max-width: 800px;
            height: 400px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.2) 0%, transparent 70%);
            filter: blur(100px);
            z-index: -1;
        }

        .form-container {
            background: rgba(15, 23, 42, 0.8);
            padding: 2.5rem;
            border-radius: 12px;
            position: relative;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(59, 130, 246, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, #3b82f6, #22d3ee);
            border-radius: 12px 12px 0 0;
        }

        h2 {
            color: white;
            margin-bottom: 1.8rem;
            text-align: center;
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(to right, #3b82f6, #22d3ee);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        input {
            padding: 1rem;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            font-size: 1rem;
            color: #ebf0ff;
            transition: all 0.3s;
            font-family: 'Mulish', sans-serif;
        }

        input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .password-container {
            position: relative;
        }

        .show-password {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 0.5rem;
            color: #93c5fd;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .show-password input {
            width: auto;
            margin: 0;
            accent-color: #3b82f6;
        }

        button {
            background: linear-gradient(to right, #2563eb, #0ea5e9);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);
            font-family: 'Mulish', sans-serif;
            letter-spacing: 0.5px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.4);
        }

        p {
            text-align: center;
            margin-top: 1.5rem;
            color: #93c5fd;
            font-size: 1rem;
        }

        a {
            color: #93c5fd;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
        }

        a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(to right, #3b82f6, #22d3ee);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s;
        }

        a:hover {
            color: white;
        }

        a:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        small {
            display: block;
            color: #93c5fd;
            background: rgba(30, 41, 59, 0.5);
            padding: 0.9rem;
            border-radius: 8px;
            margin: -0.5rem 0 0.5rem;
            border: 1px solid rgba(59, 130, 246, 0.3);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .error, .success {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            text-align: center;
        }

        .error {
            color: #fecaca;
            background: rgba(127, 29, 29, 0.3);
            border: 1px solid rgba(248, 113, 113, 0.3);
        }

        .success {
            color: #bbf7d0;
            background: rgba(21, 128, 61, 0.3);
            border: 1px solid rgba(74, 222, 128, 0.3);
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 1.8rem;
            }

            h2 {
                font-size: 1.7rem;
            }

            small {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Register</h2>
    <?= $message ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="text" name="nis" placeholder="NIS" required>
        <small>
            ⚠️ Jika NIS Anda tidak terdeteksi dalam database SMK Medikacom,
            akun Anda tetap bisa dibuat, namun berpotensi diban. Pastikan Anda sudah membaca peraturan <a href="peraturan.html">di sini</a>
        </small>

        <div class="password-container">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <label class="show-password">
                <input type="checkbox" id="showPassword"> Tampilkan Password
            </label>
        </div>

        <div class="password-container">
            <input type="password" name="repeat_password" id="repeatPassword" placeholder="Ulangi Password" required>
            <label class="show-password">
                <input type="checkbox" id="showRepeatPassword">Tampilkan Password
            </label>
        </div>

        <button type="submit" name="submit">Register</button>
    </form>
    <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
</div>

<script>

    // Password toggle functionality
    const passwordInput = document.getElementById('password');
    const repeatPasswordInput = document.getElementById('repeatPassword');
    const showPasswordCheckbox = document.getElementById('showPassword');
    const showRepeatPasswordCheckbox = document.getElementById('showRepeatPassword');

    showPasswordCheckbox.addEventListener('change', function() {
        passwordInput.type = this.checked ? 'text' : 'password';
    });

    showRepeatPasswordCheckbox.addEventListener('change', function() {
        repeatPasswordInput.type = this.checked ? 'text' : 'password';
    });
</script>
</body>
</html>