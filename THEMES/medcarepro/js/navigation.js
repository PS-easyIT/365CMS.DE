/**
 * MedCare Pro Theme – Navigation, Accessibility Toggles & UI
 */
(function () {
    'use strict';
    const html    = document.documentElement;
    const header  = document.getElementById('masthead');
    const mToggle = document.getElementById('mobileMenuToggle');
    const nav     = document.querySelector('.main-navigation');
    const srchBtn = document.getElementById('searchToggle');
    const srchPnl = document.getElementById('searchPanel');
    const srchCls = document.getElementById('searchClose');
    const fontBtn = document.getElementById('fontSizeToggle');
    const conBtn  = document.getElementById('contrastToggle');

    if (header) {
        // Dynamic content padding (handles optional emergency banner)
        const content = document.getElementById('content');
        function updateContentPadding() {
            if (content) content.style.paddingTop = header.offsetHeight + 'px';
        }
        updateContentPadding();
        window.addEventListener('resize', updateContentPadding);

        const fn = () => header.classList.toggle('scrolled', window.scrollY > 20);
        window.addEventListener('scroll', fn, { passive: true }); fn();
    }
    if (mToggle && nav) {
        mToggle.addEventListener('click', () => {
            const open = mToggle.getAttribute('aria-expanded') === 'true';
            mToggle.setAttribute('aria-expanded', String(!open));
            nav.classList.toggle('open', !open);
        });
        document.addEventListener('click', (e) => {
            if (!nav.contains(e.target) && !mToggle.contains(e.target)) { nav.classList.remove('open'); mToggle.setAttribute('aria-expanded', 'false'); }
        });
    }
    if (srchBtn && srchPnl) {
        srchBtn.addEventListener('click', () => { srchPnl.removeAttribute('hidden'); srchBtn.setAttribute('aria-expanded', 'true'); srchPnl.querySelector('input')?.focus(); });
        srchCls?.addEventListener('click', () => { srchPnl.setAttribute('hidden', ''); srchBtn.setAttribute('aria-expanded', 'false'); });
    }
    // Accessibility: Font size toggle
    if (fontBtn) {
        const ls = 'mc-large-text';
        if (localStorage.getItem(ls) === '1') document.body.classList.add(ls);
        fontBtn.addEventListener('click', () => {
            const active = document.body.classList.toggle(ls);
            localStorage.setItem(ls, active ? '1' : '0');
        });
    }
    // Accessibility: High contrast toggle
    if (conBtn) {
        const hc = 'mc-high-contrast';
        if (localStorage.getItem(hc) === '1') document.body.classList.add(hc);
        conBtn.addEventListener('click', () => {
            const active = document.body.classList.toggle(hc);
            localStorage.setItem(hc, active ? '1' : '0');
        });
    }
    if ('IntersectionObserver' in window) {
        const obs = new IntersectionObserver((entries) => { entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('is-visible'); obs.unobserve(e.target); } }); }, { threshold: 0.1 });
        document.querySelectorAll('.mc-card').forEach(el => obs.observe(el));
    }
})();
