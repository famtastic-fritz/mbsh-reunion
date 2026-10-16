<?php
declare(strict_types=1);
$config=require __DIR__.'/portal-config.php';
$config['checkout_secret']=str_repeat('t',64);
$config['db_port']=(int)getenv('MBSH_TEST_DB_PORT');
$config['allowed_origins']=['http://127.0.0.1:'.getenv('MBSH_TEST_HTTP_PORT')];
return $config;
