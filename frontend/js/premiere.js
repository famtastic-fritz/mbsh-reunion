/* premiere.js — orchestrator for "The Premiere" theme
   Activates only when body[data-premiere="on"]. No-op otherwise.

   ──────────────────────────────────────────────────────────────────────
   INIT SECTIONS (V3 / Design Map §1)
   ──────────────────────────────────────────────────────────────────────
   1. injectOverlays            — L0 .premiere-stage + L1 .premiere-fx + starfield
   2. injectNav                 — L6 .premiere-nav (DEPRECATED P1: replace with medallion menu)
   3. wireNavScrollHide         — auto-hide on scroll (DEPRECATED P1)
   4. attachSnapIn              — IntersectionObserver fade-in (replaced P1 by real snap)
   5. curtainRise               — L7 page-transition (sessionStorage-once on home)
   6. mountUsher                — L4 Harry button + per-page pose swap + idle hints
   7. activateStoryScene        — Story Then/Now/Forever IO triggers
   8. activatePreviewCards      — preview card stagger fade-in (replaced P1 by frame component)
   9. activateMemorial          — memorial names stagger
   10. activateRSVPStub          — ticket stub flip on success
   11. activateCapsule           — envelope flap open
   12. activateGenericFades      — view() timeline progressive enhancement

   ──────────────────────────────────────────────────────────────────────
   PASSES INCOMING (V3 / Design Map §0)
   ──────────────────────────────────────────────────────────────────────
   P1 will add:
   - Medallion-as-menu (replaces injectNav + scroll-hide)
   - Snap mechanism (scrollend + bounce, iOS Safari fallback)
   - Harry walk/peek/celebrate vocabulary
   - Filmstrip frame + ribbon divider components (CSS-only, no JS handler)

   P1 will remove:
   - injectNav (replaced by mountMedallionMenu)
   - wireNavScrollHide (replaced by medallion always-visible)
   - .is-visible bounce path (replaced by real scroll-snap event)

   ──────────────────────────────────────────────────────────────────────
   AUTHORITY: V2 creative direction + V3 production protocol +
              PREMIERE-DESIGN-MAP-2026-05-07.md
   ──────────────────────────────────────────────────────────────────────

   Respects prefers-reduced-motion AND prefers-reduced-data.
*/
(function () {
  'use strict';

  if (document.body.getAttribute('data-premiere') !== 'on') return;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const reduceData   = window.matchMedia('(prefers-reduced-data: reduce)').matches;
  const cinematicOK  = !reduceMotion && !reduceData;

  const page = document.body.getAttribute('data-page') || 'unknown';

  /* -----------------------------------------------------------------
     1. Curtain rise — once per session, fires on first page load
     ----------------------------------------------------------------- */
  function curtainRise() {
    if (reduceMotion) return;
    const SESSION_KEY = 'premiere_curtain_seen';
    if (sessionStorage.getItem(SESSION_KEY)) return;
    if (page !== 'home') return;

    const curtain = document.createElement('div');
    curtain.className = 'premiere-curtain';
    curtain.setAttribute('aria-hidden', 'true');
    const title = document.createElement('div');
    title.className = 'premiere-curtain__title';
    title.textContent = 'MBSH presents';
    curtain.appendChild(title);
    document.body.appendChild(curtain);

    requestAnimationFrame(() => {
      curtain.classList.add('is-rising');
    });
    setTimeout(() => {
      curtain.remove();
      sessionStorage.setItem(SESSION_KEY, '1');
    }, 1300);
  }

  /* -----------------------------------------------------------------
     2. Harry-as-usher — sticky, swaps pose by section
     ----------------------------------------------------------------- */
  /* POSE_MAP — V3 §6.2 choreography map.
     Updated P4 (2026-05-07) with real poses from P3 generation queue.
     Pose register 01-23 complete (V3 §4 / DEFERRED-ASSETS.md).
  */
  const POSE_MAP = {
    home_hero:           '01-wave-hello.png',     // existing
    home_story:          '08-pointing.png',       // existing — Story sub-moments use 11-peeking via section observer (below)
    home_event:          '21-pride-celebrate.png', // P3 — was 04-excited-cheer
    home_previews:       '07-confirming.png',     // existing
    home_footer:         '23-salute.png',         // P3 — was 02-thumbs-up
    rsvp:                '15-seated-usher.png',   // P3 — was 06-listening
    rsvp_success:        '13-ticket-stub.png',    // P3 — was 02-thumbs-up
    tickets:             '18-presenting.png',     // P3 — was 08-pointing
    'through-years':     '12-clipboard.png',      // P3 — was 03-thinking
    capsule:             '20-pointing-across.png', // P3 — was 08-pointing
    capsule_success:     '14-wax-stamping.png',   // P3 — was 04-excited-cheer
    playlist:            '16-conducting.png'      // P3 — was 04-excited-cheer
  };

  // Section-aware idle hints that Harry says when sitting on a section
  const HINT_MAP = {
    home_hero:           "Welcome back. Tap me with any question.",
    home_story:          "Want me to walk you through?",
    home_event:          "Ask me about the night.",
    home_previews:       "Need help finding something?",
    home_footer:         "I'm right here if you need me.",
    rsvp:                "Stuck on the RSVP? Ask me.",
    tickets:             "Questions about tickets or sponsorship?",
    'through-years':     "Want me to find a specific year?",
    capsule:             "Ask me what to write.",
    playlist:            "Which song should we add?"
  };

  function mountUsher() {
    // Build Harry as a real interactive button — visible context-aware assistant
    const usher = document.createElement('button');
    usher.type = 'button';
    usher.className = 'premiere-usher';
    usher.setAttribute('aria-label', 'Open Hi-Tide Harry — your reunion assistant');

    const img = document.createElement('img');
    img.alt = '';
    img.setAttribute('aria-hidden', 'true');
    img.src = 'assets/mascot/' + (POSE_MAP[page] || POSE_MAP.home_hero);
    img.width = 120; img.height = 120;

    const hint = document.createElement('span');
    hint.className = 'premiere-usher__hint';
    hint.textContent = HINT_MAP[page] || HINT_MAP.home_hero;

    usher.appendChild(img);
    usher.appendChild(hint);
    document.body.appendChild(usher);

    setTimeout(() => usher.classList.add('is-visible'), 400);

    // Periodic idle hints — every 12s show the contextual hint for 4s
    let hintTimer;
    function scheduleHint() {
      clearTimeout(hintTimer);
      hintTimer = setTimeout(() => {
        usher.classList.add('is-hinting');
        setTimeout(() => usher.classList.remove('is-hinting'), 4000);
        scheduleHint();
      }, 12000);
    }
    if (!reduceMotion) {
      // First hint shows ~3s after page load to draw attention
      setTimeout(() => {
        usher.classList.add('is-hinting');
        setTimeout(() => usher.classList.remove('is-hinting'), 4000);
      }, 3000);
      scheduleHint();
    }

    // Click/tap/keyboard → open the existing chatbot panel
    function openChat() {
      const toggle = document.getElementById('chatbot-toggle');
      const panel  = document.getElementById('chatbot-panel');
      if (toggle && typeof toggle.click === 'function') {
        toggle.click();
      } else if (panel) {
        panel.hidden = false;
      }
      document.body.classList.add('premiere-chatbot-open');
    }
    usher.addEventListener('click', openChat);

    // Listen for chatbot close to bring Harry back
    document.addEventListener('click', (e) => {
      if (e.target.closest('#chatbot-close')) {
        document.body.classList.remove('premiere-chatbot-open');
      }
    });

    // Section-aware pose + hint on home
    if (page === 'home') {
      const sectionMap = [
        { selector: '.hero',          pose: POSE_MAP.home_hero,     hint: HINT_MAP.home_hero },
        { selector: '.story',         pose: POSE_MAP.home_story,    hint: HINT_MAP.home_story },
        { selector: '.event-details', pose: POSE_MAP.home_event,    hint: HINT_MAP.home_event },
        { selector: '.previews',      pose: POSE_MAP.home_previews, hint: HINT_MAP.home_previews },
        { selector: '.footer',        pose: POSE_MAP.home_footer,   hint: HINT_MAP.home_footer }
      ];
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          const match = sectionMap.find(s => entry.target.matches(s.selector));
          if (!match) return;
          if (img.src.endsWith(match.pose)) return;
          img.style.opacity = '0';
          // Trigger step animation on section change
          usher.classList.add('is-stepping');
          setTimeout(() => usher.classList.remove('is-stepping'), 700);
          setTimeout(() => {
            img.src = 'assets/mascot/' + match.pose;
            img.style.opacity = '1';
            hint.textContent = match.hint;
          }, 250);
        });
      }, { threshold: 0.4 });
      sectionMap.forEach(s => {
        const el = document.querySelector(s.selector);
        if (el) observer.observe(el);
      });
    }
  }

  /* -----------------------------------------------------------------
     3. Story scene — Ken Burns + caption fade
     ----------------------------------------------------------------- */
  function activateStoryScene() {
    if (page !== 'home') return;
    const moments = document.querySelectorAll('.story__moment');
    const captions = document.querySelectorAll('.story__caption');
    if (!moments.length) return;

    const momentObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        entry.target.classList.toggle('is-active', entry.isIntersecting);
      });
    }, { threshold: 0.3 });
    moments.forEach(m => momentObserver.observe(m));

    const captionObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          captionObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });
    captions.forEach(c => captionObserver.observe(c));

    const foreverMark = document.querySelector('.story__forever-mark');
    if (foreverMark) {
      const fmObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            fmObserver.unobserve(entry.target);
          }
        });
      }, { threshold: 0.5 });
      fmObserver.observe(foreverMark);
    }
  }

  /* -----------------------------------------------------------------
     4. Preview cards — fade-in on enter
     ----------------------------------------------------------------- */
  function activatePreviewCards() {
    const cards = document.querySelectorAll('.preview-card');
    if (!cards.length) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry, i) => {
        if (entry.isIntersecting) {
          setTimeout(() => entry.target.classList.add('is-visible'), i * 80);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 });
    cards.forEach(c => observer.observe(c));
  }

  /* -----------------------------------------------------------------
     5. Memorial names — stagger fade-in
     ----------------------------------------------------------------- */
  function activateMemorial() {
    if (page !== 'memorial') return;
    const list = document.querySelector('.memorial__names');
    if (!list) return;

    const observeNames = () => {
      const names = list.querySelectorAll('li');
      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
          if (entry.isIntersecting) {
            setTimeout(() => entry.target.classList.add('is-visible'), i * 200);
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.3 });
      names.forEach(n => observer.observe(n));
    };

    if (list.children.length > 0) observeNames();
    const mo = new MutationObserver(observeNames);
    mo.observe(list, { childList: true });
  }

  /* -----------------------------------------------------------------
     6. RSVP success — ticket stub flip
     Uses safe DOM methods (no innerHTML)
     ----------------------------------------------------------------- */
  function activateRSVPStub() {
    if (page !== 'rsvp') return;
    const form = document.getElementById('rsvp-form');
    if (!form) return;

    let stub = document.querySelector('.premiere-ticket-stub');
    if (!stub) {
      stub = document.createElement('div');
      stub.className = 'premiere-ticket-stub';
      stub.setAttribute('aria-hidden', 'true');

      const brand = document.createElement('div');
      brand.className = 'premiere-ticket-stub__brand';
      brand.textContent = "MBSH · CLASS OF '96";

      const nameEl = document.createElement('div');
      nameEl.className = 'premiere-ticket-stub__name';
      nameEl.setAttribute('data-stub-name', '');
      nameEl.textContent = 'Welcome back';

      const meta = document.createElement('div');
      meta.className = 'premiere-ticket-stub__meta';
      meta.textContent = '30TH REUNION · ROW 96 · SEAT 30';

      stub.appendChild(brand);
      stub.appendChild(nameEl);
      stub.appendChild(meta);
      form.parentNode.insertBefore(stub, form.nextSibling);
    }

    form.addEventListener('rsvp:success', (e) => {
      const name = (e.detail && e.detail.name) || form.querySelector('[name="name"]')?.value || "Class of '96";
      const target = stub.querySelector('[data-stub-name]');
      if (target) target.textContent = name;
      stub.classList.add('is-visible');
      const usherImg = document.querySelector('.premiere-usher img');
      if (usherImg) usherImg.src = 'assets/mascot/' + POSE_MAP.rsvp_success;
    });
  }

  /* -----------------------------------------------------------------
     7. Capsule — envelope opens shortly after page load
     ----------------------------------------------------------------- */
  function activateCapsule() {
    if (page !== 'capsule') return;
    const env = document.querySelector('.capsule__envelope');
    if (!env) return;
    setTimeout(() => env.classList.add('is-open'), 800);
  }

  /* -----------------------------------------------------------------
     8. Generic fades — view() timeline progressive enhancement
     ----------------------------------------------------------------- */
  function activateGenericFades() {
    const els = document.querySelectorAll('[data-premiere-fade]');
    if (!els.length) return;
    if (CSS.supports && CSS.supports('animation-timeline: view()')) {
      els.forEach(el => el.classList.add('premiere-on-view'));
      return;
    }
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.25 });
    els.forEach(el => observer.observe(el));
  }

  /* =================================================================
     P1 ADDITIONS (Design Map §0 Pass 1)
     mountMedallionMenu  — D5 (replaces injectNav + wireNavScrollHide)
     wireSnapBounce      — D8 (scrollend + .is-snapped)
     wireHarryVocabulary — Harry walk + celebrate triggers
     ================================================================= */

  /* Mount the medallion-as-menu component.
     Replaces V1 top nav + compass medallion. Brand-mark sized button on home,
     scaled 70% on inner pages. Click expands radial menu of 7 destinations. */
  function mountMedallionMenu() {
    if (document.querySelector('.premiere-medallion')) return;

    const NAV_ITEMS = [
      { href: 'index.html',         label: 'Home',           page: 'home' },
      { href: 'rsvp.html',          label: 'RSVP',           page: 'rsvp' },
      { href: 'tickets.html',       label: 'Tickets',        page: 'tickets' },
      { href: 'through-years.html', label: 'Through Years',  page: 'through-years' },
      { href: 'memorial.html',      label: 'In Memory',      page: 'memorial' },
      { href: 'capsule.html',       label: 'Capsule',        page: 'capsule' },
      { href: 'playlist.html',      label: 'Playlist',       page: 'playlist' }
    ];

    // The medallion button itself
    const medallion = document.createElement('button');
    medallion.type = 'button';
    medallion.className = 'premiere-medallion';
    medallion.setAttribute('aria-label', 'Open menu');
    medallion.setAttribute('aria-expanded', 'false');
    medallion.setAttribute('aria-controls', 'premiere-medallion-menu');
    const img = document.createElement('img');
    img.src = 'assets/brand-mark/brand-mark.png';
    img.alt = '';
    img.setAttribute('aria-hidden', 'true');
    medallion.appendChild(img);
    document.body.appendChild(medallion);

    // The menu overlay
    const menu = document.createElement('div');
    menu.className = 'premiere-medallion-menu';
    menu.id = 'premiere-medallion-menu';
    menu.setAttribute('role', 'dialog');
    menu.setAttribute('aria-modal', 'false');
    menu.setAttribute('aria-label', 'Site navigation');
    menu.hidden = false; // we toggle via .is-open instead

    const backdrop = document.createElement('div');
    backdrop.className = 'premiere-medallion-menu__backdrop';
    backdrop.setAttribute('aria-hidden', 'true');

    const list = document.createElement('ul');
    list.className = 'premiere-medallion-menu__items';
    list.setAttribute('role', 'menu');

    // Distribute 7 items radially on a ~270° arc above the medallion
    // (skip the bottom 90° so menu doesn't extend off-screen on home).
    // angles in degrees, 0 = up, positive = clockwise
    const arcStart = -135;
    const arcEnd   = 135;
    const arcSpan  = arcEnd - arcStart;
    NAV_ITEMS.forEach((item, i) => {
      const angle = arcStart + (arcSpan * i) / (NAV_ITEMS.length - 1);
      const li = document.createElement('li');
      li.className = 'premiere-medallion-menu__item';
      li.style.setProperty('--angle', angle + 'deg');
      li.setAttribute('role', 'none');
      const a = document.createElement('a');
      a.href = item.href;
      a.className = 'premiere-medallion-menu__link';
      a.textContent = item.label;
      a.setAttribute('role', 'menuitem');
      if (item.page === page) a.setAttribute('aria-current', 'page');
      li.appendChild(a);
      list.appendChild(li);
    });

    menu.appendChild(backdrop);
    menu.appendChild(list);
    document.body.appendChild(menu);

    // Mark body so CSS can hide V1 top nav + compass + edge strips
    document.body.setAttribute('data-medallion-menu', 'mounted');

    // Open / close
    function openMenu() {
      // Anchor the radial menu around the medallion's actual position.
      // CSS variables --menu-cx / --menu-cy drive item placement.
      const r = medallion.getBoundingClientRect();
      const cx = r.left + r.width / 2;
      const cy = r.top + r.height / 2;
      list.style.setProperty('--menu-cx', cx + 'px');
      list.style.setProperty('--menu-cy', cy + 'px');
      menu.classList.add('is-open');
      medallion.setAttribute('aria-expanded', 'true');
      // Focus the first menu item for keyboard users
      requestAnimationFrame(() => {
        const first = list.querySelector('a');
        if (first) first.focus();
      });
    }
    function closeMenu() {
      menu.classList.remove('is-open');
      medallion.setAttribute('aria-expanded', 'false');
      medallion.focus();
    }
    function toggleMenu() {
      if (menu.classList.contains('is-open')) closeMenu();
      else openMenu();
    }

    medallion.addEventListener('click', toggleMenu);
    backdrop.addEventListener('click', closeMenu);

    // Esc closes
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && menu.classList.contains('is-open')) {
        e.preventDefault();
        closeMenu();
      }
    });

    // Arrow keys cycle within menu
    list.addEventListener('keydown', (e) => {
      const items = Array.from(list.querySelectorAll('a'));
      const idx = items.indexOf(document.activeElement);
      if (idx < 0) return;
      if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
        e.preventDefault();
        items[(idx + 1) % items.length].focus();
      } else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
        e.preventDefault();
        items[(idx - 1 + items.length) % items.length].focus();
      } else if (e.key === 'Home') {
        e.preventDefault();
        items[0].focus();
      } else if (e.key === 'End') {
        e.preventDefault();
        items[items.length - 1].focus();
      }
    });
  }

  /* Snap mechanism — listen for scrollend, add .is-snapped to the snapped section,
     remove after the bounce animation completes. */
  function wireSnapBounce() {
    if (document.body.getAttribute('data-snap') !== 'on') return;
    const targets = document.querySelectorAll('.premiere-snap-target');
    if (!targets.length) return;

    function findSnapped() {
      const center = window.innerHeight / 2;
      let best = null;
      let bestDist = Infinity;
      targets.forEach(el => {
        const rect = el.getBoundingClientRect();
        const elCenter = rect.top + rect.height / 2;
        const dist = Math.abs(elCenter - center);
        if (dist < bestDist) {
          best = el;
          bestDist = dist;
        }
      });
      return best;
    }

    function handleSnap() {
      const snapped = findSnapped();
      if (!snapped) return;
      if (snapped.classList.contains('is-snapped')) return; // already animated
      targets.forEach(t => t.classList.remove('is-snapped'));
      snapped.classList.add('is-snapped');
      setTimeout(() => snapped.classList.remove('is-snapped'), 700);
    }

    if ('onscrollend' in window) {
      window.addEventListener('scrollend', handleSnap, { passive: true });
    } else {
      // Debounced fallback for browsers without scrollend
      let scrollTimer;
      window.addEventListener('scroll', () => {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(handleSnap, 150);
      }, { passive: true });
    }
  }

  /* Harry walk + celebrate triggers.
     The existing mountUsher() handles pose-swap on section change; this
     extends it to add `.is-walking` between sections and `.is-celebrating`
     on form-submit success events. */
  function wireHarryVocabulary() {
    const usher = document.querySelector('.premiere-usher');
    if (!usher) return;

    // Walk on section change (home only — desktop only)
    if (page === 'home' && !reduceMotion && window.matchMedia('(min-width: 721px)').matches) {
      const sections = document.querySelectorAll('.hero, .story, .event-details, .previews, .footer');
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting && entry.intersectionRatio > 0.4) {
            usher.classList.remove('is-walking');
            // Force reflow so animation re-fires
            void usher.offsetWidth;
            usher.classList.add('is-walking');
            setTimeout(() => usher.classList.remove('is-walking'), 1100);
          }
        });
      }, { threshold: 0.4 });
      sections.forEach(s => observer.observe(s));
    }

    // Celebrate on any form submit success — listen for custom events
    ['rsvp:success', 'capsule:success', 'memory:success', 'sponsor:success', 'playlist:success'].forEach(eventName => {
      document.addEventListener(eventName, () => {
        usher.classList.remove('is-celebrating');
        void usher.offsetWidth;
        usher.classList.add('is-celebrating');
        setTimeout(() => usher.classList.remove('is-celebrating'), 800);
      });
    });
  }

  /* -----------------------------------------------------------------
     9. Auto-inject FX + starfield overlays
     Keeps HTML lean — only the body attribute + CSS/JS links are required.
     ----------------------------------------------------------------- */
  function injectOverlays() {
    if (!document.querySelector('.premiere-stage')) {
      const stage = document.createElement('div');
      stage.className = 'premiere-stage';
      stage.setAttribute('aria-hidden', 'true');
      document.body.insertBefore(stage, document.body.firstChild);
    }
    if (!document.querySelector('.premiere-fx')) {
      const fx = document.createElement('div');
      fx.className = 'premiere-fx';
      fx.setAttribute('aria-hidden', 'true');
      document.body.appendChild(fx);
    }
    if (!document.querySelector('.premiere-starfield')) {
      const sf = document.createElement('div');
      sf.className = 'premiere-starfield';
      sf.setAttribute('aria-hidden', 'true');
      document.body.appendChild(sf);
    }
  }

  /* Auto-hide nav on scroll-down, slide back in on scroll-up */
  function wireNavScrollHide() {
    const nav = document.querySelector('.premiere-nav');
    if (!nav) return;
    let lastY = window.scrollY;
    let ticking = false;
    function onScroll() {
      const y = window.scrollY;
      if (Math.abs(y - lastY) < 8) return; // ignore tiny moves
      if (y > lastY && y > 80) {
        nav.classList.add('is-hidden');
      } else {
        nav.classList.remove('is-hidden');
      }
      lastY = y;
      ticking = false;
    }
    window.addEventListener('scroll', () => {
      if (!ticking) {
        window.requestAnimationFrame(onScroll);
        ticking = true;
      }
    }, { passive: true });
  }

  /* Snap-in observer — attach to every section so they bounce in on scroll */
  function attachSnapIn() {
    const targets = document.querySelectorAll(
      '.story__moment, .event-details, .previews, .premiere-section, ' +
      '.countdown, .rsvp-form-wrap, .tickets, .timeline, .memorial, ' +
      '.capsule, .playlist, .memory-submit, .preview-card'
    );
    targets.forEach(t => t.classList.add('premiere-snap-in'));
    if (!targets.length) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.18, rootMargin: '0px 0px -8% 0px' });
    targets.forEach(t => observer.observe(t));
  }

  /* -----------------------------------------------------------------
     Inject top primary nav (real menu, not just compass)
     ----------------------------------------------------------------- */
  function injectNav() {
    if (document.querySelector('.premiere-nav')) return;

    const NAV_ITEMS = [
      { href: 'index.html',         label: 'Home',           page: 'home' },
      { href: 'rsvp.html',          label: 'RSVP',           page: 'rsvp' },
      { href: 'tickets.html',       label: 'Tickets',        page: 'tickets' },
      { href: 'through-years.html', label: 'Through Years',  page: 'through-years' },
      { href: 'memorial.html',      label: 'In Memory',      page: 'memorial' },
      { href: 'capsule.html',       label: 'Capsule',        page: 'capsule' },
      { href: 'playlist.html',      label: 'Playlist',       page: 'playlist' }
    ];

    const nav = document.createElement('nav');
    nav.className = 'premiere-nav';
    nav.setAttribute('aria-label', 'Primary');

    const brand = document.createElement('a');
    brand.className = 'premiere-nav__brand';
    brand.href = 'index.html';
    const brandImg = document.createElement('img');
    brandImg.src = 'assets/brand-mark/brand-mark.png';
    brandImg.alt = '';
    brandImg.setAttribute('aria-hidden', 'true');
    const brandText = document.createElement('span');
    brandText.textContent = "Class of '96";
    brand.appendChild(brandImg);
    brand.appendChild(brandText);

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'premiere-nav__toggle';
    toggle.setAttribute('aria-label', 'Toggle menu');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.textContent = '☰';

    const list = document.createElement('ul');
    list.className = 'premiere-nav__links';
    NAV_ITEMS.forEach(item => {
      const li = document.createElement('li');
      const a = document.createElement('a');
      a.href = item.href;
      a.textContent = item.label;
      if (item.page === page) a.setAttribute('aria-current', 'page');
      li.appendChild(a);
      list.appendChild(li);
    });

    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    nav.appendChild(brand);
    nav.appendChild(toggle);
    nav.appendChild(list);
    document.body.insertBefore(nav, document.body.firstChild);
  }

  /* P6 perf fix: defer hero video load until after the page is fully loaded.
     Without this, the autoplay video competes with FCP/LCP and Lighthouse
     pins LCP to the still-loading video. With this, LCP is the poster image. */
  function deferHeroVideo() {
    const v = document.querySelector('.hero__video[data-src]');
    if (!v) return;
    const start = () => {
      const src = v.getAttribute('data-src');
      if (!src) return;
      v.src = src;
      v.removeAttribute('data-src');
      v.setAttribute('autoplay', '');
      // Some browsers ignore autoplay when programmatically set; explicit play
      const p = v.play();
      if (p && typeof p.catch === 'function') p.catch(() => { /* allow user-gesture fallback */ });
    };
    if (document.readyState === 'complete') {
      // Already loaded — start after a microtask
      setTimeout(start, 0);
    } else {
      window.addEventListener('load', () => setTimeout(start, 0), { once: true });
    }
  }

  function init() {
    try { injectOverlays(); }       catch (e) { console.warn('[premiere] fx', e); }
    try { deferHeroVideo(); }       catch (e) { console.warn('[premiere] video-defer', e); }
    try { mountMedallionMenu(); }   catch (e) { console.warn('[premiere] medallion', e); }
    /* injectNav + wireNavScrollHide intentionally not called when medallion mounts;
       CSS hides them via [data-medallion-menu="mounted"]. Kept in source for one
       cycle so rollback is clean. */
    try { attachSnapIn(); }         catch (e) { console.warn('[premiere] snap-in', e); }
    try { wireSnapBounce(); }       catch (e) { console.warn('[premiere] snap-bounce', e); }
    try { curtainRise(); }          catch (e) { console.warn('[premiere] curtain', e); }
    try { mountUsher(); }           catch (e) { console.warn('[premiere] usher', e); }
    try { activateStoryScene(); }   catch (e) { console.warn('[premiere] story', e); }
    try { activatePreviewCards(); } catch (e) { console.warn('[premiere] previews', e); }
    try { activateMemorial(); }     catch (e) { console.warn('[premiere] memorial', e); }
    try { activateRSVPStub(); }     catch (e) { console.warn('[premiere] rsvp', e); }
    try { activateCapsule(); }      catch (e) { console.warn('[premiere] capsule', e); }
    try { activateGenericFades(); } catch (e) { console.warn('[premiere] fades', e); }
    try { wireHarryVocabulary(); }  catch (e) { console.warn('[premiere] harry-vocab', e); }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else { init(); }
})();
