<?php
declare(strict_types=1);

function fam_portal_email_shell(string $title, string $body): string {
  $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
  return '<div style="max-width:620px;margin:auto;background:#111;color:#f7f0e2;padding:32px;border:1px solid #8f1725;font-family:Arial,sans-serif">'
    . '<p style="color:#d5af62;letter-spacing:.12em;text-transform:uppercase">Miami Beach Senior High · Class of 1996</p>'
    . '<h1 style="font-family:Georgia,serif">' . $safeTitle . '</h1>' . $body
    . '<hr style="border-color:#432"><p style="color:#aaa;font-size:13px">This is a transactional reunion account message. Reply to reach the committee.</p></div>';
}

function fam_queue_portal_email(PDO $pdo,string $key,string $to,string $subject,string $html,string $fromRole='noreply'): void {
  $pdo->prepare('INSERT IGNORE INTO portal_email_jobs (idempotency_key,recipient,subject,html_body,from_role) VALUES (?,?,?,?,?)')->execute([$key,$to,$subject,$html,$fromRole]);
}

function fam_queue_verification_email(PDO $pdo,array $config,string $email,string $firstName,string $token): void {
  $frontend = rtrim((string)($config['portal_frontend_base_url'] ?? (rtrim((string)$config['portal_base_url'], '/') . '/portal')), '/');
  $url = $frontend . '/verify?token=' . rawurlencode($token);
  $name = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
  $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
  fam_queue_portal_email($pdo,'verify:'.fam_token_hash($token),$email,'Verify your reunion account',fam_portal_email_shell('Open your reunion account', "<p>Hi {$name}, confirm your email to unlock your private attendee portal.</p><p><a style=\"background:#9e1b32;color:#fff;padding:12px 18px;text-decoration:none\" href=\"{$safeUrl}\">Verify my email</a></p><p>This link expires in 24 hours.</p>"),'harry');
}

function fam_queue_reset_email(PDO $pdo,array $config,string $email,string $token): void {
  $frontend = rtrim((string)($config['portal_frontend_base_url'] ?? (rtrim((string)$config['portal_base_url'], '/') . '/portal')), '/');
  $url = $frontend . '/reset?token=' . rawurlencode($token);
  $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
  fam_queue_portal_email($pdo,'reset:'.fam_token_hash($token),$email,'Reset your reunion account password',fam_portal_email_shell('Password reset', "<p>Use the secure link below to choose a new password.</p><p><a style=\"background:#9e1b32;color:#fff;padding:12px 18px;text-decoration:none\" href=\"{$safeUrl}\">Reset password</a></p><p>This link expires in one hour. Ignore this email if you did not request it.</p>"),'noreply');
}
