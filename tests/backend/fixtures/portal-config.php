<?php
declare(strict_types=1);
return [
  'db_host'=>'127.0.0.1', 'db_port'=>(int)(getenv('MBSH_TEST_DB_PORT')?:33317), 'db_name'=>'mbsh_portal_test', 'db_user'=>'mbsh_test', 'db_password'=>'mbsh_test_password',
  'resend_api_key'=>'test_key', 'resend_api_url'=>'http://127.0.0.1:18948/mock-resend.php',
  'resend_from_domain'=>'send.test.invalid', 'resend_from_noreply'=>'noreply@send.test.invalid', 'resend_from_committee'=>'committee@send.test.invalid', 'resend_from_harry'=>'harry@send.test.invalid',
  'resend_reply_to'=>'committee@example.test', 'committee_email'=>'committee@example.test',
  'allowed_origins'=>['http://127.0.0.1:18947'], 'allowed_origin_patterns'=>[],
  'admin_password_hash'=>password_hash('AdminProof2026!',PASSWORD_DEFAULT), 'admin_csrf_secret'=>str_repeat('a',64),
  'portal_token_secret'=>str_repeat('p',64), 'portal_base_url'=>'http://127.0.0.1:18947',
  'pending_uploads_path'=>sys_get_temp_dir().'/mbsh-portal-test-pending', 'approved_uploads_path'=>sys_get_temp_dir().'/mbsh-portal-test-approved', 'environment'=>'test',
];
