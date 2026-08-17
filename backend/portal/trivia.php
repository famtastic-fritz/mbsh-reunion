<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php';
$method=fam_portal_json_method(['GET','POST']);
$attendeeId=fam_require_active_attendee($pdo);

function trivia_game(PDO $pdo): array|false {
  $q=$pdo->query("SELECT id,public_id,title,instructions,question_seconds,status FROM trivia_games WHERE status='open' AND (starts_at IS NULL OR starts_at<=NOW()) AND (closes_at IS NULL OR closes_at>NOW()) ORDER BY starts_at DESC,id DESC LIMIT 1");
  return $q->fetch();
}
function trivia_state(PDO $pdo,int $attendeeId,array $game): array {
  $a=$pdo->prepare("SELECT id,public_id,status,score,started_at,completed_at FROM trivia_attempts WHERE game_id=? AND attendee_id=?");$a->execute([$game['id'],$attendeeId]);$attempt=$a->fetch();
  $answered=[];$next=null;
  if($attempt){$x=$pdo->prepare("SELECT q.public_id,a.selected_index,a.is_correct,a.points_awarded,q.explanation FROM trivia_answers a JOIN trivia_questions q ON q.id=a.question_id WHERE a.attempt_id=? ORDER BY q.sort_order,q.id");$x->execute([$attempt['id']]);$answered=$x->fetchAll();if($attempt['status']==='active'){$n=$pdo->prepare("SELECT q.public_id,q.prompt,q.choices_json,q.points,q.sort_order FROM trivia_questions q WHERE q.game_id=? AND q.status='published' AND NOT EXISTS(SELECT 1 FROM trivia_answers a WHERE a.attempt_id=? AND a.question_id=q.id) ORDER BY q.sort_order,q.id LIMIT 1");$n->execute([$game['id'],$attempt['id']]);$next=$n->fetch()?:null;if($next)$next['choices']=json_decode((string)$next['choices_json'],true);unset($next['choices_json']);}}
  $rank=$pdo->prepare("SELECT CONCAT(LEFT(p.first_name,1),'. ',p.last_name) display_name,a.score,a.completed_at FROM trivia_attempts a JOIN attendee_profiles p ON p.attendee_id=a.attendee_id WHERE a.game_id=? AND a.status='completed' ORDER BY a.score DESC,a.completed_at ASC LIMIT 20");$rank->execute([$game['id']]);
  unset($game['id']);if($attempt)unset($attempt['id']);return ['game'=>$game,'attempt'=>$attempt?:null,'answered'=>$answered,'next_question'=>$next,'leaderboard'=>$rank->fetchAll()];
}

$game=trivia_game($pdo);if(!$game)fam_json_response(200,['game'=>null,'message'=>'No trivia game is open right now.']);
if($method==='GET')fam_json_response(200,trivia_state($pdo,$attendeeId,$game));
fam_require_attendee_csrf();$data=fam_read_json_body();$action=fam_enum($data,'action',['start','answer'],true);
if($action==='start'){
  $public=fam_uuid_v4();$q=$pdo->prepare("INSERT INTO trivia_attempts(public_id,game_id,attendee_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE public_id=public_id");$q->execute([$public,$game['id'],$attendeeId]);fam_json_response(200,['ok'=>true]+trivia_state($pdo,$attendeeId,$game));
}
$questionPublic=fam_required($data,'question_id',36);$selected=filter_var($data['selected_index']??null,FILTER_VALIDATE_INT);if($selected===false||$selected<0||$selected>9)fam_json_response(422,['error'=>'validation_error']);
$pdo->beginTransaction();try{
  $a=$pdo->prepare("SELECT id,status FROM trivia_attempts WHERE game_id=? AND attendee_id=? FOR UPDATE");$a->execute([$game['id'],$attendeeId]);$attempt=$a->fetch();if(!$attempt||$attempt['status']!=='active'){$pdo->rollBack();fam_json_response(409,['error'=>'attempt_not_active']);}
  $q=$pdo->prepare("SELECT id,choices_json,correct_index,points,explanation FROM trivia_questions WHERE public_id=? AND game_id=? AND status='published'");$q->execute([$questionPublic,$game['id']]);$question=$q->fetch();if(!$question){$pdo->rollBack();fam_json_response(404,['error'=>'not_found']);}$choices=json_decode((string)$question['choices_json'],true);if(!is_array($choices)||!array_key_exists($selected,$choices)){$pdo->rollBack();fam_json_response(422,['error'=>'validation_error']);}
  $correct=$selected===(int)$question['correct_index'];$points=$correct?(int)$question['points']:0;
  try{$pdo->prepare("INSERT INTO trivia_answers(attempt_id,question_id,selected_index,is_correct,points_awarded) VALUES (?,?,?,?,?)")->execute([$attempt['id'],$question['id'],$selected,$correct?1:0,$points]);}catch(PDOException $e){if((string)$e->getCode()==='23000'){$pdo->rollBack();fam_json_response(409,['error'=>'answer_already_recorded']);}throw $e;}
  $pdo->prepare("UPDATE trivia_attempts SET score=score+? WHERE id=?")->execute([$points,$attempt['id']]);
  $remaining=$pdo->prepare("SELECT COUNT(*) FROM trivia_questions q WHERE q.game_id=? AND q.status='published' AND NOT EXISTS(SELECT 1 FROM trivia_answers a WHERE a.attempt_id=? AND a.question_id=q.id)");$remaining->execute([$game['id'],$attempt['id']]);$complete=(int)$remaining->fetchColumn()===0;if($complete)$pdo->prepare("UPDATE trivia_attempts SET status='completed',completed_at=NOW() WHERE id=?")->execute([$attempt['id']]);
  $pdo->commit();fam_json_response(200,['ok'=>true,'correct'=>$correct,'points_awarded'=>$points,'explanation'=>$question['explanation'],'completed'=>$complete]+trivia_state($pdo,$attendeeId,$game));
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
