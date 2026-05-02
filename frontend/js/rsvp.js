// rsvp.js — progressive disclosure on attending=yes + form submit + success
(function () {
  'use strict';
  const form = document.getElementById('rsvp-form');
  const reveal = document.getElementById('rsvp-yes-reveal');
  const attending = document.getElementById('rsvp-attending');
  const success = document.getElementById('rsvp-success');
  if (!form || !attending) return;

  attending.addEventListener('change', () => {
    if (reveal) reveal.hidden = attending.value !== 'yes';
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (form.querySelector('input[name="website"]').value) return; // honeypot
    if (!window.__famHelpers.formLoadedAtIsRecent(form, 3000)) {
      console.warn('[rsvp] form submitted too quickly — silent reject');
      return;
    }
    const submit = form.querySelector('.rsvp-form__submit');
    submit.disabled = true; submit.textContent = 'Sending…';
    try {
      const data = window.__famHelpers.serializeForm(form);
      const url = window.__famHelpers.apiUrl('/rsvp.php');
      const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
      if (!res.ok) throw new Error('http ' + res.status);
      const j = await res.json();
      form.hidden = true;
      success.hidden = false;
      document.getElementById('rsvp-success-name').textContent = data.first_name || 'Hi-Tide';
      document.getElementById('rsvp-success-email').textContent = data.email;
      // Confetti micro-moment
      if (window.requestAnimationFrame) confettiBurst();
    } catch (err) {
      submit.disabled = false; submit.textContent = 'Submit RSVP 🌊';
      alert('We could not save your RSVP. Try again or email mbsh96reunion@gmail.com.');
    }
  });

  function confettiBurst() {
    const colors = ['#C8102E', '#C0C0C0', '#FFFFFF'];
    for (let i = 0; i < 60; i++) {
      const p = document.createElement('span');
      p.style.cssText = `position:fixed;top:50%;left:50%;width:8px;height:8px;background:${colors[i%3]};border-radius:50%;pointer-events:none;z-index:9999;`;
      document.body.appendChild(p);
      const dx = (Math.random() - 0.5) * 600;
      const dy = (Math.random() - 0.5) * 600;
      p.animate([
        { transform: 'translate(-50%, -50%) scale(1)', opacity: 1 },
        { transform: `translate(calc(-50% + ${dx}px), calc(-50% + ${dy}px)) scale(0)`, opacity: 0 }
      ], { duration: 1400, easing: 'cubic-bezier(0.4, 0, 0.4, 1)' }).onfinish = () => p.remove();
    }
  }
})();
