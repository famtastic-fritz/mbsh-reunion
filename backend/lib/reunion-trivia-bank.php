<?php
declare(strict_types=1);
/** Caller holds transaction and mbsh_trivia_lifecycle lock. Bank stays outside public roots. */
function fam_import_trivia_starter(PDO $pdo,array $config,array $staff): array {
    $path=$config['trivia_starter_path']??$config['trivia_starter_bank_path']??(getenv('MBSH_TRIVIA_STARTER_PATH')?:dirname(__DIR__,2).'/data/trivia/1996-starter.json');
    $bank=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
    if(($bank['schema_version']??0)!==1||count($bank['questions']??[])!==30)throw new RuntimeException('invalid_starter_bank');
    $key=fam_required($bank,'bank_key',100);$q=$pdo->prepare('SELECT public_id FROM trivia_games WHERE bank_key=?');$q->execute([$key]);if($existing=$q->fetchColumn()){return ['ok'=>true,'id'=>$existing,'already_recorded'=>true];}
    $public=fam_uuid_v4();$pdo->prepare("INSERT INTO trivia_games(public_id,title,instructions,bank_key) VALUES(?,?,?,?)")->execute([$public,'1996: The Starter Round','One attempt per round. Choose one answer per question. Correct answers earn 100 points; ties share the winning place. The committee announces published results.',$key]);$game=(int)$pdo->lastInsertId();
    foreach($bank['questions'] as $i=>$question){
        $choices=$question['choices']??[];$correct=$question['correct_index']??-1;
        if(!is_array($choices)||count($choices)!==4||!is_int($correct)||!isset($choices[$correct])||empty($question['sources']))throw new RuntimeException('invalid_starter_question');
        $pdo->prepare("INSERT INTO trivia_questions(public_id,game_id,prompt,choices_json,correct_index,explanation,points,sort_order,status,category,sources_json) VALUES(?,?,?,?,?,?,100,?,'published',?,?)")->execute([fam_uuid_v4(),$game,fam_required($question,'prompt',500),json_encode($choices),$correct,fam_required($question,'explanation',1000),$i,fam_required($question,'category',80),json_encode($question['sources'],JSON_UNESCAPED_SLASHES)]);
    }
    fam_portal_staff_audit($pdo,$staff,'trivia_starter_imported','trivia_game',$public,['bank_key'=>$key]);return ['ok'=>true,'id'=>$public,'status'=>'draft'];
}
