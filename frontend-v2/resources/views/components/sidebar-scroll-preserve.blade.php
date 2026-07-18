{{--
    Skrip ini menjaga posisi scroll sidebar saat berpindah halaman lewat
    Livewire navigate (Filament SPA). Tanpa ini, sidebar selalu di-scroll
    kembali ke atas tiap kali user klik navigasi yang urutannya di bawah.

    Mekanisme:
    1. Tepat sebelum Livewire navigate: simpan scrollTop sidebar ke sessionStorage.
    2. Setelah Livewire navigate selesai (event 'livewire:navigated'): restore.
    3. Setiap user scroll sidebar manual: simpan posisi terbaru.
--}}
<script>
(function () {
    const KEY = 'handayani.sidebarScrollTop';
    const SELECTORS = [
        '[data-flux-sidebar]',
        '.fi-sidebar-nav',
        '.fi-sidebar',
        'aside.fi-sidebar',
    ];

    function findSidebar() {
        for (const sel of SELECTORS) {
            const el = document.querySelector(sel);
            if (el) return el;
        }
        return null;
    }

    function save() {
        const el = findSidebar();
        if (!el) return;
        try { sessionStorage.setItem(KEY, String(el.scrollTop)); } catch {}
    }

    function restore() {
        const el = findSidebar();
        if (!el) return;
        try {
            const v = sessionStorage.getItem(KEY);
            if (v !== null) el.scrollTop = parseInt(v, 10) || 0;
        } catch {}
    }

    // Restore on initial load and after every Livewire navigation.
    document.addEventListener('DOMContentLoaded', restore);
    document.addEventListener('livewire:navigated', restore);

    // Save before navigating away.
    document.addEventListener('livewire:navigating', save);
    window.addEventListener('beforeunload', save);

    // Save on user scroll (debounced).
    let timer = null;
    function attachScroll() {
        const el = findSidebar();
        if (!el) return;
        if (el.dataset.handayaniScrollBound === '1') return;
        el.dataset.handayaniScrollBound = '1';
        el.addEventListener('scroll', () => {
            if (timer) clearTimeout(timer);
            timer = setTimeout(save, 100);
        }, { passive: true });
    }

    document.addEventListener('DOMContentLoaded', attachScroll);
    document.addEventListener('livewire:navigated', attachScroll);
})();
</script>
