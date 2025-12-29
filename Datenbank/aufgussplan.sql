-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 29. Dez 2025 um 11:46
-- Server-Version: 10.4.32-MariaDB
-- PHP-Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `aufgussplan`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `aufguesse`
--

CREATE TABLE `aufguesse` (
  `id` int(11) NOT NULL,
  `datum` date NOT NULL,
  `zeit` time NOT NULL,
  `zeit_anfang` time DEFAULT NULL,
  `zeit_ende` time DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `duftmittel_id` int(11) DEFAULT NULL,
  `sauna_id` int(11) DEFAULT NULL,
  `aufgieser_name` varchar(255) DEFAULT NULL,
  `mitarbeiter_id` int(11) DEFAULT NULL,
  `staerke` int(11) DEFAULT NULL,
  `beschreibung` text DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur fuer Tabelle `aufguss_aufgieser`
--

CREATE TABLE `aufguss_aufgieser` (
  `id` int(11) NOT NULL,
  `aufguss_id` int(11) NOT NULL,
  `mitarbeiter_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `aufguesse`
--

INSERT INTO `aufguesse` (`id`, `datum`, `zeit`, `zeit_anfang`, `zeit_ende`, `name`, `duftmittel_id`, `sauna_id`, `aufgieser_name`, `mitarbeiter_id`, `staerke`, `beschreibung`, `plan_id`) VALUES
(1, '2024-12-21', '14:00:00', '14:00:00', '14:30:00', 'Entspannungs-Aufguss', 3, 2, NULL, 2, 2, NULL, 1),
(3, '2024-12-21', '16:30:00', '19:43:00', '21:15:00', 'Kamille-Balsam', 5, 3, NULL, 1, 3, NULL, 1),
(7, '2024-12-22', '14:00:00', '14:00:00', '14:30:00', 'Citrus-Explosion', 4, 4, NULL, 1, 4, NULL, 2),
(8, '2024-12-22', '15:30:00', '12:30:00', '16:15:00', 'Eukalyptus-Force', 1, 1, NULL, 2, 3, NULL, 2),
(9, '2024-12-22', '17:00:00', '18:29:00', '20:45:00', 'Waldmeister-Finish', 6, 3, NULL, 9, 3, NULL, 2),
(10, '2024-12-23', '12:00:00', '12:00:00', '12:20:00', 'Schnell-Aufguss', 1, 4, 'Lisa Wagner', NULL, 3, NULL, NULL),
(41, '2025-12-25', '19:25:00', '00:00:00', '00:00:00', 'Mitarbeiter', NULL, 2, NULL, 2, 6, NULL, 1),
(42, '2025-12-25', '19:49:00', '00:00:00', '00:00:00', 'Test', NULL, 3, NULL, 9, 3, NULL, 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `duftmittel`
--

CREATE TABLE `duftmittel` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `beschreibung` text DEFAULT NULL,
  `erstellt_am` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `duftmittel`
--

INSERT INTO `duftmittel` (`id`, `name`, `beschreibung`, `erstellt_am`) VALUES
(1, 'Eukalyptus', NULL, '2025-12-25 17:01:17'),
(2, 'Minze', NULL, '2025-12-25 17:01:17'),
(3, 'Lavendel', NULL, '2025-12-25 17:01:17'),
(4, 'Zitrone', NULL, '2025-12-25 17:01:17'),
(5, 'Kamille', NULL, '2025-12-25 17:01:17'),
(6, 'Waldmeister', NULL, '2025-12-25 17:01:17');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mitarbeiter`
--

CREATE TABLE `mitarbeiter` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `bild` varchar(255) DEFAULT NULL,
  `erstellt_am` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `mitarbeiter`
--

INSERT INTO `mitarbeiter` (`id`, `name`, `bild`, `erstellt_am`) VALUES
(1, 'Peter Mustermann', 'mitarbeiter/694d9c7d6471c_beard-1845166_1280.jpg', '2025-12-25 17:01:17'),
(2, 'Anna Schmidt', 'mitarbeiter/694da872d9126_girl-1867092_1280.jpg', '2025-12-25 17:01:17'),
(3, 'Thomas Bauer', NULL, '2025-12-25 17:01:17'),
(4, 'Lisa Wagner', NULL, '2025-12-25 17:01:17'),
(7, 'peter1', NULL, '2025-12-15 15:07:47'),
(8, 'peterschwitz', NULL, '2025-12-15 15:11:28'),
(9, 'Felix Schwitz', 'mitarbeiter/694da837d9e43_Felix.png', '2025-12-15 19:00:56'),
(10, 'sdfsdfsd', NULL, '2025-12-15 19:03:18'),
(11, 'Peter ', NULL, '2025-12-17 15:55:14');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `plaene`
--

CREATE TABLE `plaene` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `beschreibung` text DEFAULT NULL,
  `erstellt_am` timestamp NOT NULL DEFAULT current_timestamp(),
  `hintergrund_bild` varchar(255) DEFAULT NULL,
  `werbung_media` varchar(255) DEFAULT NULL,
  `werbung_media_typ` varchar(50) DEFAULT NULL,
  `werbung_interval_minuten` int(11) NOT NULL DEFAULT 10,
  `werbung_dauer_sekunden` int(11) NOT NULL DEFAULT 10,
  `werbung_aktiv` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `plaene`
--

INSERT INTO `plaene` (`id`, `name`, `beschreibung`, `erstellt_am`, `hintergrund_bild`, `werbung_media`, `werbung_media_typ`, `werbung_interval_minuten`, `werbung_dauer_sekunden`, `werbung_aktiv`) VALUES
(1, 'Wellness-Tag', 'Entspannender Aufguss-Tag mit beruhigenden Düften', '2025-12-25 17:01:17', 'plan/6951769bb7552_trees-4296305_1280.jpg', NULL, NULL, 10, 10, 0),
(2, 'Power-Aufgüsse', 'Energetische Aufgüsse für mehr Vitalität', '2025-12-25 17:01:17', 'plan/69515f048c93f_nature-5411408_1280.jpg', NULL, NULL, 10, 10, 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `saunen`
--

CREATE TABLE `saunen` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `beschreibung` text DEFAULT NULL,
  `bild` varchar(255) DEFAULT NULL,
  `erstellt_am` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `saunen`
--

INSERT INTO `saunen` (`id`, `name`, `beschreibung`, `bild`, `erstellt_am`) VALUES
(1, 'Finnische Sauna', NULL, NULL, '2025-12-25 17:01:17'),
(2, 'Bio-Sauna', NULL, 'sauna/694d867a5c75c_latvia-908931_1280.jpg', '2025-12-25 17:01:17'),
(3, 'Dampfsauna', NULL, 'sauna/694d86aa38d50_cottage-7010884_1280.jpg', '2025-12-25 17:01:17'),
(4, 'Infrarotkabine', NULL, NULL, '2025-12-25 17:01:17'),
(13, 'sauna test', NULL, NULL, '2025-12-25 16:53:10');

--
-- Indizes der exportierten Tabellen
--
-- Indizes fuer die Tabelle `aufguss_aufgieser`
--
ALTER TABLE `aufguss_aufgieser`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aufguss_aufgieser_aufguss` (`aufguss_id`),
  ADD KEY `idx_aufguss_aufgieser_mitarbeiter` (`mitarbeiter_id`);

--

--
-- Indizes für die Tabelle `aufguesse`
--
ALTER TABLE `aufguesse`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_aufguss_plan` (`plan_id`);

--
-- Indizes für die Tabelle `duftmittel`
--
ALTER TABLE `duftmittel`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_duftmittel_name` (`name`);

--
-- Indizes für die Tabelle `mitarbeiter`
--
ALTER TABLE `mitarbeiter`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_mitarbeiter_name` (`name`);

--
-- Indizes für die Tabelle `plaene`
--
ALTER TABLE `plaene`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `saunen`
--
ALTER TABLE `saunen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_sauna_name` (`name`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `aufguesse`
--
ALTER TABLE `aufguesse`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT für Tabelle `duftmittel`
--
ALTER TABLE `duftmittel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT für Tabelle `mitarbeiter`
--
ALTER TABLE `mitarbeiter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT für Tabelle `plaene`
--
ALTER TABLE `plaene`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT für Tabelle `saunen`
--
ALTER TABLE `saunen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `aufguesse`
--
ALTER TABLE `aufguesse`
  ADD CONSTRAINT `aufguesse_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `plaene` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_aufguss_plan` FOREIGN KEY (`plan_id`) REFERENCES `plaene` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `aufguss_aufgieser`
--
ALTER TABLE `aufguss_aufgieser`
  ADD CONSTRAINT `aufguss_aufgieser_ibfk_1` FOREIGN KEY (`aufguss_id`) REFERENCES `aufguesse` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aufguss_aufgieser_ibfk_2` FOREIGN KEY (`mitarbeiter_id`) REFERENCES `mitarbeiter` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
