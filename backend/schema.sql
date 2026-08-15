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
