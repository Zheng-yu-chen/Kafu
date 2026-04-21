<?php
session_start();
$_SESSION['tray'] = [];
header("Location: tray.php");
exit();
?>