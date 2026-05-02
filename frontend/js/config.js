// config.js — runtime config, fetched once and applied to data-config-bind elements
(function () {
  'use strict';
  window.__SITE_CONFIG__ = null;

  function applyConfig(cfg) {
    window.__SITE_CONFIG__ = cfg;
    const formatted = {
      reunion_date_display: cfg.REUNION_DATE_DISPLAY || cfg.REUNION_DATE || 'Date TBD',
      reunion_venue: cfg.REUNION_VENUE === 'TBA' ? 'Venue announcement coming soon' : cfg.REUNION_VENUE,
      early_bird_price: cfg.EARLY_BIRD_PRICE,
      regular_price: cfg.REGULAR_PRICE,
      early_bird_deadline_display: cfg.EARLY_BIRD_DEADLINE_DISPLAY || cfg.EARLY_BIRD_DEADLINE
    };
    document.querySelectorAll('[data-config-bind]').forEach(el => {
      const key = el.getAttribute('data-config-bind');
      if (formatted[key] != null) el.textContent = formatted[key];
    });
    // Toggle payments-state visibility
    const state = cfg.PAYMENTS_STATUS || 'disabled';
    document.querySelectorAll('[data-payments-state]').forEach(el => {
      el.hidden = el.getAttribute('data-payments-state') !== state;
    });
    // Early bird auto-hide
    if (cfg.EARLY_BIRD_DEADLINE && cfg.EARLY_BIRD_ACTIVE === false) {
      document.querySelectorAll('[data-early-bird-show]').forEach(el => el.hidden = true);
    }
    document.dispatchEvent(new CustomEvent('siteconfig:loaded', { detail: cfg }));
  }

  fetch('/config/site-config.json', { cache: 'no-cache' })
    .then(r => r.ok ? r.json() : Promise.reject(r.status))
    .then(applyConfig)
    .catch(err => {
      console.warn('[config] failed to load /config/site-config.json:', err);
      // Fallback config so the page still functions
      applyConfig({
        REUNION_DATE: '2026-07-12',
        REUNION_DATE_DISPLAY: 'October–November 2026 — Miami Beach',
        REUNION_VENUE: 'TBA',
        EARLY_BIRD_PRICE: 60,
        REGULAR_PRICE: 75,
        EARLY_BIRD_DEADLINE: '2026-06-01',
        EARLY_BIRD_ACTIVE: true,
        PAYMENTS_STATUS: 'disabled',
        REGISTRATION_STATUS: 'open',
        API_BASE_URL: null
      });
    });
})();
