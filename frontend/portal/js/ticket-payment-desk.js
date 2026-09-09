(function () {
  'use strict';
  const form = document.querySelector('[data-confirm-payment-form]');
  if (!form || !window.PortalApi) return;

  document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-use-order]');
    if (!button) return;
    form.elements.order_code.value = button.dataset.useOrder || '';
    form.elements.payer_email.value = button.dataset.orderEmail || '';
    form.elements.amount_received.value = button.dataset.orderTotal || '';
    form.elements.amount_received.min = button.dataset.orderTotal || '0.01';
    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    form.elements.payment_reference.focus();
  });

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const fields = new FormData(form);
    const submit = form.querySelector('button[type="submit"]');
    const result = form.querySelector('[data-payment-result]');
    submit.disabled = true;
    result.textContent = 'Verifying and issuing…';
    try {
      const response = await window.PortalApi.staffAction({
        action: 'confirm_payment_link_order',
        order_code: String(fields.get('order_code')),
        payer_email: String(fields.get('payer_email')),
        payment_reference: String(fields.get('payment_reference')),
        amount_received: Number(fields.get('amount_received')),
        verified_in_stripe: fields.get('verified_in_stripe') === 'on'
      });
      result.textContent = response.fulfillment_status === 'issued'
        ? 'Payment confirmed. Branded confirmation and wallet ticket issued.'
        : 'Payment confirmed. The confirmation was sent; ticket issuance is waiting for this email to activate its reunion account.';
      setTimeout(function () { window.location.reload(); }, 2200);
    } catch (error) {
      result.textContent = error.message;
      submit.disabled = false;
    }
  });
})();
