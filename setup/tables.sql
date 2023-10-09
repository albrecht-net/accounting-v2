-- Datenbank Tabellen Vorlage für die Applikations-Datenbank
-- Gültig ab: Accounting vX.X.X
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(64) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `status` ENUM('Y','N') NOT NULL DEFAULT 'N',
    `password_reset` ENUM('Y','N') NOT NULL DEFAULT 'Y',
    PRIMARY KEY (`userID`),
    UNIQUE KEY `username` (`username`)
)
ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE=utf8mb4_general_ci;

--
-- Tabellenstruktur für Tabelle `sessions`
--

CREATE TABLE `sessions` (
    `id` varchar(255) NOT NULL,
    `user_id` int(11) NOT NULL,
    `user_agent` varchar(64) DEFAULT NULL,
    `ip_address` varchar(64) DEFAULT NULL,
    `expiry_date` datetime NOT NULL,
    `last_activity` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;

--
-- Tabellenstruktur für Tabelle `databases`
--

CREATE TABLE `databases` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `created` datetime NOT NULL DEFAULT current_timestamp() ,
    `db_host` varchar(32) NOT NULL,
    `db_port` int(11) NOT NULL,
    `db_username` varchar(64) NOT NULL,
    `db_password` varchar(64) NOT NULL,
    `db_name` varchar(64) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `userID` (`user_id`),
    CONSTRAINT `databases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;