<?php
session_start();
include('db.php');
include('header.php');

// 確保使用者已登入，若為訪客則導向登入頁
if (!isset($_SESSION['u_id'])) {
    echo "<script>alert('請先登入後再使用回報功能！'); window.location.href='login.php';</script>";
    exit();
}

$error = '';
$success = false;

// 處理表單送出
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u_id = $_SESSION['u_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if (!empty($title) && !empty($description)) {
        // 將回報寫入資料庫
        $stmt = $conn->prepare("INSERT INTO bugreports (u_id, title, description, status) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("iss", $u_id, $title, $description);
        
        if ($stmt->execute()) {
            $success = true;
            echo "<script>
                alert('回報成功！感謝您的協助，管理員會盡快處理。');
                window.location.href='profile.php';
            </script>";
            exit();
        } else {
            $error = "回報失敗，請稍後再試。";
        }
    } else {
        $error = "請完整填寫標題與詳細描述！";
    }
}
?>

<style>
    body { background: #f8f9fa; font-family: "Microsoft JhengHei", sans-serif; }
    
    /* 頂部標題列 */
    .header-blue { 
        background: #002B5B; color: white; padding: 20px; 
        display: flex; align-items: center; gap: 15px; 
        position: sticky; top: 0; z-index: 100;
    }
    .back-btn { color: white; text-decoration: none; font-size: 20px; font-weight: bold; }
    
    .page-container { padding: 20px; padding-bottom: 100px; }
    
    /* 表單卡片設計 */
    .form-card { 
        background: white; border-radius: 15px; padding: 25px 20px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
    }
    
    .hint-text {
        font-size: 14px; color: #666; margin-top: 0; margin-bottom: 25px; 
        line-height: 1.6; padding: 12px; background: #f4f6f8; border-radius: 10px;
        border-left: 4px solid #FF8C42;
    }

    .form-group { margin-bottom: 20px; }
    .form-group label { 
        display: block; font-weight: bold; margin-bottom: 8px; 
        color: #333; font-size: 14px; 
    }
    .required { color: #E53935; }
    
    .clean-input { 
        width: 100%; padding: 14px; border: 1px solid #ddd; 
        border-radius: 12px; font-size: 15px; box-sizing: border-box; 
        background: #fafafa; transition: 0.2s;
    }
    .clean-input:focus {
        background: white; border-color: #002B5B; outline: none;
        box-shadow: 0 0 0 3px rgba(0,43,91,0.1);
    }
    
    textarea.clean-input { resize: vertical; min-height: 150px; line-height: 1.5; }
    
    .submit-btn { 
        width: 100%; background: #E6762D; color: white; border: none; 
        padding: 16px; border-radius: 14px; font-weight: bold; font-size: 16px; 
        cursor: pointer; box-shadow: 0 6px 0 #B35C22; transition: 0.1s; 
        margin-top: 10px;
    }
    .submit-btn:active { transform: translateY(3px); box-shadow: 0 3px 0 #B35C22; }
    
    .error-text { 
        color: #E53935; font-size: 13px; margin-bottom: 20px; 
        font-weight: bold; background: #ffebee; padding: 10px; border-radius: 8px;
    }
</style>

<div class="mobile-wrapper">
    <div class="header-blue">
        <a href="profile.php" class="back-btn">❮</a>
        <h1 style="margin: 0; font-size: 20px;">系統錯誤回報</h1>
    </div>

    <div class="page-container">
        <div class="form-card">
            <div class="hint-text">
                如果您在使用系統時遇到任何 Bug、畫面異常，或是發現學餐的菜單價格與熱量資訊有誤，請隨時通知我們！
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-text"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>問題主旨 <span class="required">*</span></label>
                    <input type="text" name="title" class="clean-input" placeholder="例如：心園鬆餅價格標示錯誤" required>
                </div>
                
                <div class="form-group">
                    <label>詳細狀況描述 <span class="required">*</span></label>
                    <textarea name="description" class="clean-input" placeholder="請盡量詳細描述您遇到的問題、在哪一個畫面發生，以利我們快速修正..." required></textarea>
                </div>
                
                <button type="submit" class="submit-btn">送出回報</button>
            </form>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>