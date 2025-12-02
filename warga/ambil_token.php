<?php
session_start();
require_once '../koneksi.php';

header('Content-Type: application/json');

// Validasi user login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'warga') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek 0: Ambil dari session jika sudah ada
if (isset($_SESSION['token_unik'])) {
     echo json_encode([
         'success' => true,
         'token' => $_SESSION['token_unik'],
         'message' => 'Token Anda telah tersimpan di sesi.'
     ]);
     exit;
}

try {
    // CEK 1: Apakah user sudah pernah voting?
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM suara WHERE pengguna_id = ?");
    $stmt->execute([$user_id]);
    $sudah_voting = $stmt->fetchColumn();
    
    if ($sudah_voting > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Anda sudah melakukan voting. Token tidak dapat diambil lagi.',
            'sudah_voting' => true
        ]);
        exit;
    }
    
    // CEK 2: Apakah user sudah pernah mengambil token?
    $stmt = $pdo->prepare("SELECT token_unik, status_pengambilan FROM token WHERE id_pengguna = ?");
    $stmt->execute([$user_id]);
    $existing_token = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Jika user sudah punya token, tampilkan token yang sama
    if ($existing_token) {
        echo json_encode([
            'success' => true,
            'token' => $existing_token['token_unik'],
            'message' => 'Ini adalah token Anda yang sudah diambil sebelumnya'
        ]);
        exit;
    }
    
    // CEK 3: Ambil 1 token yang masih belum diambil siapapun
    $stmt = $pdo->query("SELECT id, token_unik FROM token WHERE status_pengambilan = 'belum' AND id_pengguna IS NULL ORDER BY RAND() LIMIT 1");
    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$token_data) {
        echo json_encode([
            'success' => false,
            'message' => 'Maaf, semua token sudah habis. Hubungi admin.'
        ]);
        exit;
    }
    
    // UPDATE: Assign token ke user ini
    $stmt = $pdo->prepare("UPDATE token SET 
        status_pengambilan = 'sudah', 
        id_pengguna = ?,
        waktu_diambil = NOW(),
        updated_at = NOW() 
        WHERE id = ?");
    $stmt->execute([$user_id, $token_data['id']]);
    
    // Simpan ke session
    $_SESSION['token_id'] = $token_data['id'];
    $_SESSION['token_unik'] = $token_data['token_unik'];
    
    echo json_encode([
        'success' => true,
        'token' => $token_data['token_unik'],
        'message' => 'Token berhasil diambil. Simpan baik-baik! Token ini hanya untuk Anda.'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>