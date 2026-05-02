// compass.js — Hi-Tide Compass medallion nav toggle + outside-click + Escape
(function () {
  'use strict';
  const nav = document.querySelector('.compass-nav');
  const toggle = document.getElementById('compass-nav-toggle');
  const items = document.getElementById('compass-nav-items');
  if (!nav || !toggle || !items) return;

  function setOpen(open) {
    nav.dataset.expanded = open ? 'true' : 'false';
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  setOpen(false);

  toggle.addEventListener('click', (e) => {
    e.stopPropagation();
    setOpen(nav.dataset.expanded !== 'true');
  });

  document.addEventListener('click', (e) => {
    if (nav.dataset.expanded === 'true' && !nav.contains(e.target)) setOpen(false);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && nav.dataset.expanded === 'true') {
      setOpen(false);
      toggle.focus();
    }
  });
})();
