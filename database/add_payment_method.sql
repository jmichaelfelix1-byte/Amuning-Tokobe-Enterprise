-- Add payment_method column to printing_orders table
ALTER TABLE printing_orders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(20) DEFAULT 'online' COMMENT 'Payment method: online or in_person' AFTER special_instruction;
