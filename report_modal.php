<div id="reportModal" class="modal-overlay" style="display: none; background-color: rgba(0,0,0,0.5); cursor: default; position: fixed; top: 0; left: 0; width: 100%; height: 100%; justify-content: center; align-items: center; z-index: 99999;">
    <div style="background: white; padding: 22px; border-radius: 15px; width: 75%; max-width: 300px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); cursor: auto;">
        <h3 style="margin: 0 0 15px; font-size: 18px; color: #333; text-align: center;">請選擇檢舉原因</h3>
        
        <input type="hidden" id="targetReportComId" value="">

        <form id="reportForm" onchange="toggleOtherTextarea()" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 15px;">
            <label style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: #444; cursor: pointer;">
                <input type="radio" name="report_reason" value="1" required checked> 不雅用語
            </label>
            <label style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: #444; cursor: pointer;">
                <input type="radio" name="report_reason" value="2"> 不雅照片
            </label>
            <label style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: #444; cursor: pointer;">
                <input type="radio" name="report_reason" value="3"> 偏離主題
            </label>
            <label style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: #444; cursor: pointer;">
                <input type="radio" name="report_reason" value="4"> 垃圾內容
            </label>
            <label style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: #444; cursor: pointer;">
                <input type="radio" name="report_reason" value="5"> 歧視或仇恨言論
            </label>
            <label style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: #444; cursor: pointer;">
                <input type="radio" name="report_reason" value="6"> 內容有害
            </label>
            <label style="display: flex; align-items: center; gap: 10px; font-size: 15px; color: #444; cursor: pointer;">
                <input type="radio" name="report_reason" value="7" id="reasonOtherRadio"> 其他原因
            </label>

            <textarea id="otherReasonTextarea" placeholder="請具體說明檢舉原因（限50字）..." max-length="50" style="display: none; width: 100%; height: 60px; padding: 8px; border-radius: 8px; border: 1px solid #ddd; resize: none; font-size: 13px; box-sizing: border-box; outline: none; margin-top: 5px;"></textarea>
        </form>

        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button onclick="closeReportModal()" style="background: #eee; color: #555; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: bold;">取消</button>
            <button onclick="submitReport()" style="background: #D32F2F; color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: bold; box-shadow: 0 2px 6px rgba(211,47,47,0.2);">送出檢舉</button>
        </div>
    </div>
</div>

<script>   
    // 🎯 控制動態輸入框顯示/隱藏的函式
    function toggleOtherTextarea() {
        const otherRadio = document.getElementById('reasonOtherRadio');
        const textarea = document.getElementById('otherReasonTextarea');
        if (otherRadio && otherRadio.checked) {
            textarea.style.display = 'block';
            textarea.focus();
        } else {
            textarea.style.display = 'none';
            textarea.value = ''; 
        }
    }

    function reportComment(comId) {
        document.getElementById('targetReportComId').value = comId;
        document.getElementById('reportModal').style.display = 'flex';
        const toolbar = document.querySelector('footer') || document.querySelector('nav') || document.querySelector('.footer');
        if (toolbar) toolbar.style.display = 'none';
    }

    function closeReportModal() {
        document.getElementById('reportModal').style.display = 'none';
        document.getElementById('reportForm').reset();
        document.getElementById('otherReasonTextarea').style.display = 'none'; // 隱藏輸入框
        const toolbar = document.querySelector('footer') || document.querySelector('nav') || document.querySelector('.footer');
        if (toolbar) toolbar.style.display = 'flex';
    }

    function submitReport() {
        const comId = document.getElementById('targetReportComId').value;
        const reasonElement = document.querySelector('input[name="report_reason"]:checked');
        
        if (!reasonElement) {
            alert('請選擇檢舉原因！');
            return;
        }
        
        const reason = reasonElement.value;
        const otherText = document.getElementById('otherReasonTextarea').value.trim();

        if (reason === '7' && otherText === '') {
            alert('請輸入具體的其他檢舉原因！');
            return;
        }
        
        const formData = new FormData();
        formData.append('com_id', comId);
        formData.append('reason_id', reason); 
        formData.append('other_text', otherText); 

    
        fetch('report_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('感謝您的回報！我們將盡快審查此內容。');
                closeReportModal();
                
                const cardId = 'comment-card-' + comId;
                const targetCard = document.getElementById(cardId);
                if (targetCard) {
                    targetCard.style.transition = 'all 0.4s ease';
                    targetCard.style.opacity = '0';
                    targetCard.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        targetCard.remove(); 
                    }, 400);
                }

            } else {
                alert(data.message);
            }
        })
        .catch(() => {
            alert('網路錯誤，無法送出檢舉。');
        });
    }
</script>