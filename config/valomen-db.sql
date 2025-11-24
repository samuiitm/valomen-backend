DROP DATABASE IF EXISTS valomen_gg;
CREATE DATABASE IF NOT EXISTS valomen_gg
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE valomen_gg;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    passwd_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    logo VARCHAR(255),
    points INT NOT NULL DEFAULT 0,
    admin TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    country VARCHAR(5) NOT NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('Upcoming','Ongoing','Completed') DEFAULT NULL,
    prize INT UNSIGNED DEFAULT NULL,
    region VARCHAR(5) NOT NULL,
    logo VARCHAR(255) NOT NULL,
    post_author INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (post_author) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE matches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_1 INT UNSIGNED NULL,
    team_2 INT UNSIGNED NULL,
    score_team_1 TINYINT UNSIGNED NULL,
    score_team_2 TINYINT UNSIGNED NULL,
    date DATE NOT NULL,
    hour TIME NOT NULL,
    status ENUM('Upcoming','Live','Completed') DEFAULT NULL,
    best_of TINYINT UNSIGNED NOT NULL DEFAULT 3,
    event_stage VARCHAR(100) NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    post_author INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (team_1) REFERENCES teams(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    FOREIGN KEY (team_2) REFERENCES teams(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (post_author) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE predictions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    match_id INT UNSIGNED NOT NULL,
    score_team_1_pred TINYINT UNSIGNED NOT NULL,
    score_team_2_pred TINYINT UNSIGNED NOT NULL,
    points_awarded INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_prediction (user_id, match_id),
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_teams (
    event_id INT UNSIGNED NOT NULL,
    team_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (event_id, team_id),
    FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE remember_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    selector CHAR(16) NOT NULL UNIQUE,
    hashed_validator CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username, passwd_hash, email, logo, admin)
VALUES
('admin', '$2y$10$9NormUYn3BVGoyZjm5kUpuRY5eKk14iEGR6hPLB2BXh0FNZWEL2gq', 'samuelcanadas2711@gmail.com', NULL, 1);

INSERT INTO users (username, passwd_hash, email, logo, points, admin)
VALUES
('s.canadas', '$2y$10$bQVnssDHklHm0Wx2rxrBEuYyEHrE92jHPdXrVCQfSBoA00dX2Tnr2', 's.canadas@sapalomera.cat', NULL, 10, 0);

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
('G2 Esports', 'us'),
('NRG', 'us'),
('DRX', 'kr'),
('Paper Rex', 'sg'),
('MIBR', 'br'),
('Rex Regum Qeon', 'id'),
('T1', 'kr'),
('Xi Lai Gaming', 'cn'),
('Team Liquid', 'eu'),
('Dragon Ranger Gaming', 'cn'),
('Bilibili Gaming', 'cn'),
('EDward Gaming', 'cn'),
('100 Thieves', 'us'),
('Cloud 9', 'us'),
('Evil Geniuses', 'us'),
('ENVY', 'us'),
('DetonatioN FocusMe', 'jp'),
('Gen.G', 'kr'),
('Global Esports', 'in'),
('Team Secret', 'ph'),
('ZETA DIVISION', 'jp'),
('SLT Seongnam', 'kr'),
('Nongshim RedForce', 'kr'),
('FUT Esports', 'tr'),
('Karmine Corp', 'fr'),
('ULF Esports', 'tr'),
('BBL PCIFIC', 'tr'),
('All Gamers', 'cn'),
('Partner Team', 'cn'),
('FunPlus Phoenix', 'cn'),
('JD Mall JDG Esports', 'cn'),
('Nova Esports', 'cn'),
('Wuxi Titan Esports Club', 'cn'),
('Trace Esports', 'cn'),
('TYLOO', 'cn'),
('Wolves Esports', 'cn');

INSERT INTO events (name, start_date, end_date, status, prize, region, logo, post_author)
VALUES
('VCT 2026: EMEA Kickoff', '2025-11-01', NULL, 'Ongoing', 0, 'de', '1.png', 1),
('VCT 2026: Americas Kickoff', '2026-01-10', '2026-02-05', 'Upcoming', 0, 'us', '2.png', 1),
('VCT 2026: Pacific Kickoff', '2026-01-15', '2026-02-10', 'Upcoming', 0, 'th', '3.png', 1),
('VCT 2026: China Kickoff', '2026-01-20', '2026-02-12', 'Upcoming', 0, 'cn', '4.png', 1),
('Valorant Champions 2025', '2025-09-12', '2025-10-06', 'Completed', 2250000, 'fr', '5.png', 1),
('VCT 2025: Americas Ascension', '2025-05-01', '2025-05-20', 'Completed', 100000, 'br', '6.png', 1),
('VCT 2025: EMEA Ascension', '2025-05-01', '2025-05-20', 'Completed', 117113, 'de', '7.png', 1),
('VCT 2025: Pacific Ascension', '2025-05-01', '2025-05-20', 'Completed', 100000, 'th', '8.png', 1),
('VCT 2025: China Ascension', '2025-05-01', '2025-05-20', 'Completed', 100000, 'cn', '9.png', 1);

INSERT INTO event_teams (event_id, team_id)
VALUES
(1, 1),(1, 2),(1, 3),(1, 5),(1, 6),(1, 7),(1, 10),(1, 22),(1, 37),(1, 38),(1, 39),(1, 40),
(2, 8),(2, 9),(2, 11),(2, 12),(2, 13),(2, 14),(2, 15),(2, 18),(2, 26),(2, 27),(2, 28),(2, 29),
(3, 16),(3, 17),(3, 19),(3, 20),(3, 30),(3, 31),(3, 32),(3, 33),(3, 34),(3, 35),(3, 36),
(4, 21),(4, 23),(4, 24),(4, 25),(4, 41),(4, 42),(4, 43),(4, 44),(4, 45),(4, 46),(4, 47),(4, 48),(4, 49),
(5, 1),(5, 2),(5, 6),(5, 11),(5, 14),(5, 15),(5, 16),(5, 17),(5, 18),(5, 19),(5, 20),(5, 21),(5, 22),(5, 23),(5, 24),(5, 25);

INSERT INTO matches (team_1, team_2, score_team_1, score_team_2, date, hour, event_stage, event_id, post_author)
VALUES
(1, 2, NULL, NULL, '2025-11-13', '13:00:00', 'Upper Round 1', 1, 1),
(3, 38, NULL, NULL, '2025-11-13', '15:00:00', 'Upper Round 1', 1, 1),
(5, NULL, NULL, NULL, '2025-11-14', '13:00:00', 'Lower Round 1', 1, 1),
(10, NULL, NULL, NULL, '2025-11-14', '15:00:00', 'Lower Round 1', 1, 1),
(12, 13, NULL, NULL, '2025-11-15', '23:00:00', 'Upper Round 1', 2, 1),
(11, 14, NULL, NULL, '2025-11-16', '02:00:00', 'Upper Round 1', 2, 1),
(6, NULL, NULL, NULL, '2025-11-15', '13:00:00', 'Upper Round 2', 1, 1),
(7, NULL, NULL, NULL, '2025-11-15', '15:00:00', 'Upper Round 2', 1, 1),
(8, 9, NULL, NULL, '2025-11-16', '23:00:00', 'Lower Round 1', 2, 1);

INSERT INTO matches (team_1, team_2, score_team_1, score_team_2, date, hour, status, event_stage, event_id, post_author)
VALUES
(6, 11, 2, 1, '2025-09-12', '18:00:00', 'Completed', 'Group Stage–Opening (A)', 5, 1),
(17, 21, 2, 0, '2025-09-12', '15:00:00', 'Completed', 'Group Stage–Opening (A)', 5, 1),
(19, 2, 0, 2, '2025-09-13', '18:00:00', 'Completed', 'Group Stage–Opening (B)', 5, 1),
(24, 18, 0, 2, '2025-09-13', '15:00:00', 'Completed', 'Group Stage–Opening (B)', 5, 1),
(22, 16, 0, 2, '2025-09-15', '18:00:00', 'Completed', 'Group Stage–Opening (C)', 5, 1),
(15, 25, 2, 0, '2025-09-15', '15:00:00', 'Completed', 'Group Stage–Opening (C)', 5, 1),
(14, 1, 0, 2, '2025-09-14', '18:00:00', 'Completed', 'Group Stage–Opening (D)', 5, 1),
(23, 20, 0, 2, '2025-09-14', '15:00:00', 'Completed', 'Group Stage–Opening (D)', 5, 1),
(17, 6, 2, 1, '2025-09-17', '15:00:00', 'Completed', 'Group Stage–Winner''s (A)', 5, 1),
(18, 2, 1, 2, '2025-09-18', '15:00:00', 'Completed', 'Group Stage–Winner''s (B)', 5, 1),
(16, 15, 1, 2, '2025-09-17', '18:00:00', 'Completed', 'Group Stage–Winner''s (C)', 5, 1),
(1, 20, 2, 0, '2025-09-18', '18:00:00', 'Completed', 'Group Stage–Winner''s (D)', 5, 1),
(21, 11, 2, 1, '2025-09-19', '18:00:00', 'Completed', 'Group Stage–Elimination (A)', 5, 1),
(24, 19, 1, 2, '2025-09-20', '15:00:00', 'Completed', 'Group Stage–Elimination (B)', 5, 1),
(22, 25, 2, 1, '2025-09-19', '15:00:00', 'Completed', 'Group Stage–Elimination (C)', 5, 1),
(14, 23, 2, 0, '2025-09-20', '18:00:00', 'Completed', 'Group Stage–Elimination (D)', 5, 1),
(6, 21, 2, 0, '2025-09-21', '17:20:00', 'Completed', 'Group Stage–Decider (A)', 5, 1),
(18, 19, 2, 0, '2025-09-22', '17:15:00', 'Completed', 'Group Stage–Decider (B)', 5, 1),
(16, 22, 2, 0, '2025-09-21', '15:00:00', 'Completed', 'Group Stage–Decider (C)', 5, 1),
(20, 14, 0, 2, '2025-09-22', '15:00:00', 'Completed', 'Group Stage–Decider (D)', 5, 1),
(15, 6, 2, 0, '2025-09-26', '17:35:00', 'Completed', 'Playoffs–Upper Quarterfinals', 5, 1),
(1, 18, 0, 2, '2025-09-26', '15:00:00', 'Completed', 'Playoffs–Upper Quarterfinals', 5, 1),
(2, 16, 2, 1, '2025-09-25', '18:50:00', 'Completed', 'Playoffs–Upper Quarterfinals', 5, 1),
(17, 14, 2, 1, '2025-09-25', '15:00:00', 'Completed', 'Playoffs–Upper Quarterfinals', 5, 1),
(1, 6, 2, 1, '2025-09-27', '15:00:00', 'Completed', 'Playoffs–Lower Round 1', 5, 1),
(16, 14, 2, 1, '2025-09-27', '18:20:00', 'Completed', 'Playoffs–Lower Round 1', 5, 1),
(18, 16, 1, 2, '2025-09-29', '18:20:00', 'Completed', 'Playoffs–Lower Round 2', 5, 1),
(17, 1, 2, 1, '2025-09-29', '15:00:00', 'Completed', 'Playoffs–Lower Round 2', 5, 1),
(18, 15, 1, 2, '2025-09-28', '18:20:00', 'Completed', 'Playoffs–Upper Semifinals', 5, 1),
(2, 17, 2, 1, '2025-09-28', '15:00:00', 'Completed', 'Playoffs–Upper Semifinals', 5, 1),
(16, 17, 2, 0, '2025-10-03', '15:35:00', 'Completed', 'Playoffs–Lower Round 3', 5, 1),
(2, 15, 0, 2, '2025-10-03', '13:00:00', 'Completed', 'Playoffs–Upper Final', 5, 1);

INSERT INTO matches (team_1, team_2, score_team_1, score_team_2, date, hour, status, best_of, event_stage, event_id, post_author)
VALUES
(2, 16, 3, 1, '2025-10-04', '13:00:00', 'Completed', 5, 'Playoffs–Lower Final', 5, 1),
(15, 2, 3, 2, '2025-10-05', '13:00:00', 'Completed', 5, 'Playoffs–Grand Final', 5, 1);

INSERT INTO predictions (user_id, match_id, score_team_1_pred, score_team_2_pred)
VALUES
(2, 1, 2, 1),
(2, 2, 1, 2),
(2, 5, 2, 0),
(2, 6, 0, 2),
(2, 9, 2, 1);

INSERT INTO predictions (user_id, match_id, score_team_1_pred, score_team_2_pred, points_awarded)
VALUES
(2, 43, 3, 2, 10);