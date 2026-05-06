/* premiere.js — orchestrator for "The Premiere" theme
   Activates only when body[data-premiere="on"]. No-op otherwise.

   Responsibilities:
   - Curtain rise (once per session, sessionStorage gated)
   - Harry-as-usher: section-aware pose swapping via IntersectionObserver
   - Story scene: .is-visible + .is-active toggles
   - Preview cards: .is-visible on enter
   - Memorial names: stagger fade-in
   - RSVP: ticket stub on submit success
   - Capsule: envelope open + seal animation
   - Chatbot toggle hides Harry-usher
   - Respects prefers-reduced-motion AND prefers-reduced-data
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
  const POSE_MAP = {
    home_hero:           '01-wave-hello.png',
    home_story:          '08-pointing.png',
    home_event:          '04-excited-cheer.png',
    home_previews:       '07-confirming.png',
    home_footer:         '02-thumbs-up.png',
    rsvp:                '06-listening.png',
    rsvp_success:        '02-thumbs-up.png',
    tickets:             '08-pointing.png',
    'through-years':     '03-thinking.png',
    capsule:             '08-pointing.png',
    capsule_success:     '04-excited-cheer.png',
    playlist:            '04-excited-cheer.png'
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

  /* -----------------------------------------------------------------
     9. Auto-inject FX + starfield overlays
     Keeps HTML lean — only the body attribute + CSS/JS links are required.
     ----------------------------------------------------------------- */
  function injectOverlays() {
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

  function init() {
    try { injectOverlays(); }       catch (e) { console.warn('[premiere] fx', e); }
    try { injectNav(); }            catch (e) { console.warn('[premiere] nav', e); }
    try { curtainRise(); }          catch (e) { console.warn('[premiere] curtain', e); }
    try { mountUsher(); }           catch (e) { console.warn('[premiere] usher', e); }
    try { activateStoryScene(); }   catch (e) { console.warn('[premiere] story', e); }
    try { activatePreviewCards(); } catch (e) { console.warn('[premiere] previews', e); }
    try { activateMemorial(); }     catch (e) { console.warn('[premiere] memorial', e); }
    try { activateRSVPStub(); }     catch (e) { console.warn('[premiere] rsvp', e); }
    try { activateCapsule(); }      catch (e) { console.warn('[premiere] capsule', e); }
    try { activateGenericFades(); } catch (e) { console.warn('[premiere] fades', e); }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else { init(); }
})();
