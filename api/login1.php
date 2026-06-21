<?php
header("Content-Type: application/json");
require_once "../config/config.php";

// FIX session_start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ambil raw input JSON
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// fallback jika bukan JSON
$username = $data['username'] ?? $_POST['username'] ?? '';
$password = $data['password'] ?? $_POST['password'] ?? '';

// validasi
if (empty($username) || empty($password)) {
    echo json_encode([
        "status" => "error",
        "message" => "Username dan password wajib diisi",
        "debug" => [
            "raw_input" => $raw
        ]
    ]);
    exit;
}

// query siswa saja
$query = mysqli_query($conn, "
    SELECT * FROM users 
    WHERE username = '$username' 
    AND role = 'siswa'
    LIMIT 1
");

$user = mysqli_fetch_assoc($query);

if (!$user) {
    echo json_encode([
        "status" => "error",
        "message" => "User tidak ditemukan atau bukan siswa"
    ]);
    exit;
}

// password check
if (!password_verify($password, $user['password'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Password salah"
    ]);
    exit;
}

// session
$_SESSION['id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

echo json_encode([
    "status" => "success",
    "message" => "Login berhasil",
    "data" => $user
]);