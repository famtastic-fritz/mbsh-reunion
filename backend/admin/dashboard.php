<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);
$counts = [
  'rsvps_total'    => (int)$pdo->query("SELECT COUNT(*) FROM rsvps")->fetchColumn(),
  'rsvps_yes'      => (int)$pdo->query("SELECT COUNT(*) FROM rsvps WHERE attending='yes'")->fetchColumn(),
  'rsvps_maybe'    => (int)$pdo->query("SELECT COUNT(*) FROM rsvps WHERE attending='maybe'")->fetchColumn(),
  'rsvps_no'       => (int)$pdo->query("SELECT COUNT(*) FROM rsvps WHERE attending='no'")->fetchColumn(),
  'sponsors_pending'  => (int)$pdo->query("SELECT COUNT(*) FROM sponsors_pending WHERE status='pending'")->fetchColumn(),
  'sponsors_approved' => (int)$pdo->query("SELECT COUNT(*) FROM sponsors_approved WHERE active=1")->fetchColumn(),
  'memories_pending'  => (int)$pdo->query("SELECT COUNT(*) FROM memories WHERE approved=0")->fetchColumn(),
  'memories_approved' => (int)$pdo->query("SELECT COUNT(*) FROM memories WHERE approved=1")->fetchColumn(),
  'in_memory'      => (int)$pdo->query("SELECT COUNT(*) FROM in_memory WHERE active=1")->fetchColumn(),
  'capsules_total' => (int)$pdo->query("SELECT COUNT(*) FROM time_capsules")->fetchColumn(),
  'capsules_queued'=> (int)$pdo->query("SELECT COUNT(*) FROM time_capsules WHERE sent_at IS NULL")->fetchColumn(),
  'chatbot_total'  => (int)$pdo->query("SELECT COUNT(*) FROM chatbot_questions")->fetchColumn(),
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
.section-title{font-family:Georgia,serif;font-size:1.1rem;margin:2rem 0 1rem;color:#444;border-bottom:1px solid #ddd;padding-bottom:.5rem}
.links{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:2rem}
.links a{background:#fff;padding:.75rem 1.25rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06);text-decoration:none;color:#0A0A0A;font-weight:600;font-size:.9rem;border-left:4px solid #C8102E}
.links a:hover{background:#fafafa}
</style></head><body>
<header><h1>Committee Dashboard</h1><a class="logout" href="logout.php">Sign out</a></header>

<div class="links">
  <a href="rsvps.php">📋 View All RSVPs</a>
  <a href="capsules.php">💌 Time Capsules (<?= $counts['capsules_total'] ?>)</a>
  <a href="chatbot.php">💬 Chatbot Questions (<?= $counts['chatbot_total'] ?>)</a>
  <a href="export-emails.php?source=rsvps">📥 Export RSVP Emails</a>
  <a href="export-emails.php?source=sponsors">📥 Export Sponsor Emails</a>
</div>

<div class="section-title">RSVPs</div>
<div class="grid">
  <div class="card"><div class="num"><?= $counts['rsvps_total'] ?></div><div class="label">Total RSVPs</div><a href="rsvps.php">View all</a></div>
  <div class="card"><div class="num"><?= $counts['rsvps_yes'] ?></div><div class="label">RSVPs Yes</div><a href="rsvps.php?filter=yes">Filter</a></div>
  <div class="card"><div class="num"><?= $counts['rsvps_maybe'] ?></div><div class="label">RSVPs Maybe</div><a href="rsvps.php?filter=maybe">Filter</a></div>
  <div class="card"><div class="num"><?= $counts['rsvps_no'] ?></div><div class="label">RSVPs No</div><a href="rsvps.php?filter=no">Filter</a></div>
</div>

<div class="section-title">Sponsors & Memories</div>
<div class="grid">
  <div class="card"><div class="num"><?= $counts['sponsors_pending'] ?></div><div class="label">Sponsor inquiries pending</div><a href="review-sponsor.php">Review</a></div>
  <div class="card"><div class="num"><?= $counts['sponsors_approved'] ?></div><div class="label">Sponsors approved</div></div>
  <div class="card"><div class="num"><?= $counts['memories_pending'] ?></div><div class="label">Memories pending</div><a href="review-memory.php">Review</a></div>
  <div class="card"><div class="num"><?= $counts['memories_approved'] ?></div><div class="label">Memories approved</div></div>
</div>

<div class="section-title">Other</div>
<div class="grid">
  <div class="card"><div class="num"><?= $counts['in_memory'] ?></div><div class="label">In Memory entries</div><a href="manage-in-memory.php">Manage</a></div>
  <div class="card"><div class="num"><?= $counts['capsules_queued'] ?></div><div class="label">Time capsules queued</div><a href="capsules.php">View</a></div>
  <div class="card"><div class="num"><?= $counts['chatbot_unresponded'] ?></div><div class="label">Chatbot fallbacks pending</div><a href="chatbot.php?filter=unresponded">View</a></div>
  <div class="card"><div class="num"><?= (int)$pdo->query("SELECT COUNT(*) FROM menu_selections")->fetchColumn() ?></div><div class="label">Menu selections</div><a href="menu-results.php">View results</a></div>
  <div class="card"><div class="num"><?= (int)$pdo->query("SELECT COUNT(*) FROM surveys")->fetchColumn() ?></div><div class="label">Class surveys</div><a href="surveys.php">View results</a></div>
</div>
</body></html>
