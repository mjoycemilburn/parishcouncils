-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 12, 2021 at 04:29 PM
-- Server version: 5.7.34
-- PHP Version: 7.3.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `qfgavcxt_parishcouncilsdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `entries`
--

CREATE TABLE `entries` (
  `section_id` varchar(20) NOT NULL,
  `entry_date` date NOT NULL,
  `entry_suffix` varchar(45) DEFAULT NULL,
  `entry_title` varchar(60) NOT NULL,
  `council_id` int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `entries`
--

INSERT INTO `entries` (`section_id`, `entry_date`, `entry_suffix`, `entry_title`, `council_id`) VALUES
('about', '1970-01-01', '', 'About Newbiggin Parish Council', 0),
('minutes', '2019-09-03', '', '', 0),
('policies', '1970-01-01', '', 'Accessibility Statement', 0),
('policies', '1970-01-01', '', 'Data Protection Policy', 0),
('policies', '1970-01-01', '', 'Privacy Policy', 0);

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `entries` (
  `section_id` varchar(20) NOT NULL,
  `entry_date` date NOT NULL,
  `entry_suffix` varchar(45) DEFAULT NULL,
  `entry_title` varchar(60) NOT NULL,
  `council_id` int(4) NOT NULL,
  `storage_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `section_header`, `section_type`, `section_prefix`, `section_sequence_number`, `council_id`) VALUES
('about', 'About', 'standard_title', '', 0, 0),
('minutes', 'Minutes', 'date_title', 'Minutes for : ', 2, 0),
('policies', 'Policies', 'standard_title', '', 3, 0);

-- --------------------------------------------------------

--
-- Table structure for table `slides`
--

CREATE TABLE `slides` (
  `slide_title` varchar(60) NOT NULL,
  `slide_file_extension` varchar(6) DEFAULT NULL,
  `slide_sequence_number` int(4) DEFAULT NULL,
  `council_id` int(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `slides`
--

INSERT INTO `slides` (`slide_title`, `slide_file_extension`, `slide_sequence_number`, `council_id`) VALUES
('Newbiggin Village Church', 'jpg', 2, 0),
('Newbiggin Village from the ai', 'jpg', 3, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(40) NOT NULL,
  `password` varchar(20) DEFAULT NULL,
  `council_id` int(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `password`, `council_id`) VALUES
('test', 'tst$', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `entries`
--
ALTER TABLE `entries`
  ADD PRIMARY KEY (`storage_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`section_id`,`council_id`);

--
-- Indexes for table `slides`
--
ALTER TABLE `slides`
  ADD PRIMARY KEY (`slide_title`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`,`council_id`) USING BTREE;
COMMIT;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `entries`
--
ALTER TABLE `entries`
  MODIFY `storage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
