CREATE TABLE IF NOT EXISTS poll_questions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  question VARCHAR(255) NOT NULL,
  description TEXT NULL,
  status ENUM('draft','active','closed') NOT NULL DEFAULT 'draft',
  allow_vote_updates TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_poll_questions_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendee portal identity. Public IDs are deliberately non-sequential.
CREATE TABLE IF NOT EXISTS attendee_accounts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id CHAR(36) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('pending_verification','active','suspended','deleted') NOT NULL DEFAULT 'pending_verification',
  email_verified_at DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_attendee_public_id (public_id),
  UNIQUE KEY uniq_attendee_email (email),
  KEY idx_attendee_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendee_profiles (
  attendee_id BIGINT UNSIGNED NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  maiden_name VARCHAR(100) NULL,
  phone VARCHAR(50) NULL,
  city_state VARCHAR(255) NULL,
  graduation_year SMALLINT UNSIGNED NULL,
  bio VARCHAR(1000) NULL,
  display_in_directory TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (attendee_id),
  CONSTRAINT fk_attendee_profile_account FOREIGN KEY (attendee_id) REFERENCES attendee_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendee_preferences (
  attendee_id BIGINT UNSIGNED NOT NULL,
  event_updates TINYINT(1) NOT NULL DEFAULT 1,
  memory_updates TINYINT(1) NOT NULL DEFAULT 1,
  promotional_email TINYINT(1) NOT NULL DEFAULT 0,
  sms_notifications TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (attendee_id),
  CONSTRAINT fk_attendee_preferences_account FOREIGN KEY (attendee_id) REFERENCES attendee_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff access is attached to the durable attendee identity so committee
-- members can use one branded login while remaining ordinary attendees.
-- WordPress administrator access is intentionally separate and owner-only.
CREATE TABLE IF NOT EXISTS portal_staff_memberships (
  attendee_id BIGINT UNSIGNED NOT NULL,
  role ENUM('committee_member','committee_lead','site_owner') NOT NULL DEFAULT 'committee_member',
  status ENUM('active','suspended','revoked') NOT NULL DEFAULT 'active',
  granted_by VARCHAR(255) NULL,
  granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  revoked_at DATETIME NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (attendee_id),
  KEY idx_portal_staff_status_role (status,role),
  CONSTRAINT fk_portal_staff_attendee FOREIGN KEY (attendee_id) REFERENCES attendee_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portal_staff_audit_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_attendee_id BIGINT UNSIGNED NOT NULL,
  actor_role VARCHAR(50) NOT NULL,
  action VARCHAR(100) NOT NULL,
  target_type VARCHAR(100) NULL,
  target_public_id VARCHAR(191) NULL,
  details_json JSON NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_portal_staff_audit_actor (actor_attendee_id,created_at),
  KEY idx_portal_staff_audit_target (target_type,target_public_id),
  CONSTRAINT fk_portal_staff_audit_actor FOREIGN KEY (actor_attendee_id) REFERENCES attendee_accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portal_legacy_record_overrides (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_type ENUM('rsvp','menu','survey','harry','capsule','ticket_order') NOT NULL,
  source_id VARCHAR(100) NOT NULL,
  field_name VARCHAR(100) NOT NULL,
  value_json JSON NULL,
  note VARCHAR(1000) NULL,
  changed_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_legacy_override_record (source_type,source_id,created_at),
  CONSTRAINT fk_legacy_override_actor FOREIGN KEY (changed_by) REFERENCES attendee_accounts(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Canonical attendee-owned reunion response. Legacy production records may
-- seed this row, but portal edits never write back to the read-only snapshot.
CREATE TABLE IF NOT EXISTS portal_event_responses (
  attendee_id BIGINT UNSIGNED NOT NULL,
  legacy_rsvp_id INT UNSIGNED NULL,
  attendance ENUM('yes','maybe','no','unknown') NOT NULL DEFAULT 'unknown',
  guest_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  guest_names VARCHAR(500) NULL,
  phone VARCHAR(50) NULL,
  meal_choice ENUM('fish','chicken','vegetarian','undecided') NOT NULL DEFAULT 'undecided',
  dietary_accessibility VARCHAR(2000) NULL,
  status ENUM('draft','submitted') NOT NULL DEFAULT 'draft',
  migration_sync_status ENUM('local_only','seeded_from_legacy','synced','conflict') NOT NULL DEFAULT 'local_only',
  submitted_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (attendee_id),
  UNIQUE KEY uniq_portal_event_legacy_rsvp (legacy_rsvp_id),
  CONSTRAINT fk_portal_event_attendee FOREIGN KEY (attendee_id) REFERENCES attendee_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portal_conversations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id CHAR(36) NOT NULL,
  attendee_id BIGINT UNSIGNED NULL,
  source_type ENUM('portal','harry','email','system') NOT NULL,
  source_id VARCHAR(100) NULL,
  subject VARCHAR(255) NOT NULL,
  status ENUM('new','assigned','waiting_attendee','waiting_committee','resolved','closed') NOT NULL DEFAULT 'new',
  priority ENUM('normal','high','urgent') NOT NULL DEFAULT 'normal',
  assigned_to BIGINT UNSIGNED NULL,
  response_due_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_conversation_public_id (public_id),
  UNIQUE KEY uniq_conversation_source (source_type,source_id),
  KEY idx_conversation_queue (status,priority,response_due_at),
  CONSTRAINT fk_conversation_attendee FOREIGN KEY (attendee_id) REFERENCES attendee_accounts(id) ON DELETE SET NULL,
  CONSTRAINT fk_conversation_assignee FOREIGN KEY (assigned_to) REFERENCES attendee_accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portal_conversation_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id CHAR(36) NOT NULL,
  conversation_id BIGINT UNSIGNED NOT NULL,
  author_type ENUM('attendee','committee','harry','email','system') NOT NULL,
  author_attendee_id BIGINT UNSIGNED NULL,
  body TEXT NOT NULL,
  delivery_status ENUM('internal','queued','sent','failed') NOT NULL DEFAULT 'internal',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_conversation_message_public_id (public_id),
  KEY idx_conversation_messages (conversation_id,created_at),
  CONSTRAINT fk_message_conversation FOREIGN KEY (conversation_id) REFERENCES portal_conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_message_author FOREIGN KEY (author_attendee_id) REFERENCES attendee_accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trivia_games (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id CHAR(36) NOT NULL,
  title VARCHAR(200) NOT NULL,
  instructions VARCHAR(1000) NOT NULL,
  status ENUM('draft','open','closed','archived') NOT NULL DEFAULT 'draft',
  question_seconds SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  starts_at DATETIME NULL,
  closes_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uniq_trivia_game_public (public_id), KEY idx_trivia_game_status (status,starts_at,closes_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trivia_questions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id CHAR(36) NOT NULL,
  game_id BIGINT UNSIGNED NOT NULL,
  prompt VARCHAR(500) NOT NULL,
  choices_json JSON NOT NULL,
  correct_index TINYINT UNSIGNED NOT NULL,
  explanation VARCHAR(1000) NOT NULL DEFAULT '',
  points SMALLINT UNSIGNED NOT NULL DEFAULT 100,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('draft','published','retired') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uniq_trivia_question_public (public_id), KEY idx_trivia_questions (game_id,status,sort_order),
  CONSTRAINT fk_trivia_question_game FOREIGN KEY (game_id) REFERENCES trivia_games(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trivia_attempts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id CHAR(36) NOT NULL,
  game_id BIGINT UNSIGNED NOT NULL,
  attendee_id BIGINT UNSIGNED NOT NULL,
  status ENUM('active','completed','abandoned') NOT NULL DEFAULT 'active',
  score INT UNSIGNED NOT NULL DEFAULT 0,
  started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  PRIMARY KEY (id), UNIQUE KEY uniq_trivia_attempt_public (public_id), UNIQUE KEY uniq_trivia_attendee_game (game_id,attendee_id),
  CONSTRAINT fk_trivia_attempt_game FOREIGN KEY (game_id) REFERENCES trivia_games(id),
  CONSTRAINT fk_trivia_attempt_attendee FOREIGN KEY (attendee_id) REFERENCES attendee_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trivia_answers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  attempt_id BIGINT UNSIGNED NOT NULL,
  question_id BIGINT UNSIGNED NOT NULL,
  selected_index TINYINT UNSIGNED NOT NULL,
  is_correct TINYINT(1) NOT NULL,
  points_awarded SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  answered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uniq_trivia_answer (attempt_id,question_id),
  CONSTRAINT fk_trivia_answer_attempt FOREIGN KEY (attempt_id) REFERENCES trivia_attempts(id) ON DELETE CASCADE,
  CONSTRAINT fk_trivia_answer_question FOREIGN KEY (question_id) REFERENCES trivia_questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendee_record_links (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  attendee_id BIGINT UNSIGNED NOT NULL,
  source_type ENUM('rsvp','ticket_order','woocommerce_customer','woocommerce_order') NOT NULL,
  source_id VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_attendee_record_source (source_type,source_id),
  KEY idx_attendee_record_owner (attendee_id,source_type),
  CONSTRAINT fk_attendee_record_account FOREIGN KEY (attendee_id) REFERENCES attendee_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendee_auth_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  attendee_id BIGINT UNSIGNED NOT NULL,
  purpose ENUM('verify_email','reset_password') NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_attendee_token_hash (token_hash),
  KEY idx_attendee_tokens_lookup (attendee_id, purpose, expires_at),
  CONSTRAINT fk_attendee_tokens_account FOREIGN KEY (attendee_id) REFERENCES attendee_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendee_login_attempts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email_hash CHAR(64) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attendee_login_throttle (email_hash, ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_wallet_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id CHAR(36) NOT NULL,
  attendee_id BIGINT UNSIGNED NOT NULL,
  ticket_order_id INT UNSIGNED NULL,
  ticket_type VARCHAR(100) NOT NULL,
  holder_name VARCHAR(200) NOT NULL,
  credential_fingerprint CHAR(64) NOT NULL,
  status ENUM('pending','active','checked_in','void','refunded') NOT NULL DEFAULT 'pending',
  issued_at DATETIME NULL,
  checked_in_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_ticket_wallet_public_id (public_id),
  UNIQUE KEY uniq_ticket_wallet_credential (credential_fingerprint),
  KEY idx_ticket_wallet_attendee (attendee_id, status),
  CONSTRAINT fk_ticket_wallet_attendee FOREIGN KEY (attendee_id) REFERENCES attendee_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendee_media_submissions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id CHAR(36) NOT NULL,
  attendee_id BIGINT UNSIGNED NOT NULL,
  media_type ENUM('photo','video','audio','document') NOT NULL,
  title VARCHAR(200) NOT NULL,
  caption VARCHAR(1000) NULL,
  event_year SMALLINT UNSIGNED NULL,
  file_path VARCHAR(1000) NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  consent_to_publish TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('pending','approved','rejected','withdrawn') NOT NULL DEFAULT 'pending',
  moderation_note VARCHAR(1000) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_attendee_media_public_id (public_id),
  KEY idx_attendee_media_owner (attendee_id, status),
  KEY idx_attendee_media_review (status, created_at),
  CONSTRAINT fk_attendee_media_account FOREIGN KEY (attendee_id) REFERENCES attendee_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendee_suggestions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id CHAR(36) NOT NULL,
  attendee_id BIGINT UNSIGNED NOT NULL,
  category ENUM('music','event','website','accessibility','other') NOT NULL DEFAULT 'other',
  subject VARCHAR(200) NOT NULL,
  message VARCHAR(2000) NOT NULL,
  status ENUM('new','reviewing','accepted','declined','closed') NOT NULL DEFAULT 'new',
  admin_note VARCHAR(1000) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_attendee_suggestion_public_id (public_id),
  KEY idx_attendee_suggestion_owner (attendee_id, created_at),
  KEY idx_attendee_suggestion_status (status, created_at),
  CONSTRAINT fk_attendee_suggestion_account FOREIGN KEY (attendee_id) REFERENCES attendee_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS attendee_notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  public_id CHAR(36) NOT NULL,
  attendee_id BIGINT UNSIGNED NOT NULL,
  notification_type VARCHAR(50) NOT NULL,
  title VARCHAR(200) NOT NULL,
  message VARCHAR(1000) NOT NULL,
  action_url VARCHAR(500) NULL,
  read_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_attendee_notification_public_id (public_id),
  KEY idx_attendee_notification_inbox (attendee_id, read_at, created_at),
  CONSTRAINT fk_attendee_notification_account FOREIGN KEY (attendee_id) REFERENCES attendee_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portal_email_jobs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  idempotency_key VARCHAR(191) NOT NULL,
  recipient VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  html_body MEDIUMTEXT NOT NULL,
  from_role ENUM('noreply','committee','harry') NOT NULL DEFAULT 'noreply',
  status ENUM('pending','processing','sent','dead') NOT NULL DEFAULT 'pending',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  next_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  provider_message_id VARCHAR(100) NULL,
  last_error VARCHAR(1000) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_portal_email_idempotency (idempotency_key),
  KEY idx_portal_email_worker (status,next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS poll_options (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  poll_id INT UNSIGNED NOT NULL,
  option_label VARCHAR(255) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_poll_options_poll_id (poll_id),
  CONSTRAINT fk_poll_options_poll FOREIGN KEY (poll_id) REFERENCES poll_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS poll_votes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  poll_id INT UNSIGNED NOT NULL,
  option_id INT UNSIGNED NOT NULL,
  voter_name VARCHAR(150) NOT NULL,
  voter_email VARCHAR(255) NOT NULL,
  voter_note TEXT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_poll_vote_email (poll_id, voter_email),
  KEY idx_poll_votes_poll_id (poll_id),
  KEY idx_poll_votes_option_id (option_id),
  CONSTRAINT fk_poll_votes_poll FOREIGN KEY (poll_id) REFERENCES poll_questions(id) ON DELETE CASCADE,
  CONSTRAINT fk_poll_votes_option FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu selections (Gold Package dinner preferences)
CREATE TABLE IF NOT EXISTS menu_selections (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(200) NOT NULL,
  email VARCHAR(255) NOT NULL,
  selections_json JSON NOT NULL,
  dietary TEXT NULL,
  submitter_email_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  submitter_email_error TEXT NULL,
  submitter_email_message_id VARCHAR(100) NULL,
  submitter_email_sent_at DATETIME NULL,
  committee_email_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  committee_email_error TEXT NULL,
  committee_email_message_id VARCHAR(100) NULL,
  committee_email_sent_at DATETIME NULL,
  notification_email_status VARCHAR(20) NOT NULL DEFAULT 'pending',
  notification_email_error TEXT NULL,
  notification_email_message_id VARCHAR(100) NULL,
  notification_email_sent_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_menu_email (email),
  KEY idx_menu_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket order requests. Payment is collected separately once the committee
-- supplies the active payment account/link.
CREATE TABLE IF NOT EXISTS ticket_orders (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_code VARCHAR(20) NOT NULL,
  contact_name VARCHAR(200) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NULL,
  quantity TINYINT UNSIGNED NOT NULL,
  guest_names TEXT NULL,
  unit_price DECIMAL(8,2) NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  price_tier ENUM('early_bird','regular') NOT NULL,
  payment_status ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_ticket_order_code (order_code),
  KEY idx_ticket_orders_email (email),
  KEY idx_ticket_orders_status (payment_status),
  KEY idx_ticket_orders_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
