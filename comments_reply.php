<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_submit'])) {
    
    // 後端權限驗證：只有目前該餐廳的店家允許提交回覆
    if (!$is_current_shop_owner) {
        echo "<script>
            alert('您沒有權限回覆此餐廳的評論！');
            window.location.href = window.location.href;
        </script>";
        exit;
    }

    $com_id = intval($_POST['com_id']);
    $reply_content = trim($_POST['reply_content']);

    if (!empty($reply_content)) {

        $stmt_reply = $conn->prepare("
            UPDATE comments
            SET reply_content = ?, 
                reply_created_at = NOW()
            WHERE com_id = ?
        ");

        $stmt_reply->bind_param("si", $reply_content, $com_id);
        $stmt_reply->execute();

        echo "<script>
            alert('回覆成功！');
            window.location.href = window.location.href;
        </script>";
        exit;
    }
}
?>