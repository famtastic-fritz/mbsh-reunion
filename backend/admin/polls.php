<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
require_once __DIR__ . '/../lib/csrf.php';

fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);
fam_admin_session_start();
$csrfToken = fam_csrf_issue(session_id(), (string)$config['admin_csrf_secret']);

$flash = null;
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $postedToken = (string)($_POST['csrf_token'] ?? '');
  if (!$postedToken || !fam_csrf_validate($postedToken, session_id(), (string)$config['admin_csrf_secret'])) {
    $errors[] = 'Session check failed. Refresh and try again.';
  } else {
    $action = (string)($_POST['action'] ?? '');
    try {
      if ($action === 'create') {
        $question = trim((string)($_POST['question'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $optionsRaw = trim((string)($_POST['options'] ?? ''));
        $status = ((string)($_POST['status'] ?? 'draft') === 'active') ? 'active' : 'draft';
        $allowUpdates = !empty($_POST['allow_vote_updates']) ? 1 : 0;

        if ($question === '') {
          throw new RuntimeException('Question is required.');
        }
        $options = array_values(array_filter(array_map(static fn($line) => trim($line), preg_split('/\r\n|\r|\n/', $optionsRaw) ?: []), static fn($line) => $line !== ''));
        if (count($options) < 2) {
          throw new RuntimeException('You need at least two options.');
        }

        $pdo->beginTransaction();
        if ($status === 'active') {
          $pdo->exec("UPDATE poll_questions SET status = 'closed' WHERE status = 'active'");
        }
        $stmt = $pdo->prepare('INSERT INTO poll_questions (question, description, status, allow_vote_updates) VALUES (?, ?, ?, ?)');
        $stmt->execute([$question, $description !== '' ? $description : null, $status, $allowUpdates]);
        $pollId = (int)$pdo->lastInsertId();
        $optStmt = $pdo->prepare('INSERT INTO poll_options (poll_id, option_label, sort_order) VALUES (?, ?, ?)');
        foreach ($options as $index => $optionLabel) {
          $optStmt->execute([$pollId, $optionLabel, $index + 1]);
        }
        fam_admin_audit($pdo, 'poll_create', 'poll_questions', $pollId, $question);
        $pdo->commit();
        $flash = $status === 'active' ? 'Poll created and activated.' : 'Poll created as draft.';
      } elseif ($action === 'activate') {
        $pollId = (int)($_POST['poll_id'] ?? 0);
        if ($pollId < 1) throw new RuntimeException('Invalid poll.');
        $pdo->beginTransaction();
        $pdo->exec("UPDATE poll_questions SET status = 'closed' WHERE status = 'active'");
        $stmt = $pdo->prepare("UPDATE poll_questions SET status = 'active' WHERE id = ?");
        $stmt->execute([$pollId]);
        fam_admin_audit($pdo, 'poll_activate', 'poll_questions', $pollId, null);
        $pdo->commit();
        $flash = 'Poll activated.';
      } elseif ($action === 'close') {
        $pollId = (int)($_POST['poll_id'] ?? 0);
        if ($pollId < 1) throw new RuntimeException('Invalid poll.');
        $stmt = $pdo->prepare("UPDATE poll_questions SET status = 'closed' WHERE id = ?");
        $stmt->execute([$pollId]);
        fam_admin_audit($pdo, 'poll_close', 'poll_questions', $pollId, null);
        $flash = 'Poll closed.';
      } else {
        throw new RuntimeException('Unknown action.');
      }
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $errors[] = $e->getMessage();
    }
  }
}

$polls = $pdo->query("SELECT pq.id, pq.question, pq.description, pq.status, pq.allow_vote_updates, pq.created_at, pq.updated_at, COUNT(DISTINCT pv.id) AS vote_count FROM poll_questions pq LEFT JOIN poll_votes pv ON pv.poll_id = pq.id GROUP BY pq.id ORDER BY CASE pq.status WHEN 'active' THEN 0 WHEN 'draft' THEN 1 ELSE 2 END, pq.updated_at DESC, pq.id DESC")->fetchAll() ?: [];

$activePoll = null;
$activeOptions = [];
$recentVotes = [];
foreach ($polls as $poll) {
  if (($poll['status'] ?? '') === 'active') {
    $activePoll = $poll;
    break;
  }
}

if ($activePoll) {
  $optionStmt = $pdo->prepare("SELECT po.id, po.option_label, po.sort_order, COUNT(pv.id) AS vote_count FROM poll_options po LEFT JOIN poll_votes pv ON pv.option_id = po.id WHERE po.poll_id = ? GROUP BY po.id ORDER BY po.sort_order ASC, po.id ASC");
  $optionStmt->execute([(int)$activePoll['id']]);
  $activeOptions = $optionStmt->fetchAll() ?: [];

  $recentStmt = $pdo->prepare('SELECT voter_name, voter_email, voter_note, created_at, updated_at, option_id FROM poll_votes WHERE poll_id = ? ORDER BY updated_at DESC, id DESC LIMIT 25');
  $recentStmt->execute([(int)$activePoll['id']]);
  $recentVotes = $recentStmt->fetchAll() ?: [];
  $optionLabels = [];
  foreach ($activeOptions as $opt) {
    $optionLabels[(int)$opt['id']] = (string)$opt['option_label'];
  }
} else {
  $optionLabels = [];
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Polls — MBSH Admin</title>
  <style>
    body{font-family:Inter,sans-serif;background:#F8F4EC;color:#0A0A0A;margin:0;padding:2rem}
    header{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
    h1,h2,h3{font-family:Georgia,serif;margin:0}
    .actions{display:flex;gap:.75rem;flex-wrap:wrap}.actions a{background:#fff;padding:.7rem 1rem;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.06);text-decoration:none;color:#0A0A0A;font-weight:600}
    .flash,.errors{padding:1rem 1.1rem;border-radius:14px;margin-bottom:1rem}.flash{background:#e8f5e9;color:#1b5e20}.errors{background:#ffebee;color:#b71c1c}.errors ul{margin:.25rem 0 0 1rem;padding:0}
    .grid{display:grid;grid-template-columns:1.1fr .9fr;gap:1.25rem;align-items:start}.panel{background:#fff;padding:1.25rem;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
    .field{display:flex;flex-direction:column;gap:.4rem;margin-top:.9rem} label{font-size:.88rem;color:#444}
    input[type=text], textarea, select{width:100%;box-sizing:border-box;padding:.8rem .9rem;border:1px solid #ddd;border-radius:10px;background:#fff;font:inherit} textarea{min-height:130px;resize:vertical}
    .submit{margin-top:1rem;border:0;background:#C8102E;color:#fff;padding:.85rem 1.1rem;border-radius:999px;font-weight:700;cursor:pointer}
    .submit.secondary{background:#0A0A0A}
    .poll-list{display:grid;gap:1rem}.poll-card{border:1px solid #eee;border-radius:12px;padding:1rem}.badge{display:inline-block;padding:.2rem .5rem;border-radius:999px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
    .badge-active{background:#e8f5e9;color:#2e7d32}.badge-draft{background:#fff3e0;color:#ef6c00}.badge-closed{background:#eceff1;color:#455a64}
    .mini{font-size:.84rem;color:#666}.row{display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;margin-top:.8rem}.inline{display:inline}.bar-row{margin:.7rem 0}.bar-meta{display:flex;justify-content:space-between;gap:1rem;font-size:.9rem}.bar-track{height:10px;background:#eee;border-radius:999px;overflow:hidden;margin-top:.35rem}.bar-fill{height:100%;background:#C8102E}
    table{width:100%;border-collapse:collapse;margin-top:1rem} th,td{padding:.65rem .75rem;text-align:left;border-bottom:1px solid #eee;font-size:.85rem;vertical-align:top} th{background:#0A0A0A;color:#fff;font-size:.74rem;text-transform:uppercase}
    .muted{color:#777}.split{display:grid;grid-template-columns:1fr;gap:1rem}.checkbox{display:flex;align-items:center;gap:.55rem;margin-top:.85rem}
    @media (max-width: 960px){.grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <header>
    <h1>Committee Polls</h1>
    <div class="actions">
      <a href="dashboard.php">&larr; Dashboard</a>
      <a href="/poll.php" target="_blank" rel="noopener">Open public poll page</a>
    </div>
  </header>

  <?php if ($flash): ?><div class="flash"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
  <?php if ($errors): ?><div class="errors"><strong>Fix this:</strong><ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

  <div class="grid">
    <section class="panel">
      <h2>Create a poll</h2>
      <p class="mini">This is the clean decision lane: one question, clear options, one email per vote.</p>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="action" value="create">
        <div class="field">
          <label for="question">Question</label>
          <input id="question" name="question" type="text" maxlength="255" required placeholder="Which venue should we lock?">
        </div>
        <div class="field">
          <label for="description">Description / context</label>
          <textarea id="description" name="description" maxlength="5000" placeholder="Give classmates the tradeoffs, deadline, and anything they need to know."></textarea>
        </div>
        <div class="field">
          <label for="options">Options (one per line)</label>
          <textarea id="options" name="options" required placeholder="Option A&#10;Option B&#10;Option C"></textarea>
        </div>
        <div class="field">
          <label for="status">Start state</label>
          <select id="status" name="status">
            <option value="draft">Draft</option>
            <option value="active">Active now</option>
          </select>
        </div>
        <label class="checkbox"><input type="checkbox" name="allow_vote_updates" value="1" checked> Allow a voter to change their vote by resubmitting</label>
        <button class="submit" type="submit">Create poll</button>
      </form>
    </section>

    <section class="panel split">
      <div>
        <h2>Current live poll</h2>
        <?php if ($activePoll): ?>
          <h3 style="margin-top:.7rem"><?= htmlspecialchars((string)$activePoll['question']) ?></h3>
          <?php if (!empty($activePoll['description'])): ?><p class="mini"><?= nl2br(htmlspecialchars((string)$activePoll['description'])) ?></p><?php endif; ?>
          <p class="mini">Public URL: <a href="/poll.php" target="_blank" rel="noopener">/poll.php</a> · Votes: <strong><?= (int)$activePoll['vote_count'] ?></strong></p>
          <?php $max = 1; foreach ($activeOptions as $opt) { $max = max($max, (int)$opt['vote_count']); } ?>
          <?php foreach ($activeOptions as $opt): $pct = (int)round(((int)$opt['vote_count'] / $max) * 100); ?>
            <div class="bar-row">
              <div class="bar-meta"><span><?= htmlspecialchars((string)$opt['option_label']) ?></span><strong><?= (int)$opt['vote_count'] ?></strong></div>
              <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
            </div>
          <?php endforeach; ?>
          <form method="post" class="row">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="close">
            <input type="hidden" name="poll_id" value="<?= (int)$activePoll['id'] ?>">
            <button class="submit secondary" type="submit">Close poll</button>
          </form>
        <?php else: ?>
          <p class="muted" style="margin-top:.8rem">No active poll right now.</p>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <section class="panel" style="margin-top:1.25rem">
    <h2>All polls</h2>
    <div class="poll-list" style="margin-top:1rem">
      <?php foreach ($polls as $poll): ?>
        <article class="poll-card">
          <div class="row" style="justify-content:space-between">
            <div>
              <strong><?= htmlspecialchars((string)$poll['question']) ?></strong>
              <div class="mini">Votes: <?= (int)$poll['vote_count'] ?> · Updated <?= htmlspecialchars((string)$poll['updated_at']) ?></div>
            </div>
            <span class="badge badge-<?= htmlspecialchars((string)$poll['status']) ?>"><?= htmlspecialchars((string)$poll['status']) ?></span>
          </div>
          <?php if (!empty($poll['description'])): ?><p class="mini" style="margin-top:.7rem"><?= nl2br(htmlspecialchars((string)$poll['description'])) ?></p><?php endif; ?>
          <div class="row">
            <?php if (($poll['status'] ?? '') !== 'active'): ?>
              <form method="post" class="inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="activate">
                <input type="hidden" name="poll_id" value="<?= (int)$poll['id'] ?>">
                <button class="submit" type="submit">Make active</button>
              </form>
            <?php endif; ?>
            <?php if (($poll['status'] ?? '') === 'active'): ?>
              <form method="post" class="inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="close">
                <input type="hidden" name="poll_id" value="<?= (int)$poll['id'] ?>">
                <button class="submit secondary" type="submit">Close</button>
              </form>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
      <?php if (empty($polls)): ?><p class="muted">No polls yet.</p><?php endif; ?>
    </div>
  </section>

  <?php if ($activePoll): ?>
    <section class="panel" style="margin-top:1.25rem">
      <h2>Recent votes for current live poll</h2>
      <table>
        <thead>
          <tr><th>Name</th><th>Email</th><th>Choice</th><th>Note</th><th>Updated</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recentVotes as $vote): ?>
            <tr>
              <td><?= htmlspecialchars((string)$vote['voter_name']) ?></td>
              <td><?= htmlspecialchars((string)$vote['voter_email']) ?></td>
              <td><?= htmlspecialchars($optionLabels[(int)$vote['option_id']] ?? '—') ?></td>
              <td><?= htmlspecialchars((string)($vote['voter_note'] ?: '—')) ?></td>
              <td><?= htmlspecialchars((string)$vote['updated_at']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($recentVotes)): ?><tr><td colspan="5" class="muted" style="padding:1rem">No votes yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </section>
  <?php endif; ?>
</body>
</html>
