<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'warga') {
    header("Location: ../login.php");
    exit;
}

// Ambil error dari session jika ada
$error = '';
if (!empty($_SESSION['reset_error'])) {
    $error = $_SESSION['reset_error'];
    unset($_SESSION['reset_error']);
}

require_once '../koneksi.php';
require_once '../auto_check_periode.php';

// Inisialisasi variabel default
$periode_aktif = null;
$kandidat_aktif = [];
$suara_kandidat = [];
$label_kandidat = [];
$jumlah_suara = [];
$error_fetch = '';

try {

    // --- 1. Ambil periode aktif (hanya 1) ---
    $stmt = $pdo->query("SELECT * FROM periode WHERE status_periode = 'aktif' LIMIT 1");
    $periode_aktif = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika tidak ada periode aktif → selesai
    if (!$periode_aktif) {
        $kandidat_aktif = [];
        $suara_kandidat = [];
    } else {

        $id_periode = $periode_aktif['id_periode'];

        // --- 2. Ambil semua kandidat di periode aktif ---
        $stmt = $pdo->prepare("
            SELECT 
                k.id_kandidat,
                k.no_kandidat,
                k.jabatan,
                k.visi,
                k.misi,
                k.foto_profil,
                p.nama,
                p.nik,
                p.tempat_lahir,
                p.tanggal_lahir,
                p.jenis_kelamin,
                p.pendidikan,
                p.pekerjaan,
                p.alamat,
                p.agama
            FROM kandidat k
            JOIN pengguna p ON k.pengguna_id = p.id
            WHERE k.id_periode = ?
            ORDER BY k.no_kandidat ASC
        ");
        $stmt->execute([$id_periode]);
        $kandidat_aktif = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- 3. Ambil jumlah suara tiap kandidat ---
        $stmt_suara = $pdo->prepare("
            SELECT 
                k.id_kandidat,
                p.nama,
                COUNT(s.id_suara) AS total_suara
            FROM kandidat k
            LEFT JOIN suara s ON s.kandidat_id = k.id_kandidat
            LEFT JOIN pengguna p ON p.id = k.pengguna_id
            WHERE k.id_periode = ?
            GROUP BY k.id_kandidat, p.nama
        ");
        $stmt_suara->execute([$id_periode]);
        $suara_kandidat = $stmt_suara->fetchAll(PDO::FETCH_ASSOC);

        // Untuk ChartJS
        if (!empty($suara_kandidat)) {
            $label_kandidat = array_column($suara_kandidat, 'nama');
            $jumlah_suara = array_column($suara_kandidat, 'total_suara');
        }
    }
} catch (PDOException $e) {
    $error_fetch = "Gagal mengambil data: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUARAWARGA</title>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>

<body class="bg">
    <!-- Navbar -->
    <div class="container mb-5">
        <nav class="navbar navbar-expand-lg mt-2 mb-5">
            <div class="container d-flex align-items-center">

                <!-- Logo -->
                <a class="navbar-brand" href="#">
                    <img src="../assets/img/logo1.png" alt="Logo" style="width:170px;">
                </a>

                <!-- Toggle button -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Menu -->
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-2">
                        <li class="nav-item">
                            <a href="index.php" class="btn btn-dark"><i class="fa-solid fa-house me-2"></i>BERANDA</a>
                        </li>
                        <?php if ($periode_aktif): ?>
                            <li class="nav-item">
                                <a class="btn btn-dark"
                                    href="#"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modal-ambil-token">
                                    <i class="fa-solid fa-ticket me-2"></i>TOKEN
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modal-reset-password" href="#"><i class="fa-solid fa-key me-2"></i>RESET PASSWORD</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modal-keluar" href="#"><i class="fa-solid fa-right-from-bracket me-2"></i>KELUAR</a>
                        </li>
                    </ul>
                </div>

            </div>
        </nav>
    </div>

    <!-- Alert Success/Error -->
    <div class="container mb-4">
        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <strong>Error!</strong> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Success Alert -->
        <?php if (isset($_SESSION['reset_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-check-circle me-2"></i>
                <strong>Berhasil!</strong> Password Anda telah diubah.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['reset_success']); ?>
        <?php endif; ?>
    </div>

    <!-- Card Kandidat -->
    <div class="container mb-5">
        <?php if ($periode_aktif): ?>
            <h2 class="text-center poppins-bold mb-5">
                <?= htmlspecialchars($periode_aktif['nama_periode']) ?>
            </h2>
        <?php else: ?>
            <h2 class="text-center poppins-bold mb-5">Periode pemilihan belum dimulai</h2>
        <?php endif; ?>
        <div class="row mb-5">
            <?php if (!empty($kandidat_aktif)): ?>
                <?php foreach ($kandidat_aktif as $data): ?>
                    <div data-aos="flip-right" class="col-lg-3 col-md-5 col-11 mx-auto">
                        <div class="card rounded-4 card-bg mb-5">

                            <!-- Foto Kandidat -->
                            <img src="../uploads/<?= htmlspecialchars($data['foto_profil']) ?>"
                                class="card-img-top p-3 img-fit"
                                style="border-radius: 26px;"
                                alt="Foto Kandidat">

                            <div class="card-body">
                                <h1 class="card-title poppins-semibold">
                                    <?= htmlspecialchars($data['no_kandidat']) ?>
                                </h1>

                                <hr>
                                <p class="card-title poppins-semibold">Nama</p>
                                <p class="card-text"><?= htmlspecialchars($data['nama']) ?></p>

                                <hr>
                                <p class="card-title poppins-semibold">Pendidikan</p>
                                <?= htmlspecialchars($data['pendidikan']) ?>

                                <hr>
                                <p class="card-title poppins-semibold">Pekerjaan</p>
                                <p class="card-text"><?= htmlspecialchars($data['pekerjaan']) ?>

                                    <hr>
                                <p class="card-title poppins-semibold">Alamat</p>
                                <p class="card-text"><?= htmlspecialchars($data['alamat']) ?></p><br>

                                <div class="d-grid gap-1">
                                    <!-- Modal Profil Kandidat -->
                                    <a href="#" class="btn btn-dark" data-bs-toggle="modal"
                                        data-bs-target="#modal-profil-<?= htmlspecialchars($data['id_kandidat']) ?>">
                                        TAMPILKAN LEBIH
                                    </a>

                                    <?php
                                    $user_id = $_SESSION['user_id'];

                                    $stmt = $pdo->prepare("SELECT status_pilih FROM pengguna WHERE id = ?");
                                    $stmt->execute([$user_id]);
                                    $status_pilih = $stmt->fetchColumn();
                                    ?>

                                    <?php if ($status_pilih === 'sudah'): ?>

                                        <!-- Jika sudah memilih -->
                                        <a href="#" class="btn btn-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modal-sudah-vote">
                                            PILIH
                                        </a>

                                    <?php else: ?>

                                        <!-- Jika BELUM memilih -->
                                        <a href="#" class="btn btn-dark"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modal-pilih-<?= htmlspecialchars($data['id_kandidat']) ?>">
                                            PILIH
                                        </a>

                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Kandidat -->
                    <div class="modal fade" id="modal-profil-<?= $data['id_kandidat'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content bg-putih rounded-4">
                                <div class="modal-body">

                                    <div class="text-end">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-3 col-12">

                                            <img src="../uploads/<?= htmlspecialchars($data['foto_profil']) ?>"
                                                class="rounded-4 d-block mx-auto mb-3 img-fit">

                                            <h2 class="poppins-bold">No. <?= $data['no_kandidat'] ?></h2>

                                            <hr>
                                            <p class="poppins-bold">Nama</p>
                                            <p><?= $data['nama'] ?></p>

                                            <hr>
                                            <p class="poppins-bold">Tempat / Tanggal Lahir</p>
                                            <p><?= $data['tempat_lahir'] ?>, <?= $data['tanggal_lahir'] ?></p>

                                            <hr>
                                            <p class="poppins-bold">Jenis Kelamin</p>
                                            <?php echo ($data['jenis_kelamin'] === 'P') ? "Perempuan" : "Laki-laki"; ?>

                                            <hr>
                                            <p class="poppins-bold">Agama</p>
                                            <p><?= $data['agama'] ?></p>

                                        </div>

                                        <div class="col-lg-9 col-12">
                                            <h3 class="poppins-bold">Visi</h3>
                                            <p><?= nl2br(htmlspecialchars($data['visi'])) ?></p>

                                            <h3 class="poppins-bold mt-4">Misi</h3>
                                            <p><?= nl2br(htmlspecialchars($data['misi'])) ?></p>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>

            <?php endif; ?>

        </div>
    </div>

    <!-- Diagram -->
    <div class="container col-lg-12 col-10 mb-5">

        <?php if ($periode_aktif && !empty($label_kandidat) && !empty($jumlah_suara)): ?>

            <h2 class="text-center poppins-bold mb-5">
                Hasil <?= htmlspecialchars($periode_aktif['nama_periode']) ?>
            </h2>

            <div class="row p-3 py-4 gap-4 gap-md-0 rounded-4 card-bg">
                <div class="col-12 flex-md-row flex-column d-flex justify-content-between align-items-center mb-3">
                    <h2 class="text-left poppins-bold text-putih">Hasil Pemilihan Sementara</h2>
                </div>

                <!-- BAR CHART -->
                <div class="col-lg-8">
                    <div class="d-flex justify-content-around bg-chart gap-lg-4 gap-3 p-1 px-md-4 py-4 rounded-4 bg-putih h-100">

                        <canvas id="myChart" style="width: 100%;"></canvas>

                        <script>
                            var xValues = <?= json_encode($label_kandidat); ?>;
                            var yValues = <?= json_encode($jumlah_suara); ?>;
                            var barColors = ["red", "green", "blue", "orange", "brown"];

                            new Chart("myChart", {
                                type: "bar",
                                data: {
                                    labels: xValues,
                                    datasets: [{
                                        backgroundColor: barColors,
                                        data: yValues
                                    }]
                                },
                                options: {
                                    legend: {
                                        display: false
                                    },
                                    title: {
                                        display: true,
                                        text: "TOTAL SUARA"
                                    },
                                    scales: {
                                        yAxes: [{
                                            ticks: {
                                                beginAtZero: true
                                            }
                                        }]
                                    }
                                }
                            });
                        </script>

                    </div>
                </div>

                <!-- PIE / DONUT CHART -->
                <div class="col-lg-4">
                    <div class="d-flex justify-content-around bg-chart gap-lg-4 gap-3 p-1 px-md-4 py-4 rounded-4 bg-putih h-100">

                        <canvas id="myChart1" style="width: 100%; height: 100%;"></canvas>

                        <script>
                            var xValues = <?= json_encode($label_kandidat); ?>;
                            var yValues = <?= json_encode($jumlah_suara); ?>;
                            var barColors = ["red", "green", "blue", "orange", "brown"];

                            new Chart("myChart1", {
                                type: "doughnut",
                                data: {
                                    labels: xValues,
                                    datasets: [{
                                        backgroundColor: barColors,
                                        data: yValues
                                    }]
                                },
                                options: {
                                    legend: {
                                        display: true
                                    },
                                    title: {
                                        display: true,
                                        text: "TOTAL SUARA"
                                    }
                                }
                            });
                        </script>

                    </div>
                </div>

            </div>

        <?php else: ?>

        <?php endif; ?>

    </div>

    <!-- Modal -->
    <div class="container">
        <!-- Modal Ambil Token -->
        <div class="modal fade" id="modal-ambil-token" tabindex="-1" aria-labelledby="ambil-token" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-putih">
                    <div class="modal-body">
                        <div class="text-end">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="container-fluid">
                            <div class="row">
                                <h5 class="text-center mt-0 mb-3" id="modal-token-title">
                                    Ini adalah token Anda. <b>Harap simpan dengan baik</b> dan jangan dibagikan kepada orang lain!
                                </h5>
                                <div class="d-grid">
                                    <button type="button" id="btn-copy-token" class="btn btn-dark border-0">
                                        <i class="fa-solid fa-spinner fa-spin" id="loading-token"></i>
                                        <span id="token-display">Memuat token...</span>
                                    </button>
                                </div>
                                <small class="text-center mt-2 text-muted" id="modal-token-subtitle">Klik untuk menyalin token</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Reset Password -->
        <div class="modal fade" id="modal-reset-password" tabindex="-1" aria-labelledby="reset-password" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-putih rounded-4">
                    <div class="modal-body">
                        <div class="text-end">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="container-fluid">
                            <div class="row">
                                <h4 class="text-center mt-0 mb-3">
                                    Ganti Password
                                </h4>
                                <p class="text-center text-muted mb-4">
                                    Masukkan password baru Anda di bawah ini.
                                </p>

                                <form action="../reset-password.php" method="POST" class="px-3">
                                    <label class="form-label fw-semibold">Password Baru</label>
                                    <input type="password" name="password_baru"
                                        class="form-control mb-2"
                                        placeholder="Masukkan Password Baru (min. 6 karakter)"
                                        minlength="6"
                                        required>

                                    <small class="text-muted d-block mb-3">
                                        <i class="fa-solid fa-info-circle me-1"></i>
                                        Password minimal 6 karakter
                                    </small>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-dark border-0 py-2">
                                            <i class="fa-solid fa-key me-2"></i>Ganti Password
                                        </button>
                                    </div>
                                </form>

                                <small class="text-center mt-3 text-muted d-block">
                                    Pastikan password kuat dan mudah diingat 🔐
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Pilih Kandidat -->
        <?php foreach ($kandidat_aktif as $data): ?>
            <div class="modal fade" id="modal-pilih-<?= htmlspecialchars($data['id_kandidat']) ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content bg-putih rounded-4">
                        <div class="modal-body">
                            <div class="text-end">
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <h5 class="text-center mb-3">
                                Suara tidak dapat diubah setelah diberikan. Apakah yakin memilih?
                            </h5>

                            <div class="text-center mb-3">
                                <strong><?= htmlspecialchars($data['nama']) ?></strong>
                            </div>

                            <form id="form-vote-<?= htmlspecialchars($data['id_kandidat']) ?>"
                                action="pilih.php"
                                method="POST">

                                <div class="d-grid gap-2">
                                    <input type="text"
                                        name="token"
                                        id="token-input-<?= htmlspecialchars($data['id_kandidat']) ?>"
                                        placeholder="Masukkan Token Anda"
                                        class="btn btn-dark mb-2"
                                        required
                                        maxlength="8"
                                        pattern="[a-zA-Z0-9]{5,8}">

                                    <!-- kandidat_id harus dikirim manual karena submitVote sudah dihapus -->
                                    <input type="hidden"
                                        name="kandidat_id"
                                        value="<?= htmlspecialchars($data['id_kandidat']) ?>">

                                    <button type="submit" class="btn btn-dark border-0">
                                        <span class="btn-text">YA, PILIH</span>
                                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Modal Keluar -->
        <div class="modal fade" id="modal-keluar" tabindex="-1" aria-labelledby="keluar" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-putih">
                    <div class="modal-body">
                        <div class="text-end">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="container-fluid">
                            <div class="row">
                                <h5 class="text-center mt-0 mb-3">Apakah Anda ingin keluar dari website <b>Suara
                                        Warga</b>?</h5>
                                <div class="d-grid">
                                    <button type="button" onclick="window.location.href='../login.php'" class="btn btn-dark border-0">YA</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal: Sudah Vote -->
        <div class="modal fade" id="modal-sudah-vote" tabindex="-1" aria-labelledby="sudah-vote" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-putih">
                    <div class="modal-body text-center">
                        <div class="text-end">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="container-fluid">
                            <div class="row">
                                <h5 class="mb-3">
                                    Anda sudah melakukan voting.<br>
                                    <b>Terima kasih atas partisipasi Anda!</b>
                                </h5>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Script -->
    <!-- Script -->
    <script src="../bootstrap/js/bootstrap.bundle.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>

    <script>
        let userToken = null;

        // Ambil token saat modal dibuka
        document.getElementById('modal-ambil-token').addEventListener('show.bs.modal', function() {
            // Reset display setiap kali modal dibuka
            const btnCopy = document.getElementById('btn-copy-token');
            const loading = document.getElementById('loading-token');
            const display = document.getElementById('token-display');

            // Reset ke state awal
            btnCopy.className = 'btn btn-dark border-0';
            loading.classList.remove('d-none');
            display.textContent = 'Memuat token...';

            if (userToken) {
                displayToken(userToken);
                return;
            }

            fetch('ambil_token.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        userToken = data.token;
                        displayToken(data.token);
                    } else {
                        // Tampilkan error di dalam modal
                        displayError(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    displayError('Terjadi kesalahan saat mengambil token');
                });
        });

        // Tampilkan token dan enable copy
        // Tampilkan token dan enable copy
        function displayToken(token) {
            const loading = document.getElementById('loading-token');
            const display = document.getElementById('token-display');
            const btnCopy = document.getElementById('btn-copy-token');
            const title = document.getElementById('modal-token-title');
            const subtitle = document.getElementById('modal-token-subtitle');

            // Show title for success
            if (title) {
                title.innerHTML = 'Ini adalah token Anda. <b>Harap simpan dengan baik</b> dan jangan dibagikan kepada orang lain!';
            }

            // Show subtitle
            if (subtitle) {
                subtitle.classList.remove('d-none');
            }

            // Show button
            if (btnCopy) {
                btnCopy.classList.remove('d-none');
                btnCopy.className = 'btn btn-dark border-0';
            }

            if (loading) loading.classList.add('d-none');
            if (display) display.innerHTML = `<i class="fa-solid fa-copy me-2"></i>${token}`;

            if (btnCopy) {
                btnCopy.onclick = function() {
                    copyToClipboard(token);
                };
            }
        }

        // Tampilkan error di dalam modal
        function displayError(message) {
            const loading = document.getElementById('loading-token');
            const display = document.getElementById('token-display');
            const btnCopy = document.getElementById('btn-copy-token');
            const title = document.getElementById('modal-token-title');
            const subtitle = document.getElementById('modal-token-subtitle');

            // Change title for error
            if (title) {
                title.innerHTML = message;
            }

            // Hide subtitle
            if (subtitle) {
                subtitle.classList.add('d-none');
            }

            if (loading) loading.classList.add('d-none');

            // Hide the button completely
            if (btnCopy) btnCopy.classList.add('d-none');

            // Clear the display area
            if (display) {
                display.innerHTML = '';
            }
        }

        // Copy token ke clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Tampilkan feedback sukses
                const btnCopy = document.getElementById('btn-copy-token');
                const originalHTML = btnCopy.innerHTML;

                btnCopy.innerHTML = '<i class="fa-solid fa-check me-2"></i>Tersalin!';
                btnCopy.classList.add('btn-success');
                btnCopy.classList.remove('btn-dark');

                setTimeout(() => {
                    btnCopy.innerHTML = originalHTML;
                    btnCopy.classList.remove('btn-success');
                    btnCopy.classList.add('btn-dark');
                }, 1500);
            }).catch(function(err) {
                // Fallback untuk browser lama
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);

                const btnCopy = document.getElementById('btn-copy-token');
                const originalHTML = btnCopy.innerHTML;

                btnCopy.innerHTML = '<i class="fa-solid fa-check me-2"></i>Tersalin!';
                btnCopy.classList.add('btn-success');
                btnCopy.classList.remove('btn-dark');

                setTimeout(() => {
                    btnCopy.innerHTML = originalHTML;
                    btnCopy.classList.remove('btn-success');
                    btnCopy.classList.add('btn-dark');
                }, 1500);
            });
        }

        // Submit voting
        function submitVote(event, kandidatId) {
            event.preventDefault();

            const form = event.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.spinner-border');
            const tokenInput = form.querySelector('input[name="token"]');

            // Disable button dan tampilkan loading
            submitBtn.disabled = true;
            btnText.classList.add('d-none');
            spinner.classList.remove('d-none');

            const formData = new FormData();
            formData.append('kandidat_id', kandidatId);
            formData.append('token', tokenInput.value);

            fetch('pilih.php', {
                    method: 'POST',
                    body: formData
                })
                .then(async response => {
                    const text = await response.text(); // Ambil response mentah
                    try {
                        return JSON.parse(text); // Coba parse JSON
                    } catch (e) {
                        console.error("Response bukan JSON:", text);
                        throw new Error("Server tidak mengembalikan JSON yang valid");
                    }
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: data.message
                        });
                    }

                    // Reset button
                    submitBtn.disabled = false;
                    btnText.classList.remove('d-none');
                    spinner.classList.add('d-none');
                })
                .catch(error => {
                    console.error("Error:", error);

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan saat memproses voting.'
                    });

                    // Reset button
                    submitBtn.disabled = false;
                    btnText.classList.remove('d-none');
                    spinner.classList.add('d-none');
                });

            return false;
        }
    </script>
    <script src="../script.js"></script>
</body>

</html>