<?php
session_start();
include 'db.php'; 

// Cek role user
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'DEV'])) {
    echo "Akses ditolak!";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel | Forum OSIS</title>
  <link rel="stylesheet" require href="styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- Tambahkan Google Fonts agar senada -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
  <div class="sidebar">
    <h2>admin panel</h2>
    <a href="?page=directory"><i class="fa fa-folder"></i> Directory</a>
    <a href="?page=log"><i class="fa fa-history"></i> LOG massage</a>
    <a href="?page=files"><i class="fa fa-file-image"></i> Files</a>
    <a href="?page=databases"><i class="fa fa-database"></i> Databases</a>
    <a href="?page=users"><i class="fa fa-users"></i> Users</a>
    <a href="?page=eskul"><i class="fa fa-users"></i> Pendaftaran Eskul</a>
    <a href="?page=osis"><i class="fa fa-users"></i> Pendaftaran osis</a>
    <a href="?page=galery"><i class="fa fa-image"></i> Gallery upload</a>
    
    <!-- MENU BARU -->
    <a href="?page=lomba_ramadhan" style="color: #60a5fa; border-left: 3px solid #60a5fa; background: rgba(96,165,250,0.1);"><i class="fa fa-star"></i> Event Ramadhan</a>
    
    <div class="user">
      <span>user: <?php echo $_SESSION['username']; ?></span>
    </div>
  </div>

  <div class="content">
    <?php
      $page = $_GET['page'] ?? 'directory';
      if ($page == 'directory') include 'pages/directory.php';
      elseif ($page == 'files') include 'pages/files.php';
      elseif ($page == 'databases') include 'pages/databases.php';
      elseif ($page == 'users') include 'pages/users.php';
      elseif ($page == 'eskul') include 'pages/eskul.php';
      elseif ($page == 'osis') include 'pages/osis.php';
      elseif ($page == 'galery') include 'pages/galery.php';
      elseif ($page == 'log') include 'pages/log.php';
      
      // LOGIC BARU UNTUK HALAMAN LOMBA
      elseif ($page == 'lomba_ramadhan') include 'lomba/lomba_ramadhan.php';
      
      else echo "<h2>Halaman tidak ditemukan.</h2>";
    ?>
  </div>
</body>
</html>