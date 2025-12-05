<?php
// FILE: generate_pdf.php
// Berisi: Konfigurasi Dompdf dan Proses Rendering PDF

// 1. Kebutuhan Library
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options; // <--- BARIS INI YANG HARUS DITAMBAHKAN!

// 2. Kebutuhan Database
include "koneksi.php"; // Pastikan koneksi database ($pdo) tersedia

// 3. Mengambil HTML Output
ob_start(); // Mulai buffer output
include "hasil_pemilihan.php"; // Load file yang berisi logika data dan HTML
$html = ob_get_clean(); // Ambil isi dan hapus buffer

// 4. Konfigurasi Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);

$options->set('isRemoteEnabled', true); 
$options->set('defaultFont', 'sans-serif'); 

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// 5. Render PDF
$dompdf->setPaper('A4', 'portrait'); // Bisa portrait atau landscape
$dompdf->render();

// 6. Output PDF
$filename = "Laporan_Pemilihan_" . date('Ymd_His') . ".pdf";
// jika true → download otomatis, false → tampil di browser
$dompdf->stream($filename, ["Attachment" => false]);

exit(0);
?>