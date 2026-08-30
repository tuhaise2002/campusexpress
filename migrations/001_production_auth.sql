ALTER TABLE `otps` MODIFY `otp_code` char(64) NOT NULL;
CREATE TABLE IF NOT EXISTS `auth_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `identifier_hash` char(64) NOT NULL,
  `action_name` varchar(50) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `auth_attempt_lookup` (`identifier_hash`, `action_name`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
DELETE FROM `otps`;