(function () {
  'use strict';
  const api = window.PortalApi;
  const $ = (s, root = document) => root.querySelector(s);
  const $$ = (s, root = document) => [...root.querySelectorAll(s)];
  const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  let entitled = false;
  const accessCopy = '<strong>Your ticket unlocks participation.</strong><p>Use a verified account with the same email as a valid paid mixer ticket. Pending, unpaid or refunded tickets do not unlock activities. Your wallet, account and committee messages remain available.</p><div class="action-row"><a class="button button--primary" href="/tickets.html#ticket-order">Get My $30 Ticket</a><a class="button" href="#ticket">Check my ticket</a><a class="button" href="#inbox">Ask the committee</a></div>';
  function safePreview(value) { try { const url = new URL(value, location.origin); return url.origin === location.origin && url.protocol.startsWith('http') ? url.href : ''; } catch (_) { return ''; } }
  function leaderboard(rows = []) { return rows.length ? '<ol class="experience-leaderboard">' + rows.map((row, i) => `<li><strong>${Number(row.rank || i + 1)}. ${escape(row.display_name)}</strong><span>${Number(row.score)} points</span></li>`).join('') + '</ol>' : '<p>No completed entries in this round.</p>'; }
  function message(form, text, error = false) {
    let output = $('[data-experience-feedback]', form);
    if (!output) { output = document.createElement('p'); output.dataset.experienceFeedback = ''; form.append(output); }
    output.className = error ? 'form-error' : 'experience-saved'; output.setAttribute('role', error ? 'alert' : 'status'); output.textContent = text;
  }
  async function save(form, work) {
    if (form.dataset.saving === 'true') return;
    form.dataset.saving = 'true'; form.setAttribute('aria-busy', 'true');
    const controls = $$('button', form); controls.forEach(b => b.disabled = true);
    try { await work(); } catch (error) { message(form, error.message || 'The request could not be saved. Your entries are still here; try again.', true); }
    finally { form.dataset.saving = 'false'; form.removeAttribute('aria-busy'); controls.forEach(b => b.disabled = false); }
  }
  function gate(data) {
    entitled = data.entitled === true;
    const unavailable = data.eligibility?.state === 'unavailable';
    const copy = entitled ? '<strong>Your ticket-holder experience is open.</strong><p>Choose an activity below. Rounds open when the committee is ready.</p>' : unavailable ? '<strong>Ticket access is temporarily unavailable.</strong><p>Refresh to try again. Your ticket and account have not changed.</p><a class="button" href="#ticket">Open my wallet</a>' : accessCopy;
    $('[data-experience-status]').innerHTML = `<div class="experience-access-notice">${copy}</div>`;
    $('[data-experience-home]').innerHTML = entitled ? '<strong>Welcome to your reunion experience.</strong><p>Play, share and reconnect before and during the mixer.</p><a class="button button--primary" href="#experience">Open My Reunion Experience</a>' : copy;
    $('[data-original-next-action]').hidden = true;
    $$('[data-experience-gate]').forEach(node => { node.hidden = entitled; node.innerHTML = copy; });
    $$('[data-participation-fields]').forEach(node => node.disabled = !entitled);
    for (const form of $$('[data-demo-form="upload"], [data-demo-form="song"], [data-demo-form="idea"]')) $$('input,textarea,button,select', form).forEach(node => node.disabled = !entitled);
    window.dispatchEvent(new CustomEvent('mbsh:experience-access', {detail:{entitled}}));
  }
  function render(data) {
    gate(data);
    const activities = entitled ? data.current_activities || [] : [];
    if (activities.length) $('[data-experience-home]').innerHTML += `<p class="experience-saved"><strong>Open now:</strong> ${activities.map(a => escape(a.title)).join(' · ')}</p>`;
    $('[data-experience-now]').innerHTML = activities.length ? `<p class="eyebrow">Open now</p>${activities.map(a => `<article><h3>${escape(a.title)}</h3><a class="button button--primary" href="#${a.type === 'guess_who' ? 'guess-who' : 'trivia'}">${a.type === 'guess_who' ? 'Play Guess Who?' : 'Play trivia'}</a></article>`).join('')}` : '<p class="eyebrow">Before the next round</p><h3>The committee will call the next game.</h3><p>There is no open round right now. Ticket holders can prepare their clues, share a photo, and send song or game ideas. Refresh here during the mixer.</p>';
    const results = [...(data.results?.trivia || []).map(x => ({...x, type:'1996 Trivia'})), ...(data.results?.guess_who || []).map(x => ({...x, type:'Guess Who?'}))];
    $('[data-experience-results]').innerHTML = results.length ? results.map(round => `<article class="experience-result"><p class="eyebrow">${round.type} · Results published</p><h4>${escape(round.title)}</h4>${(round.winners || []).length ? `<p class="experience-winner">${round.winners.length > 1 ? 'Joint winners' : 'Winner'}: ${round.winners.map(w => escape(w.display_name)).join(', ')}</p>` : '<p>No winner announced.</p>'}${leaderboard(round.leaderboard)}</article>`).join('') : '<p>No winners have been announced yet. The committee publishes results after each round closes.</p>';
    $('[data-experience-gallery]').innerHTML = (data.gallery || []).length ? data.gallery.map(photo => {const src = safePreview(photo.preview_url); return `<figure>${src ? `<img src="${escape(src)}" alt="${escape(photo.title)}" loading="lazy">` : ''}<figcaption><strong>${escape(photo.title)}</strong><p>${escape(photo.caption)}</p></figcaption></figure>`; }).join('') : '<p>No approved photos yet. Your own uploads and their review status are in Photos &amp; Memories.</p>';
  }
  function renderGuess(data) {
    const own = data.submission, form = $('[data-guess-submission]');
    const locked = own?.editable === false;
    for (let i = 1; i <= 4; i++) $(`[name="clue_${i}"]`, form).value = own?.clues?.[i - 1] || '';
    $('[name="consent_to_publish"]', form).checked = !!Number(own?.consent_to_publish);
    $('[data-participation-fields]', form).disabled = !entitled || locked;
    const status = $('[data-guess-submission-status]');
    status.innerHTML = own ? `<strong>Saved · ${escape(own.status.replaceAll('_', ' '))}</strong>${own.moderation_note ? `<p>Committee note: ${escape(own.moderation_note)}</p>` : ''}<p>${locked ? 'Your clues are in a round and are locked for fair play. Withdraw to remove the affected round from play and results, then submit corrected clues.' : 'You can edit these clues or withdraw your entry before it enters a round.'}</p>${own.status !== 'withdrawn' ? '<button class="text-button" type="button" data-guess-withdraw>Withdraw my clues</button>' : ''}` : '<p>No clues submitted yet.</p>';
    const round = data.round;
    if (!round) { $('[data-guess-round]').innerHTML = '<p>No Guess Who? round is open yet. The committee will select approved clues and open play.</p>'; return; }
    $('[data-guess-round]').innerHTML = `<p class="eyebrow">${escape(round.status)}</p><h4>${escape(round.title)}</h4><p>${round.status === 'open' ? 'One final guess per entry. Enter the person’s first and last name; use their current or maiden name. No answer hints are shown before the reveal.' : round.status === 'closed' ? 'Guesses are closed. The committee is preparing the reveal.' : 'The committee has published the answers and results.'}</p><div class="experience-clue-grid">${(round.cards || []).map(card => `<article class="experience-clue-card"><h4>Who am I?</h4><ol>${card.clues.map(clue => `<li>${escape(clue)}</li>`).join('')}</ol>${round.status === 'published' ? `<p class="experience-reveal">${escape(card.reveal_name || 'Answer withheld')}</p>` : card.is_own ? '<p>Your entry. You cannot guess yourself.</p>' : card.answered ? '<p class="experience-saved">Your guess is saved and final.</p>' : round.status === 'open' ? `<form data-guess-answer="${escape(card.public_id)}" data-round="${escape(round.public_id)}"><label>Classmate’s first and last name<input name="answer" required maxlength="200" autocomplete="off"></label><button class="button" type="submit">Lock in my guess</button></form>` : '<p>This round is closed.</p>'}</article>`).join('')}</div>${round.status === 'published' ? `<h4>Final scores</h4>${leaderboard(round.leaderboard || [])}` : ''}`;
  }
  async function loadGuess() { try { renderGuess(await api.guessWho()); } catch (error) { $('[data-guess-round]').innerHTML = `<p role="alert">${escape(error.message)} Refresh the experience to try again.</p>`; } }
  async function load() { const button = $('[data-refresh-experience]'); button.disabled = true; try { render(await api.experience()); await loadGuess(); } catch (error) { gate({entitled:false,eligibility:{state:'unavailable'}}); $('[data-experience-now]').innerHTML = `<p role="alert">${escape(error.message)} Refresh to try again.</p>`; } finally { button.disabled = false; } }
  $('[data-refresh-experience]')?.addEventListener('click', load);
  $('[data-guess-submission]')?.addEventListener('submit', event => {
    event.preventDefault(); const form = event.currentTarget;
    save(form, async () => { const fd = new FormData(form); const clues = [1,2,3,4].map(i => String(fd.get(`clue_${i}`) || '').trim()).filter(Boolean); await api.guessWhoAction({action:'submit',clues,consent_to_publish:fd.has('consent_to_publish')}); await loadGuess(); message(form, 'Your clues are saved for committee review.'); });
  });
  document.addEventListener('click', event => { if (!event.target.closest('[data-guess-withdraw]')) return; if (!confirm('Withdraw your clues? If they are in a round, that round will be removed from play and results for everyone. You can submit corrected clues afterward.')) return; const form = $('[data-guess-submission]'); save(form, async () => { await api.guessWhoAction({action:'withdraw'}); await loadGuess(); message(form, 'Your entry has been withdrawn.'); }); });
  $('[data-guess-round]')?.addEventListener('submit', event => {
    const form = event.target.closest('[data-guess-answer]'); if (!form) return; event.preventDefault();
    save(form, async () => { await api.guessWhoAction({action:'guess',round_id:form.dataset.round,card_id:form.dataset.guessAnswer,answer:String(new FormData(form).get('answer') || '').trim()}); await loadGuess(); });
  });
  window.addEventListener('mbsh:portal-ready', load, {once:true});
})();
