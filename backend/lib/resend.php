<?php
// lib/resend.php — Resend transactional email send + ResendError
declare(strict_types=1);

class ResendError extends RuntimeException {}

function fam_send_email(array $config, string $to, string $subject, string $html, string $fromRole = 'noreply', ?string $text = null): array {
  $fromKey = "resend_from_{$fromRole}";
  if (!isset($config[$fromKey])) throw new ResendError("Unknown sender role: $fromRole");
  $from = "Hi-Tide Reunion <{$config[$fromKey]}>";
  $payload = [
    'from' => $from,
    'to' => [$to],
    'reply_to' => $config['resend_reply_to'] ?? $config['committee_email'],
    'subject' => $subject,
    'html' => $html,
  ];
  if ($text) $payload['text'] = $text;

  $ch = curl_init('https://api.resend.com/emails');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 12,
    CURLOPT_HTTPHEADER     => [
      'Authorization: Bearer ' . ($config['resend_api_key'] ?? ''),
      'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
  ]);
  $body = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err = curl_error($ch);
  curl_close($ch);
  if ($body === false) throw new ResendError("Resend curl error: $err");
  if ($status < 200 || $status >= 300) throw new ResendError("Resend HTTP $status: $body");
  $j = json_decode($body, true);
  return is_array($j) ? $j : [];
}
