DROP DATABASE IF EXISTS `delivery_app_db`;
CREATE DATABASE `delivery_app_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `delivery_app_db`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Table admins_inf
CREATE TABLE `admins_inf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `cin` varchar(255) NOT NULL,
  `date_embauche` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins_inf` (`id`, `nom`, `prenom`, `photo`, `email`, `cin`, `date_embauche`, `created_at`) VALUES
(1, 'Benali', 'Liya', '11.jpg', 'liya.benali@example.com', 'AB123456', '2023-01-10', '2025-06-24 02:14:18'),
(2, 'Alami', 'Mohammed', '17.jpg', 'm.alami@example.com', 'BK123456', '2023-01-15', '2025-06-25 09:21:06'),
(3, 'Bennani', 'Fatima', '16.jpg', 'f.bennani@example.com', 'BK789012', '2023-03-20', '2025-06-25 09:21:06'),
(4, 'Tazi', 'Ahmed', '15.jpg', 'a.tazi@example.com', 'BK345678', '2023-06-10', '2025-06-25 09:21:06'),
(5, 'mohammed', 'taouille', 'admin_685be12042eb41.82295730.jpg', 'mohammedtaouille@gmail.com', 'JD3456', '2013-08-03', '2025-06-25 11:44:32');

-- Table clients
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `telephone` varchar(50) NOT NULL,
  `adresse` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clients` (`id`, `nom`, `prenom`, `email`, `telephone`, `adresse`) VALUES
(1, 'benfatah', 'zakaria', 'mohammedtaouille@gmail.com', '0625593637', 'canada'),
(2, '3wita', 'mrzoug', '3wita@gmail.com', '0625599637', 'ait 3mira'),
(3, 'hossayn', 'hasson', 'hosayn@gmail.com', '252462356256', 'tioughza'),
(4, 'yassin', 'ait moubarek', 'yassin@gmail.com', '0723623590', 'massat');

-- Table livreurs
CREATE TABLE `livreurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom_livreur` varchar(255) NOT NULL,
  `prenom` varchar(255) DEFAULT NULL,
  `cin` varchar(50) DEFAULT NULL UNIQUE,
  `email` varchar(255) DEFAULT NULL UNIQUE,
  `telephone` varchar(50) DEFAULT NULL,
  `situation_familiale` varchar(100) DEFAULT NULL,
  `statut` enum('actif','inactif') DEFAULT 'inactif',
  `contact` varchar(255) DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `livreurs` (`id`, `nom_livreur`, `prenom`, `cin`, `email`, `telephone`, `situation_familiale`, `statut`, `contact`, `date_embauche`, `age`, `deleted_at`) VALUES
(3, 'Ahmed', 'Ali', 'CC11223', 'ahmed.ali@example.com', '0655443322', 'Célibataire', 'inactif', NULL, '2010-06-22', 24, NULL),
(5, 'Omar', 'El idrissi', 'EE77889', 'omar.elidrissi@example.com', '0678901234', 'Célibataire', 'actif', NULL, '2014-06-22', 33, NULL),
(6, 'Nadia', 'Mansouri', 'FF00112', 'nadia.mansouri@example.com', '0623456789', 'Mariée', 'inactif', NULL, '2024-06-22', 45, '2025-06-23 15:24:10'),
(7, 'Youssef', 'Alaoui', 'GG33445', 'youssef.alaoui@example.com', '0687654321', 'Célibataire', 'inactif', NULL, '2024-06-22', 27, NULL),
(11, 'hassan ', 'zakaria', 'JD', 'sudhkq@gmail.com', '05467347', 'marie', 'inactif', NULL, '2022-06-22', 25, '2025-06-24 04:25:04'),
(12, 'mohamed ', 'taouille', 'JH97914', 'mohammd@gmail.com', 'JLHJ', 'marie', 'actif', NULL, '2018-06-22', 34, NULL),
(13, 'boubaker ', 'boulaid', 'JH5678', 'boubaker@gmail.com', '0956783456', 'marie', 'inactif', NULL, '2020-03-23', 19, NULL),
(14, 'anwar ', 'marzoug', 'JE34578', 'anwar@gmail.com', '05783298', 'celibataire', 'inactif', NULL, '0000-00-00', 37, NULL),
(15, 'zakaria', 'benfatah', 'JD96014', 'zakaria@gmail.com', '0578239729', 'marie', 'inactif', NULL, '2013-03-12', 36, NULL),
(16, 'hasson', 'housayn', 'JD45721', 'houssayn@gmail.com', '67474623856', 'celibataire', 'inactif', NULL, '2013-05-12', 30, NULL);

-- Table commandes
CREATE TABLE `commandes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `type_commande` varchar(100) NOT NULL,
  `demande_exacte` text NOT NULL,
  `livreur_id` int(11) DEFAULT NULL,
  `statut` varchar(50) DEFAULT 'en attente',
  `date_commande` datetime DEFAULT current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `livreur_id` (`livreur_id`),
  CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `commandes_ibfk_2` FOREIGN KEY (`livreur_id`) REFERENCES `livreurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `commandes` (`id`, `client_id`, `type_commande`, `demande_exacte`, `livreur_id`, `statut`, `date_commande`, `archived_at`, `deleted_at`) VALUES
(2, 1, 'viande', '&é"erfvb \r\n', NULL, 'terminee', '2025-06-22 15:04:27', NULL, NULL),
(5, 1, 'fruits', 'lanae', NULL, 'terminee', '2025-06-22 22:22:26', NULL, NULL),
(6, 1, 'fruits', 'lanae', 3, 'annulee', '2025-06-22 22:23:00', NULL, NULL),
(8, 1, 'objet personnel', 'jib liya dolipran', 3, 'terminee', '2025-06-22 22:35:55', NULL, NULL),
(12, 3, 'autres', 'anwar', NULL, 'terminee', '2025-06-23 01:40:16', NULL, NULL),
(13, 3, 'autres', 'batat o khizo o lnbanna jkqscq', 3, 'terminee', '2025-06-23 04:06:52', NULL, NULL),
(14, 1, 'autres', 'dolostop', 3, 'terminee', '2025-06-23 04:44:00', NULL, NULL),
(15, 1, 'autres', 'dolostop ', 3, 'terminee', '2025-06-23 05:03:11', NULL, NULL),
(16, 1, 'autres', 'dwa l3inin', NULL, 'en attente', '2025-06-23 05:05:00', NULL, NULL),
(17, 1, 'autres', 'dwa l3inin', 3, 'terminee', '2025-06-23 05:09:11', NULL, NULL),
(18, 1, 'autres', 'dwa l3inin', NULL, 'terminee', '2025-06-23 05:20:51', NULL, NULL),
(19, 1, 'fruits', '2 DLAH   3KG BOU3WID    6KG FRIZ', 12, 'en attente', '2025-06-23 14:33:41', NULL, NULL),
(20, 4, 'fruits', '2 kilo toffah', 12, 'terminee', '2025-06-23 17:36:47', NULL, NULL);

COMMIT;
