<?php
include('db.php');
$id = $_GET['id'];
$sql = "DELETE FROM tray WHERE id = $id";
if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>