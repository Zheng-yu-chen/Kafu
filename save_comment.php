<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['u_id'])) {
    $u_id = $_SESSION['u_id'];
    $item_id = $_POST['item_id'];
    $rating = $_POST['rating'];
    $content = $_POST['content'];
    $com_img = null; 


    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "food/";

        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        

        $new_filename = "food_" . $u_id . "_" . time() . "." . $ext;
        $target_file = $target_dir . $new_filename;


        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $com_img = $new_filename; 
        }
    }
    

    $stmt = $conn->prepare("INSERT INTO comments (u_id, item_id, rating, content, com_img) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiss", $u_id, $item_id, $rating, $content, $com_img);
    
    if ($stmt->execute()) {
        echo "<script>alert('評價提交成功！'); window.location.href='comments.php';</script>";
    } else {
        echo "錯誤：" . $conn->error;
    }
} else {
    header("Location: comments.php");
}
?>