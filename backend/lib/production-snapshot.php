<?php
declare(strict_types=1);

/**
 * Read-only view of legacy reunion records.
 *
 * Development may point at an imported snapshot database. In production the
 * legacy and portal tables currently share the configured database, so a
 * separate read-only connection to that database is the correct adapter.
 */
function fam_production_snapshot(array $config): ?PDO {
  static $resolved=false, $snapshot=null;
  if($resolved) return $snapshot;
  $resolved=true;
  $development=($config['environment']??'production')==='development';
  $name=(string)($config['production_snapshot_db']??($development?'mbsh_reunion_prod_snapshot':($config['db_name']??'')));
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
    'label'=>$snapshot?'Production records · read only':'Portal data',
    'read_only'=>(bool)$snapshot,
  ];
}

function fam_snapshot_count(?PDO $snapshot,string $table,string $where='1=1'): int {
  if(!$snapshot||!preg_match('/^[a-z_]+$/',$table)) return 0;
  try { return (int)$snapshot->query("SELECT COUNT(*) FROM `$table` WHERE $where")->fetchColumn(); }
  catch(Throwable $e){ return 0; }
}
