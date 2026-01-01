-- Migration: Umfrage-Bewertungen speichern
-- Erstellt Tabelle fuer Sterne-Bewertungen pro Kriterium

CREATE TABLE `umfrage_bewertungen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aufguss_id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `aufguss_name_id` int(11) DEFAULT NULL,
  `kriterium` varchar(120) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `datum` date NOT NULL,
  `erstellt_am` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_umfrage_aufguss` (`aufguss_id`),
  KEY `idx_umfrage_plan` (`plan_id`),
  KEY `idx_umfrage_name` (`aufguss_name_id`),
  KEY `idx_umfrage_kriterium` (`kriterium`),
  CONSTRAINT `fk_umfrage_aufguss` FOREIGN KEY (`aufguss_id`) REFERENCES `aufguesse` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_umfrage_plan` FOREIGN KEY (`plan_id`) REFERENCES `plaene` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_umfrage_name` FOREIGN KEY (`aufguss_name_id`) REFERENCES `aufguss_namen` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
