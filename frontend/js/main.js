// main.js — bootstrap + cross-section orchestration
(function () {
  'use strict';

  // Story scroll-fade — IntersectionObserver on .story__caption + .story__forever-mark
  function initScrollFade() {
    const nodes = document.querySelectorAll('.story__caption, .story__forever-mark');
    if (!nodes.length || !('IntersectionObserver' in window)) {
      nodes.forEach(n => n.classList.add('is-visible'));
      return;
    }
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.25, rootMargin: '0px 0px -10% 0px' });
    nodes.forEach(n => observer.observe(n));
  }

  // Stamp form_loaded_at on every form (anti-bot)
  function stampFormLoadedAt() {
    const now = Date.now();
    document.querySelectorAll('input[name="form_loaded_at"]').forEach(input => {
      input.value = String(now);
    });
  }

  // Helper exposed for other scripts
  window.__famHelpers = {
    formLoadedAtIsRecent(formEl, minMs = 3000) {
      const ts = parseInt(formEl.querySelector('input[name="form_loaded_at"]')?.value || '0', 10);
      return ts && (Date.now() - ts) >= minMs;
    },
    apiUrl(path) {
      const base = (window.__SITE_CONFIG__ && (window.__SITE_CONFIG__.API_BASE_URL || window.__SITE_CONFIG__.API_BASE_URL_DEV)) || '';
      return (base.replace(/\/$/, '')) + (path.startsWith('/') ? path : '/' + path);
    },
    serializeForm(formEl) {
      const fd = new FormData(formEl);
      const o = {};
      for (const [k, v] of fd.entries()) {
        if (v instanceof File) continue;
        if (k in o) { o[k] = [].concat(o[k], v); } else { o[k] = v; }
      }
      return o;
    }
  };

  function init() {
    initScrollFade();
    stampFormLoadedAt();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else { init(); }
})();
