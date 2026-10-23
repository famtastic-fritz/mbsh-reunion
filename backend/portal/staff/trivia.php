<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';require_once dirname(__DIR__,2).'/lib/reunion-experience.php';
fam_experience_errors();$method=fam_portal_json_method(['GET','POST','PATCH','DELETE']);$staff=fam_require_portal_staff($pdo,'manage_event_content');
if($method==='GET'){
    $games=$pdo->query("SELECT id,public_id,title,instructions,status,question_seconds,starts_at,closes_at,created_at FROM trivia_games WHERE status<>'archived' ORDER BY id DESC")->fetchAll();
    foreach($games as &$g){$g['leaderboard']=fam_trivia_leaderboard($pdo,(int)$g['id']);$g['winners']=$g['status']==='published'?array_values(array_filter($g['leaderboard'],fn($r)=>$r['rank']===1)):[];unset($g['id']);}unset($g);
    $questions=$pdo->query("SELECT q.public_id,g.public_id game_id,q.prompt,q.choices_json,q.correct_index,q.explanation,q.points,q.sort_order,q.status,q.category,q.sources_json FROM trivia_questions q JOIN trivia_games g ON g.id=q.game_id WHERE q.status<>'retired' ORDER BY g.id DESC,q.sort_order,q.id")->fetchAll();foreach($questions as &$q){$q['choices']=json_decode((string)$q['choices_json'],true);$q['sources']=json_decode((string)$q['sources_json'],true)??[];unset($q['choices_json'],$q['sources_json']);}
    fam_json_response(200,['games'=>$games,'questions'=>$questions]);
}
fam_require_attendee_csrf();$d=fam_read_json_body();fam_experience_text_fields($d,['type','id','game_id','title','instructions','status','prompt','explanation']);$type=fam_enum($d,'type',['game','question','import_starter'],true);
$pdo->beginTransaction();
$lock=$pdo->query("SELECT GET_LOCK('mbsh_trivia_lifecycle',5)")->fetchColumn();if((int)$lock!==1)throw new RuntimeException('game_busy');
register_shutdown_function(function()use($pdo){try{$pdo->query("SELECT RELEASE_LOCK('mbsh_trivia_lifecycle')");}catch(Throwable $e){}});
if($method==='POST'&&$type==='import_starter'){
    require_once dirname(__DIR__,2).'/lib/reunion-trivia-bank.php';
    $result=fam_import_trivia_starter($pdo,$config,$staff);$pdo->commit();fam_json_response(empty($result['already_recorded'])?201:200,$result);
}
if($method==='POST'&&$type==='game'){
    $public=fam_uuid_v4();$title=fam_required($d,'title',200);$instructions=fam_required($d,'instructions',1000);
    $pdo->prepare('INSERT INTO trivia_games(public_id,title,instructions,question_seconds) VALUES(?,?,?,30)')->execute([$public,$title,$instructions]);fam_portal_staff_audit($pdo,$staff,'trivia_game_created','trivia_game',$public);$pdo->commit();fam_json_response(201,['ok'=>true,'id'=>$public]);
}
if($method==='POST'&&$type==='question'){
    $gamePublic=fam_required($d,'game_id',36);$g=$pdo->prepare('SELECT id,status FROM trivia_games WHERE public_id=? FOR UPDATE');$g->execute([$gamePublic]);$game=$g->fetch();if(!$game)fam_json_response(404,['error'=>'not_found']);if($game['status']!=='draft')fam_json_response(409,['error'=>'game_not_draft']);
    $choices=$d['choices']??[];if(!is_array($choices)||!array_is_list($choices)||count($choices)<2||count($choices)>6)throw new ValidationError('Add 2–6 answer choices.');foreach($choices as &$choice){if(!is_string($choice)||trim($choice)===''||mb_strlen($choice)>240)throw new ValidationError('Each choice must be 1–240 characters.');$choice=trim($choice);}unset($choice);
    $correct=fam_int($d,'correct_index',0,count($choices)-1);if($correct===null)throw new ValidationError('Choose the correct answer.');
    $public=fam_uuid_v4();$pdo->prepare("INSERT INTO trivia_questions(public_id,game_id,prompt,choices_json,correct_index,explanation,points,sort_order,status) VALUES(?,?,?,?,?,?,?,?,'draft')")->execute([$public,$game['id'],fam_required($d,'prompt',500),json_encode($choices),$correct,fam_optional($d,'explanation',1000)??'',fam_int($d,'points',1,1000,100),fam_int($d,'sort_order',0,65535,0)]);
    fam_portal_staff_audit($pdo,$staff,'trivia_question_created','trivia_question',$public,['game_id'=>$gamePublic]);$pdo->commit();fam_json_response(201,['ok'=>true,'id'=>$public]);
}
$public=fam_required($d,'id',36);
if($type==='game'){
    $q=$pdo->prepare('SELECT id,status FROM trivia_games WHERE public_id=? FOR UPDATE');$q->execute([$public]);$g=$q->fetch();if(!$g)fam_json_response(404,['error'=>'not_found']);
    $status=$method==='DELETE'?'archived':fam_enum($d,'status',['draft','open','closed','published','archived'],true);
    if($status===$g['status']){$pdo->commit();fam_json_response(200,['ok'=>true,'already_recorded'=>true]);}
    $transitions=['draft'=>['open','archived'],'open'=>['closed'],'closed'=>['published','archived'],'published'=>['archived'],'archived'=>[]];
    if(!in_array($status,$transitions[$g['status']]??[],true))fam_json_response(409,['error'=>'invalid_game_transition']);
    if($status==='open'){
        $count=$pdo->prepare("SELECT COUNT(*) FROM trivia_questions WHERE game_id=? AND status='published'");$count->execute([$g['id']]);if((int)$count->fetchColumn()===0)fam_json_response(409,['error'=>'no_published_questions']);
        if((int)$pdo->query("SELECT COUNT(*) FROM trivia_games WHERE status='open'")->fetchColumn()>0)fam_json_response(409,['error'=>'another_game_open']);
    }
    $pdo->prepare('UPDATE trivia_games SET status=? WHERE id=?')->execute([$status,$g['id']]);fam_portal_staff_audit($pdo,$staff,'trivia_game_'.$status,'trivia_game',$public);$pdo->commit();fam_json_response(200,['ok'=>true]);
}
if($type==='question'){
    $status=$method==='DELETE'?'retired':fam_enum($d,'status',['draft','published','retired'],true);
    $q=$pdo->prepare('SELECT q.id,g.status FROM trivia_questions q JOIN trivia_games g ON g.id=q.game_id WHERE q.public_id=? FOR UPDATE');$q->execute([$public]);$question=$q->fetch();if(!$question||$question['status']!=='draft')fam_json_response(409,['error'=>'question_not_found_or_game_not_draft']);
    $pdo->prepare('UPDATE trivia_questions SET status=? WHERE id=?')->execute([$status,$question['id']]);fam_portal_staff_audit($pdo,$staff,'trivia_question_'.$status,'trivia_question',$public);$pdo->commit();fam_json_response(200,['ok'=>true]);
}
fam_json_response(405,['error'=>'method_not_allowed']);
