-- MyMusic Database
-- Importa in phpMyAdmin: crea un database chiamato `mymusic`, poi importa questo file

CREATE DATABASE IF NOT EXISTS `mymusic` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mymusic`;

-- Utenti
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar_letter` CHAR(1) NOT NULL DEFAULT 'M',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sessioni
CREATE TABLE `sessions` (
  `token` VARCHAR(64) NOT NULL PRIMARY KEY,
  `user_id` INT NOT NULL,
  `expires_at` DATETIME NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Preferiti
CREATE TABLE `favorites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `yt_id` VARCHAR(20) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `channel` VARCHAR(255) NOT NULL,
  `thumb` VARCHAR(500) NOT NULL,
  `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_fav` (`user_id`, `yt_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Playlist
CREATE TABLE `playlists` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT '',
  `cover_thumb` VARCHAR(500) DEFAULT '',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Brani nelle playlist
CREATE TABLE `playlist_songs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `playlist_id` INT NOT NULL,
  `yt_id` VARCHAR(20) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `channel` VARCHAR(255) NOT NULL,
  `thumb` VARCHAR(500) NOT NULL,
  `position` INT NOT NULL DEFAULT 0,
  `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_pl_song` (`playlist_id`, `yt_id`),
  FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
