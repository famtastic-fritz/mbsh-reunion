// time-capsule.js — capsule form submit + wax-seal success state
(function () {
  'use strict';
  const form = document.getElementById('capsule-form');
  const success = document.getElementById('capsule-success');
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (form.querySelector('input[name="website"]').value) return;
    if (!window.__famHelpers.formLoadedAtIsRecent(form, 3000)) return;
    const submit = form.querySelector('.capsule-form__submit');
    submit.disabled = true; submit.textContent = 'Sealing…';
    try {
      const data = window.__famHelpers.serializeForm(form);
      const url = window.__famHelpers.apiUrl('/capsule.php');
      const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
      if (!res.ok) throw new Error('http ' + res.status);
      form.parentElement.hidden = true;
      success.hidden = false;
      window.mbshAnalytics?.track('time_capsule_submitted');
    } catch (err) {
      alert('Could not seal the capsule. Email committee@mbsh96reunion.com.');
      submit.disabled = false; submit.textContent = 'Seal the Capsule';
    }
  });
})();
