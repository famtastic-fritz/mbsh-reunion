<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/lib/config.php';
require_once dirname(__DIR__) . '/backend/lib/db.php';

$pdo = fam_db(fam_load_config());
$sourceDatasets = [
  'rsvps' => ['portal_state' => 'linked_read_only', 'required_before_cutover' => true],
  'menu_selections' => ['portal_state' => 'not_bridged', 'required_before_cutover' => true],
  'ticket_orders' => ['portal_state' => 'compatibility_only', 'required_before_cutover' => true],
  'surveys' => ['portal_state' => 'not_bridged', 'required_before_cutover' => true],
  'memories' => ['portal_state' => 'separate_from_portal_media', 'required_before_cutover' => true],
  'time_capsules' => ['portal_state' => 'not_bridged', 'required_before_cutover' => true],
  'sponsors_pending' => ['portal_state' => 'not_bridged', 'required_before_cutover' => true],
  'sponsors_approved' => ['portal_state' => 'not_bridged', 'required_before_cutover' => true],
  'in_memory' => ['portal_state' => 'not_bridged', 'required_before_cutover' => true],
  'chatbot_questions' => ['portal_state' => 'not_bridged', 'required_before_cutover' => false],
  'poll_votes' => ['portal_state' => 'legacy_public_workflow', 'required_before_cutover' => false],
];

$tableExists = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
$results = [];
$blocked = [];
foreach ($sourceDatasets as $table => $contract) {
  $tableExists->execute([$table]);
  $exists = (bool)$tableExists->fetchColumn();
  $count = $exists ? (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn() : null;
  $ready = $exists && in_array($contract['portal_state'], ['migrated', 'bridged_read_write'], true);
  $results[$table] = $contract + ['source_present' => $exists, 'source_count' => $count, 'cutover_ready' => $ready];
  if ($contract['required_before_cutover'] && !$ready) $blocked[] = $table;
}

$portalTables = ['attendee_accounts','attendee_profiles','attendee_preferences','attendee_record_links','ticket_wallet_items','attendee_media_submissions','attendee_suggestions','attendee_notifications','portal_email_jobs'];
$portal = [];
foreach ($portalTables as $table) {
  $tableExists->execute([$table]);
  $exists = (bool)$tableExists->fetchColumn();
  $portal[$table] = ['present' => $exists, 'count' => $exists ? (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn() : null];
  if (!$exists) $blocked[] = $table;
}

$report = [
  'generated_at' => gmdate(DATE_ATOM),
  'cutover_ready' => count($blocked) === 0,
  'blocked_datasets' => array_values(array_unique($blocked)),
  'legacy_sources' => $results,
  'portal_sources' => $portal,
  'rule' => 'Production cutover is forbidden until every required source is present, reconciled, read/write bridged or migrated, and independently counted.',
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($report['cutover_ready'] ? 0 : 2);
