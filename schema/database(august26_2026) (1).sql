-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: ict_systems_registry
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `application_systems`
--

DROP TABLE IF EXISTS `application_systems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_systems` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `agency_name` varchar(191) NOT NULL,
  `application_name_version` varchar(191) NOT NULL,
  `date_of_implementation` date DEFAULT NULL,
  `development_strategy` enum('In-house','Outsourced','Combination') NOT NULL,
  `owns_ip` enum('Yes','No') NOT NULL,
  `mode_of_implementation` enum('Stand Alone','LAN','WAN','Web-based') NOT NULL,
  `acquisition_cost` decimal(15,2) DEFAULT NULL,
  `annual_maintenance_cost` decimal(15,2) DEFAULT NULL,
  `annual_transaction_amount` decimal(15,2) DEFAULT NULL,
  `no_of_users` int unsigned DEFAULT NULL,
  `type_of_information` enum('External/Public','Internal/Agency Data') NOT NULL,
  `scope_of_operation` enum('International','Nation-wide','Province','Municipal/City') NOT NULL,
  `status` enum('Fully implemented','Not fully rolled out yet, but with pilot implementation','Ongoing development and testing','Not utilized') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `batch` int unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_appsys_user` (`user_id`),
  CONSTRAINT `fk_appsys_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_systems`
--

LOCK TABLES `application_systems` WRITE;
/*!40000 ALTER TABLE `application_systems` DISABLE KEYS */;
INSERT INTO `application_systems` VALUES (4,1,'Commission on Audit','Payroll System v1','2026-08-07','Outsourced','Yes','LAN',50000.00,50000.00,50000.00,100,'Internal/Agency Data','Nation-wide','Not fully rolled out yet, but with pilot implementation','2026-08-26 08:33:57','2026-08-26 08:33:57',1);
/*!40000 ALTER TABLE `application_systems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_projects`
--

DROP TABLE IF EXISTS `ict_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_projects` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `project_name` varchar(191) NOT NULL,
  `description` varchar(255) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `project_contract_cost` decimal(15,2) DEFAULT NULL,
  `third_party_provider` varchar(191) DEFAULT NULL,
  `funding_source` varchar(191) DEFAULT NULL,
  `status` enum('Ongoing','Completed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `batch` int unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_ictproj_user` (`user_id`),
  CONSTRAINT `fk_ictproj_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_projects`
--

LOCK TABLES `ict_projects` WRITE;
/*!40000 ALTER TABLE `ict_projects` DISABLE KEYS */;
INSERT INTO `ict_projects` VALUES (4,1,'EXAMPLE','EXAMPLE','2026-08-07','2026-08-13',20000.00,'Example','Example','Ongoing','2026-08-26 08:33:10','2026-08-26 08:33:10',1);
/*!40000 ALTER TABLE `ict_projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `agency_name` varchar(191) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_initial` varchar(5) DEFAULT NULL,
  `position_designation` varchar(150) NOT NULL,
  `telephone_number` varchar(20) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `survey_stage` enum('choose','first','second','review','submitted') NOT NULL DEFAULT 'choose',
  `first_survey_type` enum('systems','projects') DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `app_systems_done` tinyint(1) NOT NULL DEFAULT '0',
  `ict_projects_done` tinyint(1) NOT NULL DEFAULT '0',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_disabled` tinyint(1) NOT NULL DEFAULT '0',
  `current_batch` int unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Commission on Audit','Orano','John Noel','D','ITAO OJT','09923139504','johnnoelorano@gmail.com','$2y$10$Nm.alUbd8qEmmBQDBdzfh.G89d7ijfkQgjz5rMhjEdU6zhOMBG8YS','2026-07-21 03:17:52','2026-08-26 08:34:01','submitted','projects','2026-08-26 08:34:01',0,0,0,0,2);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-26 16:38:13
