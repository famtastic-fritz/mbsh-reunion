(function () {
  'use strict';
  const api = window.PortalApi, root = document.querySelector('[data-trivia-game]');
  if (!root) return;
  const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  let saving = false;
  function render(data, feedback = '') {
    if (!data.game) { root.innerHTML = `<div class="empty-state"><strong>Harry is setting the next round.</strong><p>${esc(data.message || 'The committee will open play when the questions are ready. No round is open yet.')}</p></div>`; return; }
    const leaders = (data.leaderboard || []).map((row, index) => `<li><strong>${Number(row.rank || index + 1)}. ${esc(row.display_name)}</strong><span>${Number(row.score)} points</span></li>`).join('');
    const result = leaders ? `<h4>Scores</h4><ol class="experience-leaderboard">${leaders}</ol>` : '';
    const notice = feedback ? `<p class="experience-saved" role="status">${esc(feedback)}</p>` : '';
    if (data.attempt?.status === 'completed') { root.innerHTML = `${notice}<p class="eyebrow">Your completed round</p><h3>${Number(data.attempt.score)} points</h3><p>Your answers are locked. The committee will publish the final results and announce winners.</p>${result}`; return; }
    if (data.game.status !== 'open') { root.innerHTML = `<h3>${esc(data.game.title)}</h3><p>${data.game.status === 'published' ? 'The committee has published this round’s results.' : 'This round is closed. Check the reunion experience for the next activity.'}</p>${result}`; return; }
    if (!data.attempt) { root.innerHTML = `<p class="eyebrow">Open round</p><h3>${esc(data.game.title)}</h3><p>${esc(data.game.instructions)}</p><p>One attempt per ticket holder. Each answer is final. Complete the round before the committee closes play.</p><button class="button button--primary" type="button" data-trivia-start>Start my attempt</button>${result}`; return; }
    const q = data.next_question;
    if (!q) { root.innerHTML = `${notice}<p>There is no question available right now.</p><button class="button" type="button" data-trivia-refresh>Refresh my round</button>`; return; }
    root.innerHTML = `${notice}<p class="eyebrow">Question ${Number(data.answered?.length || 0) + 1}</p><h3>${esc(q.prompt)}</h3><form data-trivia-answer="${esc(q.public_id)}"><fieldset><legend class="sr-only">Choose one answer</legend>${q.choices.map((choice, index) => `<label class="choice"><input type="radio" name="selected_index" value="${index}" required><span><strong>${esc(choice)}</strong></span></label>`).join('')}</fieldset><button class="button button--primary" type="submit">Lock in answer</button></form><p><strong>Current score:</strong> ${Number(data.attempt.score)}</p>`;
  }
  function error(error) { let output = root.querySelector('[data-trivia-error]'); if (!output) { output = document.createElement('p'); output.dataset.triviaError = ''; root.prepend(output); } output.setAttribute('role', 'alert'); output.className = 'form-error'; output.textContent = error.message || 'That answer could not be saved. Try again; it will not be counted twice.'; }
  async function load() { try { render(await api.trivia()); } catch (e) { error(e); } }
  async function action(data) { if (saving) return; saving = true; root.setAttribute('aria-busy', 'true'); const buttons = [...root.querySelectorAll('button')]; buttons.forEach(b => b.disabled = true); try { const result = await api.triviaAction(data); render(result, data.action === 'answer' ? `${result.correct ? 'Correct!' : 'Answer recorded.'} ${result.explanation || ''}` : ''); } catch (e) { error(e); } finally { saving = false; root.removeAttribute('aria-busy'); buttons.forEach(b => b.disabled = false); } }
  root.addEventListener('click', event => { if (event.target.closest('[data-trivia-start]')) action({action:'start'}); if (event.target.closest('[data-trivia-refresh]')) load(); });
  root.addEventListener('submit', event => { const form = event.target.closest('[data-trivia-answer]'); if (!form) return; event.preventDefault(); action({action:'answer',question_id:form.dataset.triviaAnswer,selected_index:Number(new FormData(form).get('selected_index'))}); });
  window.addEventListener('mbsh:experience-access', event => { if (event.detail.entitled) load(); else root.innerHTML = '<p>A valid paid mixer ticket and verified account are needed to play. Your wallet and account support are still available.</p>'; });
})();
