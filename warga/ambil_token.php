<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Sesi tidak valid. Silakan login kembali.',
        'show_in_modal' => true
    ]);
    exit;
}

require_once '../koneksi.php';

try {
    $user_id = $_SESSION['user_id'];
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Cek apakah user sudah pernah mengambil token
    $check_user = $pdo->prepare("
        SELECT status_ambil 
        FROM pengguna 
        WHERE id = ?
    ");
    $check_user->execute([$user_id]);
    $user_data = $check_user->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_data) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Data pengguna tidak ditemukan.',
            'show_in_modal' => true
        ]);
        exit;
    }
    
    // Jika user sudah mengambil token sebelumnya
    if ($user_data['status_ambil'] === 'sudah') {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Anda sudah pernah mengambil token sebelumnya. Setiap pengguna hanya dapat mengambil 1 token.',
            'show_in_modal' => true
        ]);
        exit;
    }
    
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
            'message' => 'Tidak ada token yang tersedia saat ini.',
            'show_in_modal' => true
        ]);
        exit;
    }
    
    // Update status token menjadi 'sudah'
    $update_token = $pdo->prepare("
        UPDATE token 
        SET status_pengambilan = 'sudah' 
        WHERE id = ?
    ");
    $update_token->execute([$token_data['id']]);
    
    // Update status_ambil user menjadi 'sudah'
    $update_user = $pdo->prepare("
        UPDATE pengguna 
        SET status_ambil = 'sudah' 
        WHERE id = ?
    ");
    $update_user->execute([$user_id]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'token' => $token_data['token_unik'],
        'message' => 'Token berhasil diambil. Simpan token ini dengan baik!'
    ]);
    
} catch (PDOException $e) {
    // Rollback jika ada error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        'show_in_modal' => true
    ]);
}
?>