
<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Pendaftaran Eskul - OSIS SMK Medikacom</title>
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      background-color: #0a0a0a;
      font-family: 'Poppins', sans-serif;
      color: #e5e7eb;
      min-height: 100vh;
      padding: 20px;
      position: relative;
      overflow-x: hidden;
    }

    /* Background Elements */
    .background-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100vh;
      z-index: -1;
      background-image: url('../images/slider/IMG_6393.jpg');
      background-size: cover;
      background-position: center;
      filter: blur(8px);
      transform: scale(1.1);
    }
    
    .background-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100vh;
      z-index: -1;
      background-color: rgba(10, 10, 20, 0.7);
    }
  </style>
</head>
<body>
  <div class="background-container"></div>
  <div class="background-overlay"></div>
  
  <div class="max-w-6xl mx-auto py-8 px-4">
    <!-- Header -->
    <div class="text-center mb-12">
      <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">DATA PENDAFTARAN ESKUL</h1>
      <div class="w-24 h-1 bg-gradient-to-r from-blue-500 to-blue-300 mx-auto rounded-full"></div>
    </div>
    
    <!-- Filter Section -->
    <div class="bg-gray-900/50 backdrop-blur-md rounded-2xl border border-blue-500/20 p-6 mb-8 shadow-xl">
      <form method="get" class="flex flex-col md:flex-row gap-4 items-center">
        <div class="w-full md:w-3/4">
          <label class="block text-blue-300 mb-2">Filter Berdasarkan Eskul:</label>
          <div class="relative">
            <select name="eskul" class="w-full p-3 bg-gray-800/70 border border-gray-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none">
              <option value="">-- Semua Eskul --</option>
              <?php
              $sql = "SELECT DISTINCT eskul FROM pendaftaran_eskul ORDER BY eskul";
              $result = $conn->query($sql);
              $selected = isset($_GET['eskul']) ? $_GET['eskul'] : '';
              
              if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                  $isSelected = ($selected === $row['eskul']) ? 'selected' : '';
                  echo '<option value="' . htmlspecialchars($row['eskul']) . '" ' . $isSelected . '>' . htmlspecialchars($row['eskul']) . '</option>';
                }
              } else {
                echo '<option value="">Tidak ada eskul terdaftar</option>';
              }
              ?>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400">
              <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
            </div>
          </div>
        </div>
        <div class="w-full md:w-1/4 flex items-end">
          <button type="submit" class="w-full py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition duration-300 shadow-lg hover:shadow-blue-500/20">Filter Data</button>
        </div>
      </form>
    </div>
    
    <!-- Registrant Table -->
    <div class="bg-gray-900/50 backdrop-blur-md rounded-2xl border border-blue-500/20 overflow-hidden shadow-xl">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="bg-blue-900/40 text-blue-300">
              <th class="py-4 px-6 text-left">Nama Lengkap</th>
              <th class="py-4 px-6 text-left">Jurusan</th>
              <th class="py-4 px-6 text-left">Nomor HP</th>
              <th class="py-4 px-6 text-left">Eskul</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sql = "SELECT * FROM pendaftaran_eskul";
            $params = [];
            $types = "";
            
            if (isset($_GET['eskul']) && !empty($_GET['eskul'])) {
              $sql .= " WHERE eskul = ?";
              $params[] = $_GET['eskul'];
              $types = "s";
            }
            
            $sql .= " ORDER BY nama ASC";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
              if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
              }
              
              $stmt->execute();
              $result = $stmt->get_result();
              
              if ($result->num_rows > 0) {
                $count = 0;
                while($row = $result->fetch_assoc()) {
                  $bgClass = ($count % 2 == 0) ? 'bg-gray-800/30' : 'bg-gray-800/10';
                  echo "<tr class='border-b border-gray-700 hover:bg-gray-700/50 transition duration-200 $bgClass'>";
                  echo "<td class='py-4 px-6'>" . htmlspecialchars($row['nama']) . "</td>";
                  echo "<td class='py-4 px-6'>" . htmlspecialchars($row['jurusan']) . "</td>";
                  // FIX: Ensure column name matches database (telp)
                  echo "<td class='py-4 px-6'>" . htmlspecialchars($row['telp']) . "</td>";
                  echo "<td class='py-4 px-6'><span class='px-3 py-1 bg-blue-900/40 rounded-full text-sm'>" . htmlspecialchars($row['eskul']) . "</span></td>";
                  echo "</tr>";
                  $count++;
                }
              } else {
                // FIX: colspan changed from 3 to 4 (for 4 columns)
                echo "<tr><td colspan='4' class='py-8 px-6 text-center text-gray-400'>Tidak ada data pendaftaran</td></tr>";
              }
              
              $stmt->close();
            } else {
              // FIX: colspan changed from 3 to 4
              echo "<tr><td colspan='4' class='py-8 px-6 text-center text-gray-400'>Error: " . $conn->error . "</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Footer Navigation -->
    <div class="mt-10 flex justify-center gap-4">
      <a href="i.php" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-full transition duration-300 shadow-lg hover:shadow-blue-500/20">
        Form Pendaftaran
      </a>
      <a href="https://osis-medcom.web.id" target="_blank" class="px-6 py-3 bg-gray-700 hover:bg-gray-800 text-white font-medium rounded-full transition duration-300">
        Kembali ke Beranda
      </a>
    </div>
    
    <!-- Result Counter -->
<div class="mt-6 text-center text-gray-400">
      <?php
      // FIX: Use prepared statement for count to prevent SQL injection
      $countSql = "SELECT COUNT(*) as total FROM pendaftaran_eskul";
      $countParams = [];
      $countTypes = "";
      
      if (isset($_GET['eskul']) && !empty($_GET['eskul'])) {
        $countSql .= " WHERE eskul = ?";
        $countParams[] = $_GET['eskul'];
        $countTypes = "s";
      }
      
      $countStmt = $conn->prepare($countSql);
      
      if ($countStmt) {
        if (!empty($countParams)) {
          $countStmt->bind_param($countTypes, ...$countParams);
        }
        
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
        $countStmt->close();
      } else {
        $total = 0;
      }
      
      $filtered = isset($_GET['eskul']) && !empty($_GET['eskul']) ? " (Filtered by: " . htmlspecialchars($_GET['eskul']) . ")" : "";
      echo "Menampilkan <span class='text-blue-400'>$total</span> pendaftar$filtered";
      ?>
    </div>
  </div>
  </div>
</body>
</html>