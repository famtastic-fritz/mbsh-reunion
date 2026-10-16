// config.js — runtime config, fetched once and applied to data-config-bind elements
(function () {
  'use strict';
  window.__SITE_CONFIG__ = null;

  function applyConfig(cfg) {
    window.__SITE_CONFIG__ = cfg;
    const formatted = {
      reunion_date_display: cfg.REUNION_DATE_DISPLAY || cfg.REUNION_DATE || 'Date TBD',
      reunion_venue: cfg.REUNION_VENUE === 'TBA' ? 'Venue announcement coming soon' : cfg.REUNION_VENUE,
      ticket_price: cfg.TICKET_PRICE,
      reunion_time_display: cfg.REUNION_TIME_DISPLAY,
      reunion_food_note: cfg.REUNION_FOOD_NOTE
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
        "REUNION_DATE": "2026-11-07",
        "REUNION_DATE_DISPLAY": "Saturday, November 7, 2026 — Twisted Tonic",
        "REUNION_TIME": "19:00",
        "REUNION_VENUE": "Twisted Tonic",
        "REUNION_ADDRESS": "723 Lincoln Ln N, Miami Beach, FL 33139",
        "EARLY_BIRD_ACTIVE": false,
        "PAYMENTS_STATUS": "order-request",
        "REGISTRATION_STATUS": "open",
        "COMMITTEE_EMAIL": "mbsh96reunion@gmail.com",
        "API_BASE_URL": "https://mbsh96reunion.com",
        "API_BASE_URL_DEV": "http://localhost:8888",
        "HARRY_VOICE_ID": null,
        "BUILD_STATUS": "mixer-preview-2026-10-16",
        "_provenance": "Committee update supplied by Fritz, 2026-10-16. Replacement mixer; final attendee charge $30 per person. Visual preview pending.",
        "REUNION_NAME": "MBSH Class of 1996 — 30th Reunion Mixer",
        "REUNION_START_AT": "2026-11-07T19:00:00-05:00",
        "REUNION_END_AT": "2026-11-07T22:00:00-05:00",
        "REUNION_TIME_DISPLAY": "7–10 PM",
        "REUNION_TIMEZONE": "America/New_York",
        "REUNION_FOOD_NOTE": "Food on your own",
        "TICKET_PRICE": 30,
        "TICKET_CURRENCY": "USD",
        "TICKET_OFFER_ID": "mbsh-mixer-2026-11-07-v1"
});
    });
})();
