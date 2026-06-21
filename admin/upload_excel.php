<?php

require_once '../config/config.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error   = '';

function e($data)
{
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| PROSES UPLOAD EXCEL + GENERATE QR CODE
|--------------------------------------------------------------------------
*/

if (isset($_POST['upload_excel'])) {

    if (!isset($_FILES['file_excel']) || $_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
        $error = "Silakan pilih file Excel terlebih dahulu.";
    } else {

        $fileName = $_FILES['file_excel']['name'];
        $fileTmp  = $_FILES['file_excel']['tmp_name'];
        $fileSize = $_FILES['file_excel']['size'];

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed   = ['xlsx', 'xls', 'csv'];

        if (!in_array($extension, $allowed)) {
            $error = "Format file tidak valid. Gunakan file .xlsx, .xls, atau .csv.";
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $error = "Ukuran file maksimal 5 MB.";
        } else {

            try {

                /*
                |--------------------------------------------------------------------------
                | FOLDER PENYIMPANAN QR CODE
                |--------------------------------------------------------------------------
                */

                $qrFolder = '../assets/qrcode/';

                if (!is_dir($qrFolder)) {
                    if (!mkdir($qrFolder, 0777, true) && !is_dir($qrFolder)) {
                        throw new Exception("Folder QR Code gagal dibuat.");
                    }
                }

                if (!is_writable($qrFolder)) {
                    throw new Exception("Folder assets/qrcode tidak bisa ditulis.");
                }

                /*
                |--------------------------------------------------------------------------
                | BACA EXCEL
                |--------------------------------------------------------------------------
                */

                $spreadsheet = IOFactory::load($fileTmp);
                $sheet       = $spreadsheet->getActiveSheet();
                $rows        = $sheet->toArray();

                $berhasil = 0;
                $duplikat = 0;
                $kosong   = 0;
                $gagal    = 0;

                foreach ($rows as $index => $row) {

                    /* Lewati baris header */
                    if ($index === 0) {
                        continue;
                    }

                    $nis        = isset($row[0]) ? trim((string) $row[0]) : '';
                    $nama_siswa = isset($row[1]) ? trim((string) $row[1]) : '';
                    $kelas      = isset($row[2]) ? trim((string) $row[2]) : '';

                    /* Lewati baris kosong */
                    if ($nis === '' && $nama_siswa === '' && $kelas === '') {
                        continue;
                    }

                    /* Validasi kolom wajib */
                    if ($nis === '' || $nama_siswa === '' || $kelas === '') {
                        $kosong++;
                        continue;
                    }

                    $nisDb        = mysqli_real_escape_string($conn, $nis);
                    $namaSiswaDb = mysqli_real_escape_string($conn, $nama_siswa);
                    $kelasDb      = mysqli_real_escape_string($conn, $kelas);

                    /*
                    |--------------------------------------------------------------------------
                    | CEK DUPLIKAT NIS
                    |--------------------------------------------------------------------------
                    */

                    $cek = mysqli_query(
                        $conn,
                        "SELECT id FROM users WHERE nis = '$nisDb' LIMIT 1"
                    );

                    if (!$cek) {
                        $gagal++;
                        continue;
                    }

                    if (mysqli_num_rows($cek) > 0) {
                        $duplikat++;
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | DATA LOGIN
                    |--------------------------------------------------------------------------
                    */

                    $username = $nisDb;
                    $password = password_hash('123456', PASSWORD_DEFAULT);

                    /*
                    |--------------------------------------------------------------------------
                    | TOKEN QR
                    |--------------------------------------------------------------------------
                    */

                    $qrToken = bin2hex(random_bytes(32));

                    /*
                    |--------------------------------------------------------------------------
                    | ISI QR CODE
                    |--------------------------------------------------------------------------
                    */

                    $qrData = json_encode([
                        'nis'   => $nis,
                        'token' => $qrToken
                    ], JSON_UNESCAPED_UNICODE);

                    /*
                    |--------------------------------------------------------------------------
                    | NAMA FILE QR
                    |--------------------------------------------------------------------------
                    */

                    $safeNis = preg_replace('/[^A-Za-z0-9_-]/', '_', $nis);

                    $qrFileName = 'qr_' . $safeNis . '_' . uniqid('', true) . '.png';
                    $qrFileName = str_replace('.', '_', $qrFileName);

                    $qrPath = $qrFolder . $qrFileName;

                    /*
                    |--------------------------------------------------------------------------
                    | GENERATE QR CODE - ENDROID QR CODE V6
                    |--------------------------------------------------------------------------
                    */

                    $writer = new PngWriter();

                    $qrCode = new QrCode(
                        data: $qrData,
                        size: 300,
                        margin: 10
                    );

                    $result = $writer->write($qrCode);

                    $result->saveToFile($qrPath);

                    if (!file_exists($qrPath)) {
                        throw new Exception("QR Code gagal dibuat untuk NIS: " . $nis);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | SIMPAN PATH QR KE DATABASE
                    |--------------------------------------------------------------------------
                    */

                    $qrcode   = 'assets/qrcode/' . $qrFileName;
                    $qrcodeDb = mysqli_real_escape_string($conn, $qrcode);
                    $tokenDb  = mysqli_real_escape_string($conn, $qrToken);

                    $insert = mysqli_query(
                        $conn,
                        "INSERT INTO users (
                            nis,
                            nama_siswa,
                            kelas,
                            username,
                            password,
                            qr_token,
                            qrcode,
                            role,
                            created_at
                        ) VALUES (
                            '$nisDb',
                            '$namaSiswaDb',
                            '$kelasDb',
                            '$username',
                            '$password',
                            '$tokenDb',
                            '$qrcodeDb',
                            'siswa',
                            NOW()
                        )"
                    );

                    if ($insert) {
                        $berhasil++;
                    } else {
                        $gagal++;

                        if (file_exists($qrPath)) {
                            unlink($qrPath);
                        }
                    }
                }

                $success = "Import selesai. Berhasil: $berhasil siswa, NIS duplikat: $duplikat, data kosong/tidak lengkap: $kosong, gagal disimpan: $gagal.";

            } catch (Throwable $ex) {
                $error = "File gagal diproses. Detail: " . $ex->getMessage();
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Upload Excel | Absensi Wisuda</title>

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

    .upload-icon {
        font-size: 60px;
        color: #28a745;
    }

    .format-table th {
        background: #343a40;
        color: #fff;
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

                <a href="upload_excel.php" class="active">
                    <i class="fas fa-file-excel"></i> Upload Excel
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
                        <i class="fas fa-file-excel text-success"></i>
                        Upload Data Siswa
                    </h3>

                    <hr>

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

                    <div class="row">

                        <div class="col-lg-7 mb-4">
                            <div class="card">

                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-upload"></i>
                                        Import Data Siswa dan QR Code
                                    </h5>
                                </div>

                                <div class="card-body">

                                    <div class="text-center mb-4">
                                        <i class="fas fa-file-excel upload-icon"></i>
                                        <h5 class="mt-3">Upload File Excel Siswa</h5>
                                        <p class="text-muted mb-0">
                                            Format yang didukung: XLSX, XLS, CSV
                                        </p>
                                    </div>

                                    <form method="POST" enctype="multipart/form-data">

                                        <div class="form-group">
                                            <label>Pilih File Excel</label>
                                            <input type="file" name="file_excel" class="form-control-file"
                                                accept=".xlsx,.xls,.csv" required>
                                        </div>

                                        <div class="alert alert-warning">
                                            <i class="fas fa-key"></i>
                                            Password default setiap siswa:
                                            <strong>123456</strong>
                                            <br>
                                            <i class="fas fa-qrcode"></i>
                                            QR Code dibuat otomatis untuk siswa baru.
                                        </div>

                                        <button type="submit" name="upload_excel" class="btn btn-success btn-sm">
                                            <i class="fas fa-upload"></i>
                                            Upload dan Import Data
                                        </button>

                                        <a href="template_excel.php" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-download"></i>
                                            Download Template Excel
                                        </a>

                                    </form>

                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5 mb-4">
                            <div class="card">

                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle"></i>
                                        Format File Excel
                                    </h5>
                                </div>

                                <div class="card-body">

                                    <p>Baris pertama Excel wajib menggunakan header berikut:</p>

                                    <div class="table-responsive">
                                        <table class="table table-bordered format-table">
                                            <thead>
                                                <tr>
                                                    <th>NIS</th>
                                                    <th>Nama Siswa</th>
                                                    <th>Kelas</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>2026001</td>
                                                    <td>Ahmad Fauzan</td>
                                                    <td>XII IPA 1</td>
                                                </tr>
                                                <tr>
                                                    <td>2026002</td>
                                                    <td>Siti Aisyah</td>
                                                    <td>XII IPA 2</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    <ul class="pl-3 mb-0">
                                        <li>Kolom wajib: NIS, Nama Siswa, Kelas.</li>
                                        <li>NIS tidak boleh sama.</li>
                                        <li>NIS yang sudah terdaftar akan dilewati.</li>
                                        <li>QR Code dibuat otomatis untuk siswa baru.</li>
                                        <li>Ukuran file maksimal 5 MB.</li>
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

    <script>
    $(document).ready(function() {

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