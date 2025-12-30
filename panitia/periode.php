<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'panitia') {
    header("Location: ../login.php");
    exit;
}

require_once '../koneksi.php';
require_once '../auto_check_periode.php';

// ===========================
// INSERT (TAMBAH) - POST
// ===========================
if (isset($_POST['tambah'])) {

    $nama_periode   = trim($_POST['nama_periode']);
    $mulai          = trim($_POST['mulai']);
    $selesai        = trim($_POST['selesai']);
    $status_periode = $_POST['status_periode'] ?? 'tidak_aktif';

    try {

        // Cek apakah ada periode aktif
        $cekAktif = $pdo->query("SELECT COUNT(*) FROM periode WHERE status_periode = 'aktif'")
                        ->fetchColumn();

        // Cek apakah ada periode tidak aktif
        $cekTidakAktif = $pdo->query("SELECT COUNT(*) FROM periode WHERE status_periode = 'tidak_aktif'")
                             ->fetchColumn();


        // ============================================================
        // LOGIKA BARU (AND LOGIC)
        // TIDAK BOLEH TAMBAH APA PUN JIKA:
        // - ADA periode aktif  (cekAktif > 0)
        // - ATAU ADA periode tidak aktif (cekTidakAktif > 0)
        // ============================================================

        if ($cekAktif > 0 || $cekTidakAktif > 0) {
            header("Location: periode.php?err=" . urlencode(
                "Tidak dapat menambah periode baru. Selesaikan dulu periode sebelumnya."
            ));
            exit;
        }

        // ============================================================
        // JALANKAN INSERT (hanya jika keduanya kosong)
        // ============================================================
        $query = "INSERT INTO periode (nama_periode, mulai, selesai, status_periode)
                  VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$nama_periode, $mulai, $selesai, $status_periode]);

        header("Location: periode.php?msg=added");
        exit;

    } catch (PDOException $e) {
        header("Location: periode.php?err=" . urlencode("Gagal menambah periode."));
        exit;
    }
}


// ===========================
// UPDATE (EDIT) - POST
// ===========================
// ===========================
// UPDATE (EDIT) - POST
// ===========================
if (isset($_POST['edit'])) {
    $id_periode     = $_POST['id_periode'] ?? null;
    $nama_periode   = trim($_POST['nama_periode'] ?? '');
    $mulai          = trim($_POST['mulai'] ?? '');
    $selesai        = trim($_POST['selesai'] ?? '');
    $status_periode = trim($_POST['status_periode'] ?? 'tidak_aktif');

    if (!$id_periode || $nama_periode === '') {
        header("Location: periode.php?err=" . urlencode("ID dan Nama Periode wajib diisi."));
        exit;
    }

    try {
        // (EDIT) check: apakah ada periode aktif lain?
        if ($status_periode === 'aktif') {
            $checkQuery = "SELECT COUNT(*) FROM periode WHERE status_periode = 'aktif' AND id_periode != ?";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([$id_periode]);
            $activeCount = $checkStmt->fetchColumn();

            if ($activeCount > 0) {
                header("Location: periode.php?err=" . urlencode("Tidak dapat mengaktifkan periode, Sudah ada periode aktif saat ini."));
                exit;
            }
        }

        // UPDATE periode
        $query = "UPDATE periode SET
                    nama_periode = ?, mulai = ?, selesai = ?, status_periode = ?
                  WHERE id_periode = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$nama_periode, $mulai, $selesai, $status_periode, $id_periode]);

        // ===============================
        // RESET DIHAPUS - Sekarang dihandle oleh auto_check_periode.php
        // ===============================

        header("Location: periode.php?msg=updated");
        exit;

    } catch (PDOException $e) {
        header("Location: periode.php?err=" . urlencode("Gagal mengubah periode."));
        exit;
    }
}

// ===========================
// DELETE (HAPUS) - GET
// ===========================
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    try {
        $query = "DELETE FROM periode WHERE id_periode = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);

        header("Location: periode.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        header("Location: periode.php?err=" . urlencode("Gagal menghapus periode."));
        exit;
    }
}

// ===========================
// FETCH DATA UNTUK TABEL
// ===========================
try {
    $stmt = $pdo->query("SELECT id_periode, nama_periode, mulai, selesai, status_periode 
                         FROM periode 
                         ORDER BY status_periode asc, mulai desc");
    $periode_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $periode_list = [];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUARAWARGA - Data Periode</title>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../fontawesome/css/all.min.css">
</head>

<body class="bg">
    <!-- NAVBAR -->
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
                        <li class="nav-item">
                            <a href="pengguna.php" class="btn btn-dark"><i class="fa-solid fa-users me-2"></i>PENGGUNA</a>
                        </li>
                        <li class="nav-item">
                            <a href="periode.php" class="btn btn-dark"><i class="fa-solid fa-calendar-day me-2"></i>PERIODE</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modal-keluar" href="#"><i class="fa-solid fa-right-from-bracket me-2"></i>KELUAR</a>
                        </li>
                    </ul>
                </div>

            </div>
        </nav>
    </div>
    <div class="container mb-3">
        <!-- Alert Messages -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                if ($_GET['msg'] == 'added') echo 'Data periode berhasil ditambahkan!';
                if ($_GET['msg'] == 'updated') echo 'Data periode berhasil diubah!';
                if ($_GET['msg'] == 'deleted') echo 'Data periode berhasil dihapus!';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['err'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['err']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?> 

        <div class="row">
            <div class="col-8">
                <form class="d-flex" role="search" method="GET" action="periode.php">
                    <input name="q" class="form-control rounded-0 rounded-start-4 border-2 shadow" type="search" placeholder="Cari Nama Periode" aria-label="Search" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" />
                    <button class="btn btn-putih rounded-0 rounded-end-4 border-2" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>
            <div class="col-3 offset-1 text-end">
                <button type="button" class="btn btn-success poppins-bold shadow" data-bs-toggle="modal" data-bs-target="#modal-periode"><i class="fa-solid fa-circle-plus me-2"></i>TAMBAH</button>
            </div>
        </div>
        <div class="row">
            <div class="col-12 mt-3">
                <div class="table-responsive col-12 rounded-4 shadow">
                    <table class="poppins-medium bg-putih rounded-4 w-100">
                        <thead>
                            <tr class="bg-hijau">
                                <th>NAMA PERIODE</th>
                                <th>MULAI</th>
                                <th>BERAKHIR</th>
                                <th>STATUS</th>
                                <th style="width: 15%;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody id="t-body">
                            <?php if (!empty($periode_list)): ?>
                                <?php foreach ($periode_list as $data): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($data['nama_periode']) ?></td>
                                        <td><?= htmlspecialchars($data['mulai']) ?></td>
                                        <td><?= htmlspecialchars($data['selesai']) ?></td>
                                        <td>
                                            <?php if ($data['status_periode'] == 'aktif'): ?>
                                                <h6><span class="badge bg-success">Aktif</span></h6>
                                            <?php elseif ($data['status_periode'] == 'tidak_aktif'): ?>
                                                <h6><span class="badge bg-danger">Persiapan</span></h6>
                                            <?php else: ?>
                                                <h6><span class="badge bg-secondary">Berakhir</span></h6>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ($data['status_periode'] == 'berakhir'): ?>
                                                <button type="button"
                                                    class="btn btn-sm btn-success me-2"
                                                    onclick="window.location.href='../generate.php?id_periode=<?= htmlspecialchars($data['id_periode']) ?>'">
                                                    <i class="fa-solid fa-print"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button"
                                                    class="btn btn-sm btn-success me-2" disabled>
                                                    <i class="fa-solid fa-print"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($data['status_periode'] == 'tidak_aktif'): ?>
                                            <button type="button" class="btn btn-sm btn-warning me-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal-ubah"
                                                data-id="<?= htmlspecialchars($data['id_periode']) ?>"
                                                data-nama="<?= htmlspecialchars($data['nama_periode']) ?>"
                                                data-mulai="<?= htmlspecialchars($data['mulai']) ?>"
                                                data-selesai="<?= htmlspecialchars($data['selesai']) ?>"
                                                data-status="<?= htmlspecialchars($data['status_periode']) ?>">
                                                <i class="fa-solid fa-edit"></i>
                                            </button>
                                            <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-warning me-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal-ubah" disabled>
                                                <i class="fa-solid fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-danger me-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal-hapus"
                                                data-id-hapus="<?= htmlspecialchars($data['id_periode']) ?>">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada data periode.</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center align-items-center gap-2 mt-3">
                    <!-- placeholder pagination jika diperlukan nantinya -->
                </div>

            </div>
        </div>
    </div>

    <div class="container">
        <!-- Modal TAMBAH -->
        <div class="modal fade" id="modal-periode" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content rounded-4 bg-putih">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">Formulir Periode - Tambah</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="periode.php">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="col-form-label">Nama Periode <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_periode" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="col-form-label">Mulai <span class="text-danger">*</span></label>
                                    <input type="date" name="mulai" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="col-form-label">Berakhir <span class="text-danger">*</span></label>
                                    <input type="date" name="selesai" class="form-control" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="col-form-label">Status</label>
                                    <select name="status_periode" class="form-control">
                                        <option value="tidak_aktif" selected>Persiapan</option>
                                    </select>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" name="tambah" class="btn btn-success">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal UBAH -->
        <div class="modal fade" id="modal-ubah" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content rounded-4 bg-putih">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">Formulir Periode - Ubah</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="periode.php">
                            <input type="hidden" name="id_periode" id="ubah-id">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="col-form-label">Nama Periode <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_periode" id="ubah-nama" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="col-form-label">Mulai <span class="text-danger">*</span></label>
                                    <input type="date" name="mulai" id="ubah-mulai" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="col-form-label">Berakhir <span class="text-danger">*</span></label>
                                    <input type="date" name="selesai" id="ubah-selesai" class="form-control" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="col-form-label">Status</label>
                                    <select name="status_periode" id="ubah-status" class="form-control">
                                        <option value="aktif">Aktif</option>
                                        <option value="berakhir">Berakhir</option>
                                        <option value="tidak_aktif">T</option>
                                    </select>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" name="edit" class="btn btn-success">Ubah Data</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal HAPUS -->
        <div class="modal fade" id="modal-hapus" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content bg-putih rounded-4">
                    <div class="modal-body">
                        <div class="text-end">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="container-fluid">
                            <div class="row">
                                <h5 class="text-center mt-0 mb-3">Apakah Anda yakin ingin <b>Menghapus</b> Periode?</h5>
                            </div>
                            <div class="d-grid">
                                <a href="#" id="confirm-delete-btn" class="btn btn-success border-0 text-decoration-none text-center">YA</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal KELUAR -->
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
    </div>

    <script src="../bootstrap/js/bootstrap.bundle.js"></script>
    <script>
        // ------------------------------------
        // POPULATE MODAL UBAH DENGAN LOGIKA STATUS
        // ------------------------------------
        const modalUbah = document.getElementById('modal-ubah');

        modalUbah.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;

            // Ambil data
            const id = button.getAttribute('data-id');
            const nama = button.getAttribute('data-nama');
            const mulai = button.getAttribute('data-mulai');
            const selesai = button.getAttribute('data-selesai');
            const status = button.getAttribute('data-status');

            // Isi input teks biasa
            modalUbah.querySelector('#ubah-id').value = id || '';
            modalUbah.querySelector('#ubah-nama').value = nama || '';
            modalUbah.querySelector('#ubah-mulai').value = mulai || '';
            modalUbah.querySelector('#ubah-selesai').value = selesai || '';

            // Dropdown status
            const statusSelect = modalUbah.querySelector('#ubah-status');

            // Reset dropdown
            statusSelect.innerHTML = '';

            // Logika status sesuai keinginanmu
            if (status === 'tidak_aktif') {
                statusSelect.innerHTML += '<option value="tidak_aktif">Persiapan</option>';
                statusSelect.innerHTML += '<option value="aktif">Aktif</option>';

            } else if (status === 'aktif') {
                statusSelect.innerHTML += '<option value="aktif">Aktif</option>';
                statusSelect.innerHTML += '<option value="berakhir">Berakhir</option>';

            } else if (status === 'berakhir') {
                statusSelect.innerHTML += '<option value="berakhir">Berakhir</option>';
            }

            // Set value aktif
            statusSelect.value = status;
        });



        // ------------------------------------
        // MODAL HAPUS
        // ------------------------------------
        const modalHapus = document.getElementById('modal-hapus');

        modalHapus.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const idHapus = button.getAttribute('data-id-hapus');

            const btnYaHapus = modalHapus.querySelector('#confirm-delete-btn');
            btnYaHapus.href = 'periode.php?hapus=' + encodeURIComponent(idHapus);
        });
    </script>

</body>

</html>