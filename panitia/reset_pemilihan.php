<?php
include "../koneksi.php"; // hubungan ke database

try {

    // ===========================================================
    // 1. RESET STATUS PILIH & AMBIL UNTUK SEMUA PENGGUNA
    // ===========================================================
    $sql = "
        UPDATE pengguna 
        SET 
            status_pilih = 'belum',
            status_ambil = 'belum'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();


    // ===========================================================
    // 2. SET ROLE = 'warga' UNTUK SEMUA YANG ROLE NYA KANDIDAT
    // ===========================================================
    $sql2 = "
        UPDATE pengguna 
        SET role = 'warga'
        WHERE role = 'kandidat'
    ";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute();

    header("Location: index.php?reset=success");
    exit;

} catch (Exception $e) {
    echo "Terjadi kesalahan: " . $e->getMessage();
}