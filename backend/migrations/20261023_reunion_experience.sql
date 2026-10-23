-- Additive experience data; never modifies payment, admission, campaign or identity records.
ALTER TABLE trivia_games MODIFY status ENUM('draft','open','closed','published','archived') NOT NULL DEFAULT 'draft';
ALTER TABLE trivia_games ADD COLUMN IF NOT EXISTS bank_key VARCHAR(100) NULL;
ALTER TABLE trivia_games ADD UNIQUE INDEX IF NOT EXISTS uniq_trivia_bank (bank_key);
ALTER TABLE trivia_questions ADD COLUMN IF NOT EXISTS category VARCHAR(80) NULL;
ALTER TABLE trivia_questions ADD COLUMN IF NOT EXISTS sources_json JSON NULL;
ALTER TABLE attendee_media_submissions ADD COLUMN IF NOT EXISTS derivative_path VARCHAR(1000) NULL;
ALTER TABLE attendee_media_submissions ADD COLUMN IF NOT EXISTS content_hash CHAR(64) NULL;
ALTER TABLE attendee_media_submissions ADD COLUMN IF NOT EXISTS stored_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE attendee_media_submissions ADD UNIQUE INDEX IF NOT EXISTS uniq_media_owner_hash (attendee_id,content_hash);
ALTER TABLE attendee_suggestions ADD COLUMN IF NOT EXISTS request_key CHAR(36) NULL;
ALTER TABLE attendee_suggestions ADD UNIQUE INDEX IF NOT EXISTS uniq_suggestion_request (attendee_id,request_key);
CREATE TABLE IF NOT EXISTS reunion_guess_submissions (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 public_id CHAR(36) NOT NULL UNIQUE,
 attendee_id BIGINT UNSIGNED NOT NULL UNIQUE,
 clues_json JSON NOT NULL,
 answer_name VARCHAR(200) NOT NULL,
 answer_aliases_json JSON NOT NULL,
 consent_to_publish TINYINT(1) NOT NULL DEFAULT 0,
 status ENUM('pending','approved','rejected','published','withdrawn') NOT NULL DEFAULT 'pending',
 moderation_note VARCHAR(1000) NULL,
 reviewed_at DATETIME NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_guess_owner FOREIGN KEY(attendee_id) REFERENCES attendee_accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS reunion_guess_rounds (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 public_id CHAR(36) NOT NULL UNIQUE,
 title VARCHAR(200) NOT NULL,
 status ENUM('draft','open','closed','published','archived') NOT NULL DEFAULT 'draft',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS reunion_guess_cards (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 public_id CHAR(36) NOT NULL UNIQUE,
 round_id BIGINT UNSIGNED NOT NULL,
 submission_id BIGINT UNSIGNED NOT NULL,
 clues_json JSON NOT NULL,
 answer_name VARCHAR(200) NOT NULL,
 answer_aliases_json JSON NOT NULL,
 UNIQUE KEY uniq_guess_round_submission(round_id,submission_id),
 CONSTRAINT fk_guess_card_round FOREIGN KEY(round_id) REFERENCES reunion_guess_rounds(id),
 CONSTRAINT fk_guess_card_submission FOREIGN KEY(submission_id) REFERENCES reunion_guess_submissions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS reunion_guess_answers (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 card_id BIGINT UNSIGNED NOT NULL,
 attendee_id BIGINT UNSIGNED NOT NULL,
 answer_text VARCHAR(200) NOT NULL,
 is_correct TINYINT(1) NOT NULL,
 points_awarded SMALLINT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uniq_guess_answer(card_id,attendee_id),
 CONSTRAINT fk_guess_answer_card FOREIGN KEY(card_id) REFERENCES reunion_guess_cards(id),
 CONSTRAINT fk_guess_answer_attendee FOREIGN KEY(attendee_id) REFERENCES attendee_accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE reunion_guess_rounds MODIFY status ENUM('draft','open','closed','published','archived') NOT NULL DEFAULT 'draft';
