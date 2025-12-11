<?php
// FILE: hasil_pemilihan.php

// Ambil id_periode dari generate_pdf.php
$id_periode = $GLOBALS['id_periode'] ?? null;

if (!$id_periode) {
    die("ID Periode tidak ditemukan!");
}

// ===============================
// 1. Ambil daftar kandidat per PERIODE
// ===============================
$kandidatQuery = $pdo->prepare("
    SELECT 
        k.id_kandidat, 
        p.nama, 
        k.no_kandidat
    FROM kandidat k
    JOIN pengguna p ON p.id = k.pengguna_id
    WHERE k.id_periode = ?
    ORDER BY k.no_kandidat ASC
");
$kandidatQuery->execute([$id_periode]);
$kandidatList = $kandidatQuery->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// 2. Ambil jumlah suara per kandidat
// ===============================
$suaraQuery = $pdo->prepare("
    SELECT 
        kandidat_id,
        COUNT(*) AS total_suara
    FROM suara
    WHERE kandidat_id IN (
        SELECT id_kandidat FROM kandidat WHERE id_periode = ?
    )
    GROUP BY kandidat_id
");
$suaraQuery->execute([$id_periode]);
$suaraData = $suaraQuery->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// 3. Hitung total semua suara di periode tersebut
// ===============================
$totalSuaraQuery = $pdo->prepare("
    SELECT COUNT(*) 
    FROM suara
    WHERE kandidat_id IN (
        SELECT id_kandidat FROM kandidat WHERE id_periode = ?
    )
");
$totalSuaraQuery->execute([$id_periode]);
$totalSuara = $totalSuaraQuery->fetchColumn() ?? 0;

// ===============================
// 4. Proses data kandidat + hitung persentase
// ===============================
foreach ($kandidatList as $key => $k) {
    $total = 0;

    foreach ($suaraData as $s) {
        if ($s['kandidat_id'] == $k['id_kandidat']) {
            $total = $s['total_suara'];
        }
    }

    $kandidatList[$key]['total_suara'] = $total;

    $kandidatList[$key]['persentase'] = ($totalSuara > 0)
        ? number_format(($total / $totalSuara) * 100, 2)
        : 0;
}

// ===============================
// 5. Warna progress bar
// ===============================
$colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6610f2'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Perolehan Suara</title>
    <style>
        body { 
            font-family: sans-serif; 
            margin: 30px; 
            color: #333; 
            text-align: center;
        }
        
        .container {
            max-width: 700px;
            margin: 0 auto;
            text-align: left;
        }
        
        h1 { 
            color: #1e3d59; 
            font-size: 24px; 
            margin-bottom: 5px; 
            text-align: center;
        }

        h2 {
            text-align: center;
            margin-top: -10px;
            font-size: 18px;
            color: #444;
        }
        
        .date-info {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-bottom: 25px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }

        .total-info { 
            text-align: center; 
            margin: 20px 0; 
            padding: 10px; 
            background-color: #f0f8ff; 
            border: 1px solid #007bff; 
            border-radius: 5px; 
            font-size: 16px; 
            font-weight: bold; 
        }

        .card-container { 
            max-width: 600px;
            margin: 0 auto; 
            text-align: center;
        } 
        
        .card {
            background: #ffffff;
            padding: 15px;
            width: 100%;
            margin: 10px 0; 
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            page-break-inside: avoid;
            text-align: left;
            display: inline-block;
        }

        .card h3 { 
            font-size: 18px; 
            color: #007bff; 
            margin: 0 0 5px; 
            padding-bottom: 5px; 
        }
        
        .progress-bar { 
            height: 12px; 
            border-radius: 8px; 
        }

    </style>
</head>

<body>

    <div class="container">
        <h1>Laporan Resmi Perolehan Suara</h1>
        <h2>Periode: <?= htmlspecialchars($id_periode); ?></h2>

        <div class="date-info">
            Dicetak pada: <?= date('d F Y, H:i:s'); ?> WIB
        </div>
        
        <div class="total-info">
            Total Suara Masuk: <span><?= number_format($totalSuara, 0, ',', '.'); ?></span>
        </div>

        <div class="card-container">
            <?php 
            $i = 0;
            foreach ($kandidatList as $data): 
                $color = $colors[$i % count($colors)];
            ?>
                <div class="card" style="border-left: 5px solid <?= $color ?>;">
                    <h3><?= htmlspecialchars($data['nama']); ?> (No <?= $data['no_kandidat']; ?>)</h3>
                    
                    <p><strong>Jumlah Suara:</strong> <?= number_format($data['total_suara'], 0, ',', '.'); ?></p>

                    <p style="margin: 0; font-weight: 600;">Persentase Perolehan</p>
                    <div class="progress" style="background: #e9ecef; border-radius: 8px; overflow: hidden;">
                        <div class="progress-bar" 
                             style="width: <?= $data['persentase']; ?>%; background-color: <?= $color ?>;">
                        </div>
                    </div>

                    <p style="text-align:right; font-weight:bold; color:<?= $color ?>;">
                        <?= $data['persentase']; ?>%
                    </p>
                </div>
            <?php 
            $i++;
            endforeach; 
            ?>
        </div>

    </div>

</body>
</html>
