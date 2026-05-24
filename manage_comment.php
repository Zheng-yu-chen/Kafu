<script>
function deleteComment(comId, button) {
        if (!confirm('管理員確定要刪除此評論嗎？此操作無法復原。')) return;

        const formData = new FormData();
        formData.append('action', 'reject');
        formData.append('com_id', comId);

        fetch('manage_review_api.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const card = button.closest('.comment-card-item');
                if (card) card.remove();
            } else {
                alert('刪除失敗，請稍後再試。');
            }
        })
        .catch(() => {
            alert('網路錯誤，無法刪除評論。');
        });
    }

    function toggleReplyBox(comId) {
        const box = document.getElementById('reply-box-' + comId);
        if (box.style.display === 'none') {
            box.style.display = 'block';
        } else {
            box.style.display = 'none';
        }
    }
</script>
