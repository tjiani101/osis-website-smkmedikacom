<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>


<?php
include 'db.php';

function getVoteData($conn, $kategori) {
  $query = "SELECT pilihan_$kategori, COUNT(*) as total FROM hasil_voting GROUP BY pilihan_$kategori ORDER BY total DESC";
  $result = mysqli_query($conn, $query);

  $data = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
      'kandidat' => $row["pilihan_$kategori"],
      'total' => (int)$row['total']
    ];
  }
  return $data;
}

$osisData = getVoteData($conn, 'osis');
$mpkData = getVoteData($conn, 'mpk');

function calculatePercentage($data) {
  $totalVotes = array_sum(array_column($data, 'total'));
  foreach ($data as &$d) {
    $d['percent'] = $totalVotes ? round(($d['total'] / $totalVotes) * 100, 2) : 0;
  }
  return $data;
}

$osisData = calculatePercentage($osisData);
$mpkData = calculatePercentage($mpkData);

function getColor($rank) {
  return match($rank) {
    0 => 'green',
    1 => 'blue',
    default => 'red',
  };
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Hasil Voting</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      margin: 0;
      font-family: sans-serif;
      background: #000;
      color: white;
      display: flex;
      flex-direction: row;
      justify-content: space-around;
      align-items: flex-start;
      flex-wrap: wrap;
      padding: 30px;
    }
    
    .judul {
        text-align: center;
        color: white;
        font-size: 36px;
        margin-top: 40px;
        margin-bottom: 24vh;
        font-weight: bold;
        letter-spacing: 2px;
}


    .leaderboard {
      background: rgba(255, 255, 255, 0.1);
      padding: 20px;
      border-radius: 10px;
      width: 300px;
    }

    .leaderboard h2 {
      text-align: center;
    }

    .chart-container {
      width: 300px;
      height: 300px;
      margin-top: 30px;
    }

    .percent {
      font-weight: bold;
    }
    .container {
        width: 100%;
        }

  </style>
</head>
<body>

<center class = "container"> <h1 class = "judul" color = "#ffffff">HASIL VOTING OSIS DAN MPK</h1></center>
  <div class="leaderboard">
    <h2>Leaderboard OSIS</h2>
    <ol>
      <?php foreach ($osisData as $i => $d): ?>
        <li>
          <?= htmlspecialchars($d['kandidat']) ?>
          <span class="percent" style="color: <?= getColor($i) ?>;">
            <?= $d['percent'] ?>%
          </span>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>

  <div class="chart-container">
    <canvas id="osisChart"></canvas>
  </div>

  <div class="leaderboard">
    <h2>Leaderboard MPK</h2>
    <ol>
      <?php foreach ($mpkData as $i => $d): ?>
        <li>
          <?= htmlspecialchars($d['kandidat']) ?>
          <span class="percent" style="color: <?= getColor($i) ?>;">
            <?= $d['percent'] ?>%
          </span>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>

  <div class="chart-container">
    <canvas id="mpkChart"></canvas>
  </div>

  <script>
    const osisLabels = <?= json_encode(array_column($osisData, 'kandidat')) ?>;
    const osisData = <?= json_encode(array_column($osisData, 'percent')) ?>;
    const osisColors = <?= json_encode(array_map(fn($i) => getColor($i), array_keys($osisData))) ?>;

    new Chart(document.getElementById('osisChart'), {
      type: 'pie',
      data: {
        labels: osisLabels,
        datasets: [{
          data: osisData,
          backgroundColor: osisColors
        }]
      },
      options: {
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.parsed + '%';
              }
            }
          }
        }
      }
    });

    const mpkLabels = <?= json_encode(array_column($mpkData, 'kandidat')) ?>;
    const mpkData = <?= json_encode(array_column($mpkData, 'percent')) ?>;
    const mpkColors = ['yellow', 'cyan', 'magenta', 'orange', 'purple'];

    new Chart(document.getElementById('mpkChart'), {
      type: 'pie',
      data: {
        labels: mpkLabels,
        datasets: [{
          data: mpkData,
          backgroundColor: mpkColors.slice(0, mpkLabels.length)
        }]
      },
      options: {
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.parsed + '%';
              }
            }
          }
        }
      }
    });

    fetch('db.php')
    .then(response => response.json())
    .then(data => {
        createCharts(data);
        setInterval(updateCharts, 1000); // update tiap 5 detik
    });
</script>
  </script>

</script>
</body>
</html>
