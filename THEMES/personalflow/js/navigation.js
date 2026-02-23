/**
 * PersonalFlow Theme – Navigation & UI
 * Handles: mobile menu, sticky header, skill chips, match score animation
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

    // ── 1. Sticky Header ──────────────────────────────────────────────────
    if (header) {
        const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 20);
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    // ── 2. Mobile Menu ────────────────────────────────────────────────────
    if (mToggle && nav) {
        mToggle.addEventListener('click', () => {
            const isOpen = mToggle.getAttribute('aria-expanded') === 'true';
            mToggle.setAttribute('aria-expanded', String(!isOpen));
            nav.classList.toggle('open', !isOpen);
        });
        document.addEventListener('click', (e) => {
            if (!nav.contains(e.target) && !mToggle.contains(e.target)) {
                nav.classList.remove('open');
                mToggle.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                nav.classList.remove('open');
                mToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ── 3. Search Panel ────────────────────────────────────────────────────
    if (srchBtn && srchPnl) {
        srchBtn.addEventListener('click', () => {
            srchPnl.removeAttribute('hidden');
            srchBtn.setAttribute('aria-expanded', 'true');
            srchPnl.querySelector('input')?.focus();
        });
        srchCls?.addEventListener('click', () => {
            srchPnl.setAttribute('hidden', '');
            srchBtn.setAttribute('aria-expanded', 'false');
        });
        srchPnl.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                srchPnl.setAttribute('hidden', '');
                srchBtn?.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ── 4. Match Score Circle Animation ───────────────────────────────────
    const animateMatchScores = () => {
        document.querySelectorAll('.match-score-circle').forEach((el) => {
            const pct = parseInt(el.dataset.score ?? '0', 10);
            el.style.setProperty('--score', `${pct}%`);
        });
    };
    animateMatchScores();

    // ── 5. Scroll Reveal ──────────────────────────────────────────────────
    if ('IntersectionObserver' in window) {
        const obs = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.pf-card, .pf-kanban-col').forEach((el) => obs.observe(el));
    }

})();
