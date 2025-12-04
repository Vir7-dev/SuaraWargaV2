<?php
session_start();
require '../koneksi.php';

// Ambil data dari form
$id_kandidat  = $_POST['id_kandidat'];     // id kandidat
$visi         = $_POST['visi'];
$misi         = $_POST['misi'];
$foto_lama    = $_POST['foto_lama'];       // nama foto lama

$nama_file_baru = $foto_lama; // default tetap foto lama

// =============================
// 1. Cek apakah user upload foto baru
// =============================
if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {

    $file_name   = $_FILES['foto_profil']['name'];
    $file_tmp    = $_FILES['foto_profil']['tmp_name'];
    $file_size   = $_FILES['foto_profil']['size'];

    $allowed_ext = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Validasi ekstensi
    if (!in_array($ext, $allowed_ext)) {
        die("Format file tidak didukung! Gunakan JPG atau PNG.");
    }

    // Validasi ukuran (maks 2MB)
    if ($file_size > 2 * 1024 * 1024) {
        die("Ukuran foto terlalu besar! Maksimal 2MB.");
    }

    // Buat nama file baru unik
    $nama_file_baru = "foto_" . time() . "_" . rand(1000, 9999) . "." . $ext;

    // Lokasi simpan file
    $upload_path = "../uploads/" . $nama_file_baru;

    // Pindahkan file
    if (!move_uploaded_file($file_tmp, $upload_path)) {
        die("Gagal mengupload foto!");
    }

    // Hapus foto lama jika ada
    if (!empty($foto_lama) && file_exists("../uploads/" . $foto_lama)) {
        unlink("../uploads/" . $foto_lama);
    }
}


// =============================
// 2. Update database
// =============================
try {

    $stmt = $pdo->prepare("
        UPDATE kandidat 
        SET visi = :visi,
            misi = :misi,
            foto_profil = :foto
        WHERE id_kandidat = :id
    ");

    $stmt->execute([
        ':visi' => $visi,
        ':misi' => $misi,
        ':foto' => $nama_file_baru,
        ':id'   => $id_kandidat
    ]);

    header("Location: profil.php");
    exit;

} catch(PDOException $e) {
    echo "Gagal update: " . $e->getMessage();
}