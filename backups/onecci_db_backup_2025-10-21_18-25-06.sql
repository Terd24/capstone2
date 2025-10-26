-- OneCCI Database Backup
-- Generated: 2025-10-21 18:25:06
-- Approved by: School Owner
-- Database: onecci_db

SET FOREIGN_KEY_CHECKS=0;


-- Table: approval_requests
DROP TABLE IF EXISTS `approval_requests`;
CREATE TABLE `approval_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_type` enum('permanent_delete_student','permanent_delete_employee') NOT NULL,
  `record_id` varchar(50) NOT NULL,
  `record_table` varchar(50) NOT NULL,
  `requested_by` varchar(100) NOT NULL,
  `request_reason` text DEFAULT NULL,
  `status` enum('pending','approved','denied') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` varchar(100) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: archive_log
DROP TABLE IF EXISTS `archive_log`;
CREATE TABLE `archive_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `record_type` enum('student','employee') NOT NULL,
  `record_id` int(11) NOT NULL,
  `action` enum('archived','restored','exported') NOT NULL,
  `performed_by` varchar(100) DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `idx_record_type` (`record_type`),
  KEY `idx_action` (`action`),
  KEY `idx_performed_at` (`performed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: archive_log
INSERT INTO `archive_log` VALUES ('1', 'student', '69', 'archived', 'Super Admin', '2025-10-14 12:36:24', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\",\"timestamp\":\"2025-10-14 06:36:24\"}', '::1');
INSERT INTO `archive_log` VALUES ('2', 'student', '68', '', 'Super Admin', '2025-10-14 12:41:10', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\",\"timestamp\":\"2025-10-14 06:41:10\"}', '::1');
INSERT INTO `archive_log` VALUES ('3', 'student', '1', 'exported', 'Super Admin', '2025-10-14 12:41:30', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\",\"timestamp\":\"2025-10-14 06:41:30\"}', '::1');
INSERT INTO `archive_log` VALUES ('4', 'employee', '77', '', 'Super Admin', '2025-10-14 12:44:27', '{\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\",\"timestamp\":\"2025-10-14 06:44:27\"}', '::1');


-- Table: archived_employees
DROP TABLE IF EXISTS `archived_employees`;
CREATE TABLE `archived_employees` (
  `archive_id` int(11) NOT NULL AUTO_INCREMENT,
  `original_id` int(11) NOT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `rfid_uid` varchar(50) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(100) DEFAULT NULL,
  `deleted_reason` text DEFAULT NULL,
  `archive_scheduled` tinyint(1) DEFAULT 0,
  `archive_scheduled_by` varchar(100) DEFAULT NULL,
  `archive_scheduled_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT current_timestamp(),
  `archived_by` varchar(100) DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  PRIMARY KEY (`archive_id`),
  KEY `idx_original_id` (`original_id`),
  KEY `idx_id_number` (`id_number`),
  KEY `idx_archived_at` (`archived_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data for table: archived_employees
INSERT INTO `archived_employees` VALUES ('6', '94', 'CCI2025-002', 'hr', '', 'admin', 'HR Manager', 'Human Resources', 'admin@gmail.com', '639129496157', 'blk88, lot 6 , sjdm, bulacan,', '2025-10-21 15:03:44', '2025-10-21', NULL, '2025-10-21 18:20:00', 'Super Admin', 'Deleted by Super Admin', '0', NULL, NULL, '2025-10-21 18:21:30', 'School Owner', 'Approved by Owner: ');


-- Table: archived_parent_accounts
DROP TABLE IF EXISTS `archived_parent_accounts`;
CREATE TABLE `archived_parent_accounts` (
  `archive_id` int(11) NOT NULL AUTO_INCREMENT,
  `original_parent_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `child_id` varchar(50) DEFAULT NULL,
  `must_change_password` tinyint(1) DEFAULT 0,
  `archived_at` datetime DEFAULT NULL,
  `archived_by` varchar(100) DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  PRIMARY KEY (`archive_id`),
  KEY `idx_child_id` (`child_id`),
  KEY `idx_archived_at` (`archived_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: archived_students
DROP TABLE IF EXISTS `archived_students`;
CREATE TABLE `archived_students` (
  `archive_id` int(11) NOT NULL AUTO_INCREMENT,
  `original_id` int(11) NOT NULL,
  `lrn` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `academic_track` varchar(100) DEFAULT NULL,
  `enrollment_status` varchar(50) DEFAULT NULL,
  `school_type` varchar(50) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `grade_level` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `birthplace` varchar(255) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `credentials` text DEFAULT NULL,
  `payment_mode` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `father_occupation` varchar(255) DEFAULT NULL,
  `father_contact` varchar(50) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `mother_occupation` varchar(255) DEFAULT NULL,
  `mother_contact` varchar(50) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_occupation` varchar(255) DEFAULT NULL,
  `guardian_contact` varchar(50) DEFAULT NULL,
  `last_school` varchar(255) DEFAULT NULL,
  `last_school_year` varchar(20) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `rfid_uid` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `class_schedule` text DEFAULT NULL,
  `must_change_password` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(100) DEFAULT NULL,
  `deleted_reason` text DEFAULT NULL,
  `archived_at` datetime DEFAULT current_timestamp(),
  `archived_by` varchar(100) DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  PRIMARY KEY (`archive_id`),
  KEY `idx_original_id` (`original_id`),
  KEY `idx_lrn` (`lrn`),
  KEY `idx_id_number` (`id_number`),
  KEY `idx_archived_at` (`archived_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table: attendance
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: attendance_account
DROP TABLE IF EXISTS `attendance_account`;
CREATE TABLE `attendance_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: attendance_account
INSERT INTO `attendance_account` VALUES ('2', 'AttendanceAccount', '$2y$10$.dNFE/S9W5dCa05hWXuW6eWAt6FBZPCUnkEjQzGMaUOFmmOl0S69O', '2025-09-09 19:02:51', '2025-09-09 19:02:51');


-- Table: attendance_archive
DROP TABLE IF EXISTS `attendance_archive`;
CREATE TABLE `attendance_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_id` int(11) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `user_type` varchar(50) DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` varchar(100) DEFAULT NULL,
  `archived_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_id_number` (`id_number`),
  KEY `idx_date` (`date`),
  KEY `idx_archived_at` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: attendance_record
DROP TABLE IF EXISTS `attendance_record`;
CREATE TABLE `attendance_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(20) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `day` varchar(10) DEFAULT NULL,
  `schedule` varchar(20) DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=263 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: attendance_record
INSERT INTO `attendance_record` VALUES ('262', '02200000001', '2025-10-21', 'Tuesday', 'ABM 12 (5:23 PM - 5:', '14:58:39', '15:19:24', 'Present');


-- Table: auth_tokens
DROP TABLE IF EXISTS `auth_tokens`;
CREATE TABLE `auth_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `user_type` enum('student','registrar','cashier','guidance','admin') NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  KEY `expires_at` (`expires_at`),
  KEY `idx_auth_tokens_user` (`user_id`,`user_type`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: auth_tokens
INSERT INTO `auth_tokens` VALUES ('1', '02200000001', 'student', '73330614905e959b078bd30e5ec227693c30503b2fce0040a252ad87bc64c068', '2025-10-22 08:30:20', '2025-10-21 09:30:20', '2025-10-21 09:30:20');
INSERT INTO `auth_tokens` VALUES ('2', '02200000001', 'student', 'efa86664577adaf5e9864c51b90e2d2830bad77bce7a8adf0294a56c93d3203b', '2025-10-22 08:30:21', '2025-10-21 09:30:21', '2025-10-21 09:30:21');
INSERT INTO `auth_tokens` VALUES ('3', '02200000001', 'student', '1210d9be70b888166d70bc7977ffebd582fab190521d8551940a75545cc84b34', '2025-10-22 08:30:22', '2025-10-21 09:30:22', '2025-10-21 09:30:22');
INSERT INTO `auth_tokens` VALUES ('4', '02200000001', 'student', '2a59918a7cd2b9b48762f934d778dd32a7bd106d5c34a34289dcbc047bba724a', '2025-10-22 08:30:22', '2025-10-21 09:30:22', '2025-10-21 09:30:22');
INSERT INTO `auth_tokens` VALUES ('5', '02200000001', 'student', '739c4ad72b3cd4bf904164815c780e4875c93232653a90b76bf0c10d478a9294', '2025-10-22 08:34:57', '2025-10-21 09:37:30', '2025-10-21 09:34:57');


-- Table: class_schedules
DROP TABLE IF EXISTS `class_schedules`;
CREATE TABLE `class_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `days` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_section_name` (`section_name`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: class_schedules
INSERT INTO `class_schedules` VALUES ('39', 'ABM 12', '17:23:00', '05:23:00', 'Tuesday', '84', '2025-10-21 17:24:03', '2025-10-21 17:24:03');


-- Table: data_archives
DROP TABLE IF EXISTS `data_archives`;
CREATE TABLE `data_archives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `archive_type` enum('login_logs','attendance_records') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `record_count` int(11) NOT NULL,
  `archive_data` longtext NOT NULL,
  `archived_at` datetime DEFAULT current_timestamp(),
  `archived_by` varchar(100) DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_archive_type` (`archive_type`),
  KEY `idx_archived_at` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: day_schedules
DROP TABLE IF EXISTS `day_schedules`;
CREATE TABLE `day_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `day_name` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_schedule_day` (`schedule_id`,`day_name`),
  KEY `idx_day_schedules_schedule_id` (`schedule_id`),
  KEY `idx_day_schedules_day_name` (`day_name`),
  CONSTRAINT `day_schedules_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_day_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=152 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: deletion_log
DROP TABLE IF EXISTS `deletion_log`;
CREATE TABLE `deletion_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action_type` enum('soft_delete','restore','permanent_delete') NOT NULL,
  `record_id` varchar(50) NOT NULL,
  `record_table` varchar(50) NOT NULL,
  `performed_by` varchar(100) NOT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reason` text DEFAULT NULL,
  `record_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`record_data`)),
  PRIMARY KEY (`id`),
  KEY `idx_record` (`record_table`,`record_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: document_fees
DROP TABLE IF EXISTS `document_fees`;
CREATE TABLE `document_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_name` varchar(100) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_name` (`document_name`),
  KEY `idx_document_name` (`document_name`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: document_fees
INSERT INTO `document_fees` VALUES ('3', 'Form 137', '123.00', '1', '2025-10-21 15:45:21', '2025-10-21 15:45:21');


-- Table: document_requests
DROP TABLE IF EXISTS `document_requests`;
CREATE TABLE `document_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('Pending','Approved','Ready to Claim','Claimed','Declined') NOT NULL DEFAULT 'Pending',
  `date_requested` datetime DEFAULT current_timestamp(),
  `date_claimed` datetime DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: document_types
DROP TABLE IF EXISTS `document_types`;
CREATE TABLE `document_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_requestable` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_submittable` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: document_types
INSERT INTO `document_types` VALUES ('42', 'Form 137', '1', '2025-10-21 15:45:03', '0');


-- Table: employee_accounts
DROP TABLE IF EXISTS `employee_accounts`;
CREATE TABLE `employee_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('registrar','cashier','guidance','attendance','hr','teacher') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` varchar(100) DEFAULT NULL,
  `deleted_reason` text DEFAULT NULL,
  `must_change_password` tinyint(1) DEFAULT 1 COMMENT 'Force password change on first login (1=yes, 0=no)',
  `deletion_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_accounts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id_number`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: employee_accounts
INSERT INTO `employee_accounts` VALUES ('82', 'CCI2025-001', 'teacher', '$2y$10$shRaNKJqMF8ByBMiBe2.COzu30P4yAt3n2CrI90kJPU7lk0M84XJ.', 'teacher', '2025-10-21 14:47:09', NULL, NULL, NULL, '0', NULL);
INSERT INTO `employee_accounts` VALUES ('84', 'CCI2025-003', 'registrar', '$2y$10$qI2Xm5Rp/J6k.gHhrv08.exhx7V9v6XvReF1qtjococ/CoD2LJcxW', 'registrar', '2025-10-21 15:08:33', NULL, NULL, NULL, '0', NULL);
INSERT INTO `employee_accounts` VALUES ('85', 'CCI2025-004', 'cashier', '$2y$10$91230qR7VjItASeSQZFiL.XN23pgd6RCpqjLXsiutV8cKrltufDdu', 'cashier', '2025-10-21 15:12:09', NULL, NULL, NULL, '0', NULL);
INSERT INTO `employee_accounts` VALUES ('86', 'CCI2025-005', 'guidance', '$2y$10$FIYf6msLu9xqwHjCCIpeouMD6/cNtwtOVdr7NQZuK2CxDHfzQhq6q', 'guidance', '2025-10-21 15:14:44', NULL, NULL, NULL, '0', NULL);
INSERT INTO `employee_accounts` VALUES ('87', 'CCI2025-006', 'attendance', '$2y$10$ky9CyjAQ5elCvZH7N2eNceARkH3FOBs6kkDghkVRmww43Cgi.My5C', 'attendance', '2025-10-21 15:17:01', NULL, NULL, NULL, '0', NULL);


-- Table: employee_day_schedules
DROP TABLE IF EXISTS `employee_day_schedules`;
CREATE TABLE `employee_day_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `day_name` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_schedule_day` (`schedule_id`,`day_name`),
  CONSTRAINT `employee_day_schedules_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `employee_work_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;


-- Table: employee_schedules
DROP TABLE IF EXISTS `employee_schedules`;
CREATE TABLE `employee_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_emp_sched_employee` (`employee_id`),
  KEY `idx_emp_sched_schedule` (`schedule_id`),
  CONSTRAINT `fk_emp_sched_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id_number`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_emp_sched_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `employee_work_schedules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: employee_schedules
INSERT INTO `employee_schedules` VALUES ('54', 'CCI2025-001', '33', '0', '2025-10-21 17:30:27', '2025-10-21 17:30:27');


-- Table: employee_work_day_schedules
DROP TABLE IF EXISTS `employee_work_day_schedules`;
CREATE TABLE `employee_work_day_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `day_name` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_emp_work_day_sched_schedule` (`schedule_id`),
  KEY `idx_emp_work_day_sched_day` (`day_name`),
  CONSTRAINT `fk_emp_work_day_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `employee_work_schedules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: employee_work_schedules
DROP TABLE IF EXISTS `employee_work_schedules`;
CREATE TABLE `employee_work_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_name` varchar(150) NOT NULL,
  `start_time` time NOT NULL DEFAULT '00:00:00',
  `end_time` time NOT NULL DEFAULT '23:59:59',
  `days` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: employee_work_schedules
INSERT INTO `employee_work_schedules` VALUES ('33', 'ABM 12', '17:30:00', '05:30:00', 'Tuesday', '0', '2025-10-21 17:30:27');


-- Table: employees
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `position` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `hire_date` date NOT NULL DEFAULT '2024-01-01',
  `rfid_uid` varchar(20) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` varchar(255) DEFAULT NULL,
  `deleted_reason` text DEFAULT NULL,
  `archive_scheduled` tinyint(1) DEFAULT 0,
  `archive_scheduled_by` varchar(100) DEFAULT NULL,
  `archive_scheduled_at` timestamp NULL DEFAULT NULL,
  `deletion_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_number` (`id_number`),
  UNIQUE KEY `id_number_2` (`id_number`),
  UNIQUE KEY `id_number_3` (`id_number`),
  UNIQUE KEY `id_number_4` (`id_number`),
  UNIQUE KEY `id_number_5` (`id_number`),
  UNIQUE KEY `id_number_6` (`id_number`),
  UNIQUE KEY `id_number_7` (`id_number`),
  UNIQUE KEY `id_number_8` (`id_number`),
  UNIQUE KEY `id_number_9` (`id_number`),
  UNIQUE KEY `id_number_10` (`id_number`),
  UNIQUE KEY `id_number_11` (`id_number`),
  UNIQUE KEY `id_number_12` (`id_number`),
  UNIQUE KEY `id_number_13` (`id_number`),
  UNIQUE KEY `id_number_14` (`id_number`),
  UNIQUE KEY `id_number_15` (`id_number`),
  UNIQUE KEY `id_number_16` (`id_number`),
  UNIQUE KEY `id_number_17` (`id_number`),
  UNIQUE KEY `id_number_18` (`id_number`),
  UNIQUE KEY `id_number_19` (`id_number`),
  UNIQUE KEY `id_number_20` (`id_number`),
  UNIQUE KEY `id_number_21` (`id_number`),
  UNIQUE KEY `id_number_22` (`id_number`),
  UNIQUE KEY `id_number_23` (`id_number`),
  UNIQUE KEY `id_number_24` (`id_number`),
  UNIQUE KEY `id_number_25` (`id_number`),
  KEY `idx_employee_deleted_at` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: employees
INSERT INTO `employees` VALUES ('93', 'CCI2025-001', 'Gesterd', '', 'Go', 'SHS Teacher', 'Academic Affairs', 'Gesterd@gmail.com', '639813213213', 'blk88 lot6, university, sjdm, bulkacan', '2025-10-21 14:47:09', '2025-10-21', '0095105805', NULL, NULL, NULL, '0', NULL, NULL, NULL);
INSERT INTO `employees` VALUES ('95', 'CCI2025-003', 'Registrar', '', 'Admin', 'Registrar', 'Academic Affairs', 'registrar@gmail.com', '639123432342', 'gegahshgs, sdadsadsads, adadssdadas, asddas', '2025-10-21 15:08:33', '2025-10-21', NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL);
INSERT INTO `employees` VALUES ('96', 'CCI2025-004', 'Cashier', '', 'Admin', 'Cashier', 'Finance', 'cashier@gmail.com', '639438753487', 'blk 88, lot6, university, sjdm, bulacan', '2025-10-21 15:12:09', '2025-10-21', NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL);
INSERT INTO `employees` VALUES ('97', 'CCI2025-005', 'Guidance', '', 'Admin', 'Guidance', 'Student Affairs', 'guidance@gmail.com', '639123942342', 'bl8, lot6, sdjansd, sjdm, bulacan', '2025-10-21 15:14:44', '2025-10-21', NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL);
INSERT INTO `employees` VALUES ('98', 'CCI2025-006', 'Attendance', '', 'Security', 'Attendance', 'Student Affairs', 'addsa@gmail.com', '639317283173', 'adsdas, adsasdasd, adsdasads, asdasd', '2025-10-21 15:17:01', '2025-10-21', NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL);


-- Table: fee_types
DROP TABLE IF EXISTS `fee_types`;
CREATE TABLE `fee_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fee_name` varchar(100) NOT NULL,
  `default_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `fee_name` (`fee_name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: fee_types
INSERT INTO `fee_types` VALUES ('15', 'Uniform', '157.00', '1', '2025-10-21 15:37:48', '2025-10-21 15:37:48');


-- Table: grades_record
DROP TABLE IF EXISTS `grades_record`;
CREATE TABLE `grades_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(20) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `school_year_term` varchar(50) NOT NULL,
  `prelim` decimal(5,2) DEFAULT NULL,
  `midterm` decimal(5,2) DEFAULT NULL,
  `pre_finals` decimal(5,2) DEFAULT NULL,
  `finals` decimal(5,2) DEFAULT NULL,
  `teacher_name` varchar(100) DEFAULT NULL,
  `first_quarter` decimal(5,2) DEFAULT NULL,
  `second_quarter` decimal(5,2) DEFAULT NULL,
  `third_quarter` decimal(5,2) DEFAULT NULL,
  `fourth_quarter` decimal(5,2) DEFAULT NULL,
  `grading_system` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_subject_term` (`id_number`,`subject`,`school_year_term`),
  KEY `idx_grading_system` (`grading_system`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: guidance_records
DROP TABLE IF EXISTS `guidance_records`;
CREATE TABLE `guidance_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(20) DEFAULT NULL,
  `record_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=177 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: hr_activity_logs
DROP TABLE IF EXISTS `hr_activity_logs`;
CREATE TABLE `hr_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(20) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `activity_type` enum('login','logout','password_change','account_created','account_modified','permission_change','failed_login') NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: installment_schedule
DROP TABLE IF EXISTS `installment_schedule`;
CREATE TABLE `installment_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fee_assignment_id` int(11) NOT NULL,
  `installment_number` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `payment_date` timestamp NULL DEFAULT NULL,
  `status` enum('Pending','Paid','Overdue') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fee_assignment` (`fee_assignment_id`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `installment_schedule_ibfk_1` FOREIGN KEY (`fee_assignment_id`) REFERENCES `student_fee_assignments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: login_activity
DROP TABLE IF EXISTS `login_activity`;
CREATE TABLE `login_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` varchar(20) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `username` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `login_time` datetime NOT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `session_duration` int(11) DEFAULT NULL COMMENT 'Duration in seconds',
  PRIMARY KEY (`id`),
  KEY `idx_login_date` (`login_time`),
  KEY `idx_id_number` (`id_number`),
  KEY `idx_logout_time` (`logout_time`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB AUTO_INCREMENT=569 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: login_activity
INSERT INTO `login_activity` VALUES ('229', 'employee', 'HR001', 'hradmin', 'hr', '2025-10-11 23:26:10', 'rpqf5lt7ur8ckg936912ff2e62', '2025-10-20 19:17:23', NULL, '762673');
INSERT INTO `login_activity` VALUES ('230', 'employee', '645645645645', 'Registrar', 'registrar', '2025-10-11 23:30:09', 'rpqf5lt7ur8ckg936912ff2e62', '2025-10-20 20:05:13', NULL, '765304');
INSERT INTO `login_activity` VALUES ('231', 'employee', '645645645645', 'Registrar', 'registrar', '2025-10-11 23:53:38', 'u64i9980lc9svnq75o7vlrk4c5', '2025-10-20 19:30:45', NULL, '761827');
INSERT INTO `login_activity` VALUES ('232', 'employee', 'HR001', 'hradmin', 'hr', '2025-10-12 00:12:48', 'u64i9980lc9svnq75o7vlrk4c5', '2025-10-20 19:01:21', NULL, '758913');
INSERT INTO `login_activity` VALUES ('233', 'employee', '645645645645', 'Registrar', 'registrar', '2025-10-12 00:13:06', 'u64i9980lc9svnq75o7vlrk4c5', '2025-10-20 10:56:53', NULL, '729827');
INSERT INTO `login_activity` VALUES ('234', 'employee', 'HR001', 'hradmin', 'hr', '2025-10-12 00:50:52', 'u64i9980lc9svnq75o7vlrk4c5', '2025-10-20 17:48:29', NULL, '752257');
INSERT INTO `login_activity` VALUES ('235', 'employee', '345345345345', 'lance', 'teacher', '2025-10-12 00:51:55', 'u64i9980lc9svnq75o7vlrk4c5', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('236', 'employee', '645645645645', 'Registrar', 'registrar', '2025-10-12 00:54:16', 'u64i9980lc9svnq75o7vlrk4c5', '2025-10-20 10:09:11', NULL, '724495');
INSERT INTO `login_activity` VALUES ('237', 'employee', '645645645645', 'Registrar', 'registrar', '2025-10-12 07:25:42', 'l8mfnbgeg72fvg61s8mhq9db8p', '2025-10-20 07:35:54', NULL, '691812');
INSERT INTO `login_activity` VALUES ('238', 'employee', '645645645645', 'Registrar', 'registrar', '2025-10-12 08:47:52', 'v2k47o6g9mne3b27u3u71ikoe2', '2025-10-20 07:30:26', NULL, '686554');
INSERT INTO `login_activity` VALUES ('239', 'employee', '645645645645', 'Registrar', 'registrar', '2025-10-12 14:09:39', '539hhahbejbp2fu8upqll01g3f', '2025-10-20 07:23:32', NULL, '666833');
INSERT INTO `login_activity` VALUES ('240', 'employee', '645645645645', 'Registrar', 'registrar', '2025-10-12 14:21:19', 'kdq4occsffkvlncfj208mimc0h', '2025-10-20 06:40:40', NULL, '663561');
INSERT INTO `login_activity` VALUES ('241', 'employee', '645645645645', 'Registrar', 'registrar', '2025-10-12 14:26:01', 'aevd06ead6aslaj46f4vrj887b', '2025-10-20 06:38:39', NULL, '663158');
INSERT INTO `login_activity` VALUES ('242', 'employee', 'HR001', 'hradmin', 'hr', '2025-10-12 15:17:17', 'aevd06ead6aslaj46f4vrj887b', '2025-10-20 14:06:42', NULL, '686965');
INSERT INTO `login_activity` VALUES ('243', 'employee', '645645645645', 'Registrar', 'registrar', '2025-10-12 15:29:54', 'aevd06ead6aslaj46f4vrj887b', '2025-10-20 06:38:30', NULL, '659316');
INSERT INTO `login_activity` VALUES ('244', 'employee', 'HR001', 'hradmin', 'hr', '2025-10-12 15:34:05', 'aevd06ead6aslaj46f4vrj887b', '2025-10-20 13:53:39', NULL, '685174');
INSERT INTO `login_activity` VALUES ('245', 'employee', '645645645645', 'Registrar', 'registrar', '2025-10-12 15:38:41', 'ocovod9t3ndrnihnl4os43agq7', '2025-10-20 06:37:25', NULL, '658724');
INSERT INTO `login_activity` VALUES ('246', 'employee', 'CCI2025-001', 'registrar001muzon@employee.cci.edu.ph', 'registrar', '2025-10-12 21:58:28', 'ocovod9t3ndrnihnl4os43agq7', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('247', 'employee', 'CCI2025-001', 'registrar001muzon@employee.cci.edu.ph', 'registrar', '2025-10-12 22:24:49', 'ocovod9t3ndrnihnl4os43agq7', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('248', 'employee', 'CCI2025-0006', 'hr0006muzon@employee.cci.edu.ph', 'hr', '2025-10-12 22:51:02', 'nft0m0edocmnbvkjhgb62attmb', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('249', 'employee', 'CCI2025-006', 'hr006muzon@employee.cci.edu.ph', 'hr', '2025-10-13 08:20:06', 'm0dhfochjqv09gcn826hi6065o', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('250', 'employee', 'CCI2025-007', 'teacher007muzon@employee.cci.edu.ph', 'teacher', '2025-10-13 08:53:00', '2o10o8lckdli1hv63ptrhueh6a', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('251', 'employee', 'CCI2025-001', 'registrar001muzon@employee.cci.edu.ph', 'registrar', '2025-10-13 09:00:54', '2o10o8lckdli1hv63ptrhueh6a', '2025-10-20 06:38:39', NULL, '596265');
INSERT INTO `login_activity` VALUES ('252', 'student', '02200000001', 'gesterd', 'student', '2025-10-13 12:50:11', 'br4c7l9n72rkkef8srtv9j25bq', '2025-10-20 06:30:36', NULL, '582025');
INSERT INTO `login_activity` VALUES ('253', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-13 12:50:20', 'br4c7l9n72rkkef8srtv9j25bq', '2025-10-20 06:31:29', NULL, '582069');
INSERT INTO `login_activity` VALUES ('254', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-13 12:50:50', 'br4c7l9n72rkkef8srtv9j25bq', '2025-10-20 10:23:32', NULL, '595962');
INSERT INTO `login_activity` VALUES ('255', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-13 12:57:47', 'br4c7l9n72rkkef8srtv9j25bq', '2025-10-20 06:29:54', NULL, '581527');
INSERT INTO `login_activity` VALUES ('256', 'student', '02200000004', 'lance000004muzon@student.cci.edu.ph', 'student', '2025-10-13 13:00:48', 'utp3kjhbiqj549h72gifr2spgg', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('257', 'student', '02200000004', 'lance000004muzon@student.cci.edu.ph', 'student', '2025-10-13 13:01:28', 'utp3kjhbiqj549h72gifr2spgg', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('258', 'student', '02200000005', 'wew000005muzon@student.cci.edu.ph', 'student', '2025-10-13 13:11:59', 'utp3kjhbiqj549h72gifr2spgg', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('259', 'student', '02200000006', 'www000006muzon@student.cci.edu.ph', 'student', '2025-10-13 13:15:00', 'qs28cehk6g5r06asblucngoq16', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('260', 'student', '02200000006', 'www000006muzon@student.cci.edu.ph', 'student', '2025-10-13 13:19:15', 'qs28cehk6g5r06asblucngoq16', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('261', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-13 13:27:57', 'br4c7l9n72rkkef8srtv9j25bq', '2025-10-20 07:31:56', NULL, '583439');
INSERT INTO `login_activity` VALUES ('262', 'employee', 'CCI2025-010', 'gege010muzon@employee.cci.edu.ph', 'registrar', '2025-10-13 13:28:51', 'qs28cehk6g5r06asblucngoq16', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('263', 'employee', 'CCI2025-011', 'qwe011muzon@employee.cci.edu.ph', 'registrar', '2025-10-13 13:32:14', '0hu4vg0f9i46ucboooo75esgis', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('264', 'employee', 'CCI2025-011', 'qwe011muzon@employee.cci.edu.ph', 'registrar', '2025-10-13 13:32:36', '0hu4vg0f9i46ucboooo75esgis', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('265', 'employee', 'CCI2025-012', 'eee012muzon@employee.cci.edu.ph', 'cashier', '2025-10-13 13:33:29', '0hu4vg0f9i46ucboooo75esgis', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('266', 'employee', 'CCI2025-012', 'eee012muzon@employee.cci.edu.ph', 'cashier', '2025-10-13 13:33:52', '0hu4vg0f9i46ucboooo75esgis', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('267', 'employee', 'CCI2025-012', 'eee012muzon@employee.cci.edu.ph', 'cashier', '2025-10-13 13:34:01', '0hu4vg0f9i46ucboooo75esgis', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('268', 'employee', 'CCI2025-013', 'ttt013muzon@employee.cci.edu.ph', 'hr', '2025-10-13 13:37:54', 'br4c7l9n72rkkef8srtv9j25bq', '2025-10-19 00:53:11', NULL, '472517');
INSERT INTO `login_activity` VALUES ('269', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-13 21:26:42', '97r852bca9p9rpgr7lhte05st7', '2025-10-20 01:15:30', NULL, '532128');
INSERT INTO `login_activity` VALUES ('270', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-13 23:31:54', '6e2s8seo8h2f98fuous3cngdjo', '2025-10-20 01:06:20', NULL, '524066');
INSERT INTO `login_activity` VALUES ('271', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-13 23:57:37', '6e2s8seo8h2f98fuous3cngdjo', '2025-10-20 01:05:42', NULL, '522485');
INSERT INTO `login_activity` VALUES ('300', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-15 11:37:17', 'tqoppr5c06tvm7nft1feaa35ef', '2025-10-20 01:04:33', NULL, '394036');
INSERT INTO `login_activity` VALUES ('388', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-19 22:17:16', '4gra77keilrg37c8hv7hf1bu5b', '2025-10-20 00:53:44', NULL, '9388');
INSERT INTO `login_activity` VALUES ('389', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-19 22:17:32', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 01:00:05', NULL, '9753');
INSERT INTO `login_activity` VALUES ('390', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 00:49:02', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 00:53:44', NULL, '282');
INSERT INTO `login_activity` VALUES ('391', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-20 00:53:58', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 01:00:05', NULL, '367');
INSERT INTO `login_activity` VALUES ('392', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 01:00:12', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 01:04:33', NULL, '261');
INSERT INTO `login_activity` VALUES ('393', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-20 01:04:38', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 01:04:45', NULL, '7');
INSERT INTO `login_activity` VALUES ('394', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 01:04:50', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 01:05:42', NULL, '52');
INSERT INTO `login_activity` VALUES ('395', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-20 01:05:46', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 01:05:54', NULL, '8');
INSERT INTO `login_activity` VALUES ('396', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 01:06:06', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 01:06:20', NULL, '14');
INSERT INTO `login_activity` VALUES ('397', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-20 01:06:24', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 01:06:37', NULL, '13');
INSERT INTO `login_activity` VALUES ('398', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 01:06:41', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 01:15:30', NULL, '529');
INSERT INTO `login_activity` VALUES ('399', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-20 01:15:45', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 01:18:08', NULL, '143');
INSERT INTO `login_activity` VALUES ('400', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 01:18:13', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 06:29:38', NULL, '18685');
INSERT INTO `login_activity` VALUES ('401', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-20 01:18:51', 'p2sn15i2buv4bg6ic1q8sdt6n5', '2025-10-20 01:51:03', NULL, '1932');
INSERT INTO `login_activity` VALUES ('402', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 01:51:08', 'p2sn15i2buv4bg6ic1q8sdt6n5', '2025-10-20 06:29:38', NULL, '16710');
INSERT INTO `login_activity` VALUES ('403', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 06:29:45', 'p2sn15i2buv4bg6ic1q8sdt6n5', '2025-10-20 06:29:54', NULL, '9');
INSERT INTO `login_activity` VALUES ('404', 'student', '02200000001', 'gesterd', 'student', '2025-10-20 06:30:28', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 06:30:36', NULL, '8');
INSERT INTO `login_activity` VALUES ('405', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-20 06:30:47', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 06:30:52', NULL, '5');
INSERT INTO `login_activity` VALUES ('406', 'student', '02200000001', 'gesterd', 'student', '2025-10-20 06:31:08', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 06:31:13', NULL, '5');
INSERT INTO `login_activity` VALUES ('407', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 06:31:17', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 06:31:29', NULL, '12');
INSERT INTO `login_activity` VALUES ('408', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-20 06:31:35', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 06:37:40', NULL, '365');
INSERT INTO `login_activity` VALUES ('409', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 06:32:42', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 06:37:25', NULL, '283');
INSERT INTO `login_activity` VALUES ('410', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-20 06:37:35', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 06:37:40', NULL, '5');
INSERT INTO `login_activity` VALUES ('411', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 06:37:48', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 06:38:30', NULL, '42');
INSERT INTO `login_activity` VALUES ('412', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-20 06:38:48', 'p2sn15i2buv4bg6ic1q8sdt6n5', '2025-10-20 06:54:24', NULL, '936');
INSERT INTO `login_activity` VALUES ('413', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 06:40:30', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 06:40:40', NULL, '10');
INSERT INTO `login_activity` VALUES ('414', 'student', '02200000001', 'gesterd', 'student', '2025-10-20 06:40:46', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 06:40:53', NULL, '7');
INSERT INTO `login_activity` VALUES ('415', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 06:41:07', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 07:23:32', NULL, '2545');
INSERT INTO `login_activity` VALUES ('416', 'student', '02200000001', 'gesterd', 'student', '2025-10-20 06:54:34', 'p2sn15i2buv4bg6ic1q8sdt6n5', '2025-10-20 07:01:23', NULL, '409');
INSERT INTO `login_activity` VALUES ('417', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-20 07:01:33', 'p2sn15i2buv4bg6ic1q8sdt6n5', '2025-10-20 09:29:06', NULL, '8853');
INSERT INTO `login_activity` VALUES ('418', 'employee', 'SA-1', 'superadmin', 'superadmin', '2025-10-20 07:23:43', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 07:29:25', NULL, '342');
INSERT INTO `login_activity` VALUES ('419', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 07:29:34', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 07:30:26', NULL, '52');
INSERT INTO `login_activity` VALUES ('420', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 07:30:38', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 07:31:56', NULL, '78');
INSERT INTO `login_activity` VALUES ('421', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 07:32:00', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 07:35:54', NULL, '234');
INSERT INTO `login_activity` VALUES ('422', 'employee', 'SA-1', 'superadmin', 'superadmin', '2025-10-20 07:36:00', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 07:40:51', NULL, '291');
INSERT INTO `login_activity` VALUES ('423', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 07:38:14', 'b52su8ufgn8lm1hl05uilr6at8', '2025-10-20 07:51:48', NULL, '814');
INSERT INTO `login_activity` VALUES ('424', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 07:40:59', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 07:51:48', NULL, '649');
INSERT INTO `login_activity` VALUES ('425', 'employee', 'SA-1', 'superadmin', 'superadmin', '2025-10-20 07:51:57', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 10:08:53', NULL, '8216');
INSERT INTO `login_activity` VALUES ('426', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-20 08:46:06', 'gkk10k6jtkllc8ifcdl2f286lk', '2025-10-20 09:29:06', NULL, '2580');
INSERT INTO `login_activity` VALUES ('427', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-20 09:29:10', 'gkk10k6jtkllc8ifcdl2f286lk', '2025-10-20 09:52:50', NULL, '1420');
INSERT INTO `login_activity` VALUES ('428', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-20 10:07:41', 'gkk10k6jtkllc8ifcdl2f286lk', '2025-10-20 10:08:16', NULL, '35');
INSERT INTO `login_activity` VALUES ('429', 'employee', 'SA-1', 'superadmin', 'superadmin', '2025-10-20 10:08:33', 'gkk10k6jtkllc8ifcdl2f286lk', '2025-10-20 10:08:53', NULL, '20');
INSERT INTO `login_activity` VALUES ('430', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 10:09:06', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 10:09:11', NULL, '5');
INSERT INTO `login_activity` VALUES ('431', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 10:09:25', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 10:23:32', NULL, '847');
INSERT INTO `login_activity` VALUES ('432', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-20 10:09:36', 'gkk10k6jtkllc8ifcdl2f286lk', '2025-10-20 10:09:39', NULL, '3');
INSERT INTO `login_activity` VALUES ('433', 'employee', 'SA-1', 'superadmin', 'superadmin', '2025-10-20 10:09:44', 'gkk10k6jtkllc8ifcdl2f286lk', '2025-10-20 10:41:10', NULL, '1886');
INSERT INTO `login_activity` VALUES ('434', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 10:23:37', '6glf7012bfgdavqvg879dulq8c', '2025-10-20 10:53:46', NULL, '1809');
INSERT INTO `login_activity` VALUES ('435', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 10:41:18', 'gkk10k6jtkllc8ifcdl2f286lk', '2025-10-20 10:54:44', NULL, '806');
INSERT INTO `login_activity` VALUES ('436', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 10:51:00', '599138pgj7ubdmt1t1f2liverr', '2025-10-20 10:53:46', NULL, '166');
INSERT INTO `login_activity` VALUES ('437', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 10:53:51', '599138pgj7ubdmt1t1f2liverr', '2025-10-20 10:54:44', NULL, '53');
INSERT INTO `login_activity` VALUES ('438', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 10:54:49', '599138pgj7ubdmt1t1f2liverr', '2025-10-20 10:56:53', NULL, '124');
INSERT INTO `login_activity` VALUES ('439', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 10:57:00', 'ig53krsj03bgt1tgkr6ggu6do5', '2025-10-20 19:02:47', NULL, '29147');
INSERT INTO `login_activity` VALUES ('440', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 11:02:50', '599138pgj7ubdmt1t1f2liverr', '2025-10-20 18:59:17', NULL, '28587');
INSERT INTO `login_activity` VALUES ('441', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 11:10:37', 'h7ok760rfnhvir0t85r36gv53s', '2025-10-20 14:00:44', NULL, '10207');
INSERT INTO `login_activity` VALUES ('442', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 12:53:16', '90acotl1qmq8etqes3u041vpkm', '2025-10-20 13:53:39', NULL, '3623');
INSERT INTO `login_activity` VALUES ('443', 'employee', 'CCI2025-007', 'teacher', 'teacher', '2025-10-20 13:06:46', '5rb51rnuqqeeuettn1mrnrr445', '2025-10-20 18:37:02', NULL, '19816');
INSERT INTO `login_activity` VALUES ('444', 'student', '02200000001', 'gesterd', 'student', '2025-10-20 13:53:45', '90acotl1qmq8etqes3u041vpkm', '2025-10-20 13:56:09', NULL, '144');
INSERT INTO `login_activity` VALUES ('445', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 13:56:15', '90acotl1qmq8etqes3u041vpkm', '2025-10-20 14:00:44', NULL, '269');
INSERT INTO `login_activity` VALUES ('446', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-20 14:01:02', '90acotl1qmq8etqes3u041vpkm', '2025-10-20 14:03:44', NULL, '162');
INSERT INTO `login_activity` VALUES ('447', 'employee', 'CCI2025-003', 'guidance', 'guidance', '2025-10-20 14:03:49', '90acotl1qmq8etqes3u041vpkm', '2025-10-20 14:05:07', NULL, '78');
INSERT INTO `login_activity` VALUES ('448', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 14:05:24', '90acotl1qmq8etqes3u041vpkm', '2025-10-20 14:06:42', NULL, '78');
INSERT INTO `login_activity` VALUES ('449', 'employee', 'CCI2025-007', 'teacher', 'teacher', '2025-10-20 16:23:32', '2d4qu7487llfd1q7j1etk15li4', '2025-10-20 17:36:46', NULL, '4394');
INSERT INTO `login_activity` VALUES ('450', 'employee', 'CCI2025-007', 'teacher', 'teacher', '2025-10-20 17:29:10', 'o3rrb7g95jq1n4eeml7fomufl9', '2025-10-20 17:36:46', NULL, '456');
INSERT INTO `login_activity` VALUES ('451', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 17:36:55', 'o3rrb7g95jq1n4eeml7fomufl9', '2025-10-20 17:48:29', NULL, '694');
INSERT INTO `login_activity` VALUES ('452', 'employee', 'SA-1', 'superadmin', 'superadmin', '2025-10-20 17:37:53', 'rul7e834sqpjues49gefp8fh7r', '2025-10-21 09:49:51', NULL, '58318');
INSERT INTO `login_activity` VALUES ('453', 'employee', 'CCI2025-007', 'teacher', 'teacher', '2025-10-20 17:48:56', 'o3rrb7g95jq1n4eeml7fomufl9', '2025-10-20 18:37:02', NULL, '2886');
INSERT INTO `login_activity` VALUES ('454', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 17:54:38', '30pmi2f34isjo7rkvc417l1qai', '2025-10-20 19:01:21', NULL, '4003');
INSERT INTO `login_activity` VALUES ('455', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 18:37:07', 'o3rrb7g95jq1n4eeml7fomufl9', '2025-10-20 18:59:17', NULL, '1330');
INSERT INTO `login_activity` VALUES ('456', 'employee', 'CCI2025-007', 'teacher', 'teacher', '2025-10-20 19:00:05', 'o3rrb7g95jq1n4eeml7fomufl9', '2025-10-20 22:18:09', NULL, '11884');
INSERT INTO `login_activity` VALUES ('457', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 19:01:25', '30pmi2f34isjo7rkvc417l1qai', '2025-10-20 19:02:47', NULL, '82');
INSERT INTO `login_activity` VALUES ('458', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 19:03:15', '30pmi2f34isjo7rkvc417l1qai', '2025-10-20 19:17:23', NULL, '848');
INSERT INTO `login_activity` VALUES ('459', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 19:17:49', '30pmi2f34isjo7rkvc417l1qai', '2025-10-20 19:30:45', NULL, '776');
INSERT INTO `login_activity` VALUES ('460', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 19:30:49', '30pmi2f34isjo7rkvc417l1qai', '2025-10-20 19:31:06', NULL, '17');
INSERT INTO `login_activity` VALUES ('461', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 19:31:12', '30pmi2f34isjo7rkvc417l1qai', '2025-10-20 20:05:13', NULL, '2041');
INSERT INTO `login_activity` VALUES ('462', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 19:48:38', 'e1c7vfg1lnofnvhfbvttob6kon', '2025-10-20 20:26:41', NULL, '2283');
INSERT INTO `login_activity` VALUES ('463', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 20:05:16', '30pmi2f34isjo7rkvc417l1qai', '2025-10-20 20:26:41', NULL, '1285');
INSERT INTO `login_activity` VALUES ('464', 'student', '02200000001', 'gesterd', 'student', '2025-10-20 20:26:47', '30pmi2f34isjo7rkvc417l1qai', '2025-10-20 20:26:51', NULL, '4');
INSERT INTO `login_activity` VALUES ('465', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-20 20:27:00', '30pmi2f34isjo7rkvc417l1qai', '2025-10-20 20:27:03', NULL, '3');
INSERT INTO `login_activity` VALUES ('466', 'employee', 'CCI2025-003', 'guidance', 'guidance', '2025-10-20 20:27:08', '30pmi2f34isjo7rkvc417l1qai', '2025-10-20 20:27:10', NULL, '2');
INSERT INTO `login_activity` VALUES ('467', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-20 20:56:38', '30pmi2f34isjo7rkvc417l1qai', '2025-10-21 06:14:49', NULL, '33491');
INSERT INTO `login_activity` VALUES ('468', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-20 20:59:25', 'vs82mv4soishrc31q8dfp745v7', '2025-10-21 10:03:05', NULL, '47020');
INSERT INTO `login_activity` VALUES ('469', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-20 22:19:34', 'o3rrb7g95jq1n4eeml7fomufl9', '2025-10-21 10:01:18', NULL, '42104');
INSERT INTO `login_activity` VALUES ('470', 'student', '02200000001', 'gesterd', 'student', '2025-10-21 06:12:29', '498mhd26t2662vnu3dcdbt0hqh', '2025-10-21 06:12:39', NULL, '10');
INSERT INTO `login_activity` VALUES ('471', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 06:12:48', '498mhd26t2662vnu3dcdbt0hqh', '2025-10-21 06:14:49', NULL, '121');
INSERT INTO `login_activity` VALUES ('472', 'student', '02200000001', 'gesterd', 'student', '2025-10-21 06:14:52', '498mhd26t2662vnu3dcdbt0hqh', '2025-10-21 08:33:01', NULL, '8289');
INSERT INTO `login_activity` VALUES ('473', 'student', '02200000001', 'gesterd', 'student', '2025-10-21 06:50:32', 'a35alf50o46uhkea86gm486fs6', '2025-10-21 08:33:01', NULL, '6149');
INSERT INTO `login_activity` VALUES ('474', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 08:32:11', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 09:39:02', NULL, '4011');
INSERT INTO `login_activity` VALUES ('475', 'student', '02200000001', 'gesterd', 'student', '2025-10-21 08:33:05', 'a35alf50o46uhkea86gm486fs6', '2025-10-21 09:12:44', NULL, '2379');
INSERT INTO `login_activity` VALUES ('476', 'student', '02200000001', 'gesterd', 'student', '2025-10-21 09:12:51', 'a35alf50o46uhkea86gm486fs6', '2025-10-21 09:37:38', NULL, '1487');
INSERT INTO `login_activity` VALUES ('477', 'student', '02200000001', 'gesterd', 'student', '2025-10-21 09:34:57', '97nafebtavvhcpimppfjncds4k', '2025-10-21 09:37:38', NULL, '161');
INSERT INTO `login_activity` VALUES ('478', 'employee', 'SA-1', 'superadmin', 'superadmin', '2025-10-21 09:39:29', '97nafebtavvhcpimppfjncds4k', '2025-10-21 09:49:51', NULL, '622');
INSERT INTO `login_activity` VALUES ('479', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-21 09:39:39', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:01:18', NULL, '1299');
INSERT INTO `login_activity` VALUES ('480', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 09:46:38', 'ama77v5en2u5sgnrbovejve83d', '2025-10-21 09:46:46', NULL, '8');
INSERT INTO `login_activity` VALUES ('481', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 09:46:51', 'ama77v5en2u5sgnrbovejve83d', '2025-10-21 09:47:18', NULL, '27');
INSERT INTO `login_activity` VALUES ('482', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 09:47:22', 'ama77v5en2u5sgnrbovejve83d', '2025-10-21 10:07:57', NULL, '1235');
INSERT INTO `login_activity` VALUES ('483', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-21 09:50:07', '97nafebtavvhcpimppfjncds4k', '2025-10-21 10:02:16', NULL, '729');
INSERT INTO `login_activity` VALUES ('484', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-21 10:01:29', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:03:05', NULL, '96');
INSERT INTO `login_activity` VALUES ('485', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 10:02:20', '97nafebtavvhcpimppfjncds4k', '2025-10-21 10:03:58', NULL, '98');
INSERT INTO `login_activity` VALUES ('486', 'employee', 'CCI2025-003', 'guidance', 'guidance', '2025-10-21 10:03:21', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:03:47', NULL, '26');
INSERT INTO `login_activity` VALUES ('487', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 10:03:51', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:03:58', NULL, '7');
INSERT INTO `login_activity` VALUES ('488', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-21 10:04:02', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:04:58', NULL, '56');
INSERT INTO `login_activity` VALUES ('489', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 10:05:05', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:07:57', NULL, '172');
INSERT INTO `login_activity` VALUES ('490', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 10:09:16', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:11:08', NULL, '112');
INSERT INTO `login_activity` VALUES ('491', 'employee', 'CCI2025-003', 'guidance', 'guidance', '2025-10-21 10:11:13', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:11:21', NULL, '8');
INSERT INTO `login_activity` VALUES ('492', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-21 10:11:24', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:11:46', NULL, '22');
INSERT INTO `login_activity` VALUES ('493', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 10:11:54', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:18:36', NULL, '402');
INSERT INTO `login_activity` VALUES ('494', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 10:19:56', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:20:27', NULL, '31');
INSERT INTO `login_activity` VALUES ('495', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 10:20:32', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:28:04', NULL, '452');
INSERT INTO `login_activity` VALUES ('496', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-21 10:28:08', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:28:55', NULL, '47');
INSERT INTO `login_activity` VALUES ('497', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-21 10:29:00', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:29:03', NULL, '3');
INSERT INTO `login_activity` VALUES ('498', 'student', '02200000001', 'gesterd', 'student', '2025-10-21 10:29:07', 'mc9equ7vjaurih4unpun3mq6ol', '2025-10-21 10:29:13', NULL, '6');
INSERT INTO `login_activity` VALUES ('499', 'student', '02200000001', 'gesterd', 'student', '2025-10-21 11:21:51', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 11:22:05', NULL, '14');
INSERT INTO `login_activity` VALUES ('500', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 11:22:12', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 11:22:34', NULL, '22');
INSERT INTO `login_activity` VALUES ('501', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-21 11:22:48', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 11:22:51', NULL, '3');
INSERT INTO `login_activity` VALUES ('502', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-21 11:22:56', 'atr4tua8c3tk2jcrq28hk1gs7e', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('503', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-21 11:24:39', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 15:19:15', NULL, '14076');
INSERT INTO `login_activity` VALUES ('504', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-21 11:25:27', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 15:07:13', NULL, '13306');
INSERT INTO `login_activity` VALUES ('505', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-21 11:27:11', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 11:31:41', NULL, '270');
INSERT INTO `login_activity` VALUES ('506', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-21 11:28:37', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 11:31:41', NULL, '184');
INSERT INTO `login_activity` VALUES ('507', 'employee', 'CCI2025-007', 'teacher', 'teacher', '2025-10-21 11:31:52', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 11:32:35', NULL, '43');
INSERT INTO `login_activity` VALUES ('508', 'employee', 'CCI2025-007', 'teacher', 'teacher', '2025-10-21 11:32:40', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 11:33:06', NULL, '26');
INSERT INTO `login_activity` VALUES ('509', 'employee', 'SA-1', 'superadmin', 'superadmin', '2025-10-21 11:33:23', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 11:33:32', NULL, '9');
INSERT INTO `login_activity` VALUES ('510', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-21 11:33:38', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 11:35:41', NULL, '123');
INSERT INTO `login_activity` VALUES ('511', 'employee', 'CCI2025-007', 'teacher', 'teacher', '2025-10-21 11:35:59', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 11:36:35', NULL, '36');
INSERT INTO `login_activity` VALUES ('512', 'employee', 'CCI2025-007', 'teacher', 'teacher', '2025-10-21 11:36:39', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 11:36:41', NULL, '2');
INSERT INTO `login_activity` VALUES ('513', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-21 11:38:46', 'atr4tua8c3tk2jcrq28hk1gs7e', '2025-10-21 11:47:36', NULL, '530');
INSERT INTO `login_activity` VALUES ('514', 'student', '02200000001', 'gesterd', 'student', '2025-10-21 12:35:38', '9k4g7mbsm0sdgjn6au9pu1tbah', '2025-10-21 12:37:25', NULL, '107');
INSERT INTO `login_activity` VALUES ('515', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 12:37:55', '9k4g7mbsm0sdgjn6au9pu1tbah', '2025-10-21 15:12:23', NULL, '9268');
INSERT INTO `login_activity` VALUES ('516', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 12:45:36', '9k4g7mbsm0sdgjn6au9pu1tbah', '2025-10-21 13:26:19', NULL, '2443');
INSERT INTO `login_activity` VALUES ('517', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 13:26:07', 'thu413q50jv1ga9bop1rf0iqgg', '2025-10-21 13:26:19', NULL, '12');
INSERT INTO `login_activity` VALUES ('518', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-21 13:28:56', 'thu413q50jv1ga9bop1rf0iqgg', '2025-10-21 13:29:07', NULL, '11');
INSERT INTO `login_activity` VALUES ('519', 'student', '02200000001', 'gesterd', 'student', '2025-10-21 13:29:15', 'pmt8879bl9u6svah18f8bh0dqn', '2025-10-21 13:33:06', NULL, '231');
INSERT INTO `login_activity` VALUES ('520', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 13:31:05', 'thu413q50jv1ga9bop1rf0iqgg', '2025-10-21 14:42:15', NULL, '4270');
INSERT INTO `login_activity` VALUES ('521', 'student', '02200000001', 'gesterd', 'student', '2025-10-21 13:32:07', 'citnfjcdv28u3mmo4qrgn23ujb', '2025-10-21 13:33:05', NULL, '58');
INSERT INTO `login_activity` VALUES ('522', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 13:32:34', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 13:46:15', NULL, '821');
INSERT INTO `login_activity` VALUES ('523', 'student', '02200000001', 'gesterd', 'student', '2025-10-21 13:33:09', 'citnfjcdv28u3mmo4qrgn23ujb', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('524', 'employee', 'CCI2025-002', 'cashier', 'cashier', '2025-10-21 13:43:12', 'skabnikqrn90ft8okhduukd1n2', '2025-10-21 13:43:41', NULL, '29');
INSERT INTO `login_activity` VALUES ('525', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 13:44:30', 'skabnikqrn90ft8okhduukd1n2', '2025-10-21 13:46:15', NULL, '105');
INSERT INTO `login_activity` VALUES ('526', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 13:51:45', 'skabnikqrn90ft8okhduukd1n2', '2025-10-21 14:06:05', NULL, '860');
INSERT INTO `login_activity` VALUES ('527', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 14:01:16', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 14:06:05', NULL, '289');
INSERT INTO `login_activity` VALUES ('528', 'employee', 'CCI2025-001', 'registrar', 'registrar', '2025-10-21 14:06:11', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 14:42:15', NULL, '2164');
INSERT INTO `login_activity` VALUES ('529', 'employee', 'CCI2025-005', 'attendance', 'attendance', '2025-10-21 14:08:06', 'dosfvvgopa9kcivi2rng66jhbd', '2025-10-21 15:04:41', NULL, '3395');
INSERT INTO `login_activity` VALUES ('530', 'student', '02200000001', 'dejesus000001muzon@student.cci.edu.ph', 'student', '2025-10-21 14:14:44', 'o0ongno3708p7420o00t8vlnn5', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('531', 'student', '02200000002', 'sunico000002muzon@student.cci.edu.ph', 'student', '2025-10-21 14:17:20', '5ng9irh8oc4h8s46ukqkgng04g', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('532', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-21 14:31:41', '646i04khsl4adh3al32cga5a1k', '2025-10-21 14:58:49', NULL, '1628');
INSERT INTO `login_activity` VALUES ('533', 'employee', 'CCI2025-005', 'attendance', 'attendance', '2025-10-21 14:39:00', '646i04khsl4adh3al32cga5a1k', '2025-10-21 15:04:41', NULL, '1541');
INSERT INTO `login_activity` VALUES ('534', 'employee', 'CCI2025-006', 'hradmin', 'hr', '2025-10-21 14:42:20', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 14:58:49', NULL, '989');
INSERT INTO `login_activity` VALUES ('535', 'employee', 'SA-1', 'superadmin', 'superadmin', '2025-10-21 15:02:16', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 15:08:41', NULL, '385');
INSERT INTO `login_activity` VALUES ('536', 'employee', 'CCI2025-002', 'admin002muzon@employee.cci.edu.ph', 'hr', '2025-10-21 15:04:58', '646i04khsl4adh3al32cga5a1k', '2025-10-21 15:07:02', NULL, '124');
INSERT INTO `login_activity` VALUES ('537', 'employee', 'CCI2025-002', 'hradmin', 'hr', '2025-10-21 15:07:07', '646i04khsl4adh3al32cga5a1k', '2025-10-21 15:07:13', NULL, '6');
INSERT INTO `login_activity` VALUES ('538', 'employee', 'CCI2025-002', 'hradmin', 'hr', '2025-10-21 15:07:30', '646i04khsl4adh3al32cga5a1k', '2025-10-21 15:19:15', NULL, '705');
INSERT INTO `login_activity` VALUES ('539', 'employee', 'CCI2025-003', 'registrar', 'registrar', '2025-10-21 15:10:49', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 15:12:23', NULL, '94');
INSERT INTO `login_activity` VALUES ('540', 'employee', 'CCI2025-004', 'admin004muzon@employee.cci.edu.ph', 'cashier', '2025-10-21 15:12:37', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 15:14:51', NULL, '134');
INSERT INTO `login_activity` VALUES ('541', 'employee', 'CCI2025-005', 'admin005muzon@employee.cci.edu.ph', 'guidance', '2025-10-21 15:15:09', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 15:15:22', NULL, '13');
INSERT INTO `login_activity` VALUES ('542', 'employee', 'CCI2025-006', 'security006muzon@employee.cci.edu.ph', 'attendance', '2025-10-21 15:17:09', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 15:23:58', NULL, '409');
INSERT INTO `login_activity` VALUES ('543', 'employee', 'CCI2025-003', 'registrar', 'registrar', '2025-10-21 15:19:20', '646i04khsl4adh3al32cga5a1k', '2025-10-21 15:23:25', NULL, '245');
INSERT INTO `login_activity` VALUES ('544', 'employee', 'CCI2025-004', 'cashier', 'cashier', '2025-10-21 15:23:33', '646i04khsl4adh3al32cga5a1k', '2025-10-21 15:29:08', NULL, '335');
INSERT INTO `login_activity` VALUES ('545', 'employee', 'CCI2025-003', 'registrar', 'registrar', '2025-10-21 15:24:06', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 15:26:34', NULL, '148');
INSERT INTO `login_activity` VALUES ('546', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-21 15:26:52', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 15:45:48', NULL, '1136');
INSERT INTO `login_activity` VALUES ('547', 'employee', 'SA-1', 'superadmin', 'superadmin', '2025-10-21 15:29:25', '646i04khsl4adh3al32cga5a1k', '2025-10-21 15:44:24', NULL, '899');
INSERT INTO `login_activity` VALUES ('548', 'employee', 'CCI2025-003', 'registrar', 'registrar', '2025-10-21 15:44:49', '646i04khsl4adh3al32cga5a1k', '2025-10-21 15:47:39', NULL, '170');
INSERT INTO `login_activity` VALUES ('549', 'employee', 'SA-1', 'superadmin', 'superadmin', '2025-10-21 15:45:55', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 15:46:14', NULL, '19');
INSERT INTO `login_activity` VALUES ('550', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-21 15:46:21', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 15:46:43', NULL, '22');
INSERT INTO `login_activity` VALUES ('551', 'employee', 'CCI2025-005', 'guidance', 'guidance', '2025-10-21 15:49:20', '646i04khsl4adh3al32cga5a1k', '2025-10-21 15:50:02', NULL, '42');
INSERT INTO `login_activity` VALUES ('552', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-21 15:53:15', 'hnjhr65dlvsjj770pb0ob9ipvi', '2025-10-21 15:59:53', NULL, '398');
INSERT INTO `login_activity` VALUES ('553', 'employee', 'SA-1', 'superadmin', 'superadmin', '2025-10-21 16:00:15', 'hnjhr65dlvsjj770pb0ob9ipvi', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('554', 'employee', 'CCI2025-004', 'cashier', 'cashier', '2025-10-21 17:01:42', '7u5d7qc8ibfjhipro8s8k5nq6s', '2025-10-21 17:01:47', NULL, '5');
INSERT INTO `login_activity` VALUES ('555', 'employee', 'CCI2025-001', 'teacher', 'teacher', '2025-10-21 17:13:15', '7u5d7qc8ibfjhipro8s8k5nq6s', '2025-10-21 17:18:49', NULL, '334');
INSERT INTO `login_activity` VALUES ('556', 'employee', 'CCI2025-001', 'teacher', 'teacher', '2025-10-21 17:14:43', '7u5d7qc8ibfjhipro8s8k5nq6s', '2025-10-21 17:18:49', NULL, '246');
INSERT INTO `login_activity` VALUES ('557', 'employee', 'CCI2025-002', 'hradmin', 'hr', '2025-10-21 17:15:25', '646i04khsl4adh3al32cga5a1k', '2025-10-21 17:32:27', NULL, '1022');
INSERT INTO `login_activity` VALUES ('558', 'employee', 'CCI2025-003', 'registrar', 'registrar', '2025-10-21 17:19:04', '7u5d7qc8ibfjhipro8s8k5nq6s', '2025-10-21 17:28:28', NULL, '564');
INSERT INTO `login_activity` VALUES ('559', 'student', '02200000002', 'sunico000002muzon@student.cci.edu.ph', 'student', '2025-10-21 17:20:53', 's4tl16km7njdkqljdcmag6i4b4', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('560', 'student', '02200000001', 'liam', 'student', '2025-10-21 17:23:22', 'al66aomlflju9k99umgqp0sdv6', '2025-10-21 18:23:48', NULL, '3626');
INSERT INTO `login_activity` VALUES ('561', 'employee', 'CCI2025-001', 'teacher', 'teacher', '2025-10-21 17:25:29', '646i04khsl4adh3al32cga5a1k', '2025-10-21 17:32:45', NULL, '436');
INSERT INTO `login_activity` VALUES ('562', 'employee', 'CCI2025-002', 'hradmin', 'hr', '2025-10-21 17:28:36', '7u5d7qc8ibfjhipro8s8k5nq6s', '2025-10-21 17:32:27', NULL, '231');
INSERT INTO `login_activity` VALUES ('563', 'student', '02200000002', 'sunico000002muzon@student.cci.edu.ph', 'student', '2025-10-21 17:45:28', 'vrhsr73cb7a86brt624p2o2kil', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('564', 'student', '02200000001', 'liam', 'student', '2025-10-21 17:52:19', 'al66aomlflju9k99umgqp0sdv6', '2025-10-21 18:23:48', NULL, '1889');
INSERT INTO `login_activity` VALUES ('565', 'employee', 'SA-1', 'superadmin', 'superadmin', '2025-10-21 18:09:02', '7u5d7qc8ibfjhipro8s8k5nq6s', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('566', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-21 18:17:14', '646i04khsl4adh3al32cga5a1k', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('567', 'employee', 'OWN-1', 'owner', 'owner', '2025-10-21 18:24:45', '646i04khsl4adh3al32cga5a1k', NULL, NULL, NULL);
INSERT INTO `login_activity` VALUES ('568', 'student', '02200000001', 'liam', 'student', '2025-10-21 18:25:00', 'al66aomlflju9k99umgqp0sdv6', NULL, NULL, NULL);


-- Table: login_logs_archive
DROP TABLE IF EXISTS `login_logs_archive`;
CREATE TABLE `login_logs_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `login_time` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `user_type` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` varchar(100) DEFAULT NULL,
  `archived_reason` varchar(255) DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `session_duration` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_login_time` (`login_time`),
  KEY `idx_archived_at` (`archived_at`)
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: login_logs_archive
INSERT INTO `login_logs_archive` VALUES ('1', '312', 'registrar', '2025-10-17 12:01:52', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 12:02:12', 'SuperAdmin', 'Archived login logs from 2025-10-17 to 2025-10-17', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('2', '313', 'hradmin', '2025-10-17 12:12:07', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('3', '314', 'hradmin', '2025-10-17 12:16:55', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 12:55:51', NULL, '2336');
INSERT INTO `login_logs_archive` VALUES ('4', '315', 'gesterd', '2025-10-17 12:18:50', 'N/A', 'N/A', 'student', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 12:48:50', NULL, '1800');
INSERT INTO `login_logs_archive` VALUES ('5', '316', 'registrar', '2025-10-17 12:19:22', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 12:55:31', NULL, '2169');
INSERT INTO `login_logs_archive` VALUES ('6', '317', 'cashier', '2025-10-17 12:21:11', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 12:54:16', NULL, '1985');
INSERT INTO `login_logs_archive` VALUES ('7', '318', 'guidance', '2025-10-17 12:27:22', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 12:55:02', NULL, '1660');
INSERT INTO `login_logs_archive` VALUES ('8', '319', 'gesterd', '2025-10-17 12:48:44', 'N/A', 'N/A', 'student', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 12:48:50', NULL, '6');
INSERT INTO `login_logs_archive` VALUES ('9', '320', 'cashier', '2025-10-17 12:54:10', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 12:54:16', NULL, '6');
INSERT INTO `login_logs_archive` VALUES ('10', '321', 'gesterd', '2025-10-17 12:54:31', 'N/A', 'N/A', 'student', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 12:54:34', NULL, '3');
INSERT INTO `login_logs_archive` VALUES ('11', '322', 'guidance', '2025-10-17 12:54:57', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 12:55:02', NULL, '5');
INSERT INTO `login_logs_archive` VALUES ('12', '323', 'registrar', '2025-10-17 12:55:12', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 12:55:31', NULL, '19');
INSERT INTO `login_logs_archive` VALUES ('13', '324', 'hradmin', '2025-10-17 12:55:47', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 12:55:51', NULL, '4');
INSERT INTO `login_logs_archive` VALUES ('14', '325', 'registrar', '2025-10-17 12:58:15', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 13:00:01', NULL, '106');
INSERT INTO `login_logs_archive` VALUES ('15', '326', 'registrar', '2025-10-17 13:11:56', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 13:12:25', NULL, '29');
INSERT INTO `login_logs_archive` VALUES ('16', '327', 'go000001muzon@parent.cci.edu.ph', '2025-10-17 13:12:38', 'N/A', 'N/A', 'parent', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-17 13:12:52', NULL, '14');
INSERT INTO `login_logs_archive` VALUES ('17', '328', 'registrar', '2025-10-17 13:57:25', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('18', '329', 'go000001muzon@parent.cci.edu.ph', '2025-10-17 13:57:36', 'N/A', 'N/A', 'parent', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('19', '330', 'superadmin', '2025-10-17 19:07:48', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('20', '331', 'owner', '2025-10-17 19:08:01', 'N/A', 'N/A', 'employee', 'success', '2025-10-17 20:37:48', 'SA-1', 'Archived login logs from 2025-10-17 to 2025-10-17', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('21', '350', 'ttt013muzon@employee.cci.edu.ph', '2025-10-19 00:52:57', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', '2025-10-19 00:53:11', NULL, '14');
INSERT INTO `login_logs_archive` VALUES ('22', '351', 'owner', '2025-10-19 00:53:25', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', '2025-10-19 01:25:23', NULL, '1918');
INSERT INTO `login_logs_archive` VALUES ('23', '352', 'registrar', '2025-10-19 01:22:20', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('24', '353', 'owner', '2025-10-19 01:25:02', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', '2025-10-19 01:25:23', NULL, '21');
INSERT INTO `login_logs_archive` VALUES ('25', '354', 'superadmin', '2025-10-19 01:25:28', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('26', '355', 'registrar', '2025-10-19 02:11:45', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('27', '356', 'superadmin', '2025-10-19 09:04:11', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('28', '357', 'owner', '2025-10-19 09:04:18', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('29', '358', 'superadmin', '2025-10-19 09:11:18', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('30', '359', 'superadmin', '2025-10-19 09:16:34', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('31', '360', 'registrar', '2025-10-19 10:08:16', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', '2025-10-19 15:25:03', NULL, '19007');
INSERT INTO `login_logs_archive` VALUES ('32', '361', 'superadmin', '2025-10-19 10:09:14', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('33', '362', 'superadmin', '2025-10-19 11:32:40', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', '2025-10-19 15:54:39', NULL, '15719');
INSERT INTO `login_logs_archive` VALUES ('34', '363', 'registrar', '2025-10-19 11:36:07', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', '2025-10-19 15:25:03', NULL, '13736');
INSERT INTO `login_logs_archive` VALUES ('35', '364', 'hradmin', '2025-10-19 15:25:18', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', '2025-10-19 15:25:54', NULL, '36');
INSERT INTO `login_logs_archive` VALUES ('36', '365', 'superadmin', '2025-10-19 15:54:32', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', '2025-10-19 15:54:39', NULL, '7');
INSERT INTO `login_logs_archive` VALUES ('37', '366', 'owner', '2025-10-19 15:54:43', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('38', '367', 'owner', '2025-10-19 15:58:34', '0', 'N/A', 'employee', 'success', '2025-10-19 19:01:04', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('39', '334', 'superadmin', '2025-10-18 21:03:03', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('40', '335', 'owner', '2025-10-18 21:03:09', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-18 22:20:12', NULL, '4623');
INSERT INTO `login_logs_archive` VALUES ('41', '336', 'superadmin', '2025-10-18 21:04:17', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-18 23:22:01', NULL, '8264');
INSERT INTO `login_logs_archive` VALUES ('42', '337', 'owner', '2025-10-18 21:17:47', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-18 21:31:22', NULL, '815');
INSERT INTO `login_logs_archive` VALUES ('43', '338', 'owner', '2025-10-18 21:31:19', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-18 21:31:22', NULL, '3');
INSERT INTO `login_logs_archive` VALUES ('44', '339', 'superadmin', '2025-10-18 21:31:27', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-18 23:18:00', NULL, '6393');
INSERT INTO `login_logs_archive` VALUES ('45', '340', 'registrar', '2025-10-18 22:20:24', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-18 22:20:37', NULL, '13');
INSERT INTO `login_logs_archive` VALUES ('46', '341', 'hradmin', '2025-10-18 22:20:48', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-18 22:23:14', NULL, '146');
INSERT INTO `login_logs_archive` VALUES ('47', '342', 'owner', '2025-10-18 22:23:24', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-18 22:29:56', NULL, '392');
INSERT INTO `login_logs_archive` VALUES ('48', '343', 'registrar', '2025-10-18 22:30:09', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-18 22:30:21', NULL, '12');
INSERT INTO `login_logs_archive` VALUES ('49', '344', 'hradmin', '2025-10-18 22:59:03', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-18 23:37:45', NULL, '2322');
INSERT INTO `login_logs_archive` VALUES ('50', '345', 'owner', '2025-10-18 23:09:45', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-18 23:09:52', NULL, '7');
INSERT INTO `login_logs_archive` VALUES ('51', '346', 'superadmin', '2025-10-18 23:10:02', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-18 23:18:00', NULL, '478');
INSERT INTO `login_logs_archive` VALUES ('52', '347', 'superadmin', '2025-10-18 23:18:07', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-18 23:22:01', NULL, '234');
INSERT INTO `login_logs_archive` VALUES ('53', '348', 'superadmin', '2025-10-18 23:22:06', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('54', '349', 'owner', '2025-10-18 23:37:53', '0', 'N/A', 'employee', 'success', '2025-10-19 19:06:34', 'School Owner', 'Archived login logs from 2025-10-18 to 2025-10-18', '2025-10-19 00:52:17', NULL, '4464');
INSERT INTO `login_logs_archive` VALUES ('55', '332', 'owner', '2025-10-17 22:16:40', '0', 'N/A', 'employee', 'success', '2025-10-19 19:07:16', 'School Owner', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-18 22:20:12', NULL, '86612');
INSERT INTO `login_logs_archive` VALUES ('56', '333', 'registrar', '2025-10-17 22:17:25', '0', 'N/A', 'employee', 'success', '2025-10-19 19:07:16', 'School Owner', 'Archived login logs from 2025-10-17 to 2025-10-17', '2025-10-18 22:20:37', NULL, '86592');
INSERT INTO `login_logs_archive` VALUES ('57', '301', 'registrar', '2025-10-16 11:59:14', '0', 'N/A', 'employee', 'success', '2025-10-19 19:33:33', 'School Owner', 'Archived login logs from 2025-10-16 to 2025-10-16', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('58', '302', 'hradmin', '2025-10-16 11:59:33', '0', 'N/A', 'employee', 'success', '2025-10-19 19:33:33', 'School Owner', 'Archived login logs from 2025-10-16 to 2025-10-16', '2025-10-18 22:23:14', NULL, '210221');
INSERT INTO `login_logs_archive` VALUES ('59', '303', 'registrar', '2025-10-16 13:47:07', '0', 'N/A', 'employee', 'success', '2025-10-19 19:33:33', 'School Owner', 'Archived login logs from 2025-10-16 to 2025-10-16', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('60', '304', 'registrar', '2025-10-16 14:11:22', '0', 'N/A', 'employee', 'success', '2025-10-19 19:33:33', 'School Owner', 'Archived login logs from 2025-10-16 to 2025-10-16', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('61', '305', 'gesterd', '2025-10-16 14:24:08', '0', 'N/A', 'student', 'success', '2025-10-19 19:33:33', 'School Owner', 'Archived login logs from 2025-10-16 to 2025-10-16', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('62', '306', 'gesterd', '2025-10-16 14:59:31', '0', 'N/A', 'student', 'success', '2025-10-19 19:33:33', 'School Owner', 'Archived login logs from 2025-10-16 to 2025-10-16', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('63', '307', 'registrar', '2025-10-16 17:27:46', '0', 'N/A', 'employee', 'success', '2025-10-19 19:33:33', 'School Owner', 'Archived login logs from 2025-10-16 to 2025-10-16', '2025-10-18 22:30:21', NULL, '190955');
INSERT INTO `login_logs_archive` VALUES ('64', '308', 'gesterd', '2025-10-16 17:34:25', '0', 'N/A', 'student', 'success', '2025-10-19 19:33:33', 'School Owner', 'Archived login logs from 2025-10-16 to 2025-10-16', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('65', '309', 'registrar', '2025-10-16 17:51:55', '0', 'N/A', 'employee', 'success', '2025-10-19 19:33:33', 'School Owner', 'Archived login logs from 2025-10-16 to 2025-10-16', '2025-10-17 13:12:25', NULL, '69630');
INSERT INTO `login_logs_archive` VALUES ('66', '310', 'gesterd', '2025-10-16 17:56:14', '0', 'N/A', 'student', 'success', '2025-10-19 19:33:33', 'School Owner', 'Archived login logs from 2025-10-16 to 2025-10-16', '2025-10-17 12:54:34', NULL, '68300');
INSERT INTO `login_logs_archive` VALUES ('67', '311', 'registrar', '2025-10-16 17:59:09', '0', 'N/A', 'employee', 'success', '2025-10-19 19:33:33', 'School Owner', 'Archived login logs from 2025-10-16 to 2025-10-16', '2025-10-17 13:00:01', NULL, '68452');
INSERT INTO `login_logs_archive` VALUES ('68', '272', 'registrar', '2025-10-14 08:17:20', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:42', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('69', '273', 'lance000004muzon@student.cci.edu.ph', '2025-10-14 09:05:12', '0', 'N/A', 'student', 'success', '2025-10-19 21:12:42', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('70', '274', 'lance000004muzon@student.cci.edu.ph', '2025-10-14 09:06:22', '0', 'N/A', 'student', 'success', '2025-10-19 21:12:42', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('71', '275', 'lance000004muzon@student.cci.edu.ph', '2025-10-14 09:06:44', '0', 'N/A', 'student', 'success', '2025-10-19 21:12:42', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('72', '276', 'lance000004muzon@student.cci.edu.ph', '2025-10-14 09:08:01', '0', 'N/A', 'student', 'success', '2025-10-19 21:12:42', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('73', '277', 'registrar', '2025-10-14 09:15:46', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:42', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('74', '278', 'lance000004muzon@student.cci.edu.ph', '2025-10-14 09:24:57', '0', 'N/A', 'student', 'success', '2025-10-19 21:12:42', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('75', '279', 'registrar', '2025-10-14 09:27:44', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:42', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('76', '280', 'hradmin', '2025-10-14 09:38:08', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:42', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('77', '281', 'hradmin', '2025-10-14 09:49:51', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('78', '282', 'registrar', '2025-10-14 09:55:52', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('79', '283', 'hradmin', '2025-10-14 10:07:41', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('80', '284', 'registrar', '2025-10-14 10:16:12', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('81', '285', 'hradmin', '2025-10-14 10:17:38', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('82', '286', 'hradmin', '2025-10-14 10:17:54', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('83', '287', 'hradmin', '2025-10-14 10:18:06', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('84', '288', 'registrar', '2025-10-14 10:19:16', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('85', '289', 'registrar', '2025-10-14 10:22:04', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('86', '290', 'registrar', '2025-10-14 10:22:29', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('87', '291', 'registrar', '2025-10-14 10:28:29', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('88', '292', 'hradmin', '2025-10-14 11:02:20', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('89', '293', 'hradmin', '2025-10-14 11:03:21', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('90', '294', 'registrar', '2025-10-14 11:49:05', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('91', '295', 'hradmin', '2025-10-14 12:44:18', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', '2025-10-19 15:25:54', NULL, '441696');
INSERT INTO `login_logs_archive` VALUES ('92', '296', 'registrar', '2025-10-14 13:13:09', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('93', '297', 'hradmin', '2025-10-14 13:22:21', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', '2025-10-18 23:37:45', NULL, '382524');
INSERT INTO `login_logs_archive` VALUES ('94', '298', 'gaon014muzon@employee.cci.edu.ph', '2025-10-14 13:46:30', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('95', '299', 'registrar', '2025-10-14 13:51:33', '0', 'N/A', 'employee', 'success', '2025-10-19 21:12:43', 'School Owner', 'Archived login logs from 2025-10-14 to 2025-10-14', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('96', '368', 'superadmin', '2025-10-19 20:52:46', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', '2025-10-19 20:52:55', NULL, '9');
INSERT INTO `login_logs_archive` VALUES ('97', '369', 'owner', '2025-10-19 20:53:00', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('98', '370', 'superadmin', '2025-10-19 21:18:24', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('99', '371', 'owner', '2025-10-19 21:20:51', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('100', '372', 'superadmin', '2025-10-19 21:21:07', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('101', '373', 'owner', '2025-10-19 21:24:48', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('102', '374', 'superadmin', '2025-10-19 21:25:11', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('103', '375', 'owner', '2025-10-19 21:25:39', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('104', '376', 'owner', '2025-10-19 21:26:26', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('105', '377', 'superadmin', '2025-10-19 21:26:32', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('106', '378', 'owner', '2025-10-19 21:30:54', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('107', '379', 'superadmin', '2025-10-19 21:31:27', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('108', '380', 'owner', '2025-10-19 21:31:35', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('109', '381', 'owner', '2025-10-19 21:33:57', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('110', '382', 'superadmin', '2025-10-19 21:34:02', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('111', '383', 'owner', '2025-10-19 21:34:35', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('112', '384', 'owner', '2025-10-19 21:37:39', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('113', '385', 'superadmin', '2025-10-19 21:37:45', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('114', '386', 'owner', '2025-10-19 21:42:28', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);
INSERT INTO `login_logs_archive` VALUES ('115', '387', 'superadmin', '2025-10-19 21:42:49', '0', 'N/A', 'employee', 'success', '2025-10-19 21:43:37', 'School Owner', 'Archived login logs from 2025-10-19 to 2025-10-19', NULL, NULL, NULL);


-- Table: notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `date_sent` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=312 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: owner_accounts
DROP TABLE IF EXISTS `owner_accounts`;
CREATE TABLE `owner_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: owner_accounts
INSERT INTO `owner_accounts` VALUES ('1', 'owner', '$2y$10$d4lnh3Rc8oTMe.zK5g1Zz.ptW0Y7cmadaUQtYhMiAuaHWfQKqc9L2', 'School Owner', 'owner@cornerstonecollegeinc.com', '2025-10-21 18:24:45', '2025-09-30 11:16:33', '2025-10-21 18:24:45');


-- Table: owner_approval_requests
DROP TABLE IF EXISTS `owner_approval_requests`;
CREATE TABLE `owner_approval_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_title` varchar(255) NOT NULL,
  `request_description` text NOT NULL,
  `request_type` enum('delete_account','restore_account','system_maintenance','data_modification','user_management','add_hr_employee','delete_hr_employee','restore_student','restore_employee','archive_student','archive_employee','archive_login_logs','archive_attendance','database_backup','maintenance_mode_toggle','other') NOT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `requester_name` varchar(100) NOT NULL,
  `requester_role` varchar(50) NOT NULL,
  `requester_module` varchar(50) NOT NULL,
  `target_table` varchar(50) DEFAULT NULL,
  `target_id` varchar(50) DEFAULT NULL,
  `target_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_data`)),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `owner_comments` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `notification_shown` tinyint(1) DEFAULT 0,
  `reviewed_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=222 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: owner_approval_requests
INSERT INTO `owner_approval_requests` VALUES ('215', 'Restore Employee: hr admin', 'Request to restore deleted employee with ID: CCI2025-002\n\nReason: fajhdas', 'restore_employee', 'high', 'Super Admin', 'superadmin', 'HR Management', NULL, 'CCI2025-002', '{\"id\":94,\"id_number\":\"CCI2025-002\",\"first_name\":\"hr\",\"middle_name\":\"\",\"last_name\":\"admin\",\"position\":\"HR Manager\",\"department\":\"Human Resources\",\"email\":\"admin@gmail.com\",\"phone\":\"639129496157\",\"address\":\"blk88, lot 6 , sjdm, bulacan,\",\"created_at\":\"2025-10-21 15:03:44\",\"hire_date\":\"2025-10-21\",\"rfid_uid\":null,\"deleted_at\":\"2025-10-21 15:30:56\",\"deleted_by\":\"Super Admin\",\"deleted_reason\":\"Deleted by Super Admin\",\"archive_scheduled\":0,\"archive_scheduled_by\":null,\"archive_scheduled_at\":null,\"deletion_reason\":null}', 'approved', '', '2025-10-21 15:31:36', '2025-10-21 15:31:53', '0', 'School Owner');
INSERT INTO `owner_approval_requests` VALUES ('216', 'Restore Employee: Attendance Security', 'Request to restore deleted employee with ID: CCI2025-006\n\nReason: sfdfsdfsd', 'restore_employee', 'high', 'Super Admin', 'superadmin', 'HR Management', NULL, 'CCI2025-006', '{\"id\":98,\"id_number\":\"CCI2025-006\",\"first_name\":\"Attendance\",\"middle_name\":\"\",\"last_name\":\"Security\",\"position\":\"Attendance\",\"department\":\"Student Affairs\",\"email\":\"addsa@gmail.com\",\"phone\":\"639317283173\",\"address\":\"adsdas, adsasdasd, adsdasads, asdasd\",\"created_at\":\"2025-10-21 15:17:01\",\"hire_date\":\"2025-10-21\",\"rfid_uid\":null,\"deleted_at\":\"2025-10-21 15:32:31\",\"deleted_by\":\"Super Admin\",\"deleted_reason\":\"Deleted by HR for administrative purposes\",\"archive_scheduled\":0,\"archive_scheduled_by\":null,\"archive_scheduled_at\":null,\"deletion_reason\":null}', 'approved', '', '2025-10-21 15:32:42', '2025-10-21 15:32:53', '0', 'School Owner');
INSERT INTO `owner_approval_requests` VALUES ('217', 'Restore Student: Hayes Sunico', 'Request to restore deleted student with ID: 02200000002\n\nReason: saddsa', 'restore_student', 'high', 'Super Admin', 'superadmin', 'Student Management', NULL, '02200000002', '{\"id\":88,\"lrn\":\"030000012312\",\"academic_track\":\"GAS\",\"enrollment_status\":\"OLD\",\"school_type\":\"\",\"last_name\":\"Sunico\",\"first_name\":\"Hayes\",\"middle_name\":\"gds\",\"school_year\":\"2025-2026\",\"grade_level\":\"Grade 11\",\"semester\":\"\",\"dob\":\"2003-11-18\",\"birthplace\":\"iguguiasddsa\",\"gender\":\"\",\"religion\":\"sfdfdsdfsfsd\",\"credentials\":\"F-138\",\"payment_mode\":\"Installment\",\"address\":\"sfdsfdfsd, sfdfdsfsdsfd, sfdfsdfds, fdsdfsfd\",\"father_name\":\"gesterd  gaon\",\"father_occupation\":\"dasjdha\",\"father_contact\":\"98035905354\",\"mother_name\":\"liam de jesus\",\"mother_occupation\":\"dsasddsaasd\",\"mother_contact\":\"09243432874\",\"guardian_name\":\"adhuihsdaiuad  kjdajk\",\"guardian_occupation\":\"asdklnajnaj\",\"guardian_contact\":\"09432432342\",\"last_school\":\"gfgfdfdgfgf\",\"last_school_year\":\"2025-2026\",\"id_number\":\"02200000002\",\"username\":\"sunico000002muzon@student.cci.edu.ph\",\"password\":\"$2y$10$VKpOSWARVK.bXnNPQgTPtuUT\\/7COOZ8KAJROwuwjiEYLK3.puCrWe\",\"rfid_uid\":\"0095215938\",\"created_at\":\"2025-10-21 14:13:20\",\"class_schedule\":null,\"deleted_at\":\"2025-10-21 15:45:39\",\"deleted_by\":\"Registrar Admin\",\"deleted_reason\":\"Deleted by registrar for administrative purposes\",\"must_change_password\":1}', 'approved', '', '2025-10-21 15:46:05', '2025-10-21 15:46:30', '0', 'School Owner');
INSERT INTO `owner_approval_requests` VALUES ('218', 'Restore Employee: hr admin', 'Request to restore deleted employee with ID: CCI2025-002\n\nReason: error in dleetion', 'restore_employee', 'high', 'Super Admin', 'superadmin', 'HR Management', NULL, 'CCI2025-002', '{\"id\":94,\"id_number\":\"CCI2025-002\",\"first_name\":\"hr\",\"middle_name\":\"\",\"last_name\":\"admin\",\"position\":\"HR Manager\",\"department\":\"Human Resources\",\"email\":\"admin@gmail.com\",\"phone\":\"639129496157\",\"address\":\"blk88, lot 6 , sjdm, bulacan,\",\"created_at\":\"2025-10-21 15:03:44\",\"hire_date\":\"2025-10-21\",\"rfid_uid\":null,\"deleted_at\":\"2025-10-21 18:16:36\",\"deleted_by\":\"Super Admin\",\"deleted_reason\":\"Deleted by Super Admin\",\"archive_scheduled\":0,\"archive_scheduled_by\":null,\"archive_scheduled_at\":null,\"deletion_reason\":null}', 'approved', '', '2025-10-21 18:17:39', '2025-10-21 18:18:10', '0', 'School Owner');
INSERT INTO `owner_approval_requests` VALUES ('219', 'Archive Employee: hr admin', 'Request to permanently archive deleted employee with ID: CCI2025-002\n\nReason: jhsfsdfsdf', 'archive_employee', 'critical', 'Super Admin', 'superadmin', 'HR Management', NULL, 'CCI2025-002', '{\"id\":94,\"id_number\":\"CCI2025-002\",\"first_name\":\"hr\",\"middle_name\":\"\",\"last_name\":\"admin\",\"position\":\"HR Manager\",\"department\":\"Human Resources\",\"email\":\"admin@gmail.com\",\"phone\":\"639129496157\",\"address\":\"blk88, lot 6 , sjdm, bulacan,\",\"created_at\":\"2025-10-21 15:03:44\",\"hire_date\":\"2025-10-21\",\"rfid_uid\":null,\"deleted_at\":\"2025-10-21 18:20:00\",\"deleted_by\":\"Super Admin\",\"deleted_reason\":\"Deleted by Super Admin\",\"archive_scheduled\":0,\"archive_scheduled_by\":null,\"archive_scheduled_at\":null,\"deletion_reason\":null}', 'approved', '', '2025-10-21 18:21:16', '2025-10-21 18:21:30', '0', 'School Owner');
INSERT INTO `owner_approval_requests` VALUES ('220', 'Enable Maintenance Mode', 'Request to enable system maintenance mode\n\nReason: system upgrade\n\nImpact: All users except admins will be blocked from accessing the system', 'maintenance_mode_toggle', 'critical', 'Super Admin', 'superadmin', 'System Configuration', NULL, 'maintenance_enable', '{\"action\":\"enable\",\"reason\":\"system upgrade\",\"requested_at\":\"2025-10-21 18:22:49\"}', 'approved', '', '2025-10-21 18:22:49', '2025-10-21 18:23:02', '0', 'School Owner');
INSERT INTO `owner_approval_requests` VALUES ('221', 'Database Backup Request', 'Request to download complete database backup\n\nReason: data', 'database_backup', 'high', 'Super Admin', 'superadmin', 'System Configuration', NULL, 'backup_2025-10-21_18-24-25', '{\"reason\":\"data\",\"requested_at\":\"2025-10-21 18:24:25\"}', 'approved', '', '2025-10-21 18:24:25', '2025-10-21 18:25:06', '0', 'School Owner');


-- Table: owner_requests
DROP TABLE IF EXISTS `owner_requests`;
CREATE TABLE `owner_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_type` enum('delete_student','delete_employee','system_maintenance','database_backup','user_management','security_change') NOT NULL,
  `requested_by` varchar(50) NOT NULL,
  `requester_role` enum('superadmin','hr','registrar') NOT NULL,
  `request_title` varchar(255) NOT NULL,
  `request_description` text NOT NULL,
  `target_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_data`)),
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` varchar(50) DEFAULT NULL,
  `owner_comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_requested_by` (`requested_by`),
  KEY `idx_request_type` (`request_type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: parent_account
DROP TABLE IF EXISTS `parent_account`;
CREATE TABLE `parent_account` (
  `parent_id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `must_change_password` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`parent_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `unique_child` (`child_id`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `uq_parent_username` (`username`),
  KEY `idx_child_id` (`child_id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: parent_account
INSERT INTO `parent_account` VALUES ('58', '02200000001', 'dejesus000001muzon@parent.cci.edu.ph', '$2y$10$4KgF7a8lYEUPAccXuQNqZelbL4LVPKHB/GntDRmWYqQRKTgguToOG', '2025-10-21 14:07:46', '2025-10-21 14:07:46', '0');
INSERT INTO `parent_account` VALUES ('59', '02200000002', 'sunico000002muzon@parent.cci.edu.ph', '$2y$10$JbFQC4o.1.m6pM9yDQoLHuEuPc5kbl4/xEm73cPwXs3lTOrp.indm', '2025-10-21 14:13:20', '2025-10-21 14:13:20', '0');


-- Table: parent_notifications
DROP TABLE IF EXISTS `parent_notifications`;
CREATE TABLE `parent_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` varchar(50) NOT NULL,
  `child_id` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `date_sent` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_parent_child` (`parent_id`,`child_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_date_sent` (`date_sent`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table: payment_schedule
DROP TABLE IF EXISTS `payment_schedule`;
CREATE TABLE `payment_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(50) DEFAULT NULL,
  `school_year_term` varchar(100) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: sections
DROP TABLE IF EXISTS `sections`;
CREATE TABLE `sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_name` (`section_name`),
  KEY `idx_section_name` (`section_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: sections
INSERT INTO `sections` VALUES ('5', 'ABM 12', '', '2025-10-21 17:23:48', '84');


-- Table: session_cookies
DROP TABLE IF EXISTS `session_cookies`;
CREATE TABLE `session_cookies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `user_type` enum('student','registrar','cashier','guidance','admin') NOT NULL,
  `cookie_value` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cookie_value` (`cookie_value`),
  UNIQUE KEY `user_session` (`user_id`,`user_type`),
  KEY `expires_at` (`expires_at`),
  KEY `idx_session_cookies_user` (`user_id`,`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: student_account
DROP TABLE IF EXISTS `student_account`;
CREATE TABLE `student_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lrn` varchar(20) NOT NULL,
  `academic_track` varchar(100) NOT NULL,
  `enrollment_status` enum('OLD','NEW') NOT NULL,
  `school_type` enum('PUBLIC','PRIVATE') DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `school_year` varchar(20) NOT NULL,
  `grade_level` varchar(20) NOT NULL,
  `semester` varchar(10) NOT NULL,
  `dob` date NOT NULL,
  `birthplace` varchar(100) NOT NULL,
  `gender` enum('Male','Female') NOT NULL,
  `religion` varchar(50) NOT NULL,
  `credentials` text DEFAULT NULL,
  `payment_mode` enum('Cash','Installment') NOT NULL,
  `address` text NOT NULL,
  `father_name` varchar(100) NOT NULL,
  `father_occupation` varchar(100) DEFAULT NULL,
  `father_contact` varchar(20) DEFAULT NULL,
  `mother_name` varchar(100) NOT NULL,
  `mother_occupation` varchar(100) DEFAULT NULL,
  `mother_contact` varchar(20) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_occupation` varchar(100) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `last_school` varchar(100) DEFAULT NULL,
  `last_school_year` varchar(20) DEFAULT NULL,
  `id_number` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rfid_uid` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `class_schedule` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` varchar(255) DEFAULT NULL,
  `deleted_reason` text DEFAULT NULL,
  `must_change_password` tinyint(1) DEFAULT 1 COMMENT 'Force password change on first login (1=yes, 0=no)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_number` (`id_number`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `rfid_uid` (`rfid_uid`),
  KEY `idx_student_account_class_schedule` (`class_schedule`),
  KEY `idx_student_deleted_at` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: student_account
INSERT INTO `student_account` VALUES ('87', '020123312312', 'ABM', 'OLD', '', 'De Jesus', 'Liam', '', '2025-2026', 'Grade 12', '', '2003-12-19', 'sadasdsdaasddasdas', '', 'sdadassda', 'F-138', 'Cash', 'asddasdas, sdadsasda, asdsadsa, asddsaasd', 'asdasdsda  saddsaasd', 'asdsasdad', '09543354545', 'asasdasd  sdasdasda', 'asdsdaasd', '09355335544', 'sfdfdsfdsfd  sfdfdsfdsfds', 'fdssfdfds', '09543545454', 'sdaasdsdasdaasdasd', '2025-2026', '02200000001', 'liam', '$2y$10$9Abn3Fn5xG5fMljp5Ehr7.AnNwzvlx93wieIyRChxDS7Y/UgL0jP2', '0095347257', '2025-10-21 14:07:46', 'ABM 12 (5:23 PM - 5:23 AM)', NULL, NULL, NULL, '0');
INSERT INTO `student_account` VALUES ('88', '030000012312', 'GAS', 'OLD', '', 'Sunico', 'Hayes', 'gds', '2025-2026', 'Grade 11', '', '2003-11-18', 'iguguiasddsa', '', 'sfdfdsdfsfsd', 'F-138', 'Installment', 'sfdsfdfsd, sfdfdsfsdsfd, sfdfsdfds, fdsdfsfd', 'gesterd  gaon', 'dasjdha', '98035905354', 'liam de jesus', 'dsasddsaasd', '09243432874', 'adhuihsdaiuad  kjdajk', 'asdklnajnaj', '09432432342', 'gfgfdfdgfgf', '2025-2026', '02200000002', 'sunico000002muzon@student.cci.edu.ph', '$2y$10$6ccnA94lSOuNYcdCwEL9TOwjHE1Drr6gn7Q3MggdU5f3E59XsIgzq', '0095215938', '2025-10-21 14:13:20', 'ABM 12 (5:23 PM - 5:23 AM)', NULL, NULL, NULL, '0');


-- Table: student_fee_assignments
DROP TABLE IF EXISTS `student_fee_assignments`;
CREATE TABLE `student_fee_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `tuition_structure_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_mode` enum('Cash','Installment') NOT NULL,
  `installment_plan` varchar(50) DEFAULT NULL,
  `installments_count` int(11) DEFAULT 1,
  `amount_per_installment` decimal(10,2) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `school_year` varchar(20) NOT NULL,
  `status` enum('Active','Paid','Cancelled') DEFAULT 'Active',
  PRIMARY KEY (`id`),
  KEY `tuition_structure_id` (`tuition_structure_id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_school_year` (`school_year`),
  KEY `idx_status` (`status`),
  CONSTRAINT `student_fee_assignments_ibfk_1` FOREIGN KEY (`tuition_structure_id`) REFERENCES `tuition_fee_structure` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: student_fee_items
DROP TABLE IF EXISTS `student_fee_items`;
CREATE TABLE `student_fee_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(50) NOT NULL,
  `school_year_term` varchar(50) NOT NULL,
  `fee_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `student_name` varchar(200) DEFAULT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_number` (`id_number`)
) ENGINE=InnoDB AUTO_INCREMENT=233 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: student_fee_items
INSERT INTO `student_fee_items` VALUES ('203', '02200000002', '2025-2026 - 1st', 'Tuition Fee', '55200.00', '0.00', '2025-10-19 22:22:14', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('204', '02200000006', '2025-2026 1st Semester', 'Tuition Fee', '3000.00', '0.00', '2025-10-20 01:20:15', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('205', '02200000006', '2025-2026 1st Semester', 'Other Fees', '123.00', '0.00', '2025-10-20 01:20:15', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('206', '02200000007', '2025-2026 2nd Semester', 'Tuition Fee', '50000.00', '0.00', '2025-10-20 01:22:40', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('207', '02200000007', '2025-2026 2nd Semester', 'Other Fees', '123.00', '0.00', '2025-10-20 01:22:40', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('208', '02200000009', '2025-2026 1st Semester', 'Tuition Fee - Quarter 1 of 4', '2500.00', '0.00', '2025-10-20 01:32:00', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('209', '02200000009', '2025-2026 1st Semester', 'Tuition Fee - Quarter 2 of 4', '2500.00', '0.00', '2025-10-20 01:32:00', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('210', '02200000009', '2025-2026 1st Semester', 'Tuition Fee - Quarter 3 of 4', '2500.00', '0.00', '2025-10-20 01:32:00', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('211', '02200000009', '2025-2026 1st Semester', 'Tuition Fee - Quarter 4 of 4', '2500.00', '0.00', '2025-10-20 01:32:00', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('212', '02200000009', '2025-2026 1st Semester', 'Other Fees', '100.00', '0.00', '2025-10-20 01:32:00', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('213', '02200000010', '2025-2026 1st Semester', 'Tuition Fee - Trimester 1 of 3', '16666.67', '0.00', '2025-10-20 01:37:26', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('214', '02200000010', '2025-2026 1st Semester', 'Tuition Fee - Trimester 2 of 3', '16666.67', '0.00', '2025-10-20 01:37:26', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('215', '02200000010', '2025-2026 1st Semester', 'Tuition Fee - Trimester 3 of 3', '16666.66', '0.00', '2025-10-20 01:37:26', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('216', '02200000010', '2025-2026 1st Semester', 'Other Fees', '100.00', '100.00', '2025-10-20 01:37:26', '2025-10-20 06:50:28', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('217', '02200000001', '2025-2026 1st Semester', 'Document Request Fee - Form137', '32112.00', '0.00', '2025-10-20 06:53:34', '2025-10-20 06:53:34', 'Gesterd Go', 'Grade 12');
INSERT INTO `student_fee_items` VALUES ('218', '02200000001', '2025-2026 1st Semester', 'Document Request Fee - Good Moral', '300.00', '0.00', '2025-10-20 06:54:50', '2025-10-20 06:54:50', 'Gesterd Go', 'Grade 12');
INSERT INTO `student_fee_items` VALUES ('219', '02200000011', '2025-2026 1st Semester', 'Tuition Fee', '0.00', '0.00', '2025-10-20 07:32:37', '2025-10-20 07:32:37', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('220', '02200000011', '2025-2026 1st Semester', 'Other Fees', '0.00', '0.00', '2025-10-20 07:32:37', '2025-10-20 07:32:37', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('221', '02200000001', '2025-2026 1st Semester', 'Document Request Fee - Good Moral', '300.00', '300.00', '2025-10-21 08:39:13', '2025-10-21 08:39:13', 'Gesterd Go', 'Grade 12');
INSERT INTO `student_fee_items` VALUES ('222', '02200000012', '2025-2026 1st Semester', 'Tuition Fee', '123.00', '0.00', '2025-10-21 10:07:37', '2025-10-21 10:07:37', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('223', '02200000012', '2025-2026 1st Semester', 'Other Fees', '123.00', '0.00', '2025-10-21 10:07:37', '2025-10-21 10:07:37', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('224', '02200000001', '2025-2026 1st Semester', 'Document Request Fee - Form137', '32112.00', '0.00', '2025-10-21 12:38:27', '2025-10-21 12:38:27', 'Gesterd Go', 'Grade 12');
INSERT INTO `student_fee_items` VALUES ('225', '02200000013', '2025-2026 2nd Semester', 'Tuition Fee', '0.00', '0.00', '2025-10-21 13:41:08', '2025-10-21 13:41:08', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('226', '02200000013', '2025-2026 2nd Semester', 'Other Fees', '0.00', '0.00', '2025-10-21 13:41:08', '2025-10-21 13:41:08', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('227', '02200000014', '2025-2026 1st Semester', 'Tuition Fee', '0.00', '0.00', '2025-10-21 14:02:43', '2025-10-21 14:02:43', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('228', '02200000014', '2025-2026 1st Semester', 'Other Fees', '0.00', '0.00', '2025-10-21 14:02:43', '2025-10-21 14:02:43', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('229', '02200000001', '2025-2026 1st Semester', 'Tuition Fee', '453.00', '0.00', '2025-10-21 14:07:46', '2025-10-21 14:07:46', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('230', '02200000001', '2025-2026 1st Semester', 'Other Fees', '543.00', '0.00', '2025-10-21 14:07:46', '2025-10-21 14:07:46', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('231', '02200000002', '2025-2026 1st Semester', 'Tuition Fee', '0.00', '0.00', '2025-10-21 14:13:20', '2025-10-21 14:13:20', NULL, NULL);
INSERT INTO `student_fee_items` VALUES ('232', '02200000002', '2025-2026 1st Semester', 'Other Fees', '0.00', '0.00', '2025-10-21 14:13:20', '2025-10-21 14:13:20', NULL, NULL);


-- Table: student_payments
DROP TABLE IF EXISTS `student_payments`;
CREATE TABLE `student_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `or_number` varchar(50) DEFAULT NULL,
  `school_year_term` varchar(100) DEFAULT NULL,
  `fee_type` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: student_schedules
DROP TABLE IF EXISTS `student_schedules`;
CREATE TABLE `student_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_schedule` (`student_id`,`schedule_id`),
  KEY `fk_schedule` (`schedule_id`),
  CONSTRAINT `student_schedules_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: student_schedules
INSERT INTO `student_schedules` VALUES ('86', '02200000001', '39', '2025-10-21 17:26:38', '84', '2025-10-21 17:26:38', '2025-10-21 17:26:38');
INSERT INTO `student_schedules` VALUES ('87', '02200000002', '39', '2025-10-21 17:26:38', '84', '2025-10-21 17:26:38', '2025-10-21 17:26:38');


-- Table: student_tuition
DROP TABLE IF EXISTS `student_tuition`;
CREATE TABLE `student_tuition` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `grade_level` varchar(50) NOT NULL,
  `academic_track` varchar(100) DEFAULT '',
  `fee_amount` decimal(10,2) NOT NULL,
  `payment_mode` enum('Cash','Installment') DEFAULT 'Cash',
  `installments` int(11) DEFAULT 1,
  `amount_per_installment` decimal(10,2) DEFAULT 0.00,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `school_year` varchar(50) DEFAULT '2025-2026',
  `status` enum('Unpaid','Partially Paid','Paid') DEFAULT 'Unpaid',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: subject_offerings
DROP TABLE IF EXISTS `subject_offerings`;
CREATE TABLE `subject_offerings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL,
  `grade_level` varchar(20) NOT NULL,
  `strand` varchar(50) DEFAULT NULL,
  `semester` enum('1st','2nd') NOT NULL,
  `school_year_term` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_offer` (`subject_id`,`grade_level`,`strand`,`semester`,`school_year_term`),
  CONSTRAINT `fk_subject_offerings_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: subject_offerings
INSERT INTO `subject_offerings` VALUES ('73', '54', 'Grade 7', '', '2nd', '', '1', '2025-10-21 17:24:30');
INSERT INTO `subject_offerings` VALUES ('74', '54', 'Grade 7', '', '1st', '', '1', '2025-10-21 17:24:30');
INSERT INTO `subject_offerings` VALUES ('75', '55', 'Grade 12', 'ABM', '1st', '', '1', '2025-10-21 17:28:11');


-- Table: subjects
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: subjects
INSERT INTO `subjects` VALUES ('54', 'mt', 'Math 7', '2025-10-21 17:24:30');
INSERT INTO `subjects` VALUES ('55', 'as', 'dhasdas', '2025-10-21 17:28:11');


-- Table: submitted_documents
DROP TABLE IF EXISTS `submitted_documents`;
CREATE TABLE `submitted_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(50) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `date_submitted` datetime NOT NULL DEFAULT current_timestamp(),
  `remarks` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Submitted',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=198 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: submitted_documents
INSERT INTO `submitted_documents` VALUES ('197', '02200000001', 'F-138', '2025-10-21 15:25:24', 'Submitted', 'Submitted');


-- Table: super_admins
DROP TABLE IF EXISTS `super_admins`;
CREATE TABLE `super_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `role` varchar(50) DEFAULT 'Principal/Owner',
  `access_level` varchar(100) DEFAULT 'IT Personnel - System Maintenance',
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: super_admins
INSERT INTO `super_admins` VALUES ('1', 'superadmin', '$2y$10$p3l8/0v7UR8OVF2yIcbNS.JelHvjlJ6s6cMQ/vZPQC2F66yulVtti', 'Super', 'Admin', '2025-09-25 14:17:59', 'Principal/Owner', 'IT Personnel - System Maintenance', NULL, '1');


-- Table: system_config
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config` (
  `config_key` varchar(50) NOT NULL,
  `config_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: system_config
INSERT INTO `system_config` VALUES ('debug_mode', '0', '2025-09-29 21:38:55');


-- Table: system_logs
DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action_type` varchar(100) NOT NULL,
  `performed_by` varchar(50) NOT NULL,
  `user_role` varchar(20) NOT NULL,
  `description` text NOT NULL,
  `affected_table` varchar(100) DEFAULT NULL,
  `affected_record_id` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_performed_by` (`performed_by`),
  KEY `idx_action_type` (`action_type`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: system_notifications
DROP TABLE IF EXISTS `system_notifications`;
CREATE TABLE `system_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error','critical') DEFAULT 'info',
  `module` varchar(50) NOT NULL,
  `performed_by` varchar(100) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `target_table` varchar(50) DEFAULT NULL,
  `target_id` varchar(50) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=262 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: system_notifications
INSERT INTO `system_notifications` VALUES ('251', 'HR Employee Account Deleted', 'Deleted employee account: hr admin (ID: CCI2025-002)', 'warning', 'Super Admin', 'Super Admin', 'Super Admin', 'employees', 'CCI2025-002', 'employee_deleted', '{\"id\":94,\"id_number\":\"CCI2025-002\",\"first_name\":\"hr\",\"last_name\":\"admin\"}', NULL, '1', '2025-10-21 15:30:56');
INSERT INTO `system_notifications` VALUES ('252', 'Request Approved', 'Owner has approved request: Restore Employee: hr admin', 'success', 'Owner', 'School Owner', 'owner', NULL, '215', 'request_approve', NULL, NULL, '1', '2025-10-21 15:31:53');
INSERT INTO `system_notifications` VALUES ('253', 'Employee Account Deleted', 'Deleted employee account: Attendance Security (ID: CCI2025-006)', 'warning', 'HR', 'Super Admin', 'HR', 'employees', 'CCI2025-006', 'employee_deleted', '{\"first_name\":\"Attendance\",\"last_name\":\"Security\",\"id_number\":\"CCI2025-006\",\"position\":\"Attendance\",\"department\":\"Student Affairs\"}', NULL, '1', '2025-10-21 15:32:31');
INSERT INTO `system_notifications` VALUES ('254', 'Request Approved', 'Owner has approved request: Restore Employee: Attendance Security', 'success', 'Owner', 'School Owner', 'owner', NULL, '216', 'request_approve', NULL, NULL, '1', '2025-10-21 15:32:53');
INSERT INTO `system_notifications` VALUES ('255', 'Student Account Deleted', 'Deleted student account: Hayes Sunico (ID: 02200000002)', 'warning', 'Registrar', 'Registrar Admin', 'Registrar', 'student_account', '02200000002', 'student_deleted', '{\"first_name\":\"Hayes\",\"last_name\":\"Sunico\",\"id_number\":\"02200000002\"}', NULL, '1', '2025-10-21 15:45:39');
INSERT INTO `system_notifications` VALUES ('256', 'Request Approved', 'Owner has approved request: Restore Student: Hayes Sunico', 'success', 'Owner', 'School Owner', 'owner', NULL, '217', 'request_approve', NULL, NULL, '1', '2025-10-21 15:46:30');
INSERT INTO `system_notifications` VALUES ('257', 'HR Employee Account Deleted', 'Deleted employee account: hr admin (ID: CCI2025-002)', 'warning', 'Super Admin', 'Super Admin', 'Super Admin', 'employees', 'CCI2025-002', 'employee_deleted', '{\"id\":94,\"id_number\":\"CCI2025-002\",\"first_name\":\"hr\",\"last_name\":\"admin\"}', NULL, '0', '2025-10-21 18:16:36');
INSERT INTO `system_notifications` VALUES ('258', 'Request Approved', 'Owner has approved request: Restore Employee: hr admin', 'success', 'Owner', 'School Owner', 'owner', NULL, '218', 'request_approve', NULL, NULL, '0', '2025-10-21 18:18:10');
INSERT INTO `system_notifications` VALUES ('259', 'HR Employee Account Deleted', 'Deleted employee account: hr admin (ID: CCI2025-002)', 'warning', 'Super Admin', 'Super Admin', 'Super Admin', 'employees', 'CCI2025-002', 'employee_deleted', '{\"id\":94,\"id_number\":\"CCI2025-002\",\"first_name\":\"hr\",\"last_name\":\"admin\"}', NULL, '0', '2025-10-21 18:20:00');
INSERT INTO `system_notifications` VALUES ('260', 'Request Approved', 'Owner has approved request: Archive Employee: hr admin', 'success', 'Owner', 'School Owner', 'owner', NULL, '219', 'request_approve', NULL, NULL, '0', '2025-10-21 18:21:30');
INSERT INTO `system_notifications` VALUES ('261', 'Request Enabled', 'Owner has enabled request: Enable Maintenance Mode', 'warning', 'Owner', 'School Owner', 'owner', NULL, '220', 'request_enable', NULL, NULL, '0', '2025-10-21 18:23:02');


-- Table: teacher_attendance
DROP TABLE IF EXISTS `teacher_attendance`;
CREATE TABLE `teacher_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `day` varchar(10) NOT NULL,
  `shift_type` varchar(20) DEFAULT 'Regular',
  `shift_in` time DEFAULT NULL,
  `shift_out` time DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `required_hours` decimal(4,2) DEFAULT 8.00,
  `tardiness_minutes` int(11) DEFAULT 0,
  `undertime_minutes` int(11) DEFAULT 0,
  `leave_with_pay` tinyint(1) DEFAULT 0,
  `leave_without_pay` tinyint(1) DEFAULT 0,
  `ot_minutes` int(11) DEFAULT 0,
  `ob_minutes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_emp_date` (`employee_id`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: teacher_attendance
INSERT INTO `teacher_attendance` VALUES ('18', 'CCI2025-001', '2025-10-21', 'Tuesday', 'Regular', NULL, NULL, '14:58:17', NULL, '8.00', '0', '0', '0', '0', '0', '0', '2025-10-21 14:58:17');


-- Table: teacher_sections
DROP TABLE IF EXISTS `teacher_sections`;
CREATE TABLE `teacher_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` varchar(20) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_section` (`teacher_id`,`section_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: teacher_sections
INSERT INTO `teacher_sections` VALUES ('9', 'CCI2025-001', 'ABM 12', '2025-10-21 17:30:27', '0');


-- Table: teacher_subjects
DROP TABLE IF EXISTS `teacher_subjects`;
CREATE TABLE `teacher_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` varchar(20) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_subject` (`teacher_id`,`subject_name`),
  CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `employees` (`id_number`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: teacher_subjects
INSERT INTO `teacher_subjects` VALUES ('23', 'CCI2025-001', 'dhasdas', '2025-10-21 17:30:27', '0');
INSERT INTO `teacher_subjects` VALUES ('24', 'CCI2025-001', 'Math 7', '2025-10-21 17:30:27', '0');


-- Table: tuition_fee_structure
DROP TABLE IF EXISTS `tuition_fee_structure`;
CREATE TABLE `tuition_fee_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_level` varchar(50) NOT NULL,
  `academic_track` varchar(100) DEFAULT NULL,
  `tuition_fee` decimal(10,2) NOT NULL,
  `other_fees` decimal(10,2) DEFAULT 0.00,
  `total_fee` decimal(10,2) NOT NULL,
  `school_year` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `term` varchar(20) DEFAULT '1st Semester',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_grade_track_year_term` (`grade_level`,`academic_track`,`school_year`,`term`),
  KEY `idx_grade_level` (`grade_level`),
  KEY `idx_school_year` (`school_year`)
) ENGINE=InnoDB AUTO_INCREMENT=1429 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: tuition_fee_structure
INSERT INTO `tuition_fee_structure` VALUES ('1293', 'Kinder 1', 'Pre-Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1294', 'Kinder 1', 'Pre-Elementary', '123.00', '0.00', '123.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:43:07', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1295', 'Kinder 2', 'Pre-Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1296', 'Kinder 2', 'Pre-Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1297', 'Grade 1', 'Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1298', 'Grade 1', 'Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1299', 'Grade 2', 'Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1300', 'Grade 2', 'Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1301', 'Grade 3', 'Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1302', 'Grade 3', 'Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1303', 'Grade 4', 'Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1304', 'Grade 4', 'Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1305', 'Grade 5', 'Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1306', 'Grade 5', 'Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1307', 'Grade 6', 'Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1308', 'Grade 6', 'Elementary', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1309', 'Grade 7', 'Junior High School', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1310', 'Grade 7', 'Junior High School', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1311', 'Grade 8', 'Junior High School', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1312', 'Grade 8', 'Junior High School', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1313', 'Grade 9', 'Junior High School', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1314', 'Grade 9', 'Junior High School', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1315', 'Grade 10', 'Junior High School', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1316', 'Grade 10', 'Junior High School', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1317', 'Grade 11', 'ABM', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1318', 'Grade 11', 'ABM', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1319', 'Grade 12', 'ABM', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1320', 'Grade 12', 'ABM', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1321', 'Grade 11', 'GAS', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1322', 'Grade 11', 'GAS', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1323', 'Grade 12', 'GAS', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1324', 'Grade 12', 'GAS', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1325', 'Grade 11', 'HUMSS', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1326', 'Grade 11', 'HUMSS', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1327', 'Grade 12', 'HUMSS', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1328', 'Grade 12', 'HUMSS', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1329', 'Grade 11', 'STEM', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1330', 'Grade 11', 'STEM', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1331', 'Grade 12', 'STEM', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1332', 'Grade 12', 'STEM', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1333', 'Grade 11', 'ICT', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1334', 'Grade 11', 'ICT', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1335', 'Grade 12', 'ICT', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1336', 'Grade 12', 'ICT', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1337', 'Grade 11', 'HE', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1338', 'Grade 11', 'HE', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1339', 'Grade 12', 'HE', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1340', 'Grade 12', 'HE', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1341', 'Grade 11', 'SPORTS', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1342', 'Grade 11', 'SPORTS', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1343', 'Grade 12', 'SPORTS', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1344', 'Grade 12', 'SPORTS', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1345', '1st Year', 'BPEd (Bachelor of Physical Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1346', '1st Year', 'BPEd (Bachelor of Physical Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1347', '2nd Year', 'BPEd (Bachelor of Physical Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1348', '2nd Year', 'BPEd (Bachelor of Physical Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1349', '3rd Year', 'BPEd (Bachelor of Physical Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1350', '3rd Year', 'BPEd (Bachelor of Physical Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1351', '4th Year', 'BPEd (Bachelor of Physical Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1352', '4th Year', 'BPEd (Bachelor of Physical Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1353', '1st Year', 'BECEd (Bachelor of Early Childhood Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1354', '1st Year', 'BECEd (Bachelor of Early Childhood Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1355', '2nd Year', 'BECEd (Bachelor of Early Childhood Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1356', '2nd Year', 'BECEd (Bachelor of Early Childhood Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1357', '3rd Year', 'BECEd (Bachelor of Early Childhood Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1358', '3rd Year', 'BECEd (Bachelor of Early Childhood Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1359', '4th Year', 'BECEd (Bachelor of Early Childhood Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '1st Semester');
INSERT INTO `tuition_fee_structure` VALUES ('1360', '4th Year', 'BECEd (Bachelor of Early Childhood Education)', '0.00', '0.00', '0.00', '2025-2026', '2025-10-21 15:42:14', '2025-10-21 15:42:14', '2nd Semester');


-- Table: tuition_fees
DROP TABLE IF EXISTS `tuition_fees`;
CREATE TABLE `tuition_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_level` varchar(50) NOT NULL,
  `academic_track` varchar(100) DEFAULT '',
  `fee_amount` decimal(10,2) NOT NULL,
  `school_year` varchar(50) DEFAULT '2025-2026',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: violation_types
DROP TABLE IF EXISTS `violation_types`;
CREATE TABLE `violation_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `violation_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `violation_name` (`violation_name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: violation_types
INSERT INTO `violation_types` VALUES ('1', 'Dress Code Violation', '0', '2025-09-15 09:55:14', '2025-09-15 10:00:44');
INSERT INTO `violation_types` VALUES ('2', 'Late Arrival', '0', '2025-09-15 09:55:14', '2025-09-23 17:37:32');
INSERT INTO `violation_types` VALUES ('3', 'Disruptive Behavior', '1', '2025-09-15 09:55:14', '2025-09-15 09:55:14');
INSERT INTO `violation_types` VALUES ('4', 'Cheating', '1', '2025-09-15 09:55:14', '2025-09-15 09:55:14');
INSERT INTO `violation_types` VALUES ('5', 'Bullying', '1', '2025-09-15 09:55:14', '2025-09-15 09:55:14');
INSERT INTO `violation_types` VALUES ('6', 'Vandalism', '1', '2025-09-15 09:55:14', '2025-09-15 09:55:14');
INSERT INTO `violation_types` VALUES ('7', 'Fighting', '1', '2025-09-15 09:55:14', '2025-09-15 09:55:14');
INSERT INTO `violation_types` VALUES ('8', 'Smoking/Vaping', '1', '2025-09-15 09:55:14', '2025-09-15 09:55:14');
INSERT INTO `violation_types` VALUES ('9', 'Inappropriate Language', '1', '2025-09-15 09:55:14', '2025-09-15 09:55:14');
INSERT INTO `violation_types` VALUES ('10', 'Skipping Class', '1', '2025-09-15 09:55:14', '2025-09-15 09:55:14');
INSERT INTO `violation_types` VALUES ('11', 'No id', '0', '2025-09-15 10:00:17', '2025-10-21 15:49:36');
INSERT INTO `violation_types` VALUES ('12', 'sda', '0', '2025-09-23 17:37:39', '2025-09-23 17:37:45');

SET FOREIGN_KEY_CHECKS=1;
