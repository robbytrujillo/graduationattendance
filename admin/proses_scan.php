<?php
require_once '../config/config.php';

if (!isset($_POST['qr'])) {
    exit("QR tidak valid");
}

$data = json_decode($_POST['qr'], true);

if (!isset($data['nis'])) {
    exit("<div class='alert alert-danger'>QR tidak sesuai format</div>");
}

$nis = mysqli_real_escape_string($conn, $data['nis']);

// cari user
$user = mysqli_query($conn, "SELECT id FROM users WHERE nis='$nis' LIMIT 1");
if (mysqli_num_rows($user) == 0) {
    exit("<div class='alert alert-danger'>Siswa tidak ditemukan</div>");
}

$row = mysqli_fetch_assoc($user);
$user_id = $row['id'];

$tanggal = date('Y-m-d');
$jam     = date('H:i:s');

/* CEK SUDAH ABSEN HARI INI */
$cek = mysqli_query($conn, "
    SELECT id FROM absensi 
    WHERE user_id='$user_id' AND tanggal='$tanggal'
");

if (mysqli_num_rows($cek) > 0) {
    exit("<div class='alert alert-warning'>Sudah absen hari ini</div>");
}

/* INSERT ABSENSI */
$insert = mysqli_query($conn, "
    INSERT INTO absensi (user_id, hari, tanggal, jam, status)
    VALUES ('$user_id', DAYNAME(CURDATE()), '$tanggal', '$jam', 'Hadir')
");

if ($insert) {
    echo "<div class='alert alert-success'>Absensi berhasil</div>";
} else {
    echo "<div class='alert alert-danger'>Gagal menyimpan absensi</div>";
}