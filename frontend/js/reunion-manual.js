(function () {
  'use strict';

  const tracks = {
    public: [
      ['01', 'Home', 'The cinematic reunion landing page: event facts, directions, and the clearest next actions.', '/index.html', 'Open home', 'Public route'],
      ['02', 'RSVP', 'Use the diploma-style RSVP to tell the committee you are coming and supply reunion details.', '/rsvp.html', 'Start RSVP', 'Public form'],
      ['03', 'Tickets & sponsorship', 'Request seats or make a sponsor inquiry. This is not live checkout or payment collection.', '/tickets.html', 'Review ticket options', 'Request only'],
      ['04', 'Dinner preferences', 'Choose an entrée and share dietary information for the committee to plan the evening.', '/menu/', 'Open dinner choices', 'Public form'],
      ['05', 'Class check-in', 'A short class survey that helps the committee understand attendance and interest.', '/survey.html', 'Check in with the class', 'Public form'],
      ['06', 'Through the Years', 'Explore the centennial story and submit a photo or memory for committee approval.', '/through-years.html', 'Visit the archive', 'Archive coming soon'],
      ['07', 'In Memory', 'A committee-curated place to carry classmates forward with care.', '/memorial.html', 'Visit In Memory', 'Curated feed'],
      ['08', 'Time Capsule', 'Write a private note intended for scheduled reunion-day delivery.', '/capsule.html', 'Write a capsule', 'Scheduled delivery'],
      ['09', 'The Soundtrack', 'Suggest the song that belongs in the room. Suggestions go to the committee queue.', '/playlist.html', 'Suggest a song', 'Committee review'],
      ['10', 'Hi-Tide Harry', 'Get help with reunion FAQs, then send an unanswered question to the committee.', '/index.html#chatbot', 'Ask Harry', 'Human follow-up'],
      ['11', 'Join My Reunion', 'Create a verified attendee account for a private, ongoing reunion experience.', '/portal/register', 'Create an account', 'Verified account']
    ],
    member: [
      ['01', 'My Reunion dashboard', 'Your private home for the next reunion action, personal status, and event guidance.', '/portal/#home', 'Open My Reunion', 'Private'],
      ['02', 'RSVP & dinner', 'Save attendance, guests, meal choice, phone, and dietary or accessibility needs to your current portal response.', '/portal/#rsvp', 'Review RSVP & dinner', 'Private record'],
      ['03', 'Ticket wallet', 'See only tickets issued to your verified account and the current rotating check-in credential.', '/portal/#ticket', 'Open ticket wallet', 'Credential protected'],
      ['04', 'Memories', 'Upload a photo or video with a title, caption, year, and publication consent. It remains private until reviewed.', '/portal/#memories', 'Share a memory', 'Moderated'],
      ['05', 'Suggestions', 'Send music, event, accessibility, or site ideas and follow the committee decision on your own submission.', '/portal/#suggestions', 'Make a suggestion', 'Private thread'],
      ['06', 'Messages', 'Start, read, reply to, close, or reopen a durable conversation with the committee.', '/portal/#inbox', 'Open messages', 'Account scoped'],
      ['07', 'Class trivia', 'Play the open class game; scoring and attempts are handled by the server.', '/portal/#trivia', 'Play trivia', 'Honest empty state'],
      ['08', 'Notifications', 'Choose optional event, memory, promotional, and SMS preferences. Critical account and ticket messages stay separate.', '/portal/#notifications', 'Set preferences', 'Consent aware']
    ],
    committee: [
      ['01', 'Command center', 'Read operational signals and the current source context. Counts never grant permission to change a record.', '/portal/admin/#command', 'Open command center', 'Staff session'],
      ['02', 'People & RSVP', 'Deliberately search a protected attendee record and see only the details your capability permits.', '/portal/admin/#people', 'Open People', 'view_roster'],
      ['03', 'Dinner & dietary', 'Open linked attendee context for dinner submissions. Existing legacy history is read-only here.', '/portal/admin/#dinner', 'Open dinner context', 'view_menu'],
      ['04', 'Hi-Tide Harry Desk', 'Review unanswered Harry questions and save an accountable internal response—never an automatic public reply.', '/portal/admin/#harry', 'Open Harry Desk', 'respond_harry'],
      ['05', 'Media approvals', 'Approve or return submitted media after rights, safety, and context review. Originals stay private.', '/portal/admin/#review', 'Open review queue', 'moderate_media'],
      ['06', 'Messages', 'Read the full attendee conversation timeline and queue an accountable committee reply.', '/portal/admin/#messages', 'Open messages', 'view_inbox'],
      ['07', 'Tickets & check-in', 'Review ticket operations and verify a live credential at the door. Payment truth remains in commerce.', '/portal/admin/#tickets', 'Open operations', 'manage_tickets'],
      ['08', 'Content registry', 'See the structured WordPress component registry; editing and publishing remain controlled owner work.', '/portal/admin/#content', 'Open content registry', 'manage_event_content']
    ],
    owner: [
      ['01', 'Users & permissions', 'Grant or revoke attendee, committee, lead, and owner roles. Every access change is audited.', '/portal/admin/#access', 'Open permissions', 'Owner only'],
      ['02', 'Pages & components', 'Inspect the WordPress structured-component map that drives editable page sections and calls to action.', '/portal/admin/#platform', 'Open component map', 'Owner only'],
      ['03', 'Email & workers', 'Inspect queued, sent, retried, failed, and dead-letter states. “Sent” is not an inbox-delivery promise.', '/portal/admin/#delivery', 'Open delivery health', 'Owner only'],
      ['04', 'Audit & data', 'Review reconciliation counts and recorded staff actions without exposing raw data broadly.', '/portal/admin/#audit', 'Open audit', 'Owner only'],
      ['05', 'Exception tickets', 'Issue or void an audited exception only when a real entitlement exists outside the normal commerce path.', '/portal/admin/#tickets', 'Open ticket exceptions', 'Owner only'],
      ['06', 'CMS & commerce', 'Use the separate owner control plane for editable content, products, orders, refunds, and platform maintenance.', '/portal/admin/', 'Open Admin Portal', 'Separate control plane']
    ]
  };

  const escape = (value) => String(value).replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[char]);
  const renderTrack = (track) => {
    const target = document.querySelector(`[data-manual-list="${track}"]`);
    if (!target || target.dataset.rendered) return;
    target.dataset.rendered = 'true';
    target.innerHTML = tracks[track].map(([number, title, description, href, action, badge]) => `
      <article class="manual-route-card">
        <div class="manual-route-card__top"><span class="manual-route-card__number">${escape(number)}</span><span class="manual-route-card__badge">${escape(badge)}</span></div>
        <h3>${escape(title)}</h3>
        <p>${escape(description)}</p>
        <div class="manual-route-card__bottom"><a href="${escape(href)}">${escape(action)} <span aria-hidden="true">→</span></a></div>
      </article>`).join('');
  };

  const reveal = (track) => {
    const list = document.querySelector(`[data-manual-list="${track}"]`);
    const locked = document.querySelector(`[data-manual-locked="${track}"]`);
    if (!list) return;
    renderTrack(track);
    list.hidden = false;
    if (locked) locked.hidden = true;
  };

  const updateAccess = async () => {
    try {
      const response = await fetch('/portal/session.php', { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      if (!response.ok) return;
      const session = await response.json();
      if (!session.authenticated) return;
      reveal('member');
      const staff = session.staff || {};
      if (!staff.authorized) return;
      reveal('committee');
      if (staff.role === 'site_owner') reveal('owner');
    } catch (_) {
      // This public guide remains useful if an account session is unavailable.
    }
  };

  const setPresentation = () => {
    const scenes = [...document.querySelectorAll('[data-manual-scene]')];
    const controls = document.querySelector('[data-manual-controls]');
    const page = document.querySelector('[data-manual-page]');
    const total = document.querySelector('[data-manual-total]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches || navigator.connection?.saveData;
    let current = 0;
    if (!scenes.length || !controls) return;
    total.textContent = String(scenes.length);
    const show = (next) => {
      current = Math.max(0, Math.min(scenes.length - 1, next));
      scenes.forEach((scene, index) => scene.classList.toggle('manual-scene--active', index === current));
      page.textContent = String(current + 1);
      scenes[current].scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
      window.mbshAnalytics?.track?.('manual_presentation_scene_viewed', { scene: current + 1, motion_reduced: reduceMotion });
    };
    const exit = () => {
      document.body.classList.remove('manual-presentation');
      controls.hidden = true;
      scenes.forEach((scene) => scene.classList.remove('manual-scene--active'));
    };
    document.querySelectorAll('[data-manual-present]').forEach((button) => button.addEventListener('click', () => {
      document.body.classList.add('manual-presentation');
      controls.hidden = false;
      show(0);
      window.mbshAnalytics?.track?.('manual_presentation_started', { motion_reduced: reduceMotion });
    }));
    document.querySelector('[data-manual-next]')?.addEventListener('click', () => show(current + 1));
    document.querySelector('[data-manual-prev]')?.addEventListener('click', () => show(current - 1));
    document.querySelector('[data-manual-exit]')?.addEventListener('click', exit);
    document.addEventListener('keydown', (event) => {
      if (!document.body.classList.contains('manual-presentation')) return;
      if (event.key === 'Escape') exit();
      if (event.key === 'ArrowRight') show(current + 1);
      if (event.key === 'ArrowLeft') show(current - 1);
    });
  };

  const bindChapters = () => {
    document.querySelectorAll('[data-manual-chapter]').forEach((link) => link.addEventListener('click', () => {
      window.mbshAnalytics?.track?.('manual_chapter_opened', { chapter: link.dataset.manualChapter });
    }));
  };

  const init = () => {
    renderTrack('public');
    updateAccess();
    setPresentation();
    bindChapters();
    window.mbshAnalytics?.track?.('manual_opened', { page_type: 'manual' });
  };

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
})();
