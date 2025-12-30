<?php
/**
 * AUTO CHECK PERIODE
 * Otomatis ubah status periode jadi 'berakhir' jika tanggal sudah lewat
 */

if (!isset($pdo)) {
    die("Koneksi database tidak ditemukan");
}

try {
    // CEK apakah ada periode yang perlu diubah
    $stmt = $pdo->query("SELECT COUNT(*) FROM periode 
                         WHERE status_periode = 'aktif' 
                         AND DATE(selesai) < CURDATE()");
    $jumlah_berakhir = $stmt->fetchColumn();
    
    // UPDATE status ke berakhir
    $pdo->exec("UPDATE periode 
                SET status_periode = 'berakhir' 
                WHERE status_periode = 'aktif' 
                AND DATE(selesai) < CURDATE()");
    
    // JALANKAN RESET jika ada yang berakhir
    if ($jumlah_berakhir > 0) {
        // Path disesuaikan dengan lokasi file
        $reset_file = __DIR__ . '/panitia/reset_pemilihan.php';
        if (file_exists($reset_file)) {
            include $reset_file;
        }
    }
    
} catch (PDOException $e) {
    // Silent error atau log
    // error_log("Auto check periode error: " . $e->getMessage());
}
?>