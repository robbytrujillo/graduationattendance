<?php

require_once '../config/config.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

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

/*
|--------------------------------------------------------------------------
| PROSES UPLOAD EXCEL
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

        $allowed = ['xlsx', 'xls', 'csv'];

        if (!in_array($extension, $allowed)) {
            $error = "Format file tidak valid. Gunakan file .xlsx, .xls, atau .csv.";
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $error = "Ukuran file maksimal 5 MB.";
        } else {

            try {

                $spreadsheet = IOFactory::load($fileTmp);
                $sheet       = $spreadsheet->getActiveSheet();
                $rows        = $sheet->toArray();

                $berhasil = 0;
                $duplikat = 0;
                $kosong   = 0;

                /*
                | Baris pertama dianggap sebagai header:
                | NIS | Nama Siswa | Kelas
                */
                foreach ($rows as $index => $row) {

                    if ($index === 0) {
                        continue;
                    }

                    $nis        = isset($row[0]) ? trim($row[0]) : '';
                    $nama_siswa = isset($row[1]) ? trim($row[1]) : '';
                    $kelas      = isset($row[2]) ? trim($row[2]) : '';

                    if ($nis === '' || $nama_siswa === '' || $kelas === '') {
                        $kosong++;
                        continue;
                    }

                    $nis        = mysqli_real_escape_string($conn, $nis);
                    $nama_siswa = mysqli_real_escape_string($conn, $nama_siswa);
                    $kelas      = mysqli_real_escape_string($conn, $kelas);

                    $cek = mysqli_query(
                        $conn,
                        "SELECT id FROM users WHERE nis='$nis' LIMIT 1"
                    );

                    if (mysqli_num_rows($cek) > 0) {
                        $duplikat++;
                        continue;
                    }

                    $username = $nis;
                    $password = password_hash('123456', PASSWORD_DEFAULT);

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
                        $berhasil++;
                    }
                }

                $success = "Import selesai. Berhasil: $berhasil siswa, NIS duplikat: $duplikat, data kosong/tidak lengkap: $kosong.";
            } catch (Exception $e) {
                $error = "File gagal diproses. Pastikan format Excel sesuai. Detail: " . $e->getMessage();
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

    .upload-icon {
        font-size: 60px;
        color: #28a745;
    }

    .format-table th {
        background: #343a40;
        color: white;
    }

    @media(max-width:768px) {
        .sidebar {
            min-height: auto;
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

                <a href="upload_excel.php" class="active">
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

            </div>

            <!-- CONTENT -->
            <div class="col-md-10">

                <div class="container-fluid mt-4 mb-5">

                    <h3>
                        <i class="fas fa-file-excel text-success"></i>
                        Upload Data Siswa
                    </h3>

                    <hr>

                    <?php if ($success !== '') : ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <strong>Berhasil!</strong>
                        <?= e($success); ?>

                        <button type="button" class="close" data-dismiss="alert">
                            &times;
                        </button>
                    </div>
                    <?php endif; ?>

                    <?php if ($error !== '') : ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <strong>Gagal!</strong>
                        <?= e($error); ?>

                        <button type="button" class="close" data-dismiss="alert">
                            &times;
                        </button>
                    </div>
                    <?php endif; ?>

                    <div class="row">

                        <!-- FORM UPLOAD -->
                        <div class="col-lg-7 mb-4">

                            <div class="card">

                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-upload"></i>
                                        Import Data Siswa
                                    </h5>
                                </div>

                                <div class="card-body">

                                    <div class="text-center mb-4">
                                        <i class="fas fa-file-excel upload-icon"></i>

                                        <h5 class="mt-3">
                                            Upload File Excel Siswa
                                        </h5>

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
                                            Password default setiap siswa adalah:
                                            <strong>123456</strong>
                                        </div>

                                        <div class="d-flex flex-wrap">
                                            <button type="submit" name="upload_excel"
                                                class="btn btn-success btn-sm mr-2 mb-2">
                                                <i class="fas fa-upload"></i>
                                                Upload dan Import Data
                                            </button>

                                            <a href="template_excel.php" class="btn btn-outline-primary btn-sm mb-2">
                                                <i class="fas fa-download"></i>
                                                Download Template Excel
                                            </a>
                                        </div>

                                    </form>

                                </div>

                            </div>

                        </div>

                        <!-- FORMAT EXCEL -->
                        <div class="col-lg-5 mb-4">

                            <div class="card">

                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle"></i>
                                        Format File Excel
                                    </h5>
                                </div>

                                <div class="card-body">

                                    <p>
                                        Pastikan baris pertama file Excel adalah header seperti berikut:
                                    </p>

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
                                        <li>Data dengan NIS yang sudah ada akan dilewati.</li>
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

</body>

</html>