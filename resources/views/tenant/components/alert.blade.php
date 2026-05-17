<div id="global-alert-container" class="fixed top-5 right-5 z-[9999] flex flex-col gap-3 pointer-events-none">

    @if(session('success'))
        <div class="alert-box bg-green-500 text-white px-6 py-4 rounded-xl shadow-lg flex items-center gap-3 transform transition-all duration-500 ease-in-out translate-x-0">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium text-sm">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert-box bg-red-500 text-white px-6 py-4 rounded-xl shadow-lg flex items-center gap-3 transform transition-all duration-500 ease-in-out translate-x-0">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium text-sm">{{ session('error') }}</span>
        </div>
    @endif

    @if(session('info'))
        <div class="alert-box bg-blue-500 text-white px-6 py-4 rounded-xl shadow-lg flex items-center gap-3 transform transition-all duration-500 ease-in-out translate-x-0">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium text-sm">{{ session('info') }}</span>
        </div>
    @endif

</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const alerts = document.querySelectorAll('.alert-box');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => alert.remove(), 500); // Hapus elemen dari DOM setelah animasi selesai
            }, 10000); // Tampil selama 4 detik
        });
    });
</script>
