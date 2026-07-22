-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 22, 2026 at 04:56 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ptk_homestay`
--

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `personnel_id` int NOT NULL,
  `year` int NOT NULL,
  `month` tinyint NOT NULL,
  `paid` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `personnel_id`, `year`, `month`, `paid`) VALUES
(1, 1, 2026, 0, 1),
(2, 1, 2026, 1, 1),
(3, 1, 2026, 2, 1),
(4, 1, 2026, 3, 0),
(5, 1, 2026, 4, 1),
(6, 1, 2026, 11, 1),
(7, 1, 2026, 10, 1),
(8, 1, 2026, 9, 1),
(9, 1, 2026, 8, 1),
(10, 1, 2026, 7, 1),
(11, 1, 2026, 6, 1),
(12, 1, 2026, 5, 0),
(13, 2, 2026, 1, 1),
(14, 2, 2026, 5, 0),
(15, 2, 2026, 8, 1),
(16, 2, 2026, 7, 1),
(17, 2, 2026, 4, 1),
(18, 2, 2026, 9, 0),
(19, 2, 2026, 11, 1),
(20, 2, 2026, 10, 1),
(21, 3, 2026, 10, 1),
(22, 3, 2026, 0, 1),
(23, 3, 2026, 1, 1),
(24, 3, 2026, 2, 1),
(25, 3, 2026, 3, 1),
(26, 3, 2026, 5, 1),
(27, 4, 2026, 7, 1),
(28, 4, 2026, 4, 1),
(29, 3, 2026, 4, 1),
(30, 2, 2026, 3, 1),
(31, 4, 2026, 8, 1),
(32, 4, 2026, 0, 1),
(33, 4, 2026, 1, 1),
(34, 4, 2026, 2, 1),
(35, 4, 2026, 3, 1),
(36, 4, 2026, 5, 1),
(37, 4, 2026, 6, 1),
(38, 4, 2026, 11, 1),
(39, 4, 2026, 10, 1),
(40, 4, 2026, 9, 1);

-- --------------------------------------------------------

--
-- Table structure for table `personnel`
--

CREATE TABLE `personnel` (
  `id` int NOT NULL,
  `rank_name` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personnel`
--

INSERT INTO `personnel` (`id`, `rank_name`, `name`) VALUES
(1, 'SJN', 'AMBOK'),
(2, 'LANS KPL', 'ICAD'),
(3, 'DSP', 'ICAD'),
(4, 'INSP', 'HAZMI');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','user') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `personnel_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `personnel_id`, `created_at`) VALUES
(1, 'admin', '$2y$10$4Y6UGxfsyMvlD5g4lUC2OuMNRbpzsaw.vgakcM5seh93ClMwhZk42', 'admin', NULL, '2026-07-17 02:01:48'),
(3, 'Ambok Irfan', '$2y$10$vAy/oDfckBf0GegsxoGAm.Il6jq4bLFDx6WdHxXJuF60LYV5ICuZG', 'user', 1, '2026-07-17 02:23:25'),
(4, 'Irsyad Hakimi', '$2y$10$KDdCew407VxjBMEwZ.NJmeCc9EXU85e4h2Cbes5IQdSVt9wbaD82.', 'user', 2, '2026-07-17 02:27:17'),
(5, 'HAZMIZAIM', '$2y$10$dEK.1NFH38L0RgrK.QaYOeQSTdQcj/uCYly7OgqF5zeLVANkFMaYG', 'user', 4, '2026-07-17 03:04:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_payment` (`personnel_id`,`year`,`month`);

--
-- Indexes for table `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `personnel`
--
ALTER TABLE `personnel`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`personnel_id`) REFERENCES `personnel` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
