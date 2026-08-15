document.addEventListener('DOMContentLoaded', function () {
  const loadedAt = document.getElementById('form-loaded-at');
  const form = document.getElementById('menu-form');
  const errorMsg = document.getElementById('error-msg');
  const successMsg = document.getElementById('menu-success');
  const successDetails = document.getElementById('menu-success-details');
  if (!form || !loadedAt || !errorMsg || !successMsg || !successDetails) return;
  loadedAt.value = Date.now();

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    errorMsg.style.display = 'none';
    const fd = new FormData(form);
    const entree = fd.get('entree');
    if (!entree) { errorMsg.textContent = 'Please select one entrée.'; errorMsg.style.display = 'block'; return; }
    const payload = {
      name: fd.get('name'), email: fd.get('email'),
      selections: {
        hors: ['Fresh Seasonal Fruits', 'Domestic and Imported Cheeses, Breads, and Crackers'],
        salad: ['Garden Salad', 'Caesar Salad'], entree: [entree],
        side: ['Rosemary Fingerling Potatoes', 'Rice Pilaf', 'Sautéed Green Beans']
      },
      dietary: fd.get('dietary') || null, website: fd.get('website'),
      form_loaded_at: parseInt(fd.get('form_loaded_at') || '0', 10)
    };
    const submit = form.querySelector('button[type="submit"]');
    submit.disabled = true; submit.textContent = 'Submitting…';
    try {
      const response = await fetch('/menu.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.messages?.join(' ') || data.error || 'Submission failed.');
      form.hidden = true; successMsg.hidden = false;
      successDetails.innerHTML = `<h3>Your Dinner Menu</h3><ul><li><strong>Hors d'Oeuvres:</strong> ${payload.selections.hors.join(', ')}</li><li><strong>Salads:</strong> ${payload.selections.salad.join(', ')}</li><li><strong>Entrée:</strong> ${entree}</li><li><strong>Sides:</strong> ${payload.selections.side.join(', ')}</li>${payload.dietary ? `<li><strong>Dietary:</strong> ${payload.dietary}</li>` : ''}</ul>`;
    } catch (error) {
      errorMsg.textContent = error.message || 'Network error. Please try again.'; errorMsg.style.display = 'block';
      submit.disabled = false; submit.textContent = 'Submit Meal Selection';
    }
  });
});
