<?php
declare(strict_types=1);

/** Local-only, read-only connection to an imported production snapshot. */
function fam_production_snapshot(array $config): ?PDO {
  static $resolved=false, $snapshot=null;
  if($resolved) return $snapshot;
  $resolved=true;
  if(($config['environment']??'production')!=='development') return null;
  $name=(string)($config['production_snapshot_db']??'mbsh_reunion_prod_snapshot');
  if(!preg_match('/^[a-zA-Z0-9_]+$/',$name)) return null;
  try {
    $dsn=sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',$config['db_host'],(int)($config['db_port']??3306),$name);
    $snapshot=new PDO($dsn,$config['db_user'],$config['db_password'],[
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
    $snapshot->exec('SET SESSION TRANSACTION READ ONLY');
  } catch(Throwable $e) { $snapshot=null; }
  return $snapshot;
}

function fam_snapshot_context(?PDO $snapshot): array {
  return [
    'mode'=>$snapshot?'production_snapshot':'portal_only',
    'label'=>$snapshot?'Production Snapshot · read only':'Portal data',
    'read_only'=>(bool)$snapshot,
  ];
}

function fam_snapshot_count(?PDO $snapshot,string $table,string $where='1=1'): int {
  if(!$snapshot||!preg_match('/^[a-z_]+$/',$table)) return 0;
  try { return (int)$snapshot->query("SELECT COUNT(*) FROM `$table` WHERE $where")->fetchColumn(); }
  catch(Throwable $e){ return 0; }
}
