<?php
require_once '../config/config.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error   = '';

function e($data)
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

function redirectSiswa($message = '', $type = 'success')
{
    $params = [];

    if (isset($_GET['keyword']) && $_GET['keyword'] !== '') {
        $params['keyword'] = $_GET['keyword'];
    }

    if (isset($_GET['page']) && $_GET['page'] !== '') {
        $params['page'] = $_GET['page'];
    }

    if ($message !== '') {
        $params['message'] = $message;
        $params['type']    = $type;
    }

    $url = 'siswa.php';

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    header("Location: $url");
    exit;
}

/* =========================================================
   NOTIFIKASI
========================================================= */
if (isset($_GET['message'])) {
    if (isset($_GET['type']) && $_GET['type'] === 'error') {
        $error = $_GET['message'];
    } else {
        $success = $_GET['message'];
    }
}

/* =========================================================
   TAMBAH SISWA
========================================================= */
if (isset($_POST['tambah'])) {

    $nis        = mysqli_real_escape_string($conn, trim($_POST['nis']));
    $nama_siswa = mysqli_real_escape_string($conn, trim($_POST['nama_siswa']));
    $kelas      = mysqli_real_escape_string($conn, trim($_POST['kelas']));

    if ($nis === '' || $nama_siswa === '' || $kelas === '') {
        $error = "Semua data siswa wajib diisi.";
    } else {

        $cek = mysqli_query(
            $conn,
            "SELECT id FROM users WHERE nis='$nis' LIMIT 1"
        );

        if (mysqli_num_rows($cek) > 0) {
            $error = "NIS sudah terdaftar.";
        } else {

            $username = $nis;
            $password = password_hash("123456", PASSWORD_DEFAULT);

            $insert = mysqli_query(
                $conn,
                "INSERT INTO users (
                    nis,
                    nama_siswa,
                    kelas,
                    username,
                    password,
                    role,
                    created_at
                ) VALUES (
                    '$nis',
                    '$nama_siswa',
                    '$kelas',
                    '$username',
                    '$password',
                    'siswa',
                    NOW()
                )"
            );

            if ($insert) {
                redirectSiswa("Data siswa berhasil ditambahkan.");
            } else {
                $error = "Data siswa gagal ditambahkan.";
            }
        }
    }
}

/* =========================================================
   EDIT SISWA
========================================================= */
if (isset($_POST['edit'])) {

    $id         = (int) $_POST['id'];
    $nis        = mysqli_real_escape_string($conn, trim($_POST['nis']));
    $nama_siswa = mysqli_real_escape_string($conn, trim($_POST['nama_siswa']));
    $kelas      = mysqli_real_escape_string($conn, trim($_POST['kelas']));
    $username   = mysqli_real_escape_string($conn, trim($_POST['username']));

    if ($nis === '' || $nama_siswa === '' || $kelas === '' || $username === '') {
        $error = "Semua data siswa wajib diisi.";
    } else {

        $cek = mysqli_query(
            $conn,
            "SELECT id FROM users
             WHERE (nis='$nis' OR username='$username')
             AND id != '$id'
             LIMIT 1"
        );

        if (mysqli_num_rows($cek) > 0) {
            $error = "NIS atau username sudah digunakan siswa lain.";
        } else {

            $update = mysqli_query(
                $conn,
                "UPDATE users SET
                    nis='$nis',
                    nama_siswa='$nama_siswa',
                    kelas='$kelas',
                    username='$username'
                 WHERE id='$id'
                 AND role='siswa'"
            );

            if ($update) {
                redirectSiswa("Data siswa berhasil diperbarui.");
            } else {
                $error = "Data siswa gagal diperbarui.";
            }
        }
    }
}

/* =========================================================
   HAPUS SISWA
========================================================= */
if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    $hapus = mysqli_query(
        $conn,
        "DELETE FROM users
         WHERE id='$id'
         AND role='siswa'"
    );

    if ($hapus) {
        redirectSiswa("Data siswa berhasil dihapus.");
    } else {
        redirectSiswa("Data siswa gagal dihapus.", "error");
    }
}

/* =========================================================
   RESET PASSWORD
========================================================= */
if (isset($_GET['reset'])) {

    $id = (int) $_GET['reset'];

    $passwordBaru = password_hash('123456', PASSWORD_DEFAULT);

    $reset = mysqli_query(
        $conn,
        "UPDATE users
         SET password='$passwordBaru'
         WHERE id='$id'
         AND role='siswa'"
    );

    if ($reset) {
        redirectSiswa("Password siswa berhasil direset menjadi 123456.");
    } else {
        redirectSiswa("Password siswa gagal direset.", "error");
    }
}

/* =========================================================
   SEARCH
========================================================= */
$keyword = '';

if (isset($_GET['keyword'])) {
    $keyword = mysqli_real_escape_string($conn, trim($_GET['keyword']));
}

$where = "WHERE role='siswa'";

if ($keyword !== '') {
    $where .= " AND (
        nis LIKE '%$keyword%'
        OR nama_siswa LIKE '%$keyword%'
        OR kelas LIKE '%$keyword%'
        OR username LIKE '%$keyword%'
    )";
}

/* =========================================================
   PAGINATION
========================================================= */
$limit = 10;

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

$totalQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users $where"
);

$totalData = mysqli_fetch_assoc($totalQuery)['total'];

$totalPage = ceil($totalData / $limit);

if ($totalPage < 1) {
    $totalPage = 1;
}

if ($page > $totalPage) {
    $page = $totalPage;
}

$start = ($page - 1) * $limit;

$query = mysqli_query(
    $conn,
    "SELECT * FROM users
     $where
     ORDER BY id DESC
     LIMIT $start, $limit"
);

/* =========================================================
   STATISTIK
========================================================= */
$totalSiswaQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role='siswa'"
);

$totalSiswa = mysqli_fetch_assoc($totalSiswaQuery)['total'];

$no = $start + 1;

$queryString = '';

if ($keyword !== '') {
    $queryString = '&keyword=' . urlencode($keyword);
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa | Absensi Wisuda</title>

    <link rel="icon" type="image/png" href="../assets/img/logo.png">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
    body {
        background: #f4f6f9;
        overflow-x: hidden;
    }

    .navbar {
        background: #0F172A;
        min-height: 70px;
    }

    .sidebar {
        background: #1E293B;
        min-height: calc(100vh - 70px);
        transition: left .15s ease;
    }

    .sidebar a {
        color: #fff;
        display: block;
        padding: 14px 18px;
        text-decoration: none;
        font-size: 16px;
    }

    .sidebar a i {
        width: 25px;
    }

    .sidebar a:hover,
    .sidebar .active {
        background: #0d6efd;
        color: #fff;
    }

    .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 18px;
        color: white;
        border-bottom: 1px solid rgba(255, 255, 255, .15);
    }

    .sidebar-overlay {
        display: none;
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, .08);
    }

    .table td,
    .table th {
        vertical-align: middle;
    }

    .action-btn {
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {

        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            min-height: 100vh;
            z-index: 1060;
            overflow-y: auto;
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar-overlay.show {
            display: block;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 1050;
        }

        .content-area {
            width: 100%;
            flex: 0 0 100%;
            max-width: 100%;
        }

        .navbar-brand {
            font-size: 17px;
        }

        .user-name {
            display: none;
        }

        .btn-tambah {
            width: 100%;
            margin-top: 15px;
        }

        .action-btn .btn {
            margin-bottom: 3px;
        }
    }

    .content-area {
        min-height: calc(100vh - 70px);
        display: flex;
        flex-direction: column;
    }

    .content-wrapper {
        flex: 1;
    }

    .footer-text {
        padding: 18px 10px;
        margin-top: 25px;
        color: #64748B;
        border-top: 1px solid #e5e7eb;
        background: #fff;
    }
    </style>
</head>

<body>

    <nav class="navbar navbar-dark">

        <button type="button" class="btn btn-outline-light mr-2 d-md-none" id="btnSidebar">
            <i class="fas fa-bars"></i>
        </button>

        <a class="navbar-brand" href="dashboard.php">
            🎓 Graduation Attendance
        </a>

        <div class="ml-auto">

            <span class="text-white mr-3 user-name">
                <i class="fas fa-user"></i>
                <?= e($_SESSION['nama']); ?>
            </span>

            <a href="../logout.php" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin logout?')">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>

        </div>

    </nav>

    <div class="container-fluid">
        <div class="row">

            <div class="col-md-2 sidebar p-0" id="sidebar">

                <div class="sidebar-header d-md-none">
                    <strong>
                        <i class="fas fa-graduation-cap"></i>
                        Menu
                    </strong>

                    <button type="button" class="btn btn-danger btn-sm" id="btnCloseSidebar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>

                <a href="siswa.php" class="active">
                    <i class="fas fa-user-graduate"></i>
                    Data Siswa
                </a>

                <a href="upload_excel.php">
                    <i class="fas fa-file-excel"></i>
                    Upload Excel
                </a>

                <a href="scan_qrcode.php">
                    <i class="fas fa-qrcode mr-2"></i> Scan QR
                </a>

                <a href="absensi.php">
                    <i class="fas fa-check-circle"></i>
                    Data Absensi
                </a>

                <a href="export_pdf.php">
                    <i class="fas fa-file-pdf"></i>
                    Export PDF
                </a>

                <a href="export_excel.php">
                    <i class="fas fa-file-excel"></i>
                    Export Excel
                </a>

            </div>

            <div class="sidebar-overlay" id="sidebarOverlay"></div>

            <div class="col-md-10 content-area">

                <div class="content-wrapper">

                    <div class="container-fluid mt-4 mb-5">

                        <h3>
                            <i class="fas fa-user-graduate"></i>
                            Data Siswa
                        </h3>

                        <hr>

                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h2 class="text-primary"><?= $totalSiswa; ?></h2>
                                        <strong>Total Siswa</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($success !== '') : ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <strong>Berhasil!</strong> <?= e($success); ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                        <?php endif; ?>

                        <?php if ($error !== '') : ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <strong>Gagal!</strong> <?= e($error); ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                        <?php endif; ?>

                        <div class="card mb-4">
                            <div class="card-body">

                                <div class="row align-items-center mb-4">
                                    <div class="col-md-8">
                                        <form method="GET">
                                            <div class="input-group">
                                                <input type="text" name="keyword" class="form-control"
                                                    placeholder="Cari NIS, Nama, Kelas, atau Username..."
                                                    value="<?= e($keyword); ?>">

                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="submit">
                                                        <i class="fas fa-search"></i> Cari
                                                    </button>

                                                    <?php if ($keyword !== '') : ?>
                                                    <a href="siswa.php" class="btn btn-secondary">Reset</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- <div class="col-md-4 text-md-right">
                                        <button class="btn btn-success btn-tambah" data-toggle="modal"
                                            data-target="#modalTambah">
                                            <i class="fas fa-plus"></i> Tambah Siswa
                                        </button>
                                    </div> -->
                                </div>

                                <?php $modalHtml = ''; ?>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-hover mb-0">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th width="60">No</th>
                                                <th>NIS</th>
                                                <th>Nama</th>
                                                <th>Kelas</th>
                                                <th>Username</th>
                                                <th>Role</th>
                                                <th width="220">Aksi</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php if (mysqli_num_rows($query) > 0) : ?>
                                            <?php while ($row = mysqli_fetch_assoc($query)) : ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td><?= e($row['nis']); ?></td>
                                                <td><?= e($row['nama_siswa']); ?></td>
                                                <td><?= e($row['kelas']); ?></td>
                                                <td><?= e($row['username']); ?></td>
                                                <td>
                                                    <span class="badge badge-success">
                                                        <?= ucfirst(e($row['role'])); ?>
                                                    </span>
                                                </td>

                                                <td class="action-btn">
                                                    <button class="btn btn-info btn-sm" data-toggle="modal"
                                                        data-target="#detail<?= $row['id']; ?>" title="Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </button>

                                                    <button class="btn btn-warning btn-sm" data-toggle="modal"
                                                        data-target="#edit<?= $row['id']; ?>" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    <a href="?reset=<?= $row['id']; ?><?= $queryString; ?>&page=<?= $page; ?>"
                                                        class="btn btn-secondary btn-sm" title="Reset Password"
                                                        onclick="return confirm('Reset password menjadi 123456?')">
                                                        <i class="fas fa-key"></i>
                                                    </a>

                                                    <a href="?hapus=<?= $row['id']; ?><?= $queryString; ?>&page=<?= $page; ?>"
                                                        class="btn btn-danger btn-sm" title="Hapus"
                                                        onclick="return confirm('Yakin ingin menghapus siswa ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>

                                            <?php
                                                    $id = (int) $row['id'];
                                                    $nisModal = e($row['nis']);
                                                    $namaModal = e($row['nama_siswa']);
                                                    $kelasModal = e($row['kelas']);
                                                    $usernameModal = e($row['username']);
                                                    $roleModal = ucfirst(e($row['role']));

                                                    $modalHtml .= '
                                                    <div class="modal fade" id="detail' . $id . '" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-info text-white">
                                                                    <h5 class="modal-title">
                                                                        <i class="fas fa-eye"></i> Detail Siswa
                                                                    </h5>
                                                                    <button type="button" class="close text-white" data-dismiss="modal">
                                                                        &times;
                                                                    </button>
                                                                </div>

                                                                <div class="modal-body">
                                                                    <table class="table table-bordered mb-0">
                                                                        <tr>
                                                                            <th width="35%">NIS</th>
                                                                            <td>' . $nisModal . '</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Nama Siswa</th>
                                                                            <td>' . $namaModal . '</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Kelas</th>
                                                                            <td>' . $kelasModal . '</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Username</th>
                                                                            <td>' . $usernameModal . '</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th>Role</th>
                                                                            <td>' . $roleModal . '</td>
                                                                        </tr>
                                                                    </table>
                                                                </div>

                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                                        Tutup
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="modal fade" id="edit' . $id . '" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <form method="POST">
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-warning">
                                                                        <h5 class="modal-title">
                                                                            <i class="fas fa-edit"></i> Edit Siswa
                                                                        </h5>
                                                                        <button type="button" class="close" data-dismiss="modal">
                                                                            &times;
                                                                        </button>
                                                                    </div>

                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="id" value="' . $id . '">

                                                                        <div class="form-group">
                                                                            <label>NIS</label>
                                                                            <input type="text" name="nis" class="form-control"
                                                                                value="' . $nisModal . '" required>
                                                                        </div>

                                                                        <div class="form-group">
                                                                            <label>Nama Siswa</label>
                                                                            <input type="text" name="nama_siswa" class="form-control"
                                                                                value="' . $namaModal . '" required>
                                                                        </div>

                                                                        <div class="form-group">
                                                                            <label>Kelas</label>
                                                                            <input type="text" name="kelas" class="form-control"
                                                                                value="' . $kelasModal . '" required>
                                                                        </div>

                                                                        <div class="form-group mb-0">
                                                                            <label>Username</label>
                                                                            <input type="text" name="username" class="form-control"
                                                                                value="' . $usernameModal . '" required>
                                                                        </div>
                                                                    </div>

                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                                            Batal
                                                                        </button>

                                                                        <button type="submit" name="edit" class="btn btn-warning">
                                                                            <i class="fas fa-save"></i> Simpan Perubahan
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    ';
                                                    ?>
                                            <?php endwhile; ?>
                                            <?php else : ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    <i class="fas fa-info-circle"></i>
                                                    Data siswa tidak ditemukan.
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- MODAL DETAIL DAN EDIT DI LUAR TABEL -->
                                <?= $modalHtml; ?>

                                <?php if ($totalPage > 1) : ?>
                                <nav class="mt-4">
                                    <ul class="pagination justify-content-center mb-0">

                                        <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?= $page - 1; ?><?= $queryString; ?>">
                                                Previous
                                            </a>
                                        </li>

                                        <?php
                                            $startPage = max(1, $page - 2);
                                            $endPage   = min($totalPage, $page + 2);

                                            if ($startPage > 1) :
                                            ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1<?= $queryString; ?>">1</a>
                                        </li>

                                        <?php if ($startPage > 2) : ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                        <?php endif; ?>
                                        <?php endif; ?>

                                        <?php for ($i = $startPage; $i <= $endPage; $i++) : ?>
                                        <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?= $i; ?><?= $queryString; ?>">
                                                <?= $i; ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>

                                        <?php if ($endPage < $totalPage) : ?>
                                        <?php if ($endPage < $totalPage - 1) : ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                        <?php endif; ?>

                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $totalPage; ?><?= $queryString; ?>">
                                                <?= $totalPage; ?>
                                            </a>
                                        </li>
                                        <?php endif; ?>

                                        <li class="page-item <?= ($page >= $totalPage) ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?page=<?= $page + 1; ?><?= $queryString; ?>">
                                                Next
                                            </a>
                                        </li>

                                    </ul>
                                </nav>
                                <?php endif; ?>

                            </div>
                        </div>

                    </div>

                </div>

                <footer class="text-center footer-text">
                    <div class="small">
                        Copyright &copy; <?= date('Y'); ?>
                        <a href="https://robbyilham.com/" style="text-decoration: none" target="_blank">
                            by
                        </a>
                        IT Development IHBS
                    </div>
                </footer>

            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH -->
    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-user-plus"></i> Tambah Siswa
                        </h5>

                        <button type="button" class="close text-white" data-dismiss="modal">
                            &times;
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label>NIS</label>
                            <input type="text" name="nis" class="form-control" placeholder="Masukkan NIS" required>
                        </div>

                        <div class="form-group">
                            <label>Nama Siswa</label>
                            <input type="text" name="nama_siswa" class="form-control" placeholder="Masukkan nama siswa"
                                required>
                        </div>

                        <div class="form-group mb-0">
                            <label>Kelas</label>
                            <input type="text" name="kelas" class="form-control" placeholder="Contoh: XII IPA 1"
                                required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" name="tambah" class="btn btn-success">
                            <i class="fas fa-save"></i> Simpan Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
    $(document).ready(function() {

        function bukaSidebar() {
            $('#sidebar').addClass('show');
            $('#sidebarOverlay').addClass('show');
        }

        function tutupSidebar() {
            $('#sidebar').removeClass('show');
            $('#sidebarOverlay').removeClass('show');
        }

        $('#btnSidebar').on('click', function() {
            if ($('#sidebar').hasClass('show')) {
                tutupSidebar();
            } else {
                bukaSidebar();
            }
        });

        $('#btnCloseSidebar').on('click', function() {
            tutupSidebar();
        });

        $('#sidebarOverlay').on('click', function() {
            tutupSidebar();
        });

        $('.sidebar a').on('click', function() {
            if ($(window).width() < 768) {
                tutupSidebar();
            }
        });

        $(window).on('resize', function() {
            if ($(window).width() >= 768) {
                tutupSidebar();
            }
        });

    });
    </script>

</body>

</html>