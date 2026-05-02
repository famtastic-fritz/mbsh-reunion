// chatbot.js — Hi-Tide Harry Phase 1 (FAQ matching + fallback collector)
// No hallucinated answers. Unmatched → graceful fallback that collects email.
(function () {
  'use strict';

  const FAQ = [
    { match: /(when|date|day).*(reunion|happen|event)|when is/i,
      answer: () => `Saturday, ${(window.__SITE_CONFIG__?.REUNION_DATE_DISPLAY) || 'date confirmed soon'}, from 7 PM to midnight.` },
    { match: /where|venue|location|address/i,
      answer: () => 'Venue announcement coming soon. The committee is finalizing details.' },
    { match: /rsvp|reserve|confirm.*attend/i,
      answer: () => `Tap here to RSVP: <a href="rsvp.html">/rsvp</a>` },
    { match: /who.*coming|attendees|attendee list|guest list/i,
      answer: () => `See who's confirmed: <a href="rsvp.html#attendees">classmates</a>` },
    { match: /dress|attire|black.tie|what.*wear/i,
      answer: () => 'Black-tie cocktail. Think prom night, but make it 2026.' },
    { match: /price|cost|tickets|how much/i,
      answer: () => `$${(window.__SITE_CONFIG__?.EARLY_BIRD_PRICE) || 60} early bird, $${(window.__SITE_CONFIG__?.REGULAR_PRICE) || 75} after the deadline. <a href="tickets.html">/tickets</a>` },
    { match: /sponsor|donate|donation|fund/i,
      answer: () => `Yes! Tap here to see sponsor tiers: <a href="tickets.html#sponsor">/tickets#sponsor</a>` },
    { match: /are you ai|are you a bot|are you real|are you.*chatgpt/i,
      answer: () => `I'm Hi-Tide Harry, the reunion's spirit guide. Let's just say I've been here a while.` }
  ];

  const GREETING = `Hey Tide family. I'm Harry, the reunion's spirit guide. Got a question about the night? Ask me anything.`;
  const FALLBACK = `That's a great question — let me get the committee on it. Drop your email and we'll follow up.`;

  const els = {
    bubble: document.getElementById('chatbot-toggle'),
    panel: document.getElementById('chatbot-panel'),
    close: document.getElementById('chatbot-close'),
    messages: document.getElementById('chatbot-messages'),
    form: document.getElementById('chatbot-form'),
    input: document.getElementById('chatbot-input')
  };
  if (!els.bubble || !els.panel) return;

  let greeted = false;

  function open() {
    els.panel.hidden = false;
    els.bubble.setAttribute('aria-expanded', 'true');
    if (!greeted) { addMsg('harry', GREETING); greeted = true; }
    setTimeout(() => els.input.focus(), 150);
  }
  function close() {
    els.panel.hidden = true;
    els.bubble.setAttribute('aria-expanded', 'false');
  }

  function addMsg(role, html) {
    const li = document.createElement('li');
    li.className = `chatbot__msg chatbot__msg--${role}`;
    li.innerHTML = html;
    els.messages.appendChild(li);
    els.messages.scrollTop = els.messages.scrollHeight;
    return li;
  }

  function findFaq(text) {
    return FAQ.find(f => f.match.test(text));
  }

  function showFallbackForm(question) {
    const li = document.createElement('li');
    li.className = 'chatbot__msg chatbot__msg--fallback-form';
    li.innerHTML = `
      <p style="margin:0 0 .25rem;font-style:italic;font-size:.85rem;">Drop your email and the committee will follow up.</p>
      <input type="email" placeholder="you@example.com" required>
      <button type="button">Send to Committee</button>
    `;
    els.messages.appendChild(li);
    els.messages.scrollTop = els.messages.scrollHeight;
    const input = li.querySelector('input');
    const btn = li.querySelector('button');
    btn.addEventListener('click', () => {
      const email = (input.value || '').trim();
      if (!email || !email.includes('@')) { input.focus(); return; }
      submitFallback(question, email).then(() => {
        li.innerHTML = `<p style="margin:0;font-style:italic;">Got it — committee will reach out at ${email}.</p>`;
      }).catch(() => {
        li.innerHTML = `<p style="margin:0;font-style:italic;color:var(--c-red);">Couldn't send right now. Email <a href="mailto:mbsh96reunion@gmail.com">mbsh96reunion@gmail.com</a> directly.</p>`;
      });
    });
  }

  async function submitFallback(question, email) {
    const url = window.__famHelpers?.apiUrl('/chatbot-question.php');
    if (!url) throw new Error('no api');
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ question, email, was_fallback: true })
    });
    if (!res.ok) throw new Error('http ' + res.status);
    return res.json();
  }

  els.bubble.addEventListener('click', () => els.panel.hidden ? open() : close());
  els.close.addEventListener('click', close);

  els.form.addEventListener('submit', (e) => {
    e.preventDefault();
    const text = (els.input.value || '').trim();
    if (!text) return;
    addMsg('user', text.replace(/[<>]/g, ''));
    els.input.value = '';
    setTimeout(() => {
      const faq = findFaq(text);
      if (faq) {
        addMsg('harry', faq.answer());
      } else {
        addMsg('harry', FALLBACK);
        showFallbackForm(text);
      }
    }, 400);
  });
})();
