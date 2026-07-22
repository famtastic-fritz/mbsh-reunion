<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);

$rsvp = $pdo->query("SELECT attending, COUNT(*) AS total FROM rsvps GROUP BY attending")->fetchAll();
$rsvpCounts = ['yes' => 0, 'maybe' => 0, 'no' => 0];
foreach ($rsvp as $row) {
  $key = (string)$row['attending'];
  $rsvpCounts[$key] = (int)$row['total'];
}

$menuSummary = $pdo->query("SELECT COUNT(*) AS total, SUM(submitter_email_status = 'sent') AS submitter_sent, SUM(submitter_email_status = 'failed') AS submitter_failed, SUM(committee_email_status = 'sent') AS committee_sent, SUM(committee_email_status = 'failed') AS committee_failed FROM menu_selections")->fetch() ?: [];
$menuSummary = array_map(static fn($v) => (int)($v ?? 0), $menuSummary);

$menuCourseCounts = [
  'hors' => [],
  'salad' => [],
  'entree' => [],
  'side' => [],
];
$menuRows = $pdo->query("SELECT selections_json FROM menu_selections")->fetchAll(PDO::FETCH_COLUMN);
foreach ($menuRows as $json) {
  $selection = json_decode((string)$json, true);
  if (!is_array($selection)) continue;
  foreach ($menuCourseCounts as $course => $_) {
    $items = $selection[$course] ?? [];
    if (!is_array($items)) $items = [$items];
    foreach ($items as $item) {
      if (!is_string($item) || $item === '') continue;
      $menuCourseCounts[$course][$item] = ($menuCourseCounts[$course][$item] ?? 0) + 1;
    }
    arsort($menuCourseCounts[$course]);
  }
}

$surveyBudget = $pdo->query("SELECT budget, COUNT(*) AS total FROM surveys WHERE budget IS NOT NULL AND budget <> '' GROUP BY budget ORDER BY total DESC")->fetchAll();
$surveyMonths = $pdo->query("SELECT reunion_month, COUNT(*) AS total FROM surveys WHERE reunion_month IS NOT NULL AND reunion_month <> '' GROUP BY reunion_month ORDER BY total DESC")->fetchAll();
$historicalContacts = (int)$pdo->query("SELECT COUNT(*) FROM (SELECT LOWER(TRIM(email)) AS email_key FROM surveys WHERE is_imported = 1 AND email IS NOT NULL AND TRIM(email) <> '' GROUP BY LOWER(TRIM(email))) historical_contacts")->fetchColumn();
$historicalMissingRsvp = (int)$pdo->query("SELECT COUNT(*) FROM (SELECT LOWER(TRIM(s.email)) AS email_key FROM surveys s LEFT JOIN (SELECT LOWER(TRIM(email)) AS email_key FROM rsvps WHERE email IS NOT NULL AND TRIM(email) <> '' GROUP BY LOWER(TRIM(email))) r ON r.email_key = LOWER(TRIM(s.email)) WHERE s.is_imported = 1 AND s.email IS NOT NULL AND TRIM(s.email) <> '' AND r.email_key IS NULL GROUP BY LOWER(TRIM(s.email))) missing_rsvp")->fetchColumn();
$historicalMissingMenu = (int)$pdo->query("SELECT COUNT(*) FROM (SELECT LOWER(TRIM(s.email)) AS email_key FROM surveys s LEFT JOIN (SELECT LOWER(TRIM(email)) AS email_key FROM menu_selections WHERE email IS NOT NULL AND TRIM(email) <> '' GROUP BY LOWER(TRIM(email))) m ON m.email_key = LOWER(TRIM(s.email)) WHERE s.is_imported = 1 AND s.email IS NOT NULL AND TRIM(s.email) <> '' AND m.email_key IS NULL GROUP BY LOWER(TRIM(s.email))) missing_menu")->fetchColumn();
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports — MBSH Admin</title>
<style>
body{font-family:Inter,sans-serif;background:#F8F4EC;color:#0A0A0A;margin:0;padding:2rem}
header{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
h1,h2{font-family:Georgia,serif;margin:0}
.actions{display:flex;gap:.75rem;flex-wrap:wrap}.actions a{background:#fff;padding:.7rem 1rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06);text-decoration:none;color:#0A0A0A;font-weight:600}
.section{margin-top:2rem}.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem}.card{background:#fff;padding:1rem 1.25rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.num{font-family:'JetBrains Mono',monospace;font-size:2rem;color:#C8102E;font-weight:700}.label{color:#666;font-size:.9rem}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:1rem}.panel{background:#fff;padding:1.25rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.bar-row{margin:.65rem 0}.bar-meta{display:flex;justify-content:space-between;gap:.75rem;font-size:.85rem}.bar-track{height:10px;background:#eee;border-radius:999px;overflow:hidden;margin-top:.35rem}.bar-fill{height:100%;background:#C8102E}
.note{color:#666;font-size:.9rem;line-height:1.5}
</style></head><body>
<header>
  <h1>Committee Reports</h1>
  <div class="actions">
    <a href="dashboard.php">&larr; Dashboard</a>
    <a href="menu-results.php">Menu results</a>
    <a href="follow-up.php?type=rsvp">Missing RSVP</a>
    <a href="follow-up.php?type=menu">Missing menu</a>
    <a href="export-emails.php?source=menu">Export menu CSV</a>
    <a href="export-emails.php?source=historical_missing_rsvp">Export missing RSVP</a>
    <a href="export-emails.php?source=historical_missing_menu">Export missing menu</a>
  </div>
</header>

<div class="section">
  <h2>Decision snapshot</h2>
  <div class="stats">
    <div class="card"><div class="num"><?= $rsvpCounts['yes'] ?></div><div class="label">RSVP yes</div></div>
    <div class="card"><div class="num"><?= $rsvpCounts['maybe'] ?></div><div class="label">RSVP maybe</div></div>
    <div class="card"><div class="num"><?= $rsvpCounts['no'] ?></div><div class="label">RSVP no</div></div>
    <div class="card"><div class="num"><?= $menuSummary['total'] ?? 0 ?></div><div class="label">Menu submissions</div></div>
    <div class="card"><div class="num"><?= $menuSummary['submitter_sent'] ?? 0 ?></div><div class="label">Submitter emails sent</div></div>
    <div class="card"><div class="num"><?= $menuSummary['submitter_failed'] ?? 0 ?></div><div class="label">Submitter emails failed</div></div>
    <div class="card"><div class="num"><?= $historicalContacts ?></div><div class="label">Historical contacts</div></div>
    <div class="card"><div class="num"><?= $historicalMissingRsvp ?></div><div class="label">Historical contacts missing RSVP</div></div>
    <div class="card"><div class="num"><?= $historicalMissingMenu ?></div><div class="label">Historical contacts missing menu</div></div>
  </div>
</div>

<div class="section grid">
  <div class="panel">
    <h2>RSVP attendance mix</h2>
    <?php $maxRsvp = max(1, max($rsvpCounts)); foreach ($rsvpCounts as $label => $count): $pct = (int)round(($count / $maxRsvp) * 100); ?>
      <div class="bar-row">
        <div class="bar-meta"><span><?= htmlspecialchars(strtoupper($label)) ?></span><strong><?= $count ?></strong></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="panel">
    <h2>Menu email delivery</h2>
    <?php $delivery = [
      'Submitter sent' => (int)($menuSummary['submitter_sent'] ?? 0),
      'Submitter failed' => (int)($menuSummary['submitter_failed'] ?? 0),
      'Committee sent' => (int)($menuSummary['committee_sent'] ?? 0),
      'Committee failed' => (int)($menuSummary['committee_failed'] ?? 0),
    ]; $maxDelivery = max(1, max($delivery)); foreach ($delivery as $label => $count): $pct = (int)round(($count / $maxDelivery) * 100); ?>
      <div class="bar-row">
        <div class="bar-meta"><span><?= htmlspecialchars($label) ?></span><strong><?= $count ?></strong></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="section grid">
  <?php foreach ($menuCourseCounts as $course => $items): ?>
    <div class="panel">
      <h2><?= htmlspecialchars(ucfirst($course)) ?> preferences</h2>
      <?php if (empty($items)): ?>
        <p class="note">No selections yet.</p>
      <?php else: $max = max($items); foreach ($items as $item => $count): $pct = (int)round(($count / max(1, $max)) * 100); ?>
        <div class="bar-row">
          <div class="bar-meta"><span><?= htmlspecialchars($item) ?></span><strong><?= $count ?></strong></div>
          <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<div class="section grid">
  <div class="panel">
    <h2>Survey budgets</h2>
    <?php if (empty($surveyBudget)): ?><p class="note">No survey budget answers captured yet.</p><?php else: $maxBudget = max(array_column($surveyBudget, 'total')); foreach ($surveyBudget as $row): $pct = (int)round(((int)$row['total'] / max(1, $maxBudget)) * 100); ?>
      <div class="bar-row">
        <div class="bar-meta"><span><?= htmlspecialchars((string)$row['budget']) ?></span><strong><?= (int)$row['total'] ?></strong></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="panel">
    <h2>Preferred reunion timing</h2>
    <?php if (empty($surveyMonths)): ?><p class="note">No survey timing answers captured yet.</p><?php else: $maxMonth = max(array_column($surveyMonths, 'total')); foreach ($surveyMonths as $row): $pct = (int)round(((int)$row['total'] / max(1, $maxMonth)) * 100); ?>
      <div class="bar-row">
        <div class="bar-meta"><span><?= htmlspecialchars((string)$row['reunion_month']) ?></span><strong><?= (int)$row['total'] ?></strong></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="section">
  <div class="panel">
    <h2>Read this right</h2>
    <p class="note">These reports are meant to drive decisions fast: attendance appetite, menu demand, delivery health, and survey preference distribution. If you want a true poll workflow next, the clean move is a dedicated admin poll table plus a public vote page so decisions are based on explicit votes instead of inferred menu/survey patterns.</p>
  </div>
</div>
</body></html>
