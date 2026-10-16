// Compatibility for a cached meal form. The old submission flow is closed.
(function () {
  const form = document.getElementById('menu-form');
  if (!form) return;
  const notice = document.createElement('p');
  notice.textContent = 'The reunion is now a mixer at Twisted Tonic. Food is on your own and meal selection is closed. Visit /menu/ for location information.';
  form.replaceWith(notice);
})();
