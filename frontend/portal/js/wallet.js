(function(){
  'use strict';
  let selected='';
  const area=document.querySelector('[data-ticket-data]');
  const template=area?.innerHTML;
  window.renderReunionWallet=function(rows){
    if(!area)return;
    const active=rows.filter(t=>t.status==='active');
    const set=(selector,value)=>{const el=document.querySelector(selector);if(el)el.textContent=value;};
    document.querySelector('[data-ticket-verified]')?.toggleAttribute('hidden',!active.length);
    set('[data-home-ticket-title]',active.length?`${active.length} admission${active.length===1?'':'s'} ready`:rows.length?'Ticket is not active':'No ticket issued yet');
    set('[data-home-ticket-copy]',active.length?'Open your wallet to view each admission.':'Your paid admissions appear here after email verification.');
    if(!rows.length){area.innerHTML='<div class="empty-state"><strong>No active ticket yet</strong><p>Use the email address from your purchase when signing in. If you still need help, contact the committee.</p></div>';return;}
    const ticket=rows.find(t=>t.public_id===selected)||active[0]||rows[0];selected=ticket.public_id;
    area.innerHTML=template;
    const label=document.createElement('label');label.textContent=`Your admissions (${rows.length}) `;
    const select=document.createElement('select');select.setAttribute('aria-label','Choose admission');
    rows.forEach((row,index)=>{const option=document.createElement('option');option.value=row.public_id;option.textContent=`Admission ${index+1} · ${row.status.replaceAll('_',' ')}`;option.selected=row.public_id===selected;select.append(option);});
    select.addEventListener('change',()=>{selected=select.value;window.renderReunionWallet(rows);area.querySelector('select')?.focus();});label.append(select);area.prepend(label);
    set('[data-ticket-holder]',ticket.holder_name);set('[data-ticket-type]',ticket.ticket_type);set('[data-ticket-id]',`•••• ${ticket.public_id.slice(-4)}`);set('[data-ticket-status]',ticket.status.replaceAll('_',' '));
    const zone=area.querySelector('.qr-zone');zone.replaceChildren();zone.setAttribute('aria-label','Private admission check-in code');
    if(ticket.status==='active'&&ticket.credential&&window.qrcode){
      const qr=window.qrcode(0,'M');qr.addData(ticket.credential);qr.make();
      zone.innerHTML=qr.createSvgTag({cellSize:4,margin:16,scalable:true,title:'Private admission check-in code'});
      zone.querySelector('svg').style.cssText='display:block;width:100%;max-width:240px;height:auto;background:white';
      const note=document.createElement('small');note.textContent='Show this live code at check-in. It refreshes automatically.';zone.append(note);
    }else{const message=document.createElement('strong');message.textContent=ticket.status==='active'?'Code unavailable — refresh to retry':ticket.status.replaceAll('_',' ');zone.append(message);}
  };
  window.renderReunionWalletError=function(){if(area)area.innerHTML='<div class="empty-state" role="alert"><strong>Your wallet is temporarily unavailable</strong><p>Reconnect and refresh to retrieve your admissions. Your purchase is preserved.</p></div>';};
  async function refresh(){if(document.hidden||!document.body.classList.contains('workspace-ready'))return;try{const data=await window.PortalApi.tickets();window.renderReunionWallet(data.tickets||[]);}catch{const zone=area?.querySelector('.qr-zone');if(zone){zone.replaceChildren();zone.textContent='Connection interrupted. Reconnect and refresh for your live code.';}}}
  setInterval(refresh,60000);document.addEventListener('visibilitychange',refresh);
})();
