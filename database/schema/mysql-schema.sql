/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `actor_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `subject_label` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `properties` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tbl_activity_logs_actor_type_actor_id_index` (`actor_type`,`actor_id`),
  KEY `tbl_activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `tbl_activity_logs_action_created_at_index` (`action`,`created_at`),
  KEY `tbl_activity_logs_category_created_at_index` (`category`,`created_at`),
  KEY `tbl_activity_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_ad_daily_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_ad_daily_stats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ad_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ad_id` bigint unsigned NOT NULL,
  `platform` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stat_date` date NOT NULL,
  `views` int unsigned NOT NULL DEFAULT '0',
  `clicks` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ad_daily_stats_unique` (`ad_type`,`ad_id`,`platform`,`stat_date`),
  KEY `tbl_ad_daily_stats_stat_date_index` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_ad_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_ad_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `client_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'app',
  `app_slider_id` bigint unsigned DEFAULT NULL,
  `company_carousel_id` bigint unsigned DEFAULT NULL,
  `link_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `artwork_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_note` text COLLATE utf8mb4_unicode_ci,
  `amount` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SDG',
  `starts_on` date DEFAULT NULL,
  `ends_on` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'requested',
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_reference` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_ad_sales_company_id_index` (`company_id`),
  KEY `tbl_ad_sales_app_slider_id_index` (`app_slider_id`),
  KEY `tbl_ad_sales_company_carousel_id_index` (`company_carousel_id`),
  KEY `tbl_ad_sales_ends_on_index` (`ends_on`),
  KEY `tbl_ad_sales_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_admins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_admins_username_unique` (`username`),
  UNIQUE KEY `tbl_admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_api_endpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_api_endpoints` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `is_online` tinyint(1) NOT NULL DEFAULT '1',
  `offline_message_en` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offline_message_ar` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_api_endpoints_base_url_unique` (`base_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_app_sliders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_app_sliders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_width` smallint unsigned DEFAULT NULL,
  `image_height` smallint unsigned DEFAULT NULL,
  `link_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'app',
  `placement` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'home_top',
  `is_sponsored` tinyint(1) NOT NULL DEFAULT '0',
  `interval_seconds` smallint unsigned NOT NULL DEFAULT '5',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_app_sliders_display_idx` (`is_active`,`deleted_at`,`sort_order`),
  KEY `tbl_app_sliders_company_id_index` (`company_id`),
  KEY `tbl_app_sliders_placement_index` (`placement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_app_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_app_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `platform` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `minimum_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `force_update` tinyint(1) NOT NULL DEFAULT '0',
  `description_en` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description_ar` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_app_versions_created_by_foreign` (`created_by`),
  KEY `tbl_app_versions_platform_deleted_at_index` (`platform`,`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_appliance_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_appliance_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ar_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_appliances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_appliances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ar_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `watts` int unsigned NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_appliances_group_id_foreign` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_archive` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned DEFAULT NULL,
  `type` enum('customer','product','prodcut','product_spec','offer','requestd_quote','requested_quote','notification','customer_notification','company','project','project_milestone','project_update','rating','rating_answer','supplier','appliance','appliance_group','company_carousel','app_version','rating_question','customer_device_token','api_endpoint','sent_email','other','app_slider','legal_section','ad_sale') COLLATE utf8mb4_unicode_ci NOT NULL,
  `item_id` bigint unsigned DEFAULT NULL,
  `data` longtext COLLATE utf8mb4_unicode_ci,
  `source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'website',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_archive_customer_id_type_index` (`customer_id`,`type`),
  KEY `tbl_archive_type_item_id_index` (`type`,`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_company_carousels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_company_carousels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `platform` enum('app','website','both') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'app',
  `is_sponsored` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `interval_seconds` smallint unsigned NOT NULL DEFAULT '5',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_company_carousels_company_platform_unique` (`company_id`,`platform`),
  KEY `tbl_company_carousel_display_idx` (`platform`,`is_active`,`sort_order`),
  KEY `tbl_company_carousels_company_id_index` (`company_id`),
  KEY `tbl_company_carousels_platform_index` (`platform`),
  KEY `tbl_company_carousels_sort_order_index` (`sort_order`),
  KEY `tbl_company_carousels_is_active_index` (`is_active`),
  KEY `tbl_company_carousels_starts_at_index` (`starts_at`),
  KEY `tbl_company_carousels_ends_at_index` (`ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_company_quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_company_quotations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quote_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `company_id` bigint unsigned NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SDG',
  `commission_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'company',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `commission_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `message` text COLLATE utf8mb4_unicode_ci,
  `ar_message` text COLLATE utf8mb4_unicode_ci,
  `status` enum('submitted','selected','not_selected','withdrawn') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_company_quotation_request` (`quote_id`,`company_id`),
  KEY `tbl_company_quotations_quote_id_status_index` (`quote_id`,`status`),
  KEY `tbl_company_quotations_company_id_index` (`company_id`),
  KEY `tbl_company_quotations_customer_id_index` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_customer_complaints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_customer_complaints` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `category` enum('product_issue','service_issue','pricing_issue','delivery_issue','customer_service','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','in_progress','resolved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `photos` json DEFAULT NULL,
  `admin_reply` text COLLATE utf8mb4_unicode_ci,
  `customer_replied_at` timestamp NULL DEFAULT NULL,
  `company_notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_customer_complaints_customer_id_created_at_index` (`customer_id`,`created_at`),
  KEY `tbl_customer_complaints_customer_id_index` (`customer_id`),
  KEY `tbl_customer_complaints_company_id_index` (`company_id`),
  KEY `tbl_customer_complaints_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_customer_device_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_customer_device_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `fcm_token` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'android',
  `device_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lang` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_customer_device_tokens_token_hash_unique` (`token_hash`),
  KEY `tbl_customer_device_tokens_customer_index` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_customer_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_customer_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_customer_notifications_unique_recipient` (`notification_id`,`customer_id`),
  KEY `tbl_customer_notifications_customer_id_read_at_index` (`customer_id`,`read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_customer_terms_acceptances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_customer_terms_acceptances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `terms_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'login',
  `platform` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accepted_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_customer_terms_acceptances_customer_id_terms_version_index` (`customer_id`,`terms_version`),
  KEY `tbl_customer_terms_acceptances_accepted_at_index` (`accepted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `preferred_language` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `gender` enum('male','female') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','pending','suspended','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `app_status` enum('active','stopped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `app_status_message_en` text COLLATE utf8mb4_unicode_ci,
  `app_status_message_ar` text COLLATE utf8mb4_unicode_ci,
  `app_status_changed_at` timestamp NULL DEFAULT NULL,
  `max_open_quotes` tinyint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `sponsored_push_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `pending_load` text COLLATE utf8mb4_unicode_ci,
  `terms_accepted_version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_customers_username_unique` (`username`),
  UNIQUE KEY `tbl_customers_phone_unique` (`phone`),
  UNIQUE KEY `tbl_customers_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_home_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_home_sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `show_companies` tinyint(1) NOT NULL DEFAULT '1',
  `title_en` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title_ar` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_home_sections_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_quotation_id` bigint unsigned NOT NULL,
  `quote_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `invoice_number` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_date` date NOT NULL,
  `payment_terms` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_instructions` text COLLATE utf8mb4_unicode_ci,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SDG',
  `company_details` json NOT NULL,
  `customer_details` json NOT NULL,
  `items` json NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(15,2) NOT NULL DEFAULT '0.00',
  `shipping` decimal(15,2) NOT NULL DEFAULT '0.00',
  `balance_due` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoice_company_number` (`company_id`,`invoice_number`),
  UNIQUE KEY `tbl_invoices_company_quotation_id_unique` (`company_quotation_id`),
  KEY `tbl_invoices_quote_id_foreign` (`quote_id`),
  KEY `tbl_invoices_customer_id_index` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_legal_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_legal_sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'section',
  `eyebrow_en` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eyebrow_ar` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title_en` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ar` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_en` longtext COLLATE utf8mb4_unicode_ci,
  `body_ar` longtext COLLATE utf8mb4_unicode_ci,
  `last_updated` date DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_legal_sections_page_type_sort_order_index` (`page`,`type`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_notification_schedule_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_notification_schedule_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `schedule_id` bigint unsigned NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `title_en` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ar` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message_en` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_ar` text COLLATE utf8mb4_unicode_ci,
  `sent_count` int unsigned NOT NULL DEFAULT '0',
  `last_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_notification_schedule_messages_schedule_id_sort_order_index` (`schedule_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_notification_schedule_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_notification_schedule_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `schedule_id` bigint unsigned NOT NULL,
  `schedule_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_id` bigint unsigned DEFAULT NULL,
  `message_order` int DEFAULT NULL,
  `message_title` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notification_id` bigint unsigned DEFAULT NULL,
  `trigger` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto',
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipients` int unsigned NOT NULL DEFAULT '0',
  `devices` int unsigned NOT NULL DEFAULT '0',
  `delivered` int unsigned NOT NULL DEFAULT '0',
  `failed_devices` int unsigned NOT NULL DEFAULT '0',
  `error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_ms` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_notification_schedule_runs_schedule_id_index` (`schedule_id`),
  KEY `tbl_notification_schedule_runs_status_created_at_index` (`status`,`created_at`),
  KEY `tbl_notification_schedule_runs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_notification_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_notification_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `days` json NOT NULL,
  `every_days` smallint unsigned NOT NULL DEFAULT '1',
  `send_time` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '10:00',
  `timezone` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Africa/Khartoum',
  `starts_on` date DEFAULT NULL,
  `ends_on` date DEFAULT NULL,
  `notification_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `audience_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `audience_city` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `after_last` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stop',
  `last_message_id` bigint unsigned DEFAULT NULL,
  `last_message_order` int DEFAULT NULL,
  `last_sent_on` date DEFAULT NULL,
  `last_sent_at` timestamp NULL DEFAULT NULL,
  `last_run_on` date DEFAULT NULL,
  `last_error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sent_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_notification_schedules_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sender_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_id` bigint unsigned DEFAULT NULL,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `title_en` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ar` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message_en` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_ar` text COLLATE utf8mb4_unicode_ci,
  `format` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_width` smallint unsigned DEFAULT NULL,
  `image_height` smallint unsigned DEFAULT NULL,
  `link_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_label_en` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_label_ar` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `quote_id` bigint unsigned DEFAULT NULL,
  `offer_title_en` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offer_title_ar` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offer_price` decimal(14,2) DEFAULT NULL,
  `offer_old_price` decimal(14,2) DEFAULT NULL,
  `offer_currency` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offer_expires_at` timestamp NULL DEFAULT NULL,
  `audience_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'selected',
  `audience_city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_sponsored` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `package_offer_id` bigint unsigned DEFAULT NULL,
  `notification_schedule_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_notifications_sender_type_sender_id_index` (`sender_type`,`sender_id`),
  KEY `tbl_notifications_type_status_published_at_index` (`type`,`status`,`published_at`),
  KEY `tbl_notifications_company_id_index` (`company_id`),
  KEY `tbl_notifications_quote_id_index` (`quote_id`),
  KEY `tbl_notifications_package_offer_id_index` (`package_offer_id`),
  KEY `tbl_notifications_notification_schedule_id_index` (`notification_schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_otps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_hash` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purpose` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'password_reset',
  `channel` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_otps_type_email_purpose_index` (`type`,`email`,`purpose`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_package_offer_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_package_offer_responses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `package_offer_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `notification_id` bigint unsigned DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quote_id` bigint unsigned DEFAULT NULL,
  `responded_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_package_offer_responses_package_offer_id_customer_id_unique` (`package_offer_id`,`customer_id`),
  KEY `tbl_package_offer_responses_customer_id_index` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_package_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_package_offers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `title_en` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ar` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description_en` text COLLATE utf8mb4_unicode_ci,
  `description_ar` text COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_width` smallint unsigned DEFAULT NULL,
  `image_height` smallint unsigned DEFAULT NULL,
  `max_load_kw` decimal(8,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SDG',
  `items` json NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `price` decimal(15,2) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'company',
  `created_by_id` bigint unsigned DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_package_offers_company_id_status_index` (`company_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_product_specs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_product_specs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `spec_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spec_value` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_product_specs_product_id_spec_name_unique` (`product_id`,`spec_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned DEFAULT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('inverter','solar_panel','battery','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_products_supplier_id_type_index` (`supplier_id`,`type`),
  KEY `tbl_products_name_index` (`name`),
  KEY `tbl_products_model_index` (`model`),
  KEY `tbl_products_type_index` (`type`),
  KEY `tbl_products_is_active_index` (`is_active`),
  KEY `tbl_products_company_id_is_active_index` (`company_id`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_project_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_project_milestones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_project_milestones_project_id_status_index` (`project_id`,`status`),
  KEY `tbl_project_milestones_project_id_sort_order_index` (`project_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_project_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_project_updates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint unsigned NOT NULL,
  `updated_by_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by_id` bigint unsigned DEFAULT NULL,
  `old_status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `progress_percent` tinyint unsigned DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `visible_to_customer` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_project_updates_project_id_created_at_index` (`project_id`,`created_at`),
  KEY `tbl_project_updates_updated_by_type_updated_by_id_index` (`updated_by_type`,`updated_by_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `quote_id` bigint unsigned NOT NULL,
  `company_quotation_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'accepted',
  `progress_percent` tinyint unsigned NOT NULL DEFAULT '0',
  `accepted_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_projects_company_quotation_id_unique` (`company_quotation_id`),
  KEY `tbl_projects_quote_id_foreign` (`quote_id`),
  KEY `tbl_projects_company_id_status_index` (`company_id`,`status`),
  KEY `tbl_projects_customer_id_status_index` (`customer_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_quote_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_quote_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `quote_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estimated_load` text COLLATE utf8mb4_unicode_ci,
  `peak_load` text COLLATE utf8mb4_unicode_ci,
  `daily_load` text COLLATE utf8mb4_unicode_ci,
  `appliances_ids` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','accepted','rejected','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `package_offer_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_quotes_customer_id_status_index` (`customer_id`,`status`),
  KEY `tbl_quotes_company_id_status_index` (`company_id`,`status`),
  KEY `tbl_quotes_created_at_index` (`created_at`),
  KEY `tbl_quote_requests_package_offer_id_index` (`package_offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_quotes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `service_needed` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estimated_load` text COLLATE utf8mb4_unicode_ci,
  `message` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','accepted','rejected','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_quotes_customer_id_status_index` (`customer_id`,`status`),
  KEY `tbl_quotes_company_id_status_index` (`company_id`,`status`),
  KEY `tbl_quotes_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_rating_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_rating_answers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rating_id` bigint unsigned NOT NULL,
  `question_id` bigint unsigned NOT NULL,
  `value` tinyint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_rating_answers_rating_id_question_id_unique` (`rating_id`,`question_id`),
  KEY `tbl_rating_answers_question_id_index` (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_rating_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_rating_questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title_en` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_ar` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `question_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_rating_questions_is_active_sort_order_index` (`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_rating_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_rating_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `value` tinyint unsigned NOT NULL,
  `label_en` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label_ar` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_rating_values_value_unique` (`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_ratings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `quote_id` bigint unsigned NOT NULL,
  `offer_id` bigint unsigned DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `stars` tinyint unsigned NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_ratings_customer_id_quote_id_unique` (`customer_id`,`quote_id`),
  KEY `tbl_ratings_company_id_is_visible_index` (`company_id`,`is_visible`),
  KEY `tbl_ratings_customer_id_index` (`customer_id`),
  KEY `tbl_ratings_company_id_index` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_sent_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_sent_emails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `sent_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_sent_emails_recipient_email_index` (`recipient_email`),
  KEY `tbl_sent_emails_sent_at_index` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_solar_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_solar_companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `services` text COLLATE utf8mb4_unicode_ci,
  `ar_services` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ar_description` longtext COLLATE utf8mb4_unicode_ci,
  `year_of_exp` int DEFAULT NULL,
  `number_of_projects` int DEFAULT NULL,
  `logo_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_media` text COLLATE utf8mb4_unicode_ci,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `on_app` tinyint(1) NOT NULL DEFAULT '1',
  `can_notify_all_customers` tinyint(1) NOT NULL DEFAULT '0',
  `all_customers_monthly_limit` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_solar_companies_slug_unique` (`slug`),
  UNIQUE KEY `tbl_solar_companies_username_unique` (`username`),
  UNIQUE KEY `tbl_solar_companies_email_unique` (`email`),
  KEY `tbl_solar_companies_status_index` (`status`),
  KEY `tbl_solar_companies_is_active_is_verified_index` (`is_active`,`is_verified`),
  KEY `tbl_solar_companies_on_app_index` (`on_app`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_suppliers_name_unique` (`name`),
  KEY `tbl_suppliers_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tbl_visitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_visitors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_visitors_session_id_unique` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_10_11_125056_create_tbl_admins_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_10_11_125223_create_tbl_solar_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_10_11_125353_create_tbl_quote_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_10_12_081822_add_ar_description_to_tbl_solar_companies_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_10_12_084044_add_services_fields_to_tbl_solar_companies_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_10_12_123438_add_license_number_and_social_media_to_tbl_solar_companies_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_10_13_074709_create_tbl_customers_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_10_15_112833_create_personal_access_tokens_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_10_16_082807_add_year_of_exp_number_of_projects_to_tbl_solar_companies_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_10_16_092700_create_appliance_groups_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_10_16_092705_create_appliances_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_10_16_092802_create_tbl_appliance_groups_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_10_16_092833_create_tbl_appliances_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_10_16_000001_create_tbl_suppliers_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_10_16_000002_create_tbl_products_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_10_16_000003_create_tbl_product_specs_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_10_16_161219_add_company_id_to_tbl_products_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_10_17_000001_create_tbl_company_carousels_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_10_17_104231_add_unique_company_id_to_tbl_company_carousels_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_10_17_110129_replace_company_carousel_unique_index',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_10_18_093122_create_quotes_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_10_18_101754_add_load_fields_to_tbl_quotes_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_10_18_141403_create_company_quotations_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_10_18_170337_add_customer_id_to_tbl_company_quotations_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_10_18_173724_rename_tbl_quotes_to_tbl_quote_requests',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_10_19_073140_add_ar_message_to_tbl_company_quotations_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_10_19_205351_make_full_name_nullable_in_customers_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_10_20_085243_create_tbl_projects_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_10_20_085329_create_tbl_project_milestones_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_10_20_085335_create_tbl_project_updates_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_10_21_200047_create_visitors_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_10_21_202026_create_notifications_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_10_21_202031_create_customer_notifications_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_10_21_205020_create_customer_device_tokens_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_10_22_120000_create_tbl_app_versions_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_10_22_130000_add_soft_deletes_to_tbl_app_versions_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_10_22_140000_add_descriptions_to_tbl_app_versions_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_10_22_150000_add_email_to_tbl_admins_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_10_22_160000_create_tbl_otps_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_10_22_170000_add_channel_to_tbl_otps_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_10_22_180000_change_status_to_enum_on_tbl_solar_companies_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_10_22_190000_add_last_login_at_to_tbl_customers_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_10_23_100000_rename_service_needed_to_quote_name_on_tbl_quote_requests_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_10_23_100100_create_tbl_rating_values_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_10_23_100200_create_tbl_rating_questions_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_10_23_100300_create_tbl_ratings_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_10_23_100400_create_tbl_settings_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_10_23_110000_create_tbl_activity_logs_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_10_23_120000_add_appliances_ids_to_tbl_quote_requests_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_10_24_090000_add_app_status_to_tbl_customers_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_10_24_100000_change_value_to_text_on_tbl_settings_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_10_25_100000_add_max_open_quotes_to_tbl_customers_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_10_25_110000_create_tbl_api_endpoints_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_10_27_120000_create_tbl_sent_emails_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_10_28_120000_add_country_to_tbl_visitors_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_10_28_140000_create_tbl_archive_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_10_29_000001_create_tbl_customer_complaints_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_10_29_100000_create_tbl_app_sliders_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_10_29_100100_add_app_slider_to_tbl_archive_type',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_10_29_120000_add_soft_deletes_to_tbl_ratings_and_answers',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_10_30_090000_store_tbl_archive_data_as_unicode_text',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_10_30_110000_add_customer_registration_source_and_language',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_10_30_100000_add_campaign_fields_to_tbl_notifications_table',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_10_31_100000_create_tbl_invoices_table',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_10_31_110000_add_can_notify_all_customers_to_tbl_solar_companies',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_10_31_120000_add_quote_id_to_tbl_notifications_table',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_10_31_130000_create_tbl_legal_sections_table',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_10_31_130100_add_legal_section_to_tbl_archive_type',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_11_01_100000_create_tbl_ad_sales_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_11_01_100100_create_tbl_ad_daily_stats_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_11_01_100200_add_advertising_fields',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_11_01_100300_add_ad_sale_to_tbl_archive_type',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_11_01_100400_add_sponsored_wording_to_legal_sections',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_11_03_100000_create_tbl_home_sections_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_11_03_100100_add_placement_to_tbl_app_sliders',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_11_03_100200_add_on_app_to_tbl_solar_companies',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_11_05_100000_add_commission_to_companies_and_quotations',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_11_05_110000_add_commission_type_to_tbl_company_quotations',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_11_07_100000_add_pending_load_to_tbl_customers',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_11_08_100000_create_tbl_customer_terms_acceptances_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_11_08_120000_create_package_offers_tables',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_11_08_140000_add_image_and_creator_to_tbl_package_offers',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_11_09_100000_create_notification_schedules_tables',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_11_09_120000_create_notification_schedule_runs_table',64);
