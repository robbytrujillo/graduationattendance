<?php

require_once '../config/config.php';
require_once '../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

$query = mysqli_query(
    $conn,
    "SELECT id, nis
     FROM users
     WHERE role = 'siswa'
     AND (qrcode IS NULL OR qrcode = '')"
);

$folder = '../assets/qrcode/';

if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

$berhasil = 0;

while ($siswa = mysqli_fetch_assoc($query)) {

    $token = bin2hex(random_bytes(16));

    $qrData = json_encode([
        'nis'   => $siswa['nis'],
        'token' => $token
    ]);

    $fileName = 'qr_' . $siswa['nis'] . '_' . time() . '.png';
    $filePath = $folder . $fileName;

    $result = Builder::create()
        ->writer(new PngWriter())
        ->data($qrData)
        ->size(300)
        ->margin(10)
        ->build();

    $result->saveToFile($filePath);

    $qrcode = 'assets/qrcode/' . $fileName;

    mysqli_query(
        $conn,
        "UPDATE users SET
            qr_token = '$token',
            qrcode = '$qrcode'
         WHERE id = '{$siswa['id']}'"
    );

    $berhasil++;
}

echo "QR Code berhasil dibuat untuk $berhasil siswa.";