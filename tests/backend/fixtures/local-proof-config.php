<?php
declare(strict_types=1);

// Disposable Docker proof only. Never deploy this file or add provider secrets.
return [
  'db_host' => '127.0.0.1',
  'db_port' => 3307,
  'db_name' => 'mbsh_portal',
  'db_user' => 'mbsh_local',
  'db_password' => 'change-local-only',
  'resend_api_key' => '',
  'resend_from_domain' => 'example.invalid',
  'resend_from_noreply' => 'noreply@example.invalid',
  'resend_from_committee' => 'committee@example.invalid',
  'resend_from_harry' => 'harry@example.invalid',
  'resend_reply_to' => 'committee@example.invalid',
  'committee_email' => 'committee@example.invalid',
  'menu_notification_email' => 'committee@example.invalid',
  'allowed_origins' => ['http://127.0.0.1:8961'],
  'allowed_origin_patterns' => [],
  'admin_password_hash' => '',
  'admin_csrf_secret' => 'local-proof-admin-secret-2026',
  'portal_token_secret' => 'local-proof-ticket-secret-2026-at-least-32-chars',
  'portal_base_url' => 'http://127.0.0.1:8961',
  'portal_frontend_base_url' => 'http://127.0.0.1:8961/portal-ui',
  'pending_uploads_path' => '/tmp/mbsh-event-cinema-proof/pending',
  'approved_uploads_path' => '/tmp/mbsh-event-cinema-proof/approved',
  'environment' => 'test',
];
