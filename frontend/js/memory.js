// memory.js — memory submission form (multipart with optional photo)
(function () {
  'use strict';
  const form = document.getElementById('memory-form');
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (form.querySelector('input[name="website"]').value) return;
    if (!window.__famHelpers.formLoadedAtIsRecent(form, 3000)) return;
    const submit = form.querySelector('.memory-form__submit');
    submit.disabled = true; submit.textContent = 'Sending…';
    try {
      const url = window.__famHelpers.apiUrl('/memory.php');
      const res = await fetch(url, { method: 'POST', body: new FormData(form) });
      if (!res.ok) throw new Error('http ' + res.status);
      form.reset();
      submit.textContent = 'Sent. Thank you.';
      setTimeout(() => { submit.disabled = false; submit.textContent = 'Send the Memory'; }, 3000);
    } catch (err) {
      alert('Could not submit your memory. Email mbsh96reunion@gmail.com.');
      submit.disabled = false; submit.textContent = 'Send the Memory';
    }
  });
})();
