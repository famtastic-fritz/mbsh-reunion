import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';

const source = file => readFileSync(new URL(`../../${file}`, import.meta.url), 'utf8');
const flush = () => new Promise(resolve => setImmediate(resolve));
function node() { return {hidden:true,innerHTML:'',textContent:'',href:'',disabled:false,dataset:{},listeners:{},children:new Map(),addEventListener(type, fn){this.listeners[type]=fn;},querySelector(s){if (!this.children.has(s))this.children.set(s,node());return this.children.get(s);},querySelectorAll(){return [];},setAttribute(k,v){this[k]=v;},removeAttribute(k){delete this[k];},contains(){return false;},focus(){this.focused=true;}}; }
function invitation(data, {denied=false, storageThrows=false, page='home', modals=[]}={}) {
  const nodes = new Map(), get = s => {if(!nodes.has(s))nodes.set(s,node());return nodes.get(s);};
  const cta = get('[data-experience-cta]');cta.textContent='Get My $30 Ticket';cta.href='/tickets.html#ticket-order';
  const timers=[], writes=[], listeners={};
  const context={URL,Date,getComputedStyle:modal=>modal.style,localStorage:{getItem(){if(storageThrows)throw Error('blocked');return null;},setItem(...v){writes.push(v);}},document:{hidden:false,body:{dataset:{page}},activeElement:{matches:()=>false},querySelector:s=>s.startsWith('dialog')?null:get(s),querySelectorAll:s=>s==='[data-experience-cta]'?[cta]:s.startsWith('dialog')?modals:[],addEventListener:(name,fn)=>listeners[name]=fn},setTimeout:fn=>timers.push(fn),fetch:async()=>({ok:!denied,json:async()=>data})};
  vm.runInNewContext(source('frontend/js/reunion-experience-invitation.js'),context);
  return {nodes,get,cta,timers,writes,listeners};
}
test('public ticket path survives anonymous, failed and non-entitled checks', async()=>{
  for(const scenario of [{denied:true},{data:{entitled:false}},{data:{entitled:'true'}}]) {
    const view=invitation(scenario.data,scenario);await flush();assert.equal(view.cta.textContent,'Get My $30 Ticket');assert.equal(view.cta.href,'/tickets.html#ticket-order');
  }
});
test('only explicit live entitlement changes public CTA to private experience', async()=>{
  const view=invitation({entitled:true});await flush();assert.equal(view.cta.textContent,'Open My Reunion Experience');assert.equal(view.cta.href,'/portal/#experience');
});
test('announcement stays nonmodal, remembered and restricted to homepage',()=>{
  const view=invitation(null);assert.equal(view.get('[data-reunion-announcement]').hidden,true);view.timers[0]();assert.equal(view.get('[data-reunion-announcement]').hidden,false);
  view.get('[data-reunion-announcement]').querySelector('[data-dismiss-announcement]').listeners.click();assert.equal(view.get('[data-reunion-announcement]').hidden,true);assert.equal(view.writes.length,1);assert.equal(view.writes[0][0],'mbsh-reunion-experience-announcement-v1');
  assert.equal(invitation(null,{page:'tickets'}).timers.length,0);assert.equal(invitation(null,{storageThrows:true}).timers.length,0);
});
test('public benefits and purchase path are present without JavaScript',()=>{
  const html=source('frontend/index.html');for(const label of ['Play. Share.','1996 Trivia','Guess Who?','Photos &amp; Memories','Request a Song','Suggest a Game','Get My $30 Ticket'])assert.ok(html.includes(label));assert.match(html,/id="reunion-experience"/);assert.doesNotMatch(html,/Miami Shores/);
});
test('new writes use same-origin session CSRF and preserve exact payload',async()=>{
  const calls=[],window={MBSH_PORTAL_CONFIG:{apiBase:'/portal',mode:'api'}};
  const fetch=async(url,opts)=>{calls.push({url,opts});return {ok:true,status:200,headers:new Headers({'content-type':'application/json'}),json:async()=>url.endsWith('session.php')?{csrf_token:'test-csrf',authenticated:true}:{ok:true}};};
  vm.runInNewContext(source('frontend/portal/js/portal-api.js'),{window,Headers,fetch});
  const payload={action:'guess',round_id:'round',card_id:'card',answer:'Dana Sample'};
  await window.PortalApi.guessWhoAction(payload);await window.PortalApi.staffExperienceAction({action:'set_guess_round',id:'round',status:'closed'});
  assert.deepEqual(calls.map(c=>c.url),['/portal/session.php','/portal/guess-who.php','/portal/staff/experience.php']);
  for(const call of calls.slice(1)){assert.equal(call.opts.credentials,'same-origin');assert.equal(call.opts.method,'POST');assert.equal(call.opts.headers.get('X-CSRF-Token'),'test-csrf');}
  assert.deepEqual(JSON.parse(calls[1].opts.body),payload);
});
test('server entitlement failure is readable and not converted to success',async()=>{
  const window={MBSH_PORTAL_CONFIG:{apiBase:'/portal',mode:'api'}};
  vm.runInNewContext(source('frontend/portal/js/portal-api.js'),{window,Headers,fetch:async()=>({ok:false,status:403,headers:new Headers({'content-type':'application/json'}),json:async()=>({error:'ticket_holder_required'})})});
  await assert.rejects(window.PortalApi.experience(),e=>e.status===403&&e.code==='ticket_holder_required'&&/valid paid mixer ticket/.test(e.message));
});
function privateExperience(data, guess) {
  const nodes=new Map(),get=s=>{if(!nodes.has(s))nodes.set(s,node());return nodes.get(s);},listeners={};
  const forms=['[data-demo-form="upload"]','[data-demo-form="song"]','[data-demo-form="idea"]'].map(get);forms.forEach(f=>{f.controls=[node(),node()];f.querySelectorAll=()=>f.controls;});
  const fields=[node()],gates=[node()];let guessCalls=0;
  const window={PortalApi:{experience:async()=>data,guessWho:async()=>{guessCalls++;return guess;}},addEventListener:(name,fn)=>listeners[name]=fn,dispatchEvent(){}};
  const document={querySelector:get,querySelectorAll:s=>s==='[data-participation-fields]'?fields:s==='[data-experience-gate]'?gates:s.startsWith('[data-demo-form=')?forms:[],addEventListener(){},createElement:()=>node()};
  vm.runInNewContext(source('frontend/portal/js/experience.js'),{window,document,location:{origin:'https://example.test'},URL,CustomEvent:class{constructor(type,init){this.type=type;this.detail=init.detail;}},confirm:()=>true});
  return {get,fields,forms,gates,listeners,guessCalls:()=>guessCalls};
}
test('registered nonbuyer remains gated but can retrieve own entry for withdrawal',async()=>{
  const view=privateExperience({entitled:false,eligibility:{state:'ticket_required'}},{submission:{status:'published',editable:false,clues:['One','Two','Three'],consent_to_publish:1},round:null});
  await view.listeners['mbsh:portal-ready']();assert.equal(view.fields[0].disabled,true);assert.equal(view.forms[0].controls[0].disabled,true);assert.match(view.get('[data-experience-status]').innerHTML,/Pending, unpaid or refunded/);assert.equal(view.guessCalls(),1);assert.match(view.get('[data-guess-submission-status]').innerHTML,/data-guess-withdraw/);
});
test('entitled activity view never exposes Guess Who identity before reveal',async()=>{
  const card={public_id:'card',clues:['<img src=x onerror=alert(1)>','Two','Three'],answered:false,is_own:false,reveal_name:'Dana Secret'};
  const view=privateExperience({entitled:true,current_activities:[{type:'guess_who',title:'Mixer round'}],gallery:[{title:'Unsafe',preview_url:'javascript:alert(1)'}]},{submission:null,round:{public_id:'round',title:'Round',status:'open',cards:[card]}});
  await view.listeners['mbsh:portal-ready']();assert.equal(view.fields[0].disabled,false);const html=view.get('[data-guess-round]').innerHTML;assert.doesNotMatch(html,/Dana Secret|<img src=x/);assert.match(html,/&lt;img/);assert.match(html,/data-guess-answer/);assert.doesNotMatch(view.get('[data-experience-gallery]').innerHTML,/javascript:|<img/);
});
test('published Guess Who view renders reveal and no guessing controls',async()=>{
  const view=privateExperience({entitled:true},{submission:null,round:{public_id:'round',title:'Round',status:'published',cards:[{public_id:'card',clues:['One','Two','Three'],reveal_name:'Dana Sample'}],leaderboard:[{display_name:'D. Sample',score:100,rank:1}]}});
  await view.listeners['mbsh:portal-ready']();const html=view.get('[data-guess-round]').innerHTML;assert.match(html,/Dana Sample/);assert.match(html,/100 points/);assert.doesNotMatch(html,/data-guess-answer/);
});
test('network failure stays readable and never automatically retries a write',async()=>{
  let submissions=0;
  const window={MBSH_PORTAL_CONFIG:{apiBase:'/portal',mode:'api'}};
  vm.runInNewContext(source('frontend/portal/js/portal-api.js'),{window,Headers,fetch:async url=>{
    if(url.endsWith('session.php'))return {ok:true,status:200,headers:new Headers({'content-type':'application/json'}),json:async()=>({csrf_token:'test-csrf'})};
    submissions++;throw new TypeError('Failed to fetch');
  }});
  await assert.rejects(window.PortalApi.guessWhoAction({action:'guess'}),e=>e.code==='network_error'&&e.message==='Connection lost. Your form is still here. Try again.');
  assert.equal(submissions,1);
});

test('hidden chatbot modal does not block notice, but a visible modal does',()=>{
  const modal = ({hidden=false,display='block',visibility='visible',rects=1}={}) => ({hidden,style:{display,visibility},getClientRects:()=>Array(rects).fill({})});
  for (const hiddenPanel of [modal({hidden:true}),modal({display:'none',rects:0}),modal({visibility:'hidden'}),modal({rects:0})]) {
    const view=invitation(null,{modals:[hiddenPanel]});view.timers[0]();assert.equal(view.get('[data-reunion-announcement]').hidden,false);
  }
  const open=invitation(null,{modals:[modal({hidden:true}),modal()]});open.timers[0]();assert.equal(open.get('[data-reunion-announcement]').hidden,true);
});
