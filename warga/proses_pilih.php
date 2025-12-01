<?php
session_start();
require_once '../koneksi.php';

header('Content-Type: application/json');

// Validasi user login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'warga') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Ambil data POST
$kandidat_id = $_POST['kandidat_id'] ?? null;
$token_input = $_POST['token'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$kandidat_id || !$token_input) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

try {
    // CEK 1: Apakah user sudah pernah voting?
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM suara WHERE pengguna_id = ?");
    $stmt->execute([$user_id]);
    $sudah_voting = $stmt->fetchColumn();
    
    if ($sudah_voting > 0) {
        echo json_encode(['success' => false, 'message' => 'Anda sudah melakukan voting sebelumnya']);
        exit;
    }
    
    // CEK 2: Validasi token - Harus milik user ini dan sudah diambil
    $stmt = $pdo->prepare("SELECT id, token_unik, status_pengambilan, pengguna_id FROM token WHERE token_unik = ?");
    $stmt->execute([$token_input]);
    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$token_data) {
        echo json_encode(['success' => false, 'message' => 'Token tidak valid atau tidak ditemukan']);
        exit;
    }
    
    // CEK 3: Token harus sudah diambil (status 'sudah')
    if ($token_data['status_pengambilan'] == 'belum') {
        echo json_encode(['success' => false, 'message' => 'Token belum diambil atau tidak valid']);
        exit;
    }
    
    // CEK 4: Token harus milik user yang login
    if ($token_data['pengguna_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Token ini bukan milik Anda! Gunakan token Anda sendiri.']);
        exit;
    }
    
    // CEK 5: Validasi kandidat exists
    $stmt = $pdo->prepare("SELECT id_kandidat FROM kandidat WHERE id_kandidat = ?");
    $stmt->execute([$kandidat_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Kandidat tidak ditemukan']);
        exit;
    }
    
    // Mulai transaksi untuk konsistensi data
    $pdo->beginTransaction();
    
    try {
        // Simpan suara dengan token_id untuk audit trail
        $stmt = $pdo->prepare("INSERT INTO suara (kandidat_id, pengguna_id, waktu_voting) VALUES (?, ?, NOW())");
        $stmt->execute([$kandidat_id, $user_id]);
        
        // Opsional: Update token jadi "digunakan" (tambah kolom 'digunakan' jika perlu)
        // $stmt = $pdo->prepare("UPDATE token SET digunakan = 1 WHERE id = ?");
        // $stmt->execute([$token_data['id']]);
        
        // Commit transaksi
        $pdo->commit();
        
        // Hapus token dari session
        unset($_SESSION['token_id']);
        unset($_SESSION['token_unik']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Suara berhasil disimpan. Terima kasih telah berpartisipasi!'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyimpan suara: ' . $e->getMessage()
    ]);
}
?>