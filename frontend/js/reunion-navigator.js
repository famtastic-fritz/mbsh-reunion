/*
 * MBSH Reunion Navigator v2
 *
 * A deliberately small, non-mutating orientation layer for the public cinema,
 * verified attendee portal, and capability-filtered Committee Desk. It points
 * people at real navigation controls only; it never submits a form, opens a
 * record, reveals a ticket credential, or simulates a completed action.
 */
(function (window, document) {
  'use strict';

  const VERSION = '2';
  const ACTIVE_CLASS = 'mbsh-guide-target';
  const instances = new Map();

  const guides = {
    public: {
      label: 'How the reunion works',
      eyebrow: 'Your reunion preview',
      title: 'A quick way in',
      launcher: 'How it works',
      steps: [
        {
          key: 'welcome',
          title: 'Start with the real reunion plan',
          body: 'Browse the schedule, reunion stories, dinner information, and the paths that matter most before you decide what to do next.',
          target: '.cinema-menu-toggle',
          action: { href: '/index.html', label: 'Open reunion home' },
        },
        {
          key: 'rsvp',
          title: 'Reserve your place',
          body: 'The RSVP journey is where you tell the committee whether you are coming and what they need to plan for you.',
          target: 'a[href="/rsvp.html"]',
          action: { href: '/rsvp.html', label: 'Open RSVP' },
        },
        {
          key: 'portal',
          title: 'Keep your reunion in one private place',
          body: 'Create or enter your attendee account to manage RSVP and dinner details, your ticket, memories, messages, and update preferences.',
          target: '.cinema-alumni-login',
          action: { href: '/portal/register', label: 'Create attendee account' },
        },
        {
          key: 'memory',
          title: 'Bring a piece of 1996 with you',
          body: 'You can share a memory for private committee review. Nothing becomes public automatically.',
          target: 'a[href="/through-years.html"]',
          action: { href: '/through-years.html#submit-memory', label: 'Explore memories' },
        },
      ],
    },
    member: {
      label: 'My Reunion Guide',
      eyebrow: 'Your private reunion space',
      title: 'Find your next scene',
      launcher: 'Guide me',
      steps: [
        {
          key: 'dashboard',
          title: 'Begin on your dashboard',
          body: 'Your next action, event guide, ticket status, and personal reunion tools live here. Nothing shown here changes your record by itself.',
          target: 'a[data-route="home"]',
          action: { selector: 'a[data-route="home"]', label: 'Open dashboard' },
        },
        {
          key: 'rsvp',
          title: 'Confirm RSVP, dinner, and access needs',
          body: 'Use RSVP & dinner to update the details the committee needs. Saving remains a deliberate action on the real form.',
          target: 'a[data-route="rsvp"]',
          action: { selector: 'a[data-route="rsvp"]', label: 'Open RSVP & dinner' },
        },
        {
          key: 'ticket',
          title: 'Keep your ticket private',
          body: 'The wallet shows your reunion pass. Do not share a live check-in credential; it remains protected inside your authenticated portal.',
          target: 'a[data-route="ticket"]',
          action: { selector: 'a[data-route="ticket"]', label: 'Open my ticket' },
        },
        {
          key: 'memories',
          title: 'Add your chapter',
          body: 'Photos and stories begin as private submissions. The committee reviews them before any approved derivative can be shared publicly.',
          target: 'a[data-route="memories"]',
          action: { selector: 'a[data-route="memories"]', label: 'Open my memories' },
        },
        {
          key: 'notifications',
          title: 'Choose what reaches you',
          body: 'Event-critical service messages stay separate from optional reunion stories and promotions.',
          target: 'a[data-route="notifications"]',
          action: { selector: 'a[data-route="notifications"]', label: 'Open notifications' },
        },
      ],
    },
    committee: {
      label: 'Committee Desk Tour',
      eyebrow: 'Permission-controlled operations',
      title: 'Run the reunion with confidence',
      launcher: 'Start tour',
      steps: [
        {
          key: 'command',
          title: 'Begin with what needs attention',
          body: 'The Command center is a signal board, not a row of dead totals. Open the related workspace to inspect the source record, latest status, and next accountable action.',
          target: '[data-section="command"]',
          action: { selector: '[data-section="command"]', label: 'Open Command center' },
        },
        {
          key: 'people',
          capability: 'view_roster',
          title: 'Know which people count you are reading',
          body: 'People & RSVP reconciles current portal accounts, production RSVPs, and the legacy survey audience. Historical rows can contain duplicate email addresses, so audience totals and unique people are not always the same number.',
          target: '[data-section="people"]',
          action: { selector: '[data-section="people"]', label: 'Open People & RSVP' },
        },
        {
          key: 'dinner',
          capability: 'view_menu',
          title: 'Protect dinner and dietary details',
          body: 'Dinner & dietary shows production meal submissions linked by normalized email. Use the newest known selection, keep allergies and accessibility notes private, and investigate an empty result instead of assuming nobody responded.',
          target: '[data-section="dinner"]',
          action: { selector: '[data-section="dinner"]', label: 'Open Dinner & dietary' },
        },
        {
          key: 'review',
          capability: 'moderate_media',
          title: 'Protect every memory before publication',
          body: 'Review consent, safety, context, and rights in the actual approval queue. The guide never approves a submission for you.',
          target: '[data-section="review"]',
          action: { selector: '[data-section="review"]', label: 'Open Media approvals' },
        },
        {
          key: 'messages',
          capability: 'view_inbox',
          title: 'Keep conversations accountable',
          body: 'Use Messages for attendee timelines and accountable replies. Shay’s temporary Stripe setup room securely routes general setup status and questions to Fritz; it must never collect passwords, bank numbers, SSNs, API keys, card details, or verification codes.',
          target: '[data-section="messages"]',
          action: { selector: '[data-section="messages"]', label: 'Open Messages' },
        },
        {
          key: 'tickets',
          capability: 'manage_tickets',
          title: 'A saved request is not a paid ticket',
          body: 'The site can capture a ticket request while Stripe is unfinished, but it remains pending. Confirm payment authority before issuing admission, and use Sample Ticket Studio only for artwork previews—not entry.',
          target: '[data-section="tickets"]',
          action: { selector: '[data-section="tickets"]', label: 'Open Tickets & check-in' },
        },
        {
          key: 'delivery',
          roles: ['site_owner'],
          title: 'Operate email as a real campaign lifecycle',
          body: 'Email & workers separates drafted, accepted, delivered, failed, retried, suppressed, and duplicate-protected sends. Welcome campaigns must deduplicate legacy rows by normalized email and honor preferences or unsubscribe requests.',
          target: '[data-section="delivery"]',
          action: { selector: '[data-section="delivery"]', label: 'Open Email & workers' },
        },
      ],
    },
  };

  function reducedMotion() {
    return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  function saveData() {
    return Boolean(window.navigator && window.navigator.connection && window.navigator.connection.saveData);
  }

  function track(action, detail) {
    const payload = {
      guide_id: 'mbsh-reunion-navigator',
      guide_version: VERSION,
      role_bucket: detail.role,
      step_key: detail.step && detail.step.key,
      route_key: document.body.dataset.page || document.body.dataset.workspace || 'portal',
      trigger: detail.trigger || 'guide',
      viewport_bucket: window.innerWidth <= 480 ? 'phone' : (window.innerWidth <= 900 ? 'tablet' : 'desktop'),
      motion_reduced: reducedMotion(),
      save_data: saveData(),
    };
    window.mbshAnalytics?.track?.(`guide_${action}`, payload);
    window.dispatchEvent(new CustomEvent('mbsh:guide', { detail: { action, ...payload } }));
  }

  function query(selector) {
    if (!selector) return null;
    try {
      return document.querySelector(selector);
    } catch (_) {
      return null;
    }
  }

  function filterSteps(role, capabilities, staffRole = '') {
    const guide = guides[role];
    if (!guide) return [];
    const allowed = new Set(Array.isArray(capabilities) ? capabilities : []);
    return guide.steps.filter((step) => (!step.capability || allowed.has(step.capability)) && (!step.roles || step.roles.includes(staffRole)));
  }

  function clearSpotlight(instance) {
    if (!instance.spotlight) return;
    instance.spotlight.classList.remove(ACTIVE_CLASS);
    instance.spotlight.removeAttribute('data-mbsh-guide-target');
    instance.spotlight = null;
  }

  function spotlight(instance, step) {
    clearSpotlight(instance);
    const target = query(step.target);
    if (!target || target.hidden || target.getAttribute('aria-hidden') === 'true') return;
    instance.spotlight = target;
    target.classList.add(ACTIVE_CLASS);
    target.setAttribute('data-mbsh-guide-target', step.key);
    if (!reducedMotion() && !saveData()) {
      target.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
    }
  }

  function close(instance, reason = 'dismissed') {
    if (!instance.open) return;
    clearSpotlight(instance);
    instance.open = false;
    instance.panel.hidden = true;
    document.body.classList.remove('mbsh-guide-open');
    instance.launcher.setAttribute('aria-expanded', 'false');
    instance.launcher.focus({ preventScroll: true });
    track(reason, { role: instance.role, step: instance.steps[instance.index] });
  }

  function render(instance) {
    const step = instance.steps[instance.index];
    if (!step) return;
    const total = instance.steps.length;
    instance.panel.querySelector('[data-guide-eyebrow]').textContent = guides[instance.role].eyebrow;
    instance.panel.querySelector('[data-guide-title]').textContent = step.title;
    instance.panel.querySelector('[data-guide-body]').textContent = step.body;
    instance.panel.querySelector('[data-guide-progress]').textContent = `Scene ${instance.index + 1} of ${total}`;
    instance.panel.querySelector('[data-guide-progressbar]').value = instance.index + 1;
    instance.panel.querySelector('[data-guide-progressbar]').max = total;
    instance.panel.querySelector('[data-guide-back]').disabled = instance.index === 0;
    const next = instance.panel.querySelector('[data-guide-next]');
    next.textContent = instance.index === total - 1 ? 'Finish guide' : 'Next scene';
    const action = instance.panel.querySelector('[data-guide-action]');
    action.textContent = step.action.label;
    spotlight(instance, step);
    track('step_viewed', { role: instance.role, step });
  }

  function goToAction(instance) {
    const step = instance.steps[instance.index];
    if (!step || !step.action) return;
    const action = step.action;
    const target = action.selector ? query(action.selector) : null;
    clearSpotlight(instance);
    instance.open = false;
    instance.panel.hidden = true;
    document.body.classList.remove('mbsh-guide-open');
    instance.launcher.setAttribute('aria-expanded', 'false');
    track('next_action_opened', { role: instance.role, step });
    if (action.href) {
      window.location.assign(action.href);
      return;
    }
    if (!target || target.hidden || target.getAttribute('aria-hidden') === 'true') {
      instance.panel.hidden = false;
      instance.open = true;
      document.body.classList.add('mbsh-guide-open');
      instance.panel.querySelector('[data-guide-status]').textContent = 'That workspace is not available in this session.';
      track('unavailable', { role: instance.role, step });
      return;
    }
    target.focus?.({ preventScroll: true });
    target.click();
  }

  function createPanel(role, steps) {
    const panel = document.createElement('aside');
    panel.className = 'mbsh-reunion-guide';
    panel.hidden = true;
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'false');
    panel.setAttribute('aria-labelledby', `mbsh-guide-title-${role}`);
    panel.setAttribute('aria-describedby', `mbsh-guide-body-${role}`);
    panel.dataset.reunionGuidePanel = role;
    panel.innerHTML = `
      <div class="mbsh-reunion-guide__topline">
        <p class="mbsh-reunion-guide__eyebrow" data-guide-eyebrow></p>
        <button class="mbsh-reunion-guide__close" type="button" data-guide-close aria-label="Close guide">×</button>
      </div>
      <p class="mbsh-reunion-guide__progress" data-guide-progress></p>
      <progress class="mbsh-reunion-guide__progressbar" data-guide-progressbar value="1" max="${steps.length}">1</progress>
      <h2 id="mbsh-guide-title-${role}" data-guide-title></h2>
      <p id="mbsh-guide-body-${role}" class="mbsh-reunion-guide__body" data-guide-body></p>
      <p class="mbsh-reunion-guide__status" data-guide-status aria-live="polite"></p>
      <div class="mbsh-reunion-guide__actions">
        <button class="mbsh-reunion-guide__secondary" type="button" data-guide-back>Back</button>
        <button class="mbsh-reunion-guide__primary" type="button" data-guide-action></button>
        <button class="mbsh-reunion-guide__secondary" type="button" data-guide-next></button>
      </div>
      <p class="mbsh-reunion-guide__boundary">This guide points to real screens. It never submits, sends, approves, or changes anything for you.</p>`;
    return panel;
  }

  function mount({ role, capabilities = [], staffRole = '' } = {}) {
    if (!guides[role] || instances.has(role)) return instances.get(role) || null;
    const steps = filterSteps(role, capabilities, staffRole);
    if (!steps.length) return null;

    const launcher = document.createElement('button');
    launcher.className = `mbsh-guide-launcher mbsh-guide-launcher--${role}`;
    launcher.type = 'button';
    launcher.dataset.reunionGuideOpen = role;
    launcher.setAttribute('aria-expanded', 'false');
    launcher.innerHTML = `<span aria-hidden="true">✦</span><span>${guides[role].launcher}</span>`;

    const panel = createPanel(role, steps);
    document.body.append(launcher, panel);
    const instance = { role, capabilities, steps, launcher, panel, index: 0, open: false, spotlight: null };
    instances.set(role, instance);

    const open = (trigger = 'launcher') => {
      instance.open = true;
      panel.hidden = false;
      document.body.classList.add('mbsh-guide-open');
      launcher.setAttribute('aria-expanded', 'true');
      panel.querySelector('[data-guide-status]').textContent = '';
      render(instance);
      panel.querySelector('[data-guide-close]').focus({ preventScroll: true });
      track('opened', { role, step: steps[instance.index], trigger });
    };

    launcher.addEventListener('click', () => open('launcher'));
    panel.querySelector('[data-guide-close]').addEventListener('click', () => close(instance));
    panel.querySelector('[data-guide-back]').addEventListener('click', () => {
      if (instance.index === 0) return;
      instance.index -= 1;
      render(instance);
    });
    panel.querySelector('[data-guide-next]').addEventListener('click', () => {
      if (instance.index === steps.length - 1) {
        close(instance, 'completed');
        return;
      }
      instance.index += 1;
      render(instance);
    });
    panel.querySelector('[data-guide-action]').addEventListener('click', () => goToAction(instance));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && instance.open) close(instance);
    });
    document.querySelectorAll('[data-reunion-guide-open]').forEach((button) => {
      if (button === launcher || button.dataset.reunionGuideOpen && button.dataset.reunionGuideOpen !== role) return;
      button.addEventListener('click', () => open('inline'));
    });
    return instance;
  }

  function mountPublic() {
    return mount({ role: 'public' });
  }

  window.addEventListener('mbsh:portal-ready', () => mount({ role: 'member' }));
  window.addEventListener('mbsh:committee-ready', (event) => {
    const detail = event.detail || {};
    if (!detail.authorized) return;
    mount({ role: 'committee', capabilities: detail.capabilities || [], staffRole: detail.role || '' });
  });

  window.MBSHReunionNavigator = { mount, mountPublic };
})(window, document);
