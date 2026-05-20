-- phpMyAdmin SQL Dump
-- version 5.2.2deb1+noble1
-- https://www.phpmyadmin.net/
--
-- Hostiteľ: localhost:3306
-- Čas generovania: St 20.Máj 2026, 14:17
-- Verzia serveru: 10.11.14-MariaDB-0ubuntu0.24.04.1
-- Verzia PHP: 8.4.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáza: `tomtib_lab_app`
--

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `animation_usages`
--

CREATE TABLE `animation_usages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `animation_type` varchar(50) NOT NULL,
  `visitor_token` varchar(100) NOT NULL,
  `ip_hash` varchar(64) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `cas_command_histories`
--

CREATE TABLE `cas_command_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `session_token` varchar(100) NOT NULL,
  `sequence` int(10) UNSIGNED NOT NULL,
  `command` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `cas_logs`
--

CREATE TABLE `cas_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `source` varchar(50) NOT NULL,
  `command` text NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `output` longtext DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Štruktúra tabuľky pre tabuľku `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Kľúče pre exportované tabuľky
--

--
-- Indexy pre tabuľku `animation_usages`
--
ALTER TABLE `animation_usages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `animation_usages_animation_type_visitor_token_created_at_index` (`animation_type`,`visitor_token`,`created_at`);

--
-- Indexy pre tabuľku `cas_command_histories`
--
ALTER TABLE `cas_command_histories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cas_command_histories_session_token_sequence_unique` (`session_token`,`sequence`),
  ADD KEY `cas_command_histories_session_token_index` (`session_token`);

--
-- Indexy pre tabuľku `cas_logs`
--
ALTER TABLE `cas_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pre tabuľku `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pre exportované tabuľky
--

--
-- AUTO_INCREMENT pre tabuľku `animation_usages`
--
ALTER TABLE `animation_usages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pre tabuľku `cas_command_histories`
--
ALTER TABLE `cas_command_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pre tabuľku `cas_logs`
--
ALTER TABLE `cas_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pre tabuľku `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
