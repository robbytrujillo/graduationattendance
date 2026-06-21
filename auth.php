<?php

function isLogin() {
    return isset($_SESSION['id']);
}

function checkRole($allowedRoles = []) {
    if (!isLogin()) {
        header("Location: ../login.php");
        exit;
    }

    if (!in_array($_SESSION['role'], $allowedRoles)) {
        echo "<script>alert('Akses ditolak!'); window.location.href='../login.php';</script>";
        exit;
    }
}