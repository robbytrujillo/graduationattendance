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
| Filter
|--------------------------------------------------------------------------
*/

$cari    = $_GET['cari'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';

$where = "WHERE 1=1";

if (!empty($cari)) {

    $where .= " AND (
        u.nis LIKE '%$cari%'
        OR u.nama_siswa LIKE '%$cari%'
        OR u.kelas LIKE '%$cari%'
    )";
}

if (!empty($tanggal)) {

    $where .= " AND a.tanggal='$tanggal'";
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

$limit = 10;

$page = isset($_GET['page']) ?
        (int)$_GET['page'] : 1;

$start = ($page - 1) * $limit;

$totalData = mysqli_num_rows(
    mysqli_query(
        $conn,
        "SELECT a.id
        FROM absensi a
        JOIN users u
        ON a.user_id=u.id
        $where"
    )
);

$totalPage = ceil($totalData / $limit);

/*
|--------------------------------------------------------------------------
| Data Absensi
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
    JOIN users u
    ON a.user_id=u.id
    $where
    ORDER BY a.id DESC
    LIMIT $start,$limit"
);

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

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, .08);
    }

    .table-responsive {
        overflow-x: auto;
    }

    .badge-hadir {
        background: #28a745;
        color: white;
        padding: 6px 10px;
        border-radius: 20px;
    }
    </style>

</head>

<body>

    <div class="container-fluid mt-4">

        <div class="row">

            <div class="col-md-12">

                <div class="card">

                    <div class="card-header bg-primary text-white">

                        <div class="d-flex justify-content-between align-items-center">

                            <h5 class="mb-0">
                                Data Absensi Wisuda
                            </h5>

                            <div>

                                <a href="export_pdf.php" class="btn btn-danger btn-sm">

                                    <i class="fas fa-file-pdf"></i>
                                    PDF

                                </a>

                                <a href="export_excel.php" class="btn btn-success btn-sm">

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
                                        placeholder="Cari NIS, Nama, Kelas..." value="<?= $cari ?>">

                                </div>

                                <div class="col-md-3 mb-2">

                                    <input type="date" name="tanggal" class="form-control" value="<?= $tanggal ?>">

                                </div>

                                <div class="col-md-2 mb-2">

                                    <button class="btn btn-primary btn-block">

                                        Cari

                                    </button>

                                </div>

                                <div class="col-md-2 mb-2">

                                    <a href="absensi.php" class="btn btn-secondary btn-block">

                                        Reset

                                    </a>

                                </div>

                            </div>

                        </form>

                        <hr>

                        <!-- TOTAL DATA -->

                        <div class="alert alert-info">

                            Total Data Absensi :
                            <strong>
                                <?= number_format($totalData) ?>
                            </strong>

                        </div>

                        <!-- TABLE -->

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
                                        <th>Aksi</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <?php

                            if(mysqli_num_rows($query) > 0):

                            $no = $start + 1;

                            while($row =
                            mysqli_fetch_assoc($query)):

                            ?>

                                    <tr>

                                        <td><?= $no++ ?></td>

                                        <td>
                                            <?= $row['hari'] ?>
                                        </td>

                                        <td>
                                            <?= date(
                                        'd-m-Y',
                                        strtotime(
                                            $row['tanggal']
                                        )
                                    ) ?>
                                        </td>

                                        <td>
                                            <?= $row['jam'] ?>
                                        </td>

                                        <td>
                                            <?= $row['nis'] ?>
                                        </td>

                                        <td>
                                            <?= $row['nama_siswa'] ?>
                                        </td>

                                        <td>
                                            <?= $row['kelas'] ?>
                                        </td>

                                        <td>

                                            <span class="badge-hadir">

                                                <?= $row['status'] ?>

                                            </span>

                                        </td>

                                        <td>

                                            <button class="btn btn-info btn-sm" data-toggle="modal"
                                                data-target="#detail<?= $row['id'] ?>">

                                                Detail

                                            </button>

                                        </td>

                                    </tr>

                                    <!-- MODAL -->

                                    <div class="modal fade" id="detail<?= $row['id'] ?>">

                                        <div class="modal-dialog">

                                            <div class="modal-content">

                                                <div class="modal-header bg-primary text-white">

                                                    <h5>
                                                        Detail Absensi
                                                    </h5>

                                                    <button class="close text-white" data-dismiss="modal">

                                                        &times;

                                                    </button>

                                                </div>

                                                <div class="modal-body">

                                                    <table class="table">

                                                        <tr>
                                                            <th>NIS</th>
                                                            <td><?= $row['nis'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Nama</th>
                                                            <td><?= $row['nama_siswa'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Kelas</th>
                                                            <td><?= $row['kelas'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Hari</th>
                                                            <td><?= $row['hari'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Tanggal</th>
                                                            <td><?= $row['tanggal'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Jam</th>
                                                            <td><?= $row['jam'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Status</th>
                                                            <td><?= $row['status'] ?></td>
                                                        </tr>

                                                    </table>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                    <?php

                            endwhile;

                            else:

                            ?>

                                    <tr>

                                        <td colspan="9" class="text-center">

                                            Tidak ada data

                                        </td>

                                    </tr>

                                    <?php endif; ?>

                                </tbody>

                            </table>

                        </div>

                        <!-- PAGINATION -->

                        <nav>

                            <ul class="pagination">

                                <?php

                        for(
                            $i=1;
                            $i<=$totalPage;
                            $i++
                        ):

                        ?>

                                <li class="page-item
                        <?= ($page==$i)?'active':'' ?>">

                                    <a class="page-link" href="?page=<?= $i ?>
                            &cari=<?= $cari ?>
                            &tanggal=<?= $tanggal ?>">

                                        <?= $i ?>

                                    </a>

                                </li>

                                <?php endfor; ?>

                            </ul>

                        </nav>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js">
    </script>

    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js">
    </script>

    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js">
    </script>

</body>

</html>