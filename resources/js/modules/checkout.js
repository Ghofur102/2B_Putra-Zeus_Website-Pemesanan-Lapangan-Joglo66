export function initializeCheckout() {
    const checkoutApp = document.getElementById('checkout-app');
    if (!checkoutApp) {
        return;
    }

    const payButton = document.getElementById('pay-button');
    const reference = checkoutApp.dataset.reference;
    const redirectUrl = checkoutApp.dataset.redirectUrl;

    if (!payButton) {
        return;
    }

    payButton.addEventListener('click', () => {
        if (typeof checkout === 'undefined') {
            alert('Sistem pembayaran belum siap, tunggu beberapa detik atau muat ulang halaman.');
            return;
        }

        checkout.process(reference, {
            successEvent: function(result) {
                globalThis.location.href = redirectUrl;
            },
            pendingEvent: function(result) {
                globalThis.location.href = redirectUrl;
            },
            errorEvent: function(result) {
                alert('Terjadi kesalahan pada pembayaran');
            },
            closeEvent: function(result) {
                console.log('User menutup popup');
            }
        });
    });

    setTimeout(() => {
        payButton.click();
    }, 1000);
}
