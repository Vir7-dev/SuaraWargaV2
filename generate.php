<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;

ob_start(); // Mulai buffer output
include "hasil_pemilihan.php"; // load file external
$html = ob_get_clean(); // ambil isi dan hapus buffer

$dompdf = new Dompdf();
$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait'); // bisa portrait / landscape
$dompdf->render();

// jika true → download otomatis, false → tampil di browser
$dompdf->stream("laporan_pengguna.pdf", ["Attachment" => false]);
?>
