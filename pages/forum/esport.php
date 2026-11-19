<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'db.php';

// Remove form processing since event has ended
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran PORSENI 2025 Telah Berakhir</title>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@200;1000&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Existing styles remain unchanged */
        @keyframes slide-left {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }
        .animate-slide-left-infinite {
            animation: slide-left 30s reverse linear infinite;
        }
        body {
            font-family: 'Mulish', sans-serif;
            background-color: #0f172a;
            color: white;
        }
        .competition-card {
            background: linear-gradient(145deg, #1e293b, #0f172a);
            border: 1px solid #334155;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .competition-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(56, 189, 248, 0.2);
        }
        .input-field {
            background-color: #1e293b;
            border: 1px solid #334155;
            color: white;
            transition: border-color 0.3s;
        }
        .input-field:focus {
            border-color: #3b82f6;
            outline: none;
        }
        .add-btn, .remove-btn {
            transition: all 0.2s ease;
        }
        .add-btn:hover {
            transform: scale(1.05);
            background-color: #3b82f6;
        }
        .remove-btn:hover {
            transform: scale(1.05);
            background-color: #ef4444;
        }
        /* New styles for event ended message */
        .event-ended-card {
            background: linear-gradient(145deg, #1e293b, #0f172a);
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 10px 25px rgba(56, 189, 248, 0.1);
        }
        .event-ended-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            color: #3b82f6;
        }
        .event-ended-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(to right, #3b82f6, #60a5fa);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .event-ended-message {
            font-size: 1.25rem;
            color: #cbd5e1;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .countdown-container {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .countdown-box {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            min-width: 80px;
        }
        .countdown-value {
            font-size: 2rem;
            font-weight: 800;
            color: #3b82f6;
        }
        .countdown-label {
            font-size: 0.875rem;
            color: #94a3b8;
            text-transform: uppercase;
        }
    </style>
</head>
<body class="bg-slate-950">
<div class="absolute -top-50 left-1/2 -translate-x-1/2 w-[100vw] lg:w-[800px] h-[400px] rounded-full bg-blue-500 opacity-20 blur-[150px]"></div>

<nav class="mb-8">
    <div class="mx-auto max-w-7xl px-2 sm:px-6 lg:px-8">
        <div class="relative flex h-16 items-center justify-between">
            <div class="flex flex-1 items-center justify-between sm:items-stretch sm:justify-start">
                <div class="flex shrink-0 items-center">
                    <img class="h-9 w-auto mr-2" src="../../images/logo.png">
                    <span class="text-slate-100 text-sm font-semibold leading-4">OSIS & MPK<br>SMK MEDIKACOM</span>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-center mb-6 bg-gradient-to-r from-blue-500 to-cyan-400 bg-clip-text text-transparent">
        ACARA PORSENI 2025 TELAH BERAKHIR
    </h1>
    <h3 class="max-w-[70vw] lg:max-w-xl xl:max-w-4xl mx-auto text-center text-white text-sm md:text-base lg:text-xl mt-5 ">
        Terima kasih atas partisipasi dan antusiasme seluruh peserta
    </h3>

    <div class="bg-slate-900/50 backdrop-blur-lg rounded-2xl p-6 md:p-8 shadow-xl shadow-blue-500/20 border border-slate-700">
        <div class="event-ended-card">
            <div class="event-ended-icon">🏁</div>
            <h2 class="event-ended-title">Pendaftaran Telah Ditutup</h2>
            <p class="event-ended-message">
                Pendaftaran untuk PORSENI 2025 telah resmi ditutup pada tanggal 15 Juni 2025.
                Kami ucapkan terima kasih kepada semua kelas yang telah berpartisipasi dalam event ini.
            </p>
            <div class="bg-slate-800/50 rounded-xl p-4 max-w-xl mx-auto">
                <p class="text-lg text-sky-300 font-semibold mb-2">Statistik Pendaftaran</p>
                <p class="text-slate-300">
                    Total peserta: <span class="text-amber-300">NULL Kelas</span> |
                    Lomba terbanyak: <span class="text-amber-300">NULL</span>
                </p>
            </div>

            <div class="mt-10">
                <h3 class="text-xl font-bold text-sky-300 mb-4">Nantikan Event Kami Berikutnya!</h3>
                <div class="countdown-container">
                    <div class="countdown-box">
                        <div class="countdown-value">00</div>
                        <div class="countdown-label">Hari</div>
                    </div>
                    <div class="countdown-box">
                        <div class="countdown-value">00</div>
                        <div class="countdown-label">Jam</div>
                    </div>
                    <div class="countdown-box">
                        <div class="countdown-value">10</div>
                        <div class="countdown-label">Menit</div>
                    </div>
                    <div class="countdown-box">
                        <div class="countdown-value">10</div>
                        <div class="countdown-label">Detik</div>
                    </div>
                </div>
            </div>

            <div class="mt-12">
                <p class="text-slate-400">
                    Follow media sosial kami untuk informasi event selanjutnya:
                </p>
                <div class="flex justify-center gap-4 mt-4">
                    <a href="#" class="text-slate-300 hover:text-blue-400 transition-colors">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z" clip-rule="evenodd"></path></svg>
                    </a>
                    <a href="#" class="text-slate-300 hover:text-pink-500 transition-colors">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" clip-rule="evenodd"></path></svg>
                    </a>
                    <a href="#" class="text-slate-300 hover:text-red-600 transition-colors">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Simple countdown timer for next event (placeholder)
    function updateCountdown() {
        const countdownValues = document.querySelectorAll('.countdown-value');
        countdownValues.forEach(el => {
            el.textContent = 'NULL';
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateCountdown();
    });
</script>
</body>
</html>