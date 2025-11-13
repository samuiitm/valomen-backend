DROP DATABASE IF EXISTS valomen_gg;

CREATE DATABASE IF NOT EXISTS valomen_gg
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE valomen_gg;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    passwd_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    admin TINYINT(1) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    country VARCHAR(5) NOT NULL
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('upcoming','ongoing','completed') DEFAULT NULL,
    prize INT UNSIGNED DEFAULT NULL,
    region VARCHAR(5) NOT NULL,
    logo VARCHAR(255) NOT NULL,
    post_author INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (post_author) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_1 INT UNSIGNED NOT NULL,
    team_2 INT UNSIGNED NULL,
    score_team_1 TINYINT UNSIGNED NULL,
    score_team_2 TINYINT UNSIGNED NULL,
    date DATE NOT NULL,
    hour TIME NOT NULL,
    event_stage VARCHAR(100) NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    post_author INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (team_1) REFERENCES teams(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (team_2) REFERENCES teams(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (post_author) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username, passwd_hash, email, admin)
VALUES
('admin', '$2y$10$JqK7WnweyM8vnh.gnOO3GObbGuWi8HpfQ98dFv9GgAhQ7nmxFjE8C', 'samuelcanadas2711@gmail.com', 1),
('s.canadas', '$2y$10$O1vmIMinouaIeIlDEEXm2ui3TICCPkIRzN81SAaQRj2fmM30GGJKO', 's.canadas@sapalomera.cat', 0);

INSERT INTO teams (name, country)
VALUES
('Team Heretics', 'es'),
('FNATIC', 'eu'),
('Natus Vincere', 'ua'),
('KOI', 'es'),
('BBL Esports', 'tr'),
('GIANTX', 'es'),
('Team Vitality', 'fr'),
('Furia', 'br'),
('Loud', 'br'),
('Gentle Mates', 'fr'),
('Sentinels', 'us'),
('KRÜ Esports', 'ar'),
('Leviatán', 'cl'),
('G2 Esports', 'us');

INSERT INTO events (name, start_date, end_date, status, prize, region, logo, post_author)
VALUES
('VCT 2026: EMEA Kickoff', '2025-11-01', '2025-12-15', 'ongoing', 0, 'de', '1.png', 1),
('VCT 2026: Americas Kickoff', '2026-01-10', '2026-02-05', 'upcoming', 0, 'us', '2.png', 1),
('VCT 2026: Pacific Kickoff', '2026-01-15', '2026-02-10', 'upcoming', 0, 'th', '3.png', 1),
('VCT 2026: China Kickoff', '2026-01-20', '2026-02-12', 'upcoming', 0, 'cn', '4.png', 1),
('Valorant Champions 2025', '2025-08-01', '2025-08-30', 'completed', 0, 'fr', '5.png', 1),
('VCT 2025: Americas Ascension', '2025-05-01', '2025-05-20', 'completed', 0, 'br', '6.png', 1),
('VCT 2025: EMEA Ascension', '2025-05-01', '2025-05-20', 'completed', 0, 'de', '7.png', 1),
('VCT 2025: Pacific Ascension', '2025-05-01', '2025-05-20', 'completed', 0, 'th', '8.png', 1),
('VCT 2025: China Ascension', '2025-05-01', '2025-05-20', 'completed', 0, 'cn', '9.png', 1);

INSERT INTO matches (team_1, team_2, score_team_1, score_team_2, date, hour, event_stage, event_id, post_author)
VALUES
(1, 2, NULL, NULL, '2025-11-13', '13:00:00', 'Upper Round 1', 1, 1),
(3, 4, NULL, NULL, '2025-11-13', '15:00:00', 'Upper Round 1', 1, 1),
(5, NULL, NULL, NULL, '2025-11-14', '13:00:00', 'Lower Round 1', 1, 1),
(10, NULL, NULL, NULL, '2025-11-14', '15:00:00', 'Lower Round 1', 1, 1),
(12, 13, NULL, NULL, '2026-01-11', '23:00:00', 'Upper Round 1', 2, 1),
(11, 14, NULL, NULL, '2026-01-12', '02:00:00', 'Upper Round 1', 2, 1),
(6, NULL, NULL, NULL, '2025-11-15', '13:00:00', 'Upper Round 2', 1, 1),
(7, NULL, NULL, NULL, '2025-11-15', '15:00:00', 'Upper Round 2', 1, 1),
(8, 9, NULL, NULL, '2026-01-13', '23:00:00', 'Lower Round 1', 2, 1);