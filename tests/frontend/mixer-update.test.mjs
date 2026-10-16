import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
import assert from 'node:assert/strict';
const root=path.resolve(import.meta.dirname,'../..');
const read=p=>fs.readFileSync(path.join(root,p),'utf8');
const config=JSON.parse(read('config/site-config.json'));
for(const p of ['frontend/config/site-config.json','backend/config/site-config.json'])assert.deepEqual(JSON.parse(read(p)),config,'Public/server offer configuration drift');
assert.equal(Date.parse(config.REUNION_END_AT)-Date.parse(config.REUNION_START_AT),3*3600000,'Mixer must run three hours');
// Catch the November EST/EDT mistake with an actual countdown execution.
const elements=Object.fromEntries(['days','hours','mins','secs'].map(k=>[`countdown-${k}`,{textContent:''}]));
const events={};let intervals=0;
vm.runInNewContext(read('frontend/js/countdown.js'),{
 window:{__SITE_CONFIG__:config},document:{getElementById:id=>elements[id],addEventListener:(name,fn)=>events[name]=fn},
 Date:{parse:Date.parse,now:()=>Date.parse('2026-11-07T23:00:00Z')},setInterval:()=>++intervals
});
assert.equal(elements['countdown-hours'].textContent,'01','7 PM EST is 00:00 UTC, not 23:00 UTC');
events['siteconfig:loaded']();events['siteconfig:loaded']();assert.equal(intervals,1,'Config reload must not duplicate countdown timers');
for(const file of ['index.html','tickets.html','through-years.html','memorial.html','capsule.html','playlist.html']){
 const html=read('frontend/'+file);
 assert.doesNotMatch(html,/Miami Shores|Country Club|Black-tie|\$185|\$200|190\.67/,'Old offer on '+file);
 assert.equal((html.match(/property="og:image"/g)||[]).length,1,'Duplicate social image on '+file);
 const graph=JSON.parse(html.match(/<script type="application\/ld\+json">(.*?)<\/script>/s)[1]);
 const event=graph['@graph'].find(x=>x['@type']==='Event');
 assert.equal(event.location.name,'Twisted Tonic');assert.equal(event.offers.price,30);assert.equal(event.startDate,config.REUNION_START_AT);assert.equal(event.endDate,config.REUNION_END_AT);
}
const portal=read('frontend/portal/index.html');assert.doesNotMatch(portal,/Miami Shores|Country Club|Black-tie|6:30 PM|11:45 PM|name="meal"/);
assert.match(read('backend/portal/my-event.php'),/meal_selection_closed/);
assert.doesNotMatch(read('backend/portal/my-event.php'),/SET[^;]*meal_choice=\?/,'New RSVP writes must not erase historical meals');
console.log('PASS mixer content: six active routes, event times/offer schema, duplicate metadata, portal retirement and actual EST countdown');
