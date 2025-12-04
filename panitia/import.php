<?php
require '../koneksi.php'; // koneksi PDO
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_FILES['file_excel']['name'])) {

    $file_tmp = $_FILES['file_excel']['tmp_name'];

    // Load file excel
    $spreadsheet = IOFactory::load($file_tmp);
    $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

    $first = true;
    foreach ($sheet as $row) {
        if ($first) { $first = false; continue; } // skip header

        $nik            = $row['A'];
        $nama           = $row['B'];
        $tempat_lahir   = $row['C'];
        $tanggal_lahir  = $row['D'];
        $jenis_kelamin  = $row['E'];
        $pendidikan     = $row['F'];
        $pekerjaan      = $row['G'];
        $alamat         = $row['H'];
        $agama          = $row['I'];
        $status_pilih   = $row['J'];
        $role           = $row['K'];
        $password       = password_hash($row['L'], PASSWORD_DEFAULT); // Enkripsi password
        $status_ambil   = $row['M'];

        if (empty($nik)) continue; // skip jika kosong

        // Masukkan data pakai PDO prepared statement
        $query = $pdo->prepare("
            INSERT INTO pengguna 
            (nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, pendidikan, pekerjaan, alamat, agama, status_pilih, role, password, status_ambil)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $query->execute([
            $nik, $nama, $tempat_lahir, $tanggal_lahir, $jenis_kelamin,
            $pendidikan, $pekerjaan, $alamat, $agama, $status_pilih, $role, $password, $status_ambil
        ]);
    }

    echo "<script>
            alert('Data berhasil diimport!');
            window.location.href='pengguna2.php';
          </script>";
    exit;
}
?>
