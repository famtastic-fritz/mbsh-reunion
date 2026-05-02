<?php
// cron/send-capsules.php — daily 7am UTC. Send queued time capsules whose send_date has passed.
// CLI ONLY. Web invocation forbidden.
declare(strict_types=1);
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only'); }

require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/resend.php';

$config = fam_load_config();
$pdo = fam_db($config);

$stmt = $pdo->query("SELECT id, email, song_answer, person_answer, memory_answer FROM time_capsules WHERE send_date <= UTC_TIMESTAMP() AND sent_at IS NULL AND send_attempts < 5 ORDER BY send_date LIMIT 100");
$rows = $stmt->fetchAll();
$ok = 0; $fail = 0;
foreach ($rows as $r) {
  $song = $r['song_answer'] ?: '—';
  $person = $r['person_answer'] ?: '—';
  $memory = $r['memory_answer'] ?: '—';
  $html = "<div style=\"font-family:Georgia,serif;line-height:1.6;color:#0A0A0A;background:#F8F4EC;padding:2rem;border-radius:6px;\">"
        . "<h2 style=\"color:#C8102E;font-weight:900;\">From your 1996 self — welcome home, Hi-Tide.</h2>"
        . "<p><em>You wrote this when the reunion was still a few months away. Here's what you said.</em></p>"
        . "<p><strong>Song that took you back:</strong><br>" . htmlspecialchars($song, ENT_QUOTES, 'UTF-8') . "</p>"
        . "<p><strong>Who you wanted to find again:</strong><br>" . htmlspecialchars($person, ENT_QUOTES, 'UTF-8') . "</p>"
        . "<p><strong>Best memory from senior year:</strong><br>" . nl2br(htmlspecialchars($memory, ENT_QUOTES, 'UTF-8')) . "</p>"
        . "<p style=\"margin-top:2rem;font-style:italic;color:#888;\">— The Class of '96 reunion committee</p>"
        . "</div>";
  try {
    fam_send_email($config, $r['email'], "From your 1996 self — welcome home, Hi-Tide.", $html, 'noreply');
    $upd = $pdo->prepare('UPDATE time_capsules SET sent_at = UTC_TIMESTAMP(), send_attempts = send_attempts + 1 WHERE id = ?');
    $upd->execute([$r['id']]);
    $ok++;
  } catch (ResendError $e) {
    $upd = $pdo->prepare('UPDATE time_capsules SET send_attempts = send_attempts + 1, send_error = ? WHERE id = ?');
    $upd->execute([substr($e->getMessage(), 0, 1000), $r['id']]);
    $fail++;
    error_log('[send-capsules] capsule ' . $r['id'] . ' failed: ' . $e->getMessage());
  }
}
echo date('c'), " send-capsules: ok=$ok fail=$fail total=", count($rows), "\n";
