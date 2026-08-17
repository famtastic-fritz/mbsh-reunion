(function () {
  'use strict';
  document.addEventListener('click', event => {
    const ticket = event.target.closest('[data-void-ticket]');
    if (!ticket) return;
    if (!window.confirm('Void this ticket? The record and audit history will remain, but it can no longer be used for admission.')) {
      event.preventDefault();
      event.stopImmediatePropagation();
    }
  }, true);
})();
