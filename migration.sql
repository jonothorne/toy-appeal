-- MySQL dump 10.13  Distrib 9.4.0, for macos14.7 (arm64)
--
-- Host: localhost    Database: toyappeal_production
-- ------------------------------------------------------
-- Server version	9.4.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `referral_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_referral` (`referral_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`referral_id`) REFERENCES `referrals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `activity_log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `deletions_log`
--

DROP TABLE IF EXISTS `deletions_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deletions_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `deleted_referral_id` int NOT NULL COMMENT 'Original referral ID before deletion',
  `reference_number` varchar(50) NOT NULL COMMENT 'Referral reference number (e.g., TOY-2025-0001)',
  `child_initials` varchar(10) DEFAULT NULL COMMENT 'Child initials from deleted referral',
  `referrer_name` varchar(255) DEFAULT NULL COMMENT 'Who made the original referral',
  `referrer_organisation` varchar(255) DEFAULT NULL COMMENT 'Organisation that made referral',
  `deleted_by` int DEFAULT NULL COMMENT 'User ID who deleted the referral',
  `deleted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the deletion occurred',
  `reason` text COMMENT 'Reason for deletion provided by user',
  `household_id` int DEFAULT NULL COMMENT 'Original household ID',
  `household_deleted` tinyint(1) DEFAULT '0' COMMENT 'Was the household also deleted?',
  PRIMARY KEY (`id`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_deleted_by` (`deleted_by`),
  KEY `idx_reference_number` (`reference_number`),
  CONSTRAINT `deletions_log_ibfk_1` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permanent audit log of deleted referrals for GDPR compliance';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `households`
--

DROP TABLE IF EXISTS `households`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `households` (
  `id` int NOT NULL AUTO_INCREMENT,
  `referrer_name` varchar(100) NOT NULL,
  `referrer_organisation` varchar(150) NOT NULL,
  `referrer_team` varchar(100) DEFAULT NULL,
  `secondary_contact` varchar(100) DEFAULT NULL,
  `referrer_phone` varchar(20) NOT NULL,
  `referrer_email` varchar(100) NOT NULL,
  `postcode` varchar(10) NOT NULL,
  `duration_known` enum('<1 month','1-6 months','6-12 months','1-2 years','2+ years') NOT NULL,
  `additional_notes` text,
  `gdpr_consent` tinyint(1) NOT NULL DEFAULT '0',
  `gdpr_consent_date` datetime DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_postcode` (`postcode`),
  KEY `idx_organisation` (`referrer_organisation`),
  KEY `idx_submitted` (`submitted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referrals`
--

DROP TABLE IF EXISTS `referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `referrals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reference_number` varchar(20) NOT NULL,
  `household_id` int NOT NULL,
  `child_initials` varchar(10) NOT NULL,
  `child_age` int NOT NULL,
  `child_gender` enum('Male','Female','Other','Prefer not to say') NOT NULL,
  `special_requirements` text,
  `label_printed` tinyint(1) DEFAULT '0',
  `label_printed_at` timestamp NULL DEFAULT NULL,
  `label_printed_by` int DEFAULT NULL,
  `status` enum('pending','fulfilled','located','ready_for_collection','collected') DEFAULT 'pending',
  `zone_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fulfilled_at` timestamp NULL DEFAULT NULL,
  `located_at` timestamp NULL DEFAULT NULL,
  `ready_at` timestamp NULL DEFAULT NULL,
  `collected_at` timestamp NULL DEFAULT NULL,
  `fulfilled_by` int DEFAULT NULL,
  `collected_by` int DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_number` (`reference_number`),
  KEY `zone_id` (`zone_id`),
  KEY `fulfilled_by` (`fulfilled_by`),
  KEY `collected_by` (`collected_by`),
  KEY `idx_reference` (`reference_number`),
  KEY `idx_status` (`status`),
  KEY `idx_household` (`household_id`),
  KEY `idx_created` (`created_at`),
  KEY `label_printed_by` (`label_printed_by`),
  CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE,
  CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `referrals_ibfk_3` FOREIGN KEY (`fulfilled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `referrals_ibfk_4` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `referrals_ibfk_5` FOREIGN KEY (`label_printed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `zones`
--

DROP TABLE IF EXISTS `zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `zones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `zone_name` varchar(50) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_zone_name` (`zone_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-22 10:16:11
-- MySQL dump 10.13  Distrib 9.4.0, for macos14.7 (arm64)
--
-- Host: localhost    Database: toyappeal_production
-- ------------------------------------------------------
-- Server version	9.4.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('collection_hours','Monday-Friday 9am-5pm','2025-10-14 15:26:35');
INSERT INTO `settings` VALUES ('collection_location','Main Warehouse, Norfolk','2025-10-14 15:26:35');
INSERT INTO `settings` VALUES ('current_year','2025','2025-10-14 15:30:48');
INSERT INTO `settings` VALUES ('enable_referrals','1','2025-10-15 13:45:58');
INSERT INTO `settings` VALUES ('site_name','Alive UK Christmas Toy Appeal','2025-10-15 13:45:13');
INSERT INTO `settings` VALUES ('smtp_from_email','office@alive.me.uk','2025-10-21 11:42:22');
INSERT INTO `settings` VALUES ('smtp_from_name','Alive Church Christmas Toy Appeal','2025-10-21 11:42:23');
INSERT INTO `settings` VALUES ('smtp_host','smtp.gmail.com','2025-10-21 11:42:22');
INSERT INTO `settings` VALUES ('smtp_password','fdvz poix dsaa thcu','2025-10-21 11:42:22');
INSERT INTO `settings` VALUES ('smtp_port','587','2025-10-21 11:42:22');
INSERT INTO `settings` VALUES ('smtp_username','office@alive.me.uk','2025-10-21 11:42:22');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$k0NETS/lI428PMuGU9aMq.k5rxkqSmmtBF7tnl29xGPUPPu8eGANy','Administrator','admin@example.com','2025-10-14 15:26:35','2025-10-15 13:44:29',1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `zones`
--

LOCK TABLES `zones` WRITE;
/*!40000 ALTER TABLE `zones` DISABLE KEYS */;
INSERT INTO `zones` VALUES (1,'Zone A','Alive House, Nelson Street, Norwich.','Located on the stage, near the piano.',1,'2025-10-14 15:26:35');
INSERT INTO `zones` VALUES (2,'Zone B',NULL,'Secondary storage',1,'2025-10-14 15:26:35');
INSERT INTO `zones` VALUES (3,'Zone C',NULL,'Overflow area',1,'2025-10-14 15:26:35');
/*!40000 ALTER TABLE `zones` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-22 10:16:21
