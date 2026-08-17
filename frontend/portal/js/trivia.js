(function () {
  'use strict';
  const api=window.PortalApi,root=document.querySelector('[data-trivia-game]');
  if(!root)return;
  const esc=value=>{const node=document.createElement('div');node.textContent=String(value??'');return node.innerHTML};
  function render(data){
    if(!data.game){root.innerHTML=`<div class="empty-state"><strong>Harry is setting the next round.</strong><p>${esc(data.message||'Check back soon.')}</p></div>`;return;}
    const leaders=(data.leaderboard||[]).map((row,index)=>`<li><strong>${index+1}. ${esc(row.display_name)}</strong><span>${Number(row.score)} points</span></li>`).join('');
    if(!data.attempt){root.innerHTML=`<p class="eyebrow">Open round</p><h3>${esc(data.game.title)}</h3><p>${esc(data.game.instructions)}</p><button class="button button--primary" type="button" data-trivia-start>Start my attempt</button><h4>Leaderboard</h4><ol class="trivia-leaderboard">${leaders||'<li>No completed attempts yet.</li>'}</ol>`;return;}
    if(data.attempt.status==='completed'){root.innerHTML=`<p class="eyebrow">Final score</p><h3>${Number(data.attempt.score)} points</h3><p>Your answers are locked. Thanks for playing fairly.</p><h4>Leaderboard</h4><ol class="trivia-leaderboard">${leaders||'<li>You set the pace.</li>'}</ol>`;return;}
    const q=data.next_question;if(!q){root.innerHTML='<p class="empty-state">Finalizing your score…</p>';return;}
    root.innerHTML=`<p class="eyebrow">${Number(data.answered?.length||0)+1} answered next</p><h3>${esc(q.prompt)}</h3><form data-trivia-answer="${esc(q.public_id)}"><fieldset><legend class="sr-only">Choose one answer</legend>${q.choices.map((choice,index)=>`<label class="choice"><input type="radio" name="selected_index" value="${index}" required><span><strong>${esc(choice)}</strong></span></label>`).join('')}</fieldset><button class="button button--primary" type="submit">Lock in answer</button></form><p><strong>Current score:</strong> ${Number(data.attempt.score)}</p>`;
  }
  async function load(){try{render(await api.trivia())}catch(error){root.innerHTML=`<div class="empty-state"><strong>Trivia is unavailable.</strong><p>${esc(error.message)}</p></div>`}}
  root.addEventListener('click',async event=>{if(!event.target.closest('[data-trivia-start]'))return;event.target.disabled=true;render(await api.triviaAction({action:'start'}));});
  root.addEventListener('submit',async event=>{const form=event.target.closest('[data-trivia-answer]');if(!form)return;event.preventDefault();const selected=Number(new FormData(form).get('selected_index'));const result=await api.triviaAction({action:'answer',question_id:form.dataset.triviaAnswer,selected_index:selected});alert(`${result.correct?'Correct!':'Not quite.'} ${result.explanation||''}`);render(result);});
  load();
})();
