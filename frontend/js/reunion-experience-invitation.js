(function () {
  'use strict';
  const announcement = document.querySelector('[data-reunion-announcement]');
  const key = 'mbsh-reunion-experience-announcement-v1';
  const duration = 30 * 24 * 60 * 60 * 1000;
  function dismiss() {
    if (!announcement) return;
    const focused = announcement.contains(document.activeElement);
    announcement.hidden = true;
    try { localStorage.setItem(key, String(Date.now())); } catch (_) { /* Optional preference only. */ }
    if (focused) {
      const title = document.querySelector('#experience-title');
      title?.setAttribute('tabindex', '-1');
      title?.focus({preventScroll:true});
    }
  }
  announcement?.querySelector('[data-dismiss-announcement]')?.addEventListener('click', dismiss);
  announcement?.querySelector('[data-announcement-explore]')?.addEventListener('click', dismiss);
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && announcement && !announcement.hidden) dismiss(); });
  let dismissed = false;
  try { const at = Number(localStorage.getItem(key)); dismissed = at > 0 && Date.now() - at < duration; } catch (_) { dismissed = true; }
  if (announcement && !dismissed && document.body.dataset.page === 'home') setTimeout(() => {
    // Nonmodal: no stolen focus, backdrop, motion or checkout interruption.
    const visibleModal = [...document.querySelectorAll('dialog[open], [aria-modal="true"]')].some(modal => {
      const style = getComputedStyle(modal);
      return !modal.hidden && modal.getClientRects().length > 0 && style.display !== 'none' && !['hidden', 'collapse'].includes(style.visibility);
    });
    if (!document.hidden && !visibleModal && !document.activeElement?.matches('input,textarea,select')) announcement.hidden = false;
  }, 15000);
  fetch('/portal/experience.php', {credentials:'same-origin', headers:{Accept:'application/json'}, cache:'no-store'})
    .then(response => response.ok ? response.json() : null)
    .then(data => { if (data?.entitled === true) document.querySelectorAll('[data-experience-cta]').forEach(link => {
      link.textContent = 'Open My Reunion Experience'; link.href = '/portal/#experience';
    }); }).catch(() => { /* The public purchase and sign-in paths remain available. */ });
})();
