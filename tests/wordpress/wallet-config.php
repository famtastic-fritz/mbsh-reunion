<?php
return [
 'environment'=>'test','db_host'=>'mbsh-mixer-wc-db-20261016','db_port'=>3306,'db_name'=>'mixer_wp','db_user'=>'mixer_wp','db_password'=>'isolated_mixer_test',
 'wordpress_bootstrap_path'=>'/var/www/html/wp-load.php','portal_token_secret'=>str_repeat('p',64),'admin_csrf_secret'=>str_repeat('a',64),
 'allowed_origins'=>['http://127.0.0.1:8977','http://127.0.0.1'],'allowed_origin_patterns'=>[],
 'portal_base_url'=>'http://127.0.0.1:8977','portal_frontend_base_url'=>'http://127.0.0.1:8977/portal',
 'resend_api_key'=>'isolated-proof-no-provider','resend_api_url'=>'http://127.0.0.1:9/no-mail',
 'resend_from_noreply'=>'proof@example.test','resend_from_harry'=>'proof@example.test','committee_email'=>'committee@example.test',
 'pending_uploads_path'=>'/tmp/mbsh-experience-private','approved_uploads_path'=>'/tmp/mbsh-experience-approved',
 'trivia_starter_path'=>'/experience-data/trivia/1996-starter.json',
];
