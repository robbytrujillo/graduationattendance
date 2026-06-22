<?php
require_once '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

function e($data)
{
    return htmlspecialchars((string) ($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function hariIndonesia($hari)
{
    $daftarHari = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu'
    ];

    return $daftarHari[$hari] ?? $hari;
}

/*
|--------------------------------------------------------------------------
| Filter
|--------------------------------------------------------------------------
*/
$cari    = trim($_GET['cari'] ?? '');
$tanggal = trim($_GET['tanggal'] ?? '');

$where = "WHERE 1=1";
$params = [];
$types  = '';

if ($cari !== '') {
    $where .= " AND (
        u.nis LIKE ?
        OR u.nama_siswa LIKE ?
        OR u.kelas LIKE ?
    )";

    $keyword = '%' . $cari . '%';

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;

    $types .= 'sss';
}

if ($tanggal !== '') {
    $where .= " AND a.tanggal = ?";

    $params[] = $tanggal;
    $types .= 's';
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/
$limit = 10;
$page  = max(1, (int) ($_GET['page'] ?? 1));

$sqlTotal = "
    SELECT COUNT(*) AS total
    FROM absensi a
    INNER JOIN users u ON a.user_id = u.id
    $where
";

$stmtTotal = mysqli_prepare($conn, $sqlTotal);

if ($types !== '') {
    mysqli_stmt_bind_param($stmtTotal, $types, ...$params);
}

mysqli_stmt_execute($stmtTotal);

$totalResult = mysqli_stmt_get_result($stmtTotal);
$totalData   = (int) mysqli_fetch_assoc($totalResult)['total'];

$totalPage = max(1, (int) ceil($totalData / $limit));
$page      = min($page, $totalPage);
$start     = ($page - 1) * $limit;

/*
|--------------------------------------------------------------------------
| Data Absensi
|--------------------------------------------------------------------------
*/
$sqlData = "
    SELECT
        a.id,
        a.hari,
        a.tanggal,
        a.jam,
        a.status,
        u.nis,
        u.nama_siswa,
        u.kelas
    FROM absensi a
    INNER JOIN users u ON a.user_id = u.id
    $where
    ORDER BY a.id DESC
    LIMIT ?, ?
";

$stmtData = mysqli_prepare($conn, $sqlData);

$dataParams = $params;
$dataTypes  = $types . 'ii';

$dataParams[] = $start;
$dataParams[] = $limit;

mysqli_stmt_bind_param($stmtData, $dataTypes, ...$dataParams);
mysqli_stmt_execute($stmtData);

$query = mysqli_stmt_get_result($stmtData);

$queryString = http_build_query([
    'cari'    => $cari,
    'tanggal' => $tanggal
]);

$no = $start + 1;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Data Absensi Wisuda</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
    :root {
        --navy: #0f172a;
        --sidebar: #1e293b;
        --primary: #0d6efd;
        --bg: #f4f6f9;
    }

    * {
        font-family: "Poppins", sans-serif;
    }

    body {
        background: var(--bg);
        overflow-x: hidden;
    }

    .navbar {
        min-height: 56px;
        background: var(--navy);
        position: sticky;
        top: 0;
        z-index: 1060;
    }

    .navbar-brand {
        font-size: 18px;
        font-weight: 600;
    }

    .sidebar {
        background: var(--sidebar);
        min-height: calc(100vh - 56px);
        transition: left .3s ease;
    }

    .sidebar a {
        color: #fff;
        display: block;
        padding: 13px 16px;
        text-decoration: none;
        font-size: 14px;
        transition: .2s ease;
    }

    .sidebar a:hover,
    .sidebar .active {
        background: var(--primary);
        color: #fff;
    }

    .content-area {
        min-height: calc(100vh - 56px);
    }

    .main-card {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, .08);
    }

    .main-card .card-header {
        padding: 16px 20px;
    }

    .table-responsive {
        border-radius: 10px;
    }

    .table {
        margin-bottom: 0;
    }

    .table td,
    .table th {
        vertical-align: middle;
        white-space: nowrap;
    }

    .badge-hadir {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        background: #28a745;
        color: #fff;
        font-size: 11px;
        font-weight: 600;
    }

    .btn-detail {
        width: 34px;
        height: 34px;
        padding: 0;
        border-radius: 8px;
    }

    #overlay {
        display: none;
    }

    @media (max-width: 768px) {
        .sidebar {
            position: fixed;
            top: 56px;
            left: -270px;
            width: 250px;
            height: calc(100vh - 56px);
            min-height: auto;
            z-index: 1070;
            overflow-y: auto;
            box-shadow: 3px 0 15px rgba(0, 0, 0, .25);
        }

        .sidebar.show {
            left: 0;
        }

        #overlay {
            position: fixed;
            top: 56px;
            left: 0;
            width: 100%;
            height: calc(100vh - 56px);
            background: rgba(0, 0, 0, .45);
            z-index: 1065;
        }

        .content-area {
            width: 100%;
            flex: 0 0 100%;
            max-width: 100%;
        }

        .navbar-brand {
            font-size: 15px;
        }

        .filter-btn {
            width: 100%;
        }

        .export-btn {
            width: 100%;
            margin-top: 7px;
        }
    }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand navbar-dark">
        <button type="button" class="btn btn-outline-light d-md-none mr-2" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>

        <a class="navbar-brand" href="dashboard.php">
            🎓 Graduation Attendance
        </a>

        <div class="ml-auto d-flex align-items-center">
            <span class="text-white-50 mr-3 d-none d-md-inline">
                <i class="fas fa-user"></i>
                <?= e($_SESSION['nama'] ?? 'Administrator'); ?>
            </span>

            <a href="../logout.php" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin logout?')">
                Logout
            </a>
        </div>
    </nav>

    <div id="overlay"></div>

    <div class="container-fluid">
        <div class="row">

            <aside class="col-md-2 sidebar p-0" id="sidebar">
                <a href="dashboard.php">
                    <i class="fas fa-home mr-2"></i> Dashboard
                </a>

                <a href="siswa.php">
                    <i class="fas fa-user-graduate mr-2"></i> Data Siswa
                </a>

                <a href="upload_excel.php">
                    <i class="fas fa-file-excel mr-2"></i> Upload Excel
                </a>

                <a href="scan_qrcode.php">
                    <i class="fas fa-qrcode mr-2"></i> Scan QR
                </a>

                <a href="absensi.php" class="active">
                    <i class="fas fa-check-circle mr-2"></i> Data Absensi
                </a>

                <a href="export_pdf.php">
                    <i class="fas fa-file-pdf mr-2"></i> Export PDF
                </a>

                <a href="export_excel.php">
                    <i class="fas fa-file-excel mr-2"></i> Export Excel
                </a>
            </aside>

            <main class="col-md-10 content-area">
                <div class="container-fluid py-4">

                    <div class="card main-card">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <h5 class="mb-2 mb-md-0">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Data Absensi Wisuda
                                </h5>

                                <div>
                                    <a href="export_pdf.php?<?= e($queryString); ?>"
                                        class="btn btn-danger btn-sm export-btn rounded-pill">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>

                                    <a href="export_excel.php?<?= e($queryString); ?>"
                                        class="btn btn-success btn-sm export-btn rounded-pill">
                                        <i class="fas fa-file-excel"></i> Excel
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">

                            <form method="GET">
                                <div class="form-row">
                                    <div class="col-md-5 mb-2">
                                        <input type="text" name="cari" class="form-control"
                                            placeholder="Cari NIS, nama, kelas..." value="<?= e($cari); ?>">
                                    </div>

                                    <div class="col-md-3 mb-2">
                                        <input type="date" name="tanggal" class="form-control"
                                            value="<?= e($tanggal); ?>">
                                    </div>

                                    <div class="col-md-2 mb-2">
                                        <button type="submit" class="btn btn-primary btn-block filter-btn rounded-pill">
                                            <i class="fas fa-search"></i> Cari
                                        </button>
                                    </div>

                                    <div class="col-md-2 mb-2">
                                        <a href="absensi.php"
                                            class="btn btn-secondary btn-block filter-btn rounded-pill">
                                            <i class="fas fa-sync-alt"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>

                            <div class="alert alert-info mt-3 mb-3">
                                <i class="fas fa-info-circle"></i>
                                Total Data Absensi:
                                <strong><?= number_format($totalData); ?></strong>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
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
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php if (mysqli_num_rows($query) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                        <tr>
                                            <td><?= $no++; ?></td>
                                            <td><?= e(hariIndonesia($row['hari'])); ?></td>
                                            <td><?= date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                                            <td><?= e($row['jam']); ?></td>
                                            <td><?= e($row['nis']); ?></td>
                                            <td><?= e($row['nama_siswa']); ?></td>
                                            <td><?= e($row['kelas']); ?></td>
                                            <td>
                                                <span class="badge-hadir"><?= e($row['status']); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-info btn-sm btn-detail"
                                                    data-toggle="modal" data-target="#detail<?= $row['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <i class="fas fa-info-circle"></i>
                                                Tidak ada data absensi.
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($totalPage > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center mb-0">

                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?= $page - 1; ?>&<?= e($queryString); ?>">
                                            Previous
                                        </a>
                                    </li>

                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage   = min($totalPage, $page + 2);
                                    ?>

                                    <?php if ($startPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&<?= e($queryString); ?>">1</a>
                                    </li>

                                    <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                    <?php endif; ?>
                                    <?php endif; ?>

                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <li class="page-item <?= ($page === $i) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?= $i; ?>&<?= e($queryString); ?>">
                                            <?= $i; ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>

                                    <?php if ($endPage < $totalPage): ?>
                                    <?php if ($endPage < $totalPage - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                    <?php endif; ?>

                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $totalPage; ?>&<?= e($queryString); ?>">
                                            <?= $totalPage; ?>
                                        </a>
                                    </li>
                                    <?php endif; ?>

                                    <li class="page-item <?= ($page >= $totalPage) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?= $page + 1; ?>&<?= e($queryString); ?>">
                                            Next
                                        </a>
                                    </li>

                                </ul>
                            </nav>
                            <?php endif; ?>

                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php
/*
|--------------------------------------------------------------------------
| Modal dipindahkan ke luar tabel
|--------------------------------------------------------------------------
*/
mysqli_data_seek($query, 0);

while ($row = mysqli_fetch_assoc($query)):
?>
    <div class="modal fade" id="detail<?= $row['id']; ?>" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle"></i>
                        Detail Absensi
                    </h5>

                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <table class="table table-bordered mb-0">
                        <tr>
                            <th width="38%">NIS</th>
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
                            <td><?= e(hariIndonesia($row['hari'])); ?></td>
                        </tr>
                        <tr>
                            <th>Tanggal</th>
                            <td><?= date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                        </tr>
                        <tr>
                            <th>Jam</th>
                            <td><?= e($row['jam']); ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge-hadir"><?= e($row['status']); ?></span>
                            </td>
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
    <?php endwhile; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
    $(function() {
        function bukaSidebar() {
            $('#sidebar').addClass('show');
            $('#overlay').fadeIn(200);
            $('body').css('overflow', 'hidden');
        }

        function tutupSidebar() {
            $('#sidebar').removeClass('show');
            $('#overlay').fadeOut(200);
            $('body').css('overflow', 'auto');
        }

        $('#toggleSidebar').on('click', function() {
            $('#sidebar').hasClass('show') ? tutupSidebar() : bukaSidebar();
        });

        $('#overlay').on('click', tutupSidebar);

        $('#sidebar a').on('click', function() {
            if ($(window).width() <= 768) {
                tutupSidebar();
            }
        });

        $(window).on('resize', function() {
            if ($(window).width() > 768) {
                tutupSidebar();
            }
        });
    });
    </script>

</body>

</html>