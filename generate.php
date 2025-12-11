<?php
// FILE: generate_pdf.php
// Berisi: Konfigurasi Dompdf dan Proses Rendering PDF

// 1. Kebutuhan Library
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// 2. Kebutuhan Database
include "koneksi.php"; // Pastikan koneksi database ($pdo) tersedia

// 3. Menangkap id_periode dari URL
$id_periode = $_GET['id_periode'] ?? null;

// Pastikan tidak kosong
if (!$id_periode) {
    die("ID Periode tidak ditemukan!");
}

// 4. Menyediakan variabel agar bisa dipakai di hasil_pemilihan.php
// nanti di hasil_pemilihan.php kamu bisa pakai: $id_periode
$GLOBALS['id_periode'] = $id_periode;

// 5. Mengambil HTML Output
ob_start();
include "hasil_pemilihan.php"; // file ini bisa akses $id_periode
$html = ob_get_clean();

// 6. Konfigurasi Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'sans-serif');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// 7. Render PDF
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 8. Output PDF
$filename = "Laporan_Pemilihan_" . $id_periode . "_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]);

exit(0);
?>
