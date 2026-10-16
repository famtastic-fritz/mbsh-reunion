import fs from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const source=fs.readFileSync(new URL('../../frontend/js/ticket-order.js',import.meta.url),'utf8');
for (const host of ['127.0.0.1','localhost','mbsh96reunion.com']) {
  const handlers={};const controls=[{disabled:false},{disabled:false}];const button={disabled:false,textContent:''};controls.push(button);
  const form={querySelector:()=>button,querySelectorAll:()=>controls,addEventListener:(name,fn)=>handlers[name]=fn};
  const nodes={'ticket-order-form':form,'ticket-quantity':{value:'2',addEventListener:()=>{}},'ticket-order-total':{},'ticket-price-label':{},'ticket-order-status':{},'ticket-order-success':{},'ticket-order-success-copy':{},'ticket-checkout-link':{},'ticket-form-loaded-at':{}};
  let posts=0;
  vm.runInNewContext(source,{window:{location:{hostname:host,search:''}},document:{getElementById:id=>nodes[id]},URLSearchParams,Date,fetch:()=>{posts++;throw Error('Unexpected payment request');}});
  assert.equal(nodes['ticket-order-total'].textContent,'Checkout total: $60.00');
  if(host!=='mbsh96reunion.com'){
    assert.ok(controls.every(x=>x.disabled));
    await handlers.submit({preventDefault(){}});
    assert.equal(posts,0,'local visual preview must never create an order or open payment');
    assert.match(nodes['ticket-order-status'].textContent,/preview only/);
  }else assert.ok(controls.every(x=>!x.disabled),'production ticket entry must remain available');
}
console.log('PASS local preview: no purchaser entry or payment requests; quantity review and production entry preserved');
