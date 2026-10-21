<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
$root = dirname(__DIR__);
$lib = is_dir($root . '/backend/lib') ? $root . '/backend/lib' : $root . '/lib';
require_once $lib . '/config.php';
require_once $lib . '/db.php';
$pdo = fam_db(fam_load_config());
$rows = $pdo->query("SELECT order_code,contact_name,email,quantity,total_amount,price_tier,payment_status,created_at FROM ticket_orders WHERE payment_status='pending' ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
