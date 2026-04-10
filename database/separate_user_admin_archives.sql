-- Separate archive tables for users vs admins
-- Users have their own archive table, admins have their own archive table

-- ============================================================================
-- ADD ARCHIVE COLUMNS TO MAIN TABLES
-- ============================================================================

ALTER TABLE photo_bookings ADD COLUMN IF NOT EXISTS user_archived TINYINT DEFAULT 0;
ALTER TABLE photo_bookings ADD COLUMN IF NOT EXISTS admin_archived TINYINT DEFAULT 0;
ALTER TABLE photo_bookings ADD COLUMN IF NOT EXISTS archived_date TIMESTAMP NULL;

ALTER TABLE printing_orders ADD COLUMN IF NOT EXISTS user_archived TINYINT DEFAULT 0;
ALTER TABLE printing_orders ADD COLUMN IF NOT EXISTS admin_archived TINYINT DEFAULT 0;
ALTER TABLE printing_orders ADD COLUMN IF NOT EXISTS archived_date TIMESTAMP NULL;



CREATE TABLE IF NOT EXISTS photo_bookings_user_archive LIKE photo_bookings;





CREATE TABLE IF NOT EXISTS printing_orders_user_archive LIKE printing_orders;




ALTER TABLE photo_bookings DROP COLUMN IF EXISTS is_archived;
ALTER TABLE printing_orders DROP COLUMN IF EXISTS is_archived;

-- Create indexes for archive queries
CREATE INDEX IF NOT EXISTS idx_user_id_photo_archive ON photo_bookings_user_archive (user_id);
CREATE INDEX IF NOT EXISTS idx_admin_id_photo_archive ON photo_bookings_admin_archive (user_id);
CREATE INDEX IF NOT EXISTS idx_user_id_print_archive ON printing_orders_user_archive (user_id);
CREATE INDEX IF NOT EXISTS idx_admin_id_print_archive ON printing_orders_admin_archive (user_id);
