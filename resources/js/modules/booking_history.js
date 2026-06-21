export function initializeBookingHistory() {
    const searchInput = document.getElementById('filterSearch');
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
        const startDateValue = startDateInput ? startDateInput.value : '';
        const endDateValue = endDateInput ? endDateInput.value : '';
        const statusValue = statusSelect ? statusSelect.value : '';

        let visibleCardCount = 0;

        cards.forEach(card => {
            const cardSearch = card.dataset('data-search');
            const cardDate = card.dataset('data-date');
            const cardStatus = card.dataset('data-status');

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

        if (defaultEmptyState) {
            defaultEmptyState.style.display = visibleCardCount === 0 && cards.length === 0 ? '' : 'none';
        }
        if (jsEmptyState) {
            jsEmptyState.style.display = visibleCardCount === 0 && cards.length > 0 ? 'block' : 'none';
        }
    }

    searchInput.addEventListener('input', filterCards);
    if (startDateInput) startDateInput.addEventListener('change', filterCards);
    if (endDateInput) endDateInput.addEventListener('change', filterCards);
    if (statusSelect) statusSelect.addEventListener('change', filterCards);

    if (btnReset) {
        btnReset.addEventListener('click', () => {
            searchInput.value = '';
            if (startDateInput) startDateInput.value = '';
            if (endDateInput) endDateInput.value = '';
            if (statusSelect) statusSelect.value = '';
            filterCards();
        });
    }

    const toggleFilterBtn = document.getElementById('toggleFilterBtn');
    const closeFilterBtn = document.getElementById('closeFilterBtn');
    const filterPopup = document.getElementById('filterPopup');

    if (toggleFilterBtn && filterPopup) {
        toggleFilterBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            filterPopup.classList.toggle('hidden');
        });
    }

    if (closeFilterBtn && filterPopup) {
        closeFilterBtn.addEventListener('click', () => {
            filterPopup.classList.add('hidden');
        });
    }

    document.addEventListener('click', (e) => {
        if (filterPopup && !filterPopup.contains(e.target) && e.target !== toggleFilterBtn) {
            filterPopup.classList.add('hidden');
        }
    });
}
