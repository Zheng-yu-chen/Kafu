<?php
session_start();
$id = isset($_GET['id']) ? intval($_GET['id']) : -1;

if ($id >= 0 && isset($_SESSION['tray'][$id])) {
    unset($_SESSION['tray'][$id]);
    // 重新排列陣列，避免破洞
    $_SESSION['tray'] = array_values($_SESSION['tray']);
}
header("Location: tray.php");
exit();
?>