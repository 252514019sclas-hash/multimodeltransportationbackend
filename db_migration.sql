-- Migration script for MoveSmart AI Platform
USE `multimodel transportation`;

-- 1. Extend user_profiles for Travel Preferences and Notifications
ALTER TABLE `user_profiles`
  ADD COLUMN IF NOT EXISTS `modes` VARCHAR(255) DEFAULT 'Walking, Cycling, Bus, Metro',
  ADD COLUMN IF NOT EXISTS `low_carbon` TINYINT(1) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `budget_tier` VARCHAR(50) DEFAULT 'Moderate',
  ADD COLUMN IF NOT EXISTS `max_duration` VARCHAR(50) DEFAULT 'Under 45 min',
  ADD COLUMN IF NOT EXISTS `notifications_enabled` TINYINT(1) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `journey_reminders` TINYINT(1) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `booking_updates` TINYINT(1) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `tourist_recommendations` TINYINT(1) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `travel_alerts` TINYINT(1) DEFAULT 1;

-- 2. Tourist Places Table
CREATE TABLE IF NOT EXISTS `tourist_places` (
  `place_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `location_address` VARCHAR(255) NOT NULL,
  `visiting_hours` VARCHAR(100) DEFAULT '9:00 AM - 6:00 PM',
  `entry_fee` VARCHAR(50) DEFAULT 'Free',
  `rating` DECIMAL(2,1) DEFAULT 4.8,
  `distance_km` DECIMAL(4,1) DEFAULT 2.5,
  `duration_visit` VARCHAR(50) DEFAULT '2-3 hours',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`place_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Saved Places Table
CREATE TABLE IF NOT EXISTS `saved_places` (
  `saved_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `place_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`saved_id`),
  UNIQUE KEY `user_place_uniq` (`user_id`, `place_id`),
  CONSTRAINT `fk_saved_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_saved_place` FOREIGN KEY (`place_id`) REFERENCES `tourist_places` (`place_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Journeys Table
CREATE TABLE IF NOT EXISTS `journeys` (
  `journey_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `origin` VARCHAR(150) NOT NULL,
  `destination` VARCHAR(150) NOT NULL,
  `mode` VARCHAR(50) NOT NULL,
  `route_title` VARCHAR(100) NOT NULL,
  `duration_minutes` INT(11) NOT NULL,
  `distance_km` DECIMAL(6,2) NOT NULL,
  `fare` DECIMAL(6,2) NOT NULL,
  `transfers` INT(11) DEFAULT 0,
  `co2_saved_kg` DECIMAL(6,2) DEFAULT 0.00,
  `co2_emitted_kg` DECIMAL(6,2) DEFAULT 0.00,
  `status` ENUM('upcoming', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
  `journey_date` VARCHAR(50) NOT NULL,
  `booking_reference` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`journey_id`),
  KEY `user_id_idx` (`user_id`),
  CONSTRAINT `fk_journey_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Saved Routes Table
CREATE TABLE IF NOT EXISTS `saved_routes` (
  `route_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `origin` VARCHAR(150) NOT NULL,
  `destination` VARCHAR(150) NOT NULL,
  `mode` VARCHAR(50) NOT NULL,
  `duration_text` VARCHAR(50) NOT NULL,
  `cost_text` VARCHAR(50) NOT NULL,
  `co2_text` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`route_id`),
  KEY `user_id_idx` (`user_id`),
  CONSTRAINT `fk_savedroute_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Payment Methods Table
CREATE TABLE IF NOT EXISTS `payment_methods` (
  `payment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `method_type` VARCHAR(30) NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `subtitle` VARCHAR(100) DEFAULT NULL,
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `user_id_idx` (`user_id`),
  CONSTRAINT `fk_payment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. Add Advanced Multi-Modal & AI Tourist Recommendation Preferences
ALTER TABLE `user_profiles`
  ADD COLUMN IF NOT EXISTS `travel_purpose` VARCHAR(50) DEFAULT 'Daily Commute',
  ADD COLUMN IF NOT EXISTS `destination_types` VARCHAR(255) DEFAULT 'Parks, Museums',
  ADD COLUMN IF NOT EXISTS `distance_preference` VARCHAR(50) DEFAULT 'Within City';



