<?php

require_once __DIR__ . '/BaseDAO.php';

class PredictionDAO extends BaseDAO
{
    public function createPrediction(int $userId, int $matchId, int $score1, int $score2): bool
    {
        $sql = "INSERT INTO predictions
                    (user_id, match_id, score_team_1_pred, score_team_2_pred)
                VALUES (:user_id, :match_id, :score1, :score2)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':user_id' => $userId,
            ':match_id' => $matchId,
            ':score1' => $score1,
            ':score2' => $score2,
        ]);
    }

    public function getPredictionForUserAndMatch(int $userId, int $matchId): ?array
    {
        $sql = "SELECT * FROM predictions
                WHERE user_id = :user_id AND match_id = :match_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'  => $userId,
            ':match_id' => $matchId,
        ]);

        $prediction = $stmt->fetch();
        return $prediction ?: null;
    }

    public function getPredictionsByUser(int $userId): array
    {
        $sql = "SELECT p.*, 
                       m.date, m.hour,
                       t1.name AS team_1_name,
                       t2.name AS team_2_name
                FROM predictions p
                JOIN matches m ON p.match_id = m.id
                JOIN teams t1 ON m.team_1 = t1.id
                LEFT JOIN teams t2 ON m.team_2 = t2.id
                WHERE p.user_id = :user_id
                ORDER BY m.date DESC, m.hour DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
}