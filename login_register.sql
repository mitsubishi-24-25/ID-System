-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 11, 2026 at 04:04 AM
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
-- Database: `login_register`
--

-- --------------------------------------------------------

--
-- Table structure for table `deleted_students`
--

CREATE TABLE `deleted_students` (
  `id` int(11) NOT NULL,
  `advisor_id` int(11) NOT NULL DEFAULT 0,
  `student_id` int(11) NOT NULL,
  `lrn` varchar(50) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_initial` varchar(10) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `parent_guardian` varchar(255) NOT NULL,
  `permanent_address` text NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `id_templates`
--

CREATE TABLE `id_templates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `template_type` enum('front','back') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `id_templates`
--

INSERT INTO `id_templates` (`id`, `user_id`, `template_type`, `file_path`, `original_filename`, `file_size`, `uploaded_at`, `updated_at`) VALUES
(1, 65, 'front', 'assets/uploads/id_templates/front_id_65_1770247830.jpg', 'FrontID.jpg', 69352, '2026-02-04 23:30:30', '2026-02-04 23:30:30'),
(2, 65, 'back', 'assets/uploads/id_templates/back_id_65_1770247830.jpg', 'Untitled design (12).jpg', 54785, '2026-02-04 23:30:30', '2026-02-04 23:30:30');

-- --------------------------------------------------------

--
-- Table structure for table `id_template_positions`
--

CREATE TABLE `id_template_positions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `template_type` enum('front','back') NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `x_position` decimal(10,2) NOT NULL DEFAULT 0.00,
  `y_position` decimal(10,2) NOT NULL DEFAULT 0.00,
  `font_family` varchar(50) DEFAULT 'Arial',
  `font_size` int(11) DEFAULT 12,
  `font_weight` enum('normal','bold','lighter') DEFAULT 'normal',
  `text_color` varchar(7) DEFAULT '#000000',
  `text_align` enum('left','center','right') DEFAULT 'left',
  `max_width` int(11) DEFAULT NULL,
  `rotation` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `id_template_positions`
--

INSERT INTO `id_template_positions` (`id`, `user_id`, `template_type`, `field_name`, `x_position`, `y_position`, `font_family`, `font_size`, `font_weight`, `text_color`, `text_align`, `max_width`, `rotation`, `created_at`, `updated_at`) VALUES
(1, 65, 'front', 'studentName', 420.00, 566.00, 'Inter', 12, 'normal', '#212529', 'left', NULL, 0.00, '2026-02-04 23:35:07', '2026-02-04 23:38:38'),
(2, 65, 'front', 'lrn', 389.00, 703.00, 'Inter', 12, 'normal', '#212529', 'left', NULL, 0.00, '2026-02-04 23:35:07', '2026-02-04 23:38:38'),
(3, 65, 'front', 'grade', 463.00, 643.00, 'Inter', 12, 'normal', '#212529', 'left', NULL, 0.00, '2026-02-04 23:35:07', '2026-02-04 23:38:38'),
(4, 65, 'front', 'section', 50.00, 140.00, 'Inter', 12, 'normal', '#212529', 'left', NULL, 0.00, '2026-02-04 23:35:07', '2026-02-04 23:38:38'),
(5, 65, 'front', 'birthdate', 50.00, 170.00, 'Inter', 12, 'normal', '#212529', 'left', NULL, 0.00, '2026-02-04 23:35:07', '2026-02-04 23:38:38'),
(6, 65, 'front', 'address', 50.00, 200.00, 'Inter', 12, 'normal', '#212529', 'left', NULL, 0.00, '2026-02-04 23:35:07', '2026-02-04 23:38:38'),
(7, 65, 'front', 'parentGuardian', 50.00, 230.00, 'Inter', 12, 'normal', '#212529', 'left', NULL, 0.00, '2026-02-04 23:35:07', '2026-02-04 23:38:38'),
(8, 65, 'front', 'contactNumber', 50.00, 260.00, 'Inter', 12, 'normal', '#212529', 'left', NULL, 0.00, '2026-02-04 23:35:07', '2026-02-04 23:38:38'),
(9, 65, 'front', 'advisorName', 50.00, 290.00, 'Inter', 12, 'normal', '#212529', 'left', NULL, 0.00, '2026-02-04 23:35:07', '2026-02-04 23:38:38'),
(10, 65, 'front', 'principalName', 50.00, 320.00, 'Inter', 12, 'normal', '#212529', 'left', NULL, 0.00, '2026-02-04 23:35:07', '2026-02-04 23:38:38');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `advisor_id` int(11) NOT NULL DEFAULT 1,
  `grade` varchar(2) DEFAULT NULL,
  `section` varchar(20) DEFAULT NULL,
  `transportation_type` varchar(50) DEFAULT NULL,
  `travel_minutes` int(11) DEFAULT NULL,
  `lrn` varchar(50) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_initial` varchar(10) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `birthdate` date NOT NULL,
  `parent_guardian` varchar(150) NOT NULL,
  `permanent_address` text NOT NULL,
  `contact_number` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(7) NOT NULL,
  `full_name` varchar(128) NOT NULL,
  `password` varchar(255) NOT NULL,
  `section` varchar(50) NOT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `principal_name` varchar(255) DEFAULT NULL,
  `principal_signature_path` varchar(255) DEFAULT NULL,
  `school_name` varchar(255) DEFAULT 'School Name',
  `school_address` varchar(255) DEFAULT 'School Address',
  `position` varchar(100) DEFAULT 'Advisor',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `full_name`, `password`, `section`, `signature_path`, `principal_name`, `principal_signature_path`, `school_name`, `school_address`, `position`, `is_active`) VALUES
(65, '5383167', 'Christopher Rey C. San Jose', '$2y$10$O8T4Ekw.XAnHLOw7hBQFku0Fkq8VKhg61Bw0LKdk0FLgNsnss9H4.', 'ICT 12 - A', NULL, 'CEANCENO M. ESPIRITU, JR., PhD', 'uploads/principal_signature_65_1768182308.jpg', 'School Name', 'School Address', 'Advisor', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `deleted_students`
--
ALTER TABLE `deleted_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `id_templates`
--
ALTER TABLE `id_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_template` (`user_id`,`template_type`);

--
-- Indexes for table `id_template_positions`
--
ALTER TABLE `id_template_positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_template_field` (`user_id`,`template_type`,`field_name`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_students_grade` (`grade`),
  ADD KEY `idx_students_section` (`section`),
  ADD KEY `idx_students_transportation` (`transportation_type`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `deleted_students`
--
ALTER TABLE `deleted_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=975;

--
-- AUTO_INCREMENT for table `id_templates`
--
ALTER TABLE `id_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `id_template_positions`
--
ALTER TABLE `id_template_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1178;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `deleted_students`
--
ALTER TABLE `deleted_students`
  ADD CONSTRAINT `deleted_students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `id_template_positions`
--
ALTER TABLE `id_template_positions`
  ADD CONSTRAINT `id_template_positions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
