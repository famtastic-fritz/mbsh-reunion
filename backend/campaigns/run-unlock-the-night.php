<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
$mode=$argv[1]??'status';if(!in_array($mode,['run','status','preview'],true))throw new RuntimeException('Invalid command');
$dir=$argv[2]??'';$real=realpath($dir);
if(!$real||!str_starts_with($real,'/home/nineoo/.releases/mbsh-reunion/campaigns/'))throw new RuntimeException('Private campaign directory required');
$lock=fopen($real.'/worker.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit(0);
umask(0077);
$manifest=json_decode(file_get_contents($real.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
$stateFile=$real.'/state.private.json';$state=is_file($stateFile)?json_decode(file_get_contents($stateFile),true,512,JSON_THROW_ON_ERROR):[];
require __DIR__.'/../lib/campaign-worker.php';
function campaign_save(string $path,array $data):void {$temp=$path.'.tmp';$f=fopen($temp,'wb');if(!$f)throw new RuntimeException('State unavailable');$json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";if(fwrite($f,$json)!==strlen($json)||!fflush($f))throw new RuntimeException('State write failed');if(function_exists('fsync'))fsync($f);fclose($f);chmod($temp,0600);if(!rename($temp,$path))throw new RuntimeException('Atomic state replace failed');}
function campaign_summary(array $m,array $s):array {$counts=[];foreach($s['items']??[] as $key=>$item){$wave=explode(':',$key,2)[0];$status=$item['status'];$counts[$wave][$status]=($counts[$wave][$status]??0)+1;}return ['campaign'=>$m['id'],'activation'=>$m['activation'],'waves'=>$m['waves'],'frozen_pool'=>count($m['recipients']),'heartbeat_at'=>$s['heartbeat_at']??null,'counts'=>$counts,'last_error'=>$s['last_error']??null];}
if($mode==='status'){echo json_encode(campaign_summary($manifest,$state),JSON_PRETTY_PRINT)."\n";exit;}
if($mode==='run'&&getenv('MBSH_AUTHORIZED_CAMPAIGN_WORKER')!=='1')throw new RuntimeException('Worker environment guard required');
if(!hash_equals($manifest['content_sha256'],hash_file('sha256',__DIR__.'/unlock-the-night-20261023.php')))throw new RuntimeException('Approved content hash mismatch');
foreach($manifest['layout_hashes']??[] as $name=>$hash){if(!in_array($name,['portal-email-templates.php','creator-credit.php'],true)||!hash_equals($hash,hash_file('sha256','/home/nineoo/public_html/lib/'.$name)))throw new RuntimeException('Approved email layout changed');}
$expected=['friday'=>'2026-10-23T22:00:00Z','sunday'=>'2026-10-25T15:00:00Z','monday'=>'2026-10-26T14:00:00Z'];
if(array_column($manifest['waves'],'at','id')!==$expected)throw new RuntimeException('Schedule differs from authorized three dates');
// A not-yet-due or held run does not bootstrap commerce or call external APIs.
$due=array_filter($manifest['waves'],fn($w)=>time()>=strtotime($w['at'])&&time()<strtotime($w['at'])+3600);
if($mode==='run'&&(!$due||$manifest['activation']!=='active')){
 $result=fam_campaign_tick($manifest,$state,fn()=>time(),fn()=>throw new RuntimeException('No eligible execution'),fn()=>[],fn()=>[],fn($s)=>campaign_save($stateFile,$s));echo json_encode($result)."\n";exit;
}
require '/home/nineoo/public_html/lib/config.php';require '/home/nineoo/public_html/lib/db.php';require '/home/nineoo/public_html/lib/portal-email-templates.php';require __DIR__.'/unlock-the-night-20261023.php';
$config=fam_load_config();$pdo=fam_db($config);require '/home/nineoo/public_html/cms/wp-load.php';
function campaign_provider(array $config,string $method,string $path,?array $payload=null,string $key=''):array {
 static $last=0.0;$wait=0.65-(microtime(true)-$last);if($wait>0)usleep((int)($wait*1000000));
 $ch=curl_init('https://api.resend.com/'.$path);$headers=['Authorization: Bearer '.$config['resend_api_key'],'Content-Type: application/json'];if($key!=='')$headers[]='Idempotency-Key: '.$key;
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers]);if($payload!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($payload,JSON_THROW_ON_ERROR));$raw=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_errno($ch);curl_close($ch);$last=microtime(true);
 if($err||$code<200||$code>=300)throw new RuntimeException('Provider unavailable');$r=json_decode((string)$raw,true,512,JSON_THROW_ON_ERROR);return $r;
}
function campaign_provider_suppressions(array $config):array {
 $suppressed=[];
 foreach(['suppressions','contacts'] as $endpoint){$cursor=null;for($page=0;$page<100;$page++){$r=campaign_provider($config,'GET',$endpoint.'?limit=100'.($cursor?'&after='.rawurlencode($cursor):''));if(!isset($r['data'])||!is_array($r['data']))throw new RuntimeException('Invalid suppression response');foreach($r['data'] as $row)if($endpoint==='suppressions'||!empty($row['unsubscribed']))$suppressed[strtolower(trim($row['email']??''))]=true;if(empty($r['has_more']))break;$cursor=end($r['data'])['id']??null;if(!$cursor||$page===99)throw new RuntimeException('Incomplete suppression pagination');}}
 return $suppressed;
}
require __DIR__.'/../lib/campaign-audience.php';
$previewSuppressions=$mode==='preview'?campaign_provider_suppressions($config):null;
$eligibility=function(array $recipient,array $wave)use($pdo,$config,$manifest,$real,$previewSuppressions):?string {
 $email=strtolower(trim($recipient['email']));
 if(campaign_buyer($pdo,$email))return 'paid_mixer_buyer';
 // Provider state is refreshed for every candidate and retry. Fail closed if unavailable.
 $suppressed=$previewSuppressions??campaign_provider_suppressions($config);if(isset($suppressed[$email]))return 'provider_suppression';
 $q=$pdo->prepare('SELECT email_kind,action FROM mbshwp_wc_email_unsubscribes WHERE email_hash=? ORDER BY id DESC');$q->execute([hash('sha256',$email)]);$kinds=[];foreach($q->fetchAll() as $r){if(isset($kinds[$r['email_kind']]))continue;$kinds[$r['email_kind']]=true;if($r['action']==='unsubscribed')return 'commerce_unsubscribe';}
 $q=$pdo->prepare('SELECT a.status,a.email_verified_at,a.last_login_at,p.promotional_email,p.event_updates,p.updated_at FROM attendee_accounts a LEFT JOIN attendee_preferences p ON p.attendee_id=a.id WHERE LOWER(TRIM(a.email))=?');$q->execute([$email]);$account=$q->fetch();
 if($account&&in_array($account['status'],['suspended','deleted'],true))return 'account_disabled';
 if($account && !(int)$account['event_updates'])return 'event_updates_off';
 $authorization=$manifest['audience_authorization']??null;
 $explicit=$account&&(int)$account['promotional_email']===1&&$account['status']==='active'&&!empty($account['email_verified_at']);
 $legacy=$authorization==='owner_authorized_reunion_contact_pool_with_famtastic_promotion';
 $q=$pdo->prepare('SELECT contact_pref FROM surveys WHERE LOWER(TRIM(email))=? ORDER BY id DESC LIMIT 1');$q->execute([$email]);$contactPreference=$q->fetchColumn();
 if(!$explicit&&in_array($contactPreference,['Phone','Mail'],true))return 'historical_contact_preference';
 if(!$explicit){
  if(!$legacy)return 'audience_authorization_missing';
  if($account&&(int)$account['promotional_email']===0&&(($recipient['baseline_promo']??0)===1||$account['updated_at']!==($recipient['baseline_preference_updated_at']??null)))return 'preference_changed_off';
 }
 $q=$pdo->prepare("SELECT COUNT(*) FROM portal_conversation_messages m JOIN portal_conversations c ON c.id=m.conversation_id JOIN attendee_accounts a ON a.id=c.attendee_id WHERE LOWER(TRIM(a.email))=? AND m.author_type IN ('attendee','email') AND LOWER(m.body) REGEXP 'unsubscribe|remove me|stop emailing|do not email'");$q->execute([$email]);if((int)$q->fetchColumn()>0)return 'withdrawal_review_required';
 // Refresh purchase authority last, immediately before the payload is submitted.
 if(campaign_buyer($pdo,$email))return 'paid_mixer_buyer';
 return null;
};
if($mode==='preview'){$counts=[];foreach($manifest['recipients'] as $r){$reason=$eligibility($r,$manifest['waves'][0])??'eligible';$counts[$reason]=($counts[$reason]??0)+1;}echo json_encode(['activation'=>$manifest['activation'],'frozen_pool'=>count($manifest['recipients']),'eligibility'=>$counts],JSON_PRETTY_PRINT)."\n";exit;}
$payload=function(array $wave,string $email)use($config):array{return ['from'=>'Hi-Tide Harry <'.$config['resend_from_harry'].'>','to'=>[$email],'reply_to'=>$config['resend_reply_to']??$config['committee_email'],'headers'=>['List-Unsubscribe'=>'<mailto:committee@mbsh96reunion.com?subject=Unsubscribe%20MBSH%20campaign>']]+mbsh_campaign_message($wave['id'],false);};
$result=fam_campaign_tick($manifest,$state,fn()=>time(),$eligibility,$payload,fn($p,$k)=>campaign_provider($config,'POST','emails',$p,$k),fn($s)=>campaign_save($stateFile,$s));
echo json_encode($result)."\n";
