/**
 * Theme JS - Dark Mode, Preferences
 *
 * @package IT_Expert_Network_Theme
 */

(function () {
    'use strict';

    const STORAGE_KEY = 'cms_dark_mode';
    const DARK_CLASS  = 'dark-mode';
    const body        = document.body;

    // ===== Dark Mode =====

    /**
     * Gespeicherten Modus aus localStorage lesen.
     * Fallback: System-Präferenz
     */
    function getStoredMode() {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored !== null) {
            return stored === '1';
        }
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function applyDarkMode(isDark) {
        if (isDark) {
            body.classList.add(DARK_CLASS);
        } else {
            body.classList.remove(DARK_CLASS);
        }
    }

    function toggleDarkMode() {
        const isDark = body.classList.toggle(DARK_CLASS);
        try {
            localStorage.setItem(STORAGE_KEY, isDark ? '1' : '0');
        } catch (e) {
            // localStorage nicht verfügbar
        }
    }

    // Beim Laden anwenden (verhindert FOUC)
    applyDarkMode(getStoredMode());

    document.addEventListener('DOMContentLoaded', function () {
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', toggleDarkMode);
        }

        // System-Präferenz-Änderung beobachten
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
                // Nur reagieren, wenn der Nutzer keine Einstellung gespeichert hat
                if (localStorage.getItem(STORAGE_KEY) === null) {
                    applyDarkMode(e.matches);
                }
            });
        }
    });

    // ===== Scroll-to-Top =====
    document.addEventListener('DOMContentLoaded', function () {

        // Scroll-to-top Button dynamisch einbinden
        const scrollBtn = document.createElement('button');
        scrollBtn.id        = 'scrollToTop';
        scrollBtn.type      = 'button';
        scrollBtn.innerHTML = '↑';
        scrollBtn.setAttribute('aria-label', 'Nach oben scrollen');
        scrollBtn.style.cssText = [
            'position:fixed',
            'bottom:2rem',
            'right:1.5rem',
            'width:44px',
            'height:44px',
            'border-radius:50%',
            'background:var(--primary-color)',
            'color:white',
            'border:none',
            'cursor:pointer',
            'font-size:1.25rem',
            'box-shadow:0 4px 12px rgba(0,0,0,0.2)',
            'opacity:0',
            'visibility:hidden',
            'transition:opacity 0.3s ease,visibility 0.3s ease',
            'z-index:9990',
        ].join(';');

        document.body.appendChild(scrollBtn);

        window.addEventListener('scroll', function () {
            if (window.scrollY > 400) {
                scrollBtn.style.opacity     = '1';
                scrollBtn.style.visibility  = 'visible';
            } else {
                scrollBtn.style.opacity     = '0';
                scrollBtn.style.visibility  = 'hidden';
            }
        }, { passive: true });

        scrollBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

    });

})();
