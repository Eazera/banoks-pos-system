-- MySQL dump 10.13  Distrib 8.0.35, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: local
-- ------------------------------------------------------
-- Server version	8.0.35

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
-- Table structure for table `wp_banoks_orders`
--

DROP TABLE IF EXISTS `wp_banoks_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_banoks_orders` (
  `order_id` bigint NOT NULL AUTO_INCREMENT,
  `created_by` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `entry_timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date` date NOT NULL,
  `grand_total` decimal(10,2) NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'pending',
  `branch_key` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'manukan_branch',
  `payment_method` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'cash',
  `received_account` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'store_cash',
  PRIMARY KEY (`order_id`),
  KEY `status` (`status`),
  KEY `branch_key` (`branch_key`),
  KEY `payment_method` (`payment_method`),
  KEY `received_account` (`received_account`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_banoks_orders`
--

LOCK TABLES `wp_banoks_orders` WRITE;
/*!40000 ALTER TABLE `wp_banoks_orders` DISABLE KEYS */;
INSERT INTO `wp_banoks_orders` VALUES (7,'banoks123','2026-05-13 08:24:48','2026-05-13',300.00,'completed','manukan_branch','cash','store_cash'),(8,'banoks123','2026-05-13 08:25:22','2026-05-13',300.00,'completed','manukan_branch','cash','store_cash'),(9,'banoks123','2026-05-13 08:29:55','2026-05-13',300.00,'completed','manukan_branch','cash','store_cash'),(10,'Christian','2026-05-13 08:54:26','2026-05-13',300.00,'completed','manukan_branch','cash','store_cash'),(11,'Christian','2026-05-13 09:18:14','2026-05-13',450.00,'completed','manukan_branch','cash','store_cash'),(12,'Christian','2026-05-13 09:18:36','2026-05-13',600.00,'cancelled','manukan_branch','cash','store_cash'),(13,'banoks123','2026-05-14 01:40:26','2026-05-14',50.00,'completed','manukan_branch','cash','store_cash'),(14,'banoks123','2026-05-14 02:46:42','2026-05-14',150.00,'completed','manukan_branch','cash','store_cash'),(15,'banoks123','2026-05-14 03:45:31','2026-05-14',390.00,'completed','manukan_branch','cash','store_cash'),(16,'banoks123','2026-05-14 04:29:38','2026-05-13',130.00,'completed','manukan_branch','cash','store_cash'),(17,'banoks123','2026-05-14 17:12:06','2026-05-14',130.00,'completed','manukan_branch','cash','store_cash'),(18,'banoks123','2026-05-15 09:50:18','2026-05-15',260.00,'completed','manukan_branch','cash','store_cash'),(19,'banoks123','2026-05-15 12:12:43','2026-05-15',393.00,'completed','manukan_branch','cash','store_cash'),(20,'banoks123','2026-05-15 21:56:19','2026-05-15',150.00,'completed','manukan_branch','cash','store_cash'),(21,'banoks123','2026-05-15 22:07:28','2026-05-15',150.00,'completed','manukan_branch','cash','store_cash'),(22,'banoks123','2026-05-15 22:52:05','2026-05-15',130.00,'completed','manukan_branch','cash','store_cash'),(23,'banoks123','2026-05-16 15:52:53','2026-05-16',130.00,'completed','manukan_branch','cash','store_cash'),(24,'banoks123','2026-05-16 15:55:56','2026-05-16',130.00,'completed','manukan_branch','cash','store_cash'),(25,'banoks123','2026-05-16 16:12:59','2026-05-16',130.00,'completed','manukan_branch','cash','store_cash'),(26,'banoks123','2026-05-18 20:38:51','2026-05-18',130.00,'completed','manukan_branch','cash','store_cash'),(27,'banoks123','2026-05-18 21:16:31','2026-05-17',260.00,'completed','manukan_branch','cash','store_cash'),(28,'banoks123','2026-05-18 21:38:15','2026-05-18',390.00,'completed','manukan_branch','cash','store_cash'),(29,'banoks123','2026-05-18 21:38:43','2026-05-18',450.00,'completed','manukan_branch','cash','store_cash'),(30,'banoks123','2026-05-19 15:30:20','2026-05-19',130.00,'completed','manukan_branch','cash','store_cash'),(31,'banoks123','2026-05-19 15:47:30','2026-05-19',1500.00,'completed','manukan_branch','cash','store_cash');
/*!40000 ALTER TABLE `wp_banoks_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_banoks_order_items`
--

DROP TABLE IF EXISTS `wp_banoks_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_banoks_order_items` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `order_id` bigint NOT NULL,
  `product_id` bigint NOT NULL,
  `qty` int NOT NULL,
  `unit_price_at_sale` decimal(10,2) NOT NULL,
  `sub_total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_banoks_order_items`
--

LOCK TABLES `wp_banoks_order_items` WRITE;
/*!40000 ALTER TABLE `wp_banoks_order_items` DISABLE KEYS */;
INSERT INTO `wp_banoks_order_items` VALUES (1,1,1,1,150.00,150.00),(2,2,1,2,150.00,300.00),(3,3,1,1,150.00,150.00),(4,4,1,1,150.00,150.00),(5,5,1,1,150.00,150.00),(6,6,1,2,150.00,300.00),(7,7,1,2,150.00,300.00),(8,8,1,2,150.00,300.00),(9,9,1,2,150.00,300.00),(10,10,1,2,150.00,300.00),(11,11,1,3,150.00,450.00),(12,12,1,4,150.00,600.00),(13,13,4,2,25.00,50.00),(14,14,6,1,150.00,150.00),(15,15,2,3,130.00,390.00),(16,16,2,1,130.00,130.00),(17,17,2,1,130.00,130.00),(18,18,2,2,130.00,260.00),(19,19,3,1,68.00,68.00),(20,19,4,1,25.00,25.00),(21,19,1,1,150.00,150.00),(22,19,2,1,130.00,130.00),(23,19,5,1,20.00,20.00),(24,20,6,1,150.00,150.00),(25,21,6,1,150.00,150.00),(26,22,2,1,130.00,130.00),(27,23,2,1,130.00,130.00),(28,24,2,1,130.00,130.00),(29,25,2,1,130.00,130.00),(30,26,2,1,130.00,130.00),(31,27,2,2,130.00,260.00),(32,28,2,3,130.00,390.00),(33,29,6,3,150.00,450.00),(34,30,2,1,130.00,130.00),(35,31,6,10,150.00,1500.00);
/*!40000 ALTER TABLE `wp_banoks_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_banoks_online_orders`
--

DROP TABLE IF EXISTS `wp_banoks_online_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_banoks_online_orders` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `online_order_id` varchar(40) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `customer_id` bigint NOT NULL,
  `customer_public_id` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `customer_phone` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `delivery_address` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `delivery_area_id` bigint NOT NULL,
  `delivery_area_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `payment_method` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `payment_status` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'unpaid',
  `order_status` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `driver_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `driver_contact` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `fulfillment_type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'delivery',
  `branch_key` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'manukan_branch',
  PRIMARY KEY (`id`),
  UNIQUE KEY `online_order_id` (`online_order_id`),
  KEY `customer_id` (`customer_id`),
  KEY `order_status` (`order_status`),
  KEY `payment_status` (`payment_status`),
  KEY `created_at` (`created_at`),
  KEY `fulfillment_type` (`fulfillment_type`),
  KEY `branch_key` (`branch_key`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_banoks_online_orders`
--

LOCK TABLES `wp_banoks_online_orders` WRITE;
/*!40000 ALTER TABLE `wp_banoks_online_orders` DISABLE KEYS */;
INSERT INTO `wp_banoks_online_orders` VALUES (1,'ONL-20260515-0001',1,'USER-000001','Christian Fulache','09469013832','Linay',1,'Linay','cod','unpaid','completed',136.00,25.00,161.00,'w','w','','2026-05-15 10:19:47','2026-05-15 18:39:11','2026-05-15 18:39:11',NULL,'delivery','manukan_branch'),(2,'ONL-20260515-0002',2,'USER-000002','Sedie','0123456789','Linay',1,'Linay','cod','unpaid','completed',260.00,25.00,285.00,'w','w','Hello','2026-05-15 10:38:02','2026-05-15 18:39:09','2026-05-15 18:39:09',NULL,'delivery','manukan_branch'),(3,'ONL-20260515-0003',2,'USER-000002','Sedie','0123456789','Poblacion',1,'Linay','gcash','paid','completed',130.00,25.00,155.00,'Christian','09412323223','','2026-05-15 10:45:53','2026-05-15 11:03:20','2026-05-15 11:03:20',NULL,'delivery','manukan_branch'),(4,'ONL-20260515-0004',2,'USER-000002','Sedie','0123456789','Poblacion',1,'Linay','cod','unpaid','completed',650.00,25.00,675.00,'Christian','09412323223','','2026-05-15 11:03:35','2026-05-15 12:00:05','2026-05-15 12:00:05',NULL,'delivery','manukan_branch'),(5,'ONL-20260515-0005',2,'USER-000002','Sedie','0123456789','Poblacion',1,'Linay','cod','unpaid','completed',1430.00,25.00,1455.00,'w','w','','2026-05-15 12:04:37','2026-05-15 18:39:07','2026-05-15 18:39:07',NULL,'delivery','manukan_branch'),(6,'ONL-20260515-0006',2,'USER-000002','Sedie','0123456789','Poblacion',1,'Linay','cod','unpaid','completed',393.00,25.00,418.00,'Christian','09412323223','','2026-05-15 12:15:00','2026-05-15 12:36:11','2026-05-15 12:36:11',NULL,'delivery','manukan_branch'),(7,'ONL-20260515-0007',2,'USER-000002','Sedie','0123456789','Poblacion',1,'Linay','cod','unpaid','completed',150.00,25.00,175.00,'w','w','','2026-05-15 12:57:18','2026-05-15 18:39:05','2026-05-15 18:39:05',NULL,'delivery','manukan_branch'),(8,'ONL-20260515-0008',2,'USER-000002','Sedie','0123456789','Poblacion',1,'Linay','cod','unpaid','completed',150.00,25.00,175.00,'Christian','09469013832','','2026-05-15 13:14:09','2026-05-15 13:19:11','2026-05-15 13:19:11',NULL,'delivery','manukan_branch'),(9,'ONL-20260515-0009',2,'USER-000002','Sedie','0123456789','22',1,'Linay','cod','unpaid','completed',130.00,25.00,155.00,'re','er','','2026-05-15 15:11:14','2026-05-15 18:36:00','2026-05-15 18:36:00',NULL,'delivery','manukan_branch'),(10,'ONL-20260515-0010',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',1430.00,0.00,1430.00,'','','','2026-05-15 15:26:05','2026-05-15 15:28:14','2026-05-15 15:28:14',NULL,'pickup','manukan_branch'),(11,'ONL-20260515-0011',2,'USER-000002','Sedie','0123456789','',0,'','gcash','pending_verification','completed',1430.00,0.00,1430.00,'','','','2026-05-15 15:26:45','2026-05-15 18:38:56','2026-05-15 18:38:56',NULL,'pickup','manukan_branch'),(12,'ONL-20260515-0012',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',25.00,0.00,25.00,'','','1','2026-05-15 15:31:50','2026-05-15 18:38:55','2026-05-15 18:38:55',NULL,'pickup','manukan_branch'),(13,'ONL-20260515-0013',2,'USER-000002','Sedie','0123456789','Poblacion',1,'Linay','cod','unpaid','completed',130.00,25.00,155.00,'reqw','w','111','2026-05-15 15:32:16','2026-05-15 18:39:04','2026-05-15 18:39:04',NULL,'delivery','manukan_branch'),(14,'ONL-20260515-0014',2,'USER-000002','Sedie','0123456789','',0,'','gcash','paid','completed',650.00,0.00,650.00,'','','','2026-05-15 15:38:20','2026-05-15 18:38:53','2026-05-15 18:38:53',NULL,'pickup','manukan_branch'),(15,'ONL-20260515-0015',2,'USER-000002','Sedie','0123456789','Poblacion',1,'Linay','gcash','pending_verification','cancelled',130.00,25.00,155.00,'','','','2026-05-15 15:39:40','2026-05-15 16:00:57',NULL,'2026-05-15 16:00:57','delivery','manukan_branch'),(16,'ONL-20260515-0016',2,'USER-000002','Sedie','0123456789','Poblacion',1,'Linay','gcash','rejected','cancelled',1100.00,25.00,1125.00,'','','','2026-05-15 15:40:26','2026-05-15 18:37:32',NULL,'2026-05-15 18:37:32','delivery','manukan_branch'),(17,'ONL-20260515-0017',2,'USER-000002','Sedie','0123456789','Poblacion',1,'Linay','gcash','rejected','rejected',13875.00,25.00,13900.00,'','','','2026-05-15 15:57:47','2026-05-15 15:58:05',NULL,'2026-05-15 15:58:05','delivery','manukan_branch'),(18,'ONL-20260515-0018',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',7150.00,0.00,7150.00,'','','13123213','2026-05-15 16:06:28','2026-05-15 18:38:51','2026-05-15 18:38:51',NULL,'pickup','manukan_branch'),(19,'ONL-20260515-0019',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',260.00,0.00,260.00,'','','Hello','2026-05-15 16:08:40','2026-05-15 18:38:49','2026-05-15 18:38:49',NULL,'pickup','manukan_branch'),(20,'ONL-20260515-0020',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',40.00,0.00,40.00,'','','','2026-05-15 16:09:05','2026-05-15 18:38:47','2026-05-15 18:38:47',NULL,'pickup','manukan_branch'),(21,'ONL-20260515-0021',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',130.00,0.00,130.00,'','','','2026-05-15 16:12:06','2026-05-15 16:25:19','2026-05-15 16:25:19',NULL,'pickup','manukan_branch'),(22,'ONL-20260515-0022',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',300.00,0.00,300.00,'','','','2026-05-15 16:12:22','2026-05-15 18:38:46','2026-05-15 18:38:46',NULL,'pickup','manukan_branch'),(23,'ONL-20260515-0023',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',1020.00,0.00,1020.00,'','','','2026-05-15 16:13:17','2026-05-15 16:25:11','2026-05-15 16:25:11',NULL,'pickup','manukan_branch'),(24,'ONL-20260515-0024',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',1060.00,0.00,1060.00,'','','','2026-05-15 16:13:39','2026-05-15 16:20:15','2026-05-15 16:20:15',NULL,'pickup','manukan_branch'),(25,'ONL-20260515-0025',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','cancelled',40.00,0.00,40.00,'','','','2026-05-15 16:13:52','2026-05-15 16:14:56',NULL,'2026-05-15 16:14:56','pickup','manukan_branch'),(26,'ONL-20260515-0026',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',68.00,0.00,68.00,'','','','2026-05-15 16:43:28','2026-05-15 18:38:44','2026-05-15 18:38:44',NULL,'pickup','manukan_branch'),(27,'ONL-20260515-0027',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',2860.00,0.00,2860.00,'','','','2026-05-15 16:43:54','2026-05-15 18:38:43','2026-05-15 18:38:43',NULL,'pickup','manukan_branch'),(28,'ONL-20260515-0028',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',15096.00,0.00,15096.00,'','','','2026-05-15 16:45:21','2026-05-15 18:38:41','2026-05-15 18:38:41',NULL,'pickup','manukan_branch'),(29,'ONL-20260515-0029',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',1496.00,0.00,1496.00,'','','','2026-05-15 16:45:38','2026-05-15 18:38:39','2026-05-15 18:38:39',NULL,'pickup','manukan_branch'),(30,'ONL-20260515-0030',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',1100.00,0.00,1100.00,'','','','2026-05-15 16:52:07','2026-05-15 18:38:37','2026-05-15 18:38:37',NULL,'pickup','manukan_branch'),(31,'ONL-20260515-0031',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',260.00,0.00,260.00,'','','','2026-05-15 16:52:38','2026-05-15 18:38:34','2026-05-15 18:38:34',NULL,'pickup','manukan_branch'),(32,'ONL-20260515-0032',2,'USER-000002','Sedie','0123456789','Linay',1,'Linay','cod','unpaid','completed',393.00,25.00,418.00,'reqw','w','','2026-05-15 18:41:26','2026-05-15 18:58:00','2026-05-15 18:58:00',NULL,'delivery','manukan_branch'),(33,'ONL-20260515-0033',2,'USER-000002','Sedie','0123456789','',0,'','gcash','paid','completed',155.00,0.00,155.00,'','','qwe','2026-05-15 18:42:56','2026-05-15 18:57:57','2026-05-15 18:57:57',NULL,'pickup','manukan_branch'),(34,'ONL-20260515-0034',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',186.00,0.00,186.00,'','','','2026-05-15 18:50:50','2026-05-15 18:57:56','2026-05-15 18:57:56',NULL,'pickup','manukan_branch'),(35,'ONL-20260515-0035',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',310.00,0.00,310.00,'','','Test Order Notification','2026-05-15 18:59:20','2026-05-15 19:00:14','2026-05-15 19:00:14',NULL,'pickup','manukan_branch'),(36,'ONL-20260515-0036',2,'USER-000002','Sedie','0123456789','Linay',1,'Linay','cod','unpaid','completed',650.00,25.00,675.00,'reqw','w','','2026-05-15 19:33:37','2026-05-15 22:15:45','2026-05-15 22:15:45',NULL,'delivery','manukan_branch'),(37,'ONL-20260515-0037',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',150.00,0.00,150.00,'','','','2026-05-15 21:56:52','2026-05-15 21:57:46','2026-05-15 21:57:46',NULL,'pickup','manukan_branch'),(38,'ONL-20260515-0038',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',150.00,0.00,150.00,'','','','2026-05-15 22:14:40','2026-05-15 22:15:43','2026-05-15 22:15:43',NULL,'pickup','manukan_branch'),(39,'ONL-20260515-0039',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',130.00,0.00,130.00,'','','','2026-05-15 22:58:32','2026-05-16 09:35:47','2026-05-16 09:35:47',NULL,'pickup','manukan_branch'),(40,'ONL-20260516-0001',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',260.00,0.00,260.00,'','','Test','2026-05-16 16:00:03','2026-05-16 16:01:06','2026-05-16 16:01:06',NULL,'pickup','manukan_branch'),(41,'ONL-20260516-0002',2,'USER-000002','Sedie','0123456789','',0,'','pay_at_pickup','unpaid','completed',300.00,0.00,300.00,'','','','2026-05-16 18:08:25','2026-05-16 18:10:58','2026-05-16 18:10:58',NULL,'pickup','manukan_branch');
/*!40000 ALTER TABLE `wp_banoks_online_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_banoks_online_order_items`
--

DROP TABLE IF EXISTS `wp_banoks_online_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_banoks_online_order_items` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `online_order_id` bigint NOT NULL,
  `product_id` bigint NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `online_order_id` (`online_order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_banoks_online_order_items`
--

LOCK TABLES `wp_banoks_online_order_items` WRITE;
/*!40000 ALTER TABLE `wp_banoks_online_order_items` DISABLE KEYS */;
INSERT INTO `wp_banoks_online_order_items` VALUES (1,1,3,'Chicken Pastil',2,68.00,136.00),(2,2,2,'Pecho',2,130.00,260.00),(3,3,2,'Pecho',1,130.00,130.00),(4,4,2,'Pecho',5,130.00,650.00),(5,5,2,'Pecho',11,130.00,1430.00),(6,6,4,'Mismo Coke',1,25.00,25.00),(7,6,3,'Chicken Pastil',1,68.00,68.00),(8,6,1,'Paa',1,150.00,150.00),(9,6,2,'Pecho',1,130.00,130.00),(10,6,5,'Rice Addons',1,20.00,20.00),(11,7,1,'Paa',1,150.00,150.00),(12,8,1,'Paa',1,150.00,150.00),(13,9,2,'Pecho',1,130.00,130.00),(14,10,2,'Pecho',11,130.00,1430.00),(15,11,2,'Pecho',11,130.00,1430.00),(16,12,4,'Mismo Coke',1,25.00,25.00),(17,13,2,'Pecho',1,130.00,130.00),(18,14,2,'Pecho',5,130.00,650.00),(19,15,2,'Pecho',1,130.00,130.00),(20,16,5,'Rice Addons',55,20.00,1100.00),(21,17,4,'Mismo Coke',555,25.00,13875.00),(22,18,2,'Pecho',55,130.00,7150.00),(23,19,2,'Pecho',2,130.00,260.00),(24,20,5,'Rice Addons',2,20.00,40.00),(25,21,2,'Pecho',1,130.00,130.00),(26,22,1,'Paa',2,150.00,300.00),(27,23,3,'Chicken Pastil',15,68.00,1020.00),(28,24,3,'Chicken Pastil',15,68.00,1020.00),(29,24,5,'Rice Addons',2,20.00,40.00),(30,25,5,'Rice Addons',2,20.00,40.00),(31,26,3,'Chicken Pastil',1,68.00,68.00),(32,27,2,'Pecho',22,130.00,2860.00),(33,28,3,'Chicken Pastil',222,68.00,15096.00),(34,29,3,'Chicken Pastil',22,68.00,1496.00),(35,30,5,'Rice Addons',55,20.00,1100.00),(36,31,2,'Pecho',2,130.00,260.00),(37,32,4,'Mismo Coke',1,25.00,25.00),(38,32,3,'Chicken Pastil',1,68.00,68.00),(39,32,1,'Paa',1,150.00,150.00),(40,32,2,'Pecho',1,130.00,130.00),(41,32,5,'Rice Addons',1,20.00,20.00),(42,33,4,'Mismo Coke',1,25.00,25.00),(43,33,2,'Pecho',1,130.00,130.00),(44,34,4,'Mismo Coke',2,25.00,50.00),(45,34,3,'Chicken Pastil',2,68.00,136.00),(46,35,4,'Mismo Coke',2,25.00,50.00),(47,35,2,'Pecho',2,130.00,260.00),(48,36,2,'Pecho',5,130.00,650.00),(49,37,6,'Java Rice',1,150.00,150.00),(50,38,6,'Java Rice',1,150.00,150.00),(51,39,2,'Pecho',1,130.00,130.00),(52,40,2,'Pecho',2,130.00,260.00),(53,41,6,'Java Rice',2,150.00,300.00);
/*!40000 ALTER TABLE `wp_banoks_online_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_banoks_online_order_status_logs`
--

DROP TABLE IF EXISTS `wp_banoks_online_order_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_banoks_online_order_status_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `online_order_id` bigint NOT NULL,
  `old_status` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `new_status` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `updated_by` bigint NOT NULL DEFAULT '0',
  `note` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `online_order_id` (`online_order_id`),
  KEY `new_status` (`new_status`)
) ENGINE=InnoDB AUTO_INCREMENT=192 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_banoks_online_order_status_logs`
--

LOCK TABLES `wp_banoks_online_order_status_logs` WRITE;
/*!40000 ALTER TABLE `wp_banoks_online_order_status_logs` DISABLE KEYS */;
INSERT INTO `wp_banoks_online_order_status_logs` VALUES (1,1,'','pending',1,'Online order created.','2026-05-15 10:19:47'),(2,2,'','pending',1,'Online order created.','2026-05-15 10:38:02'),(3,3,'','pending',1,'Online order created.','2026-05-15 10:45:53'),(4,3,'pending','verifying',1,'','2026-05-15 11:02:15'),(5,3,'verifying','preparing',1,'','2026-05-15 11:02:35'),(6,3,'preparing','delivering',1,'Hello I will deliver','2026-05-15 11:03:06'),(7,3,'delivering','completed',1,'','2026-05-15 11:03:20'),(8,4,'','pending',1,'Online order created.','2026-05-15 11:03:35'),(9,2,'pending','verifying',1,'','2026-05-15 11:03:48'),(10,1,'pending','verifying',1,'','2026-05-15 11:03:51'),(11,4,'pending','verifying',1,'','2026-05-15 11:59:41'),(12,4,'verifying','preparing',1,'','2026-05-15 11:59:46'),(13,4,'preparing','delivering',1,'Hello I will deliver','2026-05-15 12:00:01'),(14,4,'delivering','completed',1,'','2026-05-15 12:00:05'),(15,2,'verifying','preparing',1,'','2026-05-15 12:00:12'),(16,1,'verifying','preparing',1,'','2026-05-15 12:00:22'),(17,5,'','pending',1,'Online order created.','2026-05-15 12:04:37'),(18,5,'pending','verifying',1,'','2026-05-15 12:04:53'),(19,5,'verifying','preparing',1,'','2026-05-15 12:05:10'),(20,6,'','pending',1,'Online order created.','2026-05-15 12:15:00'),(21,6,'pending','verifying',1,'','2026-05-15 12:16:43'),(22,6,'verifying','preparing',1,'','2026-05-15 12:16:49'),(23,6,'preparing','delivering',1,'','2026-05-15 12:36:09'),(24,6,'delivering','completed',1,'','2026-05-15 12:36:11'),(25,7,'','pending',1,'Online order created.','2026-05-15 12:57:18'),(26,7,'pending','verifying',1,'','2026-05-15 12:59:07'),(27,7,'verifying','preparing',1,'','2026-05-15 12:59:28'),(28,8,'','pending',1,'Online order created.','2026-05-15 13:14:09'),(29,8,'pending','verifying',1,'','2026-05-15 13:14:25'),(30,8,'verifying','preparing',1,'','2026-05-15 13:15:07'),(31,8,'preparing','delivering',2,'','2026-05-15 13:17:52'),(32,8,'delivering','completed',2,'','2026-05-15 13:19:11'),(33,9,'','pending',0,'Online order created.','2026-05-15 15:11:14'),(34,10,'','pending',0,'Online order created.','2026-05-15 15:26:05'),(35,11,'','pending',0,'Online order created.','2026-05-15 15:26:45'),(36,11,'pending','verifying',2,'','2026-05-15 15:27:28'),(37,10,'pending','verifying',2,'','2026-05-15 15:27:49'),(38,10,'verifying','preparing',2,'','2026-05-15 15:28:07'),(39,10,'preparing','ready_for_pickup',2,'','2026-05-15 15:28:11'),(40,10,'ready_for_pickup','completed',2,'','2026-05-15 15:28:14'),(41,11,'verifying','preparing',2,'','2026-05-15 15:28:19'),(42,11,'preparing','ready_for_pickup',2,'','2026-05-15 15:28:22'),(43,12,'','pending',0,'Online order created.','2026-05-15 15:31:50'),(44,13,'','pending',0,'Online order created.','2026-05-15 15:32:16'),(45,13,'pending','verifying',2,'','2026-05-15 15:32:23'),(46,13,'verifying','preparing',2,'','2026-05-15 15:32:25'),(47,14,'','pending',0,'Online order created.','2026-05-15 15:38:20'),(48,15,'','pending',0,'Online order created.','2026-05-15 15:39:40'),(49,16,'','pending',0,'Online order created.','2026-05-15 15:40:26'),(50,9,'pending','verifying',2,'','2026-05-15 15:55:01'),(51,9,'verifying','preparing',2,'','2026-05-15 15:55:45'),(52,17,'','pending',0,'Online order created.','2026-05-15 15:57:47'),(53,17,'pending','rejected',2,'GCash payment rejected: qeweweweqdqwdqe','2026-05-15 15:58:05'),(54,15,'pending','cancelled',2,'qwewe','2026-05-15 16:00:57'),(55,18,'','pending',0,'Online order created.','2026-05-15 16:06:28'),(56,19,'','pending',0,'Online order created.','2026-05-15 16:08:40'),(57,20,'','pending',0,'Online order created.','2026-05-15 16:09:05'),(58,21,'','pending',0,'Online order created.','2026-05-15 16:12:06'),(59,22,'','pending',0,'Online order created.','2026-05-15 16:12:22'),(60,23,'','pending',0,'Online order created.','2026-05-15 16:13:17'),(61,24,'','pending',0,'Online order created.','2026-05-15 16:13:39'),(62,25,'','pending',0,'Online order created.','2026-05-15 16:13:53'),(63,25,'pending','cancelled',2,'Test cancel','2026-05-15 16:14:56'),(64,24,'pending','verifying',2,'','2026-05-15 16:15:04'),(65,24,'verifying','preparing',2,'','2026-05-15 16:15:08'),(66,24,'preparing','ready_for_pickup',2,'','2026-05-15 16:15:11'),(67,24,'ready_for_pickup','completed',2,'','2026-05-15 16:20:15'),(68,23,'pending','verifying',2,'','2026-05-15 16:20:28'),(69,23,'verifying','preparing',2,'','2026-05-15 16:25:04'),(70,23,'preparing','ready_for_pickup',2,'','2026-05-15 16:25:09'),(71,23,'ready_for_pickup','completed',2,'','2026-05-15 16:25:11'),(72,21,'pending','verifying',2,'','2026-05-15 16:25:13'),(73,21,'verifying','preparing',2,'','2026-05-15 16:25:15'),(74,21,'preparing','ready_for_pickup',2,'','2026-05-15 16:25:17'),(75,21,'ready_for_pickup','completed',2,'','2026-05-15 16:25:19'),(76,20,'pending','verifying',2,'','2026-05-15 16:31:46'),(77,22,'pending','verifying',2,'','2026-05-15 16:41:38'),(78,22,'verifying','preparing',2,'','2026-05-15 16:41:44'),(79,22,'preparing','ready_for_pickup',2,'','2026-05-15 16:41:59'),(80,9,'preparing','delivering',2,'','2026-05-15 16:42:05'),(81,26,'','pending',0,'Online order created.','2026-05-15 16:43:28'),(82,27,'','pending',0,'Online order created.','2026-05-15 16:43:54'),(83,28,'','pending',0,'Online order created.','2026-05-15 16:45:21'),(84,29,'','pending',0,'Online order created.','2026-05-15 16:45:38'),(85,30,'','pending',0,'Online order created.','2026-05-15 16:52:07'),(86,31,'','pending',0,'Online order created.','2026-05-15 16:52:38'),(87,9,'delivering','completed',2,'','2026-05-15 18:36:00'),(88,31,'pending','verifying',2,'','2026-05-15 18:36:14'),(89,30,'pending','verifying',2,'','2026-05-15 18:36:16'),(90,29,'pending','verifying',2,'','2026-05-15 18:36:18'),(91,28,'pending','verifying',2,'','2026-05-15 18:36:20'),(92,27,'pending','verifying',2,'','2026-05-15 18:36:22'),(93,26,'pending','verifying',2,'','2026-05-15 18:36:24'),(94,19,'pending','verifying',2,'','2026-05-15 18:36:25'),(95,18,'pending','verifying',2,'','2026-05-15 18:36:27'),(96,14,'pending','verifying',2,'','2026-05-15 18:36:45'),(97,12,'pending','verifying',2,'','2026-05-15 18:36:48'),(98,31,'verifying','preparing',2,'','2026-05-15 18:36:52'),(99,30,'verifying','preparing',2,'','2026-05-15 18:36:54'),(100,29,'verifying','preparing',2,'','2026-05-15 18:36:55'),(101,28,'verifying','preparing',2,'','2026-05-15 18:36:56'),(102,27,'verifying','preparing',2,'','2026-05-15 18:36:59'),(103,26,'verifying','preparing',2,'','2026-05-15 18:37:01'),(104,20,'verifying','preparing',2,'','2026-05-15 18:37:03'),(105,19,'verifying','preparing',2,'','2026-05-15 18:37:06'),(106,18,'verifying','preparing',2,'','2026-05-15 18:37:16'),(107,14,'verifying','preparing',2,'','2026-05-15 18:37:17'),(108,12,'verifying','preparing',2,'','2026-05-15 18:37:19'),(109,16,'pending','cancelled',2,'Cancell','2026-05-15 18:37:32'),(110,31,'preparing','ready_for_pickup',2,'','2026-05-15 18:37:54'),(111,30,'preparing','ready_for_pickup',2,'','2026-05-15 18:37:56'),(112,29,'preparing','ready_for_pickup',2,'','2026-05-15 18:37:57'),(113,28,'preparing','ready_for_pickup',2,'','2026-05-15 18:37:59'),(114,27,'preparing','ready_for_pickup',2,'','2026-05-15 18:38:01'),(115,26,'preparing','ready_for_pickup',2,'','2026-05-15 18:38:03'),(116,20,'preparing','ready_for_pickup',2,'','2026-05-15 18:38:04'),(117,19,'preparing','ready_for_pickup',2,'','2026-05-15 18:38:06'),(118,18,'preparing','ready_for_pickup',2,'','2026-05-15 18:38:08'),(119,14,'preparing','ready_for_pickup',2,'','2026-05-15 18:38:10'),(120,13,'preparing','delivering',2,'','2026-05-15 18:38:15'),(121,12,'preparing','ready_for_pickup',2,'','2026-05-15 18:38:17'),(122,7,'preparing','delivering',2,'','2026-05-15 18:38:20'),(123,5,'preparing','delivering',2,'','2026-05-15 18:38:24'),(124,2,'preparing','delivering',2,'','2026-05-15 18:38:27'),(125,1,'preparing','delivering',2,'','2026-05-15 18:38:30'),(126,31,'ready_for_pickup','completed',2,'','2026-05-15 18:38:34'),(127,30,'ready_for_pickup','completed',2,'','2026-05-15 18:38:37'),(128,29,'ready_for_pickup','completed',2,'','2026-05-15 18:38:39'),(129,28,'ready_for_pickup','completed',2,'','2026-05-15 18:38:41'),(130,27,'ready_for_pickup','completed',2,'','2026-05-15 18:38:43'),(131,26,'ready_for_pickup','completed',2,'','2026-05-15 18:38:44'),(132,22,'ready_for_pickup','completed',2,'','2026-05-15 18:38:46'),(133,20,'ready_for_pickup','completed',2,'','2026-05-15 18:38:47'),(134,19,'ready_for_pickup','completed',2,'','2026-05-15 18:38:49'),(135,18,'ready_for_pickup','completed',2,'','2026-05-15 18:38:51'),(136,14,'ready_for_pickup','completed',2,'','2026-05-15 18:38:53'),(137,12,'ready_for_pickup','completed',2,'','2026-05-15 18:38:55'),(138,11,'ready_for_pickup','completed',2,'','2026-05-15 18:38:56'),(139,13,'delivering','completed',2,'','2026-05-15 18:39:04'),(140,7,'delivering','completed',2,'','2026-05-15 18:39:05'),(141,5,'delivering','completed',2,'','2026-05-15 18:39:07'),(142,2,'delivering','completed',2,'','2026-05-15 18:39:09'),(143,1,'delivering','completed',2,'','2026-05-15 18:39:11'),(144,32,'','pending',0,'Online order created.','2026-05-15 18:41:26'),(145,32,'pending','verifying',2,'','2026-05-15 18:42:07'),(146,32,'verifying','preparing',2,'','2026-05-15 18:42:20'),(147,33,'','pending',0,'Online order created.','2026-05-15 18:42:56'),(148,33,'pending','verifying',2,'','2026-05-15 18:46:49'),(149,33,'verifying','preparing',2,'','2026-05-15 18:46:52'),(150,34,'','pending',0,'Online order created.','2026-05-15 18:50:50'),(151,34,'pending','verifying',2,'','2026-05-15 18:54:22'),(152,34,'verifying','preparing',2,'','2026-05-15 18:54:24'),(153,34,'preparing','ready_for_pickup',2,'','2026-05-15 18:57:42'),(154,33,'preparing','ready_for_pickup',2,'','2026-05-15 18:57:44'),(155,32,'preparing','delivering',2,'','2026-05-15 18:57:51'),(156,34,'ready_for_pickup','completed',2,'','2026-05-15 18:57:56'),(157,33,'ready_for_pickup','completed',2,'','2026-05-15 18:57:57'),(158,32,'delivering','completed',2,'','2026-05-15 18:58:00'),(159,35,'','pending',0,'Online order created.','2026-05-15 18:59:20'),(160,35,'pending','verifying',2,'','2026-05-15 19:00:05'),(161,35,'verifying','preparing',2,'','2026-05-15 19:00:08'),(162,35,'preparing','ready_for_pickup',2,'','2026-05-15 19:00:11'),(163,35,'ready_for_pickup','completed',2,'','2026-05-15 19:00:14'),(164,36,'','pending',0,'Online order created.','2026-05-15 19:33:37'),(165,36,'pending','verifying',2,'','2026-05-15 21:42:27'),(166,37,'','pending',0,'Online order created.','2026-05-15 21:56:52'),(167,37,'pending','verifying',1,'','2026-05-15 21:57:08'),(168,37,'verifying','preparing',1,'','2026-05-15 21:57:23'),(169,36,'verifying','preparing',1,'','2026-05-15 21:57:34'),(170,37,'preparing','ready_for_pickup',1,'','2026-05-15 21:57:42'),(171,37,'ready_for_pickup','completed',1,'','2026-05-15 21:57:46'),(172,38,'','pending',0,'Online order created.','2026-05-15 22:14:40'),(173,38,'pending','verifying',1,'','2026-05-15 22:14:58'),(174,38,'verifying','preparing',1,'','2026-05-15 22:15:07'),(175,38,'preparing','ready_for_pickup',1,'','2026-05-15 22:15:17'),(176,36,'preparing','delivering',1,'','2026-05-15 22:15:39'),(177,38,'ready_for_pickup','completed',1,'','2026-05-15 22:15:43'),(178,36,'delivering','completed',1,'','2026-05-15 22:15:45'),(179,39,'','pending',0,'Online order created.','2026-05-15 22:58:32'),(180,39,'pending','verifying',1,'','2026-05-15 22:58:54'),(181,39,'verifying','preparing',1,'','2026-05-15 22:58:59'),(182,39,'preparing','ready_for_pickup',1,'','2026-05-16 09:35:43'),(183,39,'ready_for_pickup','completed',1,'','2026-05-16 09:35:47'),(184,40,'','pending',0,'Online order created.','2026-05-16 16:00:03'),(185,40,'pending','preparing',1,'','2026-05-16 16:00:46'),(186,40,'preparing','ready_for_pickup',1,'','2026-05-16 16:01:00'),(187,40,'ready_for_pickup','completed',1,'','2026-05-16 16:01:06'),(188,41,'','pending',0,'Online order created.','2026-05-16 18:08:25'),(189,41,'pending','preparing',1,'','2026-05-16 18:09:27'),(190,41,'preparing','ready_for_pickup',1,'','2026-05-16 18:10:38'),(191,41,'ready_for_pickup','completed',1,'','2026-05-16 18:10:58');
/*!40000 ALTER TABLE `wp_banoks_online_order_status_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_banoks_payment_proofs`
--

DROP TABLE IF EXISTS `wp_banoks_payment_proofs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_banoks_payment_proofs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `online_order_id` bigint NOT NULL,
  `reference_number` varchar(120) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `screenshot_url` text COLLATE utf8mb4_unicode_520_ci,
  `attachment_id` bigint NOT NULL DEFAULT '0',
  `verified_by` bigint NOT NULL DEFAULT '0',
  `verified_at` datetime DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `online_order_id` (`online_order_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_banoks_payment_proofs`
--

LOCK TABLES `wp_banoks_payment_proofs` WRITE;
/*!40000 ALTER TABLE `wp_banoks_payment_proofs` DISABLE KEYS */;
INSERT INTO `wp_banoks_payment_proofs` VALUES (1,3,'','http://banoks.local/wp-content/uploads/2026/05/ChatGPT-Image-May-15-2026-09_17_17-AM.png',63,1,'2026-05-15 11:02:09','verified','2026-05-15 10:45:53'),(2,11,'','http://banoks.local/wp-content/uploads/2026/05/ChatGPT-Image-May-15-2026-09_17_17-AM-1.png',64,0,NULL,'pending','2026-05-15 15:26:45'),(3,14,'','http://banoks.local/wp-content/uploads/2026/05/ChatGPT-Image-May-15-2026-09_17_17-AM-2.png',65,2,'2026-05-15 18:36:43','verified','2026-05-15 15:38:20'),(4,15,'','',0,0,NULL,'pending','2026-05-15 15:39:40'),(5,16,'','',0,2,'2026-05-15 15:44:42','rejected','2026-05-15 15:40:26'),(6,17,'','http://banoks.local/wp-content/uploads/2026/05/ChatGPT-Image-May-15-2026-09_17_17-AM-3.png',66,2,'2026-05-15 15:58:05','rejected','2026-05-15 15:57:47'),(7,33,'','http://banoks.local/wp-content/uploads/2026/05/ChatGPT-Image-May-15-2026-09_17_17-AM-4.png',67,2,'2026-05-15 18:46:45','verified','2026-05-15 18:42:56');
/*!40000 ALTER TABLE `wp_banoks_payment_proofs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_banoks_expenses`
--

DROP TABLE IF EXISTS `wp_banoks_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_banoks_expenses` (
  `expense_id` bigint NOT NULL AUTO_INCREMENT,
  `description` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cash_source` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'store_cash',
  `branch_key` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'manukan_branch',
  PRIMARY KEY (`expense_id`),
  KEY `branch_key` (`branch_key`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_banoks_expenses`
--

LOCK TABLES `wp_banoks_expenses` WRITE;
/*!40000 ALTER TABLE `wp_banoks_expenses` DISABLE KEYS */;
INSERT INTO `wp_banoks_expenses` VALUES (7,'Ingredients',300.00,'2026-03-13','2026-05-13 16:25:06','store_cash','manukan_branch'),(8,'Coke',300.00,'2026-05-12','2026-05-13 16:25:35','store_cash','manukan_branch'),(9,'Coke',300.00,'2026-05-13','2026-05-13 16:30:49','store_cash','manukan_branch'),(10,'Ingredients',300.00,'2026-05-13','2026-05-13 16:34:40','store_cash','manukan_branch'),(15,'Ingredients',100.00,'2026-05-13','2026-05-14 12:19:10','store_cash','manukan_branch'),(16,'Ingredients',350.00,'2026-05-12','2026-05-14 12:19:21','store_cash','manukan_branch');
/*!40000 ALTER TABLE `wp_banoks_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_banoks_finance_transactions`
--

DROP TABLE IF EXISTS `wp_banoks_finance_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_banoks_finance_transactions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `transaction_type` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'cash_sales_claim',
  `source_account` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'store_cash',
  `destination_account` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `branch_key` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'manukan_branch',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `transaction_date` date NOT NULL,
  `note` text COLLATE utf8mb4_unicode_520_ci,
  `created_by` bigint NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `transaction_type` (`transaction_type`),
  KEY `source_account` (`source_account`),
  KEY `destination_account` (`destination_account`),
  KEY `branch_key` (`branch_key`),
  KEY `transaction_date` (`transaction_date`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_banoks_finance_transactions`
--

LOCK TABLES `wp_banoks_finance_transactions` WRITE;
/*!40000 ALTER TABLE `wp_banoks_finance_transactions` DISABLE KEYS */;
INSERT INTO `wp_banoks_finance_transactions` VALUES (1,'owner_capital_addition','owner_capital','cash_on_hand','',1000.00,'2026-05-18','Test add balance',1,'2026-05-18 21:11:37'),(2,'account_transfer','cash_on_hand','gcash_balance','',500.00,'2026-05-18','Test transfer to gcash',1,'2026-05-18 21:12:25'),(3,'cash_sales_claim','store_cash','cash_on_hand','manukan_branch',970.00,'2026-05-18','Test',1,'2026-05-18 22:24:55'),(4,'gcash_sales_claim','gcash_sales','gcash_balance','manukan_branch',2390.00,'2026-05-15','',1,'2026-05-18 22:25:35'),(5,'owner_capital_addition','owner_capital','bank_balance','',25000.00,'2026-05-19','',1,'2026-05-19 09:17:00');
/*!40000 ALTER TABLE `wp_banoks_finance_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_banoks_inventory_movements`
--

DROP TABLE IF EXISTS `wp_banoks_inventory_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_banoks_inventory_movements` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `inventory_item_id` bigint NOT NULL,
  `movement_type` varchar(40) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `old_stock` decimal(12,3) NOT NULL DEFAULT '0.000',
  `new_stock` decimal(12,3) NOT NULL DEFAULT '0.000',
  `change_amount` decimal(12,3) NOT NULL DEFAULT '0.000',
  `source` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'manual',
  `source_id` varchar(80) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `updated_by` bigint NOT NULL DEFAULT '0',
  `note` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `affects_cash_balance` tinyint(1) NOT NULL DEFAULT '0',
  `cash_source` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'store_cash',
  `location_key` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'production',
  PRIMARY KEY (`id`),
  KEY `inventory_item_id` (`inventory_item_id`),
  KEY `movement_type` (`movement_type`),
  KEY `created_at` (`created_at`),
  KEY `location_key` (`location_key`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_banoks_inventory_movements`
--

LOCK TABLES `wp_banoks_inventory_movements` WRITE;
/*!40000 ALTER TABLE `wp_banoks_inventory_movements` DISABLE KEYS */;
INSERT INTO `wp_banoks_inventory_movements` VALUES (1,1,'stock_in',0.000,25.000,25.000,'manual','',1,'Initial stock.','2026-05-15 22:40:23',0.00,0.00,0,'store_cash','production'),(2,2,'stock_in',0.000,20.000,20.000,'manual','',1,'Initial stock.','2026-05-15 22:50:53',0.00,0.00,0,'store_cash','production'),(3,2,'recipe_usage',20.000,19.000,-1.000,'walk_in','POS-22',1,'Ingredient stock deducted from order.','2026-05-15 22:52:09',0.00,0.00,0,'store_cash','production'),(4,2,'recipe_usage',19.000,18.000,-1.000,'online','ONL-20260515-0039',1,'Ingredient stock deducted from order.','2026-05-15 22:58:59',100.00,100.00,0,'store_cash','production'),(5,2,'recipe_usage',18.000,17.000,-1.000,'walk_in','POS-23',1,'Ingredient stock deducted from order.','2026-05-16 15:53:30',100.00,100.00,0,'store_cash','production'),(6,2,'recipe_usage',17.000,16.000,-1.000,'walk_in','POS-24',1,'Ingredient stock deducted from order.','2026-05-16 15:57:45',100.00,100.00,0,'store_cash','production'),(7,2,'recipe_usage',16.000,14.000,-2.000,'online_pickup','ONL-20260516-0001',1,'Ingredient stock deducted from order.','2026-05-16 16:00:46',100.00,200.00,0,'store_cash','production'),(8,2,'usage',14.000,4.000,-10.000,'manual','',1,'','2026-05-16 16:11:57',100.00,1000.00,0,'store_cash','production'),(9,2,'recipe_usage',4.000,3.000,-1.000,'walk_in','POS-25',1,'Ingredient stock deducted from order.','2026-05-16 16:13:03',100.00,100.00,0,'store_cash','production'),(10,3,'stock_in',0.000,5.000,5.000,'manual','',1,'Initial stock.','2026-05-16 16:13:57',100.00,500.00,0,'store_cash','production'),(11,4,'stock_in',0.000,25.000,25.000,'manual','',1,'','2026-05-16 19:18:21',25.00,625.00,1,'store_cash','production'),(12,2,'stock_in',3.000,28.000,25.000,'manual','',1,'','2026-05-18 22:27:46',125.00,3125.00,1,'bank_balance','production'),(13,2,'stock_in',0.000,25.000,25.000,'manual','',1,'Stock in: Raw Chicken Pecho - 25 pcs.','2026-05-19 08:11:26',100.00,2500.00,1,'cash_on_hand','manukan_branch'),(14,2,'transfer_out',28.000,3.000,-25.000,'manual','',1,'test','2026-05-19 09:07:17',100.00,2500.00,0,'store_cash','production'),(15,2,'transfer_in',25.000,50.000,25.000,'manual','',1,'test','2026-05-19 09:07:17',100.00,2500.00,0,'store_cash','manukan_branch'),(16,3,'stock_in',5.000,55.000,50.000,'manual','',1,'Stock in: Raw Chicken Paa - 50 pcs. Note: test','2026-05-19 09:17:59',100.00,5000.00,1,'bank_balance','production'),(17,3,'transfer_out',55.000,5.000,-50.000,'manual','',1,'test','2026-05-19 09:18:41',100.00,5000.00,0,'store_cash','production'),(18,3,'transfer_in',0.000,50.000,50.000,'manual','',1,'test','2026-05-19 09:18:41',100.00,5000.00,0,'store_cash','manukan_branch'),(19,1,'transfer_out',25.000,0.000,-25.000,'manual','',1,'test','2026-05-19 09:24:25',0.00,0.00,0,'store_cash','production'),(20,1,'transfer_in',0.000,25.000,25.000,'manual','',1,'test','2026-05-19 09:24:25',0.00,0.00,0,'store_cash','manukan_branch'),(21,3,'stock_in',5.000,15.000,10.000,'manual','',1,'Stock in: Raw Chicken Paa - 10 pcs. Note: test','2026-05-19 09:27:45',113.00,1130.00,1,'cash_on_hand','production'),(22,4,'transfer_out',25.000,0.000,-25.000,'manual','',1,'Branch stock added from Production Inventory.','2026-05-19 09:49:15',25.00,625.00,0,'store_cash','production'),(23,4,'transfer_in',0.000,25.000,25.000,'manual','',1,'Branch stock added from Production Inventory.','2026-05-19 09:49:15',25.00,625.00,0,'store_cash','manukan_branch'),(24,5,'stock_in',0.000,25.000,25.000,'manual','',1,'Stock in: Rice - 25 kg. Note: Initial production stock.','2026-05-19 10:11:08',60.00,1500.00,0,'store_cash','production'),(25,5,'transfer_out',25.000,15.000,-10.000,'manual','',1,'Production transfer to Manukan Branch.','2026-05-19 10:16:15',60.00,600.00,0,'store_cash','production'),(26,5,'transfer_in',0.000,10.000,10.000,'manual','',1,'Approved transfer from Production. REQ-2','2026-05-19 10:16:15',60.00,600.00,0,'store_cash','manukan_branch'),(27,2,'recipe_usage',50.000,49.000,-1.000,'walk_in','POS-30',1,'Ingredient stock deducted from order.','2026-05-19 15:30:24',100.00,100.00,0,'store_cash','manukan_branch'),(28,5,'transfer_out',15.000,0.000,-15.000,'manual','',1,'test','2026-05-19 15:46:49',60.00,900.00,0,'store_cash','production'),(29,5,'transfer_in',10.000,25.000,15.000,'manual','',1,'test','2026-05-19 15:46:49',60.00,900.00,0,'store_cash','manukan_branch'),(30,5,'recipe_usage',25.000,23.000,-2.000,'walk_in','POS-31',1,'Ingredient stock deducted from order.','2026-05-19 15:47:34',60.00,120.00,0,'store_cash','manukan_branch');
/*!40000 ALTER TABLE `wp_banoks_inventory_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_banoks_inventory_balances`
--

DROP TABLE IF EXISTS `wp_banoks_inventory_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_banoks_inventory_balances` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `inventory_item_id` bigint NOT NULL,
  `location_key` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `current_stock` decimal(12,3) NOT NULL DEFAULT '0.000',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_location` (`inventory_item_id`,`location_key`),
  KEY `location_key` (`location_key`),
  KEY `inventory_item_id` (`inventory_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9537 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_banoks_inventory_balances`
--

LOCK TABLES `wp_banoks_inventory_balances` WRITE;
/*!40000 ALTER TABLE `wp_banoks_inventory_balances` DISABLE KEYS */;
INSERT INTO `wp_banoks_inventory_balances` VALUES (1,1,'production',0.000,'2026-05-19 09:24:25'),(2,2,'production',3.000,'2026-05-19 09:07:17'),(3,3,'production',15.000,'2026-05-19 09:27:45'),(4,4,'production',0.000,'2026-05-19 09:49:15'),(8,1,'manukan_branch',25.000,'2026-05-19 09:24:25'),(9,2,'manukan_branch',49.000,'2026-05-19 15:30:24'),(10,3,'manukan_branch',50.000,'2026-05-19 09:18:41'),(11,4,'manukan_branch',25.000,'2026-05-19 09:49:15'),(7303,5,'production',0.000,'2026-05-19 15:46:49'),(7304,5,'manukan_branch',23.000,'2026-05-19 15:47:34');
/*!40000 ALTER TABLE `wp_banoks_inventory_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_banoks_requests`
--

DROP TABLE IF EXISTS `wp_banoks_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_banoks_requests` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `request_type` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `request_status` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'pending',
  `inventory_item_id` bigint NOT NULL DEFAULT '0',
  `quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
  `unit` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `estimated_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cash_source` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'store_cash',
  `expense_date` date DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `note` text COLLATE utf8mb4_unicode_520_ci,
  `decision_note` text COLLATE utf8mb4_unicode_520_ci,
  `requested_by` bigint NOT NULL DEFAULT '0',
  `decided_by` bigint NOT NULL DEFAULT '0',
  `decided_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `branch_key` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'manukan_branch',
  PRIMARY KEY (`id`),
  KEY `request_type` (`request_type`),
  KEY `request_status` (`request_status`),
  KEY `inventory_item_id` (`inventory_item_id`),
  KEY `requested_by` (`requested_by`),
  KEY `branch_key` (`branch_key`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_banoks_requests`
--

LOCK TABLES `wp_banoks_requests` WRITE;
/*!40000 ALTER TABLE `wp_banoks_requests` DISABLE KEYS */;
INSERT INTO `wp_banoks_requests` VALUES (1,'production_transfer_request','pending',2,10.000,'pcs',0.00,'store_cash','2026-05-18','Wala nay stock Pecho','qweqwe',NULL,2,0,NULL,'2026-05-18 15:50:39','2026-05-18 15:50:39','manukan_branch'),(2,'production_transfer_request','approved',5,10.000,'kg',0.00,'cash_on_hand','2026-05-19','Wala nay rice','test','',1,1,'2026-05-19 10:16:15','2026-05-19 10:16:00','2026-05-19 10:16:15','manukan_branch');
/*!40000 ALTER TABLE `wp_banoks_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_banoks_request_logs`
--

DROP TABLE IF EXISTS `wp_banoks_request_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_banoks_request_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `request_id` bigint NOT NULL,
  `old_status` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `new_status` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `updated_by` bigint NOT NULL DEFAULT '0',
  `note` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  KEY `new_status` (`new_status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_banoks_request_logs`
--

LOCK TABLES `wp_banoks_request_logs` WRITE;
/*!40000 ALTER TABLE `wp_banoks_request_logs` DISABLE KEYS */;
INSERT INTO `wp_banoks_request_logs` VALUES (1,1,'','pending',2,'Request submitted.','2026-05-18 15:50:39'),(2,2,'','pending',1,'Request submitted.','2026-05-19 10:16:00'),(3,2,'pending','approved',1,'','2026-05-19 10:16:15');
/*!40000 ALTER TABLE `wp_banoks_request_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_banoks_stock_logs`
--

DROP TABLE IF EXISTS `wp_banoks_stock_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_banoks_stock_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `product_id` bigint NOT NULL,
  `old_stock` int NOT NULL DEFAULT '0',
  `new_stock` int NOT NULL DEFAULT '0',
  `change_amount` int NOT NULL DEFAULT '0',
  `source` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'manual',
  `source_id` varchar(80) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `updated_by` bigint NOT NULL DEFAULT '0',
  `note` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `source` (`source`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_banoks_stock_logs`
--

LOCK TABLES `wp_banoks_stock_logs` WRITE;
/*!40000 ALTER TABLE `wp_banoks_stock_logs` DISABLE KEYS */;
INSERT INTO `wp_banoks_stock_logs` VALUES (1,6,0,25,25,'manual','',1,'Stock updated from product form.','2026-05-15 21:55:29'),(2,6,25,24,-1,'walk_in','POS-21',1,'Stock deducted from order.','2026-05-15 22:07:36'),(3,6,24,23,-1,'online','ONL-20260515-0038',1,'Stock deducted from order.','2026-05-15 22:15:07'),(4,2,0,25,25,'manual','',1,'Stock updated from product form.','2026-05-15 22:51:48'),(5,2,25,24,-1,'walk_in','POS-22',1,'Stock deducted from order.','2026-05-15 22:52:09'),(6,2,24,23,-1,'online','ONL-20260515-0039',1,'Stock deducted from order.','2026-05-15 22:58:59'),(7,2,23,22,-1,'walk_in','POS-23',1,'Stock deducted from order.','2026-05-16 15:53:30'),(8,2,22,21,-1,'walk_in','POS-24',1,'Stock deducted from order.','2026-05-16 15:57:45'),(9,2,21,19,-2,'online_pickup','ONL-20260516-0001',1,'Stock deducted from order.','2026-05-16 16:00:46'),(10,2,19,18,-1,'walk_in','POS-25',1,'Stock deducted from order.','2026-05-16 16:13:03'),(11,6,23,21,-2,'online_pickup','ONL-20260516-0002',1,'Stock deducted from order.','2026-05-16 18:09:27');
/*!40000 ALTER TABLE `wp_banoks_stock_logs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-19 19:24:29
