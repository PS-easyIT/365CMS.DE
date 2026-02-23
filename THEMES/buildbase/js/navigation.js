/**
 * BuildBase Theme – Navigation & UI
 */
(function () {
    'use strict';
    const header  = document.getElementById('masthead');
    const mToggle = document.getElementById('mobileMenuToggle');
    const nav     = document.querySelector('.main-navigation');
    const srchBtn = document.getElementById('searchToggle');
    const srchPnl = document.getElementById('searchPanel');
    const srchCls = document.getElementById('searchClose');

    if (header) {
        const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 20);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }
    if (mToggle && nav) {
        mToggle.addEventListener('click', () => {
            const open = mToggle.getAttribute('aria-expanded') === 'true';
            mToggle.setAttribute('aria-expanded', String(!open));
            nav.classList.toggle('open', !open);
        });
        document.addEventListener('click', (e) => {
            if (!nav.contains(e.target) && !mToggle.contains(e.target)) {
                nav.classList.remove('open');
                mToggle.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { nav.classList.remove('open'); mToggle.setAttribute('aria-expanded', 'false'); }
        });
    }
    if (srchBtn && srchPnl) {
        srchBtn.addEventListener('click', () => { srchPnl.removeAttribute('hidden'); srchBtn.setAttribute('aria-expanded', 'true'); srchPnl.querySelector('input')?.focus(); });
        srchCls?.addEventListener('click', () => { srchPnl.setAttribute('hidden', ''); srchBtn.setAttribute('aria-expanded', 'false'); });
    }
    if ('IntersectionObserver' in window) {
        const obs = new IntersectionObserver((entries) => { entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('is-visible'); obs.unobserve(e.target); } }); }, { threshold: 0.1 });
        document.querySelectorAll('.bb-card').forEach(el => obs.observe(el));
    }
})();
