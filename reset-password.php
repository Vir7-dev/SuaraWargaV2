<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['nik']) || !isset($_SESSION['user_role'])) {
    header("Location: login.php");
    exit;
}

$nik = $_SESSION['nik'];
$role = $_SESSION['user_role'];

// Helper function untuk redirect berdasarkan role
function redirectToIndex($role) {
    if ($role === 'panitia') {
        header("Location: panitia/index.php");
    } elseif ($role === 'kandidat') {
        header("Location: kandidat/index.php");
    } elseif ($role === 'warga') {
        header("Location: warga/index.php");
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password_baru = trim($_POST['password_baru'] ?? '');

    // Validasi input
    if ($password_baru === "") {
        $_SESSION['reset_error'] = "Password baru wajib diisi!";
        redirectToIndex($role);
    }
    
    if (strlen($password_baru) < 6) {
        $_SESSION['reset_error'] = "Password minimal 6 karakter!";
        redirectToIndex($role);
    }

    // Proses update password
    try {
        $hashed = password_hash($password_baru, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE pengguna SET password = ? WHERE nik = ?");
        $stmt->execute([$hashed, $nik]);

        // Cek apakah update berhasil
        if ($stmt->rowCount() > 0) {
            $_SESSION['reset_success'] = true;  // ← PASTIKAN INI ADA
        } else {
            $_SESSION['reset_error'] = "Gagal mengubah password. NIK tidak ditemukan.";
        }

    } catch (Exception $e) {
        $_SESSION['reset_error'] = "Terjadi kesalahan pada server, coba lagi nanti.";
    }

    // Redirect ke index
    redirectToIndex($role);
}

// Jika diakses langsung tanpa POST, redirect ke login
header("Location: login.php");
exit;
?>