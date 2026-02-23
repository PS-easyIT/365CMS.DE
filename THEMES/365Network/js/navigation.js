/**
 * Navigation - Mobile Menu & Search Overlay
 *
 * @package IT_Expert_Network_Theme
 */

(function () {
    'use strict';

    /**
     * Initialisierung – robust gegen race-conditions beim Script-Laden
     * am Ende des Body (ohne defer wird DOMContentLoaded ggf. bereits gefeuert)
     */
    function init() {

        // ===== Header Scroll Behaviour =====
        const header = document.getElementById('masthead');
        if (header) {
            window.addEventListener('scroll', function () {
                header.classList.toggle('scrolled', window.scrollY > 80);
            }, { passive: true });
        }

        // ===== Mobile Menu =====
        const mobileToggle  = document.getElementById('mobileMenuToggle');
        const mobileDrawer  = document.getElementById('mobileMenuDrawer');
        const mobileOverlay = document.getElementById('mobileMenuOverlay');

        function openMobileMenu() {
            if (!mobileDrawer || !mobileOverlay || !mobileToggle) return;
            mobileDrawer.classList.add('is-open');
            mobileOverlay.classList.add('is-open');
            mobileToggle.classList.add('is-active');
            mobileToggle.setAttribute('aria-expanded', 'true');
            mobileDrawer.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            if (!mobileDrawer || !mobileOverlay || !mobileToggle) return;
            mobileDrawer.classList.remove('is-open');
            mobileOverlay.classList.remove('is-open');
            mobileToggle.classList.remove('is-active');
            mobileToggle.setAttribute('aria-expanded', 'false');
            mobileDrawer.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        if (mobileToggle) {
            mobileToggle.addEventListener('click', function () {
                if (mobileDrawer && mobileDrawer.classList.contains('is-open')) {
                    closeMobileMenu();
                } else {
                    openMobileMenu();
                }
            });
        }

        if (mobileOverlay) {
            mobileOverlay.addEventListener('click', closeMobileMenu);
        }

        // ESC schließt Menü
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeMobileMenu();
                closeSearchOverlay();
            }
        });

        // ===== Search Overlay =====
        const searchToggle  = document.getElementById('searchToggle');
        const searchOverlay = document.getElementById('searchOverlay');
        const searchClose   = document.getElementById('searchOverlayClose');

        function openSearchOverlay() {
            if (!searchOverlay) return;
            searchOverlay.classList.add('is-active');
            searchOverlay.setAttribute('aria-hidden', 'false');
            if (searchToggle) searchToggle.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
            const input = searchOverlay.querySelector('input[type="search"]');
            if (input) setTimeout(() => input.focus(), 50);
        }

        function closeSearchOverlay() {
            if (!searchOverlay) return;
            searchOverlay.classList.remove('is-active');
            searchOverlay.setAttribute('aria-hidden', 'true');
            if (searchToggle) searchToggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }

        if (searchToggle) {
            searchToggle.addEventListener('click', openSearchOverlay);
        }

        if (searchClose) {
            searchClose.addEventListener('click', closeSearchOverlay);
        }

        if (searchOverlay) {
            searchOverlay.addEventListener('click', function (e) {
                if (e.target === searchOverlay) closeSearchOverlay();
            });
        }
    }

    // Sicherer Einstiegspunkt: funktioniert egal ob DOM schon bereit oder nicht
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

}());
