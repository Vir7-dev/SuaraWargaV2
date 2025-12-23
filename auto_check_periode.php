<?php
/**
 * AUTO CHECK PERIODE
 * Otomatis ubah status periode jadi 'berakhir' jika tanggal sudah lewat
 */

if (!isset($pdo)) {
    die("Koneksi database tidak ditemukan");
}

try {
    $pdo->exec("UPDATE periode 
                SET status_periode = 'berakhir' 
                WHERE status_periode = 'aktif' 
                AND DATE(selesai) < CURDATE()");
} catch (PDOException $e) {
    // Silent error
}
?>