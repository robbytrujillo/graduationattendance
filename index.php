<?php

session_start();

if (isset($_SESSION['id'])) {

    switch ($_SESSION['role']) {

        case 'admin':
            header("Location: admin/dashboard.php");
            break;

        case 'petugas':
            header("Location: petugas/dashboard.php");
            break;

        case 'siswa':
            header("Location: siswa/dashboard.php");
            break;

        default:
            header("Location: login.php");
            break;
    }

} else {

    header("Location: login.php");
}

exit;