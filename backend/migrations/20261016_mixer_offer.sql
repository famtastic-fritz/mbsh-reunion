-- Additive migration. Preserve every legacy amount, tier and payment state.
ALTER TABLE ticket_orders MODIFY COLUMN price_tier ENUM('early_bird','regular','mixer') NOT NULL;
