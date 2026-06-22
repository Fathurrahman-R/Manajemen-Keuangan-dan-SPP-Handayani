<style>
    .custom-table-loading {
        position: absolute;
        inset: 0;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.6);
        border-radius: 0.75rem;
        opacity: 0;
        transition: opacity 0.15s;
        pointer-events: none;
    }
    .dark .custom-table-loading {
        background: rgba(17,24,39,0.6);
    }
    .custom-table-loading.active {
        opacity: 1;
        pointer-events: auto;
    }
</style>
<script>
    document.addEventListener("livewire:navigated", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });

    document.addEventListener("DOMContentLoaded", () => {
        document.body.addEventListener("click", (e) => {
            // Only match pagination nav buttons (not regular navigation tabs)
            const btn = e.target.closest("nav[role='navigation'] button");
            if (!btn) {
                return;
            }

            // Extra check: skip if this is a Filament tabs item (not pagination)
            if (btn.closest(".fi-tabs")) {
                return;
            }

            showTableOverlay(btn);
        }, true);

        // Per-page select change — only inside table pagination area
        document.body.addEventListener("change", (e) => {
            const select = e.target.closest("select");
            if (!select) return;
            // Only trigger if the select is inside a pagination/per-page toolbar area
            const paginationArea = select.closest(".fi-ta-header-toolbar, .fi-ta-pagination");
            if (!paginationArea) return;

            const wrapper = select.closest("[wire\\:id]");
            if (wrapper) {
                showTableOverlay(select);
            }
        }, true);

        function showTableOverlay(trigger) {
            const wrapper = trigger.closest("[wire\\:id]");
            if (!wrapper) return;

            let overlay = wrapper.querySelector(".custom-table-loading");
            if (!overlay) {
                overlay = document.createElement("div");
                overlay.className = "custom-table-loading";
                overlay.innerHTML = '<svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>';
                wrapper.style.position = "relative";
                wrapper.appendChild(overlay);
            }
            overlay.classList.add("active");

            const observer = new MutationObserver(() => {
                overlay.classList.remove("active");
                wrapper.scrollIntoView({ behavior: "smooth", block: "start" });
                observer.disconnect();
            });
            observer.observe(wrapper, { childList: true, subtree: true });

            setTimeout(() => {
                overlay.classList.remove("active");
                observer.disconnect();
            }, 5000);
        }
    });
</script>
