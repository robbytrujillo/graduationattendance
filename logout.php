<?php

session_start();

/*
|--------------------------------------------------------------------------
| Hapus Semua Session
|--------------------------------------------------------------------------
*/

$_SESSION = [];

session_unset();
session_destroy();

/*
|--------------------------------------------------------------------------
| Hapus Cookie Session (Opsional)
|--------------------------------------------------------------------------
*/

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/*
|--------------------------------------------------------------------------
| Redirect ke Halaman Login
|--------------------------------------------------------------------------
*/

header("Location: login.php?logout=success");
exit;