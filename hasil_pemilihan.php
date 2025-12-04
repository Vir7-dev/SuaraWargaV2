<?php
include "koneksi.php";

// ------------------
// QUERY KANDIDAT
// ------------------
$kandidatQuery = $pdo->query("
    SELECT 
        k.id_kandidat, 
        p.nama, 
        k.no_kandidat
    FROM kandidat k
    JOIN pengguna p ON p.id = k.pengguna_id
    WHERE p.role = 'kandidat'
    ORDER BY k.no_kandidat ASC
");
$kandidatList = $kandidatQuery->fetchAll(PDO::FETCH_ASSOC);

// ------------------
// QUERY SUARA PER KANDIDAT
// ------------------
$suaraQuery = $pdo->query("
    SELECT 
        kandidat_id,
        COUNT(*) AS total_suara
    FROM suara
    GROUP BY kandidat_id
");
$suaraData = $suaraQuery->fetchAll(PDO::FETCH_ASSOC);

// Total semua suara
$totalSuara = $pdo->query("SELECT COUNT(*) FROM suara")->fetchColumn();

// ------------------
// GABUNGKAN SUARA KE KANDIDAT
// ------------------
foreach ($kandidatList as $key => $k) {
    $total = 0;

    foreach ($suaraData as $s) {
        if ($s['kandidat_id'] == $k['id_kandidat']) {
            $total = $s['total_suara'];
        }
    }

    // Total suara
    $kandidatList[$key]['total_suara'] = $total;

    // Persentase
    $kandidatList[$key]['persentase'] = ($totalSuara > 0)
        ? number_format(($total / $totalSuara) * 100, 2)
        : 0;
}

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

        h1 {
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

</body>
</html>
