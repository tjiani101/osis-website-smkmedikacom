<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pendaftaran Eskul Ditutup - OSIS SMK Medikacom</title>
  
  <!-- Google Fonts: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
      display: flex;
      justify-content: center;
      align-items: center;
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
      background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
      background-size: 400% 400%;
      animation: gradientBG 15s ease infinite;
      filter: blur(8px);
      transform: scale(1.1);
    }
    
    @keyframes gradientBG {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
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

    .container {
      max-width: 800px;
      width: 100%;
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border: 1px solid rgba(59, 130, 246, 0.2);
      border-radius: 1.5rem;
      padding: 40px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      position: relative;
      z-index: 1;
      text-align: center;
    }

    .text {
      font-size: 28px;
      font-weight: 600;
      color: #ebf0ff;
      text-align: center;
      margin-bottom: 30px;
      text-transform: uppercase;
      letter-spacing: 2px;
      position: relative;
    }

    .text::after {
      content: '';
      display: block;
      width: 80px;
      height: 4px;
      background: linear-gradient(to right, #3b82f6, #60a5fa);
      margin: 15px auto 0;
      border-radius: 2px;
    }

    .closed-icon {
      font-size: 100px;
      color: #ef4444;
      margin: 30px 0;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); }
    }

    .message {
      font-size: 20px;
      line-height: 1.8;
      margin-bottom: 30px;
      background: rgba(239, 68, 68, 0.1);
      padding: 25px;
      border-radius: 15px;
      border-left: 4px solid #ef4444;
    }

    .message h2 {
      color: #ef4444;
      margin-bottom: 15px;
      font-size: 24px;
    }

    .info-box {
      background: rgba(59, 130, 246, 0.1);
      padding: 25px;
      border-radius: 15px;
      margin: 25px 0;
      border-left: 4px solid #3b82f6;
      text-align: left;
    }

    .info-box h3 {
      color: #60a5fa;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .info-box ul {
      list-style-type: none;
      padding-left: 20px;
    }

    .info-box li {
      margin: 12px 0;
      display: flex;
      align-items: flex-start;
      gap: 10px;
    }

    .info-box li i {
      color: #60a5fa;
      margin-top: 5px;
    }

    .contact {
      margin: 20px 0 30px;
      font-size: 18px;
    }

    .contact i {
      color: #60a5fa;
      margin-right: 10px;
    }

    .home-btn {
      display: inline-block;
      width: auto;
      margin: 20px 10px;
      text-align: center;
      background: #5b6eae;
      color: white;
      padding: 15px 35px;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
      box-shadow: 0 0 5px rgba(59, 130, 246, 0), 0 0 10px rgba(59, 130, 246, 0);
      border: none;
      cursor: pointer;
      font-size: 16px;
    }

    .home-btn:hover {
      background: rgb(63, 84, 153);
      box-shadow: 0 0 15px rgba(59, 130, 246, 0.6), 0 0 25px rgba(37, 99, 235, 0.4);
      transform: translateY(-2px);
    }

    .home-btn.secondary {
      background: rgba(92, 110, 174, 0.2);
      border: 1px solid #5b6eae;
    }

    .home-btn.secondary:hover {
      background: rgba(63, 84, 153, 0.3);
    }

    .counter {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin: 40px 0;
    }

    .counter-item {
      background: rgba(59, 130, 246, 0.15);
      border-radius: 15px;
      padding: 20px 15px;
      min-width: 100px;
      border: 1px solid rgba(59, 130, 246, 0.3);
    }

    .counter-value {
      font-size: 36px;
      font-weight: 700;
      color: #60a5fa;
      margin-bottom: 5px;
    }

    .counter-label {
      font-size: 14px;
      color: #9ca3af;
    }

    .logo {
      margin-top: 30px;
      opacity: 0.7;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 15px;
    }

    .logo img {
      height: 50px;
      filter: brightness(0.8);
    }

    .logo-text {
      font-size: 18px;
      font-weight: 300;
      letter-spacing: 1px;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .container {
        padding: 20px;
      }
      
      .text {
        font-size: 22px;
      }
      
      .closed-icon {
        font-size: 70px;
      }
      
      .message {
        font-size: 16px;
        padding: 20px;
      }
      
      .counter {
        flex-wrap: wrap;
      }
      
      .counter-item {
        min-width: 70px;
      }
      
      .counter-value {
        font-size: 28px;
      }
      
      .home-btn {
        display: block;
        width: 100%;
        margin: 10px 0;
      }
    }
  </style>
</head>
<body>
  <div class="background-container"></div>
  <div class="background-overlay"></div>
  
  <div class="container">
    <div class="text">Pendaftaran Ekstrakurikuler</div>
    
    <div class="closed-icon">
      <i class="fas fa-lock"></i>
    </div>
    
    <div class="message">
      <h2><i class="fas fa-exclamation-circle"></i> Pendaftaran Telah Ditutup</h2>
      <p>Mohon maaf, pendaftaran ekstrakurikuler untuk tahun ajaran 2025/2026 telah ditutup. Terima kasih atas minat dan partisipasi seluruh siswa SMK Medikacom.</p>
    </div>
    
    <div class="counter">
      <div class="counter-item">
        <div class="counter-value">215</div>
        <div class="counter-label">Pendaftar</div>
      </div>
      <div class="counter-item">
        <div class="counter-value">NULL</div>
        <div class="counter-label">Eskul</div>
      </div>
      <div class="counter-item">
        <div class="counter-value">9</div>
        <div class="counter-label">Jurusan</div>
      </div>
    </div>
    
    <div class="info-box">
      <h3><i class="fas fa-info-circle"></i> Informasi Penting</h3>
      <ul>
        <li>
          <i class="fas fa-check-circle"></i>
          <span>Pendaftaran eskul ditutup pada tanggal 24 Juli 2025 pukul 23:59 WIB</span>
        </li>
        <li>
          <i class="fas fa-check-circle"></i>
          <span>Pengumuman hasil seleksi akan disampaikan melalui email / Whatsapp masing-masing peserta</span>
        </li>
        <li>
          <i class="fas fa-check-circle"></i>
          <span>Untuk pertanyaan lebih lanjut, silakan hubungi panitia pendaftaran eskul</span>
        </li>
      </ul>
    </div>
    
    <div class="contact">
      <p><i class="fas fa-envelope"></i> eskul@medikacom.sch.id</p>
      <p><i class="fas fa-phone"></i> (022) 1234-5678</p>
    </div>
    
    <a href="https://osis-medcom.web.id" class="home-btn" target="_blank">
      <i class="fas fa-home"></i> Kembali ke Beranda
    </a>
    

    
    <div class="logo">
      <img src="https://osis-medcom.web.id/images/logo.png" alt="Logo" class="logo-img">
      <div class="logo-text">OSIS SMK MEDIKACOM</div>
    </div>
  </div>

  <script>
    // Animasi counter
    document.addEventListener('DOMContentLoaded', function() {
      const counters = document.querySelectorAll('.counter-value');
      const speed = 200;
      
      counters.forEach(counter => {
        const animate = () => {
          const value = +counter.innerText;
          const target = +counter.getAttribute('data-target');
          const increment = target / speed;
          
          if (value < target) {
            counter.innerText = Math.ceil(value + increment);
            setTimeout(animate, 1);
          } else {
            counter.innerText = target.toLocaleString();
          }
        }
        
        // Set initial data-target values
        counter.setAttribute('data-target', counter.innerText);
        counter.innerText = "0";
        animate();
      });
    });
  </script>
</body>
</html>