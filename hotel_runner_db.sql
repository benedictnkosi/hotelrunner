-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2023 at 08:14 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `new_aluve_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `add_ons`
--

CREATE TABLE `add_ons` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,0) NOT NULL,
  `property` int(11) NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'live',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `minimum` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_rooms`
--

CREATE TABLE `blocked_rooms` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `linked_resa_id` int(11) DEFAULT NULL,
  `comment` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uid` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_date` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blocked_rooms`
--

-- --------------------------------------------------------

--
-- Table structure for table `cleaning`
--

CREATE TABLE `cleaning` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `cleaner` int(11) DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cleaning`
--

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `id` int(11) NOT NULL,
  `name` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_uids`
--

CREATE TABLE `failed_uids` (
  `id` int(11) NOT NULL,
  `uid` varchar(100) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `flipability_property`
--

CREATE TABLE `flipability_property` (
  `bedrooms` int(11) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `location` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `bathrooms` int(11) DEFAULT NULL,
  `garage` int(11) DEFAULT NULL,
  `erf` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `state` varchar(11) NOT NULL DEFAULT 'new',
  `type` varchar(11) NOT NULL DEFAULT 'house',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------

--
-- Table structure for table `flipability_property_old`
--

CREATE TABLE `flipability_property_old` (
  `bedrooms` int(11) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `location` text DEFAULT NULL,
  `bathrooms` double DEFAULT NULL,
  `garage` double DEFAULT NULL,
  `erf` bigint(20) DEFAULT NULL,
  `type` text DEFAULT NULL,
  `id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `state` varchar(20) NOT NULL DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `guest`
--

CREATE TABLE `guest` (
  `id` int(11) NOT NULL,
  `name` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_image` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT 'Not Verified',
  `phone_number` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `comments` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property` int(11) NOT NULL,
  `rewards` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `ical`
--

CREATE TABLE `ical` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `link` varchar(500) NOT NULL,
  `room` int(11) NOT NULL,
  `logs` varchar(5000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------

--
-- Table structure for table `message_template`
--

CREATE TABLE `message_template` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `property` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `message_template`
--

INSERT INTO `message_template` (`id`, `name`, `message`, `property`) VALUES
(3, 'Load shedding', 'Hi guest_name, ....', 3);

-- --------------------------------------------------------

--
-- Table structure for table `message_variables`
--

CREATE TABLE `message_variables` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `message_variables`
--

INSERT INTO `message_variables` (`id`, `name`) VALUES
(1, 'guest_name'),
(2, 'check_in'),
(3, 'check_out'),
(4, 'room_name');

-- --------------------------------------------------------

--
-- Table structure for table `messenger_messages`
--

CREATE TABLE `messenger_messages` (
  `id` bigint(20) NOT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `message` varchar(500) NOT NULL,
  `actioned` tinyint(1) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `name` varchar(50) NOT NULL,
  `link` varchar(500) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `amount` double NOT NULL,
  `channel` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount` tinyint(1) NOT NULL DEFAULT 0,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property`
--

CREATE TABLE `property` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `secret` varchar(10) NOT NULL,
  `email_address` varchar(100) NOT NULL,
  `admin_email` varchar(100) NOT NULL,
  `server_name` varchar(100) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `bank_account_type` varchar(100) NOT NULL,
  `bank_account_number` varchar(100) NOT NULL,
  `bank_branch_code` varchar(100) NOT NULL,
  `uid` varchar(100) DEFAULT NULL,
  `terms` text DEFAULT '',
  `google_review_link` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `property`
--

INSERT INTO `property` (`id`, `name`, `address`, `phone_number`, `secret`, `email_address`, `admin_email`, `server_name`, `bank_name`, `bank_account_type`, `bank_account_number`, `bank_branch_code`, `uid`, `terms`, `google_review_link`) VALUES
(3, 'Aluve Guesthouse', '187 kitchener Ave, Kensington, JHB, 2194', '+27 79 634 7610', '3782', 'info@aluvegh.co.za', 'nkosi.benedict@gmail.com', 'aluvegh.co.za', 'FNB', 'Cheque', '\r\n63030034168', '250 655', '34bb4574-0742-11ed-b939-0242ac120002', '<p>No Noise at all times<###p>  <p>No loud music<###p>  <p>No parties<###p>  <p>No smoking inside the house, R500 Fine<###p>  <p>No kids under the age of 12<###p>  <p>Check-in cut-off is at 22:00. Please make arrangements for a later check-in<###p>  <p>The guest can cancel free of charge until 3 days before arrival. The guest will be charged the total price of the reservation if they cancel in the 7 days before arrival. If the guest doesn’t show up they will be charged the total price of the reservation<###p>', 'https://g.page/r/CVWFT5sx0AcPEAg/review');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `original_room_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `additional_info` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `received_on` datetime DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `uid` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_url` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `check_in_status` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT 'not checked in',
  `cleanliness_score` int(11) DEFAULT NULL,
  `checked_in_time` datetime DEFAULT NULL,
  `check_in_time` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '14:00',
  `check_out_time` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '10:00',
  `adults` int(11) DEFAULT NULL,
  `children` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `reservation_add_ons`
--

CREATE TABLE `reservation_add_ons` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `add_on_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_notes`
--

CREATE TABLE `reservation_notes` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_status`
--

CREATE TABLE `reservation_status` (
  `id` int(11) NOT NULL,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservation_status`
--

INSERT INTO `reservation_status` (`id`, `name`) VALUES
(1, 'confirmed'),
(2, 'pending'),
(3, 'cancelled'),
(4, 'opened');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `status` int(11) DEFAULT NULL,
  `bed` int(11) DEFAULT NULL,
  `name` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(10,0) DEFAULT NULL,
  `sleeps` int(11) DEFAULT NULL,
  `linked_room` int(11) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `stairs` tinyint(1) DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `property` int(11) NOT NULL,
  `tv` int(1) NOT NULL,
  `airbnb_last_export` timestamp NOT NULL DEFAULT current_timestamp(),
  `bdc_last_export` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_beds`
--

CREATE TABLE `room_beds` (
  `id` int(11) NOT NULL,
  `room` int(11) NOT NULL,
  `bed` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------

--
-- Table structure for table `room_bed_size`
--

CREATE TABLE `room_bed_size` (
  `id` int(11) NOT NULL,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_bed_size`
--

INSERT INTO `room_bed_size` (`id`, `name`) VALUES
(1, 'King'),
(2, 'Queen'),
(3, 'Double'),
(4, 'Single'),
(5, 'Sleeper Couch');

-- --------------------------------------------------------

--
-- Table structure for table `room_images`
--

CREATE TABLE `room_images` (
  `id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `room_status`
--

CREATE TABLE `room_status` (
  `id` int(11) NOT NULL,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_status`
--

INSERT INTO `room_status` (`id`, `name`) VALUES
(1, 'live'),
(2, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `room_tv`
--

CREATE TABLE `room_tv` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `room_tv`
--

INSERT INTO `room_tv` (`id`, `name`) VALUES
(1, 'Smart TV'),
(2, 'DSTV'),
(3, 'No TV'),
(4, 'OVHD'),
(5, 'Smart TV & DSTV'),
(6, 'Smart TV & OVHD');

-- --------------------------------------------------------

--
-- Table structure for table `schedule_messages`
--

CREATE TABLE `schedule_messages` (
  `id` int(11) NOT NULL,
  `message_template` int(11) DEFAULT NULL,
  `message_schedule` int(11) DEFAULT NULL,
  `room` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `schedule_times`
--

CREATE TABLE `schedule_times` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `days` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedule_times`
--

INSERT INTO `schedule_times` (`id`, `name`, `days`) VALUES
(1, 'Day of check-in', 0),
(2, 'Day before check-in', 1),
(3, 'Week before check-in', 7),
(4, 'Day after check-out', -1),
(5, 'Seven days after check-out', -7);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:json)',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `property` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voucher`
--

CREATE TABLE `voucher` (
  `id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `creation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `phone_number` varchar(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `add_ons`
--
ALTER TABLE `add_ons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `add_ons_property` (`property`);

--
-- Indexes for table `blocked_rooms`
--
ALTER TABLE `blocked_rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `cleaning`
--
ALTER TABLE `cleaning`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cleaning_reservation` (`reservation_id`),
  ADD KEY `cleaning_cleaner` (`cleaner`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_employee_property` (`property`);

--
-- Indexes for table `failed_uids`
--
ALTER TABLE `failed_uids`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `flipability_property`
--
ALTER TABLE `flipability_property`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `flipability_property_old`
--
ALTER TABLE `flipability_property_old`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `guest`
--
ALTER TABLE `guest`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_guest_property` (`property`);

--
-- Indexes for table `ical`
--
ALTER TABLE `ical`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ical_room` (`room`);

--
-- Indexes for table `message_template`
--
ALTER TABLE `message_template`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_message_template_property` (`property`);

--
-- Indexes for table `message_variables`
--
ALTER TABLE `message_variables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  ADD KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  ADD KEY `IDX_75EA56E016BA31DB` (`delivered_at`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_reservation` (`reservation_id`);

--
-- Indexes for table `property`
--
ALTER TABLE `property`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `reservations_ibfk_3` (`status`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `FK_original_roomId_room` (`original_room_id`);

--
-- Indexes for table `reservation_add_ons`
--
ALTER TABLE `reservation_add_ons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_add_on_add_on` (`add_on_id`),
  ADD KEY `reservation_add_on_reservation` (`reservation_id`);

--
-- Indexes for table `reservation_notes`
--
ALTER TABLE `reservation_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_notes` (`reservation_id`);

--
-- Indexes for table `reservation_status`
--
ALTER TABLE `reservation_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rooms_ibfk_2` (`status`),
  ADD KEY `rooms_ibfk_1` (`bed`),
  ADD KEY `fk_room_property` (`property`),
  ADD KEY `fk_room_tv` (`tv`);

--
-- Indexes for table `room_beds`
--
ALTER TABLE `room_beds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_bed_room_bed_size_fk` (`bed`),
  ADD KEY `room_bed_room_fk` (`room`);

--
-- Indexes for table `room_bed_size`
--
ALTER TABLE `room_bed_size`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_images`
--
ALTER TABLE `room_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_id_room` (`room_id`);

--
-- Indexes for table `room_status`
--
ALTER TABLE `room_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_tv`
--
ALTER TABLE `room_tv`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedule_messages`
--
ALTER TABLE `schedule_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `schedule_message_times` (`message_schedule`),
  ADD KEY `schedule_message_template` (`message_template`),
  ADD KEY `FK_schedule_messages_room` (`room`);

--
-- Indexes for table `schedule_times`
--
ALTER TABLE `schedule_times`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`),
  ADD KEY `user_property_fk` (`property`);

--
-- Indexes for table `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `add_ons`
--
ALTER TABLE `add_ons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `blocked_rooms`
--
ALTER TABLE `blocked_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=372;

--
-- AUTO_INCREMENT for table `cleaning`
--
ALTER TABLE `cleaning`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=240;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `failed_uids`
--
ALTER TABLE `failed_uids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `flipability_property`
--
ALTER TABLE `flipability_property`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12111;

--
-- AUTO_INCREMENT for table `flipability_property_old`
--
ALTER TABLE `flipability_property_old`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3950;

--
-- AUTO_INCREMENT for table `guest`
--
ALTER TABLE `guest`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6065;

--
-- AUTO_INCREMENT for table `ical`
--
ALTER TABLE `ical`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `message_template`
--
ALTER TABLE `message_template`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `message_variables`
--
ALTER TABLE `message_variables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `messenger_messages`
--
ALTER TABLE `messenger_messages`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=381;

--
-- AUTO_INCREMENT for table `property`
--
ALTER TABLE `property`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=731;

--
-- AUTO_INCREMENT for table `reservation_add_ons`
--
ALTER TABLE `reservation_add_ons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `reservation_notes`
--
ALTER TABLE `reservation_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=289;

--
-- AUTO_INCREMENT for table `reservation_status`
--
ALTER TABLE `reservation_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `room_beds`
--
ALTER TABLE `room_beds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `room_bed_size`
--
ALTER TABLE `room_bed_size`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `room_images`
--
ALTER TABLE `room_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=287;

--
-- AUTO_INCREMENT for table `room_status`
--
ALTER TABLE `room_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `room_tv`
--
ALTER TABLE `room_tv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `schedule_messages`
--
ALTER TABLE `schedule_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `schedule_times`
--
ALTER TABLE `schedule_times`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `voucher`
--
ALTER TABLE `voucher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `add_ons`
--
ALTER TABLE `add_ons`
  ADD CONSTRAINT `add_ons_property` FOREIGN KEY (`property`) REFERENCES `property` (`id`);

--
-- Constraints for table `blocked_rooms`
--
ALTER TABLE `blocked_rooms`
  ADD CONSTRAINT `FK_CBE05B6A54177093` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `cleaning`
--
ALTER TABLE `cleaning`
  ADD CONSTRAINT `FK_3F6C5CF96E8447A4` FOREIGN KEY (`cleaner`) REFERENCES `employee` (`id`),
  ADD CONSTRAINT `FK_3F6C5CF9B83297E7` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`);

--
-- Constraints for table `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `fk_employee_property` FOREIGN KEY (`property`) REFERENCES `property` (`id`);

--
-- Constraints for table `guest`
--
ALTER TABLE `guest`
  ADD CONSTRAINT `fk_guest_property` FOREIGN KEY (`property`) REFERENCES `property` (`id`);

--
-- Constraints for table `ical`
--
ALTER TABLE `ical`
  ADD CONSTRAINT `ical_room` FOREIGN KEY (`room`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `message_template`
--
ALTER TABLE `message_template`
  ADD CONSTRAINT `fk_message_template_property` FOREIGN KEY (`property`) REFERENCES `property` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `FK_65D29B32B83297E7` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `FK_4DA23954177093` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `FK_4DA2397B00651C` FOREIGN KEY (`status`) REFERENCES `reservation_status` (`id`),
  ADD CONSTRAINT `FK_4DA2399A4AA658` FOREIGN KEY (`guest_id`) REFERENCES `guest` (`id`),
  ADD CONSTRAINT `FK_original_roomId_room` FOREIGN KEY (`original_room_id`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `reservation_add_ons`
--
ALTER TABLE `reservation_add_ons`
  ADD CONSTRAINT `FK_CA784A2D220A8152` FOREIGN KEY (`add_on_id`) REFERENCES `add_ons` (`id`),
  ADD CONSTRAINT `FK_CA784A2DB83297E7` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`);

--
-- Constraints for table `reservation_notes`
--
ALTER TABLE `reservation_notes`
  ADD CONSTRAINT `FK_4264762BB83297E7` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `FK_7CA11A967B00651C` FOREIGN KEY (`status`) REFERENCES `room_status` (`id`),
  ADD CONSTRAINT `FK_7CA11A96E647FCFF` FOREIGN KEY (`bed`) REFERENCES `room_bed_size` (`id`),
  ADD CONSTRAINT `fk_room_property` FOREIGN KEY (`property`) REFERENCES `property` (`id`),
  ADD CONSTRAINT `fk_room_tv` FOREIGN KEY (`tv`) REFERENCES `room_tv` (`id`);

--
-- Constraints for table `room_images`
--
ALTER TABLE `room_images`
  ADD CONSTRAINT `FK_A15178AB54177093` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `schedule_messages`
--
ALTER TABLE `schedule_messages`
  ADD CONSTRAINT `FK_67E9F7A0531ED5EA` FOREIGN KEY (`message_schedule`) REFERENCES `schedule_times` (`id`),
  ADD CONSTRAINT `FK_67E9F7A09E46DB92` FOREIGN KEY (`message_template`) REFERENCES `message_template` (`id`),
  ADD CONSTRAINT `FK_schedule_messages_room` FOREIGN KEY (`room`) REFERENCES `rooms` (`id`);

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_property_fk` FOREIGN KEY (`property`) REFERENCES `property` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
