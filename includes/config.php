<?php

$host = "localhost";
$user = "root";
$password = "";
$database =  "fit_and_brawl_gym";

$conn = mysqli_connect($host, $user, $password, $database);

if ($conn-> connect_error) {
    die("Connection failed: " . $conn-> connect_error);
}

?>