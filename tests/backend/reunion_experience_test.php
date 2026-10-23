<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/backend/lib/validate.php';
require_once dirname(__DIR__,2).'/backend/lib/reunion-experience.php';
require_once dirname(__DIR__,2).'/backend/lib/reunion-media.php';
$checks=0;
function expect(bool $condition,string $label):void {global $checks;$checks++;if(!$condition)throw new RuntimeException($label);}
function invalid(callable $fn,string $label):void {try{$fn();}catch(ValidationError $e){expect(true,$label);return;}expect(false,$label);}
expect(fam_guess_correct('  Ana-Marie O’Neil ',['Ana Marie O Neil']),'names normalize case and punctuation');
expect(!fam_guess_correct('Ana', ['Ana Marie O Neil']),'first-name guessing does not expose loose matches');
expect(fam_guess_correct('Ana Jones', ['Ana Smith','Ana Jones']),'authorized profile maiden alias accepted');
expect(!fam_guess_correct('', ['']), 'empty normalized answers rejected');
expect(!fam_guess_correct('Another Person',['Ana Smith']), 'wrong names score zero');
// Entitlement adapter must only accept usable current Woo admissions for the authenticated email.
final class ExperienceTestStatement extends PDOStatement {
 public function execute(?array $params=null):bool{return true;}
 public function fetchColumn(int $column=0):mixed{return 'verified@example.test';}
}
final class ExperienceTestPdo extends PDO {
 public function __construct(){}
 public function prepare(string $query,array $options=[]):PDOStatement|false{return new ExperienceTestStatement();}
}
final class Famtastic_Reunion_Portal_Wallet {
 public static array $rows=[];public static string $queried='';
 public static function tickets_for_email(string $email):array{self::$queried=$email;return self::$rows;}
}
$pdo=new ExperienceTestPdo();
foreach([['wc-0123','active',true],['wc-0123','checked_in',true],['wc-0123','void',false],['legacy-0123','active',false],['wc-0123','pending',false]] as [$public,$status,$allowed]){
 Famtastic_Reunion_Portal_Wallet::$rows=[['public_id'=>$public,'status'=>$status]];
 expect(fam_experience_entitlement($pdo,[],7)===$allowed,'entitlement '.$public.' '.$status);
}
expect(Famtastic_Reunion_Portal_Wallet::$queried==='verified@example.test','wallet receives verified server account email');
Famtastic_Reunion_Portal_Wallet::$rows=[];expect(!fam_experience_entitlement($pdo,[],7),'registration alone has no admission');
invalid(fn()=>fam_experience_text_fields(['answer'=>['unexpected']],['answer']),'structured answer rejected');
$clues=['I played the trumpet.','My favorite class was art.','I brought lunch from home.'];
expect(fam_guess_clues(['clues'=>$clues])===$clues,'three distinct bounded clues accepted');
invalid(fn()=>fam_guess_clues(['clues'=>['one','two']]),'two clues rejected');
invalid(fn()=>fam_guess_clues(['clues'=>[$clues[0],$clues[0],$clues[2]]]),'duplicate clues rejected');
invalid(fn()=>fam_guess_clues(['clues'=>[$clues[0],$clues[1],str_repeat('x',241)]]),'oversized clue rejected');
invalid(fn()=>fam_guess_clues(['clues'=>[$clues[0],$clues[1],['nested']]]),'structured clue rejected');
$ranks=fam_experience_ranks([['score'=>'300'],['score'=>'300'],['score'=>'100'],['score'=>'0']]);
expect(array_column($ranks,'rank')===[1,1,3,4],'ties share rank and next position skips ties');
expect(fam_experience_ranks([])===[],'empty leaderboard creates no winner');
expect(fam_media_memory_limit_bytes('128M')===128*1024*1024,'128M configured limit parsed');
expect(fam_media_memory_limit_bytes(' 131072k ')===128*1024*1024,'lowercase K limit parsed');
expect(fam_media_memory_limit_bytes('1G')===1024**3,'G limit parsed');
expect(fam_media_memory_limit_bytes('134217728')===128*1024*1024,'raw byte limit parsed');
expect(fam_media_memory_limit_bytes('-1')===null,'unlimited PHP limit parsed');
try{fam_media_memory_limit_bytes('128MB');expect(false,'unknown memory suffix fails closed');}catch(RuntimeException $e){expect(true,'unknown memory suffix fails closed');}
expect(!fam_media_decode_fits_memory(6000,4000,4*1024*1024,32*1024*1024,128*1024*1024),'24MP image rejected against 128M before decode');
expect(fam_media_decode_fits_memory(1600,1200,500000,32*1024*1024,128*1024*1024),'resized phone image fits ordinary 128M headroom');
expect(!fam_media_decode_fits_memory(1600,1200,500000,100*1024*1024,128*1024*1024),'current Woo memory usage reduces decode headroom');
expect(fam_media_decode_fits_memory(6000,4000,4*1024*1024,32*1024*1024,null),'unlimited limit is handled explicitly');
$root=sys_get_temp_dir().'/mbsh-media-unit-'.bin2hex(random_bytes(6));mkdir($root.'/portal-media/1',0700,true);file_put_contents($root.'/portal-media/1/fake.jpg','not a photo');file_put_contents($root.'/outside.jpg','private');
$config=['pending_uploads_path'=>$root];
expect(fam_media_private_file($config,$root.'/portal-media/1/fake.jpg')===realpath($root.'/portal-media/1/fake.jpg'),'private-root path resolves');
try{fam_media_private_file($config,$root.'/outside.jpg');expect(false,'path escape rejected');}catch(RuntimeException $e){expect(true,'path escape rejected');}
if(function_exists('imagecreatefromstring')){
 invalid(fn()=>fam_media_derivative($config,$root.'/portal-media/1/fake.jpg'),'fake JPEG rejected');
 // A tiny PNG header advertises a 24MP image. The memory guard must reject it before GD decoding.
 $ihdr='IHDR'.pack('NNCCCCC',6000,4000,8,2,0,0,0);
 $largeHeader="\x89PNG\r\n\x1a\n".pack('N',13).$ihdr.pack('N',crc32($ihdr)).pack('N',0).'IEND'.pack('N',crc32('IEND'));
 file_put_contents($root.'/portal-media/1/large-header.png',$largeHeader);
 $previousLimit=ini_get('memory_limit');ini_set('memory_limit','128M');
 try{fam_media_derivative($config,$root.'/portal-media/1/large-header.png');expect(false,'large header rejected before allocation');}
 catch(ValidationError $e){expect(str_contains($e->getMessage(),'Resize')&&str_contains($e->getMessage(),'1600'),'large header gets actionable resize response before decode');}
 finally{ini_set('memory_limit',$previousLimit);unlink($root.'/portal-media/1/large-header.png');}

 $im=imagecreatetruecolor(100,70);imagepng($im,$root.'/portal-media/1/photo.png');unset($im);
 $derivative=fam_media_derivative($config,$root.'/portal-media/1/photo.png');$info=getimagesize($derivative);
 expect($info['mime']==='image/jpeg'&&$info[0]===100&&$info[1]===70,'photo decoded and re-encoded to safe JPEG');
 expect($derivative!==$root.'/portal-media/1/photo.png','original preserved privately');unlink($derivative);unlink($root.'/portal-media/1/photo.png');
}
unlink($root.'/portal-media/1/fake.jpg');unlink($root.'/outside.jpg');rmdir($root.'/portal-media/1');rmdir($root.'/portal-media');rmdir($root);
echo "PASS {$checks} reunion experience validation, scoring and private derivative checks\n";
