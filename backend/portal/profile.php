<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php';
$method=fam_portal_json_method(['GET','PATCH']); $id=fam_require_active_attendee($pdo);
if ($method==='GET') fam_json_response(200,['profile'=>fam_portal_account($pdo,$id)]);
fam_require_attendee_csrf(); $d=fam_read_json_body();
try {
  $first=fam_required($d,'first_name',100); $last=fam_required($d,'last_name',100);
  $maiden=fam_optional($d,'maiden_name',100); $phone=fam_optional($d,'phone',50); $city=fam_optional($d,'city_state',255); $bio=fam_optional($d,'bio',1000);
  $year=fam_int($d,'graduation_year',1900,2100,1996); $visible=fam_bool($d,'display_in_directory',false);
  $pdo->prepare('UPDATE attendee_profiles SET first_name=?,last_name=?,maiden_name=?,phone=?,city_state=?,graduation_year=?,bio=?,display_in_directory=? WHERE attendee_id=?')->execute([$first,$last,$maiden,$phone,$city,$year,$bio,$visible?1:0,$id]);
  fam_json_response(200,['ok'=>true,'profile'=>fam_portal_account($pdo,$id)]);
} catch(ValidationError $e){ fam_json_response(400,['error'=>'validation_error','message'=>$e->getMessage()]); }
