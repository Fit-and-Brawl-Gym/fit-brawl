<?php
session_start();
session_unset();
session_destroy();

// Delete remember-me cookies if any
setcookie('email', '', time() - 3600, "/");
setcookie('password', '', time() - 3600, "/");

header("Location: index.php");
exit;
?>