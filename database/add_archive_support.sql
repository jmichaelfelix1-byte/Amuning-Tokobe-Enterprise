-- Add archive support to printing_orders and photo_bookings tables
-- Note: Columns already exist in the database

-- Ensure indexes exist for archive queries (safe if already created)
CREATE INDEX IF NOT EXISTS idx_archived_printing ON printing_orders (is_archived);
CREATE INDEX IF NOT EXISTS idx_archived_photo ON photo_bookings (is_archived);
