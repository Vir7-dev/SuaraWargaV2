<?php
session_start();
require_once 'koneksi.php';

// pastikan NIK tersedia
$nik = $_SESSION['reset_nik'] ?? null;

if (!$nik) {
    header("Location: login.php");
    exit;
}

// Jika submit password baru
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password_baru = $_POST['password_baru'] ?? '';

    if (empty($password_baru)) {
        $error = "Password baru wajib diisi!";
    } else {
        $hashed = password_hash($password_baru, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE pengguna SET password = ? WHERE nik = ?");
        $stmt->execute([$hashed, $nik]);

        $_SESSION['reset_success'] = true;
        header("Location: reset-password.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SUARAWARGA - Reset</title>
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="fontawesome/css/all.min.css">
    <link rel="stylesheet" href="style.css" />

    <style>
        .toast-progress {
            height: 4px;
            width: 100%;
            background: linear-gradient(90deg, #0c8254, #4cc790, #d8e9d4);
            animation: shrink 3s linear forwards;
            border-radius: 0 0 5px 5px;
        }

        @keyframes shrink {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }
    </style>
</head>

<body class="bg">
    <div class="row align-items-center d-flex justify-content-end justify-content-lg-center flex-column-reverse flex-lg-row warp-login min-vh-100 m-0">
        <div class="col-lg-5 col-10 shadow-lg border rounded-4 p-0" style="background-color: #0c8254">
            <div class="p-5">
                <h2 class="mb-4 poppins-bold text-putih">RESET PASSWORD</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="text-white">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password_baru" id="password_input"
                        class="form-control w-100 mb-3 custom-input"
                        placeholder="Masukkan Password Baru"
                        required />

                    <button type="submit" class="btn btn-dark w-100">RESET PASSWORD</button>
                </form>
            </div>
        </div>

        <div class="col-lg-4 col-6 text-center mt-5">
            <img src="assets/img/logo.png" class="img-fluid" alt="">
        </div>
    </div>

    <?php if (isset($_SESSION['reset_success'])): ?>
        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div id="resetSuccessToast" class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto">Reset Password Sukses</strong>
                    <small>Baru saja</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    Password berhasil diubah! Anda akan segera diarahkan ke halaman Login.
                </div>
                <div class="toast-progress"></div>
            </div>
        </div>
    <?php unset($_SESSION['reset_success']);
    endif; ?>


    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const toastElement = document.getElementById('resetSuccessToast');
            const toast = new bootstrap.Toast(toastElement, {
                delay: 3000
            });

            toast.show();

            setTimeout(() => {
                window.location.href = "login.php";
            }, 3250);
        });
    </script>
</body>