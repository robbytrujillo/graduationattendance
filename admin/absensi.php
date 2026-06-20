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

function e($data)
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| FILTER
|--------------------------------------------------------------------------
*/

$cari    = isset($_GET['cari']) ? mysqli_real_escape_string($conn, trim($_GET['cari'])) : '';
$tanggal = isset($_GET['tanggal']) ? mysqli_real_escape_string($conn, trim($_GET['tanggal'])) : '';

$where = "WHERE 1=1";

if ($cari !== '') {
    $where .= " AND (
        u.nis LIKE '%$cari%'
        OR u.nama_siswa LIKE '%$cari%'
        OR u.kelas LIKE '%$cari%'
    )";
}

if ($tanggal !== '') {
    $where .= " AND a.tanggal='$tanggal'";
}

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$limit = 10;

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

$totalQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM absensi a
     JOIN users u ON a.user_id = u.id
     $where"
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

/*
|--------------------------------------------------------------------------
| DATA ABSENSI
|--------------------------------------------------------------------------
*/

$query = mysqli_query(
    $conn,
    "SELECT
        a.*,
        u.nis,
        u.nama_siswa,
        u.kelas
    FROM absensi a
    JOIN users u ON a.user_id = u.id
    $where
    ORDER BY a.id DESC
    LIMIT $start, $limit"
);

$queryString = '';

if ($cari !== '') {
    $queryString .= '&cari=' . urlencode($cari);
}

if ($tanggal !== '') {
    $queryString .= '&tanggal=' . urlencode($tanggal);
}

$no = $start + 1;

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Data Absensi Wisuda</title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
    body {
        background: #f4f6f9;
    }

    .navbar {
        background: #0F172A;
    }

    .sidebar {
        background: #1E293B;
        min-height: 100vh;
    }

    .sidebar a {
        color: #fff;
        display: block;
        padding: 12px 15px;
        text-decoration: none;
        transition: .3s;
    }

    .sidebar a:hover,
    .sidebar .active {
        background: #0d6efd;
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, .08);
    }

    .table-responsive {
        overflow-x: auto;
    }

    .table td,
    .table th {
        vertical-align: middle;
    }

    .badge-hadir {
        background: #28a745;
        color: white;
        padding: 6px 10px;
        border-radius: 20px;
    }

    @media(max-width:768px) {
        .sidebar {
            min-height: auto;
        }

        .btn-export {
            width: 100%;
            margin-top: 8px;
        }
    }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark">

        <a class="navbar-brand" href="dashboard.php">
            🎓 Graduation Attendance
        </a>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">

            <ul class="navbar-nav ml-auto">

                <li class="nav-item">
                    <span class="nav-link">
                        <i class="fas fa-user"></i>
                        <?= e($_SESSION['nama']); ?>
                    </span>
                </li>

                <li class="nav-item">
                    <a href="../logout.php" class="btn btn-danger btn-sm mt-1"
                        onclick="return confirm('Yakin ingin logout?')">
                        Logout
                    </a>
                </li>

            </ul>

        </div>

    </nav>

    <div class="container-fluid">

        <div class="row">

            <!-- SIDEBAR -->
            <div class="col-md-2 sidebar p-0">

                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>

                <a href="siswa.php">
                    <i class="fas fa-user-graduate"></i>
                    Data Siswa
                </a>

                <a href="upload_excel.php">
                    <i class="fas fa-file-excel"></i>
                    Upload Excel
                </a>

                <a href="absensi.php" class="active">
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

            <!-- CONTENT -->
            <div class="col-md-10">

                <div class="container-fluid mt-4 mb-5">

                    <div class="card">

                        <div class="card-header bg-primary text-white">

                            <div class="d-flex justify-content-between align-items-center flex-wrap">

                                <h5 class="mb-2 mb-md-0">
                                    <i class="fas fa-check-circle"></i>
                                    Data Absensi Wisuda
                                </h5>

                                <div>

                                    <a href="export_pdf.php" class="btn btn-danger btn-sm btn-export">
                                        <i class="fas fa-file-pdf"></i>
                                        PDF
                                    </a>

                                    <a href="export_excel.php" class="btn btn-success btn-sm btn-export">
                                        <i class="fas fa-file-excel"></i>
                                        Excel
                                    </a>

                                </div>

                            </div>

                        </div>

                        <div class="card-body">

                            <!-- FILTER -->
                            <form method="GET">

                                <div class="row">

                                    <div class="col-md-5 mb-2">
                                        <input type="text" name="cari" class="form-control"
                                            placeholder="Cari NIS, Nama, Kelas..." value="<?= e($cari); ?>">
                                    </div>

                                    <div class="col-md-3 mb-2">
                                        <input type="date" name="tanggal" class="form-control"
                                            value="<?= e($tanggal); ?>">
                                    </div>

                                    <div class="col-md-2 mb-2">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-search"></i>
                                            Cari
                                        </button>
                                    </div>

                                    <div class="col-md-2 mb-2">
                                        <a href="absensi.php" class="btn btn-secondary btn-block">
                                            <i class="fas fa-sync"></i>
                                            Reset
                                        </a>
                                    </div>

                                </div>

                            </form>

                            <hr>

                            <!-- TOTAL DATA -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Total Data Absensi:
                                <strong><?= number_format($totalData); ?></strong>
                            </div>

                            <!-- TABLE -->
                            <div class="table-responsive">

                                <table class="table table-bordered table-hover table-striped">

                                    <thead class="thead-dark">
                                        <tr>
                                            <th>No</th>
                                            <th>Hari</th>
                                            <th>Tanggal</th>
                                            <th>Jam</th>
                                            <th>NIS</th>
                                            <th>Nama</th>
                                            <th>Kelas</th>
                                            <th>Status</th>
                                            <th width="90">Aksi</th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php if (mysqli_num_rows($query) > 0) : ?>

                                        <?php while ($row = mysqli_fetch_assoc($query)) : ?>

                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= e($row['hari']); ?></td>
                                            <td><?= date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                                            <td><?= e($row['jam']); ?></td>
                                            <td><?= e($row['nis']); ?></td>
                                            <td><?= e($row['nama_siswa']); ?></td>
                                            <td><?= e($row['kelas']); ?></td>

                                            <td>
                                                <span class="badge-hadir">
                                                    <?= e($row['status']); ?>
                                                </span>
                                            </td>

                                            <td>
                                                <button class="btn btn-info btn-sm" data-toggle="modal"
                                                    data-target="#detail<?= $row['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- MODAL DETAIL -->
                                        <div class="modal fade" id="detail<?= $row['id']; ?>" tabindex="-1">

                                            <div class="modal-dialog">
                                                <div class="modal-content">

                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-info-circle"></i>
                                                            Detail Absensi
                                                        </h5>

                                                        <button type="button" class="close text-white"
                                                            data-dismiss="modal">
                                                            &times;
                                                        </button>
                                                    </div>

                                                    <div class="modal-body">

                                                        <table class="table table-bordered mb-0">

                                                            <tr>
                                                                <th width="35%">NIS</th>
                                                                <td><?= e($row['nis']); ?></td>
                                                            </tr>

                                                            <tr>
                                                                <th>Nama</th>
                                                                <td><?= e($row['nama_siswa']); ?></td>
                                                            </tr>

                                                            <tr>
                                                                <th>Kelas</th>
                                                                <td><?= e($row['kelas']); ?></td>
                                                            </tr>

                                                            <tr>
                                                                <th>Hari</th>
                                                                <td><?= e($row['hari']); ?></td>
                                                            </tr>

                                                            <tr>
                                                                <th>Tanggal</th>
                                                                <td><?= date('d-m-Y', strtotime($row['tanggal'])); ?>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <th>Jam</th>
                                                                <td><?= e($row['jam']); ?></td>
                                                            </tr>

                                                            <tr>
                                                                <th>Status</th>
                                                                <td>
                                                                    <span class="badge-hadir">
                                                                        <?= e($row['status']); ?>
                                                                    </span>
                                                                </td>
                                                            </tr>

                                                        </table>

                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-dismiss="modal">
                                                            Tutup
                                                        </button>
                                                    </div>

                                                </div>
                                            </div>

                                        </div>

                                        <?php endwhile; ?>

                                        <?php else : ?>

                                        <tr>
                                            <td colspan="9" class="text-center text-muted">
                                                <i class="fas fa-info-circle"></i>
                                                Tidak ada data absensi.
                                            </td>
                                        </tr>

                                        <?php endif; ?>

                                    </tbody>

                                </table>

                            </div>

                            <!-- PAGINATION -->
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
                                        ?>

                                    <?php if ($startPage > 1) : ?>
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
                                    <li class="page-item <?= ($page == $i) ? 'active' : ''; ?>">
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

        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>