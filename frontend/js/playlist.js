// playlist.js — Spotify embed activation + suggestion form
(function () {
  'use strict';
  const iframe = document.querySelector('.playlist__embed iframe');
  if (iframe && iframe.dataset.spotifySrc && !iframe.dataset.spotifySrc.includes('PLACEHOLDER')) {
    iframe.src = iframe.dataset.spotifySrc;
  }
  const form = document.getElementById('playlist-suggest-form');
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (form.querySelector('input[name="website"]').value) return;
    if (!window.__famHelpers.formLoadedAtIsRecent(form, 3000)) return;
    const submit = form.querySelector('.playlist-suggest__submit');
    submit.disabled = true; submit.textContent = 'Sending…';
    // No backend endpoint specifically for playlist suggestions — route to memory.php as a generic submission
    // OR build a dedicated playlist-suggest.php; for V1 we route to chatbot-question with a tag
    try {
      const data = window.__famHelpers.serializeForm(form);
      const payload = { question: `[playlist] ${data.track} — ${data.contributor_name || 'anon'} — ${data.why || ''}`, email: '', was_fallback: false };
      const url = window.__famHelpers.apiUrl('/chatbot-question.php');
      const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
      if (!res.ok) throw new Error('http ' + res.status);
      form.reset();
      submit.textContent = 'Suggested. Thank you.';
      setTimeout(() => { submit.disabled = false; submit.textContent = 'Suggest Track'; }, 3000);
    } catch (err) {
      alert('Could not send your suggestion. Email mbsh96reunion@gmail.com.');
      submit.disabled = false; submit.textContent = 'Suggest Track';
    }
  });
})();
