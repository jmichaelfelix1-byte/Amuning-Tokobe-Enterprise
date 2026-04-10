-- Create PayMongo Checkouts Table
-- This table stores PayMongo checkout sessions for tracking and reconciliation

CREATE TABLE IF NOT EXISTS `paymongo_checkouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` enum('photo_booking','printing_order') NOT NULL,
  `checkout_id` varchar(255) NOT NULL,
  `payment_id` int(11) NULL,
  `amount` decimal(10, 2) NOT NULL,
  `status` enum('pending','paid','failed','expired') NOT NULL DEFAULT 'pending',
  `payment_intent_id` varchar(255) NULL,
  `notes` text NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `checkout_id` (`checkout_id`),
  KEY `user_id` (`user_id`),
  KEY `item_id_type` (`item_id`, `item_type`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
