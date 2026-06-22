<?php
require_once '../config/config.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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

$sql = "
    SELECT
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
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query export PDF gagal: " . mysqli_error($conn));
}

if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$filterText = 'Semua Data Absensi';

if ($tanggal !== '') {
    $filterText = 'Tanggal: ' . date('d-m-Y', strtotime($tanggal));
}

if ($cari !== '') {
    $filterText .= ' | Pencarian: ' . $cari;
}

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 25px 25px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
        }

        h2 {
            margin: 0 0 4px;
            text-align: center;
            font-size: 18px;
        }

        .subtitle {
            margin-bottom: 18px;
            text-align: center;
            font-size: 10px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #0d6efd;
            color: #ffffff;
            font-size: 9px;
        }

        th, td {
            padding: 7px 5px;
            border: 1px solid #777;
            text-align: left;
        }

        .center {
            text-align: center;
        }

        .footer {
            margin-top: 18px;
            text-align: right;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>

    <h2>DATA ABSENSI WISUDA</h2>
    <div class="subtitle">' . e($filterText) . '</div>

    <table>
        <thead>
            <tr>
                <th class="center">No</th>
                <th>Hari</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
';

$no = 1;

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $html .= '
            <tr>
                <td class="center">' . $no++ . '</td>
                <td>' . e(hariIndonesia($row['hari'])) . '</td>
                <td class="center">' . e(date('d-m-Y', strtotime($row['tanggal']))) . '</td>
                <td class="center">' . e(substr($row['jam'], 0, 5)) . '</td>
                <td>' . e($row['nis']) . '</td>
                <td>' . e($row['nama_siswa']) . '</td>
                <td class="center">' . e($row['kelas']) . '</td>
                <td class="center">' . e($row['status']) . '</td>
            </tr>
        ';
    }
} else {
    $html .= '
        <tr>
            <td colspan="8" class="center">Tidak ada data absensi.</td>
        </tr>
    ';
}

$html .= '
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: ' . date('d-m-Y H:i:s') . '
    </div>

</body>
</html>
';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = 'data_absensi_wisuda_' . date('Ymd_His') . '.pdf';

$dompdf->stream($filename, [
    'Attachment' => true
]);

exit;