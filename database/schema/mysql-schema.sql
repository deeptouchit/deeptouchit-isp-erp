/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_prefix` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` json DEFAULT NULL,
  `ip_allowlist` json DEFAULT NULL,
  `rate_limit_per_minute` int NOT NULL DEFAULT '60',
  `status` enum('active','revoked','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `last_used_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_keys_user_id_foreign` (`user_id`),
  KEY `idx_api_keys_status` (`status`,`created_at`),
  KEY `idx_api_keys_prefix` (`key_prefix`),
  CONSTRAINT `api_keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `api_key_id` bigint unsigned DEFAULT NULL,
  `api_user_id` bigint unsigned DEFAULT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpoint` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_code` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_ms` int NOT NULL DEFAULT '0',
  `request_headers` json DEFAULT NULL,
  `request_payload` json DEFAULT NULL,
  `response_body` text COLLATE utf8mb4_unicode_ci,
  `error_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_logs_api_key_id_foreign` (`api_key_id`),
  KEY `api_logs_api_user_id_foreign` (`api_user_id`),
  KEY `idx_api_logs_status_time` (`status_code`,`created_at`),
  KEY `idx_api_logs_ip_time` (`ip_address`,`created_at`),
  CONSTRAINT `api_logs_api_key_id_foreign` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE SET NULL,
  CONSTRAINT `api_logs_api_user_id_foreign` FOREIGN KEY (`api_user_id`) REFERENCES `api_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_rate_limits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_type` enum('global_ip','authenticated_keys','service_accounts','endpoint_specific','whitelisted_cidr') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'global_ip',
  `endpoint_pattern` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '*',
  `requests_per_minute` int NOT NULL DEFAULT '60',
  `burst_capacity` int NOT NULL DEFAULT '10',
  `action_on_breach` enum('http_429','delay_throttle','temp_ip_ban','alert_admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'http_429',
  `ban_duration_minutes` int NOT NULL DEFAULT '15',
  `status` enum('active','disabled','dry_run') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `total_breaches` int unsigned NOT NULL DEFAULT '0',
  `last_breached_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rate_limits_status_scope` (`status`,`scope_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permissions` json DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_tokens_token_unique` (`token`),
  KEY `api_tokens_user_id_foreign` (`user_id`),
  CONSTRAINT `api_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('service_account','admin_bot','reseller_api','read_only','custom') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'service_account',
  `scopes` json DEFAULT NULL,
  `ip_restrictions` json DEFAULT NULL,
  `rate_limit_multiplier` int NOT NULL DEFAULT '1',
  `status` enum('active','suspended','revoked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `last_activity_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_calls` bigint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_users_email_unique` (`email`),
  KEY `api_users_user_id_foreign` (`user_id`),
  KEY `idx_api_users_status_role` (`status`,`role`),
  CONSTRAINT `api_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `automation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `automation_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `task_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `triggered_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cron',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `duration_ms` int unsigned NOT NULL DEFAULT '0',
  `metrics` json DEFAULT NULL,
  `output_summary` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cron_job_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cron_job_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cron_job_id` bigint unsigned NOT NULL,
  `executed_at` timestamp NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration_ms` int NOT NULL DEFAULT '0',
  `exit_code` int NOT NULL DEFAULT '0',
  `output` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cron_job_logs_cron_job_id_foreign` (`cron_job_id`),
  CONSTRAINT `cron_job_logs_cron_job_id_foreign` FOREIGN KEY (`cron_job_id`) REFERENCES `cron_jobs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cron_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cron_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `command` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cron_expression` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '* * * * *',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `output_handling` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'log_file',
  `log_file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `run_as_user` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'www-data',
  `last_run_at` timestamp NULL DEFAULT NULL,
  `last_run_status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_run_duration_ms` int DEFAULT NULL,
  `last_output_preview` text COLLATE utf8mb4_unicode_ci,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cron_jobs_user_id_foreign` (`user_id`),
  CONSTRAINT `cron_jobs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `integrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `integrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('dns_cdn','billing','storage','git_dev','notifications','migration') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dns_cdn',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'globe',
  `credentials` json DEFAULT NULL,
  `settings` json DEFAULT NULL,
  `status` enum('connected','disconnected','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disconnected',
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `last_health_check` timestamp NULL DEFAULT NULL,
  `health_status` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_events` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `integrations_provider_unique` (`provider`),
  KEY `idx_integrations_cat_status` (`category`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
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
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nasname` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shortname` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `ports` int DEFAULT NULL,
  `secret` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'secret',
  `server` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `community` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nas_nasname_index` (`nasname`),
  KEY `nas_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `network_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `network_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `device_type` enum('router','olt','onu','radius','wireguard','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'router',
  `device_id` bigint unsigned DEFAULT NULL,
  `device_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('success','failed','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `command_executed` text COLLATE utf8mb4_unicode_ci,
  `response_summary` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `network_audit_logs_user_id_foreign` (`user_id`),
  KEY `network_audit_logs_tenant_id_device_type_status_index` (`tenant_id`,`device_type`,`status`),
  KEY `network_audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `network_audit_logs_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `network_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_gateways` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('mfs','card','bank','international') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mfs',
  `credentials` json DEFAULT NULL,
  `mode` enum('sandbox','live') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sandbox',
  `fee_type` enum('percentage','fixed','none') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `fee_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `min_amount` decimal(10,2) NOT NULL DEFAULT '10.00',
  `max_amount` decimal(10,2) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_gateways_slug_unique` (`slug`),
  KEY `idx_gateways_slug_active` (`slug`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `saas_invoice_id` bigint unsigned DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'online',
  `gateway` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BDT',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `gateway_response` json DEFAULT NULL,
  `initiated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_transactions_transaction_reference_unique` (`transaction_reference`),
  KEY `payment_transactions_tenant_id_foreign` (`tenant_id`),
  KEY `payment_transactions_saas_invoice_id_foreign` (`saas_invoice_id`),
  CONSTRAINT `payment_transactions_saas_invoice_id_foreign` FOREIGN KEY (`saas_invoice_id`) REFERENCES `saas_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_transactions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'General',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `radacct`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `radacct` (
  `radacctid` bigint unsigned NOT NULL AUTO_INCREMENT,
  `acctsessionid` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `acctuniqueid` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groupname` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `realm` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `nasipaddress` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `nasportid` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nasporttype` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acctstarttime` datetime DEFAULT NULL,
  `acctupdatetime` datetime DEFAULT NULL,
  `acctstoptime` datetime DEFAULT NULL,
  `acctinterval` int DEFAULT NULL,
  `acctsessiontime` int unsigned DEFAULT NULL,
  `acctauthentic` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `connectinfo_start` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `connectinfo_stop` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acctinputoctets` bigint DEFAULT NULL,
  `acctoutputoctets` bigint DEFAULT NULL,
  `calledstationid` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `callingstationid` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `acctterminatecause` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `servicetype` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `framedprotocol` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `framedipaddress` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `framedipv6address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `framedipv6prefix` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `framedinterfaceid` varchar(44) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `delegatedipv6prefix` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tenant_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`radacctid`),
  UNIQUE KEY `radacct_acctuniqueid_unique` (`acctuniqueid`),
  KEY `radacct_acctsessionid_index` (`acctsessionid`),
  KEY `radacct_username_index` (`username`),
  KEY `radacct_nasipaddress_index` (`nasipaddress`),
  KEY `radacct_acctstarttime_index` (`acctstarttime`),
  KEY `radacct_acctstoptime_index` (`acctstoptime`),
  KEY `radacct_callingstationid_index` (`callingstationid`),
  KEY `radacct_framedipaddress_index` (`framedipaddress`),
  KEY `radacct_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `radcheck`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `radcheck` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attribute` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Cleartext-Password',
  `op` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ':=',
  `value` varchar(253) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tenant_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `radcheck_username_index` (`username`),
  KEY `radcheck_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `radgroupcheck`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `radgroupcheck` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `groupname` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attribute` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `op` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ':=',
  `value` varchar(253) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tenant_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `radgroupcheck_groupname_index` (`groupname`),
  KEY `radgroupcheck_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `radgroupreply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `radgroupreply` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `groupname` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attribute` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `op` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '=',
  `value` varchar(253) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tenant_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `radgroupreply_groupname_index` (`groupname`),
  KEY `radgroupreply_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `radpostauth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `radpostauth` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pass` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `reply` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `authdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tenant_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `radpostauth_username_index` (`username`),
  KEY `radpostauth_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `radreply`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `radreply` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `attribute` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `op` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '=',
  `value` varchar(253) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `tenant_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `radreply_username_index` (`username`),
  KEY `radreply_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `radusergroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `radusergroup` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `groupname` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `priority` int NOT NULL DEFAULT '1',
  `tenant_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `radusergroup_username_index` (`username`),
  KEY `radusergroup_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `color` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'purple',
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saas_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `tenant_subscription_id` bigint unsigned DEFAULT NULL,
  `saas_plan_id` bigint unsigned DEFAULT NULL,
  `invoice_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `credit_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `due_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `status` enum('paid','unpaid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trx_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saas_invoices_invoice_no_unique` (`invoice_no`),
  KEY `saas_invoices_tenant_id_foreign` (`tenant_id`),
  KEY `saas_invoices_saas_plan_id_foreign` (`saas_plan_id`),
  KEY `saas_invoices_tenant_subscription_id_foreign` (`tenant_subscription_id`),
  CONSTRAINT `saas_invoices_saas_plan_id_foreign` FOREIGN KEY (`saas_plan_id`) REFERENCES `saas_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `saas_invoices_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saas_invoices_tenant_subscription_id_foreign` FOREIGN KEY (`tenant_subscription_id`) REFERENCES `tenant_subscriptions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saas_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monthly_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `otc_charge` decimal(10,2) NOT NULL DEFAULT '0.00',
  `premium_monthly_price` decimal(10,2) DEFAULT NULL,
  `yearly_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `customer_limit` int NOT NULL DEFAULT '500',
  `mikrotik_limit` int NOT NULL DEFAULT '2',
  `olt_limit` int NOT NULL DEFAULT '1',
  `allow_radius` tinyint(1) NOT NULL DEFAULT '0',
  `allow_wireguard` tinyint(1) NOT NULL DEFAULT '0',
  `allow_snmp_monitoring` tinyint(1) NOT NULL DEFAULT '1',
  `reseller_limit` int NOT NULL DEFAULT '5',
  `features` json DEFAULT NULL,
  `trial_days` int NOT NULL DEFAULT '14',
  `is_popular` tinyint(1) NOT NULL DEFAULT '0',
  `badge_text` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saas_plans_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `security_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('info','warning','critical','alert') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `actor_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `actor_id` bigint unsigned DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_url` text COLLATE utf8mb4_unicode_ci,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` json DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'logged',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `security_audit_logs_event_type_severity_index` (`event_type`,`severity`),
  KEY `security_audit_logs_ip_address_index` (`ip_address`),
  KEY `security_audit_logs_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci,
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_tenant_id_key_unique` (`tenant_id`,`key`),
  KEY `settings_key_index` (`key`),
  KEY `settings_group_index` (`group`),
  CONSTRAINT `settings_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sms_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `gateway_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Greenweb',
  `recipient_phone` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sms_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `message_body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `character_count` int unsigned NOT NULL DEFAULT '0',
  `parts_count` smallint unsigned NOT NULL DEFAULT '1',
  `cost_per_part` decimal(8,4) NOT NULL DEFAULT '0.3500',
  `total_cost` decimal(10,4) NOT NULL DEFAULT '0.3500',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'delivered',
  `api_response` text COLLATE utf8mb4_unicode_ci,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sms_logs_tenant_id_sms_type_created_at_index` (`tenant_id`,`sms_type`,`created_at`),
  CONSTRAINT `sms_logs_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `saas_invoice_id` bigint unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscription_invoice_items_saas_invoice_id_foreign` (`saas_invoice_id`),
  CONSTRAINT `subscription_invoice_items_saas_invoice_id_foreign` FOREIGN KEY (`saas_invoice_id`) REFERENCES `saas_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tenant_id` bigint unsigned NOT NULL,
  `reseller_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` enum('open','in_progress','answered','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_reply_by` enum('tenant','owner') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tenant',
  `last_reply_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `support_tickets_ticket_number_unique` (`ticket_number`),
  KEY `support_tickets_user_id_foreign` (`user_id`),
  KEY `support_tickets_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `support_tickets_status_priority_index` (`status`,`priority`),
  KEY `support_tickets_last_reply_at_index` (`last_reply_at`),
  KEY `support_tickets_reseller_id_index` (`reseller_id`),
  CONSTRAINT `support_tickets_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `support_tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `actor_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'owner',
  `actor_id` bigint unsigned DEFAULT NULL,
  `actor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_activity_logs_tenant_id_event_type_index` (`tenant_id`,`event_type`),
  CONSTRAINT `tenant_activity_logs_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_automation_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_automation_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `auto_billing_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `billing_generation_day` int NOT NULL DEFAULT '1',
  `billing_generation_time` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '00:00',
  `auto_send_bill_sms` tinyint(1) NOT NULL DEFAULT '1',
  `auto_cut_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `grace_period_days` int NOT NULL DEFAULT '7',
  `min_due_threshold` decimal(10,2) NOT NULL DEFAULT '50.00',
  `auto_cut_action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disable_secret',
  `auto_cut_time` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '02:00',
  `auto_send_cut_sms` tinyint(1) NOT NULL DEFAULT '1',
  `auto_reconnect_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `auto_send_restore_sms` tinyint(1) NOT NULL DEFAULT '1',
  `expiry_reminders_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `reminder_1_days_before` int NOT NULL DEFAULT '3',
  `reminder_2_days_before` int NOT NULL DEFAULT '1',
  `reminder_dispatch_time` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '10:00',
  `auto_backup_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `backup_frequency` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily',
  `backup_time` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '03:30',
  `backup_retention_days` int NOT NULL DEFAULT '30',
  `auto_backup_email_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `backup_destination_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_automation_settings_tenant_id_unique` (`tenant_id`),
  CONSTRAINT `tenant_automation_settings_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_backups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `backup_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full_database',
  `trigger_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual_admin',
  `storage_location` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local_vault',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size_bytes` bigint NOT NULL DEFAULT '0',
  `checksum_md5` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tables_count` int NOT NULL DEFAULT '0',
  `records_count` int NOT NULL DEFAULT '0',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `log_summary` text COLLATE utf8mb4_unicode_ci,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_backups_tenant_id_index` (`tenant_id`),
  CONSTRAINT `tenant_backups_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_bandwidth_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_bandwidth_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_bandwidth_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_bandwidth_mbps` decimal(10,2) DEFAULT NULL,
  `pricing_mode` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COMPONENT_BASED',
  `allocation_type` enum('DEDICATED_CIR','BURSTABLE_MIR','SHARED_POOL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DEDICATED_CIR',
  `global_rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bdix_rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cdn_rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ggc_rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fna_rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `others_rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `flat_rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bundle_global_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bundle_bdix_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bundle_cdn_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bundle_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_bandwidth_plans_tenant_id_is_active_index` (`tenant_id`,`is_active`),
  CONSTRAINT `tenant_bandwidth_plans_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_coverage_zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_coverage_zones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `reseller_id` bigint unsigned DEFAULT NULL,
  `staff_id` bigint unsigned DEFAULT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city_upazila` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `in_charge_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `in_charge_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_coverage_zones_tenant_id_code_unique` (`tenant_id`,`code`),
  KEY `tenant_coverage_zones_code_index` (`code`),
  KEY `tenant_coverage_zones_is_active_index` (`is_active`),
  KEY `tenant_coverage_zones_reseller_id_foreign` (`reseller_id`),
  KEY `tenant_coverage_zones_staff_id_foreign` (`staff_id`),
  CONSTRAINT `tenant_coverage_zones_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `tenant_resellers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_coverage_zones_staff_id_foreign` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_coverage_zones_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_customer_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_customer_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `reseller_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `invoice_no` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_month` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2026-09',
  `package_id` bigint unsigned DEFAULT NULL,
  `package_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `vat_tax` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_payable` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `due_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `issue_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `auto_generated` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_customer_invoices_tenant_id_invoice_no_unique` (`tenant_id`,`invoice_no`),
  KEY `tenant_customer_invoices_reseller_id_foreign` (`reseller_id`),
  KEY `tenant_customer_invoices_customer_id_foreign` (`customer_id`),
  KEY `tenant_customer_invoices_package_id_foreign` (`package_id`),
  KEY `tenant_customer_invoices_invoice_no_index` (`invoice_no`),
  KEY `tenant_customer_invoices_billing_month_index` (`billing_month`),
  KEY `tenant_customer_invoices_status_index` (`status`),
  CONSTRAINT `tenant_customer_invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_customer_invoices_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `tenant_internet_packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_customer_invoices_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `tenant_resellers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_customer_invoices_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_customer_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_customer_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `reseller_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_month` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `collected_by` bigint unsigned DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paid',
  `paid_at` timestamp NULL DEFAULT NULL,
  `transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `sms_sent` tinyint(1) NOT NULL DEFAULT '0',
  `sms_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_customer_payments_tenant_id_foreign` (`tenant_id`),
  KEY `tenant_customer_payments_customer_id_foreign` (`customer_id`),
  KEY `tenant_customer_payments_collected_by_foreign` (`collected_by`),
  KEY `tenant_customer_payments_invoice_no_index` (`invoice_no`),
  KEY `tenant_customer_payments_reseller_id_foreign` (`reseller_id`),
  KEY `tenant_customer_payments_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `tenant_customer_payments_collected_by_foreign` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_customer_payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_customer_payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `tenant_customer_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_customer_payments_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `tenant_resellers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_customer_payments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `reseller_id` bigint unsigned DEFAULT NULL,
  `customer_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `father_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `zone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zone_id` bigint unsigned DEFAULT NULL,
  `division` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thana` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gps_lat` decimal(10,7) DEFAULT NULL,
  `gps_lng` decimal(10,7) DEFAULT NULL,
  `connection_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pppoe',
  `package_id` bigint unsigned DEFAULT NULL,
  `package_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monthly_bill` decimal(10,2) NOT NULL DEFAULT '0.00',
  `router_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_address` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `olt_id` bigint unsigned DEFAULT NULL,
  `onu_mac_sn` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fiber_route_info` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'prepaid',
  `billing_cycle_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `mikrotik_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enabled',
  `due_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `wallet_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `auto_cut_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `grace_period_days` int NOT NULL DEFAULT '2',
  `online_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'offline',
  `last_online_at` timestamp NULL DEFAULT NULL,
  `assigned_collector_id` bigint unsigned DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_customer_username_unique` (`tenant_id`,`username`),
  UNIQUE KEY `tenant_customer_id_unique` (`tenant_id`,`customer_id`),
  KEY `tenant_customers_reseller_id_foreign` (`reseller_id`),
  KEY `tenant_customers_package_id_foreign` (`package_id`),
  KEY `tenant_customers_router_id_foreign` (`router_id`),
  KEY `tenant_customers_olt_id_foreign` (`olt_id`),
  KEY `tenant_customers_assigned_collector_id_foreign` (`assigned_collector_id`),
  KEY `tenant_customers_customer_id_index` (`customer_id`),
  KEY `tenant_customers_username_index` (`username`),
  KEY `tenant_customers_phone_index` (`phone`),
  KEY `tenant_customers_zone_index` (`zone`),
  KEY `tenant_customers_expiry_date_index` (`expiry_date`),
  KEY `tenant_customers_status_index` (`status`),
  KEY `tenant_customers_zone_id_foreign` (`zone_id`),
  CONSTRAINT `tenant_customers_assigned_collector_id_foreign` FOREIGN KEY (`assigned_collector_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_customers_olt_id_foreign` FOREIGN KEY (`olt_id`) REFERENCES `tenant_olts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_customers_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `tenant_internet_packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_customers_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `tenant_resellers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_customers_router_id_foreign` FOREIGN KEY (`router_id`) REFERENCES `tenant_routers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_customers_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_customers_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `tenant_coverage_zones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_daily_cash_handovers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_daily_cash_handovers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `handover_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `collector_id` bigint unsigned NOT NULL,
  `handover_date` date NOT NULL,
  `shift_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily',
  `system_collected_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `handed_over_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `shortage_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `excess_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `digital_collected_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_receipts_count` int unsigned NOT NULL DEFAULT '0',
  `denominations` json DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `verified_by` bigint unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `manager_remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_daily_cash_handovers_handover_no_unique` (`handover_no`),
  KEY `tenant_daily_cash_handovers_tenant_id_foreign` (`tenant_id`),
  KEY `tenant_daily_cash_handovers_collector_id_foreign` (`collector_id`),
  KEY `tenant_daily_cash_handovers_verified_by_foreign` (`verified_by`),
  CONSTRAINT `tenant_daily_cash_handovers_collector_id_foreign` FOREIGN KEY (`collector_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_daily_cash_handovers_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_daily_cash_handovers_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_expense_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('EXPENSE','INCOME') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EXPENSE',
  `code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monthly_budget` decimal(12,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tec_filter` (`tenant_id`,`type`,`is_active`),
  CONSTRAINT `tenant_expense_categories_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_expense_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_expense_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `voucher_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('EXPENSE','INCOME') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EXPENSE',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `account_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payee_payer` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `status` enum('APPROVED','PENDING','REJECTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'APPROVED',
  `created_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tet_tenant_vouch` (`tenant_id`,`voucher_no`),
  KEY `tenant_expense_transactions_category_id_foreign` (`category_id`),
  KEY `tenant_expense_transactions_created_by_foreign` (`created_by`),
  KEY `tenant_expense_transactions_approved_by_foreign` (`approved_by`),
  KEY `idx_tet_type_date` (`tenant_id`,`type`,`transaction_date`),
  KEY `idx_tet_status` (`tenant_id`,`status`),
  CONSTRAINT `tenant_expense_transactions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_expense_transactions_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `tenant_expense_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_expense_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_expense_transactions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_field_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_field_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `job_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fiber_splicing',
  `customer_id` bigint unsigned DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zone_id` bigint unsigned DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `assigned_technician_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` enum('pending','dispatched','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `scheduled_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `issue_description` text COLLATE utf8mb4_unicode_ci,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `optical_rx_before` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `optical_rx_after` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `materials_used` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_field_jobs_job_number_unique` (`job_number`),
  KEY `tenant_field_jobs_tenant_id_index` (`tenant_id`),
  KEY `tenant_field_jobs_customer_id_index` (`customer_id`),
  KEY `tenant_field_jobs_zone_id_index` (`zone_id`),
  KEY `tenant_field_jobs_assigned_to_index` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_gateway_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_gateway_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `transaction_id` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gateway_trx_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bkash',
  `purpose` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CUSTOMER_BILL',
  `customer_id` bigint unsigned DEFAULT NULL,
  `reseller_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `reference_id` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `fee_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BDT',
  `payer_account` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payer_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('SUCCESS','PENDING','FAILED','CANCELLED','REFUNDED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `status_message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_payload` json DEFAULT NULL,
  `initiated_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_gateway_transactions_transaction_id_unique` (`transaction_id`),
  KEY `tenant_gateway_transactions_customer_id_foreign` (`customer_id`),
  KEY `tenant_gateway_transactions_reseller_id_foreign` (`reseller_id`),
  KEY `tenant_gateway_transactions_user_id_foreign` (`user_id`),
  KEY `tenant_gateway_transactions_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `tenant_gateway_transactions_tenant_id_gateway_index` (`tenant_id`,`gateway`),
  KEY `tenant_gateway_transactions_tenant_id_created_at_index` (`tenant_id`,`created_at`),
  KEY `tenant_gateway_transactions_gateway_trx_id_index` (`gateway_trx_id`),
  KEY `tenant_gateway_transactions_gateway_index` (`gateway`),
  KEY `tenant_gateway_transactions_purpose_index` (`purpose`),
  KEY `tenant_gateway_transactions_reference_id_index` (`reference_id`),
  KEY `tenant_gateway_transactions_status_index` (`status`),
  CONSTRAINT `tenant_gateway_transactions_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_gateway_transactions_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `tenant_resellers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_gateway_transactions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_gateway_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_installation_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_installation_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `task_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `applicant_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `applicant_phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `installation_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone_id` bigint unsigned DEFAULT NULL,
  `package_id` bigint unsigned DEFAULT NULL,
  `assigned_technician_id` bigint unsigned DEFAULT NULL,
  `assigned_technician_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `installation_stage` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'feasibility_check',
  `status` enum('pending','scheduled','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `cable_length_meters` int NOT NULL DEFAULT '0',
  `splitter_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onu_model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onu_mac_serial` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `optical_rx_power` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `connection_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `advance_payment` decimal(10,2) NOT NULL DEFAULT '0.00',
  `scheduled_at` datetime DEFAULT NULL,
  `activated_at` datetime DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_installation_tasks_task_number_unique` (`task_number`),
  KEY `tenant_installation_tasks_tenant_id_index` (`tenant_id`),
  KEY `tenant_installation_tasks_customer_id_index` (`customer_id`),
  KEY `tenant_installation_tasks_zone_id_index` (`zone_id`),
  KEY `tenant_installation_tasks_package_id_index` (`package_id`),
  KEY `tenant_installation_tasks_assigned_technician_id_index` (`assigned_technician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_internet_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_internet_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pppoe',
  `tenant_id` bigint unsigned NOT NULL,
  `router_id` bigint unsigned DEFAULT NULL,
  `ip_pool_id` bigint unsigned DEFAULT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `local_address` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remote_address` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `only_one` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `package_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '500.00',
  `wholesale_price` decimal(10,2) DEFAULT NULL,
  `min_retail_price` decimal(10,2) DEFAULT NULL,
  `allow_resellers` tinyint(1) NOT NULL DEFAULT '1',
  `validity_days` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '30',
  `validity_unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'days',
  `upload_speed` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `download_speed` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `facebook_speed` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `youtube_speed` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `bdix_speed` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `mikrotik_profile` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_list` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_sync_mikrotik` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `subscribers_count` int unsigned NOT NULL DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_internet_packages_tenant_id_is_active_index` (`tenant_id`,`is_active`),
  KEY `tenant_internet_packages_tenant_id_service_type_index` (`tenant_id`,`service_type`),
  KEY `tenant_internet_packages_tenant_id_router_id_index` (`tenant_id`,`router_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_ip_pools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_ip_pools` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `router_id` bigint unsigned DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pool_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pppoe',
  `ip_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ipv4',
  `range_start` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `range_end` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cidr_subnet` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dns_primary` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '8.8.8.8',
  `dns_secondary` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '1.1.1.1',
  `next_pool` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_ips` int NOT NULL DEFAULT '0',
  `used_ips` int NOT NULL DEFAULT '0',
  `vlan_id` int DEFAULT NULL,
  `is_sync_mikrotik` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_ip_pools_router_id_foreign` (`router_id`),
  KEY `tenant_ip_pools_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `tenant_ip_pools_tenant_id_pool_type_index` (`tenant_id`,`pool_type`),
  KEY `tenant_ip_pools_tenant_id_router_id_index` (`tenant_id`,`router_id`),
  CONSTRAINT `tenant_ip_pools_router_id_foreign` FOREIGN KEY (`router_id`) REFERENCES `tenant_routers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_ip_pools_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_nas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_nas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `router_id` bigint unsigned DEFAULT NULL,
  `nasname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shortname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('mikrotik','cisco','juniper','huawei','accel-ppp','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mikrotik',
  `ports` int NOT NULL DEFAULT '1812',
  `secret` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `server` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `community` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coa_port` int NOT NULL DEFAULT '3799',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_unicode_ci,
  `last_auth_at` timestamp NULL DEFAULT NULL,
  `last_accounting_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_nas_tenant_id_nasname_unique` (`tenant_id`,`nasname`),
  KEY `tenant_nas_router_id_foreign` (`router_id`),
  KEY `tenant_nas_tenant_id_is_active_index` (`tenant_id`,`is_active`),
  KEY `tenant_nas_is_active_index` (`is_active`),
  CONSTRAINT `tenant_nas_router_id_foreign` FOREIGN KEY (`router_id`) REFERENCES `tenant_routers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_nas_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_notification_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_notification_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `event_trigger` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sms_body` text COLLATE utf8mb4_unicode_ci,
  `whatsapp_body` text COLLATE utf8mb4_unicode_ci,
  `send_sms` tinyint(1) NOT NULL DEFAULT '1',
  `send_whatsapp` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_notification_templates_event_trigger_unique` (`event_trigger`),
  KEY `tenant_notification_templates_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `tenant_notification_templates_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_olts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_olts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `router_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT 'other',
  `vendor` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'EPON OLT',
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hardware_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `firmware_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `web_port` int NOT NULL DEFAULT '80',
  `web_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'root',
  `web_password` text COLLATE utf8mb4_unicode_ci,
  `connection_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'web_api',
  `serial_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `snmp_port` int DEFAULT '161',
  `snmp_community` text COLLATE utf8mb4_unicode_ci,
  `snmp_version` enum('v1','v2c','v3') COLLATE utf8mb4_unicode_ci DEFAULT 'v2c',
  `telnet_port` int DEFAULT '23',
  `telnet_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telnet_password` text COLLATE utf8mb4_unicode_ci,
  `ssh_port` int DEFAULT '22',
  `pon_ports_count` int DEFAULT '8',
  `total_pon_ports` int DEFAULT '8',
  `status` enum('online','offline','error','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `total_onus_count` int NOT NULL DEFAULT '0',
  `online_onus_count` int NOT NULL DEFAULT '0',
  `offline_onus_count` int NOT NULL DEFAULT '0',
  `los_onus_count` int NOT NULL DEFAULT '0',
  `cpu_load` int DEFAULT NULL,
  `memory_usage` int DEFAULT NULL,
  `temperature` double DEFAULT NULL,
  `license_limit` tinyint unsigned NOT NULL DEFAULT '0',
  `license_time_hours` int unsigned NOT NULL DEFAULT '0',
  `license_auto_renew` tinyint(1) NOT NULL DEFAULT '0',
  `license_renew_threshold_hours` int unsigned NOT NULL DEFAULT '168',
  `license_renew_days` int unsigned NOT NULL DEFAULT '0',
  `license_auth_password` text COLLATE utf8mb4_unicode_ci,
  `uptime` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_ping_at` timestamp NULL DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_olts_router_id_foreign` (`router_id`),
  KEY `tenant_olts_tenant_id_status_index` (`tenant_id`,`status`),
  CONSTRAINT `tenant_olts_router_id_foreign` FOREIGN KEY (`router_id`) REFERENCES `tenant_routers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_olts_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_onus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_onus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `olt_id` bigint unsigned NOT NULL,
  `pon_port` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0/1/1',
  `onu_id` int NOT NULL DEFAULT '1',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `desc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_address` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `router_mac` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vendor` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onu_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'EPON',
  `vlan_id` int DEFAULT NULL,
  `vlan_mode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'tag',
  `service_mode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'bridge',
  `pppoe_username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pppoe_password` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lan1_state` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'enable',
  `wifi_ssid` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wifi_password` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catv_state` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'enable',
  `bandwidth_profile` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `config_payload` json DEFAULT NULL,
  `hardware_version` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `software_version` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('online','offline','alarm','unregistered') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'online',
  `running_state` int NOT NULL DEFAULT '1',
  `control_flag` int NOT NULL DEFAULT '1',
  `rx_power_dbm` double DEFAULT NULL,
  `tx_power_dbm` double DEFAULT NULL,
  `distance_m` int DEFAULT NULL,
  `last_online_at` timestamp NULL DEFAULT NULL,
  `last_offline_at` timestamp NULL DEFAULT NULL,
  `offline_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `olt_pon_onu_unique` (`olt_id`,`pon_port`,`onu_id`),
  KEY `tenant_onus_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `tenant_onus_olt_id_status_index` (`olt_id`,`status`),
  KEY `tenant_onus_mac_address_index` (`mac_address`),
  KEY `tenant_onus_customer_id_foreign` (`customer_id`),
  KEY `tenant_onus_router_mac_index` (`router_mac`),
  CONSTRAINT `tenant_onus_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_onus_olt_id_foreign` FOREIGN KEY (`olt_id`) REFERENCES `tenant_olts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_onus_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_reseller_bandwidth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_reseller_bandwidth` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `reseller_id` bigint unsigned NOT NULL,
  `router_id` bigint unsigned DEFAULT NULL,
  `bandwidth_plan_id` bigint unsigned DEFAULT NULL,
  `interface_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vlan_id` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `global_bandwidth_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bdix_bandwidth_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cdn_bandwidth_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ggc_bandwidth_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fna_bandwidth_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `other_bandwidth_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_bandwidth_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `allocation_type` enum('DEDICATED_CIR','BURSTABLE_MIR','SHARED_POOL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DEDICATED_CIR',
  `rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `global_rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bdix_rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cdn_rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ggc_rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fna_rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `others_rate_per_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monthly_bill_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `mikrotik_queue_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_usage_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `peak_usage_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('ACTIVE','THROTTLED','SUSPENDED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_reseller_bandwidth_tenant_id_reseller_id_unique` (`tenant_id`,`reseller_id`),
  KEY `tenant_reseller_bandwidth_reseller_id_foreign` (`reseller_id`),
  KEY `tenant_reseller_bandwidth_router_id_foreign` (`router_id`),
  KEY `tenant_reseller_bandwidth_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `tenant_reseller_bandwidth_tenant_id_router_id_index` (`tenant_id`,`router_id`),
  KEY `tenant_reseller_bandwidth_bandwidth_plan_id_foreign` (`bandwidth_plan_id`),
  CONSTRAINT `tenant_reseller_bandwidth_bandwidth_plan_id_foreign` FOREIGN KEY (`bandwidth_plan_id`) REFERENCES `tenant_bandwidth_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_reseller_bandwidth_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `tenant_resellers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_reseller_bandwidth_router_id_foreign` FOREIGN KEY (`router_id`) REFERENCES `tenant_routers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_reseller_bandwidth_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_reseller_escalations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_reseller_escalations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `escalation_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reseller_id` bigint unsigned NOT NULL,
  `reseller_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'trunk_congestion',
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `impact_level` enum('critical_outage','high_degraded','medium_packet_loss','low_inquiry') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'high_degraded',
  `status` enum('open','tier2_investigating','tier3_noc_escalated','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `assigned_engineer_id` bigint unsigned DEFAULT NULL,
  `assigned_engineer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `affected_circuits` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_description` text COLLATE utf8mb4_unicode_ci,
  `resolution_summary` text COLLATE utf8mb4_unicode_ci,
  `first_response_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_reseller_escalations_escalation_number_unique` (`escalation_number`),
  KEY `tenant_reseller_escalations_tenant_id_index` (`tenant_id`),
  KEY `tenant_reseller_escalations_reseller_id_index` (`reseller_id`),
  KEY `tenant_reseller_escalations_assigned_engineer_id_index` (`assigned_engineer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_reseller_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_reseller_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `reseller_id` bigint unsigned NOT NULL,
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_month` date DEFAULT NULL,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PANEL_SOFTWARE_FEE',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `vat_tax` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `due_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('PAID','UNPAID','PARTIAL','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UNPAID',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `period_start` date DEFAULT NULL,
  `period_end` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `item_details` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_reseller_invoices_tenant_id_invoice_no_unique` (`tenant_id`,`invoice_no`),
  KEY `tenant_reseller_invoices_reseller_id_foreign` (`reseller_id`),
  KEY `tenant_reseller_invoices_tenant_id_reseller_id_index` (`tenant_id`,`reseller_id`),
  KEY `tenant_reseller_invoices_tenant_id_payment_status_index` (`tenant_id`,`payment_status`),
  KEY `tenant_reseller_invoices_created_by_foreign` (`created_by`),
  CONSTRAINT `tenant_reseller_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_reseller_invoices_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `tenant_resellers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_reseller_invoices_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_reseller_recharges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_reseller_recharges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `reseller_id` bigint unsigned NOT NULL,
  `recharge_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `bonus_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_credited` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH',
  `gateway_trx_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_branch` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deposit_date` date DEFAULT NULL,
  `slip_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('APPROVED','PENDING','REJECTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'APPROVED',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_reseller_recharges_tenant_id_recharge_no_unique` (`tenant_id`,`recharge_no`),
  KEY `tenant_reseller_recharges_reseller_id_foreign` (`reseller_id`),
  KEY `tenant_reseller_recharges_approved_by_foreign` (`approved_by`),
  KEY `tenant_reseller_recharges_created_by_foreign` (`created_by`),
  KEY `tenant_reseller_recharges_tenant_id_reseller_id_index` (`tenant_id`,`reseller_id`),
  KEY `tenant_reseller_recharges_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `tenant_reseller_recharges_tenant_id_payment_method_index` (`tenant_id`,`payment_method`),
  CONSTRAINT `tenant_reseller_recharges_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_reseller_recharges_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_reseller_recharges_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `tenant_resellers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_reseller_recharges_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_reseller_wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_reseller_wallet_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `reseller_id` bigint unsigned NOT NULL,
  `trx_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('CREDIT','DEBIT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_before` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance_after` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ADMIN_ADJUSTMENT',
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_reseller_wallet_transactions_tenant_id_trx_id_unique` (`tenant_id`,`trx_id`),
  KEY `tenant_reseller_wallet_transactions_reseller_id_foreign` (`reseller_id`),
  KEY `tenant_reseller_wallet_transactions_created_by_foreign` (`created_by`),
  KEY `tenant_reseller_wallet_transactions_tenant_id_reseller_id_index` (`tenant_id`,`reseller_id`),
  KEY `tenant_reseller_wallet_transactions_tenant_id_type_index` (`tenant_id`,`type`),
  CONSTRAINT `tenant_reseller_wallet_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_reseller_wallet_transactions_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `tenant_resellers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_reseller_wallet_transactions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_resellers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_resellers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `billing_type` enum('PREPAID_WALLET','POSTPAID_MONTHLY','BANDWIDTH_WHOLESALE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PREPAID_WALLET',
  `wallet_balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `credit_limit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `monthly_panel_charge` decimal(10,2) NOT NULL DEFAULT '0.00',
  `panel_expiry_date` date DEFAULT NULL,
  `panel_billing_status` enum('ACTIVE','EXPIRED','GRACE_PERIOD') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_resellers_tenant_id_code_unique` (`tenant_id`,`code`),
  KEY `tenant_resellers_tenant_id_status_index` (`tenant_id`,`status`),
  CONSTRAINT `tenant_resellers_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `color` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'purple',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fa-shield-halved',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `permissions` json DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_roles_tenant_id_name_unique` (`tenant_id`,`name`),
  KEY `tenant_roles_tenant_id_index` (`tenant_id`),
  CONSTRAINT `tenant_roles_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_routers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_routers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ros_version` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `connection_type` enum('api','radius','hybrid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'api',
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_port` int DEFAULT NULL,
  `coa_port` int NOT NULL DEFAULT '3799',
  `use_ssl` tinyint(1) NOT NULL DEFAULT '0',
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` text COLLATE utf8mb4_unicode_ci,
  `radius_secret` text COLLATE utf8mb4_unicode_ci,
  `wireguard_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wireguard_public_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('online','offline','error','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `cpu_load` int DEFAULT NULL,
  `free_memory` bigint DEFAULT NULL,
  `total_memory` bigint DEFAULT NULL,
  `uptime` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_ping_at` timestamp NULL DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_routers_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `tenant_routers_is_active_index` (`is_active`),
  CONSTRAINT `tenant_routers_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_routes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `router_id` bigint unsigned DEFAULT NULL,
  `dst_address` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0.0.0.0/0',
  `gateway` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `routing_table` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'main',
  `distance` int NOT NULL DEFAULT '1',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'static',
  `scope` int NOT NULL DEFAULT '30',
  `target_scope` int NOT NULL DEFAULT '10',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_sync_mikrotik` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_routes_router_id_foreign` (`router_id`),
  KEY `tenant_routes_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `tenant_routes_tenant_id_router_id_index` (`tenant_id`,`router_id`),
  KEY `tenant_routes_tenant_id_type_index` (`tenant_id`,`type`),
  KEY `tenant_routes_tenant_id_dst_address_index` (`tenant_id`,`dst_address`),
  CONSTRAINT `tenant_routes_router_id_foreign` FOREIGN KEY (`router_id`) REFERENCES `tenant_routers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_routes_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_sms_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_sms_gateways` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `channel_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sms',
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_endpoint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_sid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `http_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'POST',
  `custom_headers` json DEFAULT NULL,
  `custom_params` json DEFAULT NULL,
  `cost_per_sms` decimal(8,4) NOT NULL DEFAULT '0.2500',
  `balance` decimal(12,2) NOT NULL DEFAULT '1000.00',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_tested_at` timestamp NULL DEFAULT NULL,
  `last_test_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_sms_gateways_tenant_id_foreign` (`tenant_id`),
  CONSTRAINT `tenant_sms_gateways_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_staff_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_staff_attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `reseller_id` bigint unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `shift` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Morning Shift (09:00 - 18:00)',
  `punch_in_at` datetime DEFAULT NULL,
  `punch_out_at` datetime DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'present',
  `work_duration_minutes` int NOT NULL DEFAULT '0',
  `punch_in_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `punch_out_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `punch_in_device` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `late_reason` text COLLATE utf8mb4_unicode_ci,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `marked_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_user_daily_attendance_unique` (`tenant_id`,`user_id`,`date`),
  KEY `tenant_staff_attendances_user_id_foreign` (`user_id`),
  KEY `tenant_staff_attendances_reseller_id_foreign` (`reseller_id`),
  KEY `tenant_staff_attendances_marked_by_foreign` (`marked_by`),
  KEY `tenant_staff_attendances_date_index` (`date`),
  CONSTRAINT `tenant_staff_attendances_marked_by_foreign` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_staff_attendances_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `tenant_resellers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_staff_attendances_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_staff_attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned NOT NULL,
  `billing_cycle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `started_at` timestamp NULL DEFAULT NULL,
  `current_period_start` date DEFAULT NULL,
  `current_period_end` date DEFAULT NULL,
  `next_billing_date` date DEFAULT NULL,
  `grace_period_ends_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `auto_renew` tinyint(1) NOT NULL DEFAULT '1',
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_subscriptions_tenant_id_foreign` (`tenant_id`),
  KEY `tenant_subscriptions_plan_id_foreign` (`plan_id`),
  CONSTRAINT `tenant_subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `saas_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_subscriptions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_upstream_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_upstream_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `provider_id` bigint unsigned NOT NULL,
  `link_id` bigint unsigned DEFAULT NULL,
  `invoice_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_month` date NOT NULL,
  `bandwidth_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `transmission_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `vat_tax` decimal(12,2) NOT NULL DEFAULT '0.00',
  `other_charges` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `due_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UNPAID',
  `due_date` date DEFAULT NULL,
  `item_breakdown` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_upstream_invoices_provider_id_foreign` (`provider_id`),
  KEY `tenant_upstream_invoices_link_id_foreign` (`link_id`),
  KEY `tenant_upstream_invoices_tenant_id_billing_month_index` (`tenant_id`,`billing_month`),
  KEY `tenant_upstream_invoices_tenant_id_provider_id_index` (`tenant_id`,`provider_id`),
  KEY `tenant_upstream_invoices_tenant_id_payment_status_index` (`tenant_id`,`payment_status`),
  CONSTRAINT `tenant_upstream_invoices_link_id_foreign` FOREIGN KEY (`link_id`) REFERENCES `tenant_upstream_links` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_upstream_invoices_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `tenant_upstream_providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_upstream_invoices_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_upstream_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_upstream_links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `provider_id` bigint unsigned NOT NULL,
  `router_id` bigint unsigned DEFAULT NULL,
  `link_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interface_port` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `circuit_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `global_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bdix_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cdn_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ggc_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fna_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `other_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_mbps` decimal(10,2) NOT NULL DEFAULT '0.00',
  `global_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `bdix_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cdn_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ggc_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fna_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `other_rate` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monthly_transmission_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `est_monthly_bill` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_upstream_links_provider_id_foreign` (`provider_id`),
  KEY `tenant_upstream_links_router_id_foreign` (`router_id`),
  KEY `tenant_upstream_links_tenant_id_provider_id_index` (`tenant_id`,`provider_id`),
  CONSTRAINT `tenant_upstream_links_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `tenant_upstream_providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_upstream_links_router_id_foreign` FOREIGN KEY (`router_id`) REFERENCES `tenant_routers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_upstream_links_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_upstream_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_upstream_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `provider_id` bigint unsigned NOT NULL,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `voucher_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BANK_TRANSFER',
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_ref` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `receipt_attachment` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_upstream_payments_provider_id_foreign` (`provider_id`),
  KEY `tenant_upstream_payments_invoice_id_foreign` (`invoice_id`),
  KEY `tenant_upstream_payments_tenant_id_provider_id_index` (`tenant_id`,`provider_id`),
  KEY `tenant_upstream_payments_tenant_id_invoice_id_index` (`tenant_id`,`invoice_id`),
  KEY `tenant_upstream_payments_tenant_id_voucher_no_index` (`tenant_id`,`voucher_no`),
  CONSTRAINT `tenant_upstream_payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `tenant_upstream_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_upstream_payments_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `tenant_upstream_providers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_upstream_payments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_upstream_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_upstream_providers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carrier_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IIG',
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_branch` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `routing_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_upstream_providers_tenant_id_carrier_type_index` (`tenant_id`,`carrier_type`),
  KEY `tenant_upstream_providers_tenant_id_is_active_index` (`tenant_id`,`is_active`),
  CONSTRAINT `tenant_upstream_providers_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_vlans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_vlans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `router_id` bigint unsigned DEFAULT NULL,
  `olt_id` bigint unsigned DEFAULT NULL,
  `vlan_id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `interface` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ether1',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'service',
  `subnet` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dhcp_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `mtu` int NOT NULL DEFAULT '1500',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_sync_mikrotik` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_vlans_router_id_foreign` (`router_id`),
  KEY `tenant_vlans_olt_id_foreign` (`olt_id`),
  KEY `tenant_vlans_tenant_id_status_index` (`tenant_id`,`status`),
  KEY `tenant_vlans_tenant_id_vlan_id_index` (`tenant_id`,`vlan_id`),
  KEY `tenant_vlans_tenant_id_router_id_index` (`tenant_id`,`router_id`),
  KEY `tenant_vlans_tenant_id_type_index` (`tenant_id`,`type`),
  CONSTRAINT `tenant_vlans_olt_id_foreign` FOREIGN KEY (`olt_id`) REFERENCES `tenant_olts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_vlans_router_id_foreign` FOREIGN KEY (`router_id`) REFERENCES `tenant_routers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tenant_vlans_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_wallet_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `wallet_id` bigint unsigned NOT NULL,
  `type` enum('credit','debit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_wallet_transactions_tenant_id_foreign` (`tenant_id`),
  KEY `tenant_wallet_transactions_wallet_id_foreign` (`wallet_id`),
  CONSTRAINT `tenant_wallet_transactions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_wallet_transactions_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `tenant_wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenant_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BDT',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_wallets_tenant_id_unique` (`tenant_id`),
  CONSTRAINT `tenant_wallets_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `btrc_license_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `btrc_license_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Nationwide ISP',
  `trade_license_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tin_bin_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `support_hotline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_driver` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'smtp',
  `mail_host` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_port` int NOT NULL DEFAULT '587',
  `mail_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_password` text COLLATE utf8mb4_unicode_ci,
  `mail_encryption` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tls',
  `mail_from_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_from_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mail_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person_designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BDT',
  `currency_symbol` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Óº¦',
  `timezone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Dhaka',
  `invoice_footer_notes` text COLLATE utf8mb4_unicode_ci,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `favicon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `saas_plan_id` bigint unsigned DEFAULT NULL,
  `status` enum('active','suspended','pending','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `subscription_expires_at` date DEFAULT NULL,
  `wallet_balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenants_slug_unique` (`slug`),
  UNIQUE KEY `tenants_domain_unique` (`domain`),
  KEY `tenants_saas_plan_id_foreign` (`saas_plan_id`),
  CONSTRAINT `tenants_saas_plan_id_foreign` FOREIGN KEY (`saas_plan_id`) REFERENCES `saas_plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_message_id` bigint unsigned NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_attachments_ticket_message_id_foreign` (`ticket_message_id`),
  CONSTRAINT `ticket_attachments_ticket_message_id_foreign` FOREIGN KEY (`ticket_message_id`) REFERENCES `ticket_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `support_ticket_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `sender_type` enum('tenant','owner') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tenant',
  `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_internal_note` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_messages_user_id_foreign` (`user_id`),
  KEY `ticket_messages_support_ticket_id_created_at_index` (`support_ticket_id`,`created_at`),
  CONSTRAINT `ticket_messages_support_ticket_id_foreign` FOREIGN KEY (`support_ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_replies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_staff` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_replies_ticket_id_foreign` (`ticket_id`),
  KEY `ticket_replies_user_id_foreign` (`user_id`),
  CONSTRAINT `ticket_replies_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_replies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `reseller_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'isp_admin',
  `admin_role` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Super Administrator',
  `is_staff` tinyint(1) NOT NULL DEFAULT '0',
  `staff_id` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `designation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shift` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Morning (08:00 - 16:00)',
  `shift_status` enum('on_duty','off_duty','on_leave') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'on_duty',
  `status` enum('active','inactive','banned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `credit_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ip_allowlist` json DEFAULT NULL,
  `two_factor_enforced` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_tenant_id_foreign` (`tenant_id`),
  KEY `users_department_id_foreign` (`department_id`),
  KEY `users_reseller_id_foreign` (`reseller_id`),
  CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `support_departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_reseller_id_foreign` FOREIGN KEY (`reseller_id`) REFERENCES `tenant_resellers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_deliveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `webhook_id` bigint unsigned NOT NULL,
  `event` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `response_code` int DEFAULT NULL,
  `response_body` text COLLATE utf8mb4_unicode_ci,
  `response_time_ms` int NOT NULL DEFAULT '0',
  `status` enum('success','failed','retrying') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `delivered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_webhook_deliv_time` (`webhook_id`,`delivered_at`),
  CONSTRAINT `webhook_deliveries_webhook_id_foreign` FOREIGN KEY (`webhook_id`) REFERENCES `webhooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhooks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `events` json DEFAULT NULL,
  `content_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'application/json',
  `verify_ssl` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('active','paused','failing') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `last_response_code` int DEFAULT NULL,
  `success_count` int unsigned NOT NULL DEFAULT '0',
  `failure_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhooks_user_id_foreign` (`user_id`),
  KEY `idx_webhooks_status` (`status`,`created_at`),
  CONSTRAINT `webhooks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_09_04_184801_create_owner_saas_tables',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_09_04_185801_create_settings_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_09_05_175733_enhance_saas_plans_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_09_05_185000_create_subscription_billing_payment_architecture',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_09_05_191500_create_automation_logs_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_09_05_192500_create_tenant_activity_and_sms_logs_tables',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_09_05_200000_create_security_audit_logs_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_09_06_183000_create_support_ticket_architecture',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_09_07_100000_create_enterprise_network_engine_architecture',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_09_07_160000_add_is_active_to_tenant_routers_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_09_07_170000_create_tenant_nas_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_09_07_180000_enhance_tenant_olts_and_create_tenant_onus_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_09_08_142131_add_config_fields_to_tenant_onus_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_09_08_143353_create_tenant_ip_pools_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_09_08_150000_create_tenant_vlans_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_09_08_160000_create_tenant_routes_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_09_08_180000_create_freeradius_tables',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_08_24_000001_create_activity_logs_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_08_24_000002_create_hosting_plans_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_08_24_000003_create_servers_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_08_24_000004_create_subscriptions_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_08_24_000005_create_websites_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_08_24_000006_create_databases_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_08_24_000007_create_email_accounts_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_08_24_000008_create_ftp_accounts_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_08_24_000009_create_invoices_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_08_24_000010_create_invoice_items_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_08_24_000011_create_payments_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_09_08_170000_create_tenant_internet_packages_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_08_24_000012_create_support_tickets_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_08_24_000013_create_ticket_replies_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_08_24_000014_create_backup_jobs_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_08_24_000015_create_ip_blocks_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_08_24_000016_create_api_tokens_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_08_24_000017_create_system_settings_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_08_24_173509_create_permission_tables',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_08_24_173649_create_notifications_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_08_25_000001_create_server_groups_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_08_25_000002_enhance_servers_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_08_25_000003_create_server_metrics_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_08_25_000004_create_server_services_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_08_25_000005_create_server_logs_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_08_25_000006_create_server_events_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_08_25_000007_create_server_credentials_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_08_25_000008_add_host_key_fingerprint_to_servers_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_08_25_000009_create_monitoring_infrastructure_tables',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_08_30_000001_create_email_domains_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_08_30_000001_create_sftp_users_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_08_30_000002_add_used_quota_to_email_accounts',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_08_30_000002_create_domain_aliases_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_08_30_000003_make_subscription_id_nullable_in_email_accounts',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_08_30_000004_create_email_forwarders_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_08_30_000005_create_email_auto_responders_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_08_30_000006_create_email_spam_settings_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_08_30_000007_create_email_messages_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_08_30_000008_create_email_relay_settings_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_08_31_000001_create_dns_zones_and_records_tables',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_08_31_000002_create_dns_templates_tables',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_08_31_000003_create_nameservers_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_08_31_000004_create_ssl_certificates_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_08_31_000005_create_firewall_rules_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_08_31_000006_update_ip_blocks_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_08_31_000007_create_ip_allowlists_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_08_31_000008_add_quota_overrides_to_subscriptions_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_08_31_000009_enhance_backup_jobs_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_08_31_000010_add_logs_to_backup_jobs_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_08_31_000011_create_backup_storages_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_08_31_000012_create_backup_schedules_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_08_31_190033_create_cron_jobs_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_08_31_190034_create_cron_job_logs_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_09_01_000001_create_transactions_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_09_01_000002_create_coupons_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_09_01_000003_create_client_credits_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_09_01_000004_create_payment_gateways_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_09_01_000005_add_department_to_support_tickets_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_09_01_000006_create_support_departments_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_09_01_000007_create_support_agent_profiles_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_09_01_000008_create_canned_responses_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_09_01_000009_create_announcements_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_09_01_000010_create_login_histories_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_09_01_000011_create_api_keys_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_09_01_000012_create_api_users_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_09_01_000013_create_webhooks_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_09_01_000014_create_integrations_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_09_01_000015_create_api_logs_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_09_01_000016_create_api_rate_limits_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_09_01_000017_add_admin_columns_to_users_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_09_01_000018_add_staff_details_to_users_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_09_01_000019_seed_rbac_roles_and_permissions',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_09_01_000020_add_category_and_description_to_permissions_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_09_01_000021_create_system_settings_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_09_03_084605_create_domain_redirects_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_09_03_085355_add_cdn_settings_to_websites_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_09_03_090557_create_word_press_installations_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_09_03_174705_create_git_repositories_and_logs_tables',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_09_03_175117_create_protected_directories_tables',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_09_03_175835_create_hotlink_protections_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_09_03_180432_create_directory_indexings_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_09_04_000001_add_service_entitlements_to_hosting_plans',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_09_08_200000_create_tenant_resellers_and_packages_tables',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_09_08_210000_create_tenant_reseller_invoices_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_09_08_220000_create_tenant_reseller_wallet_transactions_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_09_08_230000_create_tenant_reseller_recharges_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_09_08_240000_create_tenant_reseller_bandwidth_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2026_09_09_000001_add_reseller_id_to_users_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2026_09_09_000002_create_tenant_staff_attendances_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2026_09_09_000003_create_tenant_customers_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2026_09_09_200000_drop_tenant_reseller_packages_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2026_09_10_200000_add_ggc_fna_other_to_tenant_reseller_bandwidth_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2026_09_11_120000_create_tenant_customer_payments_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2026_09_13_181500_create_tenant_coverage_zones_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2026_09_13_182500_add_reseller_id_to_tenant_coverage_zones_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2026_09_13_183500_add_staff_id_to_tenant_coverage_zones_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2026_09_13_184500_add_mikrotik_status_to_tenant_customers_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2026_09_13_185500_create_tenant_customer_invoices_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2026_09_13_191500_add_extended_fields_to_tenant_customer_payments',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2026_09_13_192500_create_tenant_daily_cash_handovers_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2026_09_14_104500_add_extended_fields_to_tenant_reseller_invoices',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2026_09_14_105000_create_tenant_gateway_transactions_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2026_09_14_110000_create_tenant_income_and_expense_tables',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2026_09_14_120000_create_tenant_field_jobs_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2026_09_14_130000_create_tenant_installation_tasks_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2026_09_14_140000_create_tenant_reseller_escalations_table',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2026_09_14_150000_add_extended_profile_fields_to_tenants_table',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2026_09_14_160000_create_tenant_sms_and_whatsapp_gateway_tables',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2026_09_14_170000_create_tenant_automation_settings_table',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2026_09_14_180000_create_tenant_roles_table',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2026_09_14_180000_create_tenant_backups_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2026_09_14_194000_add_email_gateway_and_backup_email_settings',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2026_09_15_081500_add_reseller_id_to_support_tickets_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2026_09_16_160000_add_license_fields_to_tenant_olts_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2026_09_20_120000_create_tenant_bandwidth_plans_table',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2026_09_20_130000_add_volume_slabs_and_category_rates_to_bandwidth_plans',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2026_09_20_140000_change_vlan_id_to_string_in_tenant_reseller_bandwidth',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2026_09_20_150000_create_tenant_upstream_tables',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2026_10_25_211500_add_router_mac_and_customer_id_to_tenant_onus_table',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2026_10_29_235500_drop_legacy_web_hosting_tables',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2026_10_30_002800_drop_legacy_backup_jobs_table',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2026_10_30_004500_drop_remaining_legacy_hosting_tables',65);
