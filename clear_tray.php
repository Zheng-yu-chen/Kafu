<?php
include('db.php');
$sql = "DELETE FROM tray"; // 實作時應加上 WHERE user_id = ...
if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
}
?>