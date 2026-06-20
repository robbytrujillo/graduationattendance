<?php

require_once '../config/config.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Template Data Siswa');

/*
|--------------------------------------------------------------------------
| HEADER EXCEL
|--------------------------------------------------------------------------
*/

$sheet->setCellValue('A1', 'NIS');
$sheet->setCellValue('B1', 'Nama Siswa');
$sheet->setCellValue('C1', 'Kelas');

/*
|--------------------------------------------------------------------------
| CONTOH DATA
|--------------------------------------------------------------------------
*/

$sheet->setCellValue('A2', '2026001');
$sheet->setCellValue('B2', 'Ahmad Fauzan');
$sheet->setCellValue('C2', 'XII IPA 1');

$sheet->setCellValue('A3', '2026002');
$sheet->setCellValue('B3', 'Siti Aisyah');
$sheet->setCellValue('C3', 'XII IPA 2');

/*
|--------------------------------------------------------------------------
| STYLE HEADER
|--------------------------------------------------------------------------
*/

$sheet->getStyle('A1:C1')->getFont()->setBold(true);

$sheet->getStyle('A1:C1')->getAlignment()->setHorizontal(
    Alignment::HORIZONTAL_CENTER
);

$sheet->getStyle('A1:C1')->getAlignment()->setVertical(
    Alignment::VERTICAL_CENTER
);

$sheet->getStyle('A1:C3')->getBorders()->getAllBorders()->setBorderStyle(
    Border::BORDER_THIN
);

$sheet->getStyle('A1:C1')->getFill()->setFillType(
    Fill::FILL_SOLID
);

$sheet->getStyle('A1:C1')->getFill()->getStartColor()->setRGB(
    '28A745'
);

/*
|--------------------------------------------------------------------------
| UKURAN KOLOM
|--------------------------------------------------------------------------
*/

$sheet->getColumnDimension('A')->setWidth(18);
$sheet->getColumnDimension('B')->setWidth(35);
$sheet->getColumnDimension('C')->setWidth(20);

$sheet->getRowDimension(1)->setRowHeight(25);

/*
|--------------------------------------------------------------------------
| FORMAT NIS SEBAGAI TEXT
|--------------------------------------------------------------------------
*/

$sheet->getStyle('A2:A1000')->getNumberFormat()->setFormatCode('@');

/*
|--------------------------------------------------------------------------
| DOWNLOAD FILE
|--------------------------------------------------------------------------
*/

$fileName = 'template_data_siswa.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;