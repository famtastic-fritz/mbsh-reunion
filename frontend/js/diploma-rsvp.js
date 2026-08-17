(() => {
  const stage = document.querySelector('[data-diploma-rsvp]');
  const form = document.querySelector('#rsvp-form');
  if (!stage || !form) return;

  const open = (mode, shouldFocus = true) => {
    const animated = mode === 'book' && !matchMedia('(prefers-reduced-motion: reduce)').matches;
    stage.classList.toggle('is-instant', !animated);
    stage.classList.toggle('is-opening', animated);
    if (animated) {
      window.setTimeout(() => {
        stage.classList.remove('is-opening');
        stage.classList.add('is-open');
      }, 1750);
    } else {
      stage.classList.add('is-open');
    }
    if (shouldFocus) {
      window.setTimeout(() => form.querySelector('input,select,textarea')?.focus(), animated ? 1550 : 0);
    }
  };

  document.querySelectorAll('[data-diploma-open]').forEach((button) => {
    button.addEventListener('click', () => open(button.dataset.diplomaOpen));
  });

  if (matchMedia('(max-width: 980px), (prefers-reduced-motion: reduce)').matches) {
    // Open the compact mobile layout without stealing focus or scrolling past
    // the page hero. Explicit user actions still move focus into the form.
    open('instant', false);
  }
})();
