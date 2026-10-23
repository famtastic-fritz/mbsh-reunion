<?php
declare(strict_types=1);
require_once __DIR__.'/commerce-wallet.php';

function fam_experience_errors(): void {
    header('Cache-Control: no-store, private');
    set_exception_handler(function(Throwable $e): void {
        global $pdo;
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        if ($e instanceof ValidationError) fam_json_response(422,['error'=>'validation_error','message'=>$e->getMessage()]);
        error_log('[reunion-experience] '.get_class($e).': '.$e->getMessage());
        fam_json_response(503,['error'=>'temporarily_unavailable','message'=>'Please try again. Your saved work is kept.']);
    });
}

function fam_experience_text_fields(array $data,array $fields): void {
    foreach($fields as $field)if(array_key_exists($field,$data)&&$data[$field]!==null&&!is_string($data[$field]))throw new ValidationError('Expected text for '.$field.'.');
}
/** Re-read WooCommerce on every request. Legacy/manual wallet rows never grant this access. */
function fam_experience_entitlement(PDO $pdo,array $config,int $attendeeId): bool {
    foreach (fam_commerce_wallet_for_attendee($pdo,$config,$attendeeId) as $ticket) {
        if (str_starts_with((string)($ticket['public_id']??''),'wc-') && in_array($ticket['status']??'', ['active','checked_in'],true)) return true;
    }
    return false;
}
function fam_require_experience_ticket(PDO $pdo,array $config,int $attendeeId): void {
    if (!fam_experience_entitlement($pdo,$config,$attendeeId)) fam_json_response(403,[
        'error'=>'ticket_holder_required','message'=>'A valid paid mixer ticket linked to your verified email unlocks these activities.',
        'ticket_url'=>'/tickets.html#ticket-order','wallet_url'=>'/portal/#wallet'
    ]);
}
function fam_experience_rate(PDO $pdo,int $attendeeId,string $action,int $max=60): void {
    $key='experience:'.$attendeeId.':'.$action;$ip='attendee:'.$attendeeId;
    $q=$pdo->prepare('SELECT COUNT(*) FROM rate_limits WHERE ip_address=? AND endpoint=? AND attempt_at>=DATE_SUB(NOW(),INTERVAL 1 HOUR)');$q->execute([$ip,$key]);
    if((int)$q->fetchColumn()>=$max){header('Retry-After: 3600');fam_json_response(429,['error'=>'rate_limited','retry_after'=>3600]);}
    $pdo->prepare('INSERT INTO rate_limits(ip_address,endpoint) VALUES(?,?)')->execute([$ip,$key]);
}
function fam_experience_audit(PDO $pdo,int $actor,string $action,string $type,string $public): void {
    fam_portal_staff_audit($pdo,['attendee_id'=>$actor,'role'=>'attendee'],$action,$type,$public);
}
function fam_experience_ranks(array $rows): array {
    $rank=0;$previous=null;
    foreach($rows as $i=>&$row){$row['score']=(int)$row['score'];if($previous!==$row['score'])$rank=$i+1;$row['rank']=$rank;$previous=$row['score'];}
    return $rows;
}
function fam_trivia_leaderboard(PDO $pdo,int $game): array {
    $q=$pdo->prepare("SELECT CONCAT(p.first_name,' ',p.last_name) display_name,a.score FROM trivia_attempts a JOIN attendee_profiles p ON p.attendee_id=a.attendee_id WHERE a.game_id=? AND a.status IN ('active','completed') ORDER BY a.score DESC,p.first_name,p.last_name,a.id");
    $q->execute([$game]);return fam_experience_ranks($q->fetchAll());
}
function fam_guess_leaderboard(PDO $pdo,int $round): array {
    // Card owners receive the same 100 points for their own card; guessing it is forbidden.
    // This prevents a contributor having fewer available points than other participants.
    $q=$pdo->prepare("SELECT p.attendee_id,CONCAT(p.first_name,' ',p.last_name) display_name,
      COALESCE((SELECT SUM(a.points_awarded) FROM reunion_guess_answers a JOIN reunion_guess_cards c ON c.id=a.card_id WHERE a.attendee_id=p.attendee_id AND c.round_id=?),0)
      +100*(SELECT COUNT(*) FROM reunion_guess_cards c JOIN reunion_guess_submissions s ON s.id=c.submission_id WHERE c.round_id=? AND s.attendee_id=p.attendee_id) score
      FROM attendee_profiles p WHERE EXISTS(SELECT 1 FROM reunion_guess_answers a JOIN reunion_guess_cards c ON c.id=a.card_id WHERE a.attendee_id=p.attendee_id AND c.round_id=?)
      ORDER BY score DESC,p.first_name,p.last_name,p.attendee_id");
    $q->execute([$round,$round,$round]);$rows=$q->fetchAll();foreach($rows as &$r)unset($r['attendee_id']);return fam_experience_ranks($rows);
}
function fam_experience_results(PDO $pdo,string $type): array {
    $table=$type==='trivia'?'trivia_games':'reunion_guess_rounds';
    $rows=$pdo->query("SELECT id,public_id,title,status FROM {$table} WHERE status='published' ORDER BY id DESC LIMIT 10")->fetchAll();
    foreach($rows as &$r){$r['leaderboard']=$type==='trivia'?fam_trivia_leaderboard($pdo,(int)$r['id']):fam_guess_leaderboard($pdo,(int)$r['id']);$r['winners']=array_values(array_filter($r['leaderboard'],fn($x)=>$x['rank']===1));unset($r['id']);}
    return $rows;
}
function fam_guess_normalize(string $name): string {
    $name=mb_strtolower(trim($name),'UTF-8');
    return preg_replace('/[^\p{L}\p{N}]+/u','',$name)??'';
}
function fam_guess_clues(array $data): array {
    $clues=$data['clues']??null;
    if(!is_array($clues)||!array_is_list($clues)||count($clues)<3||count($clues)>4)throw new ValidationError('Add three or four clues about yourself.');
    foreach($clues as &$clue){if(!is_string($clue))throw new ValidationError('Each clue must be text.');$clue=trim($clue);if(mb_strlen($clue)<5||mb_strlen($clue)>240)throw new ValidationError('Each clue must be 5–240 characters.');}
    if(count(array_unique($clues))!==count($clues))throw new ValidationError('Use different clues.');
    return $clues;
}
function fam_guess_correct(string $answer,array $aliases): bool {
    $answer=fam_guess_normalize($answer);if($answer==='')return false;
    foreach($aliases as $alias){if(hash_equals(fam_guess_normalize((string)$alias),$answer))return true;}
    return false;
}
function fam_media_preview_url(string $public,bool $review=false): string {
    return '/portal/media-file.php?id='.rawurlencode($public).($review?'&review=1':'');
}
function fam_experience_gallery(PDO $pdo): array {
    $rows=$pdo->query("SELECT public_id,title,caption,event_year FROM attendee_media_submissions WHERE status='approved' AND consent_to_publish=1 AND derivative_path IS NOT NULL ORDER BY reviewed_at DESC,id DESC LIMIT 60")->fetchAll();
    foreach($rows as &$r)$r['preview_url']=fam_media_preview_url($r['public_id']);return $rows;
}

/** Remove every visible use after withdrawal, retaining private answers and audit for fairness. */
function fam_guess_archive_round(PDO $pdo,int $round): void {
    $pdo->prepare("UPDATE reunion_guess_rounds SET status='archived' WHERE id=?")->execute([$round]);
    $pdo->exec("UPDATE reunion_guess_submissions s SET s.status='approved' WHERE s.status='published' AND NOT EXISTS (SELECT 1 FROM reunion_guess_cards c JOIN reunion_guess_rounds r ON r.id=c.round_id WHERE c.submission_id=s.id AND r.status<>'archived')");
}
