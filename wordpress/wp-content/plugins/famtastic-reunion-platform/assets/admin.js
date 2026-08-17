(() => {
  'use strict';
  const commandCenter = document.querySelector('#famtastic_reunion_command_center');
  const normalColumn = document.querySelector('#normal-sortables');
  if (commandCenter && normalColumn && normalColumn.firstElementChild !== commandCenter) {
    normalColumn.prepend(commandCenter);
  }
  const guide = document.querySelector('[data-famtastic-harry-guide]');
  if (!guide) return;
  const toggle = guide.querySelector('.famtastic-harry-toggle');
  const panel = guide.querySelector('#famtastic-harry-panel');
  const close = guide.querySelector('.famtastic-harry-close');
  const context = guide.querySelector('[data-harry-context]');
  const answer = guide.querySelector('[data-harry-wp-answer]');
  const screen = guide.dataset.screen || '';
  const screenGuidance = screen.includes('famtastic-growth-delivery') ? 'This is the owner’s growth and delivery map. Marketing requires explicit portal consent; transactional email never depends on promotional subscription.' : screen.includes('reunion_memory') ? 'This is the memory archive. Review rights, context, and sensitive details before publication.' : screen.includes('reunion_faq') ? 'This is Harry’s approved knowledge library. Keep answers factual, reusable, and committee-approved.' : screen.includes('shop_order') || screen.includes('woocommerce') ? 'This is financial truth. Use WooCommerce order and refund actions rather than editing history.' : screen.includes('reunion_ticket') ? 'This is ticket administration. A valid paid order or documented owner exception must exist before admission is issued.' : 'Start with exceptions and use the branded Admin Portal for attendee operations, replies, moderation, and permissions.';
  context.textContent = screenGuidance;
  const answers = [
    [/memory|photo|video|approve/, 'Open Media Archive or the branded Admin Portal’s Media approvals. Verify consent, caption, people, and sensitive details.'],
    [/question|harry|faq|answer/, 'Use FAQs & Harry Knowledge for approved reusable answers. Use the Admin Portal Harry Desk for unanswered classmate questions.'],
    [/ticket|check.?in|admission/, 'Use the Admin Portal for check-in and exceptions. Use WooCommerce here to verify the paid order and refund lifecycle.'],
    [/order|payment|refund|stripe/, 'WooCommerce is the financial authority. Never infer payment from a portal screen or edit completed financial history directly.'],
    [/page|component|content|announcement/, 'Edit the structured record here, preview the cinematic frontend, then verify mobile, CTA behavior, and the connected workflow before publishing.'],
    [/person|rsvp|dinner|message|reply/, 'Use the branded Admin Portal for people, RSVP, dinner, messages, and replies. It joins the attendee record without exposing WordPress.'],
    [/role|permission|committee|access/, 'Use the Admin Portal Users & permissions screen. Grant the smallest useful role and confirm revocation in the audit log.'],
    [/campaign|marketing|newsletter|promotion|email/, 'Use Growth & Delivery to confirm consent synchronization, then compose in FluentCRM. Only the Reunion Community Updates list is eligible for promotional sends. Use Resend-backed WordPress mail for delivery.'],
    [/cron|worker|schedule|failure/, 'Open Growth & Delivery, then Scheduled Work. Check the worker heartbeat, late events, and failed Action Scheduler jobs before trusting an automation.'],
    [/form|survey|poll|volunteer/, 'Use Fluent Forms only for committee-created public forms and surveys. Attendee registration, preferences, uploads, payments, and tickets stay in their authoritative systems.']
  ];
  toggle.addEventListener('click', () => { const open = panel.hidden; panel.hidden = !open; toggle.setAttribute('aria-expanded', String(open)); if (open) panel.querySelector('input').focus(); });
  close.addEventListener('click', () => { panel.hidden = true; toggle.setAttribute('aria-expanded', 'false'); toggle.focus(); });
  guide.querySelector('[data-harry-wp-form]').addEventListener('submit', event => { event.preventDefault(); const question = String(new FormData(event.currentTarget).get('question') || '').toLowerCase(); const match = answers.find(([pattern]) => pattern.test(question)); answer.innerHTML = `<strong>Harry’s answer</strong><p>${match ? match[1] : screenGuidance}</p>`; });
})();
