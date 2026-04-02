-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 02, 2026 at 07:32 PM
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
-- Database: `capstone`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Present','Late','Absent') NOT NULL DEFAULT 'Present',
  `approval_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `training_id`, `user_id`, `log_date`, `time_in`, `time_out`, `status`, `approval_status`, `approved_by`, `approved_at`, `approval_remarks`, `created_at`) VALUES
(1, 1, 9, '2026-03-30', '15:23:27', '15:40:53', 'Present', 'Approved', 8, '2026-04-02 11:35:43', '', '2026-03-30 07:23:27'),
(2, 1, 9, '2026-04-01', '21:21:04', '21:21:09', 'Present', 'Approved', 8, '2026-04-02 11:35:46', '', '2026-04-01 13:21:04'),
(3, 3, 10, '2026-04-02', '13:46:57', NULL, 'Present', 'Pending', NULL, NULL, NULL, '2026-04-02 05:46:57');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `issued_by` int(11) NOT NULL,
  `certificate_no` varchar(50) NOT NULL,
  `issued_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completion_date` date DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `is_revoked` tinyint(1) NOT NULL DEFAULT 0,
  `revoked_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `training_id`, `user_id`, `issued_by`, `certificate_no`, `issued_at`, `completion_date`, `remarks`, `is_revoked`, `revoked_at`) VALUES
(1, 1, 9, 8, 'PTMS-2026-0001-0009', '2026-04-02 14:28:29', '2026-03-31', 'System-generated certificate', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_records`
--

CREATE TABLE `evaluation_records` (
  `id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `evaluated_by` int(11) NOT NULL,
  `evaluation_date` date NOT NULL,
  `attendance_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `knowledge_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `skills_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `behavior_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `overall_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `result_status` enum('Passed','Needs Improvement','Failed') NOT NULL DEFAULT 'Passed',
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` int(11) NOT NULL,
  `training_id` int(11) DEFAULT NULL,
  `sender_user_id` int(11) NOT NULL,
  `recipient_user_id` int(11) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `delivery_channel` enum('In-App','Email + In-App') NOT NULL DEFAULT 'In-App',
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_code` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `reset_token`, `expires_at`, `used`, `created_at`, `reset_code`) VALUES
(2, 7, '', '2026-03-30 13:25:16', 1, '2026-03-30 05:15:16', '359118'),
(3, 8, '', '2026-03-30 13:36:39', 1, '2026-03-30 05:26:39', '908917'),
(20, 9, '', '2026-03-30 15:09:00', 1, '2026-03-30 06:59:00', '420766'),
(23, 13, '', '2026-04-02 13:43:07', 1, '2026-04-02 05:33:07', '394004');

-- --------------------------------------------------------

--
-- Table structure for table `trainee_activity_logs`
--

CREATE TABLE `trainee_activity_logs` (
  `id` int(11) NOT NULL,
  `trainee_user_id` int(11) NOT NULL,
  `activity_type` varchar(100) NOT NULL,
  `activity_message` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_seen` tinyint(1) NOT NULL DEFAULT 0,
  `seen_by_officer_id` int(11) DEFAULT NULL,
  `seen_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trainee_activity_logs`
--

INSERT INTO `trainee_activity_logs` (`id`, `trainee_user_id`, `activity_type`, `activity_message`, `created_at`, `is_seen`, `seen_by_officer_id`, `seen_at`) VALUES
(1, 9, 'Attendance', 'Timed IN for training ID #1', '2026-04-01 21:21:04', 0, NULL, NULL),
(2, 9, 'Attendance', 'Timed OUT for training ID #1', '2026-04-01 21:21:09', 0, NULL, NULL),
(3, 9, 'Profile Update', 'Updated profile information (Name/Email/Contact).', '2026-04-01 21:21:44', 0, NULL, NULL),
(4, 9, 'Profile Update', 'Updated profile information (Name/Email/Contact).', '2026-04-01 21:21:49', 0, NULL, NULL),
(5, 9, 'Profile Update', 'Updated profile information (Name/Email/Contact).', '2026-04-01 21:40:33', 0, NULL, NULL),
(6, 9, 'Profile Update', 'Updated profile information (Name/Email/Contact).', '2026-04-01 21:46:11', 0, NULL, NULL),
(7, 10, 'Attendance', 'Timed IN for training ID #3', '2026-04-02 13:46:57', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `trainings`
--

CREATE TABLE `trainings` (
  `id` int(11) NOT NULL,
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(180) DEFAULT NULL,
  `trainer_user_id` int(11) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 20,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trainings`
--

INSERT INTO `trainings` (`id`, `title`, `description`, `schedule_date`, `start_time`, `end_time`, `venue`, `trainer_user_id`, `capacity`, `created_by`, `created_at`) VALUES
(1, 'Finance', NULL, '2026-03-31', '15:00:00', '17:00:00', 'Mabalacat', 8, 20, NULL, '2026-03-30 07:22:05'),
(2, 'Management', NULL, '2026-03-31', '08:00:00', '17:00:00', 'Dau', 8, 20, NULL, '2026-03-30 14:23:07'),
(3, 'Loan Officer', NULL, '2026-03-20', '09:00:00', '18:00:00', 'Duquit', 8, 20, NULL, '2026-03-30 14:24:34');

-- --------------------------------------------------------

--
-- Table structure for table `training_participants`
--

CREATE TABLE `training_participants` (
  `id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('Enrolled','Completed','Cancelled') NOT NULL DEFAULT 'Enrolled',
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `training_participants`
--

INSERT INTO `training_participants` (`id`, `training_id`, `user_id`, `status`, `enrolled_at`) VALUES
(1, 1, 9, 'Enrolled', '2026-03-30 07:22:31'),
(2, 3, 10, 'Enrolled', '2026-03-30 14:36:24');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_no` varchar(30) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `username` varchar(60) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','training_officer','employee','trainee') NOT NULL,
  `department` varchar(120) NOT NULL,
  `position_title` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `password_updated_at` datetime DEFAULT NULL,
  `last_password_change` datetime DEFAULT NULL,
  `employee_dashboard_enabled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_no`, `full_name`, `username`, `email`, `contact_no`, `password_hash`, `role`, `department`, `position_title`, `is_active`, `created_at`, `must_change_password`, `password_updated_at`, `last_password_change`, `employee_dashboard_enabled`) VALUES
(7, 'MGR-0001', 'Training Manager', 'admin', 'a.ekariie@gmail.com', NULL, '$2y$10$gNiynZ5hu3xCq8O/2u9CduEXUGsM34vrvLWsYtyYHPtAijpERoHTG', 'admin', 'Training Department', 'Training Manager', 1, '2026-03-27 14:07:23', 0, NULL, '2026-04-02 12:42:19', 0),
(8, 'TO-0001', 'Training Officer One', 'trainer1', 'arr@gmail.com', NULL, '$2y$10$PlqxbpmQ8el1tSkosvBYHunM9fFUQg.MVyBMJaYNoyvxr3lc8vuaK', 'training_officer', 'Training Department', 'Training Officer', 1, '2026-03-27 14:07:23', 0, '2026-04-02 13:01:13', '2026-04-02 12:42:19', 0),
(9, 'TRN-0001', 'Trainee One', 'trainee1', 'arrianeaek@gmail.com', '09957890919', '$2y$10$MFZeoD1Tgvdv4QJ5cTQRLO0BbhYTZ7hUt8kpeFsuoIxN2kybGQeau', 'trainee', 'Training Department', 'Trainee', 1, '2026-03-27 14:07:23', 0, '2026-04-02 14:24:48', '2026-04-02 12:42:19', 0),
(10, 'TRN-0002', 'Layugan', 'trainee2', 'aek@gmail.com', '099999999', '$2y$10$dKzINsf2bz04X17HDVNXrei1t/LedlwER5xM3l9ZNg63FXEdwWyQ6', 'trainee', 'Training', 'Trainee', 1, '2026-03-30 14:36:05', 0, '2026-03-30 23:01:10', '2026-04-02 12:42:19', 0),
(13, 'TRN-0003', 'Andre Ino', 'trainee3', 'andreinocencio12@gmail.com\n', '09999999999', '$2y$10$g4uMDDs4gmQeLDlVUKZpzOBWT83BVOcgPsUxwmMAlx01gOFnxKIvu', 'trainee', 'Training', 'Trainee', 1, '2026-04-02 05:05:56', 0, '2026-04-02 13:07:16', NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_daily_attendance` (`training_id`,`user_id`,`log_date`),
  ADD KEY `fk_att_user` (`user_id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_certificate_training_user` (`training_id`,`user_id`),
  ADD UNIQUE KEY `uniq_certificate_no` (`certificate_no`),
  ADD KEY `idx_cert_training` (`training_id`),
  ADD KEY `idx_cert_user` (`user_id`),
  ADD KEY `fk_cert_issued_by` (`issued_by`);

--
-- Indexes for table `evaluation_records`
--
ALTER TABLE `evaluation_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_training_user_eval` (`training_id`,`user_id`),
  ADD KEY `idx_eval_training` (`training_id`),
  ADD KEY `idx_eval_user` (`user_id`),
  ADD KEY `idx_eval_by` (`evaluated_by`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_training` (`training_id`),
  ADD KEY `idx_notif_sender` (`sender_user_id`),
  ADD KEY `idx_notif_recipient` (`recipient_user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_password_resets_user` (`user_id`);

--
-- Indexes for table `trainee_activity_logs`
--
ALTER TABLE `trainee_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trainee_user_id` (`trainee_user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_activity_officer` (`seen_by_officer_id`);

--
-- Indexes for table `trainings`
--
ALTER TABLE `trainings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trainings_trainer` (`trainer_user_id`),
  ADD KEY `fk_trainings_creator` (`created_by`);

--
-- Indexes for table `training_participants`
--
ALTER TABLE `training_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_training_user` (`training_id`,`user_id`),
  ADD KEY `fk_tp_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_no` (`employee_no`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `evaluation_records`
--
ALTER TABLE `evaluation_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `trainee_activity_logs`
--
ALTER TABLE `trainee_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `trainings`
--
ALTER TABLE `trainings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `training_participants`
--
ALTER TABLE `training_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `fk_att_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_att_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `fk_cert_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cert_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cert_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluation_records`
--
ALTER TABLE `evaluation_records`
  ADD CONSTRAINT `fk_eval_by_user` FOREIGN KEY (`evaluated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eval_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eval_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD CONSTRAINT `fk_notif_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notif_sender` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notif_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trainee_activity_logs`
--
ALTER TABLE `trainee_activity_logs`
  ADD CONSTRAINT `fk_activity_officer` FOREIGN KEY (`seen_by_officer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_activity_trainee` FOREIGN KEY (`trainee_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trainings`
--
ALTER TABLE `trainings`
  ADD CONSTRAINT `fk_trainings_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_trainings_trainer` FOREIGN KEY (`trainer_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `training_participants`
--
ALTER TABLE `training_participants`
  ADD CONSTRAINT `fk_tp_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
