// countdown.js — live countdown to reunion date 7:00 PM ET (UTC-4 EDT, UTC-5 EST)
(function () {
  'use strict';
  const els = {
    days: document.getElementById('countdown-days'),
    hours: document.getElementById('countdown-hours'),
    mins: document.getElementById('countdown-mins'),
    secs: document.getElementById('countdown-secs')
  };
  if (!els.days) return;

  function getTargetTime() {
    const cfg = window.__SITE_CONFIG__ || {};
    if (!cfg.REUNION_DATE) return null;
    // Build target as 7:00 PM Eastern (UTC-4 EDT for July)
    const datePart = cfg.REUNION_DATE; // e.g. 2026-07-12
    const isoEdt = `${datePart}T19:00:00-04:00`;
    const t = Date.parse(isoEdt);
    return isNaN(t) ? null : t;
  }

  function pad(n) { return String(n).padStart(2, '0'); }

  function tick() {
    const target = getTargetTime();
    if (!target) {
      Object.values(els).forEach(el => el.textContent = '--');
      return;
    }
    const now = Date.now();
    const diff = Math.max(0, target - now);
    const d = Math.floor(diff / 86400000);
    const h = Math.floor((diff % 86400000) / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    els.days.textContent = pad(d);
    els.hours.textContent = pad(h);
    els.mins.textContent = pad(m);
    els.secs.textContent = pad(s);
  }

  document.addEventListener('siteconfig:loaded', () => { tick(); setInterval(tick, 1000); });
  tick();
})();
