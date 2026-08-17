(() => {
  const shell = document.querySelector('[data-diploma]');
  const form = document.querySelector('#diploma-form');
  const steps = [...document.querySelectorAll('[data-step]')];
  const next = document.querySelector('[data-next]');
  const back = document.querySelector('[data-back]');
  const finish = document.querySelector('[data-finish]');
  const error = document.querySelector('[data-error]');
  const progress = document.querySelector('[data-progress-bar]');
  const progressLabel = document.querySelector('[data-progress-label]');
  const complete = document.querySelector('[data-complete]');
  let current = 0;

  const labels = ['Identity', 'Reunion details', 'Preferences'];
  const open = (mode = 'book') => {
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const animated = mode === 'book' && !reducedMotion;
    shell.classList.toggle('is-instant', !animated);
    shell.classList.toggle('is-opening', animated);
    if (animated) {
      setTimeout(() => {
        shell.classList.remove('is-opening');
        shell.classList.add('is-open');
      }, 1750);
    } else {
      shell.classList.add('is-open');
    }
    setTimeout(() => form.querySelector('input,select,textarea')?.focus(), animated ? 1550 : 0);
  };
  document.querySelectorAll('[data-open]').forEach(button => {
    button.addEventListener('click', () => open(button.dataset.open));
  });

  function render() {
    steps.forEach((step, index) => { step.hidden = index !== current; });
    back.hidden = current === 0;
    next.hidden = current === steps.length - 1;
    finish.hidden = current !== steps.length - 1;
    progress.style.width = `${((current + 1) / steps.length) * 100}%`;
    progressLabel.textContent = `Step ${current + 1} of ${steps.length} · ${labels[current]}`;
    error.hidden = true;
  }

  function validateStep() {
    const invalid = [...steps[current].querySelectorAll('[required]')].find(field => !field.checkValidity());
    if (!invalid) return true;
    error.textContent = invalid.validationMessage || 'Please complete this field before continuing.';
    error.hidden = false;
    invalid.focus();
    return false;
  }

  next.addEventListener('click', () => {
    if (!validateStep()) return;
    current += 1;
    render();
    steps[current].querySelector('input,select,textarea')?.focus();
  });
  back.addEventListener('click', () => {
    current -= 1;
    render();
    steps[current].querySelector('input,select,textarea')?.focus();
  });
  form.addEventListener('submit', event => {
    event.preventDefault();
    if (!validateStep()) return;
    form.hidden = true;
    complete.hidden = false;
    complete.focus();
  });
  document.querySelector('[data-reset]').addEventListener('click', () => {
    form.reset(); current = 0; complete.hidden = true; form.hidden = false; render();
    form.querySelector('input')?.focus();
  });
  render();
})();
