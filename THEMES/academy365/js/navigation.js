/**
 * Academy365 – Navigation & Interactivity
 * CSS prefix: .ac-
 * No dark mode – bright educational theme
 */
(function () {
  'use strict';

  /* ──────────────────────────────────────────
   * Sticky Header
   * ────────────────────────────────────────── */
  const header = document.getElementById('masthead');
  if (header) {
    let lastY = 0;
    const THRESHOLD = 80;
    window.addEventListener('scroll', () => {
      const y = window.scrollY;
      if (y > THRESHOLD) {
        header.classList.add('ac-header-sticky');
        header.classList.toggle('ac-header-hidden', y > lastY + 10 && y > 250);
      } else {
        header.classList.remove('ac-header-sticky', 'ac-header-hidden');
      }
      lastY = y;
    }, { passive: true });
  }

  /* ──────────────────────────────────────────
   * Mobile Menu
   * ────────────────────────────────────────── */
  const menuToggle = document.getElementById('mobileMenuToggle');
  const mainNav    = document.getElementById('site-navigation');
  if (menuToggle && mainNav) {
    menuToggle.addEventListener('click', () => {
      const open = mainNav.classList.toggle('ac-nav-open');
      menuToggle.setAttribute('aria-expanded', String(open));
      menuToggle.setAttribute('aria-label', open ? 'Menü schließen' : 'Menü öffnen');
    });
    document.addEventListener('click', (e) => {
      if (!mainNav.contains(e.target) && !menuToggle.contains(e.target)) {
        mainNav.classList.remove('ac-nav-open');
        menuToggle.setAttribute('aria-expanded', 'false');
      }
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        mainNav.classList.remove('ac-nav-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.focus();
      }
    });
  }

  /* ──────────────────────────────────────────
   * Search Panel
   * ────────────────────────────────────────── */
  const searchToggle = document.getElementById('searchToggle');
  const searchPanel  = document.getElementById('searchPanel');
  const searchClose  = document.getElementById('searchClose');
  const searchInput  = document.getElementById('ac-search');
  if (searchToggle && searchPanel) {
    const openSearch = () => {
      searchPanel.hidden = false;
      searchToggle.setAttribute('aria-expanded', 'true');
      if (searchInput) setTimeout(() => searchInput.focus(), 50);
    };
    const closeSearch = () => {
      searchPanel.hidden = true;
      searchToggle.setAttribute('aria-expanded', 'false');
      searchToggle.focus();
    };
    searchToggle.addEventListener('click', () => {
      searchPanel.hidden ? openSearch() : closeSearch();
    });
    if (searchClose) searchClose.addEventListener('click', closeSearch);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !searchPanel.hidden) closeSearch();
    });
  }

  /* ──────────────────────────────────────────
   * Progress Bars – animate from data-progress attr
   * ────────────────────────────────────────── */
  const animateProgressBars = (entries, observer) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      const bar     = entry.target;
      const target  = parseInt(bar.getAttribute('data-progress') || '0', 10);
      bar.style.width = '0%';
      requestAnimationFrame(() => {
        bar.style.transition = 'width 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
        bar.style.width = Math.min(100, Math.max(0, target)) + '%';
      });
      observer.unobserve(bar);
    });
  };
  const progressObserver = new IntersectionObserver(animateProgressBars, { threshold: 0.3 });
  document.querySelectorAll('.ac-progress-bar').forEach((bar) => progressObserver.observe(bar));

  /* ──────────────────────────────────────────
   * Rating Stars – build visual star fill via CSS var
   * ────────────────────────────────────────── */
  document.querySelectorAll('.ac-rating-stars').forEach((el) => {
    const rating = parseFloat(el.style.getPropertyValue('--rating') || el.dataset.rating || '0');
    if (!isNaN(rating)) el.style.setProperty('--rating', String(rating));
    el.setAttribute('aria-label', `Bewertung: ${rating} von 5`);
  });

  /* ──────────────────────────────────────────
   * Scroll Reveal – .ac-card, .ac-tutor-card, .ac-stat-item
   * ────────────────────────────────────────── */
  const revealItems = document.querySelectorAll('.ac-card, .ac-stat-item, .ac-tutor-card');
  if (revealItems.length) {
    revealItems.forEach((el, i) => {
      el.style.opacity = '0';
      el.style.transform = 'translateY(24px)';
      el.style.transition = `opacity 0.5s ease ${(i % 4) * 80}ms, transform 0.5s ease ${(i % 4) * 80}ms`;
    });
    const revealObserver = new IntersectionObserver((entries, obs) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
        obs.unobserve(entry.target);
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    revealItems.forEach((el) => revealObserver.observe(el));
  }

  /* ──────────────────────────────────────────
   * Hero Stats Counter Animation
   * ────────────────────────────────────────── */
  const statItems = document.querySelectorAll('.ac-stat-item');
  if (statItems.length) {
    const countObserver = new IntersectionObserver((entries, obs) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const el       = entry.target;
        const text     = el.textContent.trim();
        const match    = text.match(/[\d.]+/);
        if (!match) return;
        const numStr   = match[0];
        const num      = parseFloat(numStr.replace(',', '.'));
        const isFloat  = numStr.includes('.');
        const suffix   = text.replace(numStr, '').trim();
        const steps    = 50;
        const duration = 1000;
        const interval = duration / steps;
        let current    = 0;
        const timer = setInterval(() => {
          current += num / steps;
          if (current >= num) { current = num; clearInterval(timer); }
          el.textContent = (isFloat ? current.toFixed(1) : Math.floor(current).toLocaleString('de-DE')) + (suffix ? ' ' + suffix : '');
        }, interval);
        obs.unobserve(el);
      });
    }, { threshold: 0.5 });
    statItems.forEach((el) => countObserver.observe(el));
  }

  /* ──────────────────────────────────────────
   * Category Filter Tabs (if present on archive page)
   * ────────────────────────────────────────── */
  const filterTabs = document.querySelectorAll('.ac-filter-tab');
  filterTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
      filterTabs.forEach((t) => t.classList.remove('active'));
      tab.classList.add('active');
      const category = tab.dataset.category || 'all';
      document.querySelectorAll('.ac-card[data-category]').forEach((card) => {
        if (category === 'all' || card.dataset.category === category) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });

})();
