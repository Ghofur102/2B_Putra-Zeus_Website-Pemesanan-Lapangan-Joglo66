import './bootstrap';

// Logika Navbar Mobile Toggle
const mobileMenuBtn = document.getElementById('mobile-menu-button');
const mobileMenu = document.getElementById('mobile-menu');
const iconClosed = document.getElementById('icon-menu-closed');
const iconOpen = document.getElementById('icon-menu-open');

if (mobileMenuBtn && mobileMenu) {
    mobileMenuBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');

        iconClosed.classList.toggle('hidden');
        iconClosed.classList.toggle('block');
        iconOpen.classList.toggle('hidden');
        iconOpen.classList.toggle('block');
    });
}
