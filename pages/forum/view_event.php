<?php
session_start();
include 'db.php';

// Cek role user
$allowed_roles = ['DEV', 'admin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit();
}

// Ambil data pendaftaran dari database (ditambah teacher_contact)
$query = "SELECT 
            participants.competition_type, 
            events.class_name, 
            participants.contact_person,
            participants.participant_names,
            events.teacher_contact
          FROM participants
          JOIN events ON participants.event_id = events.id
          ORDER BY participants.competition_type, events.class_name";

$result = $conn->query($query);
$registrations = [];
while ($row = $result->fetch_assoc()) {
    $registrations[$row['competition_type']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Pendaftaran PORSENI 2025</title>
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@200;1000&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
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
        .registration-item {
            background: rgba(30, 41, 59, 0.5);
            border-bottom: 1px solid #334155;
        }
        .registration-item:last-child {
            border-bottom: none;
        }
        .competition-title {
            background: linear-gradient(145deg, #1e40af, #0ea5e9);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .contact-link {
            transition: all 0.3s ease;
        }
        .contact-link:hover {
            color: #3b82f6;
            transform: translateY(-1px);
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
            <div class="flex items-center justify-center h-screen">
                <span class="text-blue-400 mr-4">Hai, <?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
        </div>

    </div>

</nav>

<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-center mb-6 bg-gradient-to-r from-blue-500 to-cyan-400 bg-clip-text text-transparent">
        DATA PENDAFTARAN PORSENI 2025
    </h1>

    <div class="bg-slate-900/50 backdrop-blur-lg rounded-2xl p-6 md:p-8 shadow-xl shadow-blue-500/20 border border-slate-700">
        <!-- Filter dan Pencarian -->
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-xl font-bold text-blue-300">Total Pendaftar:
                    <span class="text-cyan-400"><?= $result->num_rows / 9 ?> Kelas</span>
                </h2>
            </div>
            <div class="flex gap-3 w-full md:w-auto">
                <input type="text" id="search-input" placeholder="Cari kelas..." class="input-field px-4 py-2 rounded-lg w-full">
                <select id="competition-filter" class="input-field px-4 py-2 rounded-lg">
                    <option value="">Semua Lomba</option>
                    <option value="volly">Volly</option>
                    <option value="handball">Handball</option>
                    <option value="dance">Modern Dance</option>
                    <option value="bazar">bazar</option>
                    <option value="futsal">Futsal</option>
                    <option value="Tari">Tari Traditional</option>
                    <option value="catur">Catur</option>
                    <option value="nyanyi">Nyanyi</option>
                    <option value="esport">E-Sport</option>
                </select>
            </div>
        </div>

        <!-- Daftar Lomba dan Pendaftar -->
        <div class="space-y-10">
            <?php foreach ($registrations as $competition => $classes): ?>
                <?php
                // Map nama kompetisi
                $competitionNames = [
                    'volly' => 'Volly',
                    'handball' => 'Handball',
                    'futsal' => 'Futsal',
                    'bola_beracun' => 'Bola Beracun',
                    'catur' => 'Catur',
                    'nyanyi' => 'Nyanyi',
                    'esport' => 'E-Sport'
                ];
                $competitionName = $competitionNames[$competition] ?? $competition;
                ?>

                <div class="competition-card p-6 competition-section" data-competition="<?= $competition ?>">
                    <h2 class="text-2xl font-bold mb-6 competition-title">
                        Lomba <?= $competitionName ?>
                        <span class="text-slate-400 text-lg">(<?= count($classes) ?> Kelas)</span>
                    </h2>

                    <div class="overflow-x-auto">
                        <?php if ($competition === 'esport'): ?>
                            <!-- Tampilan khusus untuk E-Sport -->
                            <table class="w-full">
                                <thead>
                                <tr class="text-left text-slate-300 border-b border-slate-700">
                                    <th class="pb-3 px-4">No</th>
                                    <th class="pb-3 px-4">Kelas</th>
                                    <th class="pb-3 px-4">Nama Team</th>
                                    <th class="pb-3 px-4">Kontak Perwakilan</th>
                                    <th class="pb-3 px-4">Peserta</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($classes as $index => $class): ?>
                                    <tr class="registration-item hover:bg-slate-800/50">
                                        <td class="py-4 px-4"><?= $index + 1 ?></td>
                                        <td class="py-4 px-4 font-semibold"><?= htmlspecialchars($class['class_name']) ?></td>
                                        <td class="py-4 px-4">
                                            <?= htmlspecialchars($class['contact_person']) ?>
                                        </td>
                                        <td class="py-4 px-4">
                                            <a href="tel:<?= htmlspecialchars($class['teacher_contact']) ?>"
                                               class="contact-link text-blue-400 hover:text-blue-300 flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                                </svg>
                                                <?= htmlspecialchars($class['teacher_contact']) ?>
                                            </a>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="flex flex-wrap gap-2">
                                                <?php
                                                $participants = explode(', ', $class['participant_names']);
                                                foreach ($participants as $participant):
                                                    ?>
                                                    <span class="px-3 py-1 bg-slate-700 rounded-full text-sm">
                                                        <?= htmlspecialchars($participant) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <!-- Tampilan untuk lomba lainnya -->
                            <table class="w-full">
                                <thead>
                                <tr class="text-left text-slate-300 border-b border-slate-700">
                                    <th class="pb-3 px-4">No</th>
                                    <th class="pb-3 px-4">Kelas</th>
                                    <th class="pb-3 px-4">Kontak Perwakilan</th>
                                    <th class="pb-3 px-4">Peserta</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($classes as $index => $class): ?>
                                    <tr class="registration-item hover:bg-slate-800/50">
                                        <td class="py-4 px-4"><?= $index + 1 ?></td>
                                        <td class="py-4 px-4 font-semibold"><?= htmlspecialchars($class['class_name']) ?></td>
                                        <td class="py-4 px-4">
                                            <a href="tel:<?= htmlspecialchars($class['contact_person']) ?>"
                                               class="contact-link text-blue-400 hover:text-blue-300 flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                                </svg>
                                                <?= htmlspecialchars($class['contact_person']) ?>
                                            </a>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="flex flex-wrap gap-2">
                                                <?php
                                                $participants = explode(', ', $class['participant_names']);
                                                foreach ($participants as $participant):
                                                    ?>
                                                    <span class="px-3 py-1 bg-slate-700 rounded-full text-sm">
                                                        <?= htmlspecialchars($participant) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // Filter dan pencarian
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('search-input');
        const competitionFilter = document.getElementById('competition-filter');
        const competitionSections = document.querySelectorAll('.competition-section');

        function filterRegistrations() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedCompetition = competitionFilter.value;

            competitionSections.forEach(section => {
                const competition = section.dataset.competition;
                let visible = true;

                // Filter berdasarkan lomba
                if (selectedCompetition && selectedCompetition !== competition) {
                    visible = false;
                }
                // Filter berdasarkan pencarian
                else if (searchTerm) {
                    const rows = section.querySelectorAll('tbody tr');
                    let hasVisibleRows = false;

                    rows.forEach(row => {
                        // Handle different column structures
                        const classNameCell = row.querySelector('td:nth-child(2)');
                        const contactCell = row.querySelector(competition === 'esport' ? 'td:nth-child(4)' : 'td:nth-child(3)');
                        const participantsCell = row.querySelector(competition === 'esport' ? 'td:nth-child(5)' : 'td:nth-child(4)');
                        const teamNameCell = competition === 'esport' ? row.querySelector('td:nth-child(3)') : null;

                        const className = classNameCell.textContent.toLowerCase();
                        const contact = contactCell.textContent.toLowerCase();
                        const participants = participantsCell.textContent.toLowerCase();
                        const teamName = teamNameCell ? teamNameCell.textContent.toLowerCase() : '';

                        if (className.includes(searchTerm) ||
                            contact.includes(searchTerm) ||
                            participants.includes(searchTerm) ||
                            (teamName && teamName.includes(searchTerm))) {
                            row.style.display = '';
                            hasVisibleRows = true;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    visible = hasVisibleRows;
                } else {
                    // Tampilkan semua baris jika tidak ada pencarian
                    section.querySelectorAll('tbody tr').forEach(row => {
                        row.style.display = '';
                    });
                }

                section.style.display = visible ? 'block' : 'none';
            });
        }

        searchInput.addEventListener('input', filterRegistrations);
        competitionFilter.addEventListener('change', filterRegistrations);
    });
</script>
</body>
</html>