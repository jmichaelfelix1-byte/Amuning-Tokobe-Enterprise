-- Add decline_reason column to printing_orders table
ALTER TABLE printing_orders ADD COLUMN decline_reason TEXT NULL AFTER status;

-- Add decline_reason column to photo_bookings table
ALTER TABLE photo_bookings ADD COLUMN decline_reason TEXT NULL AFTER status;
