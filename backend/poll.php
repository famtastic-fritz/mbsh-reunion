<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/cors.php';
require_once __DIR__ . '/lib/validate.php';
require_once __DIR__ . '/lib/rate-limit.php';

$config = fam_load_config();
$pdo = fam_db($config);
fam_cors($config, 'public_post');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') { http_response_code(204); exit; }

$errors = [];
$flash = null;
$activePoll = null;
$pollOptions = [];

function fam_poll_load_active(PDO $pdo): ?array {
  $stmt = $pdo->query("SELECT id, question, description, status, allow_vote_updates, created_at, updated_at FROM poll_questions WHERE status = 'active' ORDER BY updated_at DESC, id DESC LIMIT 1");
  $poll = $stmt->fetch();
  return $poll ?: null;
}

function fam_poll_load_options(PDO $pdo, int $pollId): array {
  $stmt = $pdo->prepare('SELECT id, option_label, sort_order FROM poll_options WHERE poll_id = ? ORDER BY sort_order ASC, id ASC');
  $stmt->execute([$pollId]);
  return $stmt->fetchAll() ?: [];
}

$activePoll = fam_poll_load_active($pdo);
if ($activePoll) {
  $pollOptions = fam_poll_load_options($pdo, (int)$activePoll['id']);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  try {
    fam_rate_limit($pdo, 'poll', 10, 300);

    $body = [
      'name' => $_POST['name'] ?? '',
      'email' => $_POST['email'] ?? '',
      'option_id' => $_POST['option_id'] ?? '',
      'note' => $_POST['note'] ?? '',
      'website' => $_POST['website'] ?? '',
      'form_loaded_at' => $_POST['form_loaded_at'] ?? '',
    ];

    fam_honeypot_clean($body);
    fam_form_loaded_at_check($body, 1500);

    if (!$activePoll) {
      throw new ValidationError('No active poll is available right now.');
    }

    $name = fam_required($body, 'name', 150);
    $email = fam_email($body, 'email', true);
    $optionId = fam_int($body, 'option_id', 1, PHP_INT_MAX);
    $note = fam_optional($body, 'note', 1000);

    $optionStmt = $pdo->prepare('SELECT id, option_label FROM poll_options WHERE id = ? AND poll_id = ?');
    $optionStmt->execute([$optionId, (int)$activePoll['id']]);
    $selectedOption = $optionStmt->fetch();
    if (!$selectedOption) {
      throw new ValidationError('Please choose one of the listed options.');
    }

    $existingStmt = $pdo->prepare('SELECT id FROM poll_votes WHERE poll_id = ? AND voter_email = ?');
    $existingStmt->execute([(int)$activePoll['id'], $email]);
    $existing = $existingStmt->fetch();

    if ($existing) {
      if ((int)$activePoll['allow_vote_updates'] !== 1) {
        throw new ValidationError('Your vote is already recorded and this poll is locked.');
      }
      $update = $pdo->prepare('UPDATE poll_votes SET option_id = ?, voter_name = ?, voter_note = ?, ip_address = ?, user_agent = ?, updated_at = NOW() WHERE id = ?');
      $update->execute([
        (int)$selectedOption['id'],
        $name,
        $note,
        substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        (int)$existing['id'],
      ]);
      $flash = 'Vote updated. You are locked in.';
    } else {
      $insert = $pdo->prepare('INSERT INTO poll_votes (poll_id, option_id, voter_name, voter_email, voter_note, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)');
      $insert->execute([
        (int)$activePoll['id'],
        (int)$selectedOption['id'],
        $name,
        $email,
        $note,
        substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
      ]);
      $flash = 'Vote recorded. Done.';
    }
  } catch (ValidationError $e) {
    $errors[] = $e->getMessage();
  } catch (Throwable $e) {
    error_log('poll.php error: ' . $e->getMessage());
    $errors[] = 'Server error. Try again in a minute.';
  }
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MBSH Quick Poll</title>
  <style>
    body{margin:0;background:#0A0A0A;color:#F8F4EC;font-family:Inter,Arial,sans-serif}
    .shell{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem}
    .card{width:min(760px,100%);background:linear-gradient(180deg,#171717 0%,#111 100%);border:1px solid rgba(255,255,255,.08);border-radius:24px;padding:2rem;box-shadow:0 18px 48px rgba(0,0,0,.35)}
    .eyebrow{letter-spacing:.24em;text-transform:uppercase;font-size:.72rem;color:#C0C0C0;margin-bottom:.9rem}
    h1{margin:0 0 .6rem;font-family:Georgia,serif;font-size:clamp(2rem,4vw,3.2rem);line-height:1.08}
    .desc{color:#d8d2c8;line-height:1.65;margin-bottom:1.35rem}
    .flash{background:#163019;color:#d8ffd8;border:1px solid #2e7d32;padding:.85rem 1rem;border-radius:14px;margin-bottom:1rem}
    .errors{background:#341515;color:#ffd7d7;border:1px solid #a33;padding:.85rem 1rem;border-radius:14px;margin-bottom:1rem}
    .errors ul{margin:.25rem 0 0 1rem;padding:0}
    .options{display:grid;gap:.85rem;margin:1.25rem 0 1.5rem}
    label.option{display:flex;gap:.85rem;align-items:flex-start;padding:1rem 1rem;border:1px solid rgba(255,255,255,.12);border-radius:16px;background:rgba(255,255,255,.03);cursor:pointer}
    label.option:hover{border-color:#C8102E;background:rgba(200,16,46,.10)}
    input[type=radio]{margin-top:.25rem;transform:scale(1.15)}
    .option-text{font-weight:600}
    .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem}
    .field{display:flex;flex-direction:column;gap:.4rem}
    .field label{font-size:.88rem;color:#ddd}
    input[type=text],input[type=email],textarea{width:100%;box-sizing:border-box;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:#1a1a1a;color:#fff;padding:.85rem .95rem;font:inherit}
    textarea{min-height:100px;resize:vertical}
    .actions{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-top:1.4rem}
    button{border:0;border-radius:999px;background:#C8102E;color:#fff;padding:.9rem 1.3rem;font-weight:700;cursor:pointer}
    button:hover{filter:brightness(1.08)}
    .small{font-size:.86rem;color:#cfc5b8}
    .muted{color:#9d9489}
    .closed{padding:1.25rem;border-radius:16px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1)}
  </style>
</head>
<body>
  <div class="shell">
    <div class="card">
      <div class="eyebrow">MBSH Class of '96</div>
      <?php if ($activePoll): ?>
        <h1><?= htmlspecialchars((string)$activePoll['question']) ?></h1>
        <?php if (!empty($activePoll['description'])): ?><p class="desc"><?= nl2br(htmlspecialchars((string)$activePoll['description'])) ?></p><?php endif; ?>

        <?php if ($flash): ?><div class="flash"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
        <?php if ($errors): ?><div class="errors"><strong>Fix this:</strong><ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

        <form method="post" action="">
          <div class="options">
            <?php foreach ($pollOptions as $option): ?>
              <label class="option">
                <input type="radio" name="option_id" value="<?= (int)$option['id'] ?>" required>
                <span class="option-text"><?= htmlspecialchars((string)$option['option_label']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>

          <div class="grid">
            <div class="field">
              <label for="name">Your name</label>
              <input id="name" name="name" type="text" maxlength="150" required>
            </div>
            <div class="field">
              <label for="email">Your email</label>
              <input id="email" name="email" type="email" maxlength="255" required>
            </div>
          </div>

          <div class="field" style="margin-top:1rem">
            <label for="note">Optional note</label>
            <textarea id="note" name="note" maxlength="1000" placeholder="Say why you picked it, add context, or leave this blank."></textarea>
          </div>

          <input type="text" name="website" value="" style="position:absolute;left:-9999px" tabindex="-1" autocomplete="off">
          <input type="hidden" name="form_loaded_at" id="form_loaded_at" value="">

          <div class="actions">
            <div class="small">One email = one vote. If the poll owner allows updates, resubmitting changes your vote.</div>
            <button type="submit">Lock my vote</button>
          </div>
        </form>
      <?php else: ?>
        <h1>No active poll right now</h1>
        <div class="closed">
          <p class="desc">The committee does not currently have a live vote open. Check back soon.</p>
        </div>
      <?php endif; ?>
      <p class="muted" style="margin-top:1.5rem">Results are tracked in the committee admin dashboard.</p>
    </div>
  </div>
  <script>document.getElementById('form_loaded_at') && (document.getElementById('form_loaded_at').value = String(Date.now()));</script>
</body>
</html>
