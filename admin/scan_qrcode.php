<?php
require_once '../config/config.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Scan QR Code | Absensi</title>

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
        padding: 12px;
        text-decoration: none;
    }

    .sidebar a:hover,
    .sidebar .active {
        background: #0d6efd;
    }

    .card {
        border: none;
        border-radius: 15px;
    }

    #preview {
        width: 100%;
        border-radius: 10px;
    }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-dark">
        <a class="navbar-brand" href="dashboard.php">🎓 Graduation Attendance</a>

        <span class="text-white">
            <?= $_SESSION['nama']; ?>
            <a href="../logout.php" class="btn btn-danger btn-sm ml-2">Logout</a>
        </span>
    </nav>

    <div class="container-fluid">
        <div class="row">

            <!-- SIDEBAR -->
            <div class="col-md-2 sidebar p-0">

                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="siswa.php"><i class="fas fa-user-graduate"></i> Data Siswa</a>
                <a href="upload_excel.php"><i class="fas fa-file-excel"></i> Upload Excel</a>
                <a href="scan_qrcode.php" class="active"><i class="fas fa-qrcode"></i> Scan QR</a>
                <a href="absensi.php"><i class="fas fa-check-circle"></i> Absensi</a>

            </div>

            <!-- CONTENT -->
            <div class="col-md-10 p-4">

                <h3><i class="fas fa-qrcode text-success"></i> Scan QR Code Absensi</h3>
                <hr>

                <div class="row">
                    <div class="col-md-6">

                        <div class="card shadow p-3">
                            <h5 class="text-center">Arahkan QR ke Kamera</h5>

                            <video id="preview"></video>

                            <div id="result" class="mt-3"></div>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>

    <script>
    let scanner = new Instascan.Scanner({
        video: document.getElementById('preview')
    });

    scanner.addListener('scan', function(content) {

        $.ajax({
            url: 'proses_scan.php',
            type: 'POST',
            data: {
                qr: content
            },
            success: function(res) {
                $('#result').html(res);
            }
        });

    });

    Instascan.Camera.getCameras().then(function(cameras) {
        if (cameras.length > 0) {
            scanner.start(cameras[0]);
        } else {
            alert("Kamera tidak ditemukan");
        }
    }).catch(function(e) {
        console.error(e);
    });
    </script>

</body>

</html>