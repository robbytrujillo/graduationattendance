<?php
require_once '../config/config.php';

function e($data)
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['role'] !== 'petugas') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Scan QR Code | Absensi Wisuda</title>

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
        position: relative;
        z-index: 1060;
    }

    .sidebar {
        background: #1E293B;
        min-height: 100vh;
        transition: all 0.3s ease;
    }

    .sidebar a {
        color: #fff;
        display: block;
        padding: 12px 15px;
        text-decoration: none;
        transition: 0.3s;
    }

    .sidebar a:hover,
    .sidebar .active {
        background: #0d6efd;
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
    }

    .scanner-card {
        max-width: 760px;
    }

    #reader {
        width: 100%;
        min-height: 350px;
        overflow: hidden;
        border-radius: 10px;
        background: #111827;
    }

    #reader video {
        width: 100% !important;
        border-radius: 10px;
        object-fit: cover;
    }

    /* Dipakai jika kamera depan yang aktif agar tampilan tidak mirror */
    #reader.camera-depan video {
        transform: scaleX(-1);
    }

    #reader__dashboard {
        padding: 10px !important;
        background: #ffffff;
    }

    #reader__status_span {
        font-size: 13px;
    }

    #overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.45);
        display: none;
        z-index: 1040;
    }

    .scanner-status {
        min-height: 60px;
    }

    @media (max-width: 768px) {
        .sidebar {
            position: fixed;
            top: 0;
            left: -260px;
            width: 250px;
            height: 100vh;
            min-height: 100vh;
            z-index: 1050;
            overflow-y: auto;
            padding-top: 60px !important;
        }

        .sidebar.show {
            left: 0;
        }

        .content-area {
            width: 100%;
            flex: 0 0 100%;
            max-width: 100%;
        }

        .navbar-brand {
            font-size: 15px;
        }

        #reader {
            min-height: 280px;
        }
    }

    @media (max-width: 576px) {

        .content-area {
            padding-left: 10px !important;
            padding-right: 10px !important;
        }

        .scanner-card {
            max-width: 100%;
        }

        #reader {
            min-height: 260px;
        }

        #reader video {
            width: 100% !important;
            height: auto !important;
            object-fit: cover;
        }

        #reader__dashboard {
            font-size: 13px;
        }

        #reader__dashboard_section_csr select,
        #reader__dashboard_section_csr button {
            font-size: 13px !important;
        }

        .card-body {
            padding: 15px;
        }

        h3 {
            font-size: 20px;
        }
    }
    </style>
</head>

<body>

    <div id="overlay"></div>

    <nav class="navbar navbar-expand-lg navbar-dark">

        <button type="button" class="btn btn-dark d-md-none mr-2" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>

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

            <div class="col-md-2 sidebar p-0" id="sidebar">

                <a href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>

                <a href="siswa.php">
                    <i class="fas fa-user-graduate"></i> Data Siswa
                </a>

                <a href="upload_excel.php">
                    <i class="fas fa-file-excel"></i> Upload Excel
                </a>

                <a href="scan_qrcode.php" class="active">
                    <i class="fas fa-qrcode"></i> Scan QR
                </a>

                <a href="absensi.php">
                    <i class="fas fa-check-circle"></i> Data Absensi
                </a>

                <a href="export_pdf.php">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>

                <a href="export_excel.php">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>

            </div>

            <div class="col-md-10 content-area">

                <div class="container-fluid mt-4 mb-5">

                    <h3>
                        <i class="fas fa-qrcode text-success"></i>
                        Scan QR Code Absensi
                    </h3>

                    <hr>

                    <div class="row">

                        <div class="col-lg-7 mb-4">

                            <div class="card scanner-card">

                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-camera"></i>
                                        Kamera Scanner
                                    </h5>
                                </div>

                                <div class="card-body">

                                    <div class="text-center mb-3">
                                        <h5>Arahkan QR Code Siswa ke Kamera</h5>
                                        <p class="text-muted mb-0">
                                            QR Code hanya dapat digunakan satu kali dalam satu hari.
                                        </p>
                                    </div>

                                    <div id="reader"></div>

                                    <div id="result" class="scanner-status mt-3">
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-spinner fa-spin"></i>
                                            Menyiapkan kamera...
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>

                        <div class="col-lg-5 mb-4">

                            <div class="card">

                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle"></i>
                                        Informasi Scan
                                    </h5>
                                </div>

                                <div class="card-body">
                                    <ul class="pl-3 mb-0">
                                        <li>Pastikan izin kamera browser sudah diizinkan.</li>
                                        <li>Arahkan QR Code siswa ke area kamera.</li>
                                        <li>Absensi otomatis masuk ke tabel absensi.</li>
                                        <li>Siswa hanya dapat absen satu kali per hari.</li>
                                        <li>Gunakan HTTPS atau localhost agar kamera dapat dibuka.</li>
                                    </ul>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <script>
    let html5QrCode = null;
    let isProcessing = false;
    let lastScanContent = '';
    let lastScanTime = 0;

    function tampilkanPesan(html) {
        $('#result').html(html);
    }

    function prosesQR(content) {
        const now = Date.now();

        if (isProcessing || (content === lastScanContent && (now - lastScanTime) < 3000)) {
            return;
        }

        isProcessing = true;
        lastScanContent = content;
        lastScanTime = now;

        tampilkanPesan(`
            <div class="alert alert-warning mb-0">
                <i class="fas fa-spinner fa-spin"></i>
                Memproses QR Code...
            </div>
        `);

        $.ajax({
            url: 'proses_scan.php',
            type: 'POST',
            data: {
                qr: content
            },
            success: function(response) {
                tampilkanPesan(response);
            },
            error: function(xhr) {
                console.error(xhr.responseText);

                tampilkanPesan(`
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-times-circle"></i>
                        Gagal mengirim data scan ke server.
                    </div>
                `);
            },
            complete: function() {
                setTimeout(function() {
                    isProcessing = false;
                }, 2500);
            }
        });
    }

    function onScanSuccess(decodedText) {
        prosesQR(decodedText);
    }

    function onScanFailure(errorMessage) {
        // Tidak perlu ditampilkan karena dipanggil terus saat QR belum terbaca.
    }

    function aktifkanKameraDepan(config) {
        $('#reader').addClass('camera-depan');

        return html5QrCode.start({
                facingMode: "user"
            },
            config,
            onScanSuccess,
            onScanFailure
        );
    }

    function mulaiScanner() {
        html5QrCode = new Html5Qrcode("reader");

        const config = {
            fps: 10,
            qrbox: {
                width: 250,
                height: 250
            },
            aspectRatio: 1.333334,
            disableFlip: false
        };

        tampilkanPesan(`
            <div class="alert alert-info mb-0">
                <i class="fas fa-spinner fa-spin"></i>
                Meminta izin kamera...
            </div>
        `);

        $('#reader').removeClass('camera-depan');

        // Prioritas kamera belakang pada HP
        html5QrCode.start({
                    facingMode: {
                        exact: "environment"
                    }
                },
                config,
                onScanSuccess,
                onScanFailure
            )
            .then(function() {
                tampilkanPesan(`
                <div class="alert alert-success mb-0">
                    <i class="fas fa-camera"></i>
                    Kamera belakang aktif. Silakan scan QR Code siswa.
                </div>
            `);
            })
            .catch(function(error) {
                console.warn('Kamera belakang tidak tersedia:', error);

                // Laptop biasanya hanya memiliki kamera depan
                aktifkanKameraDepan(config)
                    .then(function() {
                        tampilkanPesan(`
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-camera"></i>
                            Kamera aktif. Silakan scan QR Code siswa.
                        </div>
                    `);
                    })
                    .catch(function(error2) {
                        console.error(error2);

                        tampilkanPesan(`
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-camera-slash"></i>
                            <strong>Kamera gagal dibuka.</strong><br>
                            ${error2.message || error2}
                        </div>
                    `);
                    });
            });
    }

    $(document).ready(function() {

        mulaiScanner();

        $('#toggleSidebar').on('click', function() {
            $('#sidebar').toggleClass('show');
            $('#overlay').fadeToggle(200);
        });

        $('#overlay').on('click', function() {
            $('#sidebar').removeClass('show');
            $('#overlay').fadeOut(200);
        });

        $('.sidebar a').on('click', function() {
            if ($(window).width() <= 768) {
                $('#sidebar').removeClass('show');
                $('#overlay').fadeOut(200);
            }
        });

    });
    </script>

</body>

</html>