import '../css/public.css';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Mencegah URL berubah menjadi /#hash saat mengklik link anchor
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href^="#"]');
        if (!link) return;

        const id = link.getAttribute('href');
        
        if (id !== '#' && id.length > 1) {
            const element = document.querySelector(id);
            if (element) {
                e.preventDefault();
                element.scrollIntoView({ behavior: 'smooth' });
                
                // Jika link berada di dalam menu mobile Alpine, kita bisa menutupnya dengan
                // men-trigger event klik pada tombol close menu atau state, 
                // tapi karena Alpine memanage state-nya sendiri, scrollIntoView sudah cukup.
            }
        } else if (id === '#') {
            e.preventDefault();
        }
    });
});
