<style>
.student-edit-overlay { display: none; position: fixed; z-index: 100000 !important; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); justify-content: center; align-items: center; }
    .student-edit-box { background: white; padding: 20px; border-radius: 12px; width: 90%; max-width: 300px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
    .edit-form-label { display: block; font-size: 13px; font-weight: bold; color: #002B5B; margin-bottom: 5px; margin-top: 10px; }
    .edit-select { width: 100%; padding: 8px 10px; border-radius: 8px; border: 1px solid #ddd; font-size: 13px; outline: none; background: white; color: #333; }
    .edit-select:focus { border-color: #FF8C42; }
    .edit-image-preview-wrapper { margin: 12px 0; display: flex; align-items: center; gap: 15px; background: #f9f9f9; padding: 8px; border-radius: 8px; border: 1px dashed #ddd; }
    .edit-preview-img { width: 70px; height: 70px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }
    .btn-remove-photo { background: rgba(211, 47, 47, 0.1); color: #D32F2F; border: none; font-size: 11px; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .btn-remove-photo:hover { background: rgba(211, 47, 47, 0.2); }
    .student-edit-textarea { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; font-size: 14px; resize: vertical; box-sizing: border-box; outline: none; }
    .student-edit-textarea:focus { border-color: #FF8C42; }
</style>


<div id="studentEditModal" class="student-edit-overlay">
    <div class="student-edit-box">
        <h3 style="margin-top: 0; color: #002B5B; font-size: 16px; margin-bottom: 15px;">修改我的用餐評價</h3>
        
        <input type="hidden" id="studentEditComId">
        <input type="hidden" id="deletePhotoFlag" value="0">
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label class="edit-form-label">選擇餐點</label>
            <select id="studentEditItemSelect" class="edit-select">
                <?php foreach ($all_items as $item): ?>
                    <option value="<?php echo $item['item_id']; ?>"><?php echo htmlspecialchars($item['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label class="edit-form-label">滿意度評分</label>
            <div class="rating-container" style="text-align: center; margin: 10px 0;">
                <input type="hidden" id="studentEditRatingInput" value="0" required>
                
                <div class="stars edit-modal-stars" style="display: flex; justify-content: center; gap: 8px; flex-direction: row-reverse;">
                    <span class="edit-star" data-value="5">★</span>
                    <span class="edit-star" data-value="4">★</span>
                    <span class="edit-star" data-value="3">★</span>
                    <span class="edit-star" data-value="2">★</span>
                    <span class="edit-star" data-value="1">★</span>
                </div>
                <div class="rating-hint" id="studentEditRatingHint" style="font-size: 12px; color: #999; margin-top: 8px;">請選擇評分</div>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
            <label class="edit-form-label">文字評論</label>
            <textarea id="studentEditContentField" class="student-edit-textarea" rows="4" placeholder="分享你的用餐心得..."></textarea>
        </div>
        
<div class="form-group" style="margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <label class="edit-form-label" style="margin: 0;">餐點照片</label>
                <button id="btnDeleteCurrentPhoto" type="button" class="btn-remove-photo" onclick="markPhotoDeleted()" style="display:none; padding: 3px 10px; font-size: 11px;">
                    X 刪除照片
                </button>
            </div>
            
            <div class="upload-box" onclick="document.getElementById('studentEditUploadFile').click();" style="border: 2px dashed #d0d0d0; border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; background: #fafafa; transition: 0.2s;">
                <div style="display: flex; align-items: center; justify-content: flex-start; gap: 20px; flex-wrap: wrap;">                   
                    <div>
                        <img id="modalImagePreview" class="edit-preview-img" src="" style="display:none; max-width: 85px; max-height: 85px; object-fit: cover; border-radius: 10px; border: 1px solid #eee;">
                        <span id="modalNoImageText" style="font-size:12px; color:#aaa; font-weight: bold;">無餐點照片</span>
                    </div>  
                    <div style="font-size:14px; color:#002B5B; font-weight:bold; display: inline-flex; align-items: center; gap: 5px;">
                        <div class="upload-icon">↑</div>
                        <span id="editUploadText">點擊上傳/更換照片</span>
                    </div>
                </div>

                <input type="file" id="studentEditUploadFile" accept="image/*" onchange="previewNewSelectedPhoto(this)" style="display:none;">
            </div>
        </div>

        <div style="text-align: right; margin-top: 20px; display: flex; gap: 8px; justify-content: flex-end;">
            <button class="btn-user-action btn-user-edit-text" style="background:#eee; color:#333;" onclick="closeStudentEditModal()">取消</button>
            <button class="btn-user-action btn-user-edit-text" style="background: #002B5B; color:white;" onclick="submitStudentEdit()">儲存變更</button>
        </div>
    </div>
</div>

<style>
    .edit-star {
        font-size: 35px;
        color: #e0e0e0;
        cursor: pointer;
        transition: color 0.2s;
    }
    .edit-modal-stars .edit-star:hover,
    .edit-modal-stars .edit-star:hover ~ .edit-star,
    .edit-modal-stars .edit-star.active,
    .edit-modal-stars .edit-star.active ~ .edit-star {
        color: #FFC107 !important;
    }
</style>

<script>
    // 提示文字陣列
    const editHints = ["非常不滿意", "不滿意", "普通", "滿意", "非常滿意！"];

    // 開啟編輯小視窗並完整回填舊有數據
    function openStudentEditModal(comId, itemId, rating, currentImgName) {
        const currentText = document.getElementById('comment-text-content-' + comId).innerText;
        document.getElementById('studentEditComId').value = comId;
        document.getElementById('studentEditContentField').value = currentText;
        document.getElementById('deletePhotoFlag').value = "0"; 
        document.getElementById('studentEditUploadFile').value = ""; 

        document.getElementById('studentEditItemSelect').value = itemId;

        document.getElementById('studentEditRatingInput').value = rating;

        const editStars = document.querySelectorAll('.edit-star');
        editStars.forEach(s => {
            s.classList.remove('active');
            if (s.getAttribute('data-value') == rating) {
                s.classList.add('active');
            }
        });
        
        const hintBox = document.getElementById('studentEditRatingHint');
        hintBox.innerText = rating + " 星 - " + editHints[rating - 1];
        hintBox.style.color = "#FF8C42";

        const previewImg = document.getElementById('modalImagePreview');
        const noImgText = document.getElementById('modalNoImageText');
        const deleteBtn = document.getElementById('btnDeleteCurrentPhoto');

        if (currentImgName && currentImgName.trim() !== "") {
            previewImg.src = "food/" + currentImgName;
            previewImg.style.display = "block";
            deleteBtn.style.display = "inline-block";
            noImgText.style.display = "none";
        } else {
            previewImg.src = "";
            previewImg.style.display = "none";
            deleteBtn.style.display = "none";
            noImgText.style.display = "block";
        }

        document.getElementById('studentEditModal').style.display = 'flex';
    }

    // 星星綁定滑鼠移動
    const modalStars = document.querySelectorAll('.edit-star');
    
    modalStars.forEach(star => {

        star.addEventListener('mouseenter', function() {
            let hoverVal = parseInt(this.getAttribute('data-value'), 10);
            document.getElementById('studentEditRatingHint').innerText = hoverVal + " 星 - " + editHints[hoverVal - 1];
        });

        star.addEventListener('mouseleave', function() {
            let currentVal = parseInt(document.getElementById('studentEditRatingInput').value, 10);
            if (currentVal > 0) {
                document.getElementById('studentEditRatingHint').innerText = currentVal + " 星 - " + editHints[currentVal - 1];
            } else {
                document.getElementById('studentEditRatingHint').innerText = "請選擇評分";
            }
        });

        star.addEventListener('click', function() {
            modalStars.forEach(s => s.classList.remove('active'));
            this.classList.add('active');
            
            let checkedVal = parseInt(this.getAttribute('data-value'), 10);
            document.getElementById('studentEditRatingInput').value = checkedVal;
            
            const hintBox = document.getElementById('studentEditRatingHint');
            hintBox.innerText = checkedVal + " 星 - " + editHints[checkedVal - 1];
            hintBox.style.color = "#FF8C42";
        });
    });

    function closeStudentEditModal() {
        document.getElementById('studentEditModal').style.display = 'none';
    }

    function previewNewSelectedPhoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewImg = document.getElementById('modalImagePreview');
                previewImg.src = e.target.result;
                previewImg.style.display = "block";
                document.getElementById('modalNoImageText').style.display = "none";
                document.getElementById('btnDeleteCurrentPhoto').style.display = "inline-block";
            }
            reader.readAsDataURL(input.files[0]);
        }
    }


    function markPhotoDeleted() {

        if (!confirm('確定要刪除目前這張餐點照片嗎？')) {
            return; 
        }

        document.getElementById('deletePhotoFlag').value = "1";
        document.getElementById('studentEditUploadFile').value = ""; 
        
        const previewImg = document.getElementById('modalImagePreview');
        previewImg.src = "";
        previewImg.style.display = "none";
        document.getElementById('btnDeleteCurrentPhoto').style.display = "none";
        document.getElementById('modalNoImageText').style.display = "block";
    }

    function submitStudentEdit() {
        const comId = document.getElementById('studentEditComId').value;
        const newContent = document.getElementById('studentEditContentField').value.trim();
        const newItemId = document.getElementById('studentEditItemSelect').value;
        const newItemName = document.getElementById('studentEditItemSelect').options[document.getElementById('studentEditItemSelect').selectedIndex].text;
        const newRating = document.getElementById('studentEditRatingInput').value; 
        const deletePhotoFlag = document.getElementById('deletePhotoFlag').value;
        const fileInput = document.getElementById('studentEditUploadFile');

        if (newContent === '') {
            alert('心得內文不能完全留空喔！');
            return;
        }
        if (newRating == 0) {
            alert('請給這道餐點點擊星星評分喔！');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'user_update'); 
        formData.append('com_id', comId);
        formData.append('content', newContent);
        formData.append('item_id', newItemId); 
        formData.append('rating', newRating); 
        formData.append('delete_photo_flag', deletePhotoFlag);
        
        if (fileInput.files.length > 0) {
            formData.append('comment_photo', fileInput.files[0]);
        }

        fetch('manage_review_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('comment-text-content-' + comId).innerText = newContent;
                document.getElementById('comment-item-name-' + comId).innerText = newItemName;
                document.getElementById('comment-rating-num-' + comId).innerText = newRating;
                
                const timeBox = document.getElementById('comment-time-box-' + comId);
                if (timeBox) {
                    timeBox.innerHTML = data.updated_time + ' <span style="color:#aaa; font-weight:normal; margin-left:5px;">(已編輯)</span>';
                }

                const cardItem = document.getElementById('comment-card-' + comId);
                if (cardItem) {
                    cardItem.setAttribute('data-time', Math.floor(Date.now() / 1000));
                    cardItem.setAttribute('data-rating', newRating);
                    cardItem.setAttribute('data-has-image', data.has_image ? 'true' : 'false');
                    cardItem.setAttribute('data-text', (newItemName + newContent).toLowerCase());
                }

                const imgBlock = document.getElementById('comment-image-block-' + comId);
                if (imgBlock) {
                    if (data.has_image && data.new_image_url) {
                        imgBlock.innerHTML = `<img src="food/${data.new_image_url}" class="comment-img-thumb" onclick="openFullImage(this.src)" alt="用餐照片">`;
                    } else {
                        imgBlock.innerHTML = ""; 
                    }
                }

                const editBtn = document.getElementById('btn-edit-trigger-' + comId);
                if (editBtn) {
                    editBtn.setAttribute('onclick', `openStudentEditModal(${comId}, ${newItemId}, ${newRating}, '${data.new_image_url || ''}')`);
                }

                closeStudentEditModal();
                alert('修改成功！');
            } else {
                alert(data.message || '更新失敗。');
            }
        })
        .catch(() => alert('網路傳輸失敗，無法編輯。'));
    }
</script>