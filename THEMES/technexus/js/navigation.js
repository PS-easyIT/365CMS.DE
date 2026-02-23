/**
 * TechNexus Theme – Navigation & UI
 * Handles: mobile menu, dark mode, sticky header, search panel, scroll animations
 */
(function () {
    'use strict';

    const html     = document.documentElement;
    const header   = document.getElementById('masthead');
    const mToggle  = document.getElementById('mobileMenuToggle');
    const nav      = document.querySelector('.main-navigation');
    const srchBtn  = document.getElementById('searchToggle');
    const srchPnl  = document.getElementById('searchPanel');
    const srchCls  = document.getElementById('searchClose');
    const themeBtn = document.getElementById('themeToggle');

    // ── 1. Dark Mode ──────────────────────────────────────────────────────
    const applyTheme = (mode) => {
        html.setAttribute('data-theme', mode);
        if (themeBtn) {
            themeBtn.setAttribute('aria-label', mode === 'dark' ? 'Light Mode aktivieren' : 'Dark Mode aktivieren');
        }
    };

    const savedTheme = localStorage.getItem('tn-color-scheme');
    if (savedTheme) {
        applyTheme(savedTheme);
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        applyTheme('dark');
    }

    themeBtn?.addEventListener('click', () => {
        const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        localStorage.setItem('tn-color-scheme', next);
    });

    // ── 2. Sticky Header ──────────────────────────────────────────────────
    if (header) {
        const onScroll = () => {
            header.classList.toggle('scrolled', window.scrollY > 20);
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll(); // initial state
    }

    // ── 3. Mobile Menu ────────────────────────────────────────────────────
    if (mToggle && nav) {
        mToggle.addEventListener('click', () => {
            const isOpen = mToggle.getAttribute('aria-expanded') === 'true';
            mToggle.setAttribute('aria-expanded', String(!isOpen));
            nav.classList.toggle('open', !isOpen);
            mToggle.classList.toggle('active', !isOpen);
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!nav.contains(e.target) && !mToggle.contains(e.target)) {
                nav.classList.remove('open');
                mToggle.setAttribute('aria-expanded', 'false');
                mToggle.classList.remove('active');
            }
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                nav.classList.remove('open');
                mToggle.setAttribute('aria-expanded', 'false');
                mToggle.classList.remove('active');
            }
        });
    }

    // ── 4. Search Panel ────────────────────────────────────────────────────
    if (srchBtn && srchPnl) {
        const openSearch = () => {
            srchPnl.removeAttribute('hidden');
            srchBtn.setAttribute('aria-expanded', 'true');
            const input = srchPnl.querySelector('input[type="search"]');
            setTimeout(() => input?.focus(), 50);
        };
        const closeSearch = () => {
            srchPnl.setAttribute('hidden', '');
            srchBtn.setAttribute('aria-expanded', 'false');
            srchBtn.focus();
        };

        srchBtn.addEventListener('click', openSearch);
        srchCls?.addEventListener('click', closeSearch);

        srchPnl.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSearch();
        });
    }

    // ── 5. Scroll Reveal ──────────────────────────────────────────────────
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
        );

        document.querySelectorAll('.tech-card, .home-experts article, .home-features .tech-card').forEach((el) => {
            observer.observe(el);
        });
    }

    // ── 6. Stat Counter Animation ─────────────────────────────────────────
    const animateCounter = (el) => {
        const target = parseInt(el.textContent, 10);
        if (isNaN(target) || target === 0) return;
        let start    = 0;
        const step   = Math.ceil(target / 60);
        const timer  = setInterval(() => {
            start += step;
            if (start >= target) {
                el.textContent = target + '+';
                clearInterval(timer);
            } else {
                el.textContent = start + '+';
            }
        }, 16);
    };

    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach((e) => {
            if (e.isIntersecting) {
                animateCounter(e.target);
                statsObserver.unobserve(e.target);
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.hero-stat-number').forEach((el) => statsObserver.observe(el));

})();
