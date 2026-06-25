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

/*
|--------------------------------------------------------------------------
| Statistik Dashboard
|--------------------------------------------------------------------------
*/

$totalSiswa = mysqli_num_rows(
    mysqli_query(
        $conn,
        "SELECT id FROM users WHERE role='siswa'"
    )
);

$hadirHariIni = mysqli_num_rows(
    mysqli_query(
        $conn,
        "SELECT id FROM absensi
         WHERE tanggal = CURDATE()"
    )
);

$belumHadir = $totalSiswa - $hadirHariIni;

$totalPetugas = mysqli_num_rows(
    mysqli_query(
        $conn,
        "SELECT id FROM users
         WHERE role='petugas'"
    )
);

/*
|--------------------------------------------------------------------------
| Data Absensi Terbaru
|--------------------------------------------------------------------------
*/

$absensi = mysqli_query(
    $conn,
    "SELECT
        a.*,
        u.nis,
        u.nama_siswa,
        u.kelas
    FROM absensi a
    JOIN users u
    ON a.user_id = u.id
    ORDER BY a.id DESC
    LIMIT 10"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard Admin</title>

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
        min-height: calc(100vh - 70px);
        background: #1E293B;
        color: white;
        /* transition: all .3s ease; */
        transition: left .15s ease;
    }

    .sidebar a {
        color: white;
        display: block;
        padding: 14px 18px;
        text-decoration: none;
        font-size: 16px;
    }

    .sidebar a i {
        width: 25px;
    }

    .sidebar a:hover {
        background: #334155;
        color: #fff;
    }

    .card-dashboard {
        border: none;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
    }

    .card-icon {
        font-size: 35px;
    }

    .table-responsive {
        background: white;
        border-radius: 10px;
        padding: 15px;
    }

    /* Overlay mobile */
    .sidebar-overlay {
        display: none;
    }

    /* Mobile */
    @media (max-width: 767.98px) {

        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            z-index: 1050;
            padding-top: 75px !important;
            overflow-y: auto;
            min-height: 100vh;
        }

        .sidebar.show {
            left: 0;
        }

        .sidebar-overlay.show {
            display: block;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 1040;
        }

        .content-area {
            width: 100%;
            flex: 0 0 100%;
            max-width: 100%;
        }

        .navbar-brand {
            font-size: 18px;
        }

        .user-name {
            display: none;
        }
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
                <?= $_SESSION['nama']; ?>
            </span>

            <a href="../logout.php" class="btn btn-danger btn-sm" onclick="return confirm('Yakin logout?')">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>

        </div>

    </nav>

    <div class="container-fluid">

        <div class="row">

            <!-- SIDEBAR -->

            <!-- <div class="sidebar p-0" id="sidebar">

                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>

                <a href="siswa.php">
                    <i class="fas fa-users"></i>
                    Data Siswa
                </a>

                <a href="upload_excel.php">
                    <i class="fas fa-file-excel"></i>
                    Upload Excel
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

            </div> -->

            <div class="sidebar p-0 col-md-2" id="sidebar">

                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>

                <a href="siswa.php">
                    <i class="fas fa-users"></i>
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

            <!-- WAJIB ADA -->
            <div class="sidebar-overlay" id="sidebarOverlay"></div>

            <!-- CONTENT -->

            <div class="col-md-10 content-area">

                <div class="container-fluid mt-4">

                    <h3>
                        Dashboard Admin
                    </h3>

                    <hr>

                    <!-- CARD -->

                    <div class="row">

                        <div class="col-md-3 col-6 mb-3">

                            <div class="card card-dashboard">

                                <div class="card-body text-center">

                                    <div class="card-icon text-primary">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>

                                    <h3>
                                        <?= $totalSiswa; ?>
                                    </h3>

                                    <small>Total Siswa</small>

                                </div>

                            </div>

                        </div>

                        <div class="col-md-3 col-6 mb-3">

                            <div class="card card-dashboard">

                                <div class="card-body text-center">

                                    <div class="card-icon text-success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>

                                    <h3>
                                        <?= $hadirHariIni; ?>
                                    </h3>

                                    <small>Hadir Hari Ini</small>

                                </div>

                            </div>

                        </div>

                        <div class="col-md-3 col-6 mb-3">

                            <div class="card card-dashboard">

                                <div class="card-body text-center">

                                    <div class="card-icon text-danger">
                                        <i class="fas fa-times-circle"></i>
                                    </div>

                                    <h3>
                                        <?= $belumHadir; ?>
                                    </h3>

                                    <small>Belum Hadir</small>

                                </div>

                            </div>

                        </div>

                        <div class="col-md-3 col-6 mb-3">

                            <div class="card card-dashboard">

                                <div class="card-body text-center">

                                    <div class="card-icon text-warning">
                                        <i class="fas fa-user-tie"></i>
                                    </div>

                                    <h3>
                                        <?= $totalPetugas; ?>
                                    </h3>

                                    <small>Petugas</small>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- DATA ABSENSI -->

                    <div class="card mt-4">

                        <div class="card-header bg-primary text-white">

                            Absensi Terbaru

                        </div>

                        <div class="card-body">

                            <div class="table-responsive">

                                <table class="table table-bordered table-striped">

                                    <thead>

                                        <tr>

                                            <th>No</th>
                                            <th>Hari</th>
                                            <th>Tanggal</th>
                                            <th>Jam</th>
                                            <th>NIS</th>
                                            <th>Nama</th>
                                            <th>Kelas</th>

                                        </tr>

                                    </thead>

                                    <tbody>

                                        <?php

                                $no = 1;

                                while($row = mysqli_fetch_assoc($absensi)) :

                                ?>

                                        <tr>

                                            <td><?= $no++; ?></td>

                                            <td><?= $row['hari']; ?></td>

                                            <td><?= $row['tanggal']; ?></td>

                                            <td><?= $row['jam']; ?></td>

                                            <td><?= $row['nis']; ?></td>

                                            <td><?= $row['nama_siswa']; ?></td>

                                            <td><?= $row['kelas']; ?></td>

                                        </tr>

                                        <?php endwhile; ?>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <!-- <script>
    setTimeout(function() {
        location.reload();
    }, 30000);
    </script> -->

    <!-- <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> -->

    <script>
    $(document).ready(function() {

        $('#btnSidebar').click(function() {
            $('#sidebar').toggleClass('show');
            $('#sidebarOverlay').toggleClass('show');
        });

        $('#sidebarOverlay').click(function() {
            $('#sidebar').removeClass('show');
            $('#sidebarOverlay').removeClass('show');
        });

        $('.sidebar a').click(function() {
            if ($(window).width() < 768) {
                $('#sidebar').removeClass('show');
                $('#sidebarOverlay').removeClass('show');
            }
        });

    });

    setTimeout(function() {
        location.reload();
    }, 30000);
    </script>

</body>

</html>