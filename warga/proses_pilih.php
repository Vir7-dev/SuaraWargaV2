<?php
session_start();
require '../koneksi.php';

header('Content-Type: application/json');

// ================================
// VALIDASI USER LOGIN
// ================================
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'warga') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Silakan login terlebih dahulu.'
    ]);
    exit;
}

// ================================
// AMBIL DATA POST
// ================================
$kandidat_id = $_POST['id_kandidat'] ?? null; // konsisten dengan JS
$token_input = $_POST['token'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$kandidat_id || !$token_input) {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak lengkap. Kandidat dan token wajib diisi.'
    ]);
    exit;
}

try {
    // ================================
    // CEK 1: USER SUDAH VOTING?
    // ================================
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM suara WHERE pengguna_id = ?");
    $stmt->execute([$user_id]);
    $sudah_voting = $stmt->fetchColumn();

    if ($sudah_voting > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Anda sudah melakukan voting sebelumnya.'
        ]);
        exit;
    }

    // ================================
    // CEK 2: VALIDASI TOKEN
    // ================================
    $stmt = $pdo->prepare("SELECT id, status_pengambilan FROM token WHERE token_unik = ?");
    $stmt->execute([$token_input]);
    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$token_data) {
        echo json_encode([
            'success' => false,
            'message' => 'Token tidak valid atau tidak ditemukan.'
        ]);
        exit;
    }

    // Token harus sudah diambil
    if ($token_data['status_pengambilan'] != 'sudah') {
        echo json_encode([
            'success' => false,
            'message' => 'Token belum diambil atau sudah digunakan.'
        ]);
        exit;
    }

    // Token harus milik user
    if ($token_data['pengguna_id'] != $user_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Token ini bukan milik Anda.'
        ]);
        exit;
    }

    // ================================
    // CEK 3: KANDIDAT ADA?
    // ================================
    $stmt = $pdo->prepare("SELECT id_kandidat FROM kandidat WHERE id_kandidat = ?");
    $stmt->execute([$kandidat_id]);
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Kandidat tidak ditemukan.'
        ]);
        exit;
    }

    // ================================
    // CEK 4: SIMPAN SUARA DALAM TRANSAKSI
    // ================================
    $pdo->beginTransaction();
    try {
        // Simpan suara
        $stmt = $pdo->prepare("INSERT INTO suara (kandidat_id, pengguna_id, waktu) VALUES (?, ?, NOW())");
        $stmt->execute([$kandidat_id, $user_id]);

        // Update token jadi "digunakan"
        $stmt = $pdo->prepare("UPDATE token SET status_pengambilan = 'digunakan' WHERE id = ?");
        $stmt->execute([$token_data['id']]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Suara berhasil disimpan. Terima kasih telah berpartisipasi!'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan suara: ' . $e->getMessage()
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan database: ' . $e->getMessage()
    ]);
}
?>
