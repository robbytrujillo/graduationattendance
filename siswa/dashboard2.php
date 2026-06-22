<?php
require_once "../config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Proteksi halaman khusus siswa
|--------------------------------------------------------------------------
*/
if (
    !isset($_SESSION['id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'siswa'
) {
    header("Location: ../index.html");
    exit;
}

$userId = (int) $_SESSION['id'];

/*
|--------------------------------------------------------------------------
| Ambil data siswa dari tabel users
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        id,
        nis,
        nama_siswa,
        kelas,
        username,
        qr_token,
        role
    FROM users
    WHERE id = ?
      AND role = 'siswa'
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$siswa = mysqli_fetch_assoc($result);

if (!$siswa) {
    $_SESSION = [];
    session_destroy();

    header("Location: ../login.php");
    exit;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$nama  = $siswa['nama_siswa'] ?? '-';
$nis   = $siswa['nis'] ?? '-';
$kelas = $siswa['kelas'] ?? '-';

/*
|--------------------------------------------------------------------------
| QR Code Kehadiran
|--------------------------------------------------------------------------
| Isi QR hanya qr_token agar sesuai dengan:
| admin/proses_scan.php
|--------------------------------------------------------------------------
*/
$qrText = trim($siswa['qr_token'] ?? '');

if ($qrText === '') {
    die("
        <div style='
            font-family: Poppins, Arial, sans-serif;
            max-width: 500px;
            margin: 80px auto;
            padding: 25px;
            border-radius: 14px;
            background: #fff3cd;
            color: #856404;
            text-align: center;
        '>
            <h3>QR Token Belum Tersedia</h3>
            <p>Silakan hubungi admin untuk membuat QR Code siswa.</p>
        </div>
    ");
}

$qrCodeOnline = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrText);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard Wisudawan - <?= e($nama); ?></title>

    <link rel="icon" type="image/png" href="../assets/img/logo.png">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
    :root {
        --navy: #0b2440;
        --navy-light: #1e568b;
        --gold: #c99b3b;
        --gold-light: #f0d387;
        --text: #1d2d40;
        --muted: #8390a0;
        --soft: #f4f7fb;
    }

    * {
        font-family: "Poppins", sans-serif;
    }

    body {
        min-height: 100vh;
        margin: 0;
        background:
            radial-gradient(circle at 5% 5%, rgba(245, 209, 124, .30), transparent 34%),
            radial-gradient(circle at 95% 100%, rgba(65, 148, 220, .22), transparent 34%),
            linear-gradient(135deg, var(--navy), #123b63);
        color: #ffffff;
    }

    .page-wrapper {
        width: 100%;
        max-width: 540px;
        min-height: 100vh;
        margin: auto;
        padding: 20px 16px 32px;
    }

    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 28px;
    }

    .brand {
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .4px;
    }

    .brand i {
        color: var(--gold-light);
        margin-right: 6px;
    }

    .btn-logout {
        padding: 8px 13px;
        border: 1px solid rgba(255, 255, 255, .24);
        border-radius: 12px;
        background: rgba(255, 255, 255, .10);
        color: #ffffff;
        font-size: 12px;
        text-decoration: none;
        transition: .2s ease;
    }

    .btn-logout:hover {
        color: #ffffff;
        text-decoration: none;
        background: rgba(255, 255, 255, .18);
    }

    .hero {
        margin-bottom: 22px;
    }

    .hero small {
        display: block;
        margin-bottom: 6px;
        color: var(--gold-light);
        font-size: 13px;
        font-weight: 600;
    }

    .hero h1 {
        margin: 0;
        font-size: 25px;
        font-weight: 700;
        line-height: 1.38;
    }

    .student-card {
        overflow: hidden;
        border-radius: 25px;
        background: #ffffff;
        color: var(--text);
        box-shadow: 0 20px 50px rgba(0, 0, 0, .24);
    }

    .card-header-wisuda {
        position: relative;
        padding: 28px 20px 68px;
        overflow: hidden;
        background: linear-gradient(135deg, #b8832f, #e6c265);
        color: #ffffff;
        text-align: center;
    }

    .card-header-wisuda::before {
        content: "";
        position: absolute;
        top: -120px;
        right: -85px;
        width: 250px;
        height: 250px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .13);
    }

    .graduation-icon {
        position: relative;
        z-index: 1;
        width: 65px;
        height: 65px;
        margin: 0 auto 12px;
        display: flex;
        justify-content: center;
        align-items: center;
        border-radius: 20px;
        background: rgba(255, 255, 255, .20);
        font-size: 28px;
    }

    .card-header-wisuda h2 {
        position: relative;
        z-index: 1;
        margin: 0;
        font-size: 20px;
        font-weight: 700;
    }

    .card-header-wisuda p {
        position: relative;
        z-index: 1;
        margin: 6px 0 0;
        font-size: 11px;
        opacity: .92;
    }

    .card-body-wisuda {
        padding: 0 20px 24px;
    }

    .avatar {
        width: 96px;
        height: 96px;
        margin: -49px auto 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 5px solid #ffffff;
        border-radius: 50%;
        background: #edf2f7;
        color: var(--gold);
        font-size: 36px;
        box-shadow: 0 8px 22px rgba(0, 0, 0, .14);
    }

    .student-title {
        margin-bottom: 21px;
        text-align: center;
    }

    .student-title h3 {
        margin: 0;
        color: var(--text);
        font-size: 19px;
        font-weight: 700;
    }

    .student-title p {
        margin: 4px 0 0;
        color: var(--muted);
        font-size: 12px;
    }

    .info-box {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        padding: 13px;
        border-radius: 16px;
        background: var(--soft);
    }

    .info-box-icon {
        width: 41px;
        height: 41px;
        margin-right: 12px;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-shrink: 0;
        border-radius: 12px;
        background: #fff0c9;
        color: #b8832f;
    }

    .info-box-label {
        display: block;
        margin-bottom: 2px;
        color: var(--muted);
        font-size: 10px;
    }

    .info-box-value {
        display: block;
        color: var(--text);
        font-size: 14px;
        font-weight: 600;
    }

    .qr-area {
        margin-top: 21px;
        padding: 20px 16px;
        border-radius: 19px;
        background: linear-gradient(135deg, #0c2b4c, var(--navy-light));
        color: #ffffff;
        text-align: center;
    }

    .qr-area h4 {
        margin: 0 0 5px;
        font-size: 15px;
        font-weight: 600;
    }

    .qr-area p {
        margin: 0 0 15px;
        font-size: 11px;
        opacity: .80;
    }

    .qr-container {
        width: 210px;
        margin: auto;
        padding: 10px;
        border-radius: 16px;
        background: #ffffff;
    }

    .qr-container img {
        width: 100%;
        height: auto;
        display: block;
    }

    .footer {
        margin-top: 20px;
        color: rgba(255, 255, 255, .66);
        font-size: 10px;
        text-align: center;
    }

    @media (min-width: 768px) {
        .page-wrapper {
            min-height: auto;
            margin-top: 28px;
            margin-bottom: 28px;
        }
    }
    </style>
</head>

<body>

    <div class="page-wrapper">

        <div class="topbar">
            <div class="brand">
                <i class="fas fa-graduation-cap"></i>
                GRADUATION ATTENDANCE
            </div>

            <a href="../api/logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt mr-1"></i>
                Keluar
            </a>
        </div>

        <div class="hero">
            <small>Selamat datang,</small>
            <h1>Wisudawan / Wisudawati<br><?= e($nama); ?></h1>
        </div>

        <div class="student-card">

            <div class="card-header-wisuda">
                <div class="graduation-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>

                <h2>Peserta Wisuda</h2>
                <p>Tunjukkan QR Code kepada panitia saat registrasi.</p>
            </div>

            <div class="card-body-wisuda">

                <div class="avatar">
                    <i class="fas fa-user"></i>
                </div>

                <div class="student-title">
                    <h3><?= e($nama); ?></h3>
                    <p>@<?= e($siswa['username']); ?></p>
                </div>

                <div class="info-box">
                    <div class="info-box-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <span class="info-box-label">Nomor Induk Siswa</span>
                        <span class="info-box-value"><?= e($nis); ?></span>
                    </div>
                </div>

                <div class="info-box">
                    <div class="info-box-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <div>
                        <span class="info-box-label">Kelas</span>
                        <span class="info-box-value"><?= e($kelas); ?></span>
                    </div>
                </div>

                <div class="qr-area">
                    <h4>
                        <i class="fas fa-qrcode mr-1"></i>
                        QR Code Kehadiran
                    </h4>

                    <p>Gunakan QR Code ini untuk proses absensi wisuda.</p>

                    <div class="qr-container">
                        <img src="<?= e($qrCodeOnline); ?>" alt="QR Code <?= e($nama); ?>">
                    </div>
                </div>

            </div>
        </div>

        <div class="footer">
            © <?= date('Y'); ?> Graduation Attendance System
        </div>

    </div>

</body>

</html>