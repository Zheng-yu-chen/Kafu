<style>
    .btn-remove-photo { background: rgba(211, 47, 47, 0.1); color: #D32F2F; border: none; font-size: 11px; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .btn-remove-photo:hover { background: rgba(211, 47, 47, 0.2); }
</style>

<script>
    // 自刪評論
    function studentDeleteComment(comId, button) {
        if (!confirm('確定要永久刪除此筆用餐心得嗎？')) return;
        const formData = new FormData();
        formData.append('action', 'user_delete'); 
        formData.append('com_id', comId);

        fetch('manage_review_api.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const card = button.closest('.comment-card-item');
                if (card) card.remove();
                alert('刪除成功！');
            } else {
                alert(data.message || '刪除失敗。');
            }
        });
    }
</script>