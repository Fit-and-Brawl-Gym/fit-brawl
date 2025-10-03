<?php

$host = "localhost";
$user = "root";
$password = "";
<<<<<<< HEAD
$database =  "fit_and_brawl_gym";

$conn = mysqli_connect($host, $user, $password, $database);

if ($conn-> connect_error) {
    die("Connection failed: " . $conn-> connect_error);
}

=======
$database = "fit_and_brawl_gym";


$conn = mysqli_connect($host, $user, $password, $database);


if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}



>>>>>>> 2eea5f6 (Implement login and sign-up backend with error handling)
?>