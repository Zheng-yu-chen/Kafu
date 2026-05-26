<?php
// 🎯 這是獨立的圓角詢問對話框組件（不包含任何多餘程式碼，純粹作為前端組件）
?>
<div id="trayConfirmModalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); justify-content: center; align-items: center; z-index: 100005; font-family: 'Microsoft JhengHei', sans-serif;">
    <div style="background: white; padding: 22px 20px; border-radius: 28px; width: 85%; max-width: 320px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); text-align: center; box-sizing: border-box;">
        
        <p style="margin: 0 0 25px 0; font-size: 18px; color: #333333; font-weight: bold; letter-spacing: 0.5px;">
            已成功加入托盤!
        </p>
        
        <div style="display: flex; gap: 15px; justify-content: center;">
            <button type="button" style="flex: 1; border: none; padding: 13px 0; border-radius: 16px; font-weight: bold; font-size: 15px; cursor: pointer;background: #EEEEEE; color: #555555 ; transition: background 0.2s;" onclick="closeTrayConfirmModal()">
                繼續選填
            </button>
            
            <button type="button" style="flex: 1; border: none; padding: 13px 0; 
            border-radius: 16px; font-weight: bold; font-size: 15px; cursor: pointer; background:#FF8C42; color: white; transition: background 0.2s;" onclick="goToTrayPageDirect()">
                前往托盤
            </button>
        </div>
    </div>
</div>

<script>
    // 讓這個漂亮的確認視窗現身
    if (typeof showTrayConfirmModal !== 'function') {
        function showTrayConfirmModal() {
            document.getElementById('trayConfirmModalOverlay').style.display = 'flex';
        }
    }

    // 紅色按鈕：純粹隱藏彈窗，留在原地繼續點餐
    if (typeof closeTrayConfirmModal !== 'function') {
        function closeTrayConfirmModal() {
            document.getElementById('trayConfirmModalOverlay').style.display = 'none';
        }
    }

    // 灰色按鈕：直接跳轉到托盤頁
    if (typeof goToTrayPageDirect !== 'function') {
        function goToTrayPageDirect() {
            window.location.href = 'tray.php';
        }
    }
</script>