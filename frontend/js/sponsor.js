// sponsor.js — modal open/close + tier preselection + form submit + sponsor wall poll
(function () {
  'use strict';
  const modal = document.getElementById('sponsor-modal');
  const close = document.getElementById('sponsor-modal-close');
  const tierSelect = document.getElementById('sponsor-tier-select');
  const customWrap = document.getElementById('sponsor-custom-amount-wrap');
  const form = document.getElementById('sponsor-form');
  const wall = document.getElementById('sponsor-wall');

  document.querySelectorAll('[data-sponsor-tier]').forEach(btn => {
    btn.addEventListener('click', () => {
      if (tierSelect) tierSelect.value = btn.dataset.sponsorTier;
      if (customWrap) customWrap.hidden = btn.dataset.sponsorTier !== 'custom';
      if (modal && modal.showModal) modal.showModal();
    });
  });

  if (tierSelect) tierSelect.addEventListener('change', () => {
    if (customWrap) customWrap.hidden = tierSelect.value !== 'custom';
  });

  close?.addEventListener('click', () => modal.close());

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (form.querySelector('input[name="website"]').value) return;
    if (!window.__famHelpers.formLoadedAtIsRecent(form, 3000)) return;
    const submit = form.querySelector('.sponsor-form__submit');
    submit.disabled = true; submit.textContent = 'Sending…';
    try {
      const fd = new FormData(form);
      const url = window.__famHelpers.apiUrl('/sponsor.php');
      const res = await fetch(url, { method: 'POST', body: fd });
      if (!res.ok) throw new Error('http ' + res.status);
      modal.close();
      alert('Thanks — committee will follow up via email.');
      form.reset();
    } catch (err) {
      alert('Could not submit. Email mbsh96reunion@gmail.com.');
    } finally {
      submit.disabled = false; submit.textContent = 'Submit Inquiry';
    }
  });

  // Sponsor wall poll
  async function loadWall() {
    if (!wall) return;
    try {
      const url = window.__famHelpers.apiUrl('/sponsors.php');
      const res = await fetch(url);
      if (!res.ok) return;
      const list = await res.json();
      if (!Array.isArray(list) || list.length === 0) return;
      wall.innerHTML = '';
      list.forEach(s => {
        const card = document.createElement('div');
        card.className = `sponsor-wall__item sponsor-wall__item--${s.tier}`;
        card.innerHTML = s.logo_path
          ? `<img src="${s.logo_path}" alt="${s.display_name}" loading="lazy">`
          : `<span>${s.display_name}</span>`;
        wall.appendChild(card);
      });
    } catch (e) { /* silent — keep empty state */ }
  }
  loadWall();
})();
