import Swal from 'sweetalert2';

const fieldSelect = document.getElementById('fieldSelect');
if (fieldSelect) {
    fieldSelect.addEventListener('change', function() {
        const fieldId = this.value;
        const currentPath = window.location.pathname;
        window.location.href = fieldId ? `${currentPath}?field_id=${fieldId}` : currentPath;
    });
}

const disabledPesanBtn = document.getElementById('disabledPesanBtn');
if (disabledPesanBtn) {
    disabledPesanBtn.addEventListener('click', function(e) {
        e.preventDefault();

        Swal.fire({
            icon: 'warning',
            title: 'Tunggu Dulu!',
            text: 'Silakan pilih lapangan terlebih dahulu di menu dropdown atas.',
            confirmButtonColor: '#3a5a8c',
            confirmButtonText: 'Oke, Mengerti',
            customClass: {
                popup: 'rounded-xl shadow-lg border border-gray-100'
            }
        });
    });
}
