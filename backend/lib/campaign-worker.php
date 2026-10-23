<?php
declare(strict_types=1);

/** State machine only. Authority checks and transport are injected and rerun for every attempt. */
function fam_campaign_tick(array $manifest, array &$state, callable $now, callable $eligibility, callable $payload, callable $send, callable $persist): array {
    if (($manifest['schema'] ?? null) !== 1 || ($manifest['id'] ?? '') !== 'unlock-the-night-20261023') throw new RuntimeException('Unsupported campaign');
    $state += ['schema'=>1,'campaign_id'=>$manifest['id'],'items'=>[]];
    if ($state['campaign_id'] !== $manifest['id']) throw new RuntimeException('Campaign state mismatch');
    $summary=['activation'=>$manifest['activation'],'accepted'=>0,'skipped'=>0,'deferred'=>0,'dead'=>0,'due_waves'=>[]];
    $state['heartbeat_at']=gmdate('c',$now());
    if ($manifest['activation'] !== 'active') {$persist($state);return $summary;}
    foreach($manifest['waves'] as $wave){
        $due=strtotime($wave['at']);if($due===false)throw new RuntimeException('Invalid schedule');
        if($now()<$due)continue;
        $summary['due_waves'][]=$wave['id'];
        foreach($manifest['recipients'] as $recipient){
            $email=strtolower(trim($recipient['email']));$hash=hash('sha256',$email);
            if(!filter_var($email,FILTER_VALIDATE_EMAIL)||!hash_equals($recipient['sha256'],$hash))throw new RuntimeException('Invalid recipient identity');
            $key=$wave['id'].':'.$hash;$item=$state['items'][$key]??['status'=>'pending','attempts'=>0];
            if(in_array($item['status'],['accepted','skipped','dead','missed'],true))continue;
            if($now()>=$due+3600){$item['status']=in_array($item['status'],['sending','retry'],true)?'dead':'missed';$item['reason']='send_window_expired';$state['items'][$key]=$item;$persist($state);$summary['dead']++;continue;}
            if(($item['next_attempt_at']??0)>$now())continue;
            if($item['attempts']>=5){$item['status']='dead';$item['reason']='retry_limit';$state['items'][$key]=$item;$persist($state);$summary['dead']++;continue;}
            // A crash after acceptance replays the identical payload and provider key within one hour.
            // Recheck eligibility even on that retry so a new purchase prevents a fresh submission.
            try{$reason=$eligibility($recipient,$wave);}catch(Throwable $e){$summary['deferred']++;$state['last_error']='eligibility_unavailable';$persist($state);continue;}
            if($reason!==null){$item['status']='skipped';$item['reason']=$reason;$item['at']=gmdate('c',$now());$state['items'][$key]=$item;$persist($state);$summary['skipped']++;continue;}
            $item['payload']??=$payload($wave,$email);
            if(($item['payload']['to']??null)!==[$email])throw new RuntimeException('Payload recipient mismatch');
            $item['idempotency_key']??=$manifest['id'].'-'.$wave['id'].'-'.substr($hash,0,32);
            $item['status']='sending';$item['attempts']++;$item['last_attempt_at']=gmdate('c',$now());
            $item['next_attempt_at']=$now()+min(900,60*(2**($item['attempts']-1)));
            $state['items'][$key]=$item;$persist($state); // Durable before side effect.
            try{
                $result=$send($item['payload'],$item['idempotency_key']);
                if(empty($result['id']))throw new RuntimeException('Provider ID missing');
                $item['provider_id']=$result['id'];$item['status']='accepted';$item['accepted_at']=gmdate('c',$now());$summary['accepted']++;
            }catch(Throwable $e){$item['status']=$item['attempts']>=5?'dead':'retry';$item['reason']='provider_rejected_or_unknown';$summary[$item['status']==='dead'?'dead':'deferred']++;}
            $state['items'][$key]=$item;$persist($state);
        }
    }
    $state['last_run_summary']=$summary;$persist($state);return $summary;
}
