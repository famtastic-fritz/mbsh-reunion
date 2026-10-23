<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php';
require_once dirname(__DIR__).'/lib/reunion-experience.php';
fam_experience_errors();fam_portal_json_method(['GET']);
$id=fam_require_active_attendee($pdo);
$entitled=fam_experience_entitlement($pdo,$config,$id);
$current=[];
if($entitled){
    foreach($pdo->query("SELECT public_id,title,status FROM trivia_games WHERE status='open' AND (starts_at IS NULL OR starts_at<=NOW()) AND (closes_at IS NULL OR closes_at>NOW()) ORDER BY id DESC LIMIT 1")->fetchAll() as $g)$current[]=['type'=>'trivia']+$g;
    foreach($pdo->query("SELECT public_id,title,status FROM reunion_guess_rounds WHERE status='open' ORDER BY id DESC LIMIT 1")->fetchAll() as $g)$current[]=['type'=>'guess_who']+$g;
}
fam_json_response(200,['entitled'=>$entitled,'eligibility'=>['state'=>$entitled?'ticket_holder':'ticket_required'],'current_activities'=>$current,
 'results'=>$entitled?['trivia'=>fam_experience_results($pdo,'trivia'),'guess_who'=>fam_experience_results($pdo,'guess_who')]:['trivia'=>[],'guess_who'=>[]],
 'gallery'=>$entitled?fam_experience_gallery($pdo):[],
 'rules'=>['trivia'=>'One attempt per round. 100 points per correct starter answer. Ties share the winning place. The committee publishes final results.',
 'guess_who'=>'One guess per card. Use the classmate’s full name. Own cards cannot be guessed; participants receive 100 points for their own selected card. Answers and scores stay hidden until the committee reveals the round. Ties share the winning place.',
 'media'=>'Only photos you have permission to share. Nothing appears in the gallery until committee approval.',
 'requests'=>'Song requests and game ideas are reviewed by the committee. Submission does not guarantee selection.']]);
