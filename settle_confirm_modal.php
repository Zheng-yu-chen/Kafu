<?php
// 🎯 防呆機制：如果別的檔案已經啟動過 session，這裡就不要重複啟動
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('db.php'); // 引入與輔大資料庫的連線

// 🎯 第一階段：後端苦力（當使用者從 tray.php 按下結算送過來時才執行）
$settle_success = false;
$error_msg = "";

if (!isset($_SESSION['u_id'])) {
    $error_msg = "請先登入系統！";
} else {
    $u_id = intval($_SESSION['u_id']);
    
    if (!empty($_SESSION['tray'])) {
        // 跑迴圈把目前暫存托盤裡的菜，一筆一筆用 SQL 刻進你們真正的歷史紀錄表 consumptionlogs
        foreach ($_SESSION['tray'] as $item) {
            $item_id = intval($item['item_id']);
            
            // 🎯 1. 用餐時段代號轉換 (對齊你們資料表的 1, 2, 4 代號)
            // 早餐/全天=1, 午餐=2, 晚餐=3, 點心=4 
            $meal_string = $item['meal_time'];
            $daily_meal_code = 1; // 預設早餐
            if ($meal_string === '午餐') { $daily_meal_code = 2; }
            elseif ($meal_string === '晚餐') { $daily_meal_code = 3; }
            elseif ($meal_string === '點心') { $daily_meal_code = 4; }
            elseif ($meal_string === '全天') { $daily_meal_code = 1; }

            // 🎯 2. 去 items 資料表撈出這道菜單筆的熱量與蛋白質，用來存入你們的 total_calories 與 total_protein
            $item_info_sql = "SELECT calories, protein FROM items WHERE item_id = $item_id";
            $item_info_res = $conn->query($item_info_sql);
            $cal_val = 0;
            $pro_val = 0;
            if ($item_info_res && $item_info_res->num_rows > 0) {
                $item_row = $item_info_res->fetch_assoc();
                // 單筆營養素 乘上 學生點的數量
                $qty = isset($item['quantity']) ? intval($item['quantity']) : 1;
                $cal_val = intval($item_row['calories']) * $qty;
                $pro_val = floatval($item_row['protein']) * $qty;
            }

                // 🚀 3. 執行符合你們資料庫欄位的 SQL 寫入指令
                // 將使用者在托盤選的日期與現在時間組成 recorded_at，避免寫入為當前日期
                $eat_date = isset($item['eat_date']) ? $item['eat_date'] : date('Y-m-d');
                $recorded_at = $eat_date . ' ' . date('H:i:s');

                // 完美對齊：u_id, item_id, daily_meal, total_calories, total_protein, recorded_at
                $sql = "INSERT INTO consumptionlogs (u_id, item_id, daily_meal, total_calories, total_protein, recorded_at) 
                    VALUES ($u_id, $item_id, $daily_meal_code, $cal_val, $pro_val, '" . $conn->real_escape_string($recorded_at) . "')";
                $conn->query($sql);
        }
        
        // 資料都安全進了資料庫歷史表，把目前網頁的暫存托盤 Session 徹底清空
        unset($_SESSION['tray']);
        $settle_success = true;
    } else {
        $error_msg = "托盤是空的，無法結算！";
    }
}

// 引入全域導覽列樣式
include('header.php'); 
?>

<style>
    /* 滿版變暗遮罩背景 */
    .settle-overlay { 
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(0,0,0,0.4); display: flex; justify-content: center; 
        align-items: center; z-index: 100005; font-family: 'Microsoft JhengHei', sans-serif; 
    }
    /* 大圓角、扁平精緻版純白卡片 */
    .settle-card { 
        background: white; padding: 22px 20px; border-radius: 28px; 
        width: 85%; max-width: 320px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); 
        text-align: center; box-sizing: border-box; 
    }
</style>

<div class="settle-overlay">
    <div class="settle-card">
        <?php if ($settle_success): ?>
            <p style="margin: 0 0 18px 0; font-size: 18px; color: #333333; font-weight: bold; letter-spacing: 0.5px;">
                已成功寫入歷史紀錄！
            </p>
            
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button type="button" style="flex: 1; border: none; padding: 11px 0; border-radius: 16px; font-weight: bold; font-size: 15px; cursor: pointer; background: #EEEEEE; color: #555555;" onclick="stayOnTray()">
                    停留此頁
                </button>
                
                <button type="button" style="flex: 1; border: none; padding: 11px 0; border-radius: 16px; font-weight: bold; font-size: 15px; cursor: pointer; background: #4CAF50; color: white;" onclick="goToHistory()">
                    前往歷史紀錄
                </button>
            </div>
        <?php else: ?>
            <p style="margin: 0 0 18px 0; font-size: 17px; color: #E53935; font-weight: bold;">
                ❌ 錯誤：<?php echo $error_msg; ?>
            </p>
            <button type="button" style="width: 100%; border: none; padding: 11px 0; border-radius: 16px; font-weight: bold; background: #002B5B; color: white;" onclick="window.history.back();">
                返回上一頁
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
    // 按下停留此頁：帶他回托盤頁
    function stayOnTray() {
        window.location.href = 'tray.php';
    }

    // 按下前往歷史：帶他去看歷史報表
    function goToHistory() {
        window.location.href = 'history.php';
    }
</script>

<?php 
// 引入底部版權宣告樣式
include('footer.php'); 
?>