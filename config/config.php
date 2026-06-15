<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_graduationattendace";

$conn = mysqli_connect(
    $host,
    $user,
    $pass,
    $db
);

if(!$conn){
    die("Koneksi gagal : ".mysqli_connect_error());
}

session_start();
date_default_timezone_set('Asia/Jakarta');