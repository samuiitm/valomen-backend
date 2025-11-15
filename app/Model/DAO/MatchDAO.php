<?php

require_once __DIR__ . '/BaseDAO.php';

class MatchDAO extends BaseDAO
{
    public function getMatchById(int $id): ?array
    {
        $sql = "SELECT m.*, 
                       t1.name AS team_1_name, t1.country AS team_1_country,
                       t2.name AS team_2_name, t2.country AS team_2_country,
                       e.name AS event_name, e.logo AS event_logo
                FROM matches m
                JOIN teams t1 ON m.team_1 = t1.id
                LEFT JOIN teams t2 ON m.team_2 = t2.id
                JOIN events e ON m.event_id = e.id
                WHERE m.id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $match = $stmt->fetch();

        return $match ?: null;
    }

    public function getUpcomingMatches(): array
    {
        $sql = "SELECT m.*, 
                       t1.name AS team_1_name, t1.country AS team_1_country,
                       t2.name AS team_2_name, t2.country AS team_2_country,
                       e.name AS event_name, e.logo AS event_logo
                FROM matches m
                JOIN teams t1 ON m.team_1 = t1.id
                LEFT JOIN teams t2 ON m.team_2 = t2.id
                JOIN events e ON m.event_id = e.id
                WHERE (m.score_team_1 IS NULL AND m.score_team_2 IS NULL)
                  AND (m.date > CURDATE() 
                       OR (m.date = CURDATE() AND m.hour >= CURTIME()))
                ORDER BY m.date ASC, m.hour ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getFinishedMatches(): array
    {
        $sql = "SELECT m.*, 
                       t1.name AS team_1_name, t1.country AS team_1_country,
                       t2.name AS team_2_name, t2.country AS team_2_country,
                       e.name AS event_name, e.logo AS event_logo
                FROM matches m
                JOIN teams t1 ON m.team_1 = t1.id
                LEFT JOIN teams t2 ON m.team_2 = t2.id
                JOIN events e ON m.event_id = e.id
                WHERE m.score_team_1 IS NOT NULL 
                  AND m.score_team_2 IS NOT NULL
                ORDER BY m.date DESC, m.hour DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}