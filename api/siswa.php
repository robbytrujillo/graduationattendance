<?php

require_once '../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ======================
    // GET ALL SISWA
    // ======================
    case 'GET':

        $result = mysqli_query($conn, "SELECT id, nis, nama_siswa, kelas, username, role, qrcode FROM users WHERE role='siswa'");

        $data = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }

        echo json_encode([
            "status" => true,
            "message" => "Data siswa berhasil diambil",
            "data" => $data
        ]);

        break;

    // ======================
    // POST (opsional tambah siswa manual via API)
    // ======================
    case 'POST':

        $input = json_decode(file_get_contents("php://input"), true);

        $nis   = $input['nis'] ?? '';
        $nama  = $input['nama_siswa'] ?? '';
        $kelas = $input['kelas'] ?? '';

        if ($nis == '' || $nama == '' || $kelas == '') {
            echo json_encode([
                "status" => false,
                "message" => "Data tidak lengkap"
            ]);
            exit;
        }

        $username = $nis;
        $password = password_hash('123456', PASSWORD_DEFAULT);

        $cek = mysqli_query($conn, "SELECT id FROM users WHERE nis='$nis' LIMIT 1");

        if (mysqli_num_rows($cek) > 0) {
            echo json_encode([
                "status" => false,
                "message" => "NIS sudah terdaftar"
            ]);
            exit;
        }

        mysqli_query($conn, "
            INSERT INTO users (nis, nama_siswa, kelas, username, password, role, created_at)
            VALUES ('$nis', '$nama', '$kelas', '$username', '$password', 'siswa', NOW())
        ");

        echo json_encode([
            "status" => true,
            "message" => "Siswa berhasil ditambahkan"
        ]);

        break;

    default:
        echo json_encode([
            "status" => false,
            "message" => "Method tidak diizinkan"
        ]);
}