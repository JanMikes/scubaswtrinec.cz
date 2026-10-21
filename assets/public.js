// Public site: mobile navigation + gallery lightbox.
import GLightbox from 'glightbox';
import 'glightbox/dist/css/glightbox.min.css';

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-nav-toggle]');
    const menu = document.querySelector('[data-nav-menu]');
    if (toggle && menu) {
        toggle.addEventListener('click', () => {
            const open = menu.classList.toggle('hidden') === false;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    if (document.querySelector('.glightbox')) {
        GLightbox({ selector: '.glightbox', touchNavigation: true, loop: true });
    }

    // Hero slideshow: cross-fade every 7 s (CSS does the Ken Burns zoom).
    const slides = [...document.querySelectorAll('[data-slide]')];
    if (slides.length > 1 && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        let i = 0;
        setInterval(() => {
            slides[i].classList.replace('opacity-100', 'opacity-0');
            i = (i + 1) % slides.length;
            slides[i].classList.replace('opacity-0', 'opacity-100');
        }, 7000);
    }
});
