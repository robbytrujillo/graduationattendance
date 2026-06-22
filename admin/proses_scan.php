<?php
require_once '../config/config.php';

header('Content-Type: text/html; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| Validasi login admin/petugas
|--------------------------------------------------------------------------
*/
if (
    !isset($_SESSION['id']) ||
    !in_array($_SESSION['role'] ?? '', ['admin', 'petugas'])
) {
    exit("
        <div class='alert alert-danger mb-0'>
            <i class='fas fa-lock'></i>
            Session tidak valid. Silakan login kembali.
        </div>
    ");
}

/*
|--------------------------------------------------------------------------
| Validasi QR dari AJAX scanner
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("
        <div class='alert alert-danger mb-0'>
            Metode request tidak valid.
        </div>
    ");
}

$qrToken = trim($_POST['qr'] ?? '');

if ($qrToken === '') {
    exit("
        <div class='alert alert-danger mb-0'>
            <i class='fas fa-times-circle'></i>
            QR Code tidak terbaca.
        </div>
    ");
}

/*
|--------------------------------------------------------------------------
| Cari siswa berdasarkan qr_token
|--------------------------------------------------------------------------
*/
$sqlSiswa = "
    SELECT
        id,
        nis,
        nama_siswa,
        kelas,
        qr_token
    FROM users
    WHERE qr_token = ?
      AND role = 'siswa'
    LIMIT 1
";

$stmtSiswa = mysqli_prepare($conn, $sqlSiswa);

if (!$stmtSiswa) {
    exit("
        <div class='alert alert-danger mb-0'>
            Query siswa gagal: " . e(mysqli_error($conn)) . "
        </div>
    ");
}

mysqli_stmt_bind_param($stmtSiswa, "s", $qrToken);
mysqli_stmt_execute($stmtSiswa);

$resultSiswa = mysqli_stmt_get_result($stmtSiswa);
$siswa = mysqli_fetch_assoc($resultSiswa);

if (!$siswa) {
    exit("
        <div class='alert alert-danger mb-0'>
            <i class='fas fa-times-circle'></i>
            QR Code tidak terdaftar atau bukan milik siswa.
        </div>
    ");
}

$userId  = (int) $siswa['id'];
$tanggal = date('Y-m-d');
$jam     = date('H:i:s');

/*
|--------------------------------------------------------------------------
| Cek absensi hari ini
|--------------------------------------------------------------------------
*/
$sqlCek = "
    SELECT id
    FROM absensi
    WHERE user_id = ?
      AND tanggal = ?
    LIMIT 1
";

$stmtCek = mysqli_prepare($conn, $sqlCek);

if (!$stmtCek) {
    exit("
        <div class='alert alert-danger mb-0'>
            Query cek absensi gagal: " . e(mysqli_error($conn)) . "
        </div>
    ");
}

mysqli_stmt_bind_param($stmtCek, "is", $userId, $tanggal);
mysqli_stmt_execute($stmtCek);

$resultCek = mysqli_stmt_get_result($stmtCek);

if (mysqli_num_rows($resultCek) > 0) {
    exit("
        <div class='alert alert-warning mb-0'>
            <i class='fas fa-exclamation-triangle'></i>
            <strong>" . e($siswa['nama_siswa']) . ' ' . 'kelas' . ' ' . e($siswa['kelas']) . "</strong> sudah absen hari ini.
        </div>
    ");
}

/*
|--------------------------------------------------------------------------
| Simpan absensi
|--------------------------------------------------------------------------
*/
$sqlInsert = "
    INSERT INTO absensi (user_id, hari, tanggal, jam, status)
    VALUES (?, DAYNAME(CURDATE()), ?, ?, 'Hadir')
";

$stmtInsert = mysqli_prepare($conn, $sqlInsert);

if (!$stmtInsert) {
    exit("
        <div class='alert alert-danger mb-0'>
            Query simpan absensi gagal: " . e(mysqli_error($conn)) . "
        </div>
    ");
}

mysqli_stmt_bind_param($stmtInsert, "iss", $userId, $tanggal, $jam);

if (mysqli_stmt_execute($stmtInsert)) {
    echo "
        <div class='alert alert-success mb-0'>
            <i class='fas fa-check-circle'></i>
            <strong>Absensi berhasil direkam.</strong>
            <hr class='my-2'>
            <strong>Nama:</strong> " . e($siswa['nama_siswa']) . "<br>
            <strong>NIS:</strong> " . e($siswa['nis']) . "<br>
            <strong>Kelas:</strong> " . e($siswa['kelas']) . "
        </div>
    ";
} else {
    echo "
        <div class='alert alert-danger mb-0'>
            <i class='fas fa-times-circle'></i>
            Gagal menyimpan absensi: " . e(mysqli_error($conn)) . "
        </div>
    ";
}