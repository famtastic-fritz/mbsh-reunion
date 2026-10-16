// Shared cinematic shell: conventional menu, progressive video, and page state.
(function () {
  'use strict';

  const page = document.body.dataset.page || 'home';
  document.body.classList.add('cinematic-proof');

  const routes = [
    ['home', 'Home', '/index.html'],
    ['tickets', 'Mixer Tickets', '/tickets.html'],
    ['through-years', 'Through the Years', '/through-years.html'],
    ['memorial', 'In Memory', '/memorial.html'],
    ['capsule', 'Time Capsule', '/capsule.html'],
    ['playlist', 'Soundtrack', '/playlist.html'],
    ['manual', 'How the reunion site works', '/manual/'],
    ['portal', 'My Ticket / Login', '/portal/login']
  ];

  const footerMarkup = `
    <div class="footer__rail footer__rail--top" aria-hidden="true"></div>
    <div class="footer__inner">
      <img class="footer__seal" src="/assets/premiere/brand-mark-foil.png?v=2" alt="Class of '96 and MBSH 1926–2026 commemorative seal" width="140" height="140" loading="lazy">
      <p class="footer__seal-line">MBSH · 1926 — 2026</p>
      <p class="footer__class">Class of 1996 · 30th Reunion</p>
      <p class="footer__motto">Let us be known for our deeds.</p>
      <hr class="footer__rule">
      <div class="footer__credits">
        <p class="footer__credits-eyebrow">— A final credit roll —</p>
        <p class="footer__credits-line"><strong>Reunion Committee</strong> <a href="mailto:committee@mbsh96reunion.com">committee@mbsh96reunion.com</a></p>
        <p class="footer__credits-line">${routes.map(([key, label, href]) => `<a href="${href}"${key === page ? ' aria-current="page"' : ''}>${label}</a>`).join(' · ')}</p>
        <p class="footer__credits-line"><a href="/index.html#venue">Location &amp; directions</a> · <a href="https://miamibeachseniorhigh.net" rel="noopener">Official MBSH Site</a></p>
        <p class="footer__credits-line footer__credits-line--social">Instagram &amp; Facebook coming soon — contact the committee for updates.</p>
      </div>
      <hr class="footer__rule">
      <div class="footer__encore">
        <p class="footer__encore-eyebrow">— Encore —</p>
        <p class="footer__copyright">© 2026 MBSH Class of '96 Reunion</p>
        <p class="footer__credit">Reunion experience created by <a href="https://famtasticdesigns.com" rel="noopener">FAMtastic Designs</a></p>
        <p class="footer__credit"><strong>Your business deserves its premiere.</strong> <a href="https://famtasticdesigns.com" rel="noopener">Launch a Starter Website for $199 →</a></p>
      </div>
    </div>
    <div class="footer__rail footer__rail--bottom" aria-hidden="true"></div>`;

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
      <a class="cinema-alumni-login" href="/portal/login" data-analytics-cta aria-label="My Ticket — sign in to your private ticket wallet">
        <span class="cinema-alumni-login__signal" aria-hidden="true"></span>
        <span><strong>My Ticket</strong><small>Sign in</small></span>
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
      <p class="cinema-drawer__intro">The mixer, your ticket, and our memories.</p>
      <nav>${routes.map(([key, label, href]) => `<a href="${href}"${key === page ? ' aria-current="page"' : ''}>${label}</a>`).join('')}</nav>
      <a class="cinema-drawer__primary cinema-button" href="/tickets.html#ticket-order" data-analytics-cta>Get Mixer Tickets — $30</a>`;

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

  function normalizeFooter() {
    const footer = document.querySelector('footer.footer');
    if (!footer) return;
    footer.dataset.template = 'footer';
    footer.classList.add('footer--final-reel');
    footer.setAttribute('aria-label', 'The final reel');
    footer.innerHTML = footerMarkup;
  }

  function identifyMain() {
    const main = document.querySelector('main') || document.querySelector('.cinema-hero') || document.querySelector('.page-header');
    if (main && !main.id) main.id = 'main-content';
    if (main) main.setAttribute('tabindex', '-1');
  }

  function mountReunionNavigator() {
    // The Field Guide is a browseable reference and presentation in its own
    // right. Its own controls must remain the only guidance surface there.
    if (page === 'manual') return;
    const ready = () => window.MBSHReunionNavigator?.mountPublic?.();
    if (window.MBSHReunionNavigator) {
      ready();
      return;
    }
    if (!document.querySelector('link[data-reunion-navigator-style]')) {
      const style = document.createElement('link');
      style.rel = 'stylesheet';
      style.href = '/css/reunion-navigator.css?v=1';
      style.dataset.reunionNavigatorStyle = 'true';
      document.head.appendChild(style);
    }
    const script = document.createElement('script');
    script.src = '/js/reunion-navigator.js?v=3&event=mixersimple20261016';
    script.defer = true;
    script.dataset.reunionNavigatorScript = 'true';
    script.addEventListener('load', ready, { once: true });
    script.addEventListener('error', () => {
      // Guidance is progressive enhancement; it must never block a public route.
    }, { once: true });
    document.head.appendChild(script);
  }

  function markPreview() {
    const host = window.location.hostname;
    if (host !== 'localhost' && host !== '127.0.0.1') return;
    const banner = document.createElement('div');
    banner.className = 'mixer-preview-notice';
    banner.setAttribute('role', 'status');
    banner.textContent = 'Local preview · Payments unavailable · Production: mbsh96reunion.com';
    document.body.prepend(banner);
  }

  function init() {
    mountShell();
    normalizeFooter();
    identifyMain();
    enhanceMedia();
    if (page !== 'home' && page !== 'tickets') mountReunionNavigator();
    markPreview();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
