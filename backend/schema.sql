-- ==========================================================
-- MBSH Class of '96 Reunion Site — Database Schema
-- 10 tables, utf8mb4 throughout. Idempotent (CREATE TABLE IF NOT EXISTS).
-- Apply via: bash scripts/setup-mbsh-backend.sh (Phase 4 SSH apply)
-- Or via cPanel phpMyAdmin SQL tab.
-- ==========================================================

CREATE TABLE IF NOT EXISTS rsvps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  maiden_name VARCHAR(100) DEFAULT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  city_state VARCHAR(255) DEFAULT NULL,
  attending ENUM('yes','maybe','no') NOT NULL,
  guest_count INT DEFAULT 1,
  guest_names TEXT DEFAULT NULL,
  dietary TEXT DEFAULT NULL,
  help_planning BOOLEAN DEFAULT FALSE,
  message TEXT DEFAULT NULL,
  display_publicly BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_attending (attending),
  INDEX idx_email (email),
  INDEX idx_display (display_publicly, attending)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sponsors_pending (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contact_name VARCHAR(255) NOT NULL,
  company_name VARCHAR(255) DEFAULT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  tier_interest ENUM('diamond','captain','crew','friend','custom') NOT NULL,
  custom_amount DECIMAL(10,2) DEFAULT NULL,
  logo_path VARCHAR(500) DEFAULT NULL,
  message TEXT DEFAULT NULL,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP DEFAULT NULL,
  INDEX idx_status (status),
  INDEX idx_tier (tier_interest)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sponsors_approved (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pending_id INT DEFAULT NULL,
  display_name VARCHAR(255) NOT NULL,
  tier ENUM('diamond','captain','crew','friend') NOT NULL,
  logo_path VARCHAR(500) DEFAULT NULL,
  website_url VARCHAR(500) DEFAULT NULL,
  display_order INT DEFAULT 0,
  active BOOLEAN DEFAULT TRUE,
  approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pending_id) REFERENCES sponsors_pending(id) ON DELETE SET NULL,
  INDEX idx_tier_order (tier, display_order),
  INDEX idx_active (active)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS memories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contributor_name VARCHAR(255) NOT NULL,
  contributor_email VARCHAR(255) DEFAULT NULL,
  memory_text TEXT NOT NULL,
  photo_path VARCHAR(500) DEFAULT NULL,
  approved BOOLEAN DEFAULT FALSE,
  display_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  approved_at TIMESTAMP DEFAULT NULL,
  INDEX idx_approved (approved, display_order)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS in_memory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(255) NOT NULL,
  graduation_year INT DEFAULT 1996,
  year_passed INT DEFAULT NULL,
  tribute TEXT DEFAULT NULL,
  display_order INT DEFAULT 0,
  active BOOLEAN DEFAULT TRUE,
  added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_active_order (active, display_order)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS time_capsules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  song_answer TEXT DEFAULT NULL,
  person_answer TEXT DEFAULT NULL,
  memory_answer TEXT DEFAULT NULL,
  send_date DATETIME NOT NULL DEFAULT '2026-07-12 11:00:00',
  sent_at TIMESTAMP NULL DEFAULT NULL,
  send_attempts INT DEFAULT 0,
  send_error TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_send_pending (send_date, sent_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chatbot_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question TEXT NOT NULL,
  email VARCHAR(255) DEFAULT NULL,
  matched_faq VARCHAR(255) DEFAULT NULL,
  was_fallback BOOLEAN DEFAULT FALSE,
  responded BOOLEAN DEFAULT FALSE,
  response_notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  responded_at TIMESTAMP DEFAULT NULL,
  INDEX idx_unresponded (responded, was_fallback)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  endpoint VARCHAR(100) NOT NULL,
  attempt_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_endpoint_time (ip_address, endpoint, attempt_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  success BOOLEAN DEFAULT FALSE,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_time (ip_address, attempted_at),
  INDEX idx_success_time (success, attempted_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_session_id VARCHAR(64) NOT NULL,
  admin_label VARCHAR(100) DEFAULT 'committee',
  action VARCHAR(100) NOT NULL,
  target_table VARCHAR(50) DEFAULT NULL,
  target_id INT DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_session (admin_session_id),
  INDEX idx_label (admin_label),
  INDEX idx_action_time (action, performed_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS menu_selections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  selections_json JSON NOT NULL,
  dietary TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ==========================================================
-- SURVEYS TABLE — Matches Microsoft Forms survey fields
-- ==========================================================
DROP TABLE IF EXISTS surveys;
CREATE TABLE surveys (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  hs_name VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  email VARCHAR(255) NOT NULL,
  mailing_address TEXT DEFAULT NULL,
  tshirt_size VARCHAR(20) DEFAULT NULL,
  planning ENUM('Yes','No','Maybe') DEFAULT NULL,
  planning_role VARCHAR(100) DEFAULT NULL,
  contact_pref ENUM('Email','Phone','Mail','No Preference') DEFAULT NULL,
  groupme ENUM('Yes','No') DEFAULT NULL,
  classmates_passed TEXT DEFAULT NULL,
  reunion_month VARCHAR(100) DEFAULT NULL,
  duration VARCHAR(255) DEFAULT NULL,
  days_of_week VARCHAR(255) DEFAULT NULL,
  reunion_type VARCHAR(500) DEFAULT NULL,
  venue_type VARCHAR(500) DEFAULT NULL,
  budget VARCHAR(255) DEFAULT NULL,
  open_other_classes VARCHAR(255) DEFAULT NULL,
  comments TEXT DEFAULT NULL,
  is_imported BOOLEAN DEFAULT FALSE,
  imported_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_created (created_at)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
