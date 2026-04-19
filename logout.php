<?php
session_start();
session_destroy(); // 清除 Session
header("Location: profile.php"); // 回到個人中心
?>