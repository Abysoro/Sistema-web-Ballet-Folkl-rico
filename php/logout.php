<?php
session_start();
session_unset();
session_destroy();

header("Location: /sistema_ballet/php/login.php");
exit;
?>
