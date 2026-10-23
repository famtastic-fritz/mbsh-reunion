<?php
declare(strict_types=1);

function fam_commerce_wallet_load(array $config): void {
  if (class_exists('Famtastic_Reunion_Portal_Wallet')) return;
  $path = $config['wordpress_bootstrap_path'] ?? dirname(__DIR__).'/cms/wp-load.php';
  if (!is_file($path)) throw new RuntimeException('commerce_wallet_unavailable');
  // Only server-owned configuration selects this local adapter. No HTTP or caller-supplied identity.
  require_once $path;
  if (!class_exists('Famtastic_Reunion_Portal_Wallet')) throw new RuntimeException('commerce_wallet_unavailable');
}

function fam_commerce_wallet_for_attendee(PDO $pdo, array $config, int $id): array {
  $q=$pdo->prepare("SELECT email FROM attendee_accounts WHERE id=? AND status='active' AND email_verified_at IS NOT NULL");
  $q->execute([$id]); $email=$q->fetchColumn();
  if (!is_string($email)) return [];
  if (($config['commerce_wallet_enabled']??true) === false && ($config['environment']??'') === 'test') return [];
  fam_commerce_wallet_load($config);
  return Famtastic_Reunion_Portal_Wallet::tickets_for_email($email);
}

function fam_commerce_wallet_transition(PDO $pdo,array $config,int $actor,string $public,string $to): bool {
  $access=fam_portal_staff_access($pdo,$actor);
  $capability=$to === 'checked_in' ? 'check_in_tickets' : 'issue_manual_tickets';
  if (!$access || !in_array($capability,$access['capabilities'],true)) return false;
  fam_commerce_wallet_load($config);
  return Famtastic_Reunion_Portal_Wallet::transition($public,$to,$actor);
}
