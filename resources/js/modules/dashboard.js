import Swal from 'sweetalert2';

export function initializeDashboard() {
    const fieldSelect = document.getElementById('fieldSelect');
    if (fieldSelect) {
        fieldSelect.addEventListener('change', function() {
            const fieldId = this.value;
            const currentPath = globalThis.location.pathname;
            globalThis.location.href = fieldId ? `${currentPath}?field_id=${fieldId}` : currentPath;
        });
    }

    const disabledBtn = document.getElementById('disabledBtn');
    if (disabledBtn) {
        disabledBtn.addEventListener('click', function(e) {
            e.preventDefault();

            Swal.fire({
                icon: 'warning',
                title: 'Tunggu Dulu!',
                text: 'Silakan pilih lapangan terlebih dahulu di menu dropdown atas.',
                confirmButtonColor: '#3a5a8c',
                confirmButtonText: 'Oke, Mengerti',
                customClass: {
                    popup: 'rounded-tenant-lg shadow-tenant-md border border-gray-100'
                }
            });
        });
    }
}
