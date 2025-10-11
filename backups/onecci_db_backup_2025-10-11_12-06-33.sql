-- OneCCI Database Backup
-- Generated: 2025-10-11 12:06:33
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

-- Data for table: approval_requests
INSERT INTO `approval_requests` VALUES ('1', 'permanent_delete_employee', '71092124378', 'employees', 'Super Admin', 'need', 'pending', '2025-09-30 11:31:55', NULL, NULL, NULL);
INSERT INTO `approval_requests` VALUES ('2', 'permanent_delete_student', 'S2025006', 'student_account', 'Super Admin', 'asdawdasd', 'pending', '2025-09-30 11:33:09', NULL, NULL, NULL);


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
) ENGINE=InnoDB AUTO_INCREMENT=235 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: attendance_record
INSERT INTO `attendance_record` VALUES ('224', '02000000001', '2025-09-30', 'Tuesday', 'ABM - 12A (7:30 AM -', '14:37:42', '14:37:46', 'Present');
INSERT INTO `attendance_record` VALUES ('225', '02000345678', '2025-10-04', 'Saturday', NULL, '06:35:02', '15:59:06', 'Present');
INSERT INTO `attendance_record` VALUES ('226', '02000307705', '2025-10-04', 'Saturday', 'BSIT - 601 (Variable', '06:38:05', '08:54:35', 'Present');
INSERT INTO `attendance_record` VALUES ('227', '02000645645', '2025-10-04', 'Saturday', NULL, '08:54:38', NULL, 'Present');
INSERT INTO `attendance_record` VALUES ('228', '02000534645', '2025-10-04', 'Saturday', 'BSIT - 601 (Variable', '08:54:39', NULL, 'Present');
INSERT INTO `attendance_record` VALUES ('229', '80374985739845345', '2025-10-04', 'Saturday', 'BSIT - 702 (8:00 AM ', NULL, NULL, 'Absent');
INSERT INTO `attendance_record` VALUES ('230', '67867867866666786786', '2025-10-04', 'Saturday', 'BSIT - 702 (8:00 AM ', NULL, NULL, 'Absent');


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
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: class_schedules
INSERT INTO `class_schedules` VALUES ('1', 'BSIT - 603', '07:00:00', '17:00:00', 'Monday,Tuesday,Wednesday,Thursday,Friday,Sunday', '1', '2025-09-03 08:57:19', '2025-09-03 10:08:21');
INSERT INTO `class_schedules` VALUES ('2', 'BSIT - 702', '00:00:00', '23:59:59', 'Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday', '1', '2025-09-03 10:30:54', '2025-09-21 01:59:23');
INSERT INTO `class_schedules` VALUES ('10', 'ABM - 12A', '07:30:00', '17:00:00', 'Monday, Wednesday', '1', '2025-09-09 14:38:42', '2025-09-09 14:38:42');
INSERT INTO `class_schedules` VALUES ('12', 'BSIT - 601', '00:00:00', '23:59:59', 'Monday, Tuesday, Wednesday, Sunday', '1', '2025-09-09 14:48:24', '2025-09-30 10:17:25');
INSERT INTO `class_schedules` VALUES ('18', 'dfgdfg', '17:56:00', '05:56:00', 'Friday', '10', '2025-09-21 17:56:56', '2025-09-21 17:56:56');
INSERT INTO `class_schedules` VALUES ('20', 'DSFSDF', '18:04:00', '06:04:00', 'Tuesday', '10', '2025-09-21 18:04:31', '2025-09-21 18:04:31');
INSERT INTO `class_schedules` VALUES ('22', 'GHJGHJ', '18:05:00', '06:05:00', 'Friday', '10', '2025-09-21 18:05:37', '2025-09-21 18:05:37');
INSERT INTO `class_schedules` VALUES ('23', 'BSIT - 603', '19:35:00', '07:34:00', 'Monday', '10', '2025-09-21 19:34:19', '2025-09-21 19:34:19');
INSERT INTO `class_schedules` VALUES ('24', 'ijn', '00:00:00', '23:59:59', 'Monday', '10', '2025-09-21 21:05:13', '2025-09-21 21:05:13');
INSERT INTO `class_schedules` VALUES ('25', 'BSIT - 702', '00:00:00', '23:59:59', 'Monday, Tuesday, Wednesday', '10', '2025-09-21 21:18:36', '2025-09-24 11:20:21');
INSERT INTO `class_schedules` VALUES ('26', 'sdawd', '11:28:00', '23:28:00', 'Wednesday', '10', '2025-09-22 11:28:08', '2025-09-22 11:28:08');
INSERT INTO `class_schedules` VALUES ('27', 'ghfhcvbcvbcvb', '11:28:00', '23:28:00', 'Monday', '10', '2025-09-22 11:28:14', '2025-09-22 11:28:14');
INSERT INTO `class_schedules` VALUES ('28', 'cvbcvbcvb', '11:28:00', '11:28:00', 'Monday', '10', '2025-09-22 11:28:21', '2025-09-22 11:28:21');
INSERT INTO `class_schedules` VALUES ('29', 'iopiopiop', '11:28:00', '23:28:00', 'Monday', '10', '2025-09-22 11:28:28', '2025-09-22 11:28:28');
INSERT INTO `class_schedules` VALUES ('30', 'kl;kl;kl;', '11:28:00', '23:28:00', 'Monday', '10', '2025-09-22 11:28:37', '2025-09-22 11:28:37');
INSERT INTO `class_schedules` VALUES ('31', 'kl;kl;kl;', '11:28:00', '23:28:00', 'Tuesday', '10', '2025-09-22 11:28:48', '2025-09-22 11:28:48');
INSERT INTO `class_schedules` VALUES ('32', 'rtrtrtrt', '11:28:00', '23:28:00', 'Monday', '10', '2025-09-22 11:28:55', '2025-09-22 11:28:55');
INSERT INTO `class_schedules` VALUES ('34', 'dfgdfg', '01:21:00', '11:26:00', 'Wednesday', '10', '2025-09-22 13:21:08', '2025-09-24 11:24:54');


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
  CONSTRAINT `day_schedules_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `class_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=152 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: day_schedules
INSERT INTO `day_schedules` VALUES ('103', '2', 'Monday', '08:00:00', '11:00:00');
INSERT INTO `day_schedules` VALUES ('104', '2', 'Tuesday', '06:00:00', '20:58:00');
INSERT INTO `day_schedules` VALUES ('105', '2', 'Wednesday', '10:00:00', '17:00:00');
INSERT INTO `day_schedules` VALUES ('106', '2', 'Thursday', '11:00:00', '15:55:00');
INSERT INTO `day_schedules` VALUES ('107', '2', 'Friday', '08:00:00', '13:00:00');
INSERT INTO `day_schedules` VALUES ('108', '2', 'Saturday', '08:00:00', '13:00:00');
INSERT INTO `day_schedules` VALUES ('109', '2', 'Sunday', '01:04:00', '02:00:00');
INSERT INTO `day_schedules` VALUES ('129', '24', 'Monday', '21:07:00', '21:06:00');
INSERT INTO `day_schedules` VALUES ('145', '25', 'Monday', '23:18:00', '21:18:00');
INSERT INTO `day_schedules` VALUES ('146', '25', 'Tuesday', '21:20:00', '21:19:00');
INSERT INTO `day_schedules` VALUES ('147', '25', 'Wednesday', '11:19:00', '11:21:00');
INSERT INTO `day_schedules` VALUES ('148', '12', 'Monday', '16:47:00', '20:47:00');
INSERT INTO `day_schedules` VALUES ('149', '12', 'Tuesday', '11:17:00', '22:21:00');
INSERT INTO `day_schedules` VALUES ('150', '12', 'Wednesday', '07:48:00', '05:48:00');
INSERT INTO `day_schedules` VALUES ('151', '12', 'Sunday', '03:02:00', '15:02:00');


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

-- Data for table: deletion_log
INSERT INTO `deletion_log` VALUES ('1', 'restore', '02000645645', 'student_account', 'Principal Owner', '2025-09-30 07:52:17', 'Record restored by Super Admin', '{\"id\":2,\"lrn\":\"4645645\",\"academic_track\":\"GAS\",\"enrollment_status\":\"OLD\",\"school_type\":\"\",\"last_name\":\"Sunico\",\"first_name\":\"Hayes\",\"middle_name\":\"\",\"school_year\":\"76867876\",\"grade_level\":\"Grade 11\",\"semester\":\"1st\",\"dob\":\"2025-09-01\",\"birthplace\":\"hgjghj\",\"gender\":\"Male\",\"religion\":\"ghjhgj\",\"credentials\":\"F-138,ESC Certification\",\"payment_mode\":\"Cash\",\"address\":\"ghjgh\",\"father_name\":\"ghj\",\"father_occupation\":\"ghj\",\"father_contact\":\"678\",\"mother_name\":\"jghjgh\",\"mother_occupation\":\"ghjgh\",\"mother_contact\":\"687\",\"guardian_name\":\"jghjghj\",\"guardian_occupation\":\"jgh\",\"guardian_contact\":\"678\",\"last_school\":\"hjk\",\"last_school_year\":\"678\",\"id_number\":\"02000645645\",\"username\":\"hayes\",\"password\":\"$2y$10$HfOi6JzYBPQb2WuhjOVyLOMl3fobQ7YCgFOFe9C8rum30TLqWlJ7W\",\"rfid_uid\":\"0095310074\",\"created_at\":\"2025-09-01 00:22:20\",\"class_schedule\":null,\"deleted_at\":\"2025-09-30 07:40:39\",\"deleted_by\":\"Test Registrar\",\"deleted_reason\":\"Test deletion for demonstration\"}');
INSERT INTO `deletion_log` VALUES ('2', 'restore', '02000534645', 'student_account', 'Principal Owner', '2025-09-30 08:28:50', 'Record restored by Super Admin', '{\"id\":3,\"lrn\":\"456456\",\"academic_track\":\"Junior High School\",\"enrollment_status\":\"OLD\",\"school_type\":\"\",\"last_name\":\"De Jesus\",\"first_name\":\"Liam\",\"middle_name\":\"\",\"school_year\":\"234234\",\"grade_level\":\"Grade 8\",\"semester\":\"1st\",\"dob\":\"2025-09-01\",\"birthplace\":\"asdas\",\"gender\":\"\",\"religion\":\"sdfsdf\",\"credentials\":\"F-138,ESC Certification\",\"payment_mode\":\"Cash\",\"address\":\"sdf\",\"father_name\":\"dh\",\"father_occupation\":\"fghfgh\",\"father_contact\":\"234\",\"mother_name\":\"dfgh\",\"mother_occupation\":\"sdf\",\"mother_contact\":\"234\",\"guardian_name\":\"sdf\",\"guardian_occupation\":\"sdf\",\"guardian_contact\":\"234\",\"last_school\":\"sdf\",\"last_school_year\":\"234\",\"id_number\":\"02000534645\",\"username\":\"liam\",\"password\":\"$2y$10$EXpo2kYA8JtEthUNiDlG3.3qZ3VkoMEg7NyUVnMFu7UBtdLHYTgyy\",\"rfid_uid\":\"0095560689\",\"created_at\":\"2025-09-01 00:24:44\",\"class_schedule\":\"BSIT - 601 (Variable Times)\",\"deleted_at\":\"2025-09-30 08:28:35\",\"deleted_by\":\"Debug Test\",\"deleted_reason\":\"Test deletion from debug script\"}');
INSERT INTO `deletion_log` VALUES ('3', 'restore', '02000645645', 'student_account', 'Principal Owner', '2025-09-30 08:40:24', 'Record restored by Super Admin', '{\"id\":2,\"lrn\":\"4645645\",\"academic_track\":\"GAS\",\"enrollment_status\":\"OLD\",\"school_type\":\"\",\"last_name\":\"Sunico\",\"first_name\":\"Hayes\",\"middle_name\":\"\",\"school_year\":\"76867876\",\"grade_level\":\"Grade 11\",\"semester\":\"1st\",\"dob\":\"2025-09-01\",\"birthplace\":\"hgjghj\",\"gender\":\"Male\",\"religion\":\"ghjhgj\",\"credentials\":\"F-138,ESC Certification\",\"payment_mode\":\"Cash\",\"address\":\"ghjgh\",\"father_name\":\"ghj\",\"father_occupation\":\"ghj\",\"father_contact\":\"678\",\"mother_name\":\"jghjgh\",\"mother_occupation\":\"ghjgh\",\"mother_contact\":\"687\",\"guardian_name\":\"jghjghj\",\"guardian_occupation\":\"jgh\",\"guardian_contact\":\"678\",\"last_school\":\"hjk\",\"last_school_year\":\"678\",\"id_number\":\"02000645645\",\"username\":\"hayes\",\"password\":\"$2y$10$HfOi6JzYBPQb2WuhjOVyLOMl3fobQ7YCgFOFe9C8rum30TLqWlJ7W\",\"rfid_uid\":\"0095310074\",\"created_at\":\"2025-09-01 00:22:20\",\"class_schedule\":null,\"deleted_at\":\"2025-09-30 08:32:11\",\"deleted_by\":\"Debug Test\",\"deleted_reason\":\"Test deletion from debug script\"}');
INSERT INTO `deletion_log` VALUES ('4', 'restore', '02000307705', 'student_account', 'Principal Owner', '2025-09-30 08:40:27', 'Record restored by Super Admin', '{\"id\":1,\"lrn\":\"45645645645\",\"academic_track\":\"BS Computer Science\",\"enrollment_status\":\"OLD\",\"school_type\":\"\",\"last_name\":\"Go\",\"first_name\":\"Gesterd\",\"middle_name\":\"Gaon\",\"school_year\":\"2025-2026\",\"grade_level\":\"4th Year\",\"semester\":\"1st\",\"dob\":\"2003-05-24\",\"birthplace\":\"caloocan\",\"gender\":\"\",\"religion\":\"catholic\",\"credentials\":\"\",\"payment_mode\":\"Cash\",\"address\":\"university heights\",\"father_name\":\"Alexander Go\",\"father_occupation\":\"Father\",\"father_contact\":\"091234324\",\"mother_name\":\"Gloria Gaon\",\"mother_occupation\":\"Mother\",\"mother_contact\":\"0985345345\",\"guardian_name\":\"Alexander Go\",\"guardian_occupation\":\"Father\",\"guardian_contact\":\"09345345\",\"last_school\":\"Sti SJDM\",\"last_school_year\":\"2024-2025\",\"id_number\":\"02000307705\",\"username\":\"gesterd\",\"password\":\"$2y$10$d4lnh3Rc8oTMe.zK5g1Zz.ptW0Y7cmadaUQtYhMiAuaHWfQKqc9L2\",\"rfid_uid\":\"0095105805\",\"created_at\":\"2025-08-31 18:01:23\",\"class_schedule\":\"ABM - 12A (7:30 AM - 5:00 PM)\",\"deleted_at\":\"2025-09-30 08:32:08\",\"deleted_by\":\"Debug Test\",\"deleted_reason\":\"Test deletion from debug script\"}');
INSERT INTO `deletion_log` VALUES ('5', 'restore', '02000534645', 'student_account', 'Principal Owner', '2025-09-30 08:40:36', 'Record restored by Super Admin', '{\"id\":3,\"lrn\":\"456456\",\"academic_track\":\"Junior High School\",\"enrollment_status\":\"OLD\",\"school_type\":\"\",\"last_name\":\"De Jesus\",\"first_name\":\"Liam\",\"middle_name\":\"\",\"school_year\":\"234234\",\"grade_level\":\"Grade 8\",\"semester\":\"1st\",\"dob\":\"2025-09-01\",\"birthplace\":\"asdas\",\"gender\":\"\",\"religion\":\"sdfsdf\",\"credentials\":\"F-138,ESC Certification\",\"payment_mode\":\"Cash\",\"address\":\"sdf\",\"father_name\":\"dh\",\"father_occupation\":\"fghfgh\",\"father_contact\":\"234\",\"mother_name\":\"dfgh\",\"mother_occupation\":\"sdf\",\"mother_contact\":\"234\",\"guardian_name\":\"sdf\",\"guardian_occupation\":\"sdf\",\"guardian_contact\":\"234\",\"last_school\":\"sdf\",\"last_school_year\":\"234\",\"id_number\":\"02000534645\",\"username\":\"liam\",\"password\":\"$2y$10$EXpo2kYA8JtEthUNiDlG3.3qZ3VkoMEg7NyUVnMFu7UBtdLHYTgyy\",\"rfid_uid\":\"0095560689\",\"created_at\":\"2025-09-01 00:24:44\",\"class_schedule\":\"BSIT - 601 (Variable Times)\",\"deleted_at\":\"2025-09-30 08:30:58\",\"deleted_by\":\"Debug Test\",\"deleted_reason\":\"Test deletion from debug script\"}');
INSERT INTO `deletion_log` VALUES ('6', 'restore', '0912384353453', 'student_account', 'Principal Owner', '2025-09-30 08:43:13', 'Record restored by Super Admin', '{\"id\":6,\"lrn\":\"567567\",\"academic_track\":\"Junior High School\",\"enrollment_status\":\"OLD\",\"school_type\":\"\",\"last_name\":\"jmghmj\",\"first_name\":\"gmmj\",\"middle_name\":\"ghjmgh\",\"school_year\":\"65567\",\"grade_level\":\"Grade 8\",\"semester\":\"1st\",\"dob\":\"2025-09-05\",\"birthplace\":\"hgjghmghj\",\"gender\":\"Male\",\"religion\":\"ghjmgmjgm\",\"credentials\":\"F-138\",\"payment_mode\":\"Cash\",\"address\":\"gmhjmghjmghj\",\"father_name\":\"jmghjmgh\",\"father_occupation\":\"gmhjmg\",\"father_contact\":\"867867\",\"mother_name\":\"mgjhm\",\"mother_occupation\":\"ghmgj\",\"mother_contact\":\"678678\",\"guardian_name\":\"mhjgmjg\",\"guardian_occupation\":\"gmhj\",\"guardian_contact\":\"67867\",\"last_school\":\"kjhkjhkhkj\",\"last_school_year\":\"6786786\",\"id_number\":\"0912384353453\",\"username\":\"tejada\",\"password\":\"$2y$10$oQafKwiycTrCmEa9vsb96..yk3zl3.j4GgpNu3Nqza\\/pA1H1Cyfu6\",\"rfid_uid\":\"9834759837459834\",\"created_at\":\"2025-09-19 02:52:43\",\"class_schedule\":\"dfgdfg (1:21 AM - 11:26 AM)\",\"deleted_at\":\"2025-09-30 08:32:24\",\"deleted_by\":\"Debug Test\",\"deleted_reason\":\"Test deletion from debug script\"}');
INSERT INTO `deletion_log` VALUES ('7', 'restore', '67867867866666786786', 'student_account', 'Principal Owner', '2025-09-30 08:43:15', 'Record restored by Super Admin', '{\"id\":5,\"lrn\":\"234234234\",\"academic_track\":\"Elementary\",\"enrollment_status\":\"OLD\",\"school_type\":\"\",\"last_name\":\"dfgdfgd\",\"first_name\":\"dfgdfg\",\"middle_name\":\"fgdfgdfgdg\",\"school_year\":\"2023-2024\",\"grade_level\":\"Grade 4\",\"semester\":\"1st\",\"dob\":\"2025-09-10\",\"birthplace\":\"asdawdasd\",\"gender\":\"Male\",\"religion\":\"dgf\",\"credentials\":\"F-138,PSA Birth\",\"payment_mode\":\"Installment\",\"address\":\"dfgdfg\",\"father_name\":\"dfgdfg\",\"father_occupation\":\"dfgdfg\",\"father_contact\":\"345345\",\"mother_name\":\"dfg\",\"mother_occupation\":\"dfgdf\",\"mother_contact\":\"34534\",\"guardian_name\":\"gdfg\",\"guardian_occupation\":\"gdfgdf\",\"guardian_contact\":\"53453\",\"last_school\":\"dfgdfgdf\",\"last_school_year\":\"345345345\",\"id_number\":\"67867867866666786786\",\"username\":\"sunico\",\"password\":\"$2y$10$QH9xs3BDsq87IySsAwz0D.ju2g9BvpJKE.27oIY6M0R6mew9znyEy\",\"rfid_uid\":\"49586094586094850698\",\"created_at\":\"2025-09-19 02:45:44\",\"class_schedule\":null,\"deleted_at\":\"2025-09-30 08:32:19\",\"deleted_by\":\"Debug Test\",\"deleted_reason\":\"Test deletion from debug script\"}');
INSERT INTO `deletion_log` VALUES ('8', 'restore', '80374985739845345', 'student_account', 'Principal Owner', '2025-09-30 08:43:25', 'Record restored by Super Admin', '{\"id\":4,\"lrn\":\"234234234\",\"academic_track\":\"Elementary\",\"enrollment_status\":\"OLD\",\"school_type\":\"\",\"last_name\":\"De jes\",\"first_name\":\"dfgdfg\",\"middle_name\":\"fgdfgdfgdg\",\"school_year\":\"2023-2024\",\"grade_level\":\"Grade 4\",\"semester\":\"1st\",\"dob\":\"2025-09-10\",\"birthplace\":\"asdawdasd\",\"gender\":\"\",\"religion\":\"dgf\",\"credentials\":\"F-138,PSA Birth\",\"payment_mode\":\"Installment\",\"address\":\"dfgdfg\",\"father_name\":\"dfgdfg\",\"father_occupation\":\"dfgdfg\",\"father_contact\":\"345345\",\"mother_name\":\"dfg\",\"mother_occupation\":\"dfgdf\",\"mother_contact\":\"34534\",\"guardian_name\":\"gdfg\",\"guardian_occupation\":\"gdfgdf\",\"guardian_contact\":\"53453\",\"last_school\":\"dfgdfgdf\",\"last_school_year\":\"345345345\",\"id_number\":\"80374985739845345\",\"username\":\"go\",\"password\":\"$2y$10$MnV9dLRO.CDBtqBH8LyMIeMOlo5NqW\\/5SDmEhWxuCtNr8e\\/5hdwpK\",\"rfid_uid\":\"3453453453\",\"created_at\":\"2025-09-19 02:30:24\",\"class_schedule\":null,\"deleted_at\":\"2025-09-30 08:32:15\",\"deleted_by\":\"Debug Test\",\"deleted_reason\":\"Test deletion from debug script\"}');
INSERT INTO `deletion_log` VALUES ('9', 'restore', 'S2025006', 'student_account', 'Principal Owner', '2025-09-30 08:47:51', 'Record restored by Super Admin', '{\"id\":12,\"lrn\":\"LRN2025006\",\"academic_track\":\"ABM\",\"enrollment_status\":\"NEW\",\"school_type\":\"PRIVATE\",\"last_name\":\"Mendoza\",\"first_name\":\"Carla\",\"middle_name\":\"S\",\"school_year\":\"2025-2026\",\"grade_level\":\"11-B\",\"semester\":\"1st\",\"dob\":\"2008-10-10\",\"birthplace\":\"Batangas\",\"gender\":\"Female\",\"religion\":\"Catholic\",\"credentials\":null,\"payment_mode\":\"Cash\",\"address\":\"Batangas, PH\",\"father_name\":\"Jun Mendoza\",\"father_occupation\":\"Farmer\",\"father_contact\":\"09171234506\",\"mother_name\":\"Leni Mendoza\",\"mother_occupation\":\"Nurse\",\"mother_contact\":\"09181234506\",\"guardian_name\":\"N\\/A\",\"guardian_occupation\":\"N\\/A\",\"guardian_contact\":\"N\\/A\",\"last_school\":\"Batangas HS\",\"last_school_year\":\"2024-2025\",\"id_number\":\"S2025006\",\"username\":\"carla_m\",\"password\":\"$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi\",\"rfid_uid\":\"RFID001F\",\"created_at\":\"2025-09-21 16:45:55\",\"class_schedule\":\"dfgdfg (5:56 PM - 5:56 AM)\",\"deleted_at\":\"2025-09-30 08:47:45\",\"deleted_by\":\"Registrar Admin\",\"deleted_reason\":\"Deleted by registrar for administrative purposes\"}');
INSERT INTO `deletion_log` VALUES ('10', 'restore', 'S2025015', 'student_account', 'Principal Owner', '2025-09-30 08:48:41', 'Record restored by Super Admin', '{\"id\":21,\"lrn\":\"LRN2025015\",\"academic_track\":\"STEM\",\"enrollment_status\":\"NEW\",\"school_type\":\"PRIVATE\",\"last_name\":\"Rivera\",\"first_name\":\"Bea\",\"middle_name\":\"B\",\"school_year\":\"2025-2026\",\"grade_level\":\"12-B\",\"semester\":\"1st\",\"dob\":\"2007-09-17\",\"birthplace\":\"Cavite\",\"gender\":\"Female\",\"religion\":\"Catholic\",\"credentials\":null,\"payment_mode\":\"Installment\",\"address\":\"Cavite, PH\",\"father_name\":\"Ben Rivera\",\"father_occupation\":\"Sales\",\"father_contact\":\"09170000015\",\"mother_name\":\"Anne Rivera\",\"mother_occupation\":\"Cashier\",\"mother_contact\":\"09180000015\",\"guardian_name\":\"N\\/A\",\"guardian_occupation\":\"N\\/A\",\"guardian_contact\":\"N\\/A\",\"last_school\":\"Cavite HS\",\"last_school_year\":\"2024-2025\",\"id_number\":\"S2025015\",\"username\":\"bea_r\",\"password\":\"$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi\",\"rfid_uid\":\"RFID001O\",\"created_at\":\"2025-09-21 17:33:43\",\"class_schedule\":\"BSIT - 702 (Variable Times)\",\"deleted_at\":\"2025-09-30 08:47:35\",\"deleted_by\":\"Registrar Admin\",\"deleted_reason\":\"Deleted by registrar for administrative purposes\"}');
INSERT INTO `deletion_log` VALUES ('11', 'restore', '02000534645', 'student_account', 'Principal Owner', '2025-09-30 08:48:43', 'Record restored by Super Admin', '{\"id\":3,\"lrn\":\"456456\",\"academic_track\":\"Junior High School\",\"enrollment_status\":\"OLD\",\"school_type\":\"\",\"last_name\":\"De Jesus\",\"first_name\":\"Liam\",\"middle_name\":\"\",\"school_year\":\"234234\",\"grade_level\":\"Grade 8\",\"semester\":\"1st\",\"dob\":\"2025-09-01\",\"birthplace\":\"asdas\",\"gender\":\"\",\"religion\":\"sdfsdf\",\"credentials\":\"F-138,ESC Certification\",\"payment_mode\":\"Cash\",\"address\":\"sdf\",\"father_name\":\"dh\",\"father_occupation\":\"fghfgh\",\"father_contact\":\"234\",\"mother_name\":\"dfgh\",\"mother_occupation\":\"sdf\",\"mother_contact\":\"234\",\"guardian_name\":\"sdf\",\"guardian_occupation\":\"sdf\",\"guardian_contact\":\"234\",\"last_school\":\"sdf\",\"last_school_year\":\"234\",\"id_number\":\"02000534645\",\"username\":\"liam\",\"password\":\"$2y$10$EXpo2kYA8JtEthUNiDlG3.3qZ3VkoMEg7NyUVnMFu7UBtdLHYTgyy\",\"rfid_uid\":\"0095560689\",\"created_at\":\"2025-09-01 00:24:44\",\"class_schedule\":\"BSIT - 601 (Variable Times)\",\"deleted_at\":\"2025-09-30 08:47:01\",\"deleted_by\":\"Test Script\",\"deleted_reason\":\"Test deletion\"}');
INSERT INTO `deletion_log` VALUES ('12', 'restore', '02000307705', 'student_account', 'Principal Owner', '2025-09-30 08:48:46', 'Record restored by Super Admin', '{\"id\":1,\"lrn\":\"45645645645\",\"academic_track\":\"BS Computer Science\",\"enrollment_status\":\"OLD\",\"school_type\":\"\",\"last_name\":\"Go\",\"first_name\":\"Gesterd\",\"middle_name\":\"Gaon\",\"school_year\":\"2025-2026\",\"grade_level\":\"4th Year\",\"semester\":\"1st\",\"dob\":\"2003-05-24\",\"birthplace\":\"caloocan\",\"gender\":\"\",\"religion\":\"catholic\",\"credentials\":\"\",\"payment_mode\":\"Cash\",\"address\":\"university heights\",\"father_name\":\"Alexander Go\",\"father_occupation\":\"Father\",\"father_contact\":\"091234324\",\"mother_name\":\"Gloria Gaon\",\"mother_occupation\":\"Mother\",\"mother_contact\":\"0985345345\",\"guardian_name\":\"Alexander Go\",\"guardian_occupation\":\"Father\",\"guardian_contact\":\"09345345\",\"last_school\":\"Sti SJDM\",\"last_school_year\":\"2024-2025\",\"id_number\":\"02000307705\",\"username\":\"gesterd\",\"password\":\"$2y$10$d4lnh3Rc8oTMe.zK5g1Zz.ptW0Y7cmadaUQtYhMiAuaHWfQKqc9L2\",\"rfid_uid\":\"0095105805\",\"created_at\":\"2025-08-31 18:01:23\",\"class_schedule\":\"ABM - 12A (7:30 AM - 5:00 PM)\",\"deleted_at\":\"2025-09-30 08:46:42\",\"deleted_by\":\"Test Script\",\"deleted_reason\":\"Test deletion\"}');
INSERT INTO `deletion_log` VALUES ('13', 'restore', '67867867866666786786', 'student_account', 'Principal Owner', '2025-09-30 08:53:47', 'Record restored by Super Admin', '{\"id\":5,\"lrn\":\"234234234\",\"academic_track\":\"Elementary\",\"enrollment_status\":\"OLD\",\"school_type\":\"\",\"last_name\":\"dfgdfgd\",\"first_name\":\"dfgdfg\",\"middle_name\":\"fgdfgdfgdg\",\"school_year\":\"2023-2024\",\"grade_level\":\"Grade 4\",\"semester\":\"1st\",\"dob\":\"2025-09-10\",\"birthplace\":\"asdawdasd\",\"gender\":\"Male\",\"religion\":\"dgf\",\"credentials\":\"F-138,PSA Birth\",\"payment_mode\":\"Installment\",\"address\":\"dfgdfg\",\"father_name\":\"dfgdfg\",\"father_occupation\":\"dfgdfg\",\"father_contact\":\"345345\",\"mother_name\":\"dfg\",\"mother_occupation\":\"dfgdf\",\"mother_contact\":\"34534\",\"guardian_name\":\"gdfg\",\"guardian_occupation\":\"gdfgdf\",\"guardian_contact\":\"53453\",\"last_school\":\"dfgdfgdf\",\"last_school_year\":\"345345345\",\"id_number\":\"67867867866666786786\",\"username\":\"sunico\",\"password\":\"$2y$10$QH9xs3BDsq87IySsAwz0D.ju2g9BvpJKE.27oIY6M0R6mew9znyEy\",\"rfid_uid\":\"49586094586094850698\",\"created_at\":\"2025-09-19 02:45:44\",\"class_schedule\":null,\"deleted_at\":\"2025-09-30 08:53:37\",\"deleted_by\":\"Registrar Admin\",\"deleted_reason\":\"Deleted by registrar for administrative purposes\"}');
INSERT INTO `deletion_log` VALUES ('14', 'restore', 'S2025006', 'student_account', 'Principal Owner', '2025-09-30 08:53:49', 'Record restored by Super Admin', '{\"id\":12,\"lrn\":\"LRN2025006\",\"academic_track\":\"ABM\",\"enrollment_status\":\"NEW\",\"school_type\":\"PRIVATE\",\"last_name\":\"Mendoza\",\"first_name\":\"Carla\",\"middle_name\":\"S\",\"school_year\":\"2025-2026\",\"grade_level\":\"11-B\",\"semester\":\"1st\",\"dob\":\"2008-10-10\",\"birthplace\":\"Batangas\",\"gender\":\"Female\",\"religion\":\"Catholic\",\"credentials\":null,\"payment_mode\":\"Cash\",\"address\":\"Batangas, PH\",\"father_name\":\"Jun Mendoza\",\"father_occupation\":\"Farmer\",\"father_contact\":\"09171234506\",\"mother_name\":\"Leni Mendoza\",\"mother_occupation\":\"Nurse\",\"mother_contact\":\"09181234506\",\"guardian_name\":\"N\\/A\",\"guardian_occupation\":\"N\\/A\",\"guardian_contact\":\"N\\/A\",\"last_school\":\"Batangas HS\",\"last_school_year\":\"2024-2025\",\"id_number\":\"S2025006\",\"username\":\"carla_m\",\"password\":\"$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi\",\"rfid_uid\":\"RFID001F\",\"created_at\":\"2025-09-21 16:45:55\",\"class_schedule\":\"dfgdfg (5:56 PM - 5:56 AM)\",\"deleted_at\":\"2025-09-30 08:52:03\",\"deleted_by\":\"Registrar Admin\",\"deleted_reason\":\"Deleted by registrar for administrative purposes\"}');
INSERT INTO `deletion_log` VALUES ('15', 'restore', 'S2025015', 'student_account', 'Principal Owner', '2025-09-30 08:53:54', 'Record restored by Super Admin', '{\"id\":21,\"lrn\":\"LRN2025015\",\"academic_track\":\"STEM\",\"enrollment_status\":\"NEW\",\"school_type\":\"PRIVATE\",\"last_name\":\"Rivera\",\"first_name\":\"Bea\",\"middle_name\":\"B\",\"school_year\":\"2025-2026\",\"grade_level\":\"12-B\",\"semester\":\"1st\",\"dob\":\"2007-09-17\",\"birthplace\":\"Cavite\",\"gender\":\"Female\",\"religion\":\"Catholic\",\"credentials\":null,\"payment_mode\":\"Installment\",\"address\":\"Cavite, PH\",\"father_name\":\"Ben Rivera\",\"father_occupation\":\"Sales\",\"father_contact\":\"09170000015\",\"mother_name\":\"Anne Rivera\",\"mother_occupation\":\"Cashier\",\"mother_contact\":\"09180000015\",\"guardian_name\":\"N\\/A\",\"guardian_occupation\":\"N\\/A\",\"guardian_contact\":\"N\\/A\",\"last_school\":\"Cavite HS\",\"last_school_year\":\"2024-2025\",\"id_number\":\"S2025015\",\"username\":\"bea_r\",\"password\":\"$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi\",\"rfid_uid\":\"RFID001O\",\"created_at\":\"2025-09-21 17:33:43\",\"class_schedule\":\"BSIT - 702 (Variable Times)\",\"deleted_at\":\"2025-09-30 08:51:53\",\"deleted_by\":\"Registrar Admin\",\"deleted_reason\":\"Deleted by registrar for administrative purposes\"}');
INSERT INTO `deletion_log` VALUES ('16', 'restore', 'S2025015', 'student_account', 'Principal Owner', '2025-09-30 08:54:13', 'Record restored by Super Admin', '{\"id\":21,\"lrn\":\"LRN2025015\",\"academic_track\":\"STEM\",\"enrollment_status\":\"NEW\",\"school_type\":\"PRIVATE\",\"last_name\":\"Rivera\",\"first_name\":\"Bea\",\"middle_name\":\"B\",\"school_year\":\"2025-2026\",\"grade_level\":\"12-B\",\"semester\":\"1st\",\"dob\":\"2007-09-17\",\"birthplace\":\"Cavite\",\"gender\":\"Female\",\"religion\":\"Catholic\",\"credentials\":null,\"payment_mode\":\"Installment\",\"address\":\"Cavite, PH\",\"father_name\":\"Ben Rivera\",\"father_occupation\":\"Sales\",\"father_contact\":\"09170000015\",\"mother_name\":\"Anne Rivera\",\"mother_occupation\":\"Cashier\",\"mother_contact\":\"09180000015\",\"guardian_name\":\"N\\/A\",\"guardian_occupation\":\"N\\/A\",\"guardian_contact\":\"N\\/A\",\"last_school\":\"Cavite HS\",\"last_school_year\":\"2024-2025\",\"id_number\":\"S2025015\",\"username\":\"bea_r\",\"password\":\"$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi\",\"rfid_uid\":\"RFID001O\",\"created_at\":\"2025-09-21 17:33:43\",\"class_schedule\":\"BSIT - 702 (Variable Times)\",\"deleted_at\":\"2025-09-30 08:54:08\",\"deleted_by\":\"Registrar Admin\",\"deleted_reason\":\"Deleted by registrar for administrative purposes\"}');
INSERT INTO `deletion_log` VALUES ('17', 'restore', 'S2025006', 'student_account', 'Principal Owner', '2025-09-30 08:54:23', 'Record restored by Super Admin', '{\"id\":12,\"lrn\":\"LRN2025006\",\"academic_track\":\"ABM\",\"enrollment_status\":\"NEW\",\"school_type\":\"PRIVATE\",\"last_name\":\"Mendoza\",\"first_name\":\"Carla\",\"middle_name\":\"S\",\"school_year\":\"2025-2026\",\"grade_level\":\"11-B\",\"semester\":\"1st\",\"dob\":\"2008-10-10\",\"birthplace\":\"Batangas\",\"gender\":\"Female\",\"religion\":\"Catholic\",\"credentials\":null,\"payment_mode\":\"Cash\",\"address\":\"Batangas, PH\",\"father_name\":\"Jun Mendoza\",\"father_occupation\":\"Farmer\",\"father_contact\":\"09171234506\",\"mother_name\":\"Leni Mendoza\",\"mother_occupation\":\"Nurse\",\"mother_contact\":\"09181234506\",\"guardian_name\":\"N\\/A\",\"guardian_occupation\":\"N\\/A\",\"guardian_contact\":\"N\\/A\",\"last_school\":\"Batangas HS\",\"last_school_year\":\"2024-2025\",\"id_number\":\"S2025006\",\"username\":\"carla_m\",\"password\":\"$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi\",\"rfid_uid\":\"RFID001F\",\"created_at\":\"2025-09-21 16:45:55\",\"class_schedule\":\"dfgdfg (5:56 PM - 5:56 AM)\",\"deleted_at\":\"2025-09-30 08:54:17\",\"deleted_by\":\"Registrar Admin\",\"deleted_reason\":\"Deleted by registrar for administrative purposes\"}');
INSERT INTO `deletion_log` VALUES ('18', 'restore', '34534534534', 'student_account', 'Principal Owner', '2025-09-30 09:03:59', 'Record restored by Super Admin', '{\"id\":38,\"lrn\":\"678678678678\",\"academic_track\":\"STEM\",\"enrollment_status\":\"OLD\",\"school_type\":\"\",\"last_name\":\"Go\",\"first_name\":\"Gilbert\",\"middle_name\":\"jhkhjkhjkhjk\",\"school_year\":\"2025-2026\",\"grade_level\":\"Grade 12\",\"semester\":\"1st\",\"dob\":\"2025-09-30\",\"birthplace\":\"gfdgdfgdfg\",\"gender\":\"Male\",\"religion\":\"dfgdfgdfg\",\"credentials\":\"F-138\",\"payment_mode\":\"Cash\",\"address\":\"dfgdfg\",\"father_name\":\"dfgdfg\",\"father_occupation\":\"dfgdfg\",\"father_contact\":\"45345546456\",\"mother_name\":\"dfgdfg\",\"mother_occupation\":\"dfg\",\"mother_contact\":\"64564564565\",\"guardian_name\":\"dfgdf\",\"guardian_occupation\":\"dfgdfg\",\"guardian_contact\":\"45645645645\",\"last_school\":\"gdddgfdfgdf\",\"last_school_year\":\"2024-2025\",\"id_number\":\"34534534534\",\"username\":\"fsdfsefsdf\",\"password\":\"$2y$10$lZScYwk8DnM\\/c49uWb2hN.HLcOpB.GYfLhKQEPKZ\\/EmkCDeR4KbRW\",\"rfid_uid\":\"4634534534\",\"created_at\":\"2025-09-30 09:03:00\",\"class_schedule\":null,\"deleted_at\":\"2025-09-30 09:03:05\",\"deleted_by\":\"Registrar Admin\",\"deleted_reason\":\"Deleted by registrar for administrative purposes\"}');
INSERT INTO `deletion_log` VALUES ('19', 'restore', '345345345345', 'employees', 'Principal Owner', '2025-09-30 09:13:21', 'Record restored by Super Admin', '{\"id\":41,\"id_number\":\"345345345345\",\"first_name\":\"Lance\",\"middle_name\":null,\"last_name\":\"Cereno\",\"position\":\"asdawdasd\",\"department\":\"Finance\",\"email\":\"gfesfsf@gmail.com\",\"phone\":\"54645645645\",\"address\":null,\"created_at\":\"2025-09-26 10:16:24\",\"hire_date\":\"2025-09-26\",\"rfid_uid\":\"0095347257\",\"deleted_at\":\"2025-09-30 09:13:07\",\"deleted_by\":\"HR Administrator\",\"deleted_reason\":\"Deleted by HR for administrative purposes\"}');
INSERT INTO `deletion_log` VALUES ('20', 'restore', 'S2025006', 'student_account', 'Super Admin', '2025-09-30 12:31:19', 'Record restored by Super Admin', '{\"id\":12,\"lrn\":\"LRN2025006\",\"academic_track\":\"ABM\",\"enrollment_status\":\"NEW\",\"school_type\":\"PRIVATE\",\"last_name\":\"Mendoza\",\"first_name\":\"Carla\",\"middle_name\":\"S\",\"school_year\":\"2025-2026\",\"grade_level\":\"11-B\",\"semester\":\"1st\",\"dob\":\"2008-10-10\",\"birthplace\":\"Batangas\",\"gender\":\"Female\",\"religion\":\"Catholic\",\"credentials\":null,\"payment_mode\":\"Cash\",\"address\":\"Batangas, PH\",\"father_name\":\"Jun Mendoza\",\"father_occupation\":\"Farmer\",\"father_contact\":\"09171234506\",\"mother_name\":\"Leni Mendoza\",\"mother_occupation\":\"Nurse\",\"mother_contact\":\"09181234506\",\"guardian_name\":\"N\\/A\",\"guardian_occupation\":\"N\\/A\",\"guardian_contact\":\"N\\/A\",\"last_school\":\"Batangas HS\",\"last_school_year\":\"2024-2025\",\"id_number\":\"S2025006\",\"username\":\"carla_m\",\"password\":\"$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC\\/.og\\/at2.uheWG\\/igi\",\"rfid_uid\":\"RFID001F\",\"created_at\":\"2025-09-21 16:45:55\",\"class_schedule\":\"dfgdfg (5:56 PM - 5:56 AM)\",\"deleted_at\":\"2025-09-30 10:38:13\",\"deleted_by\":\"Registrar Admin\",\"deleted_reason\":\"Deleted by registrar for administrative purposes\"}');
INSERT INTO `deletion_log` VALUES ('21', 'restore', '71092124378', 'employees', 'Super Admin', '2025-09-30 12:31:22', 'Record restored by Super Admin', '{\"id\":46,\"id_number\":\"71092124378\",\"first_name\":\"talaga\",\"middle_name\":\"asdasdasdas\",\"last_name\":\"dasdasdasd\",\"position\":\"dasdasdas\",\"department\":\"HR\",\"email\":\"sdfsdfsdfsdfsdfsdfsd@gmail.com\",\"phone\":\"54645645645\",\"address\":\"dfgdfgdfgdfgdfg\",\"created_at\":\"2025-09-30 02:50:41\",\"hire_date\":\"2025-09-30\",\"rfid_uid\":null,\"deleted_at\":\"2025-09-30 11:00:14\",\"deleted_by\":\"HR Administrator\",\"deleted_reason\":\"Deleted by HR for administrative purposes\"}');


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
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: document_requests
INSERT INTO `document_requests` VALUES ('60', '02000307705', 'Gesterd Go', 'Form137', 'sad', 'Pending', '2025-10-11 12:05:27', NULL, '1');
INSERT INTO `document_requests` VALUES ('61', '02000307705', 'Gesterd Go', 'Good Moral', 'asd', 'Pending', '2025-10-11 12:05:31', NULL, '1');
INSERT INTO `document_requests` VALUES ('62', '02000307705', 'Gesterd Go', 'weqqweqwe', 'asd', 'Pending', '2025-10-11 12:06:08', NULL, '1');
INSERT INTO `document_requests` VALUES ('63', '02000307705', 'Gesterd Go', 'sdfsdf', 'dsfsdf', 'Pending', '2025-10-11 12:06:11', NULL, '0');
INSERT INTO `document_requests` VALUES ('64', '02000307705', 'Gesterd Go', 'sasadads', 'sdfsd', 'Pending', '2025-10-11 12:06:15', NULL, '0');
INSERT INTO `document_requests` VALUES ('65', '02000307705', 'Gesterd Go', 'sadsad', 'dfgdgf', 'Pending', '2025-10-11 12:06:19', NULL, '0');
INSERT INTO `document_requests` VALUES ('66', '02000307705', 'Gesterd Go', 'gdffgd', 'dfgdgf', 'Pending', '2025-10-11 12:06:23', NULL, '0');
INSERT INTO `document_requests` VALUES ('67', '02000307705', 'Gesterd Go', 'fhghfg', 'fhghfg', 'Pending', '2025-10-11 12:06:27', NULL, '0');
INSERT INTO `document_requests` VALUES ('68', '02000307705', 'Gesterd Go', 'fgddfg', 'jhghgj', 'Pending', '2025-10-11 12:06:31', NULL, '0');
INSERT INTO `document_requests` VALUES ('69', '02000307705', 'Gesterd Go', 'dgfgdf', 'ghjhg', 'Declined', '2025-10-11 12:06:34', NULL, '0');
INSERT INTO `document_requests` VALUES ('70', '02000307705', 'Gesterd Go', 'asdasd', 'dfgdfg', 'Approved', '2025-10-11 12:06:37', NULL, '0');


-- Table: document_types
DROP TABLE IF EXISTS `document_types`;
CREATE TABLE `document_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_requestable` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_submittable` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: document_types
INSERT INTO `document_types` VALUES ('27', 'Form137', '1', '2025-09-25 15:55:47', '0');
INSERT INTO `document_types` VALUES ('29', 'Good Moral', '1', '2025-09-30 14:47:17', '0');
INSERT INTO `document_types` VALUES ('30', 'sdfsdf', '1', '2025-10-11 12:05:49', '0');
INSERT INTO `document_types` VALUES ('37', 'sadsad', '1', '2025-10-11 12:05:56', '0');
INSERT INTO `document_types` VALUES ('38', 'gdffgd', '1', '2025-10-11 12:05:57', '0');
INSERT INTO `document_types` VALUES ('40', 'weqqweqwe', '1', '2025-10-11 12:05:59', '0');
INSERT INTO `document_types` VALUES ('41', 'sasadads', '1', '2025-10-11 12:06:00', '0');


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
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `employee_accounts_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id_number`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: employee_accounts
INSERT INTO `employee_accounts` VALUES ('10', '645645645645', 'Registrar', '$2y$10$1vCQru.1erZps5rhGwEJTeMlG.WW8HO2KsONxsArv/lm2ritgRFzS', 'registrar', '2025-09-15 13:02:25', NULL, NULL, NULL);
INSERT INTO `employee_accounts` VALUES ('11', 'HR001', 'hradmin', '$2y$10$Qd8ZG/bmcfEppTXLcS1hru5DIH9NA24t4NRTngziL04UsvHwlPNbm', 'hr', '2025-09-19 00:36:22', NULL, NULL, NULL);
INSERT INTO `employee_accounts` VALUES ('13', '546464564', 'attendance', '$2y$10$20C2h/eqYjoPujmzfTaQIOKUkwYVIHXggeSuny4lG.JBhVaUEH7Ti', 'attendance', '2025-09-19 04:24:40', NULL, NULL, NULL);
INSERT INTO `employee_accounts` VALUES ('14', '545645645645', 'guidance', '$2y$10$zOCEwsZz9TERMFl5QCSkRe2cBUX/V7ddh3xiCoRNUREV4MdPbFsoe', 'guidance', '2025-09-23 17:28:12', NULL, NULL, NULL);
INSERT INTO `employee_accounts` VALUES ('19', '450789789789789', 'dfgdfgdfgdfg', '$2y$10$v0RL2iL2UjHcOUaGHYm6Ru32EwXSdaAKubmrSMpPkIePnNseXT3gu', 'cashier', '2025-09-25 11:12:07', NULL, NULL, NULL);
INSERT INTO `employee_accounts` VALUES ('47', '34576575675', 'gdfgdfgd', '$2y$10$8G3C44ouhc6jcI/YQ./AJu.1z6zh6mEE2iLPCcnHj1dsDBPfIeVs6', 'hr', '2025-09-30 03:02:54', NULL, NULL, NULL);
INSERT INTO `employee_accounts` VALUES ('49', '71092124374', 'mark', '$2y$10$Px6fxYnzuzaFpXFCRsp.dO3H7BYzVigXyx1jZg0hld2fw0oEl2BKS', 'registrar', '2025-09-30 09:53:56', NULL, NULL, NULL);
INSERT INTO `employee_accounts` VALUES ('51', '345345345345', 'lance', '$2y$10$594cSVFiVPP0VO6PDXsXTeg18suCimsr/P0ATG6fTeAIODyA4TCyO', 'teacher', '2025-09-30 10:47:47', NULL, NULL, NULL);
INSERT INTO `employee_accounts` VALUES ('52', '54353453453', 'cashier', '$2y$10$8sK2V6qUKhgbicAu5Gzug.DyBVSS2Mg4xqq.xYBCGo1WlNeVuAQbu', 'cashier', '2025-09-30 11:07:49', NULL, NULL, NULL);
INSERT INTO `employee_accounts` VALUES ('54', '10000115605', 'herbert', '$2y$10$Cr3qvPdZDS20icZuuG22T.cgYv.7DpfUqs7UWu95b7wi.ZTUilT7O', 'teacher', '2025-09-30 14:14:05', NULL, NULL, NULL);
INSERT INTO `employee_accounts` VALUES ('55', '423423423', 'dfgdfgdfgdfgdfg', '$2y$10$9p7NQqZBCOgYDQo2ONsG8uk643G.6rCHgfVOKXkroVb46aF73rwHO', 'hr', '2025-10-03 23:11:56', NULL, NULL, NULL);
INSERT INTO `employee_accounts` VALUES ('57', '08000012312', 'yeyeng', '$2y$10$AdHQwLxdmlqXbJG2q4n.3eehyx9IFb3klF6Nivw0k9Z46gfWFGlam', 'hr', '2025-10-04 06:20:55', NULL, NULL, NULL);


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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: employee_schedules
INSERT INTO `employee_schedules` VALUES ('32', '10000115605', '11', '0', '2025-10-04 08:51:08', '2025-10-04 08:51:08');
INSERT INTO `employee_schedules` VALUES ('33', '345345345345', '12', '0', '2025-10-04 08:55:33', '2025-10-04 08:55:33');


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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: employee_work_schedules
INSERT INTO `employee_work_schedules` VALUES ('11', 'Herbert Gardener', '08:51:00', '20:51:00', 'Monday', '0', '2025-10-04 08:51:08');
INSERT INTO `employee_work_schedules` VALUES ('12', 'Lance Cereno', '08:57:00', '08:56:00', 'Thursday', '0', '2025-10-04 08:55:33');


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
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_number` (`id_number`),
  KEY `idx_employee_deleted_at` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: employees
INSERT INTO `employees` VALUES ('5', '645645645645', 'Registrar', NULL, 'Admin', 'Registrar Officer', 'Student Affairs', 'gsdfgsdf@gmial.com', '45645645', NULL, '2025-09-15 13:02:25', '2025-09-15', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('6', 'HR001', 'HR', '', 'Administrator', 'HR Manager', 'Human Resources', '', '', '', '2025-09-19 00:36:22', '2025-09-18', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('7', '546456456', 'Teacher', NULL, 'Go', 'teacher', 'Student Affairs', 'gesterd@gmail.com', '09129496123', NULL, '2025-09-19 04:17:21', '2025-09-19', '0095215938', NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('8', '546464564', 'fgjghjhgj', NULL, 'ghjghjghjghj', 'dfgdfgdf', 'Finance', 'gdfgdfg@gmail.com', '12345678911', NULL, '2025-09-19 04:24:40', '2025-09-11', NULL, '2025-10-11 15:36:06', 'HR Administrator', 'Deleted by HR for administrative purposes', '0', NULL, NULL);
INSERT INTO `employees` VALUES ('29', '545645645645', 'Guidance', NULL, 'Admin', 'Guidance', 'Student Affairs', 'ihasidhs@gmail.com', '09129496136', NULL, '2025-09-23 17:28:12', '2025-09-23', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('33', '123123123', 'sdasda', NULL, 'sdasda', 'asdasd', 'Finance', 'asdasda@gmail.com', '12312312312', NULL, '2025-09-25 11:08:21', '2025-09-25', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('36', '450789789789789', 'adsdawdasd', NULL, 'asdasdasd', 'asdawdasd', 'Academic Affairs', 'dgfgdfgdrg@gmail.com', '45345345345', NULL, '2025-09-25 11:12:07', '2025-09-25', NULL, '2025-10-04 07:27:28', 'HR Administrator', 'Deleted by HR for administrative purposes', '0', NULL, NULL);
INSERT INTO `employees` VALUES ('41', '345345345345', 'Lance', NULL, 'Cereno', 'asdawdasdss', 'Finance', 'gfesfsf@gmail.com', '54645645645', NULL, '2025-09-26 10:16:24', '2025-09-26', '0095295411', NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('42', '21312312312', 'gegegege', '', 'fdsfsdfsdfsdf', 'asdasdas', 'Academic Affairs', '', '', NULL, '2025-09-26 11:30:45', '2025-09-26', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('44', '234234234234', 'sdfsdfsdfsdfsdfsdfsdf', 'sdfsdfsdf', 'sdfsdf', 'sdfsdf', 'Academic Affairs', 'sdfsdfsdf@gmail.com', '234234234234234', 'gfdgdfgdgdfgdfg', '2025-09-30 01:28:15', '2025-09-30', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('46', '71092124378', 'talaga', 'asdasdasdas', 'dasdasdasd', 'dasdasdas', 'HR', 'sdfsdfsdfsdfsdfsdfsd@gmail.com', '54645645645', 'dfgdfgdfgdfgdfg', '2025-09-30 02:50:41', '2025-09-30', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('47', '71092124372', 'gfgffggffgf', 'sdfsdf', 'sdssssss', 'dasdasd', 'IT Department', 'dsads@gmail.com', '12312312312', 'dfgdfgdfgdfgdfgdfgdfgdfg', '2025-09-30 02:59:02', '2025-09-30', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('48', '71092124374', 'mark', 'sdfsdf', 'ooooo', 'asdawdasd', 'IT Department', 'dsads@gmail.com', '12312312312', 'dfgdfgdfgdfgdfgdfgdfgdfg', '2025-09-30 03:00:43', '2025-09-30', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('49', '12350000000', 'dawdasdasd', 'asdasd', 'dasdasdsa', 'asdasd', 'Academic Affairs', 'dasdasd@gmail.com', '21343463645', 'sdofujwhsdiufsiudhfisudhfiusdf', '2025-09-30 03:02:01', '2025-09-30', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('50', '34576575675', 'gfhfghfthf', 'ghfghfg', 'ghfghf', 'hfghf', 'Maintenance', 'ghfghfg@gmail.com', '54645764564', 'hfghfghfghfghfg', '2025-09-30 03:02:54', '2025-09-30', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('51', '54645456456', 'gfdhfghfghf', 'ghfgh', 'ghfghfg', 'fghfghf', 'Maintenance', 'jhgjghjgjhghjghj@gmail.com', '54364564756', 'kljljkljkljkljk', '2025-09-30 03:04:04', '2025-09-30', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('52', '76342347567', 'dsfsdfsd', 'fsdfs', 'sdfsdfsdf', 'dfsdf', 'Student Affairs', 'sdfsdfsdfsdf@gmail.com', '34534534534', 'gdfgdfgdfgdfgdfgdfg', '2025-09-30 03:08:17', '2025-09-30', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('53', '12312312343', 'fdsfsdfsef', 'sdfsdf', 'gfgdfgdfg', 'dfgdfgdfgdf', 'HR', 'dsfsdf@gmail.com', '53453453453', 'hgfhfghfghfgh', '2025-09-30 03:10:59', '2025-09-30', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('54', '54353453453', 'Cashier', 'fdgdfgdfdfg', 'Admin', 'cashier', 'Finance', 'asdasdawsd@gmail.com', '23423423423', 'fsdfsdfsdfsdfsdf', '2025-09-30 11:07:49', '2025-09-30', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('55', '10000115605', 'Herbert', 'Garcia', 'Gardener', 'Faculty', 'IT Department', 'herbert@gmail.com', '09743527353', 'stisjdfm =, brangay likoliko', '2025-09-30 14:14:05', '2025-09-30', '0095180630', NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('56', '423423423', 'sdgsdfsdfdf', 'fdgdfgdfg', 'dfgdfgdfgdfg', 'dfgdfg', 'Human Resources', 'dfgdfgdfgdfgdgdg@gmail.com', '34534534534', 'dfgdfgdfgdfgdfgdfgdfg', '2025-10-03 23:11:56', '2025-10-03', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('58', '13123123123', 'saasdas', 'sdasdasd', 'sdasda', 'dasdasda', 'Human Resources', 'sdadasdasd@gmail.com', '12312321312', 'asdasdad', '2025-10-04 05:27:14', '2025-11-01', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('59', '12312312312', 'terd', 'sadsdsdasda', 'sdasdasdasd', 'sdasdasda', 'Human Resources', 'sadsadas@gmail.com', '21312311212', 'asdadasdasdasdsa', '2025-10-04 05:28:55', '2025-10-04', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('60', '08000012312', 'dasdasda', '', 'asdasdasd', 'asdasasd', 'Human Resources', 'sdasda@gmail.com', '12312312312', 'sadasdasdasd', '2025-10-04 06:10:23', '2025-10-04', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('61', '02039248792', 'Pogi', 'asdasdasd', 'dasda', 'asdas', 'Human Resources', 'sdasdasdd@gmail.com', '23123123123', 'sadasdasdas', '2025-10-04 06:11:02', '2025-10-04', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('63', '65752130891', 'sdfasdawda', 'asdasdas', 'sdas', 'dasda', 'Finance', 'sdasdas@gmail.com', '12312312312', 'asdasdasdasd', '2025-10-04 07:48:17', '2025-10-04', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('64', '54656423423', 'asdasdasdassdasdasd', '', 'dasdasd', 'asdassda', 'Human Resources', 'asdasdas@gmail.com', '12312312312', 'asdasdasdasdasd', '2025-10-04 07:59:55', '2025-10-04', NULL, NULL, NULL, NULL, '0', NULL, NULL);
INSERT INTO `employees` VALUES ('65', '15485213123', 'feswfsd', '', 'sdfsdf', 'fsdf', 'Human Resources', 'sdf@gmail.com', '21312312312', 'dsfsdfsdfsdf', '2025-10-04 08:02:06', '2025-10-04', NULL, '2025-10-11 15:38:10', 'HR Administrator', 'Deleted by HR for administrative purposes', '0', NULL, NULL);


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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: fee_types
INSERT INTO `fee_types` VALUES ('5', 'Uniform', '453.00', '1', '2025-09-04 10:59:17', '2025-09-04 10:59:17');
INSERT INTO `fee_types` VALUES ('6', 'P.E Pants', '545.00', '0', '2025-09-04 12:38:15', '2025-09-23 14:57:52');
INSERT INTO `fee_types` VALUES ('7', 'Tuition Fee', '43234.00', '1', '2025-09-04 13:23:17', '2025-09-04 13:23:17');
INSERT INTO `fee_types` VALUES ('8', 'Form 137', '249.00', '1', '2025-09-07 18:04:52', '2025-09-23 03:51:19');
INSERT INTO `fee_types` VALUES ('9', 'hayes', '123.00', '0', '2025-09-11 15:08:23', '2025-09-23 14:57:45');
INSERT INTO `fee_types` VALUES ('10', 'andre', '5.00', '0', '2025-09-11 15:14:39', '2025-09-11 15:15:13');
INSERT INTO `fee_types` VALUES ('11', 'pe pants', '300.00', '1', '2025-09-11 15:38:30', '2025-09-11 15:38:30');
INSERT INTO `fee_types` VALUES ('12', 'Washday T-Shirt', '257.00', '1', '2025-09-23 03:47:27', '2025-09-23 03:47:27');
INSERT INTO `fee_types` VALUES ('13', 'asdf', '123.00', '1', '2025-09-23 03:51:24', '2025-09-23 14:58:06');


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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: grades_record
INSERT INTO `grades_record` VALUES ('22', '02000307705', 'asdasdasd', '2025-2026 1st Term', '45.00', '54.00', '78.00', '67.00', 'Lance Cereno');


-- Table: guidance_records
DROP TABLE IF EXISTS `guidance_records`;
CREATE TABLE `guidance_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(20) DEFAULT NULL,
  `record_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=177 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: guidance_records
INSERT INTO `guidance_records` VALUES ('174', '02000307705', '2025-09-15', 'Dress Code Violation - 1st Offense');
INSERT INTO `guidance_records` VALUES ('175', '02000307705', '2025-09-15', 'No id - 1st Offense');
INSERT INTO `guidance_records` VALUES ('176', '02000307705', '2025-09-29', 'Bullying - 1st Offense');


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
  PRIMARY KEY (`id`),
  KEY `idx_login_date` (`login_time`),
  KEY `idx_id_number` (`id_number`)
) ENGINE=InnoDB AUTO_INCREMENT=225 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `date_sent` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=255 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: notifications
INSERT INTO `notifications` VALUES ('242', '02000307705', '📤 You have submitted a document request for Form137. We’ll notify you once it’s processed.', '2025-10-11 12:05:27', '0');
INSERT INTO `notifications` VALUES ('243', '02000307705', '📤 You have submitted a document request for Good Moral. We’ll notify you once it’s processed.', '2025-10-11 12:05:31', '0');
INSERT INTO `notifications` VALUES ('244', '02000307705', '📤 You have submitted a document request for weqqweqwe. We’ll notify you once it’s processed.', '2025-10-11 12:06:08', '0');
INSERT INTO `notifications` VALUES ('245', '02000307705', '📤 You have submitted a document request for sdfsdf. We’ll notify you once it’s processed.', '2025-10-11 12:06:11', '0');
INSERT INTO `notifications` VALUES ('246', '02000307705', '📤 You have submitted a document request for sasadads. We’ll notify you once it’s processed.', '2025-10-11 12:06:15', '0');
INSERT INTO `notifications` VALUES ('247', '02000307705', '📤 You have submitted a document request for sadsad. We’ll notify you once it’s processed.', '2025-10-11 12:06:19', '0');
INSERT INTO `notifications` VALUES ('248', '02000307705', '📤 You have submitted a document request for gdffgd. We’ll notify you once it’s processed.', '2025-10-11 12:06:23', '0');
INSERT INTO `notifications` VALUES ('249', '02000307705', '📤 You have submitted a document request for fhghfg. We’ll notify you once it’s processed.', '2025-10-11 12:06:27', '0');
INSERT INTO `notifications` VALUES ('250', '02000307705', '📤 You have submitted a document request for fgddfg. We’ll notify you once it’s processed.', '2025-10-11 12:06:31', '0');
INSERT INTO `notifications` VALUES ('251', '02000307705', '📤 You have submitted a document request for dgfgdf. We’ll notify you once it’s processed.', '2025-10-11 12:06:34', '0');
INSERT INTO `notifications` VALUES ('252', '02000307705', '📤 You have submitted a document request for asdasd. We’ll notify you once it’s processed.', '2025-10-11 12:06:37', '0');
INSERT INTO `notifications` VALUES ('253', '02000307705', '📄 Your document \'asdasd\' status has been updated to \'Approved\'.', '2025-10-11 13:19:45', '0');
INSERT INTO `notifications` VALUES ('254', '02000307705', '📄 Your document \'dgfgdf\' status has been updated to \'Declined\'.', '2025-10-11 13:21:26', '0');


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
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: owner_accounts
INSERT INTO `owner_accounts` VALUES ('1', 'owner', '$2y$10$d4lnh3Rc8oTMe.zK5g1Zz.ptW0Y7cmadaUQtYhMiAuaHWfQKqc9L2', 'School Owner', 'owner@cornerstonecollegeinc.com', '2025-10-11 17:22:11', '2025-09-30 11:16:33', '2025-10-11 17:22:11');


-- Table: owner_approval_requests
DROP TABLE IF EXISTS `owner_approval_requests`;
CREATE TABLE `owner_approval_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_title` varchar(255) NOT NULL,
  `request_description` text NOT NULL,
  `request_type` enum('delete_account','restore_account','system_maintenance','data_modification','user_management','other') NOT NULL,
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
  `reviewed_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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

-- Data for table: owner_requests
INSERT INTO `owner_requests` VALUES ('1', 'delete_student', 'superadmin', 'superadmin', 'Permanent Student Deletion Request', 'Request to permanently delete student record ID: S2025001 due to data privacy compliance', '{\"student_id\": \"S2025001\", \"reason\": \"GDPR compliance\", \"backup_created\": true}', 'high', 'pending', '2025-09-30 11:16:33', NULL, NULL, NULL, '2025-09-30 11:16:33', '2025-09-30 11:16:33');
INSERT INTO `owner_requests` VALUES ('2', 'system_maintenance', 'superadmin', 'superadmin', 'Database Optimization Request', 'Request permission to perform database optimization and cleanup during maintenance window', '{\"maintenance_type\": \"database_optimization\", \"estimated_downtime\": \"2 hours\", \"scheduled_date\": \"2025-01-15\"}', 'medium', 'pending', '2025-09-30 11:16:33', NULL, NULL, NULL, '2025-09-30 11:16:33', '2025-09-30 11:16:33');
INSERT INTO `owner_requests` VALUES ('3', 'delete_employee', 'hr_admin', 'hr', 'Employee Record Deletion', 'Request to permanently delete terminated employee record', '{\"employee_id\": \"EMP001\", \"termination_date\": \"2024-12-31\", \"reason\": \"Contract ended\"}', 'low', 'approved', '2025-09-30 11:16:33', '2025-09-30 11:53:51', 'owner', '', '2025-09-30 11:16:33', '2025-09-30 11:53:51');


-- Table: parent_account
DROP TABLE IF EXISTS `parent_account`;
CREATE TABLE `parent_account` (
  `parent_id` int(11) NOT NULL AUTO_INCREMENT,
  `child_id` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`parent_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `unique_child` (`child_id`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `uq_parent_username` (`username`),
  KEY `idx_child_id` (`child_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: parent_account
INSERT INTO `parent_account` VALUES ('1', '02000307705', 'gesterd', '$2y$10$4gJBLMCobsGt50sOgTbk6uJYgPMoHXUWuuH7SQL/84u60Pn2T0KGe', '2025-08-31 21:53:43', '2025-09-11 11:55:14');
INSERT INTO `parent_account` VALUES ('2', '6786786786666678678678', 'sunico', '$2y$10$u6LjDqHt4qKjMQ0EmhcFju4hLZ8E8qJ4yMRNY1XHcOPLr.qBMIpOy', '2025-09-19 02:45:44', '2025-09-19 02:45:44');
INSERT INTO `parent_account` VALUES ('3', '0912384353453', 'tejada', '$2y$10$JVU6DKwWsQTgPAk3GDruvO6enSCoU8OkkQ2URwz7OgWNi2/jUB9Ci', '2025-09-19 02:52:43', '2025-09-19 02:52:43');
INSERT INTO `parent_account` VALUES ('4', '12345678900', 'asdasdasd', '$2y$10$djxZhjLm2Go17OGBAWvKEu9bxyUzyrhkQZRCs5HFCtBn1C1TmQ9d.', '2025-09-25 08:57:32', '2025-09-25 08:57:32');
INSERT INTO `parent_account` VALUES ('5', '02000307345', 'gsegsdfsdf', '$2y$10$juotwT014ulrgkf99DSnTuGmlWThZop7cPNDAOCApDW7TJXcugSJq', '2025-09-25 10:50:03', '2025-09-25 10:50:03');
INSERT INTO `parent_account` VALUES ('6', '56454564564', 'gdsfgsdfsdfs', '$2y$10$70B5hvpPVFYBVq4W6bEcW.x15Hw.eXSyKg25eack2zWyty4MQN9W6', '2025-09-25 10:58:37', '2025-09-25 10:58:37');
INSERT INTO `parent_account` VALUES ('7', '02000345678', 'parent123', '$2y$10$wuMFCiCba8vC.f5CRuC7heL6SEwbHJ1C9yPNtFZj2Bwg3OPeJj5IC', '2025-09-25 18:49:28', '2025-09-25 18:49:28');
INSERT INTO `parent_account` VALUES ('8', '54765687567', 'drgdfgdfgdfg', '$2y$10$AMfgmrQj/iJKjWShwK2YluQreKkZX4vp2eL9CmFzWPXSHGBYOCUB2', '2025-09-30 09:01:58', '2025-09-30 09:01:58');
INSERT INTO `parent_account` VALUES ('9', '34534534534', 'gsddfsefsdfsdf', '$2y$10$KX4ktE0HYBsBR1eD6UHV9OoRZXddm2oxzWpNuyTlKC6nOvvRj8mgq', '2025-09-30 09:03:00', '2025-09-30 09:03:00');
INSERT INTO `parent_account` VALUES ('10', '02000000001', 'janice', '$2y$10$P4VEb5E9x9WUh0H1u0yfaOg9TYkkWAFNoTZ5K0Ww9JWCo2MBFGOEq', '2025-09-30 14:37:27', '2025-09-30 14:37:27');
INSERT INTO `parent_account` VALUES ('11', '12312312312', 'asdasd', '$2y$10$9hWTZnbnu9U/EFVKhmnqieJY4vPD.KrCtLS5zRrjXnGxaT.HCHlle', '2025-10-04 12:04:52', '2025-10-04 12:04:52');
INSERT INTO `parent_account` VALUES ('12', '43124123123', 'jhgjghjghjghjghj', '$2y$10$ajB7vf9c88eN5Nr/uYNfA.MwhEwiMqMj2JeSYxQeQDRmV3orD4k/e', '2025-10-04 12:12:08', '2025-10-04 12:12:08');
INSERT INTO `parent_account` VALUES ('13', '12312312213', 'hgfhfghfgh', '$2y$10$FA0raNvizNRGmX4PFlwflOdD3oFA4ooFB6AQbwT4BZCtKarQQxfDS', '2025-10-04 12:16:24', '2025-10-04 12:16:24');
INSERT INTO `parent_account` VALUES ('14', '54612356431', 'hgfghfthfgh', '$2y$10$6NQ5d.i0ppbqvi/xalssbeOK4EmZy6tFslezrzoIJ8hMPYswDXkC6', '2025-10-04 12:21:13', '2025-10-04 12:21:13');
INSERT INTO `parent_account` VALUES ('15', '67867867867', 'jlqweqsad', '$2y$10$HDfASh6Crozjq46ekuyTb.HbbN6pPf1.mJrpg49cALQ9H7xtoPhXS', '2025-10-04 12:24:09', '2025-10-04 12:24:09');
INSERT INTO `parent_account` VALUES ('16', '12312312356', 'sdfsdfsdf4567456', '$2y$10$WCwlHlJDkSzd5q0qg55jnOpGT2AkSE98T3aYkozuNGz3mezqyldAO', '2025-10-04 12:30:43', '2025-10-04 12:30:43');
INSERT INTO `parent_account` VALUES ('17', '23423423423', 'dsfsdfsdfsd', '$2y$10$GB.ECAnrwe9d1X4qEeprfenoOqlfwtqx3H4SeeOK.FAPHlXp5b1oa', '2025-10-04 12:38:04', '2025-10-04 12:38:04');
INSERT INTO `parent_account` VALUES ('18', '56568567567', 'dfgdf', '$2y$10$yLC1mgUscQ4gE6Kfr6ZiruC0h6zyxHVrirt/31T2ykVzubSlVf6T.', '2025-10-04 12:41:32', '2025-10-04 12:41:32');
INSERT INTO `parent_account` VALUES ('19', '23123123123', 'dasdasdasdasd', '$2y$10$kyu0Hy4eOvRfoOApt.yWtuuE5MhLsjDpZ6Tv0MIUSDEaq3dAWFnZS', '2025-10-04 12:45:38', '2025-10-04 12:45:38');
INSERT INTO `parent_account` VALUES ('20', '31231231231', 'fghdfgdfg', '$2y$10$VDEM3fVPWHE28X1PjsAJp.xR6xFQ3tyBWBU58wzXJiFA/tAQbX6Wm', '2025-10-04 12:54:27', '2025-10-04 12:54:27');
INSERT INTO `parent_account` VALUES ('21', '12345346657', 'fhjkiuoukhjk', '$2y$10$O/lY/GPNuIcg5uxeNlKHn.6dfaELUxT6/PMKTLqzIXm28tEucaNpK', '2025-10-04 13:02:46', '2025-10-04 13:02:46');
INSERT INTO `parent_account` VALUES ('22', '35234253453', 'hgfhfghfghf', '$2y$10$/gCBhLwcAnoCXrlYNiSK7.WClhdG5iq9cmpBhHnwWW3wBe8ulqIni', '2025-10-04 13:07:15', '2025-10-04 13:07:15');
INSERT INTO `parent_account` VALUES ('23', '43253453453', 'mlkhjmjklmlkhj', '$2y$10$3HgwADEhcsPeKgGvxM8YieZ9AQ/Ale8ocZlJ08c9D3gmJMG3LOrZu', '2025-10-04 13:08:06', '2025-10-04 13:08:06');
INSERT INTO `parent_account` VALUES ('24', '45645645645', 'ghffghfgh', '$2y$10$s2lse2aIH/eR6VF8.H4GmOSj7S5U48NQl3r80O1EM8y1Pde8dsECO', '2025-10-04 13:35:42', '2025-10-04 13:35:42');
INSERT INTO `parent_account` VALUES ('25', '34534534532', 'fgdfgdfgdfg', '$2y$10$A8kbL94oSySA3FNrbdW.9uBfDBC3IUAQgZHbay1YENnQvvt8Rs4lu', '2025-10-04 14:07:25', '2025-10-04 14:07:25');
INSERT INTO `parent_account` VALUES ('26', '02000000002', 'michaeljackson', '$2y$10$XAGFE.iiR6r68vHrhkTkt.Bz5VqgQulbBzTIB0x7U4yioWDNVoUBO', '2025-10-04 17:10:40', '2025-10-04 17:10:40');
INSERT INTO `parent_account` VALUES ('27', '06000000001', 'gdffdfg', '$2y$10$l9y7weFLo8.fzNSo8Y6etuffU0urkkvildwXsnD2TPe0907ZBGl9C', '2025-10-09 17:24:35', '2025-10-09 17:24:35');
INSERT INTO `parent_account` VALUES ('28', '02200000001', 'ghfghfg000001muzon@parent.cci.edu.ph', '$2y$10$lpCqg0D4qPDz6i12bRExwOQ.vYSWgCTw2c8HOcT5DcZwAVrRtBDRG', '2025-10-10 10:14:02', '2025-10-10 10:14:02');
INSERT INTO `parent_account` VALUES ('29', '02200000002', 'dgfdgfdgfdgf000002muzon@parent.cci.edu.ph', '$2y$10$jXrpMyj//e7E0xbcYbKnF.pcQ/LJwm2FlagAfoawHRWETbX42cMKW', '2025-10-10 10:15:36', '2025-10-10 10:15:36');
INSERT INTO `parent_account` VALUES ('30', '02200000003', 'gfdgdf000003muzon@parent.cci.edu.ph', '$2y$10$bPU092yY19jMqkZY8J.D0eIcT9XTd5nabyRMSAkdcW6VDkcs5jypm', '2025-10-11 11:01:43', '2025-10-11 11:01:43');
INSERT INTO `parent_account` VALUES ('31', '02200000004', 'sfdddd000004muzon@parent.cci.edu.ph', '$2y$10$KMJo/a5FEwJWN0X9rZ4.sOWch1NZ4zLg/EIt5xEW60Aa3SLqRnxui', '2025-10-11 11:51:09', '2025-10-11 11:51:09');


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
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_number` (`id_number`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `rfid_uid` (`rfid_uid`),
  KEY `idx_student_account_class_schedule` (`class_schedule`),
  KEY `idx_student_deleted_at` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: student_account
INSERT INTO `student_account` VALUES ('1', '45645645645', 'Bachelor of Physical Education (BPed)', 'OLD', '', 'Go', 'Gesterd', 'Gaon', '2025-2026', '1st Year', '1st', '2003-05-24', 'caloocan', '', 'catholic', '', 'Cash', 'university heightsasdasdasd', 'Alexander Go', 'Father', '091234324', 'Gloria Gaon', 'Mother', '0985345345', 'Alexander Go', 'Father', '09345345', 'Sti SJDM', '2024-2025', '02000307705', 'gesterd', '$2y$10$d4lnh3Rc8oTMe.zK5g1Zz.ptW0Y7cmadaUQtYhMiAuaHWfQKqc9L2', '0095105805', '2025-08-31 18:01:23', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('2', '4645645', 'GAS', 'OLD', '', 'Sunico', 'Hayes', '', '2025-2026', 'Grade 11', '1st', '2025-09-01', 'hgjghj', '', 'ghjhgj', 'F-138,ESC Certification', 'Cash', 'ghjgh', 'ghj', 'ghj', '678', 'jghjgh', 'ghjgh', '687', 'jghjghj', 'jgh', '678', 'hjk', '678', '02000645645', 'hayes', '$2y$10$HfOi6JzYBPQb2WuhjOVyLOMl3fobQ7YCgFOFe9C8rum30TLqWlJ7W', '0095310074', '2025-09-01 00:22:20', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('3', '456456', 'Junior High School', 'OLD', '', 'De Jesus', 'Liam', '', '2025-2026', 'Grade 8', '1st', '2025-09-01', 'asdas', '', 'sdfsdf', 'F-138,ESC Certification', 'Cash', 'sdf', 'dh', 'fghfgh', '234', 'dfgh', 'sdf', '234', 'sdf', 'sdf', '234', 'sdf', '234', '02000534645', 'liam', '$2y$10$EXpo2kYA8JtEthUNiDlG3.3qZ3VkoMEg7NyUVnMFu7UBtdLHYTgyy', '0095560689', '2025-09-01 00:24:44', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('4', '234234234', 'Elementary', 'OLD', '', 'De jess', 'dffsefsdf', 'fgdfgdfgdgsss', '2023-2024', 'Grade 4', '1st', '2025-09-10', 'asdawdasd', '', 'dgf', 'F-138,PSA Birth', 'Installment', 'dfgdfg', 'dfgdfg', 'dfgdfg', '345345', 'dfg', 'dfgdf', '34534', 'gdfg', 'gdfgdf', '53453', 'dfgdfgdf', '345345345', '80374985739845345', 'go', '$2y$10$MnV9dLRO.CDBtqBH8LyMIeMOlo5NqW/5SDmEhWxuCtNr8e/5hdwpK', '3453453453', '2025-09-19 02:30:24', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('5', '234234234', 'Elementary', 'OLD', '', 'dfgdfgd', 'dfgdfg', 'fgdfgdfgdg', '2023-2024', 'Grade 4', '1st', '2025-09-10', 'asdawdasd', 'Male', 'dgf', 'F-138,PSA Birth', 'Installment', 'dfgdfg', 'dfgdfg', 'dfgdfg', '345345', 'dfg', 'dfgdf', '34534', 'gdfg', 'gdfgdf', '53453', 'dfgdfgdf', '345345345', '67867867866666786786', 'sunico', '$2y$10$QH9xs3BDsq87IySsAwz0D.ju2g9BvpJKE.27oIY6M0R6mew9znyEy', '4958609458', '2025-09-19 02:45:44', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('6', '567567', 'Junior High School', 'OLD', '', 'jmghmj', 'gmmj', 'ghjmgh', '2025-2026', 'Grade 8', '1st', '2025-09-05', 'hgjghmghj', '', 'ghjmgmjgm', 'F-138', 'Cash', 'gmhjmghjmghj', 'jmghjmgh', 'gmhjmg', '867867', 'mgjhm', 'ghmgj', '678678', 'mhjgmjg', 'gmhj', '67867', 'kjhkjhkhkj', '6786786', '0912384353453', 'tejada', '$2y$10$oQafKwiycTrCmEa9vsb96..yk3zl3.j4GgpNu3Nqza/pA1H1Cyfu6', '9834759837', '2025-09-19 02:52:43', 'dfgdfg (1:21 AM - 11:26 AM)', '2025-10-09 19:59:32', 'Registrar Admin', 'Deleted by registrar for administrative purposes');
INSERT INTO `student_account` VALUES ('34', '123123123123', 'Junior High School', 'OLD', '', 'sdfsdf', 'sdfsdf', 'sdfsdf', '2025-2026', 'Grade 8', '1st', '2025-09-04', 'sdfsdfsd', 'Male', 'sdfsdfsdf', 'F-138,PSA Birth', 'Cash', 'sdfsdfsdf', 'sdfsdf', 'xcvxcv', '12431423123', 'xcvxcvx', 'xcv', '23423423424', 'xcvxcvx', 'cvxcv', '42342352342', 'sdfsdfsefsdfsdf', '2024-2025', '02000307345', 'asdasdasd', '$2y$10$Go5ptB3ADcqYtdFY1ixfBODfEC.ZA/RZJmhDyMxusdc9Dun2Yqw3S', '4564564566', '2025-09-25 10:50:03', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('35', '123234234234', 'Junior High School', 'OLD', '', 'sdfsd', 'gd', 'fsdfsdf', '2025-2026', 'Grade 8', '1st', '2025-09-19', 'sdfsdfs', '', 'sdfsdfsd', 'F-138', 'Installment', 'sdfsdfsdf', 'asdasd', 'asdasd', '12312312312', 'dasd', 'asda', '12312312342', 'das', 'sdas', '31243242234', 'dasdawdasdasd', '2021-2022', '56454564564', 'fghfghfthfgh', '$2y$10$dmzE7RitmWCPwk5j3fcPdug3r1zSIcSfSh4c.TjCU0hwjpHbep.Qe', '4234786867', '2025-09-25 10:58:37', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('36', '020003456781', 'BS Information Technology', 'NEW', 'PUBLIC', 'tejada', 'jazzer andre', 'perez', '2025-2026', '4th Year', '2nd', '2025-09-25', 'quezon city', 'Male', 'ffsdfjsioefsdf', 'F-138,PSA Birth', 'Cash', 'san josedelmonte', 'lebron', 'basketball', '09632581251', 'gladys', 'worker', '09393033811', 'dolly', 'asadad', '09362325484', 'public', '2022-2023', '02000345678', 'lebron', '$2y$10$dYJgIpTPQ8XVrOsD9.BmTO967kIUK9fFuKEKBBx45b2qlWSMjh6ci', '0095101581', '2025-09-25 18:49:28', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('37', '345345345345', 'Elementary', 'OLD', '', 'fghfghf', 'ghfghfghfghfgh', 'hgfhfghfghf', '2025-2026', 'Grade 2', '1st', '2025-09-30', 'fghfghfthfghfgh', 'Male', 'hgjghjghjgh', 'F-138,PSA Birth', 'Installment', 'jghjghjghjghjghj', 'dasda', 'asdasd', '32423423534', 'sdas', 'asda', '65456456456', 'sdasdasd', 'sda', '56456456456', 'dfgdfgdrgdfgdfgdgdfg', '2023-2024', '54765687567', 'fgdfgdfg', '$2y$10$1QMftyuATNXK3E/x95JQhubyAFxEAit4xcloOJU/hncGcb/YeRhre', '4545645744', '2025-09-30 09:01:58', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('38', '678678678678', 'STEM', 'OLD', '', 'Go', 'Joselito', 'jhkhjkhjkhjk', '2025-2026', 'Grade 12', '1st', '2025-09-30', 'gfdgdfgdfg', '', 'dfgdfgdfg', 'F-138', 'Cash', 'dfgdfg', 'dfgdfg', 'dfgdfg', '45345546456', 'dfgdfg', 'dfg', '64564564565', 'dfgdf', 'dfgdfg', '45645645645', 'gdddgfdfgdf', '2024-2025', '34534534534', 'fsdfsefsdf', '$2y$10$lZScYwk8DnM/c49uWb2hN.HLcOpB.GYfLhKQEPKZ/EmkCDeR4KbRW', '4634534534', '2025-09-30 09:03:00', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('39', '123123123123', 'Elementary', 'OLD', '', 'Austria1231', 'Deffrey', 'P', '2025-2026', 'Grade 1', '2nd', '2003-09-30', 'tungkong mangga', '', 'Catholic', '', 'Cash', 'tungkong manga damuhanf', 'Jeffrey Coco', 'Farmers', '09312537253', 'Chris Barameda', 'House Wife', '09361726317', 'Janice Flores', 'Manager', '09651271731', 'UCC', '2025-2026', '02000000001', 'deffrey', '$2y$10$xwOvaYlu8ZMl4SomH/KWu.IN49xffIKVCOMm/WK/PcaPlbeH5GH1a', '0094977889', '2025-09-30 14:37:27', NULL, '2025-10-09 20:50:39', 'Registrar Admin', 'Deleted by registrar for administrative purposes');
INSERT INTO `student_account` VALUES ('40', '098547069485', 'Elementary', 'NEW', '', 'Gosad', 'Genalyn', 'asdasdas', '2025-2026', 'Grade 2', '1st', '2025-10-04', 'asdawdasdasd', '', 'asdasdasdasd', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Cash', 'asdas', 'dasd', 'asd', '12312311231', 'asda', 'asd', '23123112312', 'sdasd', 'sda', '23123121231', 'asdasd', '', '12312312312', 'genalyn', '$2y$10$eN4d8nvUXEt01hT.NYYtOu.y//T8EQ.j340Zg.DHll7iAEnTrgHd2', '1231231212', '2025-10-04 12:04:52', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('41', '654364564564', 'Junior High School', 'OLD', '', 'sige', 'oona', 'asdasdasdasd', '2025-2026', 'Grade 8', '2nd', '2025-10-04', 'asdadasd', 'Female', 'asdasdasd', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Cash', 'asdasdasd', 'dasd', 'asdas', '12312312423', 'asd', 'asd', '42342342342', 'asdasd', 'asd', '23423423423', 'asdasdasd', '', '43124123123', 'asdasd12', '$2y$10$KdrHhwsowF9yHvsDmb6d6.TVJEIsYJZwdl6C3SEBerL3AZnpeB5jW', '1231231231', '2025-10-04 12:12:08', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('42', '654564564564', 'Junior High School', 'OLD', '', 'geege', 'talaga', 'gfhfghfhg', '2025-2026', 'Grade 8', '1st', '2025-10-04', 'asdasdasd', 'Male', 'asdasdasd', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Cash', 'asdasdasdas', 'dasd', 'asda', '13131311231', 'asda', 'sdasd', '31231231231', 'dasdas', 'asdas', '12313123123', 'dasdasdasd', '', '12312312213', 'asd', '$2y$10$1my1kuYiZMyj0r6LW9zHJut2TcAJoG4hkXXepdDcEsjwasLx6QgFy', '8678678678', '2025-10-04 12:16:24', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('43', '987978978978', 'ABM', 'OLD', '', 'uiuiuiuiui', 'uyuyuyuy', 'dsadaw', '2025-2026', 'Grade 12', '1st', '2025-10-04', 'asdawdasdawd', 'Female', 'asdawdasdasd', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Cash', 'asdawdasdasdasd', 'asdasdasd', 'asdasda', '12312332424', 'sdasdasdasdas', 'dasdasda', '23423432423', 'sdasdasdasddasd', 'asdasdaasasda', '42323432242', 'asdawdasdawd', '', '54612356431', 'sdasda', '$2y$10$965mws3gD50ME7CMGkG6tOvUGGGqZZQZAmImQVw7RkVnVcxvykJGy', '1231231232', '2025-10-04 12:21:13', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('44', '756756756756', 'Junior High School', 'OLD', '', 'gdfgdf', 'popopo', 'dfgdfgdf', '2025-2026', 'Grade 9', '1st', '2025-10-04', 'asdawdasdasd', 'Male', 'sdfsdfsdfsdf', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Cash', 'sdfsdfs', 'dfsd', 'dfs', '45345345345', 'fsdfsdf', 'fsd', '53453453453', 'fsdf', 'sdfsd', '21343543245', 'sdfsdf', '', '67867867867', 'fdgdfgdtasdasg', '$2y$10$nA0oL6yhTesYPthSBNsfT.0SFmifDW94zOlkNrnanFHwQwia5vJte', '3123123213', '2025-10-04 12:24:09', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('45', '675756756756', 'Elementary', 'OLD', '', 'asdasdasdasd', 'qwerty', 'ghjghjghj', '2025-2026', 'Grade 3', '1st', '2025-10-04', 'sdfsdfsdfsd', 'Male', 'sdfsdfsd', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Cash', 'sdfsd', 'fsdf', 'sdf', '23423423423', 'sdf', 'sdf', '23423423423', 'dfsdf', 'sdfs', '23423423423', 'sdfsdf', '', '12312312356', 'sdfsdfsdf', '$2y$10$NywMWKIYNqkM77XCQJwLiOA4PO5hPrvGebIvAKO7geTR0DxfTiMSm', '8792312313', '2025-10-04 12:30:43', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('46', '048234234234', 'HE', 'OLD', '', 'asdasdasdasd', 'wew', 'asdasd', '2025-2026', 'Grade 11', '1st', '2025-10-01', 'sdfgsdfsdf', 'Male', 'sdfsdfsdf', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Cash', 'sdfsdf', 'sdfsdfs', 'dfsdf', '42342342342', 'fsdfsdfsdf', 'sd', '23423423423', 'sdfs', 'sdfsdf', '12312332452', 'dfsdfsdf', '', '23423423423', 'dfsdfsdf', '$2y$10$S8yL7Y9i1Vo2dL3S/YpPNObXbggTmmVkP1XSH7T3EvZV/ZaTR.eMi', '2342342342', '2025-10-04 12:38:04', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('47', '654444445645', 'Elementary', 'OLD', '', 'sadawdasdasd', 'yes', 'asdasdasdasd', '2025-2026', 'Grade 3', '1st', '2025-10-04', 'asdasda', 'Male', 'sdfgsdfsdfsdf', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Cash', 'fgddfgdgdfg', 'dfg', 'dgfd', '23423545435', 'fgdfgdgdfdfgdf', 'gdf', '23423423423', 'dfgdfgdf', 'dgf', '32423423423', 'gdgddffgdfdg', '', '56568567567', 'asdasdasddsadasdasasd', '$2y$10$TgobPU8rbR.it5yCNsbN0OZ825KZrOuSZbQixgYuKT7h2.0IrnU9u', '9873453245', '2025-10-04 12:41:32', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('48', '888978978978', 'Junior High School', 'OLD', '', 'asdsdfsdfsd', 'gheghe', 'dsfsdfsdf', '2025-2026', 'Grade 8', '1st', '2025-10-04', 'asdasdasd', 'Male', 'dasdasdasdasdsd', 'F-138,PSA Birth', 'Cash', 'asdasdasdasd', 'adsdasd', 'asdas', '31231231231', 'dasd', 'das', '12312312312', 'asd', 'asdasd', '23123123123', 'asdasdas', '', '23123123123', 'asdasd', '$2y$10$o5GT1Fbm40R.Xk9d76bbV.GHJMw5YpSMrGRMLVivOsJoWUf9n652S', '1234242343', '2025-10-04 12:45:38', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('49', '908560984506', 'Elementary', 'OLD', '', 'dasdasdasd', 'hehehe', 'asdasd', '2025-2026', 'Grade 1', '1st', '2025-10-04', 'asdasdasdasd', 'Male', 'asdasdasdas', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Cash', 'dasdasd', 'asd', 'asd', '53453453453', 'asda', 'asd', '45345345345', 'sdasd', 'sda', '45345345345', '', '', '31231231231', 'gfdgdfgdfgjhgjgj', '$2y$10$ZObQXyfBKBwAhEmRFvSjKOLvdrWB/lnmguZolZ5fjjNIrSI5hzZmq', '5867756756', '2025-10-04 12:54:27', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('50', '074564564564', 'Bachelor of Physical Education (BPed)', 'OLD', '', 'sdfsdfsdfsdf', 'qeqeqeqe', 'asfsdfsdf', '2025-2026', '3rd Year', '1st', '2025-10-04', 'sdfsdfsdfsd', 'Male', 'dfgdfg', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Cash', 'dfgdf', 'gdfg', 'dfg', '13123123123', 'dfgd', 'dfg', '33242342342', 'fgdfgdfg', 'fgd', '23423423423', 'asdasdasdasd', '', '12345346657', 'zxcsdfwdwasd', '$2y$10$Y.4irud3hzNmpS.uiJDBguCfDyKWzqSjDvR4AMJd50p8IGKkM7dpm', '2345765123', '2025-10-04 13:02:46', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('51', '876856785675', 'ICT', 'OLD', '', 'sdfsdf', 'sdfsdfsdf', 'sdfsdfsdf', '2025-2026', 'Grade 12', '2nd', '2025-10-04', 'sdfsdfsdf', 'Female', 'sdfsdfsdf', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Cash', 'sdfsdfsdf', 'sdf', 'sdfs', '53453453453', 'dfsdfsdf', 'dfs', '53453453453', 'fsdf', 'ssfdsd', '45353453453', 'sdfsdfsdf', '', '35234253453', 'fghfghfghfgh', '$2y$10$tH6PXfE9iJuIfSVNTg/glOb52xf1XGapZSHvgimh4hucKA4xeXawe', '2345461231', '2025-10-04 13:07:15', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('52', '094587690485', 'Junior High School', 'OLD', '', 'ijdoifjgiodfgdf', 'nanana', 'jlkhkmjkhmjklm', '2025-2026', 'Grade 8', '1st', '2025-10-04', 'jhmlklkjklmhjmlj', 'Male', 'mjkmjklm', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Cash', 'jklmjkhmhkml', 'hmljkhkmljhmlkj', 'mhljkmlk', '64564564564', 'mlkjhhmljkhmljk', 'hhmlkjhmlkj', '56456456456', 'mkljhkmljmlkj', 'khmljmjkhl', '56434645674', 'mjhklmhljkmhjlk', '', '43253453453', 'mjhlkmkhljhmljk', '$2y$10$KJF4jMJs6sVes64NQ1L7Ru0/p9Vvle1ZL1.RP6eyxbQ9GMxZ.k5vW', '7568712312', '2025-10-04 13:08:06', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('53', '546464564564', 'Pre-Elementary', 'OLD', '', 'fghfghfghf', 'fghfghfgh', 'ghfghf', '2025-2026', 'Kinder', '1st', '2005-10-04', 'hgjghjghjghj', '', 'ghjghjghj', 'F-138,PSA Birth', 'Cash', 'ghjghj', 'ghjg', 'hjghj', '54645645456', 'hjghj', 'ghjg', '56456456456', 'ghjghj', 'ghj', '56456456464', 'fghfghfgh', '', '45645645645', 'ghfghfghfg', '$2y$10$Qt2W1wyDuC3QVoqpVZSSd.A52BBD5n3as3pY4F6fCIgOrQbBBKW3O', '5345345345', '2025-10-04 13:35:42', 'ABM - 12A (7:30 AM - 5:00 PM)', NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('54', '645645645768', 'Elementary', 'OLD', '', 'gdfgdfgdfg', 'sese', 'dfgdfg', '2025-2026', 'Grade 2', '1st', '2025-10-04', 'dfgdfg', 'Male', 'dfgdfgdfg', 'F-138,PSA Birth', 'Cash', 'dfgdfg', 'dfgdfg', 'dfg', '45645645645', 'dfgdfg', 'dfgdfg', '45645645645', 'dfgdfg', 'dfg', '64564564564', 'dfgdfgdf', '', '34534534532', 'dfgdfgdfg', '$2y$10$ZO/p89BNzLOvC5.KwwXG7OUOlRchRdNjPhRKT9QOcrwPNxckvc76G', '4534534534', '2025-10-04 14:07:25', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('55', '123456789012', 'ABM', 'NEW', '', 'Jackson', 'Percy', '', '2024-2025', 'Grade 11', '1st', '2025-10-04', 'Mandaluyong', '', 'Physical Science', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Installment', 'Blk1dsfsdf', 'Michael Jackson', 'Dancer', '00000000000', 'Janneth Jackson', 'Singer', '00000000000', '', '', '', 'STI COLLEGE', '', '02000000002', 'percyjackson', '$2y$10$feMwfTkXDMLV3ONZob98UuU0OqLvGe/FeM3XpVQnvLcx/SzdMBjJ2', '0095369457', '2025-10-04 17:10:39', NULL, '2025-10-09 20:52:48', 'Registrar Admin', 'Deleted by registrar for administrative purposes');
INSERT INTO `student_account` VALUES ('56', '666666666663', 'Junior High School', 'NEW', '', 'Gaon', 'Gabriel', '', '2025-2026', 'Grade 7', '1st', '2025-10-09', 'Caloocan', '', 'Catholic', 'F-138,Good Moral,PSA Birth,ESC Certification', 'Cash', 'dasdasdasdasd', 'asdasdasd', 'asdasdas', '33234444444', 'asdas', 'dasd', '12312312312', 'dasd', 'asdasd', '32423333333', 'asdasdasd', '2024-2025', '06000000001', 'sdfsdsdfs', '$2y$10$5237zDuAdKKVpQRWS/xDXO5zeczfJB9FrgmJ1nNUkF6nHH6SY2h8G', '3423423423', '2025-10-09 17:24:35', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('57', '576767676767', 'Pre-Elementary', 'OLD', '', 'ghfghfg', 'fhghgf', 'fhgfghfhg', '2025-2026', '', '2nd', '2004-11-17', 'fhggffhg', '', 'fhgfhghgffhg', 'F-138,PSA Birth', 'Cash', 'blok 88 lot 6, univeristy height, branrgay kaypian, sjdm, bulacan', 'fhgfh  fhgfgh', 'fhgfhg', '54545454545', 'gfhfhg  fhgfhg', 'fhghgf', '54545454545', 'fhgfhg  hfghgf', 'fhghgf', '45665454545', 'fghfghgfhfgfhg', '2025-2026', '02200000001', 'ghfghfg000001muzon@student.cci.edu.ph', '$2y$10$yGYz5f4tbDKqr/36K1jkh.uQf9HcuvTruMJWJkCF0R9D9l0o4rtOK', '5675675675', '2025-10-10 10:14:02', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('58', '345555555555', 'Pre-Elementary', 'OLD', '', 'dgfdgfdgfdgf', 'gdfdgfgdf', '', '2025-2026', 'Kinder 2', '1st', '2004-11-18', 'dgfgdfdfg', 'Male', 'dgfdfgdgf', 'F-138,PSA Birth', 'Cash', 'dfgdfg, dfgdfgdgfgdfdgfdgfdgf, sdf sdf sdf sdf, sdfsdfssdf', 'dgfdfg  dgfdgf', 'dfggfd', '35454545454', 'dgf  dgf', 'dfg', '35444444444', 'dfg  dfg', 'gdf', '35454545454', 'dfggffgdgf', '2025-2026', '02200000002', 'dgfdgfdgfdgf000002muzon@student.cci.edu.ph', '$2y$10$kxbOGuAzwLgJ6JKeon5qnOoWUEk8K8U9MZKJ63Lhdd/Zjd4RSTtS6', '4565656565', '2025-10-10 10:15:36', 'BSIT - 601 (Variable Times)', NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('59', '435555555555', 'Pre-Elementary', 'OLD', '', 'gfdgdf', 'ppppppppppppppppppp', '', '2025-2026', 'Kinder 1', '1st', '2005-10-18', 'dfgdfgdgf', 'Female', 'dfggdfdfgdfgdfg', 'F-138,PSA Birth', 'Cash', 'dfgdgfdfggfdfgd, gdfgdgdfdfg, dfgdfgdfgdf, dfgdfdfgfgdfd', 'sdasad  aasdasas', 'asddassad', '44444444444', 'sadsad  asdasd', 'sadsad', '32444444444', 'sdfsdf  fsdsdf', 'sdfsdf', '44444444444', 'sdfsdfsdf', '2022-2023', '02200000003', 'gfdgdf000003muzon@student.cci.edu.ph', '$2y$10$cMhutsdOKqPwuISs3RfruucB6Fl7nd93nGiMzeh3v8ASg1NMeAZIm', NULL, '2025-10-11 11:01:43', NULL, NULL, NULL, NULL);
INSERT INTO `student_account` VALUES ('60', '342122222222', 'Elementary', 'OLD', '', 'sfdddd', 'ddddddddddddddddddd', '', '2025-2026', 'Grade 2', '2nd', '2006-11-17', 'sdf', 'Male', 'sdf', '', 'Cash', 'sdfsdfsdf, sdfsdfsdfsdf, fsdsdfsdfsfd, fsdsdfsfdsfd', 'sdfsdf  sdf', 'sdsdf', '32432432432', 'sfdsfd  sfd', 'sfdsdf', '23432432432', 'sdfsdf  sdf', 'sfd', '23423432432', 'sfdsd', '2023-2024', '02200000004', 'sfdddd000004muzon@student.cci.edu.ph', '$2y$10$MAFLl40rrDiPGsyiTOvIV.IbA3zdRYosXVyCzbuK6/QxK5A5ZQVWG', '4566666666', '2025-10-11 11:51:09', NULL, NULL, NULL, NULL);


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
  PRIMARY KEY (`id`),
  KEY `id_number` (`id_number`)
) ENGINE=InnoDB AUTO_INCREMENT=203 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: student_fee_items
INSERT INTO `student_fee_items` VALUES ('160', '02000307705', '2025-2026 1st Semester', 'Tuition Fee - 1st Year (Bachelor of Physical Education (BPed))', '60000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('161', '02000645645', '2025-2026 1st Semester', 'Tuition Fee - Grade 11 (GAS)', '48000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('162', '02000534645', '2025-2026 1st Semester', 'Tuition Fee - Grade 8', '42000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('163', '80374985739845345', '2025-2026 1st Semester', 'Tuition Fee - Grade 4', '36000.00', '36000.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('164', '67867867866666786786', '2025-2026 1st Semester', 'Tuition Fee - Grade 4', '36000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('165', '0912384353453', '2025-2026 1st Semester', 'Tuition Fee - Grade 8', '42000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('166', 'S2025001', '2025-2026 1st Semester', 'Tuition Fee - 1-A (BSIT)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('167', 'S2025002', '2025-2026 1st Semester', 'Tuition Fee - 1-B (BSIT)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('168', 'S2025003', '2025-2026 1st Semester', 'Tuition Fee - 2-A (BSHM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('169', 'S2025005', '2025-2026 1st Semester', 'Tuition Fee - 11-A (ABM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('170', 'S2025006', '2025-2026 1st Semester', 'Tuition Fee - 11-B (ABM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('171', 'S2025007', '2025-2026 1st Semester', 'Tuition Fee - 12-A (STEM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('172', 'S2025008', '2025-2026 1st Semester', 'Tuition Fee - 12-B (STEM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('173', 'S2025010', '2025-2026 1st Semester', 'Tuition Fee - 3-A (BSHM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('174', 'S2025011', '2025-2026 1st Semester', 'Tuition Fee - 12-A (STEM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('175', 'S2025012', '2025-2026 1st Semester', 'Tuition Fee - 11-B (ABM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('176', 'S2025013', '2025-2026 1st Semester', 'Tuition Fee - 2-B (BSIT)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('177', 'S2025014', '2025-2026 1st Semester', 'Tuition Fee - 3-B (BSHM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('178', 'S2025015', '2025-2026 1st Semester', 'Tuition Fee -  (STEM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('179', 'S2025016', '2025-2026 1st Semester', 'Tuition Fee - 1-A (BSIT)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('180', 'S2025017', '2025-2026 1st Semester', 'Tuition Fee - 11-A (ABM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('181', 'S2025018', '2025-2026 1st Semester', 'Tuition Fee - 3-A (BSHM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('182', 'S2025019', '2025-2026 1st Semester', 'Tuition Fee - 12-A (STEM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('183', 'S2025020', '2025-2026 1st Semester', 'Tuition Fee - 2-A (BSIT)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('184', 'S2025021', '2025-2026 1st Semester', 'Tuition Fee - 11-B (ABM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('185', 'S2025022', '2025-2026 1st Semester', 'Tuition Fee - 3-C (BSHM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('186', 'S2025023', '2025-2026 1st Semester', 'Tuition Fee - 12-C (STEM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('187', 'S2025024', '2025-2026 1st Semester', 'Tuition Fee - 2-C (BSIT)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('188', 'S2025025', '2025-2026 1st Semester', 'Tuition Fee - 3-D (BSHM)', '35000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('189', '02000307345', '2025-2026 1st Semester', 'Tuition Fee - Grade 8', '42000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('190', '56454564564', '2025-2026 1st Semester', 'Tuition Fee - Grade 8', '42000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('191', '02000345678', '2025-2026 1st Semester', 'Tuition Fee - 4th Year (BS Information Technology)', '67500.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('192', '54765687567', '2025-2026 1st Semester', 'Tuition Fee - Grade 2', '33500.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('193', '34534534534', '2025-2026 1st Semester', 'Tuition Fee - Grade 12 (STEM)', '54000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('194', '02000000001', '2025-2026 1st Semester', 'Tuition Fee - 4th Year (BS Computer Science)', '67500.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('195', '12312312312', '2025-2026 1st Semester', 'Tuition Fee - Grade 2', '33500.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('196', '43124123123', '2025-2026 1st Semester', 'Tuition Fee - Grade 8', '42000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('197', '12312312213', '2025-2026 1st Semester', 'Tuition Fee - Grade 8', '42000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('198', '54612356431', '2025-2026 1st Semester', 'Tuition Fee - Grade 12 (ABM)', '48000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('199', '67867867867', '2025-2026 1st Semester', 'Tuition Fee - Grade 9', '44500.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('200', '12312312356', '2025-2026 1st Semester', 'Tuition Fee - Grade 3', '36000.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('201', '23423423423', '2025-2026 1st Semester', 'Tuition Fee - Grade 11 (HE)', '50500.00', '0.00', '2025-10-04 12:40:29');
INSERT INTO `student_fee_items` VALUES ('202', 'TEST123456', '2025-2026 1st Semester', 'Tuition Fee - Grade 9', '44500.00', '0.00', '2025-10-04 12:44:22');


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
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: student_payments
INSERT INTO `student_payments` VALUES ('130', '02000000001', '2025-09-30', 'OR202509309664', '2025-2026 1st Semester', 'pe pants', '300.00', 'Cash');
INSERT INTO `student_payments` VALUES ('131', '67867867867', '2025-10-04', 'OR202510045433', '2025-2026 1st Semester', 'Form 137', '249.00', 'Cash');
INSERT INTO `student_payments` VALUES ('132', '80374985739845345', '2025-10-04', 'OR202510047175', '2025-2026 1st Semester', 'Tuition Fee - Grade 4', '36000.00', 'Cash');


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
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: student_schedules
INSERT INTO `student_schedules` VALUES ('5', '02000645645', '2', '2025-09-09 14:42:56', '1', '2025-09-09 14:42:56', '2025-09-09 14:42:56');
INSERT INTO `student_schedules` VALUES ('9', '80374985739845345', '2', '2025-09-21 18:04:53', '10', '2025-09-21 18:04:53', '2025-09-21 18:04:53');
INSERT INTO `student_schedules` VALUES ('10', '67867867866666786786', '2', '2025-09-21 18:04:53', '10', '2025-09-21 18:04:53', '2025-09-21 18:04:53');
INSERT INTO `student_schedules` VALUES ('11', 'S2025009', '2', '2025-09-21 18:04:53', '10', '2025-09-21 18:04:53', '2025-09-21 18:04:53');
INSERT INTO `student_schedules` VALUES ('19', 'S2025006', '18', '2025-09-21 19:00:59', '10', '2025-09-21 19:00:59', '2025-09-21 19:00:59');
INSERT INTO `student_schedules` VALUES ('20', 'S2025001', '1', '2025-09-21 19:05:54', '10', '2025-09-21 19:05:54', '2025-09-21 19:05:54');
INSERT INTO `student_schedules` VALUES ('21', 'S2025010', '1', '2025-09-21 19:05:54', '10', '2025-09-21 19:05:54', '2025-09-21 19:05:54');
INSERT INTO `student_schedules` VALUES ('30', 'S2025011', '23', '2025-09-22 12:17:51', '10', '2025-09-22 12:17:51', '2025-09-22 12:17:51');
INSERT INTO `student_schedules` VALUES ('31', 'S2025018', '23', '2025-09-22 12:17:51', '10', '2025-09-22 12:17:51', '2025-09-22 12:17:51');
INSERT INTO `student_schedules` VALUES ('32', 'S2025020', '23', '2025-09-22 12:17:51', '10', '2025-09-22 12:17:51', '2025-09-22 12:17:51');
INSERT INTO `student_schedules` VALUES ('35', 'S2025013', '23', '2025-09-22 12:38:19', '10', '2025-09-22 12:38:19', '2025-09-22 12:38:19');
INSERT INTO `student_schedules` VALUES ('36', 'S2025021', '23', '2025-09-22 12:38:27', '10', '2025-09-22 12:38:27', '2025-09-22 12:38:27');
INSERT INTO `student_schedules` VALUES ('40', 'S2025012', '12', '2025-09-22 12:41:44', '10', '2025-09-22 12:41:44', '2025-09-22 12:41:44');
INSERT INTO `student_schedules` VALUES ('41', 'S2025003', '12', '2025-09-22 12:42:35', '10', '2025-09-22 12:42:35', '2025-09-22 12:42:35');
INSERT INTO `student_schedules` VALUES ('45', 'S2025024', '10', '2025-09-22 16:56:07', '10', '2025-09-22 16:56:07', '2025-09-22 16:56:07');
INSERT INTO `student_schedules` VALUES ('46', 'S2025019', '10', '2025-09-22 16:56:07', '10', '2025-09-22 16:56:07', '2025-09-22 16:56:07');
INSERT INTO `student_schedules` VALUES ('47', 'S2025008', '25', '2025-09-24 11:19:43', '10', '2025-09-24 11:19:43', '2025-09-24 11:19:43');
INSERT INTO `student_schedules` VALUES ('48', 'S2025015', '25', '2025-09-24 11:19:43', '10', '2025-09-24 11:19:43', '2025-09-24 11:19:43');
INSERT INTO `student_schedules` VALUES ('49', 'S2025002', '25', '2025-09-24 11:19:43', '10', '2025-09-24 11:19:43', '2025-09-24 11:19:43');
INSERT INTO `student_schedules` VALUES ('50', 'S2025014', '25', '2025-09-24 11:19:43', '10', '2025-09-24 11:19:43', '2025-09-24 11:19:43');
INSERT INTO `student_schedules` VALUES ('51', 'S2025017', '34', '2025-09-24 11:25:05', '10', '2025-09-24 11:25:05', '2025-09-24 11:25:05');
INSERT INTO `student_schedules` VALUES ('52', 'S2025005', '34', '2025-09-24 11:25:05', '10', '2025-09-24 11:25:05', '2025-09-24 11:25:05');
INSERT INTO `student_schedules` VALUES ('53', 'S2025004', '34', '2025-09-24 11:25:05', '10', '2025-09-24 11:25:05', '2025-09-24 11:25:05');
INSERT INTO `student_schedules` VALUES ('54', 'S2025016', '34', '2025-09-24 11:25:05', '10', '2025-09-24 11:25:05', '2025-09-24 11:25:05');
INSERT INTO `student_schedules` VALUES ('55', 'S2025025', '34', '2025-09-24 11:25:05', '10', '2025-09-24 11:25:05', '2025-09-24 11:25:05');
INSERT INTO `student_schedules` VALUES ('56', 'S2025007', '34', '2025-09-24 11:25:05', '10', '2025-09-24 11:25:05', '2025-09-24 11:25:05');
INSERT INTO `student_schedules` VALUES ('57', '0912384353453', '34', '2025-09-24 11:25:05', '10', '2025-09-24 11:25:05', '2025-09-24 11:25:05');
INSERT INTO `student_schedules` VALUES ('58', '02000534645', '12', '2025-09-25 16:01:07', '10', '2025-09-25 16:01:07', '2025-09-25 16:01:07');
INSERT INTO `student_schedules` VALUES ('60', '56454564564', '10', '2025-09-25 16:02:19', '10', '2025-09-25 16:02:19', '2025-09-25 16:02:19');
INSERT INTO `student_schedules` VALUES ('61', '02000307345', '10', '2025-09-25 16:02:19', '10', '2025-09-25 16:02:19', '2025-09-25 16:02:19');
INSERT INTO `student_schedules` VALUES ('62', 'S2025023', '10', '2025-09-25 16:02:19', '10', '2025-09-25 16:02:19', '2025-09-25 16:02:19');
INSERT INTO `student_schedules` VALUES ('63', 'S2025022', '10', '2025-09-25 16:02:19', '10', '2025-09-25 16:02:19', '2025-09-25 16:02:19');
INSERT INTO `student_schedules` VALUES ('64', '02000307705', '12', '2025-09-30 10:17:13', '10', '2025-09-30 10:17:13', '2025-09-30 10:17:13');
INSERT INTO `student_schedules` VALUES ('65', '12312312356', '10', '2025-10-06 00:02:21', '10', '2025-10-06 00:02:21', '2025-10-06 00:02:21');
INSERT INTO `student_schedules` VALUES ('66', '23123123123', '10', '2025-10-10 17:47:31', '10', '2025-10-10 17:47:31', '2025-10-10 17:47:31');
INSERT INTO `student_schedules` VALUES ('67', '02000000001', '10', '2025-10-11 06:06:27', '10', '2025-10-11 06:06:27', '2025-10-11 06:06:27');
INSERT INTO `student_schedules` VALUES ('68', '31231231231', '10', '2025-10-11 06:11:55', '10', '2025-10-11 06:11:55', '2025-10-11 06:11:55');
INSERT INTO `student_schedules` VALUES ('69', '23423423423', '12', '2025-10-11 16:32:02', '10', '2025-10-11 16:32:02', '2025-10-11 16:32:02');
INSERT INTO `student_schedules` VALUES ('70', '02200000002', '12', '2025-10-11 16:32:47', '10', '2025-10-11 16:32:47', '2025-10-11 16:32:47');
INSERT INTO `student_schedules` VALUES ('71', '45645645645', '10', '2025-10-11 16:33:51', '10', '2025-10-11 16:33:51', '2025-10-11 16:33:51');


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

-- Data for table: student_tuition
INSERT INTO `student_tuition` VALUES ('1', '02000307705', '1st Year', 'Bachelor of Physical Education (BPed)', '60000.00', 'Cash', '1', '60000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('2', '02000645645', 'Grade 11', 'GAS', '48000.00', 'Cash', '1', '48000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('3', '02000534645', 'Grade 8', 'Junior High School', '42000.00', 'Cash', '1', '42000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('4', '80374985739845345', 'Grade 4', 'Elementary', '36000.00', 'Cash', '1', '36000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('5', '67867867866666786786', 'Grade 4', 'Elementary', '36000.00', 'Cash', '1', '36000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('6', '0912384353453', 'Grade 8', 'Junior High School', '42000.00', 'Cash', '1', '42000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('7', 'S2025001', '1-A', 'BSIT', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('8', 'S2025002', '1-B', 'BSIT', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('9', 'S2025003', '2-A', 'BSHM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('10', 'S2025005', '11-A', 'ABM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('11', 'S2025006', '11-B', 'ABM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('12', 'S2025007', '12-A', 'STEM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('13', 'S2025008', '12-B', 'STEM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('14', 'S2025010', '3-A', 'BSHM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('15', 'S2025011', '12-A', 'STEM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('16', 'S2025012', '11-B', 'ABM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('17', 'S2025013', '2-B', 'BSIT', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('18', 'S2025014', '3-B', 'BSHM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('19', 'S2025015', '', 'STEM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('20', 'S2025016', '1-A', 'BSIT', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('21', 'S2025017', '11-A', 'ABM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('22', 'S2025018', '3-A', 'BSHM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('23', 'S2025019', '12-A', 'STEM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('24', 'S2025020', '2-A', 'BSIT', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('25', 'S2025021', '11-B', 'ABM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('26', 'S2025022', '3-C', 'BSHM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('27', 'S2025023', '12-C', 'STEM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('28', 'S2025024', '2-C', 'BSIT', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('29', 'S2025025', '3-D', 'BSHM', '35000.00', 'Cash', '1', '35000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('30', '02000307345', 'Grade 8', 'Junior High School', '42000.00', 'Cash', '1', '42000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('31', '56454564564', 'Grade 8', 'Junior High School', '42000.00', 'Cash', '1', '42000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('32', '02000345678', '4th Year', 'BS Information Technology', '67500.00', 'Cash', '1', '67500.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('33', '54765687567', 'Grade 2', 'Elementary', '33500.00', 'Cash', '1', '33500.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('34', '34534534534', 'Grade 12', 'STEM', '54000.00', 'Cash', '1', '54000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('35', '02000000001', '4th Year', 'BS Computer Science', '67500.00', 'Cash', '1', '67500.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('36', '12312312312', 'Grade 2', 'Elementary', '33500.00', 'Cash', '1', '33500.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('37', '43124123123', 'Grade 8', 'Junior High School', '42000.00', 'Cash', '1', '42000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('38', '12312312213', 'Grade 8', 'Junior High School', '42000.00', 'Cash', '1', '42000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('39', '54612356431', 'Grade 12', 'ABM', '48000.00', 'Cash', '1', '48000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('40', '67867867867', 'Grade 9', 'Junior High School', '44500.00', 'Cash', '1', '44500.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('41', '12312312356', 'Grade 3', 'Elementary', '36000.00', 'Cash', '1', '36000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('42', '23423423423', 'Grade 11', 'HE', '50500.00', 'Cash', '1', '50500.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('43', '56568567567', 'Grade 3', 'Elementary', '36000.00', 'Cash', '1', '36000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');
INSERT INTO `student_tuition` VALUES ('44', '23123123123', 'Grade 8', 'Junior High School', '42000.00', 'Cash', '1', '42000.00', '2025-10-04 12:53:03', '2025-2026', 'Unpaid');


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
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Table: subjects
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: submitted_documents
INSERT INTO `submitted_documents` VALUES ('28', '12345678900', 'F-138', '2025-09-25 08:57:32', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('29', '12345678900', 'Good Moral', '2025-09-25 08:57:32', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('30', '12345678900', 'PSA Birth', '2025-09-25 08:57:32', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('45', '02000307345', 'F-138', '2025-09-25 10:50:03', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('46', '02000307345', 'PSA Birth', '2025-09-25 10:50:03', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('47', '56454564564', 'F-138', '2025-09-25 10:58:37', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('48', '02000534645', 'Form137', '2025-09-25 15:57:40', 'Claimed', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('49', '02000345678', 'F-138', '2025-09-25 18:49:28', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('50', '02000345678', 'PSA Birth', '2025-09-25 18:49:28', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('51', '02000645645', 'F-138', '2025-09-30 09:00:24', 'Submitted', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('52', '02000645645', 'ESC Certification', '2025-09-30 09:00:24', 'Submitted', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('53', '0912384353453', 'F-138', '2025-09-30 09:00:34', 'Submitted', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('54', '02000534645', 'F-138', '2025-09-30 09:00:45', 'Submitted', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('55', '02000534645', 'ESC Certification', '2025-09-30 09:00:45', 'Submitted', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('56', '54765687567', 'F-138', '2025-09-30 09:01:58', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('57', '54765687567', 'PSA Birth', '2025-09-30 09:01:58', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('58', '34534534534', 'F-138', '2025-09-30 09:03:00', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('59', '80374985739845345', 'F-138', '2025-09-30 09:03:48', 'Submitted', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('60', '80374985739845345', 'PSA Birth', '2025-09-30 09:03:48', 'Submitted', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('61', '02000000001', 'Form137', '2025-09-30 14:58:26', 'Claimed', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('62', '12312312312', 'F-138', '2025-10-04 12:04:52', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('63', '12312312312', 'Good Moral', '2025-10-04 12:04:52', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('64', '12312312312', 'PSA Birth', '2025-10-04 12:04:52', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('65', '12312312312', 'ESC Certification', '2025-10-04 12:04:52', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('66', '43124123123', 'F-138', '2025-10-04 12:12:08', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('67', '43124123123', 'Good Moral', '2025-10-04 12:12:08', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('68', '43124123123', 'PSA Birth', '2025-10-04 12:12:08', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('69', '43124123123', 'ESC Certification', '2025-10-04 12:12:08', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('70', '12312312213', 'F-138', '2025-10-04 12:16:24', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('71', '12312312213', 'Good Moral', '2025-10-04 12:16:24', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('72', '12312312213', 'PSA Birth', '2025-10-04 12:16:24', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('73', '12312312213', 'ESC Certification', '2025-10-04 12:16:24', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('74', '54612356431', 'F-138', '2025-10-04 12:21:13', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('75', '54612356431', 'Good Moral', '2025-10-04 12:21:13', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('76', '54612356431', 'PSA Birth', '2025-10-04 12:21:13', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('77', '54612356431', 'ESC Certification', '2025-10-04 12:21:13', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('78', '67867867867', 'F-138', '2025-10-04 12:24:09', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('79', '67867867867', 'Good Moral', '2025-10-04 12:24:09', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('80', '67867867867', 'PSA Birth', '2025-10-04 12:24:09', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('81', '67867867867', 'ESC Certification', '2025-10-04 12:24:09', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('82', '12312312356', 'F-138', '2025-10-04 12:30:43', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('83', '12312312356', 'Good Moral', '2025-10-04 12:30:43', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('84', '12312312356', 'PSA Birth', '2025-10-04 12:30:43', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('85', '12312312356', 'ESC Certification', '2025-10-04 12:30:43', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('86', '23423423423', 'F-138', '2025-10-04 12:38:04', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('87', '23423423423', 'Good Moral', '2025-10-04 12:38:04', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('88', '23423423423', 'PSA Birth', '2025-10-04 12:38:04', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('89', '23423423423', 'ESC Certification', '2025-10-04 12:38:04', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('90', '56568567567', 'F-138', '2025-10-04 12:41:32', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('91', '56568567567', 'Good Moral', '2025-10-04 12:41:32', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('92', '56568567567', 'PSA Birth', '2025-10-04 12:41:32', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('93', '56568567567', 'ESC Certification', '2025-10-04 12:41:32', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('94', '23123123123', 'F-138', '2025-10-04 12:45:38', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('95', '23123123123', 'PSA Birth', '2025-10-04 12:45:38', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('96', '31231231231', 'F-138', '2025-10-04 12:54:27', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('97', '31231231231', 'Good Moral', '2025-10-04 12:54:27', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('98', '31231231231', 'PSA Birth', '2025-10-04 12:54:27', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('99', '31231231231', 'ESC Certification', '2025-10-04 12:54:27', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('100', '12345346657', 'F-138', '2025-10-04 13:02:46', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('101', '12345346657', 'Good Moral', '2025-10-04 13:02:46', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('102', '12345346657', 'PSA Birth', '2025-10-04 13:02:46', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('103', '12345346657', 'ESC Certification', '2025-10-04 13:02:46', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('104', '35234253453', 'F-138', '2025-10-04 13:07:15', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('105', '35234253453', 'Good Moral', '2025-10-04 13:07:15', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('106', '35234253453', 'PSA Birth', '2025-10-04 13:07:15', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('107', '35234253453', 'ESC Certification', '2025-10-04 13:07:15', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('108', '43253453453', 'F-138', '2025-10-04 13:08:06', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('109', '43253453453', 'Good Moral', '2025-10-04 13:08:06', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('110', '43253453453', 'PSA Birth', '2025-10-04 13:08:06', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('111', '43253453453', 'ESC Certification', '2025-10-04 13:08:06', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('112', '45645645645', 'F-138', '2025-10-04 13:35:42', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('113', '45645645645', 'PSA Birth', '2025-10-04 13:35:42', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('114', '34534534532', 'F-138', '2025-10-04 14:07:25', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('115', '34534534532', 'PSA Birth', '2025-10-04 14:07:25', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('116', '02000000002', 'F-138', '2025-10-04 17:10:40', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('117', '02000000002', 'Good Moral', '2025-10-04 17:10:40', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('118', '02000000002', 'PSA Birth', '2025-10-04 17:10:40', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('119', '02000000002', 'ESC Certification', '2025-10-04 17:10:40', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('120', '02000000002', 'Certificate of Enrollment', '2025-10-04 17:38:49', 'Submitted', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('121', '06000000001', 'F-138', '2025-10-09 17:24:35', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('122', '06000000001', 'Good Moral', '2025-10-09 17:24:35', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('123', '06000000001', 'PSA Birth', '2025-10-09 17:24:35', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('124', '06000000001', 'ESC Certification', '2025-10-09 17:24:35', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('125', '02200000001', 'F-138', '2025-10-10 10:14:02', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('126', '02200000001', 'PSA Birth', '2025-10-10 10:14:02', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('127', '02200000002', 'F-138', '2025-10-10 10:15:36', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('128', '02200000002', 'PSA Birth', '2025-10-10 10:15:36', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('129', '02200000003', 'F-138', '2025-10-11 11:01:43', 'Submitted via Registrar onboarding', 'Submitted');
INSERT INTO `submitted_documents` VALUES ('130', '02200000003', 'PSA Birth', '2025-10-11 11:01:43', 'Submitted via Registrar onboarding', 'Submitted');


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

-- Data for table: system_logs
INSERT INTO `system_logs` VALUES ('1', 'owner_login', 'owner', 'owner', 'Owner logged in successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 11:20:36');
INSERT INTO `system_logs` VALUES ('2', 'owner_logout', 'owner', 'owner', 'Owner logged out', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 11:39:29');
INSERT INTO `system_logs` VALUES ('3', 'owner_login', 'owner', 'owner', 'Owner logged in successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 11:53:34');
INSERT INTO `system_logs` VALUES ('4', 'request_approve', 'owner', 'owner', 'Owner approved request ID: 3', NULL, '3', NULL, NULL, '2025-09-30 11:53:51');
INSERT INTO `system_logs` VALUES ('5', 'owner_logout', 'owner', 'owner', 'Owner logged out', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 11:59:28');
INSERT INTO `system_logs` VALUES ('6', 'owner_logout', 'owner', 'owner', 'Owner logged out', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 12:33:41');
INSERT INTO `system_logs` VALUES ('7', 'owner_login', 'owner', 'owner', 'Owner logged in successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 12:33:51');
INSERT INTO `system_logs` VALUES ('8', 'owner_logout', 'owner', 'owner', 'Owner logged out', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 12:33:52');
INSERT INTO `system_logs` VALUES ('9', 'owner_logout', 'owner', 'owner', 'Owner logged out', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 12:34:32');
INSERT INTO `system_logs` VALUES ('10', 'owner_logout', 'owner', 'owner', 'Owner logged out', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 12:35:56');
INSERT INTO `system_logs` VALUES ('11', 'owner_logout', 'owner', 'owner', 'Owner logged out', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-30 12:36:12');


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: teacher_attendance
INSERT INTO `teacher_attendance` VALUES ('10', '345345345345', '2025-09-26', 'Friday', 'Regular', NULL, NULL, '12:17:46', '12:17:47', '8.00', '0', '0', '0', '0', '0', '0', '2025-09-26 12:17:46');
INSERT INTO `teacher_attendance` VALUES ('11', '345345345345', '2025-09-30', 'Tuesday', 'Regular', NULL, NULL, '10:49:59', '12:24:38', '8.00', '0', '0', '0', '0', '0', '0', '2025-09-30 10:49:59');
INSERT INTO `teacher_attendance` VALUES ('12', '546456456', '2025-09-30', 'Tuesday', 'Regular', NULL, NULL, '12:24:37', NULL, '8.00', '0', '0', '0', '0', '0', '0', '2025-09-30 12:24:37');
INSERT INTO `teacher_attendance` VALUES ('13', '10000115605', '2025-09-30', 'Tuesday', 'Regular', NULL, NULL, '14:19:20', '14:19:47', '8.00', '0', '0', '0', '0', '0', '0', '2025-09-30 14:19:20');
INSERT INTO `teacher_attendance` VALUES ('14', '345345345345', '2025-10-04', 'Saturday', 'Regular', NULL, NULL, '06:36:24', '08:54:14', '8.00', '0', '0', '0', '0', '0', '0', '2025-10-04 06:36:24');
INSERT INTO `teacher_attendance` VALUES ('15', '546456456', '2025-10-04', 'Saturday', 'Regular', NULL, NULL, '06:37:55', '08:54:17', '8.00', '0', '0', '0', '0', '0', '0', '2025-10-04 06:37:55');
INSERT INTO `teacher_attendance` VALUES ('16', '10000115605', '2025-10-04', 'Saturday', 'Regular', NULL, NULL, '08:54:55', '08:55:05', '8.00', '0', '0', '0', '0', '0', '0', '2025-10-04 08:54:55');


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
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_grade_track_year` (`grade_level`,`academic_track`,`school_year`),
  KEY `idx_grade_level` (`grade_level`),
  KEY `idx_school_year` (`school_year`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data for table: tuition_fee_structure
INSERT INTO `tuition_fee_structure` VALUES ('1', 'Kinder', 'Pre-Elementary', '25000.00', '5000.00', '30000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('2', 'Grade 1', 'Elementary', '28000.00', '5500.00', '33500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('3', 'Grade 2', 'Elementary', '28000.00', '5500.00', '33500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('4', 'Grade 3', 'Elementary', '30000.00', '6000.00', '36000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('5', 'Grade 4', 'Elementary', '30000.00', '6000.00', '36000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('6', 'Grade 5', 'Elementary', '32000.00', '6500.00', '38500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('7', 'Grade 6', 'Elementary', '32000.00', '6500.00', '38500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('8', 'Grade 7', 'Junior High School', '35000.00', '7000.00', '42000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('9', 'Grade 8', 'Junior High School', '35000.00', '7000.00', '42000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('10', 'Grade 9', 'Junior High School', '37000.00', '7500.00', '44500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('11', 'Grade 10', 'Junior High School', '37000.00', '7500.00', '44500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('12', 'Grade 11', 'ABM', '40000.00', '8000.00', '48000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('13', 'Grade 12', 'ABM', '40000.00', '8000.00', '48000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('14', 'Grade 11', 'GAS', '40000.00', '8000.00', '48000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('15', 'Grade 12', 'GAS', '40000.00', '8000.00', '48000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('16', 'Grade 11', 'HE', '42000.00', '8500.00', '50500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('17', 'Grade 12', 'HE', '42000.00', '8500.00', '50500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('18', 'Grade 11', 'HUMSS', '40000.00', '8000.00', '48000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('19', 'Grade 12', 'HUMSS', '40000.00', '8000.00', '48000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('20', 'Grade 11', 'ICT', '45000.00', '9000.00', '54000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('21', 'Grade 12', 'ICT', '45000.00', '9000.00', '54000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('22', 'Grade 11', 'SPORTS', '43000.00', '8500.00', '51500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('23', 'Grade 12', 'SPORTS', '43000.00', '8500.00', '51500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('24', 'Grade 11', 'STEM', '45000.00', '9000.00', '54000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('25', 'Grade 12', 'STEM', '45000.00', '9000.00', '54000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('26', '1st Year', 'Bachelor of Physical Education (BPed)', '50000.00', '10000.00', '60000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('27', '2nd Year', 'Bachelor of Physical Education (BPed)', '52000.00', '10500.00', '62500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('28', '3rd Year', 'Bachelor of Physical Education (BPed)', '54000.00', '11000.00', '65000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('29', '4th Year', 'Bachelor of Physical Education (BPed)', '56000.00', '11500.00', '67500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('30', '1st Year', 'Bachelor of Early Childhood Education (BECEd)', '48000.00', '9500.00', '57500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('31', '2nd Year', 'Bachelor of Early Childhood Education (BECEd)', '50000.00', '10000.00', '60000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('32', '3rd Year', 'Bachelor of Early Childhood Education (BECEd)', '52000.00', '10500.00', '62500.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('33', '4th Year', 'Bachelor of Early Childhood Education (BECEd)', '54000.00', '11000.00', '65000.00', '2024-2025', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('34', 'Kinder', 'Pre-Elementary', '26000.00', '5200.00', '31200.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('35', 'Grade 1', 'Elementary', '29000.00', '5700.00', '34700.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('36', 'Grade 2', 'Elementary', '29000.00', '5700.00', '34700.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('37', 'Grade 3', 'Elementary', '31000.00', '6200.00', '37200.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('38', 'Grade 4', 'Elementary', '31000.00', '6200.00', '37200.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('39', 'Grade 5', 'Elementary', '33000.00', '6700.00', '39700.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('40', 'Grade 6', 'Elementary', '33000.00', '6700.00', '39700.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('41', 'Grade 7', 'Junior High School', '36000.00', '7200.00', '43200.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('42', 'Grade 8', 'Junior High School', '36000.00', '7200.00', '43200.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('43', 'Grade 9', 'Junior High School', '38000.00', '7700.00', '45700.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('44', 'Grade 10', 'Junior High School', '38000.00', '7700.00', '45700.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('45', 'Grade 11', 'STEM', '46000.00', '9200.00', '55200.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('46', 'Grade 12', 'STEM', '46000.00', '9200.00', '55200.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('47', 'Grade 11', 'ICT', '46000.00', '9200.00', '55200.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');
INSERT INTO `tuition_fee_structure` VALUES ('48', 'Grade 12', 'ICT', '46000.00', '9200.00', '55200.00', '2025-2026', '2025-10-04 12:23:05', '2025-10-04 12:23:05');


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

-- Data for table: tuition_fees
INSERT INTO `tuition_fees` VALUES ('1', 'Kinder', 'Pre-Elementary', '30000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('2', 'Grade 1', 'Elementary', '33500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('3', 'Grade 2', 'Elementary', '33500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('4', 'Grade 3', 'Elementary', '36000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('5', 'Grade 4', 'Elementary', '36000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('6', 'Grade 5', 'Elementary', '38500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('7', 'Grade 6', 'Elementary', '38500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('8', 'Grade 7', 'Junior High School', '42000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('9', 'Grade 8', 'Junior High School', '42000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('10', 'Grade 9', 'Junior High School', '44500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('11', 'Grade 10', 'Junior High School', '44500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('12', 'Grade 11', 'ABM', '48000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('13', 'Grade 12', 'ABM', '48000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('14', 'Grade 11', 'GAS', '48000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('15', 'Grade 12', 'GAS', '48000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('16', 'Grade 11', 'HUMSS', '48000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('17', 'Grade 12', 'HUMSS', '48000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('18', 'Grade 11', 'STEM', '54000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('19', 'Grade 12', 'STEM', '54000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('20', 'Grade 11', 'ICT', '54000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('21', 'Grade 12', 'ICT', '54000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('22', 'Grade 11', 'HE', '50500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('23', 'Grade 12', 'HE', '50500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('24', 'Grade 11', 'SPORTS', '50500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('25', 'Grade 12', 'SPORTS', '50500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('26', '1st Year', 'Bachelor of Physical Education (BPed)', '60000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('27', '2nd Year', 'Bachelor of Physical Education (BPed)', '62500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('28', '3rd Year', 'Bachelor of Physical Education (BPed)', '65000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('29', '4th Year', 'Bachelor of Physical Education (BPed)', '67500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('30', '1st Year', 'Bachelor of Early Childhood Education (BECEd)', '57500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('31', '2nd Year', 'Bachelor of Early Childhood Education (BECEd)', '60000.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('32', '3rd Year', 'Bachelor of Early Childhood Education (BECEd)', '62500.00', '2025-2026', '2025-10-04 12:53:03');
INSERT INTO `tuition_fees` VALUES ('33', '4th Year', 'Bachelor of Early Childhood Education (BECEd)', '65000.00', '2025-2026', '2025-10-04 12:53:03');


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
INSERT INTO `violation_types` VALUES ('11', 'No id', '1', '2025-09-15 10:00:17', '2025-09-15 10:00:17');
INSERT INTO `violation_types` VALUES ('12', 'sda', '0', '2025-09-23 17:37:39', '2025-09-23 17:37:45');

SET FOREIGN_KEY_CHECKS=1;
