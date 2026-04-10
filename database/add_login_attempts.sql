-- Migration: add fields to track login attempts and lockouts
ALTER TABLE `users`
  ADD COLUMN `login_attempts` INT NOT NULL DEFAULT 0,
  ADD COLUMN `last_failed_login` DATETIME NULL DEFAULT NULL,
  ADD COLUMN `locked_until` DATETIME NULL DEFAULT NULL;

-- Optional: create an index on locked_until for efficient checks
CREATE INDEX idx_users_locked_until ON `users` (`locked_until`);
