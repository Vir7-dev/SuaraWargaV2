<?php
session_start();
require '../koneksi.php';

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    die("Anda harus login terlebih dahulu.");
}

$id_kandidat = $_POST['id_kandidat'] ?? null;
$token = $_POST['token'] ?? null;
$pengguna_id = $_SESSION['user_id'];

if (!$id_kandidat || !$token) {
    die("Token atau ID kandidat tidak valid.");
}

/* =======================================================
   1. VALIDASI TOKEN
=========================================================== */

$stmt = $pdo->prepare("SELECT * FROM token WHERE token_unik = ?");
$stmt->execute([$token]);
$tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
    die("Token tidak ditemukan!");
}

/* =======================================================
   2. CEK APAKAH USER SUDAH VOTING
=========================================================== */

$stmt = $pdo->prepare("SELECT COUNT(*) FROM suara WHERE pengguna_id = ?");
$stmt->execute([$pengguna_id]);
$sudah = $stmt->fetchColumn();

if ($sudah > 0) {
    die("Anda sudah melakukan voting sebelumnya!");
}

/* =======================================================
   3. SIMPAN SUARA
=========================================================== */

$stmt = $pdo->prepare("
    INSERT INTO suara (kandidat_id, pengguna_id, token_id, waktu, created_at, updated_at)
    VALUES (?, ?, ?, NOW(), NOW(), NOW())
");
$insert = $stmt->execute([
    $id_kandidat,
    $pengguna_id,
    $tokenData['id']
]);

if (!$insert) {
    die("Gagal menyimpan suara!");
}

/* =======================================================
   4. UPDATE STATUS PENGGUNA → 'sudah'
=========================================================== */

$stmt = $pdo->prepare("
    UPDATE pengguna 
    SET status_ambil = 'sudah', updated_at = NOW() 
    WHERE id = ?
");
$stmt->execute([$pengguna_id]);

/* =======================================================
   5. SELESAI
=========================================================== */

echo "<script>
    alert('Terima kasih, suara Anda sudah direkam.');
    window.location = 'index.php';
</script>";

?>
