<?php
// lib/db.php — PDO MariaDB/MySQL connection factory with utf8mb4 + exception mode
declare(strict_types=1);

function fam_db(array $config): PDO {
  static $pdo = null;
  if ($pdo !== null) return $pdo;
  $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['db_host'], (int)($config['db_port'] ?? 3306), $config['db_name']);
  $pdo = new PDO($dsn, $config['db_user'], $config['db_password'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ]);
  return $pdo;
}
