<?php
session_start();
require '../koneksi.php';



// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Anda harus login terlebih dahulu."
    ]);
    exit;
}

$kandidat_id = $_POST['kandidat_id'] ?? null;
$token       = $_POST['token'] ?? null;

// Validasi awal
if (!$kandidat_id) {
    echo json_encode([
        "success" => false,
        "message" => "ID kandidat tidak ditemukan."
    ]);
    exit;
}

if (!$token) {
    echo json_encode([
        "success" => false,
        "message" => "Token belum dimasukkan."
    ]);
    exit;
}

/* =======================================================
   CEK KANDIDAT TERDAFTAR
========================================================= */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM kandidat WHERE id_kandidat = ?");
$stmt->execute([$kandidat_id]);
$cekKandidat = $stmt->fetchColumn();

if ($cekKandidat == 0) {
    echo json_encode([
        "success" => false,
        "message" => "Kandidat tidak terdaftar."
    ]);
    exit;
}

/* =======================================================
   CEK TOKEN VALID
========================================================= */
$stmt = $pdo->prepare("SELECT * FROM token WHERE token_unik = ?");
$stmt->execute([$token]);
$tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
    echo json_encode([
        "success" => false,
        "message" => "Token tidak valid atau tidak ditemukan."
    ]);
    exit;
}

$token_id = $tokenData['id_token'] ?? $tokenData['id'] ?? null;

if (!$token_id) {
    echo json_encode([
        "success" => false,
        "message" => "Kolom ID token tidak ditemukan!"
    ]);
    exit;
}

/* =======================================================
   CEK TOKEN SUDAH DIPAKAI? (CEK BERDASARKAN token_id)
========================================================= */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM suara WHERE token_id = ?");
$stmt->execute([$token_id]);
$sudah = $stmt->fetchColumn();

if ($sudah > 0) {
    echo json_encode([
        "success" => false,
        "message" => "Token ini sudah digunakan untuk voting!"
    ]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO suara (kandidat_id, token_id, waktu, created_at, updated_at)
    VALUES (?, ?, NOW(), NOW(), NOW())
");
$insert = $stmt->execute([
    $kandidat_id,
    $token_id
]);

if (!$insert) {
    echo json_encode([
        "success" => false,
        "message" => "Gagal menyimpan suara!"
    ]);
    exit;
}

/* =======================================================
   UPDATE STATUS PILIH PENGGUNA
========================================================= */
$stmt = $pdo->prepare("UPDATE pengguna SET status_pilih = 'sudah' WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);

/* =======================================================
   RESPONSE BERHASIL
========================================================= */
echo "<script>alert('Terima kasih, suara Anda sudah direkam.'); window.location='index.php';</script>";

exit;
