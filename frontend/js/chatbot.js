// Hi-Tide Harry — page-aware reunion guide with personality and movement.
// FAQ answers remain curated. Unknown questions are sent to the committee.
(function () {
  'use strict';

  const FAQ = [
    { match: /(when|date|day).*(reunion|happen|event)|when is/i,
      answer: () => `${window.__SITE_CONFIG__?.REUNION_DATE_DISPLAY || 'Saturday, November 7, 2026'}, from 7 PM to midnight.` },
    { match: /where|venue|location|address/i,
      answer: () => 'Miami Shores Country Club, 10000 Biscayne Blvd, Miami Shores, FL 33138. <a href="https://www.google.com/maps/dir/?api=1&destination=Miami+Shores+Country+Club%2C+10000+Biscayne+Blvd%2C+Miami+Shores%2C+FL+33138" target="_blank" rel="noopener">Get directions</a>.' },
    { match: /rsvp|reserve|confirm.*attend/i,
      answer: () => 'I can walk you right to it. <a href="/rsvp.html">Reserve your place</a>.' },
    { match: /who.*coming|attendees|attendee list|guest list/i,
      answer: () => 'The public attendee list only shows classmates who choose to be displayed. The committee will publish the class list once it is ready.' },
    { match: /dress|attire|black.tie|what.*wear/i,
      answer: () => 'Black-tie cocktail. Think prom night—grown up, polished, and ready for pictures.' },
    { match: /price|cost|tickets|how much/i,
      answer: () => `$${window.__SITE_CONFIG__?.EARLY_BIRD_PRICE || 185} early bird and $${window.__SITE_CONFIG__?.REGULAR_PRICE || 200} after the deadline. <a href="/tickets.html">See ticket details</a>.` },
    { match: /food|meal|menu|dinner|allerg|diet/i,
      answer: () => 'Dinner choices and dietary notes live on the menu page. <a href="/menu/">Choose your entrée</a>.' },
    { match: /music|playlist|song|soundtrack/i,
      answer: () => 'Now you are speaking my language. <a href="/playlist.html">Visit the soundtrack</a> and suggest the song that takes you back.' },
    { match: /memory|photo|yearbook|1996|through the years/i,
      answer: () => 'The living archive is taking shape. <a href="/through-years.html">Share a memory or photo</a> for committee review.' },
    { match: /sponsor|donate|donation|fund/i,
      answer: () => 'Your support helps produce the night. <a href="/tickets.html#sponsor">See sponsorship options</a>.' },
    { match: /are you ai|are you a bot|are you real|are you.*chatgpt/i,
      answer: () => 'I am Hi-Tide Harry—the spirit of the halls, your reunion usher, and apparently the only mascot working the night shift.' }
  ];

  const PAGE_PERSONA = {
    home: {
      pose: '/assets/mascot/01-wave-hello.png',
      greeting: 'There you are. Thirty years later—and right on time. I can show you around or get you straight to RSVP.',
      nudge: 'The lobby is open. Start with your place at the table?',
      chips: [['RSVP', 'How do I RSVP?'], ['Tickets', 'How much are tickets?'], ['The night', 'When and where is the reunion?']]
    },
    rsvp: {
      pose: '/assets/mascot/12-clipboard.png',
      greeting: 'I have the clipboard. You bring the memories. This takes about two minutes, and I will stay out of your way while you type.',
      nudge: 'First things first: tell us whether we should save you a seat.',
      chips: [['What happens next?', 'What happens after I RSVP?'], ['Privacy', 'Who can see my RSVP?'], ['Tickets', 'Do I pay here?']]
    },
    tickets: {
      pose: '/assets/mascot/13-ticket-stub.png',
      greeting: 'Welcome to the box office. I can explain the ticket phase, guest orders, or sponsorship without the fine-print fog.',
      nudge: 'Pick the number of seats first. I will help with the rest.',
      chips: [['Ticket price', 'How much are tickets?'], ['Payment', 'How does payment work?'], ['Sponsor', 'How can I sponsor?']]
    },
    menu: {
      pose: '/assets/mascot/15-seated-usher.png',
      greeting: 'Dinner service, handled. Choose one entrée and tell us about anything the kitchen needs to know.',
      nudge: 'Chicken, salmon, or vegetarian—what is calling your name?',
      chips: [['Dietary needs', 'How do I report an allergy?'], ['Update choice', 'Can I change my meal later?'], ['Tickets', 'Where is my ticket order?']]
    },
    survey: {
      pose: '/assets/mascot/12-clipboard.png',
      greeting: 'This is the quick class check-in—not the final ticket purchase. Sixty seconds gives the committee a better head count.',
      nudge: 'A quick signal now helps the committee plan the room.',
      chips: [['Survey vs RSVP', 'Is this the same as RSVP?'], ['Privacy', 'How is this used?'], ['Event date', 'When is the reunion?']]
    },
    'through-years': {
      pose: '/assets/mascot/22-walk-frame.png',
      greeting: 'A century behind us and a whole lot of stories between. Walk the timeline—or leave something only you remember.',
      nudge: 'That one hallway story? This is where it belongs.',
      chips: [['Add memory', 'How do I add a memory?'], ['Photos', 'Can I upload a photo?'], ['1996', 'Show me the Class of 1996 story.']]
    },
    memorial: {
      pose: '/assets/mascot/17-respectful.png',
      greeting: 'We slow down here. Every name matters, and the committee verifies additions with care.',
      nudge: 'If a classmate is missing, I can show you the respectful way to notify the committee.',
      chips: [['Add a name', 'How do I add a name?'], ['Tribute', 'How will classmates be honored?']]
    },
    capsule: {
      pose: '/assets/mascot/14-wax-stamping.png',
      greeting: 'Write it tonight. I will seal it, and the system delivers it back on reunion morning.',
      nudge: 'What would seventeen-year-old you need to hear?',
      chips: [['Privacy', 'Will the committee read this?'], ['Delivery', 'When is my capsule delivered?']]
    },
    playlist: {
      pose: '/assets/mascot/16-conducting.png',
      greeting: 'Every reunion needs the song that makes the room shout the first line together. What is yours?',
      nudge: 'Give me the song that instantly puts you back in 1996.',
      chips: [['Suggest song', 'How do I suggest a song?'], ['Playlist', 'Where can I hear the playlist?']]
    }
  };

  const page = document.body.dataset.page || 'home';
  const persona = PAGE_PERSONA[page] || PAGE_PERSONA.home;
  const els = {
    root: document.getElementById('chatbot'),
    bubble: document.getElementById('chatbot-toggle'),
    panel: document.getElementById('chatbot-panel'),
    close: document.getElementById('chatbot-close'),
    messages: document.getElementById('chatbot-messages'),
    form: document.getElementById('chatbot-form'),
    input: document.getElementById('chatbot-input')
  };
  if (!els.root || !els.bubble || !els.panel) return;

  let greeted = false;
  let typing = false;
  let nudgeTimer = null;
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function mountPersonality() {
    els.root.classList.add('harry-guide');
    els.root.dataset.dock = 'right';
    els.root.dataset.mood = page === 'memorial' ? 'respectful' : 'ready';
    els.bubble.querySelector('img').src = persona.pose;
    els.bubble.querySelector('img').alt = '';
    els.bubble.setAttribute('aria-label', 'Talk with Hi-Tide Harry, reunion guide');

    const label = document.createElement('span');
    label.className = 'harry-guide__label';
    label.innerHTML = '<strong>Ask Harry</strong><span>Reunion guide</span>';
    els.bubble.appendChild(label);

    const speech = document.createElement('button');
    speech.type = 'button';
    speech.className = 'harry-guide__speech';
    speech.setAttribute('aria-label', `Harry says: ${persona.nudge} Open reunion guide.`);
    speech.textContent = persona.nudge;
    speech.addEventListener('click', open);
    els.root.appendChild(speech);

    const panelPose = els.panel.querySelector('.chatbot__panel-pose');
    if (panelPose) panelPose.src = persona.pose;
    const panelTitle = els.panel.querySelector('.chatbot__panel-title strong');
    if (panelTitle) {
      panelTitle.id = 'harry-dialog-title';
      els.panel.setAttribute('role', 'dialog');
      els.panel.setAttribute('aria-modal', 'true');
      els.panel.setAttribute('aria-labelledby', panelTitle.id);
    }

    const chips = document.createElement('div');
    chips.className = 'harry-guide__chips';
    chips.setAttribute('aria-label', 'Suggested questions');
    persona.chips.forEach(([labelText, question]) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = labelText;
      button.addEventListener('click', () => ask(question));
      chips.appendChild(button);
    });
    els.form.before(chips);

    if (!reduceMotion) {
      requestAnimationFrame(() => els.root.classList.add('is-entered'));
      window.addEventListener('scroll', updateDock, { passive: true });
      window.addEventListener('scroll', hideNudge, { passive: true, once: true });
      window.addEventListener('scroll', updateAvoidance, { passive: true });
      window.addEventListener('resize', updateAvoidance, { passive: true });
      updateDock();
      updateAvoidance();
    } else {
      els.root.classList.add('is-entered');
    }

    const form = document.querySelector('form:not(.chatbot__input)');
    if (form) {
      form.addEventListener('focusin', () => {
        typing = true;
        els.root.classList.add('is-polite');
        hideNudge();
      });
      form.addEventListener('focusout', () => {
        window.setTimeout(() => {
          if (!form.contains(document.activeElement)) {
            typing = false;
            els.root.classList.remove('is-polite');
          }
        }, 80);
      });
      form.addEventListener('submit', () => {
        els.root.dataset.mood = 'cheering';
        const image = els.bubble.querySelector('img');
        image.src = '/assets/mascot/04-excited-cheer.png';
      });
    }

    window.addEventListener('harry:menu', (event) => {
      els.root.classList.toggle('is-polite', event.detail.open);
      if (event.detail.open) hideNudge();
    });

    nudgeTimer = window.setTimeout(showNudge, 1800);

  }

  function updateAvoidance() {
    if (innerWidth > 600 || els.panel.hidden === false) {
      els.root.classList.remove('is-avoiding');
      return;
    }
    const targets = document.querySelectorAll('.cinema-actions, .journey-progress, .rsvp-form');
    const shouldAvoid = [...targets].some((target) => {
      const rect = target.getBoundingClientRect();
      return rect.top < innerHeight && rect.bottom > innerHeight - 145;
    });
    els.root.classList.toggle('is-avoiding', shouldAvoid);
    if (shouldAvoid) hideNudge();
  }

  function updateDock() {
    if (typing || els.panel.hidden === false) return;
    const max = Math.max(1, document.documentElement.scrollHeight - innerHeight);
    const progress = scrollY / max;
    const next = progress < .28 ? 'right' : progress < .62 ? 'left' : 'right';
    if (els.root.dataset.dock !== next) {
      els.root.classList.add('is-walking');
      els.root.dataset.dock = next;
      const image = els.bubble.querySelector('img');
      image.src = '/assets/mascot/22-walk-frame.png';
      window.setTimeout(() => {
        image.src = persona.pose;
        els.root.classList.remove('is-walking');
      }, 720);
    }
  }

  function showNudge() {
    if (typing || !els.panel.hidden) return;
    els.root.classList.add('is-speaking');
    window.setTimeout(hideNudge, 7000);
  }
  function hideNudge() { els.root.classList.remove('is-speaking'); }

  function open() {
    clearTimeout(nudgeTimer);
    hideNudge();
    els.panel.hidden = false;
    document.body.style.overflow = 'hidden';
    els.root.classList.add('is-open');
    els.bubble.setAttribute('aria-expanded', 'true');
    if (!greeted) {
      addMsg('harry', persona.greeting);
      greeted = true;
    }
    window.setTimeout(() => els.input.focus(), 120);
  }

  function close() {
    els.panel.hidden = true;
    document.body.style.overflow = '';
    els.root.classList.remove('is-open');
    els.bubble.setAttribute('aria-expanded', 'false');
    els.bubble.focus();
  }

  function addMsg(role, html) {
    const li = document.createElement('li');
    li.className = `chatbot__msg chatbot__msg--${role}`;
    li.innerHTML = html;
    els.messages.appendChild(li);
    els.messages.scrollTop = els.messages.scrollHeight;
    return li;
  }

  function findFaq(text) { return FAQ.find(item => item.match.test(text)); }

  function contextualAnswer(text) {
    if (/what happens after.*rsvp|what happens next/i.test(text)) return 'You receive a confirmation email. Next, the ticket page handles seats and the menu page captures dinner choices.';
    if (/who can see|privacy|how is this used/i.test(text)) return 'Only the public-display choice can place your name and city on the attendee list. The committee uses the remaining details to coordinate the reunion.';
    if (/do i pay here|payment work/i.test(text)) return 'The current ticket page records the order at today’s price. It clearly explains whether payment is collected now or sent as a follow-up instruction.';
    if (/same as rsvp/i.test(text)) return 'No. Class Check-In is an early planning signal. RSVP is the formal attendance record. The facelift makes that distinction explicit.';
    if (/committee read|capsule.*private/i.test(text)) return 'The capsule promise says the committee does not read or share your answers. The message is queued for delivery to your email on reunion day.';
    if (/capsule delivered|when.*deliver/i.test(text)) return 'The capsule is scheduled for reunion morning, November 7, 2026.';
    if (/add a name|classmate.*missing/i.test(text)) return 'Send the classmate’s name and a short note to the committee. Additions are verified before publication.';
    if (/honored|tribute/i.test(text)) return 'Every verified name is included in the reunion tribute and read aloud with care.';
    if (/change.*meal|update.*choice/i.test(text)) return 'Use the committee email shown in your confirmation so the team can update the recorded selection.';
    if (/report an allergy/i.test(text)) return 'Use the dietary restrictions field on the menu form. Include the specific allergy and anything the venue needs to avoid.';
    if (/add a memory/i.test(text)) return 'Open Through the Years, add your name and story, and optionally upload one safe JPG, PNG, or WebP image for committee review.';
    if (/upload a photo/i.test(text)) return 'Yes—JPG, PNG, or WebP up to 2 MB. The committee reviews it before it appears publicly.';
    if (/suggest a song/i.test(text)) return 'Enter the artist and song title, then tell us why it belongs in the room. Your name is optional.';
    if (/hear the playlist/i.test(text)) return 'The Soundtrack page activates the playlist player and keeps song suggestions in the same experience.';
    return null;
  }

  function ask(text) {
    if (els.panel.hidden) open();
    addMsg('user', text.replace(/[<>]/g, ''));
    const direct = contextualAnswer(text);
    const faq = findFaq(text);
    window.setTimeout(() => {
      if (direct) addMsg('harry', direct);
      else if (faq) addMsg('harry', faq.answer());
      else {
        addMsg('harry', 'You found one for the committee. Leave your email and I will put the question in the right hands.');
        showFallbackForm(text);
      }
    }, reduceMotion ? 0 : 320);
  }

  function showFallbackForm(question) {
    const li = document.createElement('li');
    li.className = 'chatbot__msg chatbot__msg--fallback-form';
    li.innerHTML = '<p>Where should the committee reply?</p><input type="email" autocomplete="email" placeholder="you@example.com" required><button type="button">Send to committee</button>';
    els.messages.appendChild(li);
    const input = li.querySelector('input');
    const button = li.querySelector('button');
    button.addEventListener('click', async () => {
      const email = input.value.trim();
      if (!/^\S+@\S+\.\S+$/.test(email)) { input.focus(); return; }
      button.disabled = true;
      button.textContent = 'Sending…';
      try {
        await submitFallback(question, email);
        li.innerHTML = `<p>Got it. The committee will reply to ${email.replace(/[<>]/g, '')}.</p>`;
      } catch (_) {
        li.innerHTML = '<p>I could not send that right now. Email <a href="mailto:mbsh96reunion@gmail.com">mbsh96reunion@gmail.com</a> directly.</p>';
      }
    });
    input.focus();
  }

  async function submitFallback(question, email) {
    const url = window.__famHelpers?.apiUrl('/chatbot-question.php');
    if (!url) throw new Error('API unavailable');
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ question, email, was_fallback: true })
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
  }

  els.bubble.addEventListener('click', () => els.panel.hidden ? open() : close());
  els.close.addEventListener('click', close);
  els.form.addEventListener('submit', (event) => {
    event.preventDefault();
    const text = els.input.value.trim();
    if (!text) return;
    els.input.value = '';
    ask(text);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !els.panel.hidden) close();
  });

  mountPersonality();
})();
