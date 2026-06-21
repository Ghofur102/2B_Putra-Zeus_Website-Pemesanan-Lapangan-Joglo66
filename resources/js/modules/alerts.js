export function initializeAlertDismissals() {
    const alerts = document.querySelectorAll('.alert-dismissible');

    alerts.forEach((alert) => {
        const closeBtn = alert.querySelector('.alert-close-button');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                alert.classList.add('opacity-0', 'scale-95');
                setTimeout(() => {
                    alert.remove();
                }, 300);
            });
        }
    });
}
