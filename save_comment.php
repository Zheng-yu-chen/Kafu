<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['u_id'])) {
    $u_id = $_SESSION['u_id'];
    $item_id = $_POST['item_id'];
    $rating = $_POST['rating'];
    $content = $_POST['content'];
    
    // 預設 status = 0，代表需要管理員審核
    $stmt = $conn->prepare("INSERT INTO comments (u_id, item_id, rating, content, status) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("iiis", $u_id, $item_id, $rating, $content);
    
    if ($stmt->execute()) {
        echo "<script>alert('評價提交成功！請靜候管理員審核。'); window.location.href='comments.php';</script>";
    } else {
        echo "錯誤：" . $conn->error;
    }
} else {
    header("Location: comments.php");
}
?>