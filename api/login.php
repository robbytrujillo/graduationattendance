<?php
header("Content-Type: application/json");
require_once "../config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$username = trim($data['username'] ?? $_POST['username'] ?? '');
$password = trim($data['password'] ?? $_POST['password'] ?? '');

if ($username === '' || $password === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Username dan password wajib diisi"
    ]);
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, username, password, role, nis, nama, kelas, qrcode
     FROM users
     WHERE username = ?
     AND role = 'siswa'
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo json_encode([
        "status" => "error",
        "message" => "Akun siswa tidak ditemukan"
    ]);
    exit;
}

if (!password_verify($password, $user['password'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Password salah"
    ]);
    exit;
}

$_SESSION['id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['nis'] = $user['nis'];
$_SESSION['nama'] = $user['nama'];
$_SESSION['kelas'] = $user['kelas'];
$_SESSION['qrcode'] = $user['qrcode'];

echo json_encode([
    "status" => "success",
    "message" => "Login berhasil",
    "redirect" => "../siswa/dashboard.php"
]);