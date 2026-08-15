// Shared cinematic shell: conventional menu, progressive video, and page state.
(function () {
  'use strict';

  const page = document.body.dataset.page || 'home';
  document.body.classList.add('cinematic-proof');

  const routes = [
    ['home', 'Home', '/index.html'],
    ['rsvp', 'RSVP', '/rsvp.html'],
    ['tickets', 'Tickets & Sponsorship', '/tickets.html'],
    ['menu', 'Dinner Preferences', '/menu/'],
    ['survey', 'Class Check-In', '/survey.html'],
    ['through-years', 'Through the Years', '/through-years.html'],
    ['memorial', 'In Memory', '/memorial.html'],
    ['capsule', 'Time Capsule', '/capsule.html'],
    ['playlist', 'Soundtrack', '/playlist.html']
  ];

  function mountShell() {
    if (document.querySelector('.cinema-site-header')) return;

    const skip = document.createElement('a');
    skip.className = 'cinema-skip-link';
    skip.href = '#main-content';
    skip.textContent = 'Skip to main content';
    document.body.prepend(skip);

    const header = document.createElement('header');
    header.className = 'cinema-site-header';
    header.innerHTML = `
      <a class="cinema-site-header__brand" href="/index.html" aria-label="MBSH Class of 1996 reunion home">
        <img src="/assets/brand-mark/brand-mark.png" alt="" width="48" height="48">
        <span class="cinema-site-header__brand-copy"><strong>Class of 1996</strong><span>30th Reunion · 100 Years of Hi-Tides</span></span>
      </a>
      <button class="cinema-menu-toggle" type="button" aria-expanded="false" aria-controls="cinema-drawer">
        <span class="cinema-menu-toggle__bars" aria-hidden="true"></span><span class="cinema-menu-toggle__label">Explore</span>
      </button>`;

    const drawer = document.createElement('aside');
    drawer.className = 'cinema-drawer';
    drawer.id = 'cinema-drawer';
    drawer.setAttribute('aria-label', 'Reunion navigation');
    drawer.setAttribute('aria-hidden', 'true');
    drawer.innerHTML = `
      <p class="cinema-drawer__intro">Choose your next scene, Hi-Tide.</p>
      <nav>${routes.map(([key, label, href]) => `<a href="${href}"${key === page ? ' aria-current="page"' : ''}>${label}</a>`).join('')}</nav>
      <a class="cinema-drawer__primary cinema-button" href="/rsvp.html">Reserve your place</a>`;

    const backdrop = document.createElement('div');
    backdrop.className = 'cinema-backdrop';
    backdrop.hidden = true;

    document.body.prepend(skip, header, drawer, backdrop);

    const toggle = header.querySelector('.cinema-menu-toggle');
    const firstLink = drawer.querySelector('a');
    let lastFocus = null;

    function keepFocusInside(event) {
      if (event.key !== 'Tab' || toggle.getAttribute('aria-expanded') !== 'true') return;
      const focusable = [...drawer.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])')]
        .filter((node) => !node.hidden && node.getAttribute('aria-hidden') !== 'true');
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }

    function setOpen(open) {
      lastFocus = open ? document.activeElement : lastFocus;
      drawer.classList.toggle('is-open', open);
      drawer.setAttribute('aria-hidden', String(!open));
      toggle.setAttribute('aria-expanded', String(open));
      backdrop.hidden = !open;
      document.body.style.overflow = open ? 'hidden' : '';
      if (open) window.setTimeout(() => firstLink.focus(), 380);
      else if (lastFocus && lastFocus.focus) lastFocus.focus();
      window.dispatchEvent(new CustomEvent('harry:menu', { detail: { open } }));
    }

    toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
    backdrop.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') setOpen(false);
      keepFocusInside(event);
    });
  }

  function enhanceMedia() {
    const media = document.querySelector('.cinema-hero video');
    if (!media) return;
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const saveData = navigator.connection && navigator.connection.saveData;
    if (reduce || saveData) return;
    const source = media.dataset.src;
    if (source && !media.src) media.src = source;
    media.play().catch(() => {});
  }

  function prioritizePrimaryTask() {
    if (page !== 'rsvp') return;
    const form = document.querySelector('.rsvp-form-wrap');
    const countdown = document.querySelector('.countdown');
    if (form && countdown) countdown.before(form);
  }

  function identifyMain() {
    const main = document.querySelector('main') || document.querySelector('.cinema-hero') || document.querySelector('.page-header');
    if (main && !main.id) main.id = 'main-content';
    if (main) main.setAttribute('tabindex', '-1');
  }

  function init() {
    mountShell();
    identifyMain();
    prioritizePrimaryTask();
    enhanceMedia();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
