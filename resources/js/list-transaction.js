document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('filterSearch');

    // KUNCI: Jika elemen pencarian tidak ada (berarti sedang di halaman lain), hentikan script!
    if (!searchInput) return;

    const startDateInput = document.getElementById('filterStartDate');
    const endDateInput = document.getElementById('filterEndDate');
    const statusSelect = document.getElementById('filterStatus');
    const btnReset = document.getElementById('btnReset');

    const cards = document.querySelectorAll('.transaction-card');
    const jsEmptyState = document.getElementById('jsEmptyState');
    const defaultEmptyState = document.getElementById('defaultEmptyState');

    function filterCards() {
        const searchValue = searchInput.value.toLowerCase();
        const startDateValue = startDateInput.value;
        const endDateValue = endDateInput.value;
        const statusValue = statusSelect.value;

        let visibleCardCount = 0;

        cards.forEach(card => {
            const cardSearch = card.getAttribute('data-search');
            const cardDate = card.getAttribute('data-date');
            const cardStatus = card.getAttribute('data-status');

            const matchSearch = cardSearch.includes(searchValue);
            const matchStatus = statusValue === '' || cardStatus === statusValue;

            let matchDate = true;
            if (startDateValue && cardDate < startDateValue) matchDate = false;
            if (endDateValue && cardDate > endDateValue) matchDate = false;

            if (matchSearch && matchStatus && matchDate) {
                card.style.display = '';
                visibleCardCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (defaultEmptyState) defaultEmptyState.style.display = 'none';
        jsEmptyState.style.display = visibleCardCount === 0 && cards.length > 0 ? 'block' : 'none';
    }

    searchInput.addEventListener('input', filterCards);
    startDateInput.addEventListener('change', filterCards);
    endDateInput.addEventListener('change', filterCards);
    statusSelect.addEventListener('change', filterCards);

    btnReset.addEventListener('click', function() {
        searchInput.value = '';
        startDateInput.value = '';
        endDateInput.value = '';
        statusSelect.value = '';
        filterCards();
    });

    const toggleFilterBtn = document.getElementById('toggleFilterBtn');
    const closeFilterBtn = document.getElementById('closeFilterBtn');
    const filterPopup = document.getElementById('filterPopup');

    toggleFilterBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        filterPopup.classList.toggle('hidden');
    });

    closeFilterBtn.addEventListener('click', function() {
        filterPopup.classList.add('hidden');
    });

    document.addEventListener('click', function(e) {
        if (!filterPopup.contains(e.target) && e.target !== toggleFilterBtn) {
            filterPopup.classList.add('hidden');
        }
    });
});
