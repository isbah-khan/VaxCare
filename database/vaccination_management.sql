-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: vaccination_management
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `vaccination_management`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `vaccination_management` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `vaccination_management`;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appointments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned NOT NULL,
  `child_id` bigint(20) unsigned NOT NULL,
  `hospital_id` bigint(20) unsigned NOT NULL,
  `vaccine_id` bigint(20) unsigned NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` varchar(300) DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed','not_vaccinated') NOT NULL DEFAULT 'pending',
  `admin_notes` varchar(500) DEFAULT NULL,
  `hospital_notes` varchar(500) DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_appointment_child` (`child_id`),
  KEY `fk_appointment_vaccine` (`vaccine_id`),
  KEY `fk_appointment_admin` (`approved_by`),
  KEY `idx_appointments_parent` (`parent_id`,`appointment_date`),
  KEY `idx_appointments_hospital` (`hospital_id`,`status`,`appointment_date`),
  KEY `idx_appointments_status_date` (`status`,`appointment_date`),
  CONSTRAINT `fk_appointment_admin` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_appointment_child` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointment_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`),
  CONSTRAINT `fk_appointment_parent` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointment_vaccine` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (1,2,1,1,1,'2026-03-07','10:00:00','Routine vaccination','completed',NULL,NULL,1,'2026-08-07 11:13:17','2026-03-07 10:00:00','2026-08-07 09:13:17','2026-08-07 09:13:17'),(2,2,1,1,2,'2026-08-11','11:30:00','Next scheduled dose','approved',NULL,NULL,1,'2026-08-07 11:13:17',NULL,'2026-08-07 09:13:17','2026-08-07 09:13:17'),(3,2,1,1,3,'2026-08-15','09:30:00','Routine appointment request','pending',NULL,NULL,NULL,NULL,NULL,'2026-08-07 09:13:17','2026-08-07 09:13:17'),(4,2,1,1,5,'2026-08-07','14:00:00','Routine follow-up dose','completed','Approved for the requested date.','Dose administered successfully.',1,'2026-08-07 14:15:33','2026-08-07 14:16:41','2026-08-07 09:15:18','2026-08-07 09:16:41');
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `children`
--

DROP TABLE IF EXISTS `children`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `children` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('female','male','other') NOT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `medical_notes` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_children_parent` (`parent_id`),
  KEY `idx_children_birth` (`date_of_birth`),
  CONSTRAINT `fk_child_parent` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `children`
--

LOCK TABLES `children` WRITE;
/*!40000 ALTER TABLE `children` DISABLE KEYS */;
INSERT INTO `children` VALUES (1,2,'Hana Khan','2025-12-07','female','O+','No known allergies.','2026-08-07 09:13:17','2026-08-07 09:13:17');
/*!40000 ALTER TABLE `children` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hospital_vaccines`
--

DROP TABLE IF EXISTS `hospital_vaccines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hospital_vaccines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `hospital_id` bigint(20) unsigned NOT NULL,
  `vaccine_id` bigint(20) unsigned NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 0,
  `stock_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hospital_vaccine` (`hospital_id`,`vaccine_id`),
  KEY `idx_inventory_available` (`vaccine_id`,`is_available`),
  CONSTRAINT `fk_inventory_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inventory_vaccine` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hospital_vaccines`
--

LOCK TABLES `hospital_vaccines` WRITE;
/*!40000 ALTER TABLE `hospital_vaccines` DISABLE KEYS */;
INSERT INTO `hospital_vaccines` VALUES (1,1,1,1,20,'2026-08-07 09:13:17'),(2,1,2,1,20,'2026-08-07 09:13:17'),(3,1,3,1,20,'2026-08-07 09:13:17'),(4,1,4,1,20,'2026-08-07 09:13:17'),(5,1,5,1,19,'2026-08-07 09:16:41'),(6,1,6,1,20,'2026-08-07 09:13:17'),(7,1,7,1,20,'2026-08-07 09:13:17'),(8,1,8,1,20,'2026-08-07 09:13:17'),(9,1,9,1,20,'2026-08-07 09:13:17'),(10,1,10,1,20,'2026-08-07 09:13:17'),(11,1,11,1,20,'2026-08-07 09:13:17'),(12,1,12,1,20,'2026-08-07 09:13:17'),(13,1,13,1,20,'2026-08-07 09:13:17'),(14,1,14,1,20,'2026-08-07 09:13:17'),(15,1,15,1,20,'2026-08-07 09:13:17');
/*!40000 ALTER TABLE `hospital_vaccines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hospitals`
--

DROP TABLE IF EXISTS `hospitals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hospitals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `location_details` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_hospitals_city` (`city`),
  CONSTRAINT `fk_hospital_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hospitals`
--

LOCK TABLES `hospitals` WRITE;
/*!40000 ALTER TABLE `hospitals` DISABLE KEYS */;
INSERT INTO `hospitals` VALUES (1,3,'12 Community Road','Karachi','Near Central Park','2026-08-07 09:13:17','2026-08-07 09:13:17');
/*!40000 ALTER TABLE `hospitals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parent_profiles`
--

DROP TABLE IF EXISTS `parent_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parent_profiles` (
  `user_id` bigint(20) unsigned NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_parent_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parent_profiles`
--

LOCK TABLES `parent_profiles` WRITE;
/*!40000 ALTER TABLE `parent_profiles` DISABLE KEYS */;
INSERT INTO `parent_profiles` VALUES (2,'Gulshan-e-Iqbal, Karachi');
/*!40000 ALTER TABLE `parent_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role` enum('admin','parent','hospital') NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role_status` (`role`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','System Administrator','admin@vaxcare.test','$2y$10$zFcjJJ.mbA4fI5Jqj42P1.FKJcMPnyCQ1/97iTOXpWG5Ecm4mP3Y6','+92 300 0000001','active','2026-08-07 09:13:17','2026-08-07 09:13:17'),(2,'parent','Ayesha Khan','parent@vaxcare.test','$2y$10$aAAGxoPD72rxu0Y22z9JAueEi7OubzyRTIIzpPB/9bIqcxaDU38j.','+92 300 0000002','active','2026-08-07 09:13:17','2026-08-07 09:13:17'),(3,'hospital','Sunrise Children Hospital','hospital@vaxcare.test','$2y$10$aZVViKXwleshOI9NxxGeU.lmJsIHEf.eyNxuF1Ne/xLPQ.2hGrBPK','+92 300 0000003','active','2026-08-07 09:13:17','2026-08-07 09:13:17');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vaccination_records`
--

DROP TABLE IF EXISTS `vaccination_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vaccination_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint(20) unsigned NOT NULL,
  `child_id` bigint(20) unsigned NOT NULL,
  `vaccine_id` bigint(20) unsigned NOT NULL,
  `hospital_id` bigint(20) unsigned NOT NULL,
  `administered_at` datetime NOT NULL,
  `batch_number` varchar(80) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_id` (`appointment_id`),
  KEY `fk_record_vaccine` (`vaccine_id`),
  KEY `fk_record_hospital` (`hospital_id`),
  KEY `idx_records_child_date` (`child_id`,`administered_at`),
  KEY `idx_records_date` (`administered_at`),
  CONSTRAINT `fk_record_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_record_child` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_record_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`),
  CONSTRAINT `fk_record_vaccine` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vaccination_records`
--

LOCK TABLES `vaccination_records` WRITE;
/*!40000 ALTER TABLE `vaccination_records` DISABLE KEYS */;
INSERT INTO `vaccination_records` VALUES (1,1,1,1,1,'2026-03-07 10:00:00','DEMO-001','Vaccination completed without complications.','2026-08-07 09:13:17'),(2,4,1,5,1,'2026-08-07 14:16:41','OPV-260807-A','Dose administered successfully.','2026-08-07 09:16:41');
/*!40000 ALTER TABLE `vaccination_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vaccines`
--

DROP TABLE IF EXISTS `vaccines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vaccines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `disease` varchar(140) NOT NULL,
  `dose_label` varchar(60) NOT NULL,
  `recommended_age_months` smallint(5) unsigned NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vaccine_schedule` (`name`,`dose_label`,`recommended_age_months`),
  KEY `idx_vaccine_age_active` (`recommended_age_months`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vaccines`
--

LOCK TABLES `vaccines` WRITE;
/*!40000 ALTER TABLE `vaccines` DISABLE KEYS */;
INSERT INTO `vaccines` VALUES (1,'BCG','Tuberculosis','Birth dose',0,'Recommended at birth.',1,'2026-08-07 09:13:17'),(2,'OPV','Polio','Birth dose',0,'Oral polio vaccine birth dose.',1,'2026-08-07 09:13:17'),(3,'Pentavalent','Diphtheria, tetanus, pertussis, hepatitis B and Hib','Dose 1',2,'First infant dose.',1,'2026-08-07 09:13:17'),(4,'PCV','Pneumococcal disease','Dose 1',2,'First pneumococcal dose.',1,'2026-08-07 09:13:17'),(5,'OPV','Polio','Dose 1',2,'First scheduled oral polio dose.',1,'2026-08-07 09:13:17'),(6,'Pentavalent','Diphtheria, tetanus, pertussis, hepatitis B and Hib','Dose 2',3,'Second infant dose.',1,'2026-08-07 09:13:17'),(7,'PCV','Pneumococcal disease','Dose 2',3,'Second pneumococcal dose.',1,'2026-08-07 09:13:17'),(8,'OPV','Polio','Dose 2',3,'Second scheduled oral polio dose.',1,'2026-08-07 09:13:17'),(9,'Pentavalent','Diphtheria, tetanus, pertussis, hepatitis B and Hib','Dose 3',4,'Third infant dose.',1,'2026-08-07 09:13:17'),(10,'PCV','Pneumococcal disease','Dose 3',4,'Third pneumococcal dose.',1,'2026-08-07 09:13:17'),(11,'IPV','Polio','Dose 1',4,'First injectable polio dose.',1,'2026-08-07 09:13:17'),(12,'Measles-Rubella','Measles and rubella','Dose 1',9,'First measles-rubella dose.',1,'2026-08-07 09:13:17'),(13,'Typhoid Conjugate','Typhoid','Single dose',9,'Routine typhoid conjugate dose.',1,'2026-08-07 09:13:17'),(14,'Measles-Rubella','Measles and rubella','Dose 2',15,'Second measles-rubella dose.',1,'2026-08-07 09:13:17'),(15,'IPV','Polio','Dose 2',15,'Second injectable polio dose.',1,'2026-08-07 09:13:17');
/*!40000 ALTER TABLE `vaccines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'vaccination_management'
--

--
-- Dumping routines for database 'vaccination_management'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-07 14:17:36
