-- ============================================================
--  ระบบสารบรรณอิเล็กทรอนิกส์ (หนังสือรับ-ส่ง)
--  สคีมาฐานข้อมูล MySQL 5.7+ / MariaDB 10.3+
--  ใช้งาน: mysql -u root -p saraban < database/schema.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `document_logs`;
DROP TABLE IF EXISTS `document_attachments`;
DROP TABLE IF EXISTS `document_assignments`;
DROP TABLE IF EXISTS `documents`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `organizations`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `provinces`;
DROP TABLE IF EXISTS `settings`;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- จังหวัด (ใช้กับที่อยู่หน่วยงานภายนอก)
-- ------------------------------------------------------------
CREATE TABLE `provinces` (
  `id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_province_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ฝ่าย/กลุ่มงานภายในหน่วยงาน
-- ------------------------------------------------------------
CREATE TABLE `departments` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`      VARCHAR(20)  NOT NULL,
  `name`      VARCHAR(150) NOT NULL,
  `is_active` TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dept_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- หน่วยงานภายนอกที่ติดต่อบ่อย (ใช้เลือกเป็นผู้ส่ง/ผู้รับ)
-- ------------------------------------------------------------
CREATE TABLE `organizations` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(255) NOT NULL,
  `province_id` INT UNSIGNED NULL,
  `address`     VARCHAR(255) NULL,
  `phone`       VARCHAR(50)  NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_org_province` (`province_id`),
  CONSTRAINT `fk_org_province` FOREIGN KEY (`province_id`)
    REFERENCES `provinces` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ผู้ใช้งาน
-- หมายเหตุ: password เก็บเป็น plain text ตามที่ผู้ใช้ระบุ
--          ถ้าต้องการความปลอดภัยให้เปลี่ยนไปใช้ password_hash()
--          แก้ที่ App\Models\User::verify() จุดเดียว
-- ------------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `password`      VARCHAR(255) NOT NULL,
  `full_name`     VARCHAR(150) NOT NULL,
  `position`      VARCHAR(150) NULL,
  `department_id` INT UNSIGNED NULL,
  `role`          ENUM('admin','clerk','staff') NOT NULL DEFAULT 'staff',
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  KEY `idx_user_department` (`department_id`),
  CONSTRAINT `fk_user_department` FOREIGN KEY (`department_id`)
    REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ทะเบียนหนังสือ (รับและส่งอยู่ตารางเดียวกัน แยกด้วย direction)
-- ------------------------------------------------------------
CREATE TABLE `documents` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `direction`           ENUM('incoming','outgoing') NOT NULL,
  `reg_year`            SMALLINT UNSIGNED NOT NULL COMMENT 'ปี พ.ศ.',
  `reg_number`          INT UNSIGNED NOT NULL COMMENT 'เลขทะเบียน นับแยกตาม direction+ปี',
  `reg_datetime`        DATETIME     NOT NULL COMMENT 'วันเวลาที่ลงทะเบียน',
  `doc_number`          VARCHAR(100) NULL COMMENT 'ที่/เลขที่หนังสือ',
  `doc_date`            DATE         NULL COMMENT 'ลงวันที่',
  `from_org_id`         INT UNSIGNED NULL,
  `from_text`           VARCHAR(255) NULL COMMENT 'ผู้ส่ง (กรณีไม่ได้เลือกจากรายการ)',
  `to_org_id`           INT UNSIGNED NULL,
  `to_text`             VARCHAR(255) NULL COMMENT 'ผู้รับ (กรณีไม่ได้เลือกจากรายการ)',
  `subject`             VARCHAR(500) NOT NULL COMMENT 'เรื่อง',
  `detail`              TEXT         NULL COMMENT 'รายละเอียด/การปฏิบัติ',
  `doc_type`            ENUM('ภายนอก','ภายใน','ประทับตรา','สั่งการ','ประชาสัมพันธ์','เจ้าหน้าที่ทำขึ้น') NOT NULL DEFAULT 'ภายนอก',
  `speed`               ENUM('ปกติ','ด่วน','ด่วนมาก','ด่วนที่สุด') NOT NULL DEFAULT 'ปกติ',
  `secrecy`             ENUM('ปกติ','ลับ','ลับมาก','ลับที่สุด') NOT NULL DEFAULT 'ปกติ',
  `status`              ENUM('pending','in_progress','done','closed') NOT NULL DEFAULT 'pending',
  `owner_department_id` INT UNSIGNED NULL COMMENT 'ฝ่ายเจ้าของเรื่อง',
  `signer`              VARCHAR(150) NULL COMMENT 'ผู้ลงนาม (หนังสือส่ง)',
  `created_by`          INT UNSIGNED NULL,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  -- กันเลขทะเบียนซ้ำแม้เจ้าหน้าที่สองคนกดบันทึกพร้อมกัน
  UNIQUE KEY `uq_reg` (`direction`, `reg_year`, `reg_number`),
  KEY `idx_doc_date` (`doc_date`),
  KEY `idx_reg_datetime` (`reg_datetime`),
  KEY `idx_status` (`status`),
  KEY `idx_speed` (`speed`),
  KEY `idx_owner_dept` (`owner_department_id`),
  KEY `idx_subject` (`subject`(100)),
  CONSTRAINT `fk_doc_from_org` FOREIGN KEY (`from_org_id`)
    REFERENCES `organizations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_doc_to_org` FOREIGN KEY (`to_org_id`)
    REFERENCES `organizations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_doc_owner_dept` FOREIGN KEY (`owner_department_id`)
    REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_doc_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- การเกษียณ/มอบหมายหนังสือ
-- ------------------------------------------------------------
CREATE TABLE `document_assignments` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `document_id`   INT UNSIGNED NOT NULL,
  `department_id` INT UNSIGNED NULL,
  `assigned_to`   INT UNSIGNED NULL COMMENT 'มอบหมายถึงบุคคล (ถ้าระบุ)',
  `instruction`   TEXT         NULL COMMENT 'คำสั่งการ/ความเห็นของผู้บังคับบัญชา',
  `assigned_by`   INT UNSIGNED NULL,
  `assigned_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status`        ENUM('pending','in_progress','done','closed') NOT NULL DEFAULT 'pending',
  `response_note` TEXT         NULL COMMENT 'ผลการดำเนินการ',
  `responded_at`  DATETIME     NULL,
  PRIMARY KEY (`id`),
  KEY `idx_asg_document` (`document_id`),
  KEY `idx_asg_department` (`department_id`),
  KEY `idx_asg_user` (`assigned_to`),
  KEY `idx_asg_status` (`status`),
  CONSTRAINT `fk_asg_document` FOREIGN KEY (`document_id`)
    REFERENCES `documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asg_department` FOREIGN KEY (`department_id`)
    REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_asg_user` FOREIGN KEY (`assigned_to`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_asg_by` FOREIGN KEY (`assigned_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ไฟล์แนบ (ไฟล์จริงอยู่ใน storage/uploads นอก docroot)
-- ------------------------------------------------------------
CREATE TABLE `document_attachments` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `document_id`   INT UNSIGNED NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name`   VARCHAR(255) NOT NULL,
  `mime`          VARCHAR(100) NOT NULL,
  `size`          INT UNSIGNED NOT NULL,
  `uploaded_by`   INT UNSIGNED NULL,
  `uploaded_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stored_name` (`stored_name`),
  KEY `idx_att_document` (`document_id`),
  CONSTRAINT `fk_att_document` FOREIGN KEY (`document_id`)
    REFERENCES `documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_att_user` FOREIGN KEY (`uploaded_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ประวัติการดำเนินการ (audit trail)
-- ------------------------------------------------------------
CREATE TABLE `document_logs` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `document_id` INT UNSIGNED NOT NULL,
  `user_id`     INT UNSIGNED NULL,
  `action`      VARCHAR(50)  NOT NULL,
  `detail`      VARCHAR(500) NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_document` (`document_id`),
  CONSTRAINT `fk_log_document` FOREIGN KEY (`document_id`)
    REFERENCES `documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- ตั้งค่าระบบ (key/value)
-- ------------------------------------------------------------
CREATE TABLE `settings` (
  `key`   VARCHAR(50)  NOT NULL,
  `value` VARCHAR(500) NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
