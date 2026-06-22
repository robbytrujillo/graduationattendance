<?php
require_once 'config/config.php';

if (isset($_SESSION['id'])) {

    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    }

    if ($_SESSION['role'] == 'petugas') {
        header("Location: petugas/dashboard.php");
        exit;
    }

    if ($_SESSION['role'] == 'siswa') {
        header("Location: siswa/dashboard.php");
        exit;
    }
}

$error = '';

if (isset($_POST['login'])) {

    $username = mysqli_real_escape_string(
        $conn,
        trim($_POST['username'])
    );

    $password = trim($_POST['password']);

    $query = mysqli_query(
        $conn,
        "SELECT * FROM users WHERE username='$username' LIMIT 1"
    );

    if (mysqli_num_rows($query) > 0) {

        $user = mysqli_fetch_assoc($query);

        if (password_verify($password, $user['password'])) {

            $_SESSION['id'] = $user['id'];
            $_SESSION['nis'] = $user['nis'];
            $_SESSION['nama'] = $user['nama_siswa'];
            $_SESSION['kelas'] = $user['kelas'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
                exit;
            }

            if ($user['role'] == 'petugas') {
                header("Location: petugas/dashboard.php");
                exit;
            }

            if ($user['role'] == 'siswa') {
                header("Location: siswa/dashboard.php");
                exit;
            }

        } else {
            $error = "Password salah!";
        }

    } else {
        $error = "Username tidak ditemukan!";
    }

}
?>

<?php if(isset($_GET['logout'])) : ?>

<div class="alert alert-success">
    Berhasil logout dari sistem.
</div>

<?php endif; ?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login Wisuda QRCode</title>

    <link rel="icon" type="image/png" href="assets/img/logo.png">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <style>
    body {
        background: #f4f6f9;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login-card {
        width: 100%;
        max-width: 400px;
        border: none;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0, 0, 0, .1);
    }

    .login-header {
        background: #C5953F;
        color: white;
        padding: 7px;
        text-align: center;
    }

    .logo {
        width: 60px;
        height: 60px;
        object-fit: contain;
        margin-bottom: 2px;
    }

    .btn-login {
        background: #184978;
        border: none;
    }

    .btn-login:hover {
        background: #2f6faa;
    }

    .footer-text {
        font-size: 15px;
        color: #888;
    }
    </style>

</head>

<body>

    <div class="card login-card">

        <div class="login-header">

            <img src="assets/img/logo.png" class="logo"
                style="width: 80px; margin-left: 0%; margin-top: 0%; bg-color: white" alt="Logo">

            <h4 class="mb-0">
                🎓Graduation
            </h4>

            <!-- <small>
                QR Code Attendance System
            </small> -->

        </div>

        <div class="card-body p-4">

            <?php if($error != '') : ?>

            <div class="alert alert-danger">
                <?= $error ?>
            </div>

            <?php endif; ?>

            <form method="POST">

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control rounded-pill"
                        placeholder="Masukkan Nomor Induk Siswa" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control rounded-pill"
                        placeholder="Masukkan Password" required>
                </div>

                <button type="submit" name="login" class="btn btn-primary btn-block btn-login rounded-pill">

                    Login
                </button>

            </form>

            <hr>

            <div class="text-center footer-text">

                <div class="small">Copyright &copy; 2026 by <a href="https://robbyilham.com/"
                        style="text-decoration: none" target="_blank"> Robby
                        Ilham</a></div>

            </div>

        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>