<?php
declare(strict_types=1);
header('Content-Type: application/json');
$body=json_decode(file_get_contents('php://input')?:'{}',true)?:[];
$to=(string)($body['to'][0]??'');
if(str_contains($to,'fail@')){ http_response_code(500); echo json_encode(['error'=>'synthetic_failure']); exit; }
echo json_encode(['id'=>'mock_'.substr(hash('sha256',json_encode($body)),0,20)]);
