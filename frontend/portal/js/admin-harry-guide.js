(function () {
  'use strict';
  const form = document.querySelector('[data-harry-admin-ask]');
  const answer = document.querySelector('[data-harry-admin-answer]');
  if (!form || !answer) return;

  const routes = [
    [/trivia|game|question|score|leaderboard/, ['trivia', 'Open Trivia studio. Build the round in draft, publish reviewed questions, then open exactly one game. Never expose correct answers to attendee payloads.']],
    [/message|reply|inbox|question/, ['messages', 'Open Messages, select the classmate’s thread, read the full timeline, reply, and set the truthful waiting status.']],
    [/harry|faq|knowledge/, ['harry', 'Open the Hi-Tide Harry Desk. Answer the person first; promote repeatable guidance only after the committee confirms it.']],
    [/photo|video|memory|approve|media/, ['review', 'Open Media approvals. Verify permission, caption, people, and sensitive details before approving or returning the submission.']],
    [/ticket|check.?in|admission|scan|void/, ['tickets', 'Open Tickets & check-in. Confirm the authoritative paid order or documented exception before issuing, scanning, or voiding admission.']],
    [/dinner|meal|diet|allerg|accessib/, ['dinner', 'Open Dinner & dietary, then inspect the attendee record. Treat dietary and accessibility details as private operations data.']],
    [/person|people|rsvp|guest|attendee/, ['people', 'Open People & RSVP, search the classmate, and reconcile the current portal response with the read-only production history.']],
    [/page|component|content|announcement/, ['content', 'Open Content for the real CMS registry. Site Owners use Full Site Admin for publishing; committee access stays within assigned capabilities.']],
    [/email|delivery|worker|failed|retry/, ['delivery', 'Site Owners can open Email & workers to inspect queued, retried, failed, and dead-letter states. A sent event is not proof of inbox delivery.']],
    [/role|permission|committee|owner|access/, ['access', 'Site Owners use Users & permissions. Make the person an attendee first, then grant the smallest committee role required.']],
    [/audit|import|migration|production|data/, ['audit', 'Site Owners use Audit & data to compare authorities, preserve source identifiers, and verify rollback before migration.']]
  ];

  form.addEventListener('submit', event => {
    event.preventDefault();
    const question = String(new FormData(form).get('question') || '').trim().toLowerCase();
    const match = routes.find(([pattern]) => pattern.test(question));
    const current = location.hash.slice(1) || 'command';
    const result = match ? match[1] : [current, 'Use the lesson and checklist above first. If the task changes data, confirm the record, authority, permission, and expected notification before acting.'];
    answer.hidden = false;
    answer.innerHTML = `<strong>Harry’s answer</strong><p>${result[1]}</p><a href="#${result[0]}" data-section="${result[0]}">Open the right workspace →</a>`;
  });
})();
