<script>
    function filterAndSortComments() {
        const keyword = document.getElementById('commentSearch').value.toLowerCase().trim();
        const selectedStar = document.getElementById('commentFilterStars').value; 
        const sortMode = document.getElementById('commentSort').value; 
        const container = document.getElementById('commentsContainer');
        const cards = Array.from(container.getElementsByClassName('comment-card-item'));

        if (cards.length === 0) return;

        const nowTs = Math.floor(Date.now() / 1000);
        const oneWeekSec = 7 * 24 * 60 * 60;   
        const oneMonthSec = 30 * 24 * 60 * 60; 

        cards.forEach(card => {
            const text = card.getAttribute('data-text');
            const rating = card.getAttribute('data-rating');
            const hasImg = card.getAttribute('data-has-image');
            const timeTs = parseInt(card.getAttribute('data-time'), 10); 
            
            const matchesKeyword = text.includes(keyword);
            const matchesStar = (selectedStar === 'all' || rating === selectedStar);
            
            let matchesCondition = true;
            if (sortMode === 'one_week') {
                matchesCondition = (nowTs - timeTs <= oneWeekSec);
            } else if (sortMode === 'one_month') {
                matchesCondition = (nowTs - timeTs <= oneMonthSec);
            } else if (sortMode === 'has_image') {
                matchesCondition = (hasImg === 'true');
            }

            if (matchesKeyword && matchesStar && matchesCondition) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });

        cards.sort((a, b) => {
            return b.getAttribute('data-time') - a.getAttribute('data-time'); 
        });

        cards.forEach(card => container.appendChild(card));

        const hasVisible = cards.some(card => !card.classList.contains('hidden'));
        const noMatchMsg = document.getElementById('noMatchMessage');
        if (hasVisible) {
            noMatchMsg.classList.add('hidden');
        } else {
            noMatchMsg.classList.remove('hidden');
        }
    }
</script>