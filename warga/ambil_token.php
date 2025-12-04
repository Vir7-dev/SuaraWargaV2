<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Sesi tidak valid. Silakan login kembali.'
    ]);
    exit;
}

require_once '../koneksi.php';

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Cari token yang belum diambil
    $stmt = $pdo->prepare("
        SELECT id, token_unik 
        FROM token 
        WHERE status_pengambilan = 'belum' 
        LIMIT 1 
        FOR UPDATE
    ");
    
    $stmt->execute();
    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$token_data) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Tidak ada token yang tersedia saat ini.'
        ]);
        exit;
    }
    
    // Update status token menjadi 'sudah' (sesuai ENUM database)
    $update_stmt = $pdo->prepare("
        UPDATE token 
        SET status_pengambilan = 'sudah' 
        WHERE id = ?
    ");
    
    $update_stmt->execute([$token_data['id']]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'token' => $token_data['token_unik'],
        'message' => 'Token berhasil diambil.'
    ]);
    
} catch (PDOException $e) {
    // Rollback jika ada error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>