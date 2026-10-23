<?php
declare(strict_types=1);
require __DIR__.'/../../backend/lib/campaign-worker.php';
function check(bool $ok,string $label):void{if(!$ok)throw new RuntimeException($label);echo "PASS $label\n";}
$at=strtotime('2026-10-23T22:00:00Z');$email='person@example.test';$hash=hash('sha256',$email);
$m=['schema'=>1,'id'=>'unlock-the-night-20261023','activation'=>'active','waves'=>[['id'=>'friday','at'=>'2026-10-23T22:00:00Z'],['id'=>'sunday','at'=>'2026-10-25T15:00:00Z'],['id'=>'monday','at'=>'2026-10-26T14:00:00Z']],'recipients'=>[['email'=>$email,'sha256'=>$hash]]];
$time=$at-1;$s=[];$sent=[];$saved=[];$buyer=false;$suppressed=false;$fail=false;$attemptKeys=[];
$clock=function()use(&$time){return $time;};
$eligible=function()use(&$buyer,&$suppressed){return $buyer?'paid_mixer_buyer':($suppressed?'provider_suppression':null);};
$payload=fn($w,$e)=>['to'=>[$e],'subject'=>$w['id'],'html'=>'approved content'];
$persist=function($v)use(&$saved){$saved[]=$v;};
$send=function($p,$k)use(&$sent,&$saved,&$fail,&$attemptKeys){$attemptKeys[]=$k;check(end($saved)['items']['friday:'.hash('sha256','person@example.test')]['status']==='sending'||$p['subject']!=='friday','durable record precedes provider');if($fail)throw new RuntimeException('timeout after possible acceptance');$sent[]=$p;return ['id'=>'provider-'.count($sent)];};
$run=function()use(&$m,&$s,$clock,$eligible,$payload,$send,$persist){return fam_campaign_tick($m,$s,$clock,$eligible,$payload,$send,$persist);};
$run();check(count($sent)===0&&count($s['items'])===0,'no early send');
$time=$at;$run();check(count($sent)===1,'exact UTC Friday due');$run();check(count($sent)===1,'replay sends no duplicate');
$buyer=true;$time=strtotime($m['waves'][1]['at']);$run();check(count($sent)===1&&$s['items']['sunday:'.$hash]['reason']==='paid_mixer_buyer','purchase between waves suppresses Sunday');
$buyer=false;$suppressed=true;$time=strtotime($m['waves'][2]['at']);$run();check(count($sent)===1&&$s['items']['monday:'.$hash]['reason']==='provider_suppression','new suppression blocks Monday');
$s=[];$sent=[];$suppressed=false;$time=$at;$fail=true;$run();$first=$s['items']['friday:'.$hash];check($first['status']==='retry'&&$first['attempts']===1,'unknown result enters bounded retry');
$run();check($s['items']['friday:'.$hash]['attempts']===1,'retry waits until backoff');
$time+=60;$fail=false;$run();$last=$s['items']['friday:'.$hash];check($last['idempotency_key']===$first['idempotency_key']&&$last['payload']===$first['payload'],'retry preserves exact payload and idempotency key');
$s=[];$time=$at;$fail=true;$run();$buyer=true;$time+=60;$run();check($s['items']['friday:'.$hash]['status']==='skipped'&&$s['items']['friday:'.$hash]['reason']==='paid_mixer_buyer','purchase before retry prevents submission');
$buyer=false;$s=[];$fail=false;$time=$at+3600;$before=count($sent);$run();check(count($sent)===$before&&$s['items']['friday:'.$hash]['status']==='missed','late worker does not compress campaign cadence');
$s=[];$m['activation']='held';$time=$at;$run();check(count($s['items'])===0,'held manifest never submits');
$m['activation']='active';$s=[];$time=$at;$fail=true;for($i=0;$i<5;$i++){$run();$time=$s['items']['friday:'.$hash]['next_attempt_at'];}check($s['items']['friday:'.$hash]['status']==='dead'&&$s['items']['friday:'.$hash]['attempts']===5,'five failed attempts enter dead-letter state');
// Persisted sending state models process death between provider acceptance and local acknowledgement.
$s=[];$time=$at;$fail=true;$run();$s['items']['friday:'.$hash]['status']='sending';$key=$s['items']['friday:'.$hash]['idempotency_key'];$time+=60;$fail=false;$run();check($s['items']['friday:'.$hash]['idempotency_key']===$key,'crash recovery retains provider deduplication key');
$bad=$m;$bad['recipients'][0]['email']='different@example.test';$s=[];$threw=false;try{fam_campaign_tick($bad,$s,$clock,$eligible,$payload,$send,$persist);}catch(RuntimeException $e){$threw=true;}check($threw,'tampered recipient identity fails closed');
