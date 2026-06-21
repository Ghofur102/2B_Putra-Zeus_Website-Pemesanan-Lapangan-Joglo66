export function initializeMobileNavbar() {
    const mobileMenuBtn = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const iconClosed = document.getElementById('icon-menu-closed');
    const iconOpen = document.getElementById('icon-menu-open');

    if (!mobileMenuBtn || !mobileMenu) {
        return;
    }

    mobileMenuBtn.addEventListener('click', () => {
        const isHidden = mobileMenu.classList.toggle('hidden');

        if (isHidden) {
            iconClosed.classList.replace('hidden', 'block');
            iconOpen.classList.replace('block', 'hidden');
        } else {
            iconClosed.classList.replace('block', 'hidden');
            iconOpen.classList.replace('hidden', 'block');
        }
    });
}
