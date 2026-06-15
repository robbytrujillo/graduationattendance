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
$error = '';

/*
|--------------------------------------------------------------------------
| TAMBAH SISWA
|--------------------------------------------------------------------------
*/

if(isset($_POST['tambah'])){

    $nis        = mysqli_real_escape_string($conn, $_POST['nis']);
    $nama_siswa = mysqli_real_escape_string($conn, $_POST['nama_siswa']);
    $kelas      = mysqli_real_escape_string($conn, $_POST['kelas']);

    $cek = mysqli_query(
        $conn,
        "SELECT id
        FROM users
        WHERE nis='$nis'"
    );

    if(mysqli_num_rows($cek) > 0){

        $error = "NIS sudah terdaftar.";

    }else{

        $username = $nis;

        $password = password_hash(
            "123456",
            PASSWORD_DEFAULT
        );

        mysqli_query(
            $conn,
            "INSERT INTO users
            (
                nis,
                nama_siswa,
                kelas,
                username,
                password,
                role,
                created_at
            )
            VALUES
            (
                '$nis',
                '$nama_siswa',
                '$kelas',
                '$username',
                '$password',
                'siswa',
                NOW()
            )"
        );

        $success = "Data siswa berhasil ditambahkan.";
    }
}

/*
|--------------------------------------------------------------------------
| HAPUS SISWA
|--------------------------------------------------------------------------
*/

if(isset($_GET['hapus'])){

    $id = (int)$_GET['hapus'];

    mysqli_query(
        $conn,
        "DELETE FROM users
        WHERE id='$id'
        AND role='siswa'"
    );

    header("Location:siswa.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$keyword = '';

if(isset($_GET['keyword'])){

    $keyword = mysqli_real_escape_string(
        $conn,
        $_GET['keyword']
    );
}

$where = "
WHERE role='siswa'
";

if($keyword != ''){

    $where .= "
    AND
    (
        nis LIKE '%$keyword%'
        OR nama_siswa LIKE '%$keyword%'
        OR kelas LIKE '%$keyword%'
    )
    ";
}

/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$limit = 10;

$page = isset($_GET['page'])
    ? (int)$_GET['page']
    : 1;

if($page < 1){
    $page = 1;
}

$start = ($page - 1) * $limit;

$totalData = mysqli_num_rows(
    mysqli_query(
        $conn,
        "SELECT id
        FROM users
        $where"
    )
);

$totalPage = ceil(
    $totalData / $limit
);

$query = mysqli_query(
    $conn,
    "SELECT *
    FROM users
    $where
    ORDER BY id DESC
    LIMIT $start,$limit"
);

/*
|--------------------------------------------------------------------------
| STATISTIK
|--------------------------------------------------------------------------
*/

$totalSiswa = mysqli_num_rows(
    mysqli_query(
        $conn,
        "SELECT id
        FROM users
        WHERE role='siswa'"
    )
);

/*
|--------------------------------------------------------------------------
| RESET PASSWORD
|--------------------------------------------------------------------------
*/

if(isset($_GET['reset'])){

    $id = (int)$_GET['reset'];

    $passwordBaru = password_hash(
        '123456',
        PASSWORD_DEFAULT
    );

    mysqli_query(
        $conn,
        "UPDATE users
        SET password='$passwordBaru'
        WHERE id='$id'"
    );

    header("Location:siswa.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Siswa</title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

    <style>
    body {
        background: #f4f6f9;
    }

    /* Navbar */

    .navbar {
        background: #0F172A;
    }

    /* Sidebar */

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

    .sidebar a:hover {
        background: #334155;
    }

    .sidebar .active {
        background: #0d6efd;
    }

    /* Card */

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, .08);
    }

    /* Mobile */

    @media(max-width:768px) {

        .sidebar {
            min-height: auto;
        }

    }

    @media(max-width:768px) {

        .text-right {
            text-align: left !important;
            margin-top: 10px;
        }

        .btn-success {
            width: 100%;
        }

    }
    </style>

</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark">

        <a class="navbar-brand" href="#">

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

                        <?= $_SESSION['nama']; ?>

                    </span>

                </li>

                <li class="nav-item">

                    <a href="../logout.php" class="btn btn-danger btn-sm mt-1"
                        onclick="return confirm('Yakin ingin logout ?')">

                        Logout

                    </a>

                </li>

            </ul>

        </div>

    </nav>

    <div class="container-fluid">

        <div class="row">
            <div class="col-md-2 sidebar p-0">

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
        </div>

        <div class="col-md-10">

            <div class="container-fluid mt-4">

                <h3>

                    <i class="fas fa-user-graduate"></i>

                    Data Siswa

                </h3>

                <hr>

                <div class="row mb-4">

                    <div class="col-md-3">

                        <div class="card">

                            <div class="card-body text-center">

                                <h2 class="text-primary">

                                    <?= $totalSiswa ?>

                                </h2>

                                <strong>

                                    Total Siswa

                                </strong>

                            </div>

                        </div>

                    </div>

                </div>

                <?php if($success != ''): ?>

                <div class="alert alert-success alert-dismissible fade show">

                    <strong>
                        Berhasil!
                    </strong>

                    <?= $success ?>

                    <button type="button" class="close" data-dismiss="alert">

                        &times;

                    </button>

                </div>

                <?php endif; ?>


                <?php if($error != ''): ?>

                <div class="alert alert-danger alert-dismissible fade show">

                    <strong>
                        Gagal!
                    </strong>

                    <?= $error ?>

                    <button type="button" class="close" data-dismiss="alert">

                        &times;

                    </button>

                </div>

                <?php endif; ?>

                <div class="card mb-4">

                    <div class="card-body">

                        <div class="row">

                            <!-- Search -->

                            <div class="col-md-8">

                                <form method="GET">

                                    <div class="input-group">

                                        <input type="text" name="keyword" class="form-control"
                                            placeholder="Cari NIS, Nama Siswa, atau Kelas..." value="<?= $keyword ?>">

                                        <div class="input-group-append">

                                            <button class="btn btn-primary">

                                                <i class="fas fa-search"></i>

                                                Cari

                                            </button>

                                        </div>

                                    </div>

                                </form>

                            </div>

                            <div class="card">

                                <div class="card-header bg-primary text-white">

                                    <h5 class="mb-0">

                                        <i class="fas fa-user-graduate"></i>

                                        Data Siswa

                                    </h5>

                                </div>

                                <div class="card-body">

                                    <div class="table-responsive">

                                        <table id="tableSiswa" class="table table-bordered table-striped table-hover">

                                            <thead class="thead-dark">

                                                <tr>

                                                    <th>No</th>
                                                    <th>NIS</th>
                                                    <th>Nama</th>
                                                    <th>Kelas</th>
                                                    <th>Username</th>
                                                    <th>Role</th>
                                                    <th width="250">Aksi</th>

                                                </tr>

                                            </thead>

                                            <tbody>

                                                <?php

                $no = 1;

                while($row = mysqli_fetch_assoc($query)) :

                ?>

                                                <tr>

                                                    <td><?= $no++ ?></td>

                                                    <td><?= $row['nis'] ?></td>

                                                    <td><?= $row['nama_siswa'] ?></td>

                                                    <td><?= $row['kelas'] ?></td>

                                                    <td><?= $row['username'] ?></td>

                                                    <td>

                                                        <span class="badge badge-success">

                                                            <?= ucfirst($row['role']) ?>

                                                        </span>

                                                    </td>

                                                    <td>

                                                        <button class="btn btn-info btn-sm" data-toggle="modal"
                                                            data-target="#detail<?= $row['id'] ?>">

                                                            <i class="fas fa-eye"></i>

                                                        </button>

                                                        <button class="btn btn-warning btn-sm" data-toggle="modal"
                                                            data-target="#edit<?= $row['id'] ?>">

                                                            <i class="fas fa-edit"></i>

                                                        </button>

                                                        <a href="?reset=<?= $row['id'] ?>"
                                                            class="btn btn-secondary btn-sm"
                                                            onclick="return confirm('Reset password menjadi 123456?')">

                                                            <i class="fas fa-key"></i>

                                                        </a>

                                                        <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Hapus siswa ini?')">

                                                            <i class="fas fa-trash"></i>

                                                        </a>

                                                    </td>

                                                </tr>

                                                <?php endwhile; ?>

                                            </tbody>

                                        </table>

                                    </div>

                                </div>

                            </div>

                            <!-- Tombol Tambah -->

                            <div class="col-md-4 text-right">

                                <button class="btn btn-success" data-toggle="modal" data-target="#modalTambah">

                                    <i class="fas fa-plus"></i>

                                    Tambah Siswa

                                </button>

                            </div>

                        </div>

                    </div>

                </div>