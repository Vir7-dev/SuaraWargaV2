<?php
echo "<div style='background:yellow;padding:10px;border:2px solid red;'>
🔥 AUTO CHECK PERIODE DIJALANKAN!
</div>";

if (!isset($pdo)) {
    die("❌ Koneksi database tidak ditemukan");
}

try {
    $query = "UPDATE periode 
              SET status_periode = 'berakhir' 
              WHERE status_periode = 'aktif' 
              AND DATE(selesai) < CURDATE()";
    
    $affected = $pdo->exec($query);
    
    echo "<div style='background:lime;padding:10px;'>
    ✅ Query dijalankan! Rows affected: $affected
    </div>";
    
} catch (PDOException $e) {
    echo "<div style='background:red;color:white;padding:10px;'>
    ❌ ERROR: " . $e->getMessage() . "
    </div>";
}
?>