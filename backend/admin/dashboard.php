<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);
$counts = [
  'rsvps_yes'      => (int)$pdo->query("SELECT COUNT(*) FROM rsvps WHERE attending='yes'")->fetchColumn(),
  'rsvps_maybe'    => (int)$pdo->query("SELECT COUNT(*) FROM rsvps WHERE attending='maybe'")->fetchColumn(),
  'rsvps_no'       => (int)$pdo->query("SELECT COUNT(*) FROM rsvps WHERE attending='no'")->fetchColumn(),
  'sponsors_pending'  => (int)$pdo->query("SELECT COUNT(*) FROM sponsors_pending WHERE status='pending'")->fetchColumn(),
  'sponsors_approved' => (int)$pdo->query("SELECT COUNT(*) FROM sponsors_approved WHERE active=1")->fetchColumn(),
  'memories_pending'  => (int)$pdo->query("SELECT COUNT(*) FROM memories WHERE approved=0")->fetchColumn(),
  'memories_approved' => (int)$pdo->query("SELECT COUNT(*) FROM memories WHERE approved=1")->fetchColumn(),
  'in_memory'      => (int)$pdo->query("SELECT COUNT(*) FROM in_memory WHERE active=1")->fetchColumn(),
  'capsules_queued'=> (int)$pdo->query("SELECT COUNT(*) FROM time_capsules WHERE sent_at IS NULL")->fetchColumn(),
  'chatbot_unresponded' => (int)$pdo->query("SELECT COUNT(*) FROM chatbot_questions WHERE responded=0 AND was_fallback=1")->fetchColumn(),
];
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Committee Dashboard — MBSH</title>
<style>
body{font-family:Inter,sans-serif;background:#F8F4EC;color:#0A0A0A;margin:0;padding:2rem}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem}
h1{font-family:Georgia,serif;margin:0}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem}
.card{background:#fff;padding:1.5rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06);text-align:center}
.card a{color:#C8102E;text-decoration:none;font-weight:600;display:inline-block;margin-top:.5rem}
.num{font-family:'JetBrains Mono',monospace;font-size:2.5rem;color:#C8102E;font-weight:700}
.label{font-style:italic;color:#666}
.logout{color:#C8102E;text-decoration:none;font-weight:600}
</style></head><body>
<header><h1>Committee Dashboard</h1><a class="logout" href="logout.php">Sign out</a></header>
<div class="grid">
  <div class="card"><div class="num"><?= $counts['rsvps_yes'] ?></div><div class="label">RSVPs Yes</div></div>
  <div class="card"><div class="num"><?= $counts['rsvps_maybe'] ?></div><div class="label">RSVPs Maybe</div></div>
  <div class="card"><div class="num"><?= $counts['rsvps_no'] ?></div><div class="label">RSVPs No</div></div>
  <div class="card"><div class="num"><?= $counts['sponsors_pending'] ?></div><div class="label">Sponsor inquiries pending</div><a href="review-sponsor.php">Review</a></div>
  <div class="card"><div class="num"><?= $counts['sponsors_approved'] ?></div><div class="label">Sponsors approved</div></div>
  <div class="card"><div class="num"><?= $counts['memories_pending'] ?></div><div class="label">Memories pending</div><a href="review-memory.php">Review</a></div>
  <div class="card"><div class="num"><?= $counts['memories_approved'] ?></div><div class="label">Memories approved</div></div>
  <div class="card"><div class="num"><?= $counts['in_memory'] ?></div><div class="label">In Memory entries</div><a href="manage-in-memory.php">Manage</a></div>
  <div class="card"><div class="num"><?= $counts['capsules_queued'] ?></div><div class="label">Time capsules queued</div></div>
  <div class="card"><div class="num"><?= $counts['chatbot_unresponded'] ?></div><div class="label">Chatbot fallbacks pending</div></div>
</div>
</body></html>
