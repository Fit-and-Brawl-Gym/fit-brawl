<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "fit_and_brawl_gym";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
