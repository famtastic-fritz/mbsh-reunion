<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/lib/config.php'; require_once dirname(__DIR__).'/lib/admin-auth.php'; require_once dirname(__DIR__).'/lib/csrf.php'; require_once dirname(__DIR__).'/lib/validate.php';
$config=fam_load_config(); fam_require_admin_auth();
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET') fam_json_response(405,['error'=>'method_not_allowed']);
fam_json_response(200,['authenticated'=>true,'csrf_token'=>fam_csrf_issue(session_id(),(string)$config['admin_csrf_secret'])]);
