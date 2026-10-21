ALTER TABLE ticket_orders
  ADD COLUMN payment_provider VARCHAR(50) NULL AFTER payment_status,
  ADD COLUMN payment_reference VARCHAR(191) NULL AFTER payment_provider,
  ADD COLUMN payment_amount DECIMAL(10,2) NULL AFTER payment_reference,
  ADD COLUMN payment_confirmed_at DATETIME NULL AFTER payment_amount,
  ADD COLUMN payment_confirmed_by BIGINT UNSIGNED NULL AFTER payment_confirmed_at,
  ADD COLUMN payment_email_sent_at DATETIME NULL AFTER payment_confirmed_by,
  ADD COLUMN ticket_email_sent_at DATETIME NULL AFTER payment_email_sent_at,
  ADD UNIQUE KEY uniq_ticket_payment_reference (payment_reference);
