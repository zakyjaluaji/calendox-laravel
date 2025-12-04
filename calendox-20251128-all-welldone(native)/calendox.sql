/*
 Navicat Premium Dump SQL

 Source Server         : SIPADU
 Source Server Type    : MySQL
 Source Server Version : 100339 (10.3.39-MariaDB-0ubuntu0.20.04.2)
 Source Host           : 103.104.177.233:3306
 Source Schema         : calendox

 Target Server Type    : MySQL
 Target Server Version : 100339 (10.3.39-MariaDB-0ubuntu0.20.04.2)
 File Encoding         : 65001

 Date: 28/11/2025 10:36:35
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for appointments
-- ----------------------------
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `pic_name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `attachment_filename` varchar(255) DEFAULT NULL,
  `color` varchar(32) DEFAULT NULL,
  `google_event_id` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_title_pic` (`id`,`title`,`pic_name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of appointments
-- ----------------------------
BEGIN;
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (7, 'Proyek Droid', 'Ulya', '2025-11-02', '2025-11-05', '09:00:00', '10:00:00', NULL, NULL, 'kla5llaevflan84hnpe98etplk', '2025-11-04 22:11:04');
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (8, 'Proyek Event Kalender', 'Zaky', '2025-11-05', '2025-11-07', '14:00:00', '16:00:00', 'Kebijakan_PISN_2025.pdf', '#8b5cf6', 'j48be37pkisepk9492mf7voa3c', '2025-11-04 22:11:04');
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (10, 'Proyek Figma', 'Zaky', '2025-11-11', '2025-11-17', '09:00:00', '12:00:00', NULL, '#f43f5e', 'hr665ub8o4k9g18lpfe052kb4g', '2025-11-04 22:11:04');
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (11, 'Proyek iFlow', 'Ulya', '2025-11-19', '2025-11-21', '09:00:00', '10:00:00', NULL, '#10b981', 'it0ekdf3mu5tk01tukek4uro1c', '2025-11-04 22:11:04');
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (16, 'Kontrol Gigi', '', '2025-08-05', '2025-08-06', '00:00:00', '23:59:00', NULL, NULL, '6sqjed9kc9gj0b9m6dgjib9k6gq3cbb26pj34b9icgqm4pj46cs34opi60', '2025-11-05 04:50:00');
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (21, 'Happy birthday!', '', '2025-11-09', '2025-11-10', '00:00:00', '23:59:00', NULL, NULL, 'c53hm8a4173cdeef2lc0odj6c4_20251109', '2025-11-05 13:18:03');
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (38, 'Evaluasi Proyek Event Calendar', 'Ade', '2025-11-24', '2025-11-25', '13:00:00', '15:00:00', NULL, '#1e3a8a', 'ifiqjk8khec3b4ttf8628vej2k', '2025-11-24 13:41:00');
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (39, 'Koordinasi Penyusunan Dokumen Tata Kelola Informasi', 'Prastawa Sunu', '2025-12-01', '2025-12-01', '10:00:00', '12:00:00', 'Und_Rapat_Koordinasi_Penyusunan_Dokumen_Tata_Kelola_Informasi.pdf', '#3b82f6', '911sh8frvpq39iamaam7ii7gd8', '2025-11-27 09:21:43');
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (41, 'perjadin', 'rektor', '2025-11-27', '2025-11-30', '09:00:00', '10:00:00', 'Surat_Tugas_perjadin_Makasar.pdf', '#3b82f6', 'vjcqveocc1bo45f2fstrq70hc4', '2025-11-27 10:39:07');
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (42, 'perjadin ke jambi', 'rektor', '2025-12-05', '2025-12-07', '09:00:00', '10:00:00', NULL, '#3b82f6', NULL, '2025-11-27 10:52:16');
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (43, 'perjadin maluku', 'rektor', '2025-12-12', '2025-12-15', '09:00:00', '10:00:00', NULL, '#3b82f6', NULL, '2025-11-27 10:52:52');
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (44, 'ASMEN PRODI FOTGRAFI', 'REKTOR', '2025-12-04', '2025-12-04', '09:00:00', '10:00:00', NULL, '#3b82f6', NULL, '2025-11-27 15:00:01');
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (45, 'AUDIENSI BTN', 'REKTOR', '2025-12-02', '2025-12-02', '10:30:00', '13:00:00', NULL, '#3b82f6', NULL, '2025-11-27 15:01:28');
INSERT INTO `appointments` (`id`, `title`, `pic_name`, `start_date`, `end_date`, `start_time`, `end_time`, `attachment_filename`, `color`, `google_event_id`, `created_at`) VALUES (46, 'AUDIENSI BTN', 'REKTOR', '2025-12-03', '2025-12-03', '09:00:00', '12:00:00', NULL, '#3b82f6', NULL, '2025-11-27 15:02:16');
COMMIT;

-- ----------------------------
-- Table structure for participant
-- ----------------------------
DROP TABLE IF EXISTS `participant`;
CREATE TABLE `participant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_unique_event_email` (`appointment_id`,`email`),
  KEY `appointment_id` (`appointment_id`),
  CONSTRAINT `fk_participant_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------
-- Records of participant
-- ----------------------------
BEGIN;
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (16, 7, 'Agung Dwi Susilo, S.T., M.M.', 'agungdsusilo@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (17, 7, 'Chandra Aan Setiawan, S.Kom', 'chandra@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (18, 7, 'Irvan Muhamad Nursyahid, S.Kom., M.M.', 'irvan@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (19, 7, 'Ulya Ruhana, S.Kom.', 'ulyaruhana@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (20, 7, 'Fransisca Pramesti, S.Si, M.Eng.', 'pramesti@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (21, 7, 'Muhammad Zaky Jaluaji, A.Md.Kom.', 'zakyjaluaji@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (22, 8, 'Chandra Aan Setiawan, S.Kom', 'chandra@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (23, 8, 'Ade Hidayat Santoso, S.Kom.', 'adehidayat86@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (24, 8, 'Fransisca Pramesti, S.Si, M.Eng.', 'pramesti@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (25, 10, 'Chandra Aan Setiawan, S.Kom', 'chandra@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (26, 10, 'Ade Hidayat Santoso, S.Kom.', 'adehidayat86@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (27, 10, 'Fransisca Pramesti, S.Si, M.Eng.', 'pramesti@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (28, 10, 'Ulya Ruhana, S.Kom.', 'ulyaruhana@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (29, 10, 'Muhammad Zaky Jaluaji, A.Md.Kom.', 'zakyjaluaji@isi-ska.ac.id', '2025-11-04 22:11:04');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (32, 7, 'Ade Hidayat Santoso, S.Kom.', 'adehidayat86@isi-ska.ac.id', '2025-11-05 09:33:22');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (37, 7, 'Dr. Anung Rachman, ST., M.Kom.', 'anung@isi-ska.ac.id', '2025-11-05 13:06:00');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (38, 8, 'Dr. Anung Rachman, ST., M.Kom.', 'anung@isi-ska.ac.id', '2025-11-05 13:06:30');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (39, 10, 'Dr. Anung Rachman, ST., M.Kom.', 'anung@isi-ska.ac.id', '2025-11-05 13:06:43');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (40, 11, 'Dr. Anung Rachman, ST., M.Kom.', 'anung@isi-ska.ac.id', '2025-11-05 13:06:56');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (41, 11, 'Ade Hidayat Santoso, S.Kom.', 'adehidayat86@isi-ska.ac.id', '2025-11-05 13:07:06');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (42, 11, 'Muhammad Zaky Jaluaji, A.Md.Kom.', 'zakyjaluaji@isi-ska.ac.id', '2025-11-05 13:07:13');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (49, 38, 'Dr. Anung Rachman, ST., M.Kom.', 'anung@isi-ska.ac.id', '2025-11-24 13:41:00');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (50, 38, 'Ade Hidayat Santoso, S.Kom.', 'adehidayat86@isi-ska.ac.id', '2025-11-24 13:41:00');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (51, 38, 'Ulya Ruhana, S.Kom.', 'ulyaruhana@isi-ska.ac.id', '2025-11-24 13:41:00');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (52, 38, 'Muhammad Zaky Jaluaji, A.Md.Kom.', 'zakyjaluaji@isi-ska.ac.id', '2025-11-24 13:41:00');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (56, 39, 'Yanuar Dwi Anggara, S.H.', 'yanuarda@isi-ska.ac.id', '2025-11-27 09:38:00');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (57, 39, 'Chandra Aan Setiawan, S.Kom', 'chandra@isi-ska.ac.id', '2025-11-27 09:38:12');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (58, 39, 'Ade Hidayat Santoso, S.Kom.', 'adehidayat86@isi-ska.ac.id', '2025-11-27 09:38:17');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (59, 39, 'Iwan Sulistyo, S.E., M.M.', 'iwansulistyo@isi-ska.ac.id', '2025-11-27 09:38:22');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (60, 39, 'Irvan Muhamad Nursyahid, S.Kom., M.M.', 'irvan@isi-ska.ac.id', '2025-11-27 09:38:37');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (61, 39, 'Agung Dwi Susilo, S.T., M.M.', 'agungdsusilo@isi-ska.ac.id', '2025-11-27 09:38:42');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (62, 39, 'Sartono, S.Sn.', 'tono@isi-ska.ac.id', '2025-11-27 09:38:51');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (63, 39, 'Esha Karwinarno, S.Sn., M.M.', 'esha@isi-ska.ac.id', '2025-11-27 09:39:05');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (64, 39, 'Dony Yuwono, S.Kom., M.M.', 'dony.yuwono@isi-ska.ac.id', '2025-11-27 09:39:09');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (65, 39, 'Agung Cahyana, ST., M.Eng.', 'agungcahya@isi-ska.ac.id', '2025-11-27 09:39:16');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (66, 39, 'Prastawa Sunu, S.Sos., M.M.', 'prastawa@isi-ska.ac.id', '2025-11-27 09:39:31');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (67, 39, 'Dr. Anung Rachman, ST., M.Kom.', 'anung@isi-ska.ac.id', '2025-11-27 09:39:36');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (68, 39, 'Dr. Bagong Pujiono, M.Sn.', 'bagong@isi-ska.ac.id', '2025-11-27 09:39:47');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (69, 39, 'Dr. Agung Purnomo, S.Sn., M.Sn.', 'agung70@isi-ska.ac.id', '2025-11-27 09:39:54');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (71, 39, 'Dr. Drs. H. M. Arif Jati Purnomo, M.Sn.', 'arifjati@isi-ska.ac.id', '2025-11-27 09:40:16');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (72, 39, 'Dr. Isa Ansari, M.Hum.', 'ansori@isi-ska.ac.id', '2025-11-27 09:52:52');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (76, 41, 'Dr. Bondet Wrahatnala, S.Sos., M.Sn.', 'bondet@isi-ska.ac.id', '2025-11-27 10:42:49');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (77, 41, 'Dr. Isa Ansari, M.Hum.', 'ansori@isi-ska.ac.id', '2025-11-27 10:43:08');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (78, 41, 'Dr. Dra. Tatik Harpawati, M.Sn.', 'tatik@isi-ska.ac.id', '2025-11-27 10:43:18');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (79, 41, 'Prastawa Sunu, S.Sos., M.M.', 'prastawa@isi-ska.ac.id', '2025-11-27 10:43:25');
INSERT INTO `participant` (`id`, `appointment_id`, `name`, `email`, `created_at`) VALUES (80, 41, 'Sulistyani Astuti, S.Sn.', 'sulis@isi-ska.ac.id', '2025-11-27 10:43:35');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
