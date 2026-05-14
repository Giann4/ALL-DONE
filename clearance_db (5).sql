-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 14, 2026 at 09:10 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clearance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` text NOT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `user_name`, `user_role`, `action_type`, `action_description`, `ip_address`, `created_at`) VALUES
(1, 31, 'CARL JONATHAN BALOG', 'student', 'LOGIN', 'Student logged in to the system.', '::1', '2026-05-11 06:09:32'),
(2, 1, 'Admin', 'super_admin', 'LOGIN', 'Super Admin logged in to the system.', '::1', '2026-05-11 06:09:41'),
(3, 31, 'CARL JONATHAN BALOG', 'student', 'LOGIN', 'Student logged in to the system.', '::1', '2026-05-11 10:50:04'),
(4, 31, 'CARL JONATHAN BALOG', 'student', 'LOGIN', 'Student logged in to the system.', '::1', '2026-05-12 15:04:23'),
(5, 2, 'Admin User', 'admin', 'LOGIN', 'Admin logged in to the system.', '::1', '2026-05-12 15:58:34'),
(6, 35, 'GIAN BENEDICT ESIO', 'student', 'LOGIN', 'Student logged in to the system.', '::1', '2026-05-12 16:03:41'),
(7, 0, 'c23-4908-01@spist.edu.ph', 'student', 'PASSWORD RESET', 'Student reset password using Gmail OTP verification.', '::1', '2026-05-12 16:26:32'),
(8, 0, 'c23-4953-01@spist.edu.ph', 'student', 'PASSWORD RESET', 'Student reset password using Gmail OTP verification.', '::1', '2026-05-12 16:30:53');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `role`, `profile_photo`) VALUES
(1, 'Admin', 'admin@gmail.com', '2637a5c30af69a7bad877fdb65fbd78b', 'super_admin', 'admin_1_1777649956.png'),
(2, 'Admin User', 'admin2@gmail.com', '2637a5c30af69a7bad877fdb65fbd78b', 'admin', ''),
(3, 'Mark Paredes', 'markparedes54321@gmail.com', '2bcf93cd395823a0ee296b497a7f5b6d', 'admin', '');

-- --------------------------------------------------------

--
-- Table structure for table `class_members`
--

CREATE TABLE `class_members` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_requests`
--

CREATE TABLE `class_requests` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `status` varchar(50) DEFAULT 'Requesting',
  `result` varchar(50) DEFAULT '',
  `comment` text DEFAULT NULL,
  `date_signed` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_requests`
--

INSERT INTO `class_requests` (`id`, `class_id`, `student_id`, `subject`, `status`, `result`, `comment`, `date_signed`, `created_at`) VALUES
(1, 1, 3, 'S A A D', 'Reviewed', 'Passed', 'GOOD', '2026-04-12', '2026-04-12 06:29:04'),
(3, 3, 3, 'NETWORKING', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 06:45:06'),
(4, 4, 3, 'IAS', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 06:46:59'),
(5, 5, 3, 'ARDUINO', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-15', '2026-04-12 06:48:47'),
(6, 6, 3, 'ENGLISH', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 06:50:58'),
(7, 7, 3, 'CODE', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 07:00:10'),
(8, 8, 3, 'CALCULUS', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 15:58:55'),
(9, 9, 3, 'PE', 'Reviewed', 'Passed', '1234', '2026-04-12', '2026-04-12 16:06:33'),
(10, 10, 12, 'SCIENCE', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 17:53:36'),
(11, 11, 14, 'AP', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 19:21:23'),
(12, 14, 19, 'S A A D', 'Reviewed', 'Failed', 'SEE YOU NEXT YEAR', '2026-04-18', '2026-04-18 07:20:48'),
(13, 14, 20, 'S A A D', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-18', '2026-04-18 07:22:17'),
(14, 15, 19, 'CODING', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-18', '2026-04-18 07:27:02'),
(18, 22, 31, 'SIPP', 'Reviewed', 'Passed', 'CONGRATS PO', '2026-05-01', '2026-05-01 14:34:02'),
(19, 21, 31, 'Application Development & Emerging Technologies', 'Reviewed', 'Passed', 'CONGRATS PO', '2026-05-01', '2026-05-01 14:34:25'),
(20, 20, 31, 'DSA LAB', 'Reviewed', 'Passed', 'CONGRATS', '2026-05-01', '2026-05-01 14:34:50'),
(21, 19, 31, 'INFORMATION ASSURANCE AND SECURITY 1', 'Reviewed', 'Passed', 'CONGRATS PO', '2026-05-01', '2026-05-01 14:35:34'),
(22, 18, 31, 'SECURITY AND ESSENTIAL', 'Reviewed', 'Passed', 'CONGRATS PO', '2026-05-01', '2026-05-01 14:35:49'),
(23, 23, 31, 'System Analysis and Design', 'Reviewed', 'Passed', 'CONGRATS PO', '2026-05-01', '2026-05-01 14:38:57'),
(24, 24, 31, 'RIZAL LIFE AND WORKS', 'Reviewed', 'Passed', 'CONGRATS PO', '2026-05-01', '2026-05-01 14:42:07'),
(25, 24, 33, 'RIZAL LIFE AND WORKS', 'Reviewed', 'Passed', 'CONGRATS PO', '2026-05-01', '2026-05-01 14:53:09'),
(26, 23, 33, 'System Analysis and Design', 'Reviewed', 'Passed', 'CONGRATS PO', '2026-05-01', '2026-05-01 14:53:22'),
(27, 22, 33, 'SIPP', 'Reviewed', 'Passed', 'CONGRATS PO', '2026-05-01', '2026-05-01 14:53:33'),
(28, 21, 33, 'Application Development & Emerging Technologies', 'Reviewed', 'Passed', 'CONGRATS PO', '2026-05-01', '2026-05-01 14:53:43'),
(29, 20, 33, 'DSA LAB', 'Reviewed', 'Passed', 'CONGRATS PO', '2026-05-01', '2026-05-01 14:53:52'),
(30, 19, 33, 'INFORMATION ASSURANCE AND SECURITY 1', 'Reviewed', 'Passed', 'CONGRATS PO', '2026-05-01', '2026-05-01 14:54:03'),
(31, 18, 33, 'SECURITY AND ESSENTIAL', 'Reviewed', 'Passed', 'CONGRATS PO', '2026-05-01', '2026-05-01 14:54:17');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `user_type` enum('student','teacher','admin') NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `failed_attempts` int(11) DEFAULT 0,
  `last_sent_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `user_type`, `otp_code`, `expires_at`, `is_used`, `created_at`, `failed_attempts`, `last_sent_at`, `verified_at`, `locked_until`) VALUES
(14, 'markparedes54321@gmail.com', 'student', '119131', '2026-05-12 19:14:30', 0, '2026-05-12 17:09:30', 5, '2026-05-12 19:09:30', NULL, '2026-05-12 19:19:48');

-- --------------------------------------------------------

--
-- Table structure for table `registrar_email_logs`
--

CREATE TABLE `registrar_email_logs` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `registrar_email` varchar(150) NOT NULL,
  `email_subject` varchar(255) NOT NULL,
  `status` enum('Sent','Failed') DEFAULT 'Sent',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrar_email_logs`
--

INSERT INTO `registrar_email_logs` (`id`, `student_id`, `registrar_email`, `email_subject`, `status`, `sent_at`) VALUES
(1, 24, 'gianbenedictesio@gmail.com', 'Student Clearance Result - TULAGAN, KYLE', 'Sent', '2026-05-01 08:53:49'),
(2, 31, 'sidyeykaru@gmail.com', 'Student Clearance Result - BALOG, CARL JONATHAN', 'Sent', '2026-05-01 14:48:25'),
(3, 33, 'geraldtraifalgarmamay29@gmail.com', 'Student Clearance Result - MAMAY, GERALD', 'Sent', '2026-05-01 14:59:18'),
(4, 31, 'gianbenedictesio@gmail.com', 'Student Clearance Result - BALOG, CARL JONATHAN', 'Sent', '2026-05-01 16:21:05'),
(5, 31, 'darylesio6@gmail.com', 'Student Clearance Result - BALOG, CARL JONATHAN', 'Sent', '2026-05-01 16:23:51'),
(6, 31, 'gianbenedictesio@gmail.com', 'Student Clearance Result - BALOG, CARL JONATHAN', 'Sent', '2026-05-11 11:02:33');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `system_name` varchar(255) NOT NULL DEFAULT 'Online Clearance Management System',
  `school_name` varchar(255) NOT NULL DEFAULT 'Southern Philippines Institute of Science and Technology',
  `school_year` varchar(100) NOT NULL DEFAULT '2025-2026',
  `maintenance_mode` enum('ON','OFF') DEFAULT 'OFF',
  `theme_mode` enum('dark','light') DEFAULT 'dark',
  `theme_color` varchar(50) DEFAULT '#6d28d9',
  `smtp_email` varchar(255) DEFAULT '',
  `smtp_password` varchar(255) DEFAULT '',
  `logo` varchar(255) DEFAULT '',
  `favicon` varchar(255) DEFAULT '',
  `backup_reminder` enum('ON','OFF') DEFAULT 'ON',
  `auto_logout_time` int(11) DEFAULT 30,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `system_name`, `school_name`, `school_year`, `maintenance_mode`, `theme_mode`, `theme_color`, `smtp_email`, `smtp_password`, `logo`, `favicon`, `backup_reminder`, `auto_logout_time`, `updated_at`) VALUES
(1, 'Online Clearance Management System', 'Southern Philippines Institute of Science and Technology', '2025-2026', 'OFF', 'dark', '#6d28d9', '', '', '', '', 'ON', 30, '2026-05-11 07:17:27');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_album`
--

CREATE TABLE `teacher_album` (
  `id` int(11) NOT NULL,
  `teacher_name` varchar(150) NOT NULL,
  `teacher_photo` varchar(255) DEFAULT NULL,
  `teacher_email` varchar(150) DEFAULT NULL,
  `teacher_contact` varchar(50) DEFAULT NULL,
  `teacher_department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_album`
--

INSERT INTO `teacher_album` (`id`, `teacher_name`, `teacher_photo`, `teacher_email`, `teacher_contact`, `teacher_department`, `created_at`) VALUES
(1, 'ALLETE MANZANERO', '1776320441_4380.jpg', 'alette@gmail.com', '09851642711', 'IT DEPARTMENT', '2026-04-16 06:11:44'),
(3, 'ANGELO VILLIANUEVA', '1776320962_7238.jpg', 'angelo@gmail.com', '09851642711', 'IT DEPARTMENT', '2026-04-16 06:29:22'),
(4, 'KENJI UY', '1776321024_9877.jpg', 'kenji@gmail.com', '09851642711', 'IT DEPARTMENT', '2026-04-16 06:30:24'),
(5, 'DENNIS SANTOS', '1776321083_1276.jpg', 'denis@gmail.com', '09851642711', 'IT DEPARTMENT', '2026-04-16 06:31:13'),
(6, 'DONALENE TOCMO', '1776321149_2576.jpg', 'donalene@gmail.com', '09851642711', 'IT DEPARTMENT', '2026-04-16 06:32:29'),
(7, 'MARIA ANGEL BERNASE', '1776321473_6911.jpg', 'angel@gmail.com', '09851642711', 'IT DEPARTMENT', '2026-04-16 06:37:53'),
(8, 'RAYMART FALLER', '1778265044_5450.png', 'c23-4908-31@spist.edu.ph', '09851642711', 'IT DEPARTMENT', '2026-05-08 17:52:45');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_classes`
--

CREATE TABLE `teacher_classes` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `course` varchar(50) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_classes`
--

INSERT INTO `teacher_classes` (`id`, `teacher_id`, `subject`, `course`, `class_code`, `created_at`) VALUES
(1, 2, 'S A A D', 'BSIT 3', 'mc46nfw', '2026-04-12 05:00:39'),
(3, 5, 'NETWORKING', 'BSIT 3', '2puqa93', '2026-04-12 06:44:27'),
(4, 6, 'IAS', 'BSIT 3', '4ti31ze', '2026-04-12 06:46:39'),
(5, 7, 'ARDUINO', 'BSIT 3', 'es0jo1c', '2026-04-12 06:48:26'),
(6, 8, 'ENGLISH', 'BSIT 3', 'hu8pikn', '2026-04-12 06:50:34'),
(7, 9, 'CODE', 'BSIT 3', 'dvlccmp', '2026-04-12 06:59:46'),
(8, 10, 'CALCULUS', 'BSIT 3', '16ccs8k', '2026-04-12 15:58:31'),
(9, 11, 'PE', 'BSIT 3', '1d9l5jy', '2026-04-12 16:05:56'),
(10, 2, 'SCIENCE', 'BSIT 1', '2DWKS27J', '2026-04-12 17:07:13'),
(11, 13, 'AP', 'BSIT 2', 'SVUA4CFL', '2026-04-12 19:20:08'),
(13, 4, 'HUMAN COMPUTER INTERACTION', 'BSIT 3', 'VXZPLNYK', '2026-04-15 14:00:24'),
(14, 18, 'S A A D', 'BSIT 3', 'EJCTZS83', '2026-04-18 07:18:32'),
(15, 21, 'CODING', 'BSIT 3', 'JL5LCANK', '2026-04-18 07:26:25'),
(18, 27, 'SECURITY AND ESSENTIAL', 'BSIT 3', 'W8YESCJ3', '2026-05-01 14:21:07'),
(19, 27, 'INFORMATION ASSURANCE AND SECURITY 1', 'BSIT 3', 'NR7K4GNC', '2026-05-01 14:22:13'),
(20, 28, 'DSA LAB', 'BSIT 3', 'EURTR6EA', '2026-05-01 14:25:04'),
(21, 29, 'Application Development & Emerging Technologies', 'BSIT 3', 'RNVDSYDU', '2026-05-01 14:27:28'),
(22, 30, 'SIPP', 'BSIT 3', '5YJNTY5U', '2026-05-01 14:29:44'),
(23, 25, 'System Analysis and Design', 'BSIT 3', 'SDUARR92', '2026-05-01 14:36:50'),
(24, 32, 'RIZAL LIFE AND WORKS', 'BSIT 3', 'WNT59W3M', '2026-05-01 14:40:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('student','teacher') DEFAULT NULL,
  `course` varchar(50) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `contact_number`, `password`, `role`, `course`, `profile_photo`, `is_deleted`, `deleted_at`) VALUES
(1, 'Mark', 'Paredes', 'markparedes54321@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'student', 'BSIT 3', 'user_1_1775969467.jpg', 1, '2026-04-25 12:44:30'),
(4, 'alette', 'manzanero', 'alette@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'teacher', NULL, NULL, 1, '2026-04-25 12:54:07'),
(5, 'Kenji', 'Uy', 'kenji@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'teacher', NULL, NULL, 1, '2026-04-25 12:54:03'),
(6, 'Angelo', 'Villianueva', 'Angelo@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'teacher', NULL, NULL, 1, '2026-04-25 12:44:46'),
(7, 'Angel', 'Bernase', 'Angel@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'teacher', NULL, 'teacher_7_1776276983.png', 1, '2026-04-25 12:44:40'),
(9, 'Dennis', 'Santos', 'dennis@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'teacher', NULL, NULL, 1, '2026-04-25 12:44:37'),
(10, 'Daryl', 'Esio', 'daryl@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'teacher', NULL, NULL, 1, '2026-04-23 14:24:38'),
(12, 'natasha', 'Esio', 'natasha@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'student', 'BSIT 1', NULL, 1, '2026-04-25 12:44:28'),
(14, 'JECK', 'DETERA', 'jeck@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'student', 'BSIT 2', 'student_14_1776021794.png', 1, '2026-04-25 12:44:26'),
(15, 'Harlie', 'Poras', 'harlie@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'student', 'BSIT 3', NULL, 1, '2026-04-25 12:44:24'),
(16, 'Martin', 'Angeles', 'martin@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'student', 'BSIT 3', NULL, 1, '2026-04-23 13:47:21'),
(18, 'DONALENE', 'TOCMO', 'donalene@gmail.com', '09851642711', 'a8d1655d2c7eb752e8bf3cc928fd6baf', 'teacher', NULL, NULL, 1, '2026-04-25 12:44:33'),
(19, 'GIAN', 'ESIO', 'gian@gmail.com', '09851642711', '81dc9bdb52d04dc20036dbd8313ed055', 'student', 'BSIT 3', NULL, 1, '2026-04-23 14:24:45'),
(22, 'PATRICIO EZEKIEL', 'MAGDAME', 'patricio@gmail.com', '09602097975', '60193d6c97cd6460457cccddc827c568', 'student', 'BSIT 1', 'student_22_1776927318.png', 1, '2026-04-25 12:44:22'),
(23, 'GIAN', 'ESIO', 'gianesio@gmail.cm', '09602097975', 'a49e82406c16eb5b66e9010ac9122d0c', 'student', 'BSIT 3', NULL, 0, NULL),
(24, 'KYLE', 'TULAGAN', 'kyle@gmail.com', '09602097975', '4331dc2425cffc324497c0f6c257904a', 'student', 'BSIT 3', 'student_24_1777385720.png', 0, NULL),
(25, 'DONALENE', 'TOCMO', 'c24-4908-01@spist.edu.ph', '09080504063', 'bcb5abc2f942f9751c4fb61caaacacb5', 'teacher', NULL, 'teacher_25_1777388454.png', 0, NULL),
(26, 'KIEL', 'MAGDAME', 'c23-4908-02@spist.edu.ph', '09080504063', 'cd57bd10e2cedfd0896e845ac5e50d88', 'student', 'BSIT 1', 'student_26_1777389748.png', 0, NULL),
(27, 'ANGELO', 'VILLIANUEVA', 'c24-4908-02@spist.edu.ph', '09602097975', 'ff3d73e7f1b5502d7b5d4108fb929012', 'teacher', NULL, NULL, 0, NULL),
(28, 'DENNIS', 'SANTOS', 'c24-4908-03@spist.edu.ph', '09602097975', 'ee68a5ce46ea94c55095c3c5064c0bf8', 'teacher', NULL, NULL, 0, NULL),
(29, 'ANGEL', 'BERNASE', 'c24-4908-04@spist.edu.ph', '09564875621', 'cf13b392babb470c1a400ce91ccb4c3d', 'teacher', NULL, NULL, 0, NULL),
(30, 'Mekaella Marie', 'Almeron', 'c24-4908-05@spist.edu.ph', '09602097975', 'd1ecea84cc49202dbed6fc3bfcff86cc', 'teacher', NULL, NULL, 0, NULL),
(31, 'CARL JONATHAN', 'BALOG', 'c23-4953-01@spist.edu.ph', '09080504063', '$2y$10$AZ1OLAn2g4aYcz.Rnm0P7eLBmJnd9zt8S9Ly6RjISUmJ87i/y0eGm', 'student', 'BSIT 3', 'student_31_1777648013.png', 0, NULL),
(32, 'ANGELO', 'BOLINGO', 'c24-4908-06@spist.edu.ph', '09602097975', '1a37c050ae64c1e29513e6ef53beca7b', 'teacher', NULL, NULL, 0, NULL),
(33, 'GERALD', 'MAMAY', 'c23-4871-01@spist.edu.ph', '09602097975', '6655947a83e8e1b9533c8d09ac83d1bd', 'student', 'BSIT 3', NULL, 0, NULL),
(34, 'ARKIN', 'BALINTOS', 'c23-3333-33@spist.edu.ph', '09851642711', '91bfbf6c81d88a37e28ade11f8e5a58a', 'student', 'BSIT 3', NULL, 0, NULL),
(35, 'GIAN BENEDICT', 'ESIO', 'c23-4908-01@spist.edu.ph', '09851642711', '$2y$10$KNH1/0JQkkl6z8MDM05mveCDqzUOpCdzY3GYk/Ok5PWPfRdqn9ZPi', 'student', 'BSIT 3', NULL, 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_members`
--
ALTER TABLE `class_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_requests`
--
ALTER TABLE `class_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registrar_email_logs`
--
ALTER TABLE `registrar_email_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_album`
--
ALTER TABLE `teacher_album`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_classes`
--
ALTER TABLE `teacher_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_code` (`class_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `class_members`
--
ALTER TABLE `class_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_requests`
--
ALTER TABLE `class_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `registrar_email_logs`
--
ALTER TABLE `registrar_email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teacher_album`
--
ALTER TABLE `teacher_album`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `teacher_classes`
--
ALTER TABLE `teacher_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
