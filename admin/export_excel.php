<?php
require_once '../config/config.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
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
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query export Excel gagal: " . mysqli_error($conn));
}

if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Absensi');

$sheet->mergeCells('A1:H1');
$sheet->setCellValue('A1', 'DATA ABSENSI WISUDA');

$sheet->mergeCells('A2:H2');

$keteranganFilter = 'Semua Data Absensi';

if ($tanggal !== '') {
    $keteranganFilter = 'Tanggal: ' . date('d-m-Y', strtotime($tanggal));
}

if ($cari !== '') {
    $keteranganFilter .= ' | Pencarian: ' . $cari;
}

$sheet->setCellValue('A2', $keteranganFilter);

$sheet->setCellValue('A4', 'No');
$sheet->setCellValue('B4', 'Hari');
$sheet->setCellValue('C4', 'Tanggal');
$sheet->setCellValue('D4', 'Jam');
$sheet->setCellValue('E4', 'NIS');
$sheet->setCellValue('F4', 'Nama Siswa');
$sheet->setCellValue('G4', 'Kelas');
$sheet->setCellValue('H4', 'Status');

$sheet->getStyle('A1:H1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A2:H2')->getFont()->setItalic(true);

$sheet->getStyle('A1:H2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:H2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

$sheet->getStyle('A4:H4')->getFont()->setBold(true);
$sheet->getStyle('A4:H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('A4:H4')->getFill()->setFillType(Fill::FILL_SOLID);
$sheet->getStyle('A4:H4')->getFill()->getStartColor()->setRGB('0D6EFD');
$sheet->getStyle('A4:H4')->getFont()->getColor()->setRGB('FFFFFF');

$rowNumber = 5;
$no = 1;

while ($row = mysqli_fetch_assoc($result)) {
    $sheet->setCellValue('A' . $rowNumber, $no++);
    $sheet->setCellValue('B' . $rowNumber, hariIndonesia($row['hari']));
    $sheet->setCellValue('C' . $rowNumber, date('d-m-Y', strtotime($row['tanggal'])));
    $sheet->setCellValue('D' . $rowNumber, substr($row['jam'], 0, 5));
    $sheet->setCellValueExplicit('E' . $rowNumber, $row['nis'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue('F' . $rowNumber, $row['nama_siswa']);
    $sheet->setCellValue('G' . $rowNumber, $row['kelas']);
    $sheet->setCellValue('H' . $rowNumber, $row['status']);

    $rowNumber++;
}

$lastRow = max(4, $rowNumber - 1);

$sheet->getStyle('A4:H' . $lastRow)->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

$sheet->getStyle('A4:H' . $lastRow)->getAlignment()
    ->setVertical(Alignment::VERTICAL_CENTER);

$sheet->getStyle('A5:A' . $lastRow)->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('B5:E' . $lastRow)->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('G5:H' . $lastRow)->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(12);
$sheet->getColumnDimension('E')->setWidth(18);
$sheet->getColumnDimension('F')->setWidth(35);
$sheet->getColumnDimension('G')->setWidth(18);
$sheet->getColumnDimension('H')->setWidth(15);

$sheet->getPageSetup()->setOrientation(
    \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
);

$filename = 'data_absensi_wisuda_' . date('Ymd_His') . '.xlsx';

if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;