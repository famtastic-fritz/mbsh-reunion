(function () {
  'use strict';
  const form = document.getElementById('ticket-order-form');
  if (!form) return;
  const quantity = document.getElementById('ticket-quantity');
  const total = document.getElementById('ticket-order-total');
  const priceLabel = document.getElementById('ticket-price-label');
  const status = document.getElementById('ticket-order-status');
  const success = document.getElementById('ticket-order-success');
  const successCopy = document.getElementById('ticket-order-success-copy');
  const loadedAt = document.getElementById('ticket-form-loaded-at');
  loadedAt.value = Date.now();

  function currentPrice() {
    const starts = new Date('2026-09-08T00:00:00-04:00');
    return new Date() < starts ? { amount: 185, label: 'Early Bird' } : { amount: 200, label: 'Regular' };
  }
  function updateTotal() {
    const price = currentPrice();
    const count = Number(quantity.value || 1);
    priceLabel.textContent = `${price.label} · $${price.amount} per person`;
    total.textContent = `Total due: $${(price.amount * count).toLocaleString('en-US')}`;
  }
  quantity.addEventListener('change', updateTotal);
  updateTotal();

  form.addEventListener('submit', async function (event) {
    event.preventDefault(); status.textContent = '';
    const submit = form.querySelector('button[type="submit"]');
    const fd = new FormData(form);
    const payload = Object.fromEntries(fd.entries());
    payload.quantity = Number(payload.quantity);
    payload.form_loaded_at = Number(payload.form_loaded_at);
    submit.disabled = true; submit.textContent = 'Submitting…';
    try {
      const response = await fetch('/ticket-order.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.message || 'Your order could not be submitted.');
      form.hidden = true; success.hidden = false;
      successCopy.textContent = `${data.quantity} ticket(s) reserved at $${Number(data.unit_price).toFixed(2)} each. Total due: $${Number(data.total_amount).toFixed(2)}. Order number: ${data.order_code}.`;
      window.mbshAnalytics?.track('ticket_order_submitted', {
        quantity: Number(data.quantity), value: Number(data.total_amount), currency: 'USD', payment_status: 'pending'
      });
    } catch (error) {
      status.textContent = `${error.message} Please try again or email committee@mbsh96reunion.com.`;
      submit.disabled = false; submit.textContent = 'Submit Ticket Order';
    }
  });
})();
