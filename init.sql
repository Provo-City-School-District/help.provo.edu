
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `employee` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `alert_level` enum('crit','warn') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




-- --------------------------------------------------------

--
-- Table structure for table `exclude_days`
--

CREATE TABLE `exclude_days` (
  `id` int(11) NOT NULL,
  `exclude_day` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exclude_days`
--


-- --------------------------------------------------------

--
-- Table structure for table `flagged_tickets`
--

CREATE TABLE `flagged_tickets` (
  `user_id` int(11) NOT NULL,
  `ticket_id` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `location_id` int(15) NOT NULL,
  `sitenumber` int(11) NOT NULL,
  `location_name` varchar(255) DEFAULT NULL,
  `archived_location_id` int(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`location_id`, `sitenumber`, `location_name`, `archived_location_id`) VALUES
(22, 38, 'District Office', 5),
(23, 100, 'Amelia Earhart', 1),
(24, 101, 'Canyon Crest', 2),
(25, 102, 'Edgemont', 7),
(26, 103, 'Provo Peaks', 16),
(27, 104, 'Franklin', 8),
(28, 108, 'Grandview', 31),
(29, 118, 'Lakeview', 11),
(30, 120, 'Provost', 17),
(31, 122, 'Rock Canyon', 18),
(32, 123, 'Spring Creek', 20),
(33, 124, 'Sunset View', 22),
(34, 128, 'Timpanogos', 23),
(35, 132, 'Wasatch', 26),
(36, 134, 'Westridge', 27),
(37, 404, 'Centennial Middle', 3),
(38, 408, 'Dixon', 6),
(39, 555, 'Slate Canyon', 19),
(40, 560, 'Oak Springs', 12),
(41, 590, 'VAN', 36),
(42, 610, 'Central Utah Enterprises', 4),
(43, 610, 'EBPH', 33),
(44, 641, 'Preschool', 13),
(45, 704, 'Provo High', 15),
(46, 712, 'Timpview High', 24),
(47, 730, 'Independence High', 10),
(48, 740, 'Provo Adult Education', 14),
(49, 818, 'CLC', 21),
(50, 1200, 'Hillside', 9),
(51, 1600, 'Transportation', 25),
(52, 1700, 'Maintenance', 30),
(53, 1896, 'Aux Services', 34);

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `note_id` int(20) NOT NULL,
  `linked_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `creator` varchar(255) NOT NULL,
  `note` longtext DEFAULT NULL,
  `time` int(11) DEFAULT NULL,
  `idx` int(11) DEFAULT NULL,
  `visible_to_client` int(11) NOT NULL,
  `date_override` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_type`
--

CREATE TABLE `request_type` (
  `request_id` int(25) NOT NULL,
  `request_name` varchar(255) NOT NULL,
  `archived_request_ID` int(25) DEFAULT NULL,
  `archived_parent` int(5) DEFAULT NULL,
  `request_parent` int(25) DEFAULT NULL,
  `is_archived` int(5) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_type`
--

INSERT INTO `request_type` (`request_id`, `request_name`, `archived_request_ID`, `archived_parent`, `request_parent`, `is_archived`) VALUES
(39, 'Anti-Virus (Sophos)', 1, 171, 209, 0),
(40, 'E-mail', 2, 171, 209, 0),
(41, 'Employee Resource Portal', 3, NULL, NULL, 1),
(42, 'Destiny Library System', 4, 171, 209, 0),
(43, 'Goalview', 5, 171, 209, 1),
(44, 'Hardware', 6, NULL, NULL, 1),
(45, 'IFAS (SunGuard)', 7, NULL, NULL, 1),
(46, 'Intercom/Bell Schedule', 8, 171, 209, 0),
(47, 'Internet Filtering', 9, 171, 209, 0),
(48, 'Nutrikids', 10, 171, 209, 0),
(49, 'Network/Internet', 11, 171, 209, 0),
(50, 'Phone', 12, 171, 209, 0),
(51, 'PowerSchool', 13, 171, 209, 0),
(52, 'I am an Admin', 14, 13, 51, 1),
(53, 'I am a Teacher', 15, 13, 51, 1),
(54, 'Printer', 16, 171, 209, 0),
(55, 'Promethean Board', 17, 108, 146, 0),
(56, 'Digital Keys/Door Readers', 18, 110, 148, 0),
(57, 'Server', 19, 171, 209, 0),
(58, 'Software/System', 20, 171, 209, 0),
(59, 'Projector', 21, 108, 146, 0),
(60, 'Website', 22, 171, 209, 0),
(61, 'Other', 23, 171, 209, 0),
(62, 'Assessment', 24, 171, 209, 0),
(63, 'Imaging', 25, NULL, NULL, 1),
(64, 'Training', 26, NULL, NULL, 1),
(65, 'Human Resources Dept.', 27, NULL, NULL, 1),
(66, 'ALIO', 28, 171, 209, 0),
(67, 'Tech Initiative', 29, NULL, NULL, 1),
(68, 'Promethean-ActivInspire', 30, 26, 64, 1),
(69, 'Training-Secondary', 31, NULL, NULL, 1),
(70, 'Microsoft Excel', 32, 26, 64, 1),
(71, 'Powerschool', 33, 26, 64, 1),
(72, 'Troubleshooting Basics', 34, 26, 64, 1),
(73, 'Casper Focus', 35, 26, 64, 1),
(74, 'Chromebooks', 36, 26, 64, 1),
(75, 'Gmail', 37, 39, 77, 1),
(76, 'Goalview', 38, 26, 64, 1),
(77, 'Google', 39, 26, 64, 1),
(78, 'Calendars', 40, 39, 77, 1),
(79, 'Drive', 41, 39, 77, 1),
(80, 'Docs', 42, 39, 77, 1),
(81, 'Slides', 43, 39, 77, 1),
(82, 'Sheets', 44, 39, 77, 1),
(83, 'Forms', 45, 39, 77, 1),
(84, 'iBooks Author', 46, 26, 64, 1),
(85, 'iMovie', 47, 26, 64, 1),
(86, 'iPads', 48, 26, 64, 1),
(87, 'iPhoto', 49, 26, 64, 1),
(88, 'iTunesU', 50, 26, 64, 1),
(89, 'LanSchool', 51, 26, 64, 1),
(90, 'MacOS', 52, 26, 64, 1),
(91, 'Windows', 53, 26, 64, 1),
(92, 'Online Classrooms', 54, 26, 64, 1),
(93, 'BUZZ', 55, 54, 92, 1),
(94, 'Edmodo', 56, 54, 92, 1),
(95, 'Schoology', 57, 54, 92, 1),
(96, 'Teacher Gradebook', 58, 33, 71, 1),
(97, 'Reports', 59, 33, 71, 1),
(98, 'Admin', 60, 33, 71, 1),
(99, 'Basics Elementary', 61, 30, 68, 1),
(100, 'Advanced Elementary', 62, 30, 68, 1),
(101, 'Activotes/Activexpressions', 63, 30, 68, 1),
(102, 'Assessment', 64, 26, 64, 1),
(103, 'SAGE Formative', 65, 64, 102, 1),
(104, 'SAGE Summative', 66, 64, 102, 1),
(105, 'Interpreting the Data', 67, 64, 102, 1),
(106, 'Presentation Basics', 68, 26, 64, 1),
(107, 'Microsoft Powerpoint', 69, 68, 106, 1),
(108, 'Apple Keynote', 70, 68, 106, 1),
(109, 'Internet', 71, 26, 64, 1),
(110, 'Safety', 72, 71, 109, 1),
(111, 'Search Tips', 73, 71, 109, 1),
(112, 'Web Pages Elementary', 74, 26, 64, 1),
(113, 'Web Pages Secondary', 75, 26, 64, 1),
(114, 'Advanced Secondary', 76, 30, 68, 1),
(115, 'Basics Secondary', 77, 30, 68, 1),
(116, 'Word Processing Basics', 78, 26, 64, 1),
(117, 'Google Docs', 79, 78, 116, 1),
(118, 'Microsoft Word', 80, 78, 116, 1),
(119, 'Apple Pages', 81, 78, 116, 1),
(120, 'xOther', 82, 26, 64, 1),
(121, 'Security', 83, 26, 64, 1),
(122, 'DIBELS Test Administration', 84, 64, 102, 1),
(123, 'DIBELS Reporting', 85, 64, 102, 1),
(124, 'Utah Compose', 86, 64, 102, 1),
(125, 'Computer', 87, 171, 209, 0),
(126, 'Imaging/Provisioning', 88, 171, 209, 0),
(127, 'Audio System', 89, 108, 146, 0),
(128, 'Meetings Scheduled Tech Help', 90, 171, 209, 0),
(129, 'AppleTV', 91, 108, 146, 0),
(130, 'ChromeBook', 92, 171, 209, 0),
(131, 'Wireless', 93, 171, 209, 0),
(132, 'Security Alarm', 94, 110, 148, 0),
(133, 'Security Camera', 95, 110, 148, 0),
(134, 'iPad', 96, 171, 209, 0),
(135, 'District/Secondary', 97, 22, 60, 1),
(136, 'Elementary/Preschool', 98, 22, 60, 1),
(137, 'Intermediate Elementary', 99, 30, 68, 1),
(138, 'Intermediate Secondary', 100, 30, 68, 1),
(139, 'test permissions', 101, NULL, NULL, 1),
(140, 'Security-Network', 102, 171, 209, 0),
(141, 'After Hours/Installs', 103, NULL, NULL, 1),
(142, 'Purchasing', 104, 171, 209, 0),
(143, 'Inventory', 105, 152, 190, 0),
(144, 'Wonders/GoMath', 106, NULL, NULL, 1),
(145, 'Online Digital Curriculum', 107, 171, 209, 0),
(146, 'AV Audio Visual', 108, 171, 209, 0),
(147, 'Touch TV', 109, 108, 146, 0),
(148, 'Physical Security', 110, 171, 209, 0),
(149, 'Hardware/Device', 111, 171, 209, 0),
(150, 'Document Camera', 112, 108, 146, 0),
(151, 'Time Tracking', 113, NULL, NULL, 1),
(152, 'ATE', 114, 171, 209, 1),
(153, 'Fleet Management', 115, 152, 190, 0),
(154, 'PHS Doors & Keys', 116, 117, 155, 1),
(155, 'PHS Maintenance', 117, NULL, NULL, 1),
(156, 'PHS Electrical', 118, 117, 155, 1),
(157, 'PHS Flooring', 119, 117, 155, 1),
(158, 'PHS Miscellaneous', 120, 117, 155, 1),
(159, 'PHS Plumbing', 121, 117, 155, 1),
(160, 'PHS Outside & Grounds', 122, 117, 155, 1),
(161, 'Fill out Form', 123, NULL, NULL, 1),
(162, 'Marquee', 124, 108, 146, 0),
(163, 'Digital Signage', 125, 108, 146, 0),
(164, 'Google Classroom', 126, NULL, NULL, 1),
(165, 'Canvas', 127, 171, 209, 0),
(166, 'Chromebook Repair/Replacement', 128, 171, 209, 0),
(167, 'Charger', 129, 171, 209, 0),
(168, 'THS Chromebook Repair/Replacement', 130, 128, 166, 0),
(169, 'PHS Chromebook Repair/Replacement', 131, 128, 166, 0),
(170, 'Classroom Management (Blocksi/Lanschool)', 132, 171, 209, 0),
(171, 'Canvas-Amelia', 133, 127, 165, 1),
(172, 'Canvas-Canyon Crest', 134, 127, 165, 1),
(173, 'Canvas-Edgemont', 135, 127, 165, 1),
(174, 'Canvas-Provo Peaks', 136, 127, 165, 1),
(175, 'Canvas-Franklin', 137, 127, 165, 1),
(176, 'Canvas-Lakeview', 138, 127, 165, 1),
(177, 'Canvas-Provost', 139, 127, 165, 1),
(178, 'Canvas-Rock Canyon', 140, 127, 165, 1),
(179, 'Canvas-Spring Creek', 141, 127, 165, 1),
(180, 'Canvas-Sunset View', 142, 127, 165, 1),
(181, 'Canvas-Timpanogos', 143, 127, 165, 1),
(182, 'Canvas-Wasatch', 144, 127, 165, 1),
(183, 'Canvas-Westridge', 145, 127, 165, 1),
(184, 'Canvas-Centennial', 146, 127, 165, 1),
(185, 'Canvas-Dixon', 147, 127, 165, 1),
(186, 'Canvas-Provo High', 148, 127, 165, 1),
(187, 'Canvas-Timpview', 149, 127, 165, 1),
(188, 'Canvas-District Wide', 150, 127, 165, 1),
(189, 'Professional Services', 151, NULL, NULL, 1),
(190, 'Tech Services', 152, 171, 209, 0),
(191, 'AV Audio Visual Software/System', 153, NULL, NULL, 1),
(192, 'Collage', 154, NULL, NULL, 1),
(193, 'Eshare/Unplug\'d', 155, NULL, NULL, 1),
(194, 'Airplay', 156, NULL, NULL, 1),
(195, 'Promethean ActivInspire', 157, NULL, NULL, 1),
(196, 'Provisioning', 158, 152, 190, 0),
(197, 'E-Rate', 159, 152, 190, 0),
(198, 'Other', 160, 11, 49, 1),
(199, 'Other', 161, 110, 148, 0),
(200, 'Other', 162, 151, 189, 1),
(201, 'Other', 163, 20, 201, 1),
(202, 'Tech Help', 164, 171, 209, 0),
(203, 'Visitor Management', 165, 171, 209, 0),
(204, 'Inventory', 166, NULL, NULL, 1),
(205, 'EULA', 167, 171, 209, 1),
(206, 'Maps', 168, NULL, NULL, 1),
(207, 'Road Runner', 169, NULL, NULL, 1),
(208, 'Public Relations/Communications', 170, NULL, NULL, 0),
(209, 'Technology Support', 171, NULL, NULL, 0),
(210, 'Update Content on Website', 172, 170, 208, 0),
(211, 'Website Issue', 173, 170, 208, 0),
(212, 'Build New Website', 174, 170, 208, 0),
(213, 'Research&Development', 175, 152, 190, 0),
(214, 'Camp Tracking', 176, 152, 190, 0),
(215, 'Software Creation', 177, 181, 219, 0),
(216, 'Workflow Creation', 178, 181, 219, 0),
(217, 'Feature Request', 179, 181, 219, 0),
(218, 'Repeated Maintenance/Cleaning', 180, 152, 190, 0),
(219, 'Development', 181, 171, 209, 0),
(220, 'District Public Website', 182, 22, 60, 0),
(221, 'District Employee Website', 183, 22, 60, 0),
(222, 'High School Website', 184, 22, 60, 0),
(223, 'Middle School Website', 185, 22, 60, 0),
(224, 'Elementary School Website', 186, 22, 60, 0),
(225, 'All Websites', 187, 22, 60, 0),
(226, 'Provo High School', 188, 184, 222, 0),
(227, 'Timpview High School', 189, 184, 222, 0),
(228, 'Independence High School', 190, 184, 222, 0),
(229, 'Dixon Middle School', 191, 185, 223, 0),
(230, 'Centennial Middle School', 192, 185, 223, 0),
(231, 'Amelia Earhart Elementary School', 193, 186, 224, 0),
(232, 'Canyon Crest Elementary School', 194, 186, 224, 0),
(233, 'Edgemont Elementary School', 195, 186, 224, 0),
(234, 'Franklin Elementary School', 196, 186, 224, 0),
(235, 'Lakeview Elementary School', 197, 186, 224, 0),
(236, 'Provo Peaks Elementary School', 198, 186, 224, 0),
(237, 'Provost Elementary School', 199, 186, 224, 0),
(238, 'Rock Canyon Elementary School', 200, 186, 224, 0),
(239, 'Spring Creek Elementary School', 201, 186, 224, 0),
(240, 'Sunset View Elementary School', 202, 186, 224, 0),
(241, 'Sunrise Preschool', 203, 186, 224, 0),
(242, 'Timpanogos Elementary School', 204, 186, 224, 0),
(243, 'Wasatch Elementary School', 205, 186, 224, 0),
(244, 'Westridge Elementary School', 206, 186, 224, 0),
(245, 'EDPlan', 207, 171, 209, 0),
(246, 'Bug Fix', 208, 181, 219, 0);

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `due_date` date DEFAULT NULL,
  `last_updated` datetime NOT NULL DEFAULT current_timestamp(),
  `name` varchar(100) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `location` varchar(50) DEFAULT NULL,
  `room` varchar(10) DEFAULT NULL,
  `employee` varchar(255) DEFAULT NULL,
  `client` varchar(255) DEFAULT NULL,
  `status` enum('open','closed','resolved','pending','vendor','maintenance') DEFAULT NULL,
  `attachment_path` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `request_type_id` varchar(200) DEFAULT NULL,
  `cc_emails` varchar(512) DEFAULT NULL,
  `bcc_emails` varchar(512) DEFAULT NULL,
  `priority` int(10) NOT NULL,
  `merged_into_id` int(11) DEFAULT NULL,
  `parent_ticket` int(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `ticket_logs`
--

CREATE TABLE `ticket_logs` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `old_value` longtext DEFAULT NULL,
  `new_value` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `ifasid` varchar(255) DEFAULT NULL,
  `worksite` varchar(255) DEFAULT NULL,
  `pre_name` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `can_view_tickets` tinyint(1) NOT NULL DEFAULT 1,
  `can_create_tickets` tinyint(1) NOT NULL DEFAULT 1,
  `can_edit_tickets` tinyint(1) NOT NULL DEFAULT 1,
  `can_delete_tickets` tinyint(1) NOT NULL DEFAULT 0,
  `is_tech` tinyint(4) NOT NULL DEFAULT 0,
  `is_supervisor` tinyint(1) NOT NULL DEFAULT 0,
  `is_location_manager` tinyint(1) NOT NULL DEFAULT 0,
  `location_manager_sitenumber` int(11) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `color_scheme` varchar(15) NOT NULL DEFAULT 'light',
  `supervisor_username` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `employee` (`employee`);

--
-- Indexes for table `exclude_days`
--
ALTER TABLE `exclude_days`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `flagged_tickets`
--
ALTER TABLE `flagged_tickets`
  ADD PRIMARY KEY (`user_id`,`ticket_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`note_id`),
  ADD KEY `fk_ticket_note_id` (`linked_id`);

--
-- Indexes for table `request_type`
--
ALTER TABLE `request_type`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ticket_logs`
--
ALTER TABLE `ticket_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ticket_logs` (`ticket_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=771;

--
-- AUTO_INCREMENT for table `exclude_days`
--
ALTER TABLE `exclude_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `note_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `request_type`
--
ALTER TABLE `request_type`
  MODIFY `request_id` int(25) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=247;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `ticket_logs`
--
ALTER TABLE `ticket_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=390;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`),
  ADD CONSTRAINT `alerts_ibfk_2` FOREIGN KEY (`employee`) REFERENCES `users` (`username`);

--
-- Constraints for table `flagged_tickets`
--
ALTER TABLE `flagged_tickets`
  ADD CONSTRAINT `FK_FlaggedTicketsID` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `fk_ticket_note_id` FOREIGN KEY (`linked_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_logs`
--
ALTER TABLE `ticket_logs`
  ADD CONSTRAINT `fk_ticket_logs` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
