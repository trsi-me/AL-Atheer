-- =============================================================================
-- مشروع الأثير — ملف SQL موحد (إنشاء القاعدة، الجداول، البذور، وإصلاحات اختيارية)
-- ترميز: UTF-8
-- =============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS `alatheer_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `alatheer_db`;

DROP TABLE IF EXISTS `routes`;
CREATE TABLE `routes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `detail_extra` TEXT NULL,
  `date` DATE NULL,
  `time` TIME NULL,
  `meeting_point` VARCHAR(500) NULL,
  `price` DECIMAL(10,2) NULL,
  `min_participants` INT NULL,
  `max_participants` INT NULL,
  `location` TEXT NULL,
  `itinerary` TEXT NULL,
  `image_url` VARCHAR(1000) NULL,
  `source_url` VARCHAR(1000) NOT NULL,
  `booking_url` VARCHAR(1000) NULL,
  `type` ENUM('رياضي','فلكي','ثقافي','زراعي','مهاجر') NOT NULL DEFAULT 'رياضي',
  `map_json` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_source_url` (`source_url`(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_ref` VARCHAR(24) NOT NULL,
  `order_ref` VARCHAR(24) NULL,
  `route_id` INT UNSIGNED NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(255) NULL,
  `civil_id` VARCHAR(20) NULL,
  `participants` INT UNSIGNED NOT NULL DEFAULT 25,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `payment_method` VARCHAR(32) NOT NULL,
  `payment_status` ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booking_ref` (`booking_ref`),
  KEY `idx_route_id` (`route_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_order_ref` (`order_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key_name` VARCHAR(255) NOT NULL,
  `key_value` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`key_name`, `key_value`) VALUES
('site_title', 'جمعية المشي والجري بالأحساء'),
('admin_note', 'لوحة التحكم — جمعية المشي والجري بالأحساء')
ON DUPLICATE KEY UPDATE `key_name` = VALUES(`key_name`);

INSERT INTO `routes` (`name`, `description`, `detail_extra`, `date`, `time`, `meeting_point`, `price`, `min_participants`, `max_participants`, `location`, `itinerary`, `image_url`, `source_url`, `booking_url`, `type`, `map_json`, `is_active`) VALUES
('مسار الأثير 2', 'مسار فلكي ليلي ضمن مبادرة عين على الأحساء: مشاهدة النجوم والمشي تحت سماء الصحراء في جودة شمال الأحساء، مناسب للعوائل.', '• البرنامج يشمل فقرات مصاحبة ومشي ليلي وتعرف على النجوم.\n• يُنصح بارتداء ملابس مناسبة للبرّ والليل.', NULL, NULL, NULL, NULL, 25, 100, 'شمال الأحساء (جودة)', NULL, NULL, 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%A3%D8%AB%D9%8A%D8%B1-2/', 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%A3%D8%AB%D9%8A%D8%B1-2/checkout/', 'فلكي', NULL, 1),
('مسار الرز الحساوي', 'مسار زراعي يبرز تراث الأحساء في زراعة الرز وطبيعة المزارع والمناطق الريفية.', '• جولة ميدانية في بيئة زراعية محلية.\n• يشمل شرحاً موجزاً عن المحصول والمنطقة.', NULL, NULL, NULL, NULL, 25, 100, 'الأحساء', NULL, NULL, 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%B1%D8%B2-%D8%A7%D9%84%D8%AD%D8%B3%D8%A7%D9%88%D9%8A/', 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%B1%D8%B2-%D8%A7%D9%84%D8%AD%D8%B3%D8%A7%D9%88%D9%8A/checkout/', 'زراعي', NULL, 1),
('مسار الأثير', 'رحلة فلكية ومشي ليلي للتعرف على الأجرام السماوية وتصويرها في بيئة صحراوية بعيدة عن التلوث الضوئي.', '• يُفضّل إحضار كاميرا أو هاتف للتصوير الليلي.\n• يتوفر شرح مبسّط عن الأجرام المرئية.', NULL, NULL, NULL, NULL, 25, 100, 'شمال الأحساء', NULL, NULL, 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%A7%D8%AB%D9%8A%D8%B1/', 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%A7%D8%AB%D9%8A%D8%B1/checkout/', 'فلكي', NULL, 1),
('مسار القارة', 'مسار ثقافي يستعرض معالم وقيمة المنطقة وروابطها التاريخية ضمن تجربة مشي منظمة.', '• محطات تعريفية عن المعالم والسياق التاريخي.\n• مسار مشي مريح يناسب مختلف الأعمار.', NULL, NULL, NULL, NULL, 25, 100, 'الأحساء', NULL, NULL, 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D9%82%D8%A7%D8%B1%D8%A9/', 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D9%82%D8%A7%D8%B1%D8%A9/checkout/', 'ثقافي', NULL, 1),
('مسار الطيور المهاجرة', 'مسار لمشاهدة الطيور المهاجرة والتعرف على بيئاتها في مواسمها، ضمن تنظيم الجمعية.', '• يعتمد البرنامج على موسمية الطيور وحالة الطقس.\n• يُنصح بمناظير مشاهدة إن توفرت.', NULL, NULL, NULL, NULL, 25, 100, 'الأحساء', NULL, 'assets/images/الطيور المهاجرة.jpeg', 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%B7%D9%8A%D9%88%D8%B1-%D8%A7%D9%84%D9%85%D9%87%D8%A7%D8%AC%D8%B1%D8%A9/', 'https://www.wra.org.sa/programs-register/%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%B7%D9%8A%D9%88%D8%B1-%D8%A7%D9%84%D9%85%D9%87%D8%A7%D8%AC%D8%B1%D8%A9/checkout/', 'مهاجر', NULL, 1),
('المسار الثقافي', 'جولة ثقافية تربط بين المشي والتعرف على محطات تراثية ومعرفية في المحافظة.', '• محطات تعريفية موزعة على المسار.\n• مناسب للعائلات والمهتمين بالتراث.', NULL, NULL, NULL, NULL, 25, 100, 'الأحساء', NULL, NULL, 'https://www.wra.org.sa/programs-register/%D8%A7%D9%84%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%AB%D9%82%D8%A7%D9%81%D9%8A/', 'https://www.wra.org.sa/programs-register/%D8%A7%D9%84%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%AB%D9%82%D8%A7%D9%81%D9%8A/checkout/', 'ثقافي', NULL, 1),
('المسار الزراعي عين على الأحساء', 'مسار زراعي ضمن مبادرة عين على الأحساء لربط النشاط البدني بالبيئة الزراعية المحلية.', '• تعرّف على المزارع والمنتج المحلي.\n• نشاط بدني خفيف ضمن مسار منظم.', NULL, NULL, NULL, NULL, 25, 100, 'الأحساء', NULL, 'assets/images/المسار الزراعي.jpg', 'https://www.wra.org.sa/programs-register/%D8%A7%D9%84%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%B2%D8%B1%D8%A7%D8%B9%D9%8A-%D8%B9%D9%8A%D9%86-%D8%B9%D9%84%D9%89-%D8%A7%D9%84%D8%A3%D8%AD%D8%B3%D8%A7%D8%A1-/', 'https://www.wra.org.sa/programs-register/%D8%A7%D9%84%D9%85%D8%B3%D8%A7%D8%B1-%D8%A7%D9%84%D8%B2%D8%B1%D8%A7%D8%B9%D9%8A-%D8%B9%D9%8A%D9%86-%D8%B9%D9%84%D9%89-%D8%A7%D9%84%D8%A3%D8%AD%D8%B3%D8%A7%D8%A1-/checkout/', 'زراعي', NULL, 1);

-- -----------------------------------------------------------------------------
-- ترقية قواعد قديمة (نفّذ فقط إن كان جدول routes موجوداً بدون detail_extra أو map_json)
-- إن ظهر خطأ Duplicate column name فالمحتوى مُحدَّث مسبقاً — تجاهل.
-- -----------------------------------------------------------------------------
-- ALTER TABLE `routes` ADD COLUMN `map_json` TEXT NULL;
-- ALTER TABLE `routes` ADD COLUMN `detail_extra` TEXT NULL;

-- -----------------------------------------------------------------------------
-- تصحيح مسارات الصور لملفاتك الأصلية (آمن لإعادة التشغيل؛ مفيد لقواعد قديمة)
-- -----------------------------------------------------------------------------
UPDATE `routes` SET `image_url` = 'assets/images/الطيور المهاجرة.jpeg' WHERE `name` = 'مسار الطيور المهاجرة';
UPDATE `routes` SET `image_url` = 'assets/images/المسار الزراعي.jpg' WHERE `name` = 'المسار الزراعي عين على الأحساء';
