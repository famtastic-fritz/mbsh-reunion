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
  const sequenceEntry = window.PageSequence && window.PageSequence.getEntry ? window.PageSequence.getEntry(page) : null;

  function sceneMarkerText(entry) {
    return window.PageSequence && window.PageSequence.formatSceneMarker ? window.PageSequence.formatSceneMarker(entry) : '';
  }

  function scriptSceneText(entry) {
    return window.PageSequence && window.PageSequence.formatScriptScene ? window.PageSequence.formatScriptScene(entry) : sceneMarkerText(entry);
  }

  function escapeAttr(value) {
    return String(value || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  /* -----------------------------------------------------------------
     1. Curtain rise — once per session, fires on first page load.
     Slowed to 3200ms (Fritz: 'way way too fast'). Schedules the
     Harry intro bubble to appear after the curtain finishes.
     ----------------------------------------------------------------- */
  function curtainRise() {
    if (reduceMotion) {
      // Reduced-motion: skip curtain animation but still show Harry intro
      setTimeout(harryIntro, 600);
      return;
    }
    const SESSION_KEY = 'premiere_curtain_seen';
    const alreadySeen = sessionStorage.getItem(SESSION_KEY);
    if (page !== 'home' || alreadySeen) {
      // Still trigger Harry intro on home if curtain was skipped
      if (page === 'home') setTimeout(harryIntro, 800);
      return;
    }

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
    // Curtain duration is 3200ms (CSS var --curtain-duration).
    // Remove element after rise + 100ms safety margin.
    setTimeout(() => {
      curtain.remove();
      sessionStorage.setItem(SESSION_KEY, '1');
      // Now Harry steps in with his intro bubble
      setTimeout(harryIntro, 600);
    }, 3300);
  }

  /* Harry intro bubble — first-visit-per-session greeting. Fritz:
     'harry comes in with a message introducing himself and letting users
     know he can help navigate, take suggestions to send to the committee'. */
  function harryIntro() {
    /* Pass 12 — retired. The speech-bubble welcome lived alongside the
       corner .premiere-usher, which we've removed. The home billboard's
       slide-1 ("Welcome inside, Hi-Tide. I'm Harry — your usher for the
       evening.") now carries the same opening line on-system. */
    return;
    /* eslint-disable */
    if (page !== 'home') return;
    const INTRO_KEY = 'premiere_harry_intro_seen';
    if (sessionStorage.getItem(INTRO_KEY)) return;
    const usher = document.querySelector('.premiere-usher');
    if (!usher) return;

    const bubble = document.createElement('div');
    bubble.className = 'premiere-usher-intro';
    bubble.setAttribute('role', 'dialog');
    bubble.setAttribute('aria-label', 'Hi-Tide Harry intro');

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'premiere-usher-intro__close';
    close.setAttribute('aria-label', 'Close intro');
    close.textContent = '×';

    const name = document.createElement('span');
    name.className = 'premiere-usher-intro__name';
    name.textContent = "I'm Hi-Tide Harry.";

    const body = document.createElement('span');
    body.textContent = "I'm your reunion assistant — tap me to ask anything about the night, or send the committee a note. I'll help you navigate, too.";

    bubble.appendChild(close);
    bubble.appendChild(name);
    bubble.appendChild(body);
    document.body.appendChild(bubble);

    requestAnimationFrame(() => bubble.classList.add('is-visible'));

    function dismiss() {
      bubble.classList.remove('is-visible');
      setTimeout(() => bubble.remove(), 700);
      sessionStorage.setItem(INTRO_KEY, '1');
    }
    close.addEventListener('click', (e) => { e.stopPropagation(); dismiss(); });
    // Click anywhere also dismisses (after a tick so the open animation lands)
    setTimeout(() => {
      document.addEventListener('click', dismiss, { once: true });
    }, 600);
    // Auto-dismiss after 12s
    setTimeout(dismiss, 12000);
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

    // Filmstrip drawer NAV_ITEMS — each gets a `tease` line under the title
    const NAV_ITEMS = [
      { href: 'index.html',         label: 'Home',           page: 'home',          tease: 'The Lobby Opens' },
      { href: 'rsvp.html',          label: 'RSVP',           page: 'rsvp',          tease: 'Lock Your Seat' },
      { href: 'tickets.html',       label: 'Tickets',        page: 'tickets',       tease: 'Claim Your Ticket' },
      { href: 'through-years.html', label: 'Through Years',  page: 'through-years', tease: 'Roll the Memory Reel' },
      { href: 'memorial.html',      label: 'In Memory',      page: 'memorial',      tease: 'Hold the Light' },
      { href: 'capsule.html',       label: 'Capsule',        page: 'capsule',       tease: 'Seal the Time Capsule' },
      { href: 'playlist.html',      label: 'Playlist',       page: 'playlist',      tease: 'Drop the Soundtrack' }
    ];

    // P7-fix: on HOME, promote the existing hero brand-mark to BE the menu
    // trigger — eliminates the duplicate medallion (hero brand-mark + floating
    // medallion button were both visible). On inner pages, mount the floating
    // medallion at top-right.
    let medallion;
    const heroMark = document.querySelector('.hero__mark');
    if (page === 'home' && heroMark) {
      // Wrap the hero__mark in a real <button> so it's keyboard-accessible
      const wrapper = document.createElement('button');
      wrapper.type = 'button';
      wrapper.className = 'premiere-medallion premiere-medallion--inline';
      wrapper.setAttribute('aria-label', 'Open menu');
      wrapper.setAttribute('aria-expanded', 'false');
      wrapper.setAttribute('aria-controls', 'premiere-medallion-menu');
      heroMark.parentNode.insertBefore(wrapper, heroMark);
      wrapper.appendChild(heroMark);
      medallion = wrapper;
    } else {
      medallion = document.createElement('button');
      medallion.type = 'button';
      medallion.className = 'premiere-medallion';
      medallion.setAttribute('aria-label', 'Open menu');
      medallion.setAttribute('aria-expanded', 'false');
      medallion.setAttribute('aria-controls', 'premiere-medallion-menu');
      const img = document.createElement('img');
      img.src = page === 'rsvp' ? 'assets/brand-mark/brand-mark-160.webp' : 'assets/brand-mark/brand-mark.png';
      img.alt = '';
      img.setAttribute('aria-hidden', 'true');
      medallion.appendChild(img);
      document.body.appendChild(medallion);
    }

    // Filmstrip drawer (Option A) — replaces radial-fan menu.
    // Page-dim backdrop + golden filmstrip ribbon sliding down from top.
    const backdrop = document.createElement('div');
    backdrop.className = 'premiere-menu-filmstrip-backdrop';
    backdrop.setAttribute('aria-hidden', 'true');
    document.body.appendChild(backdrop);

    const menu = document.createElement('div');
    menu.className = 'premiere-menu-filmstrip';
    menu.id = 'premiere-medallion-menu';
    menu.setAttribute('role', 'dialog');
    menu.setAttribute('aria-modal', 'false');
    menu.setAttribute('aria-label', 'Site navigation');

    const sprocketTop = document.createElement('div');
    sprocketTop.className = 'premiere-menu-filmstrip__sprocket premiere-menu-filmstrip__sprocket--top';
    sprocketTop.setAttribute('aria-hidden', 'true');
    menu.appendChild(sprocketTop);

    const list = document.createElement('ul');
    list.className = 'premiere-menu-filmstrip__frames';
    list.setAttribute('role', 'menu');
    NAV_ITEMS.forEach((item, i) => {
      const li = document.createElement('li');
      li.className = 'premiere-menu-filmstrip__frame';
      li.setAttribute('role', 'none');
      const a = document.createElement('a');
      a.href = item.href;
      a.className = 'premiere-menu-filmstrip__link';
      a.setAttribute('role', 'menuitem');
      if (item.page === page) a.setAttribute('aria-current', 'page');

      const num = document.createElement('span');
      num.className = 'premiere-menu-filmstrip__num';
      num.textContent = 'Scene ' + String(i + 1).padStart(2, '0');
      const title = document.createElement('span');
      title.className = 'premiere-menu-filmstrip__title';
      title.textContent = item.label;
      const tease = document.createElement('span');
      tease.className = 'premiere-menu-filmstrip__tease';
      tease.textContent = item.tease;

      a.appendChild(num);
      a.appendChild(title);
      a.appendChild(tease);
      li.appendChild(a);
      list.appendChild(li);
    });
    menu.appendChild(list);

    const sprocketBottom = document.createElement('div');
    sprocketBottom.className = 'premiere-menu-filmstrip__sprocket premiere-menu-filmstrip__sprocket--bottom';
    sprocketBottom.setAttribute('aria-hidden', 'true');
    menu.appendChild(sprocketBottom);

    document.body.appendChild(menu);

    // Mark body so CSS can hide V1 top nav + compass + edge strips
    document.body.setAttribute('data-medallion-menu', 'mounted');

    // Open / close
    function openMenu() {
      menu.classList.add('is-open');
      backdrop.classList.add('is-open');
      medallion.setAttribute('aria-expanded', 'true');
      // Focus the active page's frame if present, otherwise first frame
      requestAnimationFrame(() => {
        const active = list.querySelector('a[aria-current="page"]') || list.querySelector('a');
        if (active) active.focus({ preventScroll: false });
      });
    }
    function closeMenu() {
      menu.classList.remove('is-open');
      backdrop.classList.remove('is-open');
      medallion.setAttribute('aria-expanded', 'false');
      medallion.focus();
    }
    function toggleMenu() {
      if (menu.classList.contains('is-open')) closeMenu();
      else openMenu();
    }

    medallion.addEventListener('click', toggleMenu);
    backdrop.addEventListener('click', closeMenu);
    // Close after click on a frame so SPA-like UX works
    list.addEventListener('click', (e) => {
      if (e.target.closest('a')) closeMenu();
    });

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
  /* Inject a clear text-based site-map column into the footer.
     Solves Fritz post-staging concern: "I don't know how many pages
     there are or how to navigate." Medallion menu is visual; footer
     site-map is scannable and discoverable. */
  /* Pass 12 — Final Reel footer upgrader.
     Replaces the legacy three-column footer with the closing-credits
     scene on every page. Idempotent — checks for the .footer--final-reel
     marker before rewriting. */
  function injectFooterSiteMap() {
    const footer = document.querySelector('footer.footer');
    if (!footer) return;
    if (footer.classList.contains('footer--final-reel')) return;
    footer.classList.add('footer--final-reel');
    footer.setAttribute('data-mode', 'scene');
    footer.setAttribute('data-page-slot', 'footer-scene');
    footer.setAttribute('aria-label', 'The final reel');

    const navHTML = [
      ['index.html', 'Visit', 'home'],
      ['rsvp.html', 'RSVP', 'rsvp'],
      ['tickets.html', 'Tickets', 'tickets'],
      ['through-years.html', 'Through the Years', 'through-years'],
      ['memorial.html', 'In Memory', 'memorial'],
      ['capsule.html', 'Time Capsule', 'capsule'],
      ['playlist.html', 'Soundtrack', 'playlist']
    ].map(function (row) {
      var href = row[0], label = row[1], key = row[2];
      var cur = key === page ? ' aria-current="page"' : '';
      return '<a href="' + href + '"' + cur + '>' + label + '</a>';
    }).join(' · ');

    footer.innerHTML =
      '<div class="footer__rail footer__rail--top" aria-hidden="true"></div>' +
      '<div class="footer__inner">' +
        '<img class="footer__seal" src="assets/premiere/brand-mark-foil.png?v=2" alt="Class of \'96 + MBSH 1926-2026 commemorative seal" width="140" height="140" loading="lazy">' +
        '<p class="footer__seal-line">MBSH · 1926 — 2026</p>' +
        '<p class="footer__class">Class of 1996 · 30th Reunion</p>' +
        '<p class="footer__motto">Let us be known for our deeds.</p>' +
        '<hr class="footer__rule">' +
        '<div class="footer__credits">' +
          '<p class="footer__credits-eyebrow">— A final credit roll —</p>' +
          '<p class="footer__credits-line"><strong>Reunion Committee</strong> <a href="mailto:mbsh96reunion@gmail.com">mbsh96reunion@gmail.com</a></p>' +
          '<p class="footer__credits-line">' + navHTML + '</p>' +
          '<p class="footer__credits-line"><a href="https://miamibeachseniorhigh.net" rel="noopener">Official MBSH Site</a> · <a href="through-years.html#submit-memory">Submit a memory</a> · <a href="tickets.html#sponsor">Become a sponsor</a></p>' +
          '<p class="footer__credits-line footer__credits-line--social">Instagram &amp; Facebook coming soon — drop a note to the committee for a heads-up.</p>' +
        '</div>' +
        '<hr class="footer__rule">' +
        '<div class="footer__encore">' +
          '<p class="footer__encore-eyebrow">— Encore —</p>' +
          '<p class="footer__copyright">© 2026 MBSH Class of \'96 Reunion Committee</p>' +
          '<p class="footer__credit">Built with <a href="https://famtastic.com" rel="noopener">FAMtastic Site Studio</a></p>' +
        '</div>' +
      '</div>' +
      '<div class="footer__rail footer__rail--bottom" aria-hidden="true"></div>';
  }

  function injectOverlays() {
    // RSVP Phase 3 carries its own scene plate, dust, lamp, bleed, and light spill.
    // Skip the full-page injected overlay here so Lighthouse does not treat the
    // fixed decorative backdrop as the LCP element.
    if (page === 'rsvp') return;
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

  /* Make the hero scroll-tease (tagline + double chevron) clickable so it
     scrolls the next section into view. Snap-mandatory will then settle
     the section at the top. Keyboard accessible (Enter/Space). */
  function wireScrollTeaseClick() {
    const teasers = Array.from(document.querySelectorAll('[data-scroll-tease="true"], .scroll-teaser, .hero__scroll-tease'));
    if (!teasers.length) return;

    function resolveTarget(tease) {
      const selector = tease.getAttribute('data-scroll-target');
      if (selector) return document.querySelector(selector);
      const allSnaps = Array.from(document.querySelectorAll('.premiere-snap-target'));
      const hero = tease.closest('.hero, .experience-hero, .reel-hero, header');
      const heroIdx = allSnaps.indexOf(hero);
      return heroIdx >= 0 ? allSnaps[heroIdx + 1] : null;
    }

    function scrollToTarget(target) {
      // Use window.scrollTo instead of scrollIntoView — the latter has
      // inconsistent behavior under scroll-snap-type: y mandatory in
      // headless Chrome and some real-Chrome versions. window.scrollTo
      // with offsetTop is reliable everywhere.
      // Briefly relax snap so the smooth scroll isn't intercepted.
      if (!target) return;
      const body = document.body;
      const prevSnap = body.style.scrollSnapType;
      body.style.scrollSnapType = 'none';
      const targetY = Math.max(0, target.getBoundingClientRect().top + window.pageYOffset - 110);
      const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      window.scrollTo({ top: targetY, behavior: reduceMotion ? 'auto' : 'smooth' });
      setTimeout(() => { body.style.scrollSnapType = prevSnap; }, 900);
    }

    teasers.forEach((tease) => {
      const target = resolveTarget(tease);
      if (!target || tease.dataset.scrollTeaseWired === 'true') return;
      tease.dataset.scrollTeaseWired = 'true';
      tease.removeAttribute('aria-hidden');
      if (tease.tagName !== 'BUTTON') {
        tease.setAttribute('role', 'button');
        tease.setAttribute('tabindex', '0');
      }
      const targetLabel = target.getAttribute('aria-label') || target.id || 'next section';
      if (!tease.getAttribute('aria-label')) tease.setAttribute('aria-label', 'Scroll to ' + targetLabel);
      tease.classList.add('is-clickable');
      tease.addEventListener('click', () => scrollToTarget(target));
      tease.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          scrollToTarget(target);
        }
      });
    });
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

  /* -----------------------------------------------------------------
     PASS 9 — PROGRAM array + reel-card vocabulary + Where-Next +
     Harry-in-scene. Single source of truth for cross-page consistency.
     ----------------------------------------------------------------- */

  // PROGRAM — runtime-specific reel data used by where-next, the home
  // bulletin, and the Harry-in-scene placement table. The canonical reel
  // ORDER and reel NUMBERING live in `js/page-sequence.js` (window.PAGE_SEQUENCE);
  // this array adapts that data into the shape premiere.js already uses
  // (with .href and .runtime). If page-sequence.js failed to load we fall
  // back to the embedded copy so this file still works standalone.
  const PROGRAM_RUNTIMES = {
    home: '—', rsvp: '4 min', tickets: '6 min', 'through-years': '12 min',
    memorial: '5 min', capsule: '8 min', playlist: '∞'
  };
  const PROGRAM = (window.PAGE_SEQUENCE && Array.isArray(window.PAGE_SEQUENCE))
    ? window.PAGE_SEQUENCE.map(function (e) {
        return {
          id: e.id, page: e.page, href: e.slug, reel: e.reelRoman,
          title: e.title, usher: e.usher,
          runtime: PROGRAM_RUNTIMES[e.page] || '—'
        };
      })
    : [
        { id: 'home',     page: 'home',          href: 'index.html',         reel: 'I',    title: 'Welcome — The Premiere',     usher: 'Curtain up. Find your row, find your row of 1996.',           runtime: '—' },
        { id: 'rsvp',     page: 'rsvp',          href: 'rsvp.html',          reel: 'II',   title: 'Reserve Your Seat',          usher: "Tell us you're coming. The night unlocks once we hear from you.", runtime: '4 min' },
        { id: 'tickets',  page: 'tickets',       href: 'tickets.html',       reel: 'III',  title: 'Tickets & Sponsorship',      usher: 'Two ways in — secure a seat, or help fund the night.',  runtime: '6 min' },
        { id: 'years',    page: 'through-years', href: 'through-years.html', reel: 'IV',   title: 'Through the Years',          usher: 'One hundred years of Hi-Tides. The eras that built us.',     runtime: '12 min' },
        { id: 'memory',   page: 'memorial',      href: 'memorial.html',      reel: 'V',    title: 'In Memory',                  usher: 'Forever Hi-Tides. Names we carry with us.',                  runtime: '5 min' },
        { id: 'capsule',  page: 'capsule',       href: 'capsule.html',       reel: 'VI',   title: 'Time Capsule',               usher: "Send your younger self a note. We'll deliver on the day.",   runtime: '8 min' },
        { id: 'sound',    page: 'playlist',      href: 'playlist.html',      reel: 'VII',  title: 'The Soundtrack',             usher: 'The songs that made us who we are. Curated, embedded, alive.', runtime: '∞' },
      ];

  function programIndex(pageName) {
    const i = PROGRAM.findIndex(p => p.page === pageName);
    return i < 0 ? 0 : i;
  }
  function nextInProgram(pageName, count) {
    const start = programIndex(pageName);
    const out = [];
    for (let n = 1; n <= count; n++) {
      out.push(PROGRAM[(start + n) % PROGRAM.length]);
    }
    return out;
  }

  function renderReelCard(p, opts) {
    opts = opts || {};
    const a = document.createElement('a');
    a.className = 'reel-card' + (opts.nowPlaying ? ' reel-card--now-playing' : '');
    a.href = opts.nowPlaying ? '#' : p.href;
    if (opts.nowPlaying) { a.setAttribute('aria-current', 'page'); a.tabIndex = -1; }
    a.innerHTML =
      '<span class="reel-card__eyebrow">Reel ' + p.reel + '</span>' +
      '<h3 class="reel-card__title">' + p.title + '</h3>' +
      '<p class="reel-card__usher">' + p.usher + '</p>' +
      '<div class="reel-card__footer">' +
        '<span class="reel-card__runtime">' + p.runtime + '</span>' +
        '<span class="reel-card__cta">' + (opts.nowPlaying ? 'Now Playing' : 'Select') + '</span>' +
      '</div>';
    return a;
  }

  function buildReelRail(items, opts) {
    opts = opts || {};
    const rail = document.createElement('div');
    rail.className = 'reel-rail';
    if (opts.head) {
      const head = document.createElement('div');
      head.className = 'reel-rail__head';
      const r1 = document.createElement('span'); r1.className = 'reel-rail__head-rule'; r1.setAttribute('aria-hidden','true');
      const lbl = document.createElement('span'); lbl.textContent = opts.head;
      const r2 = document.createElement('span'); r2.className = 'reel-rail__head-rule'; r2.setAttribute('aria-hidden','true');
      const aff = document.createElement('span'); aff.className = 'reel-rail__head-affordance'; aff.textContent = 'Scroll ↔';
      head.appendChild(r1); head.appendChild(lbl); head.appendChild(r2); head.appendChild(aff);
      rail.appendChild(head);
    }
    const track = document.createElement('div');
    track.className = 'reel-rail__track';
    items.forEach(it => track.appendChild(renderReelCard(it.p, { nowPlaying: !!it.nowPlaying })));
    rail.appendChild(track);
    return rail;
  }

  /* Replace the home Bulletin list with reel-cards. */
  function fillHomeBulletin() {
    const list = document.querySelector('.program-bulletin__list');
    if (!list) return;
    const host = list.parentElement;
    list.remove();
    const rail = buildReelRail(
      PROGRAM.filter(p => p.page !== 'home').map(p => ({ p })),
      { head: 'Also on the program' }
    );
    host.appendChild(rail);
  }

  /* Inject (or upgrade) Where-Next on every page. Picks next 4 in program
     order, with current page rendered as NOW PLAYING. */
  function injectWhereNext() {
    // If a hardcoded .page-next exists (home), upgrade it in place.
    const legacy = document.querySelector('.page-next');
    let target;
    if (legacy && legacy.dataset.replaced !== 'where-next') {
      legacy.dataset.replaced = 'where-next';
      target = document.createElement('section');
      target.className = 'where-next premiere-snap-target';
      target.setAttribute('aria-label', 'Up next');
      legacy.parentNode.insertBefore(target, legacy);
    } else {
      // Inner pages: append before footer.
      const footer = document.querySelector('footer.footer, footer[data-template="footer"]');
      if (!footer) return;
      // Don't double-mount.
      if (document.querySelector('section.where-next')) return;
      target = document.createElement('section');
      target.className = 'where-next premiere-snap-target';
      target.setAttribute('aria-label', 'Up next');
      footer.parentNode.insertBefore(target, footer);
    }

    const intro = document.createElement('div');
    intro.className = 'where-next__intro';
    const eb = document.createElement('p'); eb.className = 'where-next__eyebrow'; eb.textContent = '— Up next —';
    const hd = document.createElement('h2'); hd.className = 'where-next__headline';
    const sub = document.createElement('p'); sub.className = 'where-next__sub';

    {
      // Derive Up Next entirely from the program sequence so playlist
      // wraps back to home and any future reorder is one-edit-away.
      const next = nextInProgram(page, 1)[0];
      hd.textContent = 'Reel ' + next.reel + ' — ' + next.title + '.';
      sub.textContent = next.usher;
    }
    intro.appendChild(eb); intro.appendChild(hd); intro.appendChild(sub);
    target.appendChild(intro);

    // Build the rail: current page as NOW PLAYING (when not home), then next 3.
    const items = [];
    if (page !== 'home') {
      const me = PROGRAM[programIndex(page)];
      if (me) items.push({ p: me, nowPlaying: true });
    }
    nextInProgram(page, items.length ? 3 : 4).forEach(p => items.push({ p }));

    const rail = buildReelRail(items, { head: 'Also playing' });
    target.appendChild(rail);
  }

  /* Harry-in-scene — page-aware integration character. Distinct from the
     corner .premiere-usher (which remains the chat trigger). */
  /* Pass 13 — placement rule: graphics/in-scene Harry sits LEFT of the
     scene, the chat assistant lives RIGHT. The medallion menu is
     centered-top. This keeps the page composition predictable. */
  const HARRY_SCENE_MAP = {
    'home':          { pose: '20-pointing-across.png', anchor: 'bottom-left', host: '.program-bulletin, .where-next', alt: 'Hi-Tide Harry pointing at the program' },
    'rsvp':          { pose: '12-clipboard.png',       anchor: 'bottom-left', host: 'section.rsvp-form-wrap, section[id="rsvp"], main section:nth-of-type(1)', alt: 'Hi-Tide Harry holding a clipboard' },
    'tickets':       { pose: '13-ticket-stub.png',     anchor: 'bottom-left', host: 'section.tickets, main section:nth-of-type(1)', alt: 'Hi-Tide Harry holding a ticket stub' },
    'through-years': { pose: '22-walk-frame.png',      anchor: 'bottom-left', host: 'section.timeline, main section:nth-of-type(1)', alt: 'Hi-Tide Harry walking the years' },
    'memorial':      { pose: '17-respectful.png',      anchor: 'bottom-left', host: 'section.memorial, main section:nth-of-type(1)', alt: 'Hi-Tide Harry, hat in hand' },
    'capsule':       { pose: '14-wax-stamping.png',    anchor: 'bottom-left', host: 'section.capsule, main section:nth-of-type(1)', alt: 'Hi-Tide Harry stamping the wax seal' },
    'playlist':      { pose: '16-conducting.png',      anchor: 'bottom-left', host: 'section.playlist, main section:nth-of-type(1)', alt: 'Hi-Tide Harry conducting the soundtrack' },
  };

  function injectHarryInScene() {
    const cfg = HARRY_SCENE_MAP[page];
    if (!cfg) return;
    let host = document.querySelector(cfg.host);
    if (!host) return;
    // Pass 11 — Harry refuses Sequence hosts. If the candidate host is a
    // Sequence, walk to the nearest Scene sibling (prefer next, fall back
    // prev) and re-anchor there. The billboard slide already shows the
    // page-specific pose so the Note Scene is the natural fallback.
    if (host.dataset.mode === 'sequence') {
      const all = collectPageSections();
      const idx = all.indexOf(host);
      const nextScene = all.slice(idx + 1).find(s => s.dataset.mode === 'scene');
      const prevScene = all.slice(0, idx).reverse().find(s => s.dataset.mode === 'scene');
      host = nextScene || prevScene;
      if (!host) return;
    }
    if (host.querySelector(':scope > .harry-in-scene')) return;
    if (getComputedStyle(host).position === 'static') host.style.position = 'relative';
    const img = document.createElement('img');
    img.className = 'harry-in-scene harry-in-scene--anchor-' + cfg.anchor;
    img.src = 'assets/mascot/' + cfg.pose;
    img.alt = cfg.alt;
    img.width = 200; img.height = 240;
    img.loading = 'lazy';
    host.appendChild(img);

    // Director-strip salute on home: a second Harry in the post section.
    if (page === 'home') {
      const post = document.querySelector('.director-strip');
      if (post && !post.querySelector('.harry-in-scene')) {
        if (getComputedStyle(post).position === 'static') post.style.position = 'relative';
        const salute = document.createElement('img');
        salute.className = 'harry-in-scene harry-in-scene--anchor-bottom-left';
        salute.src = 'assets/mascot/23-salute.png';
        salute.alt = 'Hi-Tide Harry saluting from the proscenium';
        salute.width = 180; salute.height = 220;
        salute.loading = 'lazy';
        post.appendChild(salute);
      }
    }
  }

  /* -----------------------------------------------------------------
     PASS 10 — Billboard slideshow + chevrons everywhere + unified
     section pull-in animation.
     ----------------------------------------------------------------- */

  function upgradeInteriorHero() {
    if (page === 'home' || !sequenceEntry) return;
    const header = document.querySelector('header.page-header');
    if (!header || header.dataset.experienceHero === '1') return;

    header.dataset.experienceHero = '1';
    header.dataset.pageSlot = 'title';
    header.dataset.mode = 'scene';
    header.dataset.height = 'large';
    header.classList.add('experience-hero', 'premiere-snap-target');
    if (sequenceEntry.heroTier) header.dataset.heroTier = sequenceEntry.heroTier;
    if (sequenceEntry.heroBridge) header.dataset.heroBridge = sequenceEntry.heroBridge;
    if (sequenceEntry.heroImage) header.style.setProperty('--experience-hero-image', 'url("' + escapeAttr(sequenceEntry.heroImage) + '")');
    header.style.setProperty('--experience-hero-mood', '"' + escapeAttr(sequenceEntry.mood || '') + '"');

    const title = header.querySelector('.page-header__title');
    const sub = header.querySelector('.page-header__sub');
    if (title && sequenceEntry.heroTitle) title.textContent = sequenceEntry.heroTitle;
    if (sub && sequenceEntry.heroSub) sub.textContent = sequenceEntry.heroSub;

    if (!header.querySelector('.experience-hero__layers')) {
      const layers = document.createElement('div');
      layers.className = 'experience-hero__layers';
      layers.setAttribute('aria-hidden', 'true');
      layers.innerHTML = '<span class="experience-hero__layer experience-hero__image"></span>' +
                         '<span class="experience-hero__layer experience-hero__set experience-hero__set--rsvp"><span class="rsvp-set__marquee"></span><span class="rsvp-set__doors"></span><span class="rsvp-set__podium"></span><span class="rsvp-set__guest-list"></span><span class="rsvp-set__seat-cards"></span><span class="rsvp-set__rope rsvp-set__rope--left"></span><span class="rsvp-set__rope rsvp-set__rope--right"></span><span class="rsvp-set__stanchion rsvp-set__stanchion--a"></span><span class="rsvp-set__stanchion rsvp-set__stanchion--b"></span><span class="rsvp-set__stanchion rsvp-set__stanchion--c"></span><span class="rsvp-set__carpet"></span></span>' +
                         '<span class="experience-hero__layer experience-hero__wash"></span>' +
                         '<span class="experience-hero__layer experience-hero__beam"></span>' +
                         '<span class="experience-hero__layer experience-hero__grain"></span>' +
                         '<span class="experience-hero__layer experience-hero__foreground"></span>' +
                         '<span class="experience-hero__layer experience-hero__bleed experience-hero__bleed--one"></span>' +
                         '<span class="experience-hero__layer experience-hero__bleed experience-hero__bleed--two"></span>' +
                         '<span class="experience-hero__layer experience-hero__bulbs"></span>';
      header.insertBefore(layers, header.firstChild);
    }

    if ((sequenceEntry.heroHarryPhoto || sequenceEntry.heroHarry) && !header.querySelector('.experience-hero__harry')) {
      const harry = document.createElement('img');
      harry.className = 'experience-hero__harry';
      harry.src = sequenceEntry.heroHarryPhoto || ('assets/mascot/' + sequenceEntry.heroHarry);
      harry.alt = sequenceEntry.heroHarryAlt || 'Hi-Tide Harry in the scene';
      harry.width = 360;
      harry.height = 420;
      harry.loading = 'eager';
      header.appendChild(harry);
    }

    const back = header.querySelector('.page-header__back');
    const content = document.createElement('div');
    content.className = 'experience-hero__content';
    [...header.children].forEach(child => {
      if (child.classList && child.classList.contains('experience-hero__layers')) return;
      if (child.classList && child.classList.contains('experience-hero__harry')) return;
      if (child === content) return;
      content.appendChild(child);
    });
    if (!content.parentNode) header.appendChild(content);
    if (back) back.classList.add('experience-hero__back');

    if (!content.querySelector('.experience-hero__kicker')) {
      const kicker = document.createElement('p');
      kicker.className = 'experience-hero__kicker';
      kicker.textContent = sequenceEntry.heroKicker || sequenceEntry.sceneLocation || '';
      const titleNode = content.querySelector('.page-header__title');
      if (titleNode) content.insertBefore(kicker, titleNode);
      else content.appendChild(kicker);
    }

    if (sequenceEntry.heroAction && !content.querySelector('.experience-hero__action')) {
      const action = document.createElement('p');
      action.className = 'experience-hero__action';
      action.textContent = sequenceEntry.heroAction;
      content.appendChild(action);
    }

    if (page === 'rsvp' && !header.querySelector('.experience-hero__chevron')) {
      const chevron = document.createElement('button');
      chevron.className = 'experience-hero__chevron scroll-teaser';
      chevron.type = 'button';
      chevron.dataset.scrollTarget = '#rsvp-form';
      chevron.dataset.scrollTease = 'true';
      chevron.setAttribute('aria-label', 'Continue to RSVP form');
      chevron.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" /></svg>';
      header.appendChild(chevron);
    }

    if (page !== 'rsvp' && !document.querySelector('.scene-marker-marquee[data-page-slot="scene-marker"]')) {
      const marker = document.createElement('section');
      marker.className = 'scene-marker-marquee premiere-snap-target';
      if (sequenceEntry.heroBridge) marker.dataset.heroBridge = sequenceEntry.heroBridge;
      marker.dataset.pageSlot = 'scene-marker';
      marker.dataset.mode = 'tease';
      marker.dataset.height = 'third';
      marker.setAttribute('aria-label', 'Scene marker');
      marker.innerHTML = '<div class="scene-marker-marquee__rail" aria-hidden="true"></div>' +
                         '<p class="scene-marker-marquee__num">SCENE ' + escapeAttr(sequenceEntry.sceneNumber || sequenceEntry.reelRoman) + '</p>' +
                         '<h2 class="scene-marker-marquee__title">' + escapeAttr(sequenceEntry.sceneTitle || sequenceEntry.title) + '</h2>' +
                         '<div class="scene-marker-marquee__rail" aria-hidden="true"></div>';
      header.parentNode.insertBefore(marker, header.nextSibling);
    }
  }

  const BILLBOARD = {
    home: [
      {
        eyebrow: 'A note from your usher',
        kind: 'welcome',
        harry: '18-presenting.png',
        line: "Welcome inside, Hi-Tide. I'm <em>Harry</em> — your usher for the evening.",
        beats: [
          'Tap the chevron at the bottom of any scene to drop into the next.',
          'The medallion in the top corner is the <em>Compass</em> — every page in one tap.',
          "Tap me any time. Questions about the night, or a note for the committee — I'll carry it."
        ],
        sign: '— Harry'
      },
      {
        eyebrow: "Tonight's program",
        kind: 'headline',
        harry: '20-pointing-across.png',
        headline: 'Seven scenes.<br>One night.',
        sub: 'Every page is one controlled scene in the reunion movie. Pick a door, or keep tapping the chevron and follow the program.'
      },
      {
        eyebrow: 'The night',
        kind: 'headline',
        harry: '23-salute.png',
        headline: 'October–November 2026.<br><span class="billboard__accent">Black-tie cocktail.</span>',
        sub: 'Miami Beach. The night unlocks once we hear from you.'
      }
    ],
    rsvp: [
      { eyebrow: 'A note from your usher', kind: 'welcome', harry: '12-clipboard.png',
        line: "Take your seat, Hi-Tide. The night unlocks the moment you tell us you're coming.",
        beats: [
          'Fill the form below — name, plus-one, dietary notes.',
          'You can update later if anything changes.',
          'Tap me if a field gets weird or you want a hand.'
        ], sign: '— Harry' },
      { eyebrow: 'Save the date', kind: 'headline', harry: '02-thumbs-up.png',
        headline: 'October–November 2026.', sub: 'Black-tie cocktail. Miami Beach. The full schedule lands once tickets open.' }
    ],
    tickets: [
      { eyebrow: 'A note from your usher', kind: 'welcome', harry: '13-ticket-stub.png',
        line: 'Two ways in tonight, Hi-Tide — secure a seat, or help fund the night.',
        beats: [
          'Tier badges below show what each level unlocks.',
          'Patrons get on-site recognition + the patron-row at dinner.',
          "Got a company that wants to put their name on it? I'll point them my way."
        ], sign: '— Harry' },
      { eyebrow: 'Patrons of the evening', kind: 'headline', harry: '04-excited-cheer.png',
        headline: 'Studio · Director · Producer · Patron.', sub: 'Pick a tier. Or pick the seat. Either reels the room.' }
    ],
    'through-years': [
      { eyebrow: 'A note from your usher', kind: 'welcome', harry: '22-walk-frame.png',
        line: 'The projector is warming up, Hi-Tide. This room is for the photos, programs, hallways, and stories that made us.',
        beats: [
          'The full archive is still being cut with the committee.',
          "1996 is the anchor — send the story only you remember.",
          'Add a memory at the end — we read every one.'
        ], sign: '— Harry' },
      { eyebrow: 'The reel rolls', kind: 'headline', harry: '20-pointing-across.png',
        headline: '1926 → 2026.', sub: 'The memory reel begins with what we have now and grows as the committee archive lands.' }
    ],
    memorial: [
      { eyebrow: 'A note from your usher', kind: 'welcome', harry: '17-respectful.png',
        line: 'Forever Hi-Tides. Names we carry with us tonight.',
        beats: [
          'This scene moves slower on purpose — give every name the beat.',
          "If someone is missing, use the email here so the committee can verify with care.",
          'Hat off, hand on heart, then we keep walking together.'
        ], sign: '— Harry' },
      { eyebrow: 'In Memoriam', kind: 'headline', harry: '17-respectful.png',
        headline: 'Forever Hi-Tides.', sub: 'Once a Hi-Tide, always a Hi-Tide.' }
    ],
    capsule: [
      { eyebrow: 'A note from your usher', kind: 'welcome', harry: '14-wax-stamping.png',
        line: 'Send your younger self a note. We seal it tonight, deliver it on the day.',
        beats: [
          'Write what 17-year-old you should know now.',
          "We'll wax-seal it on submit — sealed, not sent yet.",
          'Delivered when the reunion date is locked. No spoilers from me.'
        ], sign: '— Harry' },
      { eyebrow: 'The promise', kind: 'headline', harry: '14-wax-stamping.png',
        headline: 'Seal it for reunion night.', sub: "Date locks when the committee locks the room; the promise is the same." }
    ],
    playlist: [
      { eyebrow: 'A note from your usher', kind: 'welcome', harry: '16-conducting.png',
        line: 'The songs that made us who we are. Curated, embedded, alive.',
        beats: [
          'The Spotify embed turns on when the final playlist ID is ready.',
          'Got a song we missed? Suggest it below and the committee can add it.',
          'Until the embed is live, the starter tracklist keeps the room moving.'
        ], sign: '— Harry' },
      { eyebrow: 'The encore', kind: 'headline', harry: '16-conducting.png',
        headline: 'Press play.', sub: 'The night has a soundtrack. We were there for every song.' }
    ]
  };

  function buildSlide(s) {
    const slide = document.createElement('div');
    slide.className = 'billboard__slide billboard__slide--' + (s.kind || 'welcome');
    let inner = '';
    inner += '<p class="billboard__eyebrow">— ' + s.eyebrow + ' —</p>';
    if (s.harry) {
      inner += '<img class="billboard__harry" src="assets/mascot/' + s.harry + '" alt="Hi-Tide Harry" loading="lazy" width="160" height="200">';
    }
    if (s.kind === 'welcome') {
      inner += '<p class="billboard__line">' + s.line + '</p>';
      if (s.beats && s.beats.length) {
        inner += '<ul class="billboard__beats">';
        const numerals = ['i','ii','iii','iv','v'];
        s.beats.forEach((b, i) => {
          inner += '<li><span class="billboard__beat-num">' + (numerals[i]||'') + '.</span> ' + b + '</li>';
        });
        inner += '</ul>';
      }
      if (s.sign) inner += '<p class="billboard__sign">' + s.sign + '</p>';
    } else if (s.kind === 'headline') {
      if (s.headline) inner += '<h2 class="billboard__headline">' + s.headline + '</h2>';
      if (s.sub)      inner += '<p class="billboard__sub">' + s.sub + '</p>';
    }
    slide.innerHTML = inner;
    return slide;
  }

  function mountBillboard(host, slides) {
    if (!host || !slides || !slides.length) return;
    if (host.dataset.billboardMounted === '1') return;
    host.dataset.billboardMounted = '1';

    const stage = document.createElement('div');
    stage.className = 'billboard__stage';
    slides.forEach((s, i) => {
      const el = buildSlide(s);
      if (i === 0) el.classList.add('is-current');
      stage.appendChild(el);
    });
    host.appendChild(stage);

    if (slides.length > 1) {
      const dots = document.createElement('div');
      dots.className = 'billboard__dots';
      dots.setAttribute('role', 'tablist');
      slides.forEach((_, i) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'billboard__dot' + (i === 0 ? ' is-current' : '');
        b.setAttribute('aria-label', 'Slide ' + (i+1) + ' of ' + slides.length);
        b.addEventListener('click', () => go(i, true));
        dots.appendChild(b);
      });
      host.appendChild(dots);

      let current = 0;
      let timer = null;
      const ADVANCE_MS = 7000;

      function go(i, manual) {
        if (i === current) return;
        const stageSlides = stage.children;
        stageSlides[current].classList.remove('is-current');
        stageSlides[current].classList.add('is-leaving');
        stageSlides[i].classList.remove('is-leaving');
        stageSlides[i].classList.add('is-current');
        setTimeout(() => stageSlides[(current)].classList.remove('is-leaving'), 800);
        [...dots.children].forEach((d, idx) => d.classList.toggle('is-current', idx === i));
        current = i;
        if (manual) restart();
      }
      function tick() { go((current + 1) % slides.length, false); }
      function start() { if (!reduceMotion) timer = setInterval(tick, ADVANCE_MS); }
      function restart() { if (timer) clearInterval(timer); start(); }
      host.addEventListener('mouseenter', () => { if (timer) clearInterval(timer); });
      host.addEventListener('mouseleave', () => restart());
      start();
    }
  }


  function buildInfoCompanion(entry) {
    if (!entry || !entry.companion) return null;
    const c = entry.companion;
    const panel = document.createElement('aside');
    panel.className = 'info-companion info-companion--' + (c.kind || 'cue');
    panel.setAttribute('aria-label', c.eyebrow || 'Scene cue');
    panel.innerHTML =
      '<p class="info-companion__eyebrow">' + escapeAttr(c.eyebrow || 'Scene cue') + '</p>' +
      '<div class="info-companion__prop" aria-hidden="true"><span></span></div>' +
      '<h3 class="info-companion__headline">' + escapeAttr(c.headline || '') + '</h3>' +
      '<p class="info-companion__sub">' + escapeAttr(c.sub || '') + '</p>' +
      '<p class="info-companion__stat">' + escapeAttr(c.stat || '') + '</p>';
    return panel;
  }

  function mountInfoCompanion(note, entry) {
    if (!note || note.querySelector('.info-companion')) return;
    const companion = buildInfoCompanion(entry);
    if (!companion) return;
    const wrap = document.createElement('div');
    wrap.className = 'page-note__duo';
    const stage = note.querySelector('.billboard__stage');
    const dots = note.querySelector('.billboard__dots');
    if (stage) {
      note.insertBefore(wrap, stage);
      wrap.appendChild(stage);
      wrap.appendChild(companion);
      if (dots) wrap.appendChild(dots);
    } else {
      note.appendChild(wrap);
      wrap.appendChild(companion);
    }
  }

  function upgradeFormsForCinema() {
    const forms = document.querySelectorAll('.rsvp-form, .sponsor-form, .memory-form, .capsule-form, .playlist-suggest, .chatbot__input');
    forms.forEach(function (form) {
      form.classList.add('premiere-form-wow');
      if (!form.querySelector(':scope > .premiere-form-wow__rail')) {
        const rail = document.createElement('div');
        rail.className = 'premiere-form-wow__rail';
        rail.setAttribute('aria-hidden', 'true');
        form.insertBefore(rail, form.firstChild);
      }
      const submit = form.querySelector('button[type="submit"]');
      if (submit && !submit.querySelector('.premiere-submit-cue')) {
        const cue = document.createElement('span');
        cue.className = 'premiere-submit-cue';
        cue.setAttribute('aria-hidden', 'true');
        cue.textContent = '★';
        submit.appendChild(cue);
      }
    });
  }

  function mountAllBillboards() {
    // Home billboard already has a host element with data-billboard="home".
    document.querySelectorAll('[data-billboard]').forEach(host => {
      const key = host.dataset.billboard;
      if (BILLBOARD[key]) mountBillboard(host, BILLBOARD[key]);
    });
    // Inner pages — inject the configurable Note from Usher directly after
    // the mini-marquee scene marker. It is the info panel, not decoration.
    if (page !== 'home' && page !== 'rsvp' && BILLBOARD[page] && !document.querySelector('.page-note')) {
      const anchor = document.querySelector('.scene-marker-marquee') || document.querySelector('header.experience-hero') || document.querySelector('header.page-header');
      if (anchor && anchor.parentNode) {
        const note = document.createElement('section');
        note.className = 'page-note billboard premiere-snap-target';
        note.setAttribute('aria-label', 'A note from your usher');
        note.dataset.pageSlot = 'note';
        note.dataset.mode = 'scene';
        note.dataset.height = 'large';
        note.dataset.billboard = page;
        const slate = document.createElement('div');
        slate.className = 'page-note__slate';
        slate.innerHTML = '<span class="page-note__slate-text">' + scriptSceneText(sequenceEntry) + '</span>' +
                          '<span class="page-note__rec" aria-hidden="true"><span class="page-note__rec-dot"></span>Info</span>';
        note.appendChild(slate);
        anchor.parentNode.insertBefore(note, anchor.nextSibling);
        mountBillboard(note, BILLBOARD[page]);
        mountInfoCompanion(note, sequenceEntry);
      }
    }
  }

  /* Section chevrons — down (next) on every snap section, up (prev) on
     every snap section except the first. Click triggers a smooth scroll
     with snap temporarily disabled, matching the home hero pattern. */
  /* Pass 11 — Section archetype tagger.
     Walks every top-level section, classifies as scene|sequence|tease,
     and writes data-mode. Heuristic + page-specific overrides. */
  const SECTION_MODE_OVERRIDES = {
    'rsvp': {
      'Countdown to reunion':         'scene',
      'rsvp':                          'sequence',
      'What to expect that night':    'sequence',
      'Why now':                      'tease'
    },
    'tickets': {
      'Ticket tiers':                 'sequence',
      'Sponsorship tiers':            'sequence',
      'Become a patron':              'sequence',
      'Why it matters':               'tease'
    },
    'through-years': {
      'Through-the-Years overview':   'scene',
      'Submit a memory':              'sequence'
    },
    'memorial': {
      'In memory of':                 'scene',
      'Add a name':                   'tease',
      'At the reunion':               'tease'
    },
    'capsule': {
      'Time capsule form':            'sequence',
      'What to write':                'sequence',
      'The promise':                  'tease'
    },
    'playlist': {
      "Class of '96 Spotify playlist": 'sequence',
      'Suggest a track':              'sequence',
      'About the soundtrack':         'sequence'
    }
  };

  function tagSectionModes() {
    const overrides = SECTION_MODE_OVERRIDES[page] || {};
    const allSections = collectPageSections();
    allSections.forEach(s => {
      if (s.dataset.mode) return; // explicit attr wins
      const label = (s.getAttribute('aria-label') || s.id || '').trim();
      // 1. JS-injected sections from earlier passes are always Scenes
      if (s.classList.contains('hero')) { s.dataset.mode = 'scene'; return; }
      if (s.classList.contains('billboard') || s.dataset.pageSlot === 'note') { s.dataset.mode = 'scene'; return; }
      if (s.classList.contains('where-next')) { s.dataset.mode = 'scene'; return; }
      if (s.classList.contains('director-strip') || s.dataset.pageSlot === 'post') { s.dataset.mode = 'scene'; return; }
      if (s.classList.contains('program-bulletin') || s.dataset.pageSlot === 'main') { s.dataset.mode = 'scene'; return; }
      // 2. Per-page override map
      if (overrides[label]) { s.dataset.mode = overrides[label]; return; }
      // 3. Heuristics: contains form → sequence; tall → sequence; else scene
      if (s.querySelector('form')) { s.dataset.mode = 'sequence'; return; }
      const h = s.getBoundingClientRect().height;
      if (h > window.innerHeight * 1.05) { s.dataset.mode = 'sequence'; return; }
      if (h < window.innerHeight * 0.7)  { s.dataset.mode = 'tease'; return; }
      s.dataset.mode = 'scene';
    });
  }


  const PAGE_SLOT_OVERRIDES = {
    rsvp: {
      'Countdown to reunion': ['pre', 'third'],
      'rsvp': ['main', 'content'],
      'What to expect that night': ['post', 'large'],
      'Why now': ['post', 'half']
    },
    tickets: {
      'Ticket tiers': ['main', 'content'],
      'Sponsorship tiers': ['main', 'content'],
      'Become a patron': ['main', 'content'],
      'Why it matters': ['post', 'half']
    },
    'through-years': {
      'Through-the-Years overview': ['pre', 'large'],
      'Submit a memory': ['main', 'content']
    },
    memorial: {
      'In memory of': ['main', 'large'],
      'Add a name': ['post', 'half'],
      'At the reunion': ['post', 'half']
    },
    capsule: {
      'Time capsule form': ['main', 'content'],
      'What to write': ['post', 'large'],
      'The promise': ['post', 'half']
    },
    playlist: {
      "Class of '96 Spotify playlist": ['main', 'content'],
      'Suggest a track': ['main', 'content'],
      'About the soundtrack': ['post', 'large']
    }
  };

  function assignPageSlots() {
    const overrides = PAGE_SLOT_OVERRIDES[page] || {};
    collectPageSections().forEach(s => {
      if (s.dataset.pageSlot) return;
      if (s.classList.contains('where-next')) { s.dataset.pageSlot = 'where-next'; s.dataset.height = 'large'; return; }
      const label = (s.getAttribute('aria-label') || s.id || '').trim();
      const spec = overrides[label];
      if (spec) {
        s.dataset.pageSlot = spec[0];
        s.dataset.height = spec[1];
      }
    });
  }

  function collectPageSections() {
    const snap = [...document.querySelectorAll('section.premiere-snap-target')];
    const labeled = [...document.querySelectorAll('body > section[aria-label], main > section[aria-label], body > section[id], main > section[id]')]
      .filter(s => !s.closest('footer') && !s.classList.contains('compass-nav'));
    /* Pass 12 — the final reel footer participates in the archetype
       system. Treat it as the last "section" in the page so chevrons
       wire it up correctly. */
    const finalFooter = document.querySelector('footer.footer--final-reel');
    const merged = new Set([...snap, ...labeled]);
    if (finalFooter) merged.add(finalFooter);
    return [...merged].sort((a, b) =>
      (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING) ? -1 : 1
    );
  }

  function injectSectionChevrons() {
    let sections = collectPageSections();
    if (sections.length < 2) return;

    sections.forEach((sec, i) => {
      // Skip if hero already has its own chevron
      const isHero = sec.classList.contains('hero');
      const mode = sec.dataset.mode;
      // Pass 11: skip down-chevron on Sequences. Sequences are content the
      // user reads/scrolls — a floating mid-form chevron is noise.
      const skipDown = mode === 'sequence';
      if (!isHero && !skipDown && !sec.querySelector(':scope > .section-chevron--down') && i < sections.length - 1) {
        const down = document.createElement('button');
        down.type = 'button';
        down.className = 'section-chevron section-chevron--down';
        down.setAttribute('aria-label', 'Next scene');
        down.innerHTML = '<svg viewBox="0 0 24 22" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 6 12 14 20 6"/><polyline points="4 12 12 20 20 12"/></svg>';
        down.addEventListener('click', () => scrollToSection(sections[i + 1]));
        if (getComputedStyle(sec).position === 'static') sec.style.position = 'relative';
        sec.appendChild(down);
      }
      if (i > 0 && !sec.querySelector(':scope > .section-chevron--up')) {
        const up = document.createElement('button');
        up.type = 'button';
        up.className = 'section-chevron section-chevron--up';
        up.setAttribute('aria-label', 'Previous scene');
        up.innerHTML = '<svg viewBox="0 0 24 22" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 16 12 8 20 16"/><polyline points="4 10 12 2 20 10"/></svg>';
        up.addEventListener('click', () => scrollToSection(sections[i - 1]));
        if (getComputedStyle(sec).position === 'static') sec.style.position = 'relative';
        sec.appendChild(up);
      }
    });
  }

  function scrollToSection(target) {
    if (!target) return;
    const wasSnap = document.body.dataset.snap === 'on';
    if (wasSnap) document.body.removeAttribute('data-snap');
    const top = target.getBoundingClientRect().top + window.scrollY;
    window.scrollTo({ top, behavior: reduceMotion ? 'auto' : 'smooth' });
    if (wasSnap) setTimeout(() => { document.body.setAttribute('data-snap', 'on'); }, 900);
    target.classList.add('is-arriving');
    setTimeout(() => target.classList.remove('is-arriving'), 1100);
  }

  /* Unified pull-in arrival — same animation whether the user clicked a
     chevron or just scrolled naturally. Watches every snap section and
     toggles `.is-arriving` when it crosses the viewport center. */
  function wireSectionArrival() {
    if (reduceMotion) return;
    const snap = [...document.querySelectorAll('section.premiere-snap-target')];
    const labeled = [...document.querySelectorAll('body > section[aria-label], main > section[aria-label]')]
      .filter(s => !s.closest('footer') && !s.classList.contains('compass-nav'));
    const sections = [...new Set([...snap, ...labeled])];
    if (!sections.length) return;
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting && e.intersectionRatio > 0.55) {
          if (!e.target.classList.contains('is-arrived')) {
            e.target.classList.add('is-arriving');
            setTimeout(() => {
              e.target.classList.remove('is-arriving');
              e.target.classList.add('is-arrived');
            }, 700);
          }
        }
      });
    }, { threshold: [0.55, 0.7] });
    sections.forEach(s => io.observe(s));
  }

  function wireOpeningChatDeferral() {
    const opening = document.querySelector('.experience-hero');
    const marker = document.querySelector('.scene-marker-marquee');
    if (!opening || !marker) return;
    const update = () => {
      const threshold = opening.getBoundingClientRect().height + marker.getBoundingClientRect().height - 16;
      document.body.classList.toggle('is-past-opening', window.scrollY > threshold);
    };
    update();
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
  }

  function init() {
    try { injectOverlays(); }       catch (e) { console.warn('[premiere] fx', e); }
    try { injectFooterSiteMap(); }  catch (e) { console.warn('[premiere] footer-nav', e); }
    try { deferHeroVideo(); }       catch (e) { console.warn('[premiere] video-defer', e); }
    try { wireScrollTeaseClick(); } catch (e) { console.warn('[premiere] scroll-tease', e); }
    try { mountMedallionMenu(); }   catch (e) { console.warn('[premiere] medallion', e); }
    /* injectNav + wireNavScrollHide intentionally not called when medallion mounts;
       CSS hides them via [data-medallion-menu="mounted"]. Kept in source for one
       cycle so rollback is clean. */
    try { attachSnapIn(); }         catch (e) { console.warn('[premiere] snap-in', e); }
    try { wireSnapBounce(); }       catch (e) { console.warn('[premiere] snap-bounce', e); }
    try { curtainRise(); }          catch (e) { console.warn('[premiere] curtain', e); }
    /* Pass 12 — mountUsher() retired. The corner-floating .premiere-usher
       button duplicated .chatbot__bubble in the same corner. The
       .harry-in-scene per-section system + the billboard's slide-1 welcome
       cover the role. CSS hides any residual .premiere-usher node. */
    try { activateStoryScene(); }   catch (e) { console.warn('[premiere] story', e); }
    try { activatePreviewCards(); } catch (e) { console.warn('[premiere] previews', e); }
    try { activateMemorial(); }     catch (e) { console.warn('[premiere] memorial', e); }
    try { activateRSVPStub(); }     catch (e) { console.warn('[premiere] rsvp', e); }
    try { activateCapsule(); }      catch (e) { console.warn('[premiere] capsule', e); }
    try { activateGenericFades(); } catch (e) { console.warn('[premiere] fades', e); }
    try { wireHarryVocabulary(); }  catch (e) { console.warn('[premiere] harry-vocab', e); }
    try { fillHomeBulletin(); }     catch (e) { console.warn('[premiere] bulletin', e); }
    try { upgradeInteriorHero(); }  catch (e) { console.warn('[premiere] hero-upgrade', e); }
    try { wireScrollTeaseClick(); } catch (e) { console.warn('[premiere] scroll-tease-hero', e); }
    try { mountAllBillboards(); }   catch (e) { console.warn('[premiere] billboard', e); }
    try { upgradeFormsForCinema(); } catch (e) { console.warn('[premiere] form-wow', e); }
    try { injectWhereNext(); }      catch (e) { console.warn('[premiere] where-next', e); }
    /* Pass 11 — section archetype tagger MUST run before chevrons +
       Harry-in-scene so they can read data-mode. */
    try { tagSectionModes(); }      catch (e) { console.warn('[premiere] tag-modes', e); }
    try { assignPageSlots(); }      catch (e) { console.warn('[premiere] page-slots', e); }
    try { injectHarryInScene(); }   catch (e) { console.warn('[premiere] harry-scene', e); }
    try { injectSectionChevrons();} catch (e) { console.warn('[premiere] chevrons', e); }
    try { wireSectionArrival(); }   catch (e) { console.warn('[premiere] arrival', e); }
    try { wireOpeningChatDeferral(); } catch (e) { console.warn('[premiere] opening-chat', e); }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else { init(); }
})();
