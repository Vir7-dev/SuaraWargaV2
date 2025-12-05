<?php
// FILE: hasil_pemilihan.php

// Pastikan 'koneksi.php' sudah di-include di generate_pdf.php sebelum file ini di-include.

// ------------------
// QUERY KANDIDAT & DATA PROCESSING (TIDAK BERUBAH)
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

$suaraQuery = $pdo->query("
    SELECT 
        kandidat_id,
        COUNT(*) AS total_suara
    FROM suara
    GROUP BY kandidat_id
");
$suaraData = $suaraQuery->fetchAll(PDO::FETCH_ASSOC);

$totalSuara = $pdo->query("SELECT COUNT(*) FROM suara")->fetchColumn();

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

$colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6610f2'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Perolehan Suara</title>
    <style>
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 400;
            src: url(https://fonts.gstatic.com/s/poppins/v20/pxiEyp8kSU5KZyEAAIzDQA.woff2) format('woff2');
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 700;
            src: url(https://fonts.gstatic.com/s/poppins/v20/pxiByp8kSU5KZyEAAIzHdA.woff2) format('woff2');
        }
        /* CSS yang ramah dompdf */
        body { 
            font-family: sans-serif; 
            margin: 30px; 
            color: #333; 
            /* PENTING: Gunakan text-align: center pada body untuk konten tengah */
            text-align: center;
        }
        
        /* Kontainer Utama untuk membatasi lebar */
        .container {
            max-width: 700px; /* Batasi lebar agar konten tidak terlalu lebar di PDF */
            margin: 0 auto; /* Tengah-kan kontainer utama */
            text-align: left; /* Kembalikan alignment teks di dalam kontainer ke kiri */
        }
        
        h1 { 
            color: #1e3d59; 
            font-size: 24px; 
            margin-bottom: 5px; 
            text-align: center;
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
            display: block; 
            /* Menggunakan margin negatif dan lebar kontainer untuk meniru flex/gap */
            margin: 0 auto; 
            text-align: center; /* PENTING: Untuk menengahkan float elements */
        } 
        
        .card {
            background: #ffffff;
            padding: 15px;
            width: 100%; 
            margin: 10px 1%; 
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            page-break-inside: avoid;
            text-align: left; /* Teks di dalam card tetap rata kiri */
            display: inline-block; /* Alternatif untuk float di dompdf */
        }

        .card h3 { 
            font-size: 18px; 
            color: #007bff; 
            margin: 0 0 5px; 
            padding-bottom: 5px; 
        }
        
        .progress-bar { height: 12px; border-radius: 8px; }
        .clearfix::after { content: ""; clear: both; display: table; }

    </style>
</head>

<body>

    <div class="container">
        <h1>Laporan Resmi Perolehan Suara Kandidat</h1>
        
        <div class="date-info">
            Dicetak pada: <?= date('d F Y, H:i:s'); ?> WIB
        </div>
        
        <div class="total-info">
            Total Suara Masuk: <span><?= number_format($totalSuara, 0, ',', '.'); ?></span>
        </div>

        <div class="card-container clearfix">
            <?php 
            $i = 0;
            foreach ($kandidatList as $data): 
            $color = $colors[$i % count($colors)];
            ?>
                <div class="card" style="border-left: 5px solid <?= $color ?>;">
                    <h3><?= htmlspecialchars($data['nama']); ?> (No <?= $data['no_kandidat']; ?>)</h3>
                    
                    <p><strong>Jumlah Suara:</strong> <?= number_format($data['total_suara'], 0, ',', '.'); ?></p>

                    <div class="progress-section">
                        <span style="float: right; font-weight: bold; color: <?= $color ?>;"><?= $data['persentase']; ?>%</span>
                        <p style="margin: 0; font-weight: 600; margin-bottom: 10px;">Persentase Perolehan</p>
                        <div class="progress" style="background: #e9ecef; border-radius: 8px; overflow: hidden;">
                            <div class="progress-bar" style="width: <?= $data['persentase']; ?>%; background-color: <?= $color ?>;">
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
            $i++;
            endforeach; ?>
        </div>
        <div class="clearfix"></div> 
    </div>

</body>
</html>