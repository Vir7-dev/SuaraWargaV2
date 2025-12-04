<?php
include "koneksi.php";

// ------------------
// Query kandidat (ambil nama pengguna + nomor kandidat dari tabel kandidat)
// ------------------
$kandidatQuery = $pdo->query("
    SELECT p.id, p.nama, k.no_kandidat
    FROM pengguna p
    JOIN kandidat k ON p.id = k.pengguna_id
    WHERE p.role = 'kandidat'
    ORDER BY k.no_kandidat ASC
");
$kandidatList = $kandidatQuery->fetchAll(PDO::FETCH_ASSOC);

// ------------------
// Query suara per kandidat
// ------------------
$suaraQuery = $pdo->query("
    SELECT 
        kandidat_id,
        COUNT(*) AS total_suara
    FROM suara
    GROUP BY kandidat_id
");
$suaraData = $suaraQuery->fetchAll(PDO::FETCH_ASSOC);

// Hitung total keseluruhan suara
$totalSuara = $pdo->query("SELECT COUNT(*) FROM suara")->fetchColumn();

// ------------------
// Gabungkan suara ke kandidat
// ------------------
foreach ($kandidatList as $key => $k) {
    $total = 0;

    foreach ($suaraData as $s) {
        if ($s['kandidat_id'] == $k['id']) {
            $total = $s['total_suara'];
        }
    }

    $kandidatList[$key]['total_suara'] = $total;
    $kandidatList[$key]['persentase'] = ($totalSuara > 0)
        ? number_format(($total / $totalSuara) * 100, 2)
        : 0;
}

// Grafik
$labels = array_column($kandidatList, 'nama');
$data = array_column($kandidatList, 'total_suara');

// Tabel suara
$dataTable = $pdo->query("SELECT * FROM suara ORDER BY waktu DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Perolehan Suara</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 25px;
            background: #f5f5f5;
        }

        h1,
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            padding: 20px;
            width: 280px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            margin: 0 0 10px;
        }

        .progress {
            background: #dcdcdc;
            border-radius: 10px;
            height: 18px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-bar {
            background: #28a745;
            height: 18px;
            text-align: center;
            color: white;
            font-size: 12px;
            line-height: 18px;
        }

        canvas {
            display: block;
            max-width: 900px;
            margin: 0 auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 40px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
        }

        th {
            background: #009879;
            color: white;
        }
    </style>
</head>

<body>

    <h1>Dashboard Perolehan Suara Kandidat</h1>

    <!-- CARD KANDIDAT -->
    <div class="card-container">
        <?php foreach ($kandidatList as $data): ?>
            <div class="card">
                <h3><?= htmlspecialchars($data['nama']); ?> (No <?= $data['no_kandidat']; ?>)</h3>
                <p><strong>Total Suara:</strong> <?= $data['total_suara']; ?></p>
                <p><strong>Persentase:</strong> <?= $data['persentase']; ?>%</p>

                <div class="progress">
                    <div class="progress-bar" style="width: <?= $data['persentase']; ?>%;">
                        <?= $data['persentase']; ?>%
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- GRAFIK -->
    <h2>Grafik Perolehan Suara</h2>
    <canvas id="suaraChart"></canvas>

    <script>
        const labels = <?= json_encode($labels); ?>;
        const data = <?= json_encode($data); ?>;

        new Chart(document.getElementById('suaraChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Suara',
                    data: data
                }]
            }
        });
    </script>

</body>

</html>